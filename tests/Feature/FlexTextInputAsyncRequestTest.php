<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Tests\Feature;

use Bjanczak\FilamentFlexFields\Support\Playground\TextInput\TextInputDemo;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Bjanczak\FilamentFlexFields\Tests\Support\WithNativeFilamentPartials;
use Bjanczak\FilamentFlexFields\Tests\TestCase;
use Filament\Support\Livewire\Partials\DataStoreOverride;
use Filament\Support\Livewire\Partials\PartialsComponentHook;
use Livewire\ComponentHookRegistry;
use Livewire\Livewire;
use Livewire\Mechanisms\DataStore;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

// Pest's CLI does not set the Composer entry used by PHPUnit's child process.
if (! defined('PHPUNIT_COMPOSER_INSTALL')) {
    define('PHPUNIT_COMPOSER_INSTALL', $GLOBALS['_composer_autoload_path'] ?? dirname(__DIR__, 2).'/vendor/autoload.php');
}

/** Filament partial hooks have static boot state; each request fixture starts clean. */
#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class FlexTextInputAsyncRequestTest extends TestCase
{
    use WithNativeFilamentPartials;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->singleton(DataStore::class, DataStoreOverride::class);
        TestableTranslatableForm::$formSchema = [];
    }

    public function test_async_suggestions_do_not_render_the_focused_field_with_a_bundled_update(): void
    {
        $reply = $this->request('async_suggestions', 'getTextInputSuggestionsForJs', ['search' => 'a'], 'a');
        self::assertNotEmpty($reply);
        self::assertSame('Amsterdam', $reply[0]['value']);
    }

    public function test_async_trailing_descriptions_keep_metadata_without_rerendering_the_focused_field(): void
    {
        $reply = $this->request('async_suggestions_trailing', 'getTextInputSuggestionsForJs', ['search' => 'ams'], 'ams');
        self::assertSame([['value' => 'Amsterdam', 'label' => 'Amsterdam', 'description' => 'Netherlands', 'disabled' => false]], $reply);
    }

    public function test_async_verification_does_not_render_the_focused_field_with_a_bundled_update(): void
    {
        $reply = $this->request('verification', 'verifyTextInputForJs', ['value' => 'audit@example.test'], 'audit@example.test');
        self::assertSame(['state' => 'success', 'message' => 'Valid email format'], $reply);
    }

    /** @param array<string, string> $arguments */
    private function request(string $name, string $method, array $arguments, string $value): array
    {
        $scenario = collect(TextInputDemo::groups()['assistance'])->firstWhere('key', 'flex_text_input__'.$name);
        TestableTranslatableForm::$formSchema = [TextInputDemo::field($scenario)];
        $test = Livewire::test(TestableTranslatableForm::class)->call('enableSchemaStateUpdateHooksForTesting');
        self::assertNotNull(ComponentHookRegistry::getHook($test->instance(), PartialsComponentHook::class));
        $key = $test->instance()->getSchema('form')->getComponents()[0]->getKey();
        $test->update(calls: [['method' => 'callSchemaComponentMethod', 'params' => [$key, $method, $arguments], 'path' => '']], updates: ['data.'.$scenario['key'] => $value]);
        self::assertArrayNotHasKey('html', $test->effects);
        self::assertSame([], $test->effects['partials'] ?? []);

        return $test->effects['returns'][0];
    }
}
