<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\TextInput;

use Closure;

trait ConfiguresTextInputAdvanced
{
    public function counterLimit(int|Closure|null $limit): static
    {
        return $this->textInputOption('counterLimit', $limit);
    }

    public function enforceCounterLimit(bool|Closure $condition = true): static
    {
        return $this->textInputOption('enforceCounterLimit', $condition);
    }

    public function counterTemplate(string|Closure|null $template): static
    {
        return $this->textInputOption('counterTemplate', $template);
    }

    public function counterLocale(string|Closure|null $locale): static
    {
        return $this->textInputOption('counterLocale', $locale);
    }

    public function clearConfirmation(bool|Closure $condition = true): static
    {
        return $this->textInputOption('clearConfirmation', $condition);
    }

    public function clearConfirmationLabel(string|Closure $label): static
    {
        return $this->textInputOption('clearConfirmationLabel', $label);
    }

    public function copyMode(string|Closure $mode): static
    {
        return $this->textInputOption('copyMode', $mode);
    }

    public function copyTransform(string|Closure $transform): static
    {
        return $this->textInputOption('copyTransform', $transform);
    }

    public function copyErrorLabel(string|Closure $label): static
    {
        return $this->textInputOption('copyErrorLabel', $label);
    }

    public function revealTimeout(int|Closure|null $milliseconds): static
    {
        return $this->textInputOption('revealTimeout', $milliseconds);
    }

    /** @param array<string, int>|Closure $requirements */
    public function passwordRequirements(array|Closure $requirements): static
    {
        return $this->textInputOption('passwordRequirements', $requirements);
    }

    public function passwordRequirementsVisible(bool|Closure $condition = true): static
    {
        return $this->textInputOption('passwordRequirementsVisible', $condition);
    }

    public function passwordStrengthAlgorithm(string|Closure $algorithm): static
    {
        return $this->textInputOption('passwordStrengthAlgorithm', $algorithm);
    }

    /** @param list<string>|Closure $order */
    public function toolsOrder(array|Closure $order): static
    {
        return $this->textInputOption('toolsOrder', $order);
    }

    public function toolsVisibility(string|Closure $visibility): static
    {
        return $this->textInputOption('toolsVisibility', $visibility);
    }

    public function speechDictationTimeout(int|Closure $milliseconds): static
    {
        return $this->textInputOption('speechDictationTimeout', $milliseconds);
    }

    /** @param array<string, string>|Closure $messages */
    public function speechDictationMessages(array|Closure $messages): static
    {
        return $this->textInputOption('speechDictationMessages', $messages);
    }

    public function normalizeOnBlur(string|Closure $normalization): static
    {
        return $this->textInputOption('normalizeOnBlur', $normalization);
    }

    public function pasteMode(string|Closure $mode): static
    {
        return $this->textInputOption('pasteMode', $mode);
    }

    public function unicodeNormalization(string|Closure $normalization): static
    {
        return $this->textInputOption('unicodeNormalization', $normalization);
    }

    public function readingTime(bool|Closure $condition = true): static
    {
        return $this->textInputOption('readingTime', $condition);
    }

    public function readingWordsPerMinute(int|Closure $words): static
    {
        return $this->textInputOption('readingWordsPerMinute', $words);
    }

    public function suggestionLimit(int|Closure $limit): static
    {
        return $this->textInputOption('suggestionLimit', $limit);
    }

    public function suggestionMinLength(int|Closure $length): static
    {
        return $this->textInputOption('suggestionMinLength', $length);
    }

    public function suggestionPlacement(string|Closure $placement): static
    {
        return $this->textInputOption('suggestionPlacement', $placement);
    }

    public function suggestionDescriptionPosition(string|Closure $position): static
    {
        return $this->textInputOption('suggestionDescriptionPosition', $position);
    }

    public function suggestionDebounce(int|Closure $milliseconds): static
    {
        return $this->textInputOption('suggestionDebounce', $milliseconds);
    }

    public function draftKey(string|Closure|null $key): static
    {
        return $this->textInputOption('draftKey', $key);
    }

    public function draftStorage(string|Closure $storage): static
    {
        return $this->textInputOption('draftStorage', $storage);
    }

    public function draftLifetime(int|Closure $seconds): static
    {
        return $this->textInputOption('draftLifetime', $seconds);
    }
}
