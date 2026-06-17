<?php

declare(strict_types=1);

use Bjanczak\FilamentFlexFields\Support\Playground\FlexFileUploadPlayground;
use Bjanczak\FilamentFlexFields\Tests\Support\TestableTranslatableForm;
use Livewire\Livewire;

it('renders a balanced html tree for the file upload playground section', function () {
    TestableTranslatableForm::$formSchema = app(FlexFileUploadPlayground::class)->components();

    $html = Livewire::test(TestableTranslatableForm::class)->html(false);

    expect(substr_count($html, '<div'))
        ->toBe(substr_count($html, '</div>'));
});
