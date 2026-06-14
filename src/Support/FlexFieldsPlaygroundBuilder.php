<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

use Bjanczak\FilamentFlexFields\Support\Playground\AddressAutocompletePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\AudioFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ChoiceCardsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ChoiceCheckboxCardsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ColorSwatchPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CountryFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CoverCardPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CreditCardPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\CurrencyFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\DateTimeFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\DualListboxPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexChecklistPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexColorPickerPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexFileUploadPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexRadiolistPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexSliderPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexTextareaPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexTextInputPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FlexVerificationCodePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FocusOutlinePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\FormLayoutPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\ItemCardGroupPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\MapPickerPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\MatrixChoiceFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\NumberStepperPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\PhoneFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\PriceRangePlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\RatingColumnPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\RatingPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SegmentControlPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SegmentTabsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SelectPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SignatureFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SlugFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\SwitchPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TimezoneFieldPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TrackSliderPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TrafficSplitPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\TranslatableFieldsPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\UserColumnPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\UserSelectPlayground;
use Bjanczak\FilamentFlexFields\Support\Playground\VideoFieldPlayground;
use Filament\Schemas\Components\Component;

class FlexFieldsPlaygroundBuilder
{
    public function __construct(
        protected PhoneFieldPlayground $phoneFieldPlayground = new PhoneFieldPlayground,
        protected CountryFieldPlayground $countryFieldPlayground = new CountryFieldPlayground,
        protected TimezoneFieldPlayground $timezoneFieldPlayground = new TimezoneFieldPlayground,
        protected CurrencyFieldPlayground $currencyFieldPlayground = new CurrencyFieldPlayground,
        protected NumberStepperPlayground $numberStepperPlayground = new NumberStepperPlayground,
        protected ChoiceCardsPlayground $choiceCardsPlayground = new ChoiceCardsPlayground,
        protected ChoiceCheckboxCardsPlayground $choiceCheckboxCardsPlayground = new ChoiceCheckboxCardsPlayground,
        protected SegmentControlPlayground $segmentControlPlayground = new SegmentControlPlayground,
        protected SegmentTabsPlayground $segmentTabsPlayground = new SegmentTabsPlayground,
        protected TranslatableFieldsPlayground $translatableFieldsPlayground = new TranslatableFieldsPlayground,
        protected DateTimeFieldPlayground $dateTimeFieldPlayground = new DateTimeFieldPlayground,
        protected TrackSliderPlayground $trackSliderPlayground = new TrackSliderPlayground,
        protected FlexSliderPlayground $flexSliderPlayground = new FlexSliderPlayground,
        protected TrafficSplitPlayground $trafficSplitPlayground = new TrafficSplitPlayground,
        protected SwitchPlayground $switchPlayground = new SwitchPlayground,
        protected SelectPlayground $selectPlayground = new SelectPlayground,
        protected UserSelectPlayground $userSelectPlayground = new UserSelectPlayground,
        protected UserColumnPlayground $userColumnPlayground = new UserColumnPlayground,
        protected RatingPlayground $ratingPlayground = new RatingPlayground,
        protected RatingColumnPlayground $ratingColumnPlayground = new RatingColumnPlayground,
        protected DualListboxPlayground $dualListboxPlayground = new DualListboxPlayground,
        protected PriceRangePlayground $priceRangePlayground = new PriceRangePlayground,
        protected FlexTextareaPlayground $flexTextareaPlayground = new FlexTextareaPlayground,
        protected FlexTextInputPlayground $flexTextInputPlayground = new FlexTextInputPlayground,
        protected CreditCardPlayground $creditCardPlayground = new CreditCardPlayground,
        protected ColorSwatchPlayground $colorSwatchPlayground = new ColorSwatchPlayground,
        protected FlexColorPickerPlayground $flexColorPickerPlayground = new FlexColorPickerPlayground,
        protected FlexFileUploadPlayground $flexFileUploadPlayground = new FlexFileUploadPlayground,
        protected VideoFieldPlayground $videoFieldPlayground = new VideoFieldPlayground,
        protected AudioFieldPlayground $audioFieldPlayground = new AudioFieldPlayground,
        protected MapPickerPlayground $mapPickerPlayground = new MapPickerPlayground,
        protected AddressAutocompletePlayground $addressAutocompletePlayground = new AddressAutocompletePlayground,
        protected SlugFieldPlayground $slugFieldPlayground = new SlugFieldPlayground,
        protected SignatureFieldPlayground $signatureFieldPlayground = new SignatureFieldPlayground,
        protected FlexVerificationCodePlayground $flexVerificationCodePlayground = new FlexVerificationCodePlayground,
        protected FlexChecklistPlayground $flexChecklistPlayground = new FlexChecklistPlayground,
        protected FlexRadiolistPlayground $flexRadiolistPlayground = new FlexRadiolistPlayground,
        protected MatrixChoiceFieldPlayground $matrixChoiceFieldPlayground = new MatrixChoiceFieldPlayground,
        protected ItemCardGroupPlayground $itemCardGroupPlayground = new ItemCardGroupPlayground,
        protected FormLayoutPlayground $formLayoutPlayground = new FormLayoutPlayground,
        protected CoverCardPlayground $coverCardPlayground = new CoverCardPlayground,
        protected FocusOutlinePlayground $focusOutlinePlayground = new FocusOutlinePlayground,
    ) {}

    /**
     * @return list<Component>
     */
    public function build(): array
    {
        return [
            ...$this->focusOutlinePlayground->components(),
            ...$this->phoneFieldPlayground->components(),
            ...$this->countryFieldPlayground->components(),
            ...$this->timezoneFieldPlayground->components(),
            ...$this->currencyFieldPlayground->components(),
            ...$this->numberStepperPlayground->components(),
            ...$this->choiceCardsPlayground->components(),
            ...$this->choiceCheckboxCardsPlayground->components(),
            ...$this->segmentControlPlayground->components(),
            ...$this->segmentTabsPlayground->components(),
            ...$this->formLayoutPlayground->components(),
            ...$this->trackSliderPlayground->components(),
            ...$this->flexSliderPlayground->components(),
            ...$this->trafficSplitPlayground->components(),
            ...$this->switchPlayground->components(),
            ...$this->selectPlayground->components(),
            ...$this->userSelectPlayground->components(),
            ...$this->userColumnPlayground->components(),
            ...$this->ratingPlayground->components(),
            ...$this->ratingColumnPlayground->components(),
            ...$this->dualListboxPlayground->components(),
            ...$this->priceRangePlayground->components(),
            ...$this->flexTextareaPlayground->components(),
            ...$this->flexTextInputPlayground->components(),
            ...$this->slugFieldPlayground->components(),
            ...$this->translatableFieldsPlayground->components(),
            ...$this->dateTimeFieldPlayground->components(),
            ...$this->creditCardPlayground->components(),
            ...$this->colorSwatchPlayground->components(),
            ...$this->flexColorPickerPlayground->components(),
            ...$this->flexFileUploadPlayground->components(),
            ...$this->videoFieldPlayground->components(),
            ...$this->audioFieldPlayground->components(),
            ...$this->mapPickerPlayground->components(),
            ...$this->addressAutocompletePlayground->components(),
            ...$this->signatureFieldPlayground->components(),
            ...$this->flexVerificationCodePlayground->components(),
            ...$this->flexChecklistPlayground->components(),
            ...$this->flexRadiolistPlayground->components(),
            ...$this->matrixChoiceFieldPlayground->components(),
            ...$this->itemCardGroupPlayground->components(),
            ...$this->coverCardPlayground->components(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            ...$this->focusOutlinePlayground->defaultState(),
            ...$this->phoneFieldPlayground->defaultState(),
            ...$this->countryFieldPlayground->defaultState(),
            ...$this->timezoneFieldPlayground->defaultState(),
            ...$this->currencyFieldPlayground->defaultState(),
            ...$this->numberStepperPlayground->defaultState(),
            ...$this->choiceCardsPlayground->defaultState(),
            ...$this->choiceCheckboxCardsPlayground->defaultState(),
            ...$this->segmentControlPlayground->defaultState(),
            ...$this->segmentTabsPlayground->defaultState(),
            ...$this->formLayoutPlayground->defaultState(),
            ...$this->trackSliderPlayground->defaultState(),
            ...$this->flexSliderPlayground->defaultState(),
            ...$this->trafficSplitPlayground->defaultState(),
            ...$this->switchPlayground->defaultState(),
            ...$this->selectPlayground->defaultState(),
            ...$this->userSelectPlayground->defaultState(),
            ...$this->ratingPlayground->defaultState(),
            ...$this->dualListboxPlayground->defaultState(),
            ...$this->priceRangePlayground->defaultState(),
            ...$this->flexTextareaPlayground->defaultState(),
            ...$this->flexTextInputPlayground->defaultState(),
            ...$this->slugFieldPlayground->defaultState(),
            ...$this->translatableFieldsPlayground->defaultState(),
            ...$this->dateTimeFieldPlayground->defaultState(),
            ...$this->creditCardPlayground->defaultState(),
            ...$this->colorSwatchPlayground->defaultState(),
            ...$this->flexColorPickerPlayground->defaultState(),
            ...$this->flexFileUploadPlayground->defaultState(),
            ...$this->videoFieldPlayground->defaultState(),
            ...$this->audioFieldPlayground->defaultState(),
            ...$this->mapPickerPlayground->defaultState(),
            ...$this->addressAutocompletePlayground->defaultState(),
            ...$this->signatureFieldPlayground->defaultState(),
            ...$this->flexVerificationCodePlayground->defaultState(),
            ...$this->flexChecklistPlayground->defaultState(),
            ...$this->flexRadiolistPlayground->defaultState(),
            ...$this->matrixChoiceFieldPlayground->defaultState(),
            ...$this->itemCardGroupPlayground->defaultState(),
        ];
    }
}
