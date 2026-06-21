<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components;

use BackedEnum;
use Bjanczak\FilamentFlexFields\Concerns\HasControlSize;
use Bjanczak\FilamentFlexFields\Concerns\HasFieldFocusOutline;
use Bjanczak\FilamentFlexFields\Enums\BarcodeFormat;
use Bjanczak\FilamentFlexFields\Support\Barcode\BarcodeStateNormalizer;
use Bjanczak\FilamentFlexFields\Support\Barcode\BarcodeValidator;
use Bjanczak\FilamentFlexFields\Support\FlexFieldAssets;
use Bjanczak\FilamentFlexFields\Support\GravityIcon;
use Bjanczak\FilamentFlexFields\Support\Translations;
use Closure;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\Field;
use Illuminate\Contracts\Support\Htmlable;
use InvalidArgumentException;

class BarcodeScannerField extends Field
{
    use CanBeReadOnly;
    use HasControlSize;
    use HasFieldFocusOutline;
    use HasPlaceholder;

    protected string $view = 'filament-flex-fields::forms.components.barcode-scanner-field';

    protected string|Closure $variant = 'primary';

    /**
     * @var list<string|BarcodeFormat>|Closure|null
     */
    protected array|Closure|null $supportedFormats = null;

    protected bool|Closure $continuous = false;

    protected bool|Closure $beepOnScan = true;

    protected bool|Closure $autoSubmit = false;

    /** @var 'environment'|'user'|Closure */
    protected string|Closure $cameraFacing = 'environment';

    protected int|Closure $scanDelay = 750;

    protected int|Closure $scanInterval = 120;

    protected bool $scanIntervalFromFps = false;

    protected bool|Closure $allowCameraSwitch = true;

    protected string|Closure|null $preferredDeviceId = null;

    protected bool|Closure $storeDetectedFormat = false;

    protected bool|Closure $pauseWhenHidden = true;

    protected bool|Closure $allowManualInput = true;

    protected string|Closure $scanButtonLabel = '';

    protected string|Closure $modalHeading = '';

    protected bool|Closure $validateChecksum = false;

    protected string|BackedEnum|Htmlable|Closure|null $scanIcon = null;

    protected Closure|string|null $barcodeRule = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->placeholder(Translations::get('filament-flex-fields::default.barcode_scanner.placeholder'));
        $this->scanButtonLabel(Translations::get('filament-flex-fields::default.barcode_scanner.scan'));
        $this->modalHeading(Translations::get('filament-flex-fields::default.barcode_scanner.modal_heading'));

        $this->afterStateHydrated(function (BarcodeScannerField $component, mixed $state): void {
            $component->state(BarcodeStateNormalizer::dehydrate(
                $state,
                $component->shouldStoreDetectedFormat(),
            ));
        });

        $this->dehydrateStateUsing(function (BarcodeScannerField $component, mixed $state): string|array|null {
            return BarcodeStateNormalizer::dehydrate(
                $state,
                $component->shouldStoreDetectedFormat(),
            );
        });

        $this->rule(function (BarcodeScannerField $component): Closure {
            return function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                if ($component->isRequired() && BarcodeStateNormalizer::isEmpty($value)) {
                    $fail(__('validation.required', ['attribute' => $component->getLabel()]));

                    return;
                }

                if (BarcodeStateNormalizer::isEmpty($value)) {
                    return;
                }

                $extractedValue = BarcodeStateNormalizer::extractValue($value);

                if ($extractedValue === null) {
                    return;
                }

                $message = BarcodeValidator::validateValue(
                    $extractedValue,
                    $component->getSupportedFormats(),
                    $component->shouldValidateChecksum(),
                );

                if ($message !== null) {
                    $fail($message);

                    return;
                }

                $customRule = $component->getBarcodeRule();

                if ($customRule instanceof Closure) {
                    $customRule($attribute, $extractedValue, $fail);
                } elseif (is_string($customRule)) {
                    $validator = validator([$attribute => $extractedValue], [$attribute => $customRule]);

                    if ($validator->fails()) {
                        $fail($validator->errors()->first($attribute));
                    }
                }
            };
        });
    }

    public function variant(string|Closure $variant): static
    {
        $this->variant = $variant;

        return $this;
    }

    public function getVariant(): string
    {
        $variant = $this->evaluate($this->variant);

        if (! in_array($variant, ['primary', 'secondary', 'soft', 'flat', 'ghost'], true)) {
            throw new InvalidArgumentException("Invalid BarcodeScannerField variant [{$variant}].");
        }

        return $variant;
    }

    /**
     * @param  list<string|BarcodeFormat>|Closure|null  $formats
     */
    public function formats(array|Closure|null $formats): static
    {
        $this->supportedFormats = $formats;

        return $this;
    }

    /**
     * @param  list<string|BarcodeFormat>|Closure|null  $formats
     */
    public function supportedFormats(array|Closure|null $formats): static
    {
        return $this->formats($formats);
    }

    /**
     * @return list<BarcodeFormat>
     */
    public function getSupportedFormats(): array
    {
        $formats = $this->evaluate($this->supportedFormats);

        if ($formats === null) {
            return BarcodeFormat::cases();
        }

        return BarcodeFormat::normalizeList($formats);
    }

    public function continuous(bool|Closure $condition = true): static
    {
        $this->continuous = $condition;

        return $this;
    }

    public function isContinuous(): bool
    {
        return (bool) $this->evaluate($this->continuous);
    }

    public function beepOnScan(bool|Closure $condition = true): static
    {
        $this->beepOnScan = $condition;

        return $this;
    }

    public function shouldBeepOnScan(): bool
    {
        return (bool) $this->evaluate($this->beepOnScan);
    }

    public function autoSubmit(bool|Closure $condition = true): static
    {
        $this->autoSubmit = $condition;

        return $this;
    }

    public function shouldAutoSubmit(): bool
    {
        return (bool) $this->evaluate($this->autoSubmit);
    }

    /**
     * @param  'environment'|'user'|Closure  $facing
     */
    public function cameraFacing(string|Closure $facing): static
    {
        $this->cameraFacing = $facing;

        return $this;
    }

    /**
     * @return 'environment'|'user'
     */
    public function getCameraFacing(): string
    {
        $facing = $this->evaluate($this->cameraFacing);

        if (! in_array($facing, ['environment', 'user'], true)) {
            throw new InvalidArgumentException("Invalid BarcodeScannerField camera facing [{$facing}].");
        }

        return $facing;
    }

    public function scanDelay(int|Closure $milliseconds): static
    {
        $this->scanDelay = $milliseconds;

        return $this;
    }

    public function getScanDelay(): int
    {
        return max(0, (int) $this->evaluate($this->scanDelay));
    }

    public function scanInterval(int|Closure $milliseconds): static
    {
        $this->scanInterval = $milliseconds;
        $this->scanIntervalFromFps = false;

        return $this;
    }

    public function decodeFps(int|Closure $fps): static
    {
        $this->scanInterval = $fps;
        $this->scanIntervalFromFps = true;

        return $this;
    }

    public function getScanInterval(): int
    {
        if ($this->scanIntervalFromFps) {
            $fps = max(1, (int) $this->evaluate($this->scanInterval));

            return max(50, min(2000, (int) round(1000 / $fps)));
        }

        return max(50, min(2000, (int) $this->evaluate($this->scanInterval)));
    }

    public function allowCameraSwitch(bool|Closure $condition = true): static
    {
        $this->allowCameraSwitch = $condition;

        return $this;
    }

    public function allowsCameraSwitch(): bool
    {
        return (bool) $this->evaluate($this->allowCameraSwitch);
    }

    public function preferredDeviceId(string|Closure|null $deviceId): static
    {
        $this->preferredDeviceId = $deviceId;

        return $this;
    }

    public function getPreferredDeviceId(): ?string
    {
        $deviceId = $this->evaluate($this->preferredDeviceId);

        if (! is_string($deviceId)) {
            return null;
        }

        $trimmed = trim($deviceId);

        return $trimmed === '' ? null : $trimmed;
    }

    public function storeDetectedFormat(bool|Closure $condition = false): static
    {
        $this->storeDetectedFormat = $condition;

        return $this;
    }

    public function shouldStoreDetectedFormat(): bool
    {
        return (bool) $this->evaluate($this->storeDetectedFormat);
    }

    public function pauseWhenHidden(bool|Closure $condition = true): static
    {
        $this->pauseWhenHidden = $condition;

        return $this;
    }

    public function shouldPauseWhenHidden(): bool
    {
        return (bool) $this->evaluate($this->pauseWhenHidden);
    }

    public function allowManualInput(bool|Closure $condition = true): static
    {
        $this->allowManualInput = $condition;

        return $this;
    }

    public function allowsManualInput(): bool
    {
        return (bool) $this->evaluate($this->allowManualInput);
    }

    public function scanButtonLabel(string|Closure $label): static
    {
        $this->scanButtonLabel = $label;

        return $this;
    }

    public function getScanButtonLabel(): string
    {
        return $this->evaluate($this->scanButtonLabel);
    }

    public function modalHeading(string|Closure $heading): static
    {
        $this->modalHeading = $heading;

        return $this;
    }

    public function getModalHeading(): string
    {
        return $this->evaluate($this->modalHeading);
    }

    public function validateChecksum(bool|Closure $condition = true): static
    {
        $this->validateChecksum = $condition;

        return $this;
    }

    public function shouldValidateChecksum(): bool
    {
        return (bool) $this->evaluate($this->validateChecksum);
    }

    public function scanIcon(string|BackedEnum|Htmlable|Closure|null $icon): static
    {
        $this->scanIcon = $icon;

        return $this;
    }

    public function getScanIcon(): string|BackedEnum|Htmlable
    {
        return $this->evaluate($this->scanIcon) ?? GravityIcon::make('qr-code');
    }

    public function barcodeRule(string|Closure $rule): static
    {
        $this->barcodeRule = $rule;

        return $this;
    }

    public function getBarcodeRule(): Closure|string|null
    {
        return $this->barcodeRule;
    }

    public function getBeepUrl(): string
    {
        return FlexFieldAssets::barcodeScanBeepUrl();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAlpineConfiguration(): array
    {
        return [
            'beepUrl' => $this->getBeepUrl(),
            'supportedFormats' => array_map(
                fn (BarcodeFormat $format): string => $format->value,
                $this->getSupportedFormats(),
            ),
            'continuous' => $this->isContinuous(),
            'beepOnScan' => $this->shouldBeepOnScan(),
            'autoSubmit' => $this->shouldAutoSubmit(),
            'cameraFacing' => $this->getCameraFacing(),
            'scanDelay' => $this->getScanDelay(),
            'scanInterval' => $this->getScanInterval(),
            'allowCameraSwitch' => $this->allowsCameraSwitch(),
            'preferredDeviceId' => $this->getPreferredDeviceId(),
            'storeDetectedFormat' => $this->shouldStoreDetectedFormat(),
            'pauseWhenHidden' => $this->shouldPauseWhenHidden(),
            'allowManualInput' => $this->allowsManualInput(),
            'validateChecksum' => $this->shouldValidateChecksum(),
            'labels' => [
                'scan' => $this->getScanButtonLabel(),
                'modalHeading' => $this->getModalHeading(),
                'close' => __('filament-flex-fields::default.barcode_scanner.close'),
                'torchOn' => __('filament-flex-fields::default.barcode_scanner.torch_on'),
                'torchOff' => __('filament-flex-fields::default.barcode_scanner.torch_off'),
                'switchCamera' => __('filament-flex-fields::default.barcode_scanner.switch_camera'),
                'permissionDenied' => __('filament-flex-fields::default.barcode_scanner.permission_denied'),
                'cameraUnavailable' => __('filament-flex-fields::default.barcode_scanner.camera_unavailable'),
                'scanHint' => __('filament-flex-fields::default.barcode_scanner.scan_hint'),
                'scanSuccess' => __('filament-flex-fields::default.barcode_scanner.scan_success'),
                'engineNative' => __('filament-flex-fields::default.barcode_scanner.engine_native'),
                'engineZxing' => __('filament-flex-fields::default.barcode_scanner.engine_zxing'),
                'manualOnly' => __('filament-flex-fields::default.barcode_scanner.manual_only'),
                'validation' => [
                    'unrecognized' => __('filament-flex-fields::default.barcode_scanner.validation.unrecognized'),
                    'checksum' => __('filament-flex-fields::default.barcode_scanner.validation.checksum'),
                ],
            ],
        ];
    }

    /**
     * @return array<string, bool>
     */
    public function getWrapperClasses(): array
    {
        return [
            'fff-barcode-scanner-field' => true,
            'fff-flex-text-input-field' => true,
            'fff-barcode-scanner-field--'.$this->getSize() => true,
            'fff-flex-text-input-field--'.$this->getSize() => true,
            'fff-barcode-scanner-field--'.$this->getVariant() => true,
            'fff-flex-text-input-field--'.$this->getVariant() => true,
        ];
    }
}
