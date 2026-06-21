import fs from 'node:fs'
import path from 'node:path'

const packageRoot = path.resolve(import.meta.dirname, '..')
const baselinePath = path.join(packageRoot, 'phpstan-baseline.neon')

const pathsToRemove = new Set([
    'src/Data/FlexFieldSchema.php',
    'src/Enums/FieldCategory.php',
    'src/Enums/FieldType.php',
    'src/Enums/FieldTypeDefaults/FieldTypeDefaultConfigRegistry.php',
    'src/Support/FieldTypeRegistry.php',
    'src/Support/PhoneCountries.php',
    'src/Support/Barcode/BarcodeValidator.php',
    'src/Support/SocialLinks/SocialLinkValidator.php',
    'src/Filament/Forms/Components/Concerns/FlexFileUpload/FlexFileUploadDisplay.php',
    'src/Support/FormBuilder/Registry/FieldTypeHandlerRegistry.php',
    'src/Filament/Forms/Components/BarcodeScannerField.php',
    'src/Filament/Forms/Components/ScheduleField.php',
    'src/Support/ProgressColor.php',
])

const baseline = fs.readFileSync(baselinePath, 'utf8')
const lines = baseline.split('\n')
const output = []
let skipping = false
let before = 0

for (let index = 0; index < lines.length; index++) {
    const line = lines[index]

    if (line === '\t\t-') {
        before++

        const blockLines = [line]
        let pathLine = null

        for (let offset = 1; offset < 8 && index + offset < lines.length; offset++) {
            blockLines.push(lines[index + offset])

            if (lines[index + offset].startsWith('\t\t\tpath: ')) {
                pathLine = lines[index + offset].slice('\t\t\tpath: '.length)
            }
        }

        if (pathLine && pathsToRemove.has(pathLine)) {
            skipping = true
            index += blockLines.length - 1

            continue
        }
    }

    if (! skipping) {
        output.push(line)
    }

    if (skipping && line === '' && lines[index + 1]?.startsWith('\t\t-')) {
        skipping = false
    }
}

fs.writeFileSync(baselinePath, output.join('\n'))

const after = (output.join('\n').match(/^\t\t-$/gm) ?? []).length

console.log(`Removed ${before - after} baseline entries (${before} -> ${after}).`)
