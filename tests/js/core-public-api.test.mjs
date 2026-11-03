import assert from 'node:assert/strict'
import test from 'node:test'
import fs from 'node:fs'
import path from 'node:path'
import { execFileSync } from 'node:child_process'
import { capturePublicApi, comparePublicApi } from '../../scripts/core/public-api.mjs'

const root = path.resolve(import.meta.dirname, '../..')
const fixture = () => JSON.parse(fs.readFileSync(path.join(root, 'tests/fixtures/core-public-api-v1.json')))
const type = 'Bjanczak\\FilamentFlexFields\\Support\\Media\\FlexMedia'

test('relative PHP types use the declaring class across supported PHP versions without erasing late static binding', () => {
    const prefix = 'Bjanczak\\FilamentFlexFields\\Tests\\PublicApiVersions\\'
    const code = 'require $argv[1]; require $argv[2]; echo json_encode([publicApiClass($argv[3]), publicApiClass($argv[4])], JSON_THROW_ON_ERROR);'
    const [child, grandchild] = JSON.parse(execFileSync(process.env.PHP_BINARY ?? 'php', ['-r', code,
        path.join(root, 'scripts/core/export-php-api.php'), path.join(root, 'tests/fixtures/core-public-api-types.php'),
        prefix + 'ChildType', prefix + 'GrandchildType'], { cwd: root }))
    for (const declaration of [child, grandchild]) {
        assert.equal(declaration.methods.copy.parameters[0].type, '?' + prefix + 'ParentType')
        assert.equal(declaration.methods.copy.return, '?' + prefix + 'ChildType')
        assert.equal(declaration.properties.child.type, '?' + prefix + 'ChildType')
        assert.equal(declaration.properties.parent.type, '?' + prefix + 'ParentType')
        assert.equal(declaration.methods.combine.return, '(' + prefix + 'ChildType|DateTimeInterface|null)')
        assert.equal(declaration.methods.combine.parameters[0].type, declaration.methods.combine.return)
        assert.equal(declaration.methods.late.return, 'static')
    }
})

test('installed declarations and compiled public SDK facade match the reviewed compatibility fixture', async () => {
    const report = comparePublicApi(fixture(), await capturePublicApi(root))
    assert.equal(report.compatible, true)
    assert.deepEqual(report.breaking, [])
})

for (const [label, mutate] of [
    ['removed class', value => { delete value.php.classes[type] }],
    ['removed method', value => { delete value.php.classes[type].methods.store }],
    ['renamed named argument', value => { value.php.classes[type].methods.store.parameters[0].name = 'different' }],
    ['changed argument type', value => { value.php.classes[type].methods.store.parameters[0].type = 'string' }],
    ['changed return type', value => { value.php.classes[type].methods.store.return = 'void' }],
    ['changed default', value => { value.php.classes[type].methods.store.parameters[2].default = 'false' }],
    ['method visibility', value => { value.php.classes[type].methods.store.visibility = 'protected' }],
    ['SDK export removed', value => { delete value.sdk.exports.createResourceScope }],
    ['SDK protocol changed', value => { value.sdk.abi = 2 }],
    ['SDK required arity changed', value => { value.sdk.exports.createAssetSurface.requiredArguments = 2 }],
    ['new abstract implementation obligation', value => {
        const item = Object.values(value.php.classes).find(item => item.kind === 'interface')
        item.methods.newObligation = { abstract: true }
    }],
]) {
    test('detects ' + label, () => {
        const before = fixture(), after = structuredClone(before)
        mutate(after)
        assert.equal(comparePublicApi(before, after).compatible, false)
    })
}

test('additive concrete methods and new SDK exports remain visible without rewriting existing contracts', () => {
    const before = fixture(), after = structuredClone(before)
    after.php.classes[type].methods.additional = { abstract: false }
    after.sdk.exports.additional = { kind: 'function', requiredArguments: 0 }
    const report = comparePublicApi(before, after)
    assert.equal(report.compatible, true)
    assert.equal(report.additions.length, 2)
})

test('enum backing-value changes and new cases require explicit review', () => {
    const before = fixture(), after = structuredClone(before)
    const name = Object.keys(before.php.classes).find(name => before.php.classes[name].kind === 'enum')
    const member = Object.keys(after.php.classes[name].constants)[0]
    after.php.classes[name].constants[member].value.value = 'changed'
    assert.equal(comparePublicApi(before, after).compatible, false)
    const addition = structuredClone(before)
    addition.php.classes[name].constants.NewCase = { value: 'new' }
    assert.equal(comparePublicApi(before, addition).compatible, false)
})
