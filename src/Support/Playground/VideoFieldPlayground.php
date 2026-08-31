<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\VideoField;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class VideoFieldPlayground
{
    private const DEMO_VIDEO = 'https://avtshare01.rz.tu-ilmenau.de/avt-vqdb-uhd-1/test_1/segments/bigbuck_bunny_8bit_15000kbps_1080p_60.0fps_h264.mp4';

    private const DEMO_YOUTUBE = 'https://www.youtube.com/watch?v=aqz-KE-bpKQ';

    private const DEMO_POSTER = 'https://peach.blender.org/wp-content/uploads/title_anouncement.jpg?x11217';

    /**
     * @return array<string, mixed>
     */
    public function defaultState(): array
    {
        return [
            'video__basic' => self::DEMO_VIDEO,
            'video__youtube' => self::DEMO_YOUTUBE,
            'video__fullwidth' => self::DEMO_VIDEO,
            'video__square' => self::DEMO_VIDEO,
            'video__cinema' => self::DEMO_VIDEO,
            'video__poster_only' => null,
            'video__sm' => self::DEMO_VIDEO,
            'video__lg' => self::DEMO_VIDEO,
            'video__compact' => self::DEMO_VIDEO,
            'video__advanced' => self::DEMO_VIDEO,
            'video__speed' => self::DEMO_VIDEO,
            'video__cast' => self::DEMO_VIDEO,
            'video__quality' => self::DEMO_VIDEO,
            'video__compact_advanced' => self::DEMO_VIDEO,
            'video__lg_advanced' => self::DEMO_VIDEO,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function demoQualityOptions(): array
    {
        return [
            ['key' => 'auto', 'label' => 'Auto (1080p)', 'src' => self::DEMO_VIDEO, 'default' => true],
            ['key' => '720', 'label' => '720p', 'src' => self::DEMO_VIDEO, 'height' => 720],
            ['key' => '540', 'label' => '540p', 'src' => self::DEMO_VIDEO, 'height' => 540],
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Video field')
                ->description('Native HTML5 MP4 with glass controls, configurable speed/quality/cast/AirPlay/PiP, plus a lightweight YouTube facade.')
                ->extraAttributes(['class' => 'fff-playground-section'])
                ->schema([
                    VideoField::make('video__basic')
                        ->label('Featured clip')
                        ->subtitle('S1, E2 · Deuce')
                        ->title('Your Friends & Neighbors')
                        ->poster(self::DEMO_POSTER)
                        ->ratio('16:9')
                        ->fullWidth()
                        ->pictureInPictureable()
                        ->helperText('Default layout — icon play/pause, scrubbing with frame preview, PiP and fullscreen.')
                        ->columnSpanFull(),
                    VideoField::make('video__advanced')
                        ->label('Full enterprise toolbar')
                        ->subtitle('Settings · Cast · AirPlay')
                        ->title('Enterprise playback')
                        ->poster(self::DEMO_POSTER)
                        ->ratio('16:9')
                        ->fullWidth()
                        ->playbackRates([0.5, 0.75, 1, 1.25, 1.5, 2])
                        ->qualityOptions($this->demoQualityOptions())
                        ->castable()
                        ->airPlayable()
                        ->pictureInPictureable()
                        ->helperText('Gear menu (speed + quality), cast, AirPlay, PiP, tooltips and touch-friendly scrubbing.')
                        ->columnSpanFull(),
                    VideoField::make('video__compact')
                        ->label('Compact controls')
                        ->poster(self::DEMO_POSTER)
                        ->ratio('16:9')
                        ->fullWidth()
                        ->compactControls()
                        ->playbackRates(true)
                        ->pictureInPictureable()
                        ->helperText('Single-line toolbar — on touch devices volume opens as a popover instead of hover expand.')
                        ->columnSpanFull(),
                    VideoField::make('video__youtube')
                        ->label('YouTube facade')
                        ->subtitle('Streaming')
                        ->title('Big Buck Bunny')
                        ->ratio('16:9')
                        ->fullWidth()
                        ->helperText('YouTube URL auto-detected — thumbnail facade, iframe loads on play.')
                        ->columnSpanFull(),
                    Section::make('Configurable options')
                        ->description('Individual feature toggles — useful when you only need speed, cast, or quality switching.')
                        ->compact()
                        ->schema([
                            Grid::make(['default' => 1, 'lg' => 2])
                                ->extraAttributes(['class' => 'fff-playground-variants'])
                                ->schema([
                                    VideoField::make('video__speed')
                                        ->label('Playback speed only')
                                        ->poster(self::DEMO_POSTER)
                                        ->ratio('16:9')
                                        ->playbackRates([0.5, 0.75, 1, 1.25, 1.5, 2])
                                        ->helperText('Settings gear shows only the speed submenu.'),
                                    VideoField::make('video__quality')
                                        ->label('Quality switching only')
                                        ->poster(self::DEMO_POSTER)
                                        ->ratio('16:9')
                                        ->qualityOptions($this->demoQualityOptions())
                                        ->helperText('Provide multiple MP4 sources — settings gear shows only quality rows.'),
                                    VideoField::make('video__cast')
                                        ->label('Cast & AirPlay')
                                        ->poster(self::DEMO_POSTER)
                                        ->ratio('16:9')
                                        ->castable()
                                        ->airPlayable()
                                        ->pictureInPictureable()
                                        ->helperText('Cast (Chrome Remote Playback) and AirPlay (Safari) buttons alongside PiP.'),
                                    VideoField::make('video__compact_advanced')
                                        ->label('Compact + all options')
                                        ->poster(self::DEMO_POSTER)
                                        ->ratio('16:9')
                                        ->compactControls()
                                        ->playbackRates(true)
                                        ->qualityOptions($this->demoQualityOptions())
                                        ->castable()
                                        ->airPlayable()
                                        ->pictureInPictureable()
                                        ->helperText('Stress-test compact layout with the full end-toolbar on narrow widths.'),
                                ]),
                        ]),
                    Section::make('Ratios & placeholders')
                        ->compact()
                        ->schema([
                            Grid::make(['default' => 1, 'lg' => 2])
                                ->extraAttributes(['class' => 'fff-playground-variants'])
                                ->schema([
                                    VideoField::make('video__fullwidth')
                                        ->label('Full width')
                                        ->title('Big Buck Bunny')
                                        ->poster(self::DEMO_POSTER)
                                        ->ratio('16:9')
                                        ->fullWidth(),
                                    VideoField::make('video__square')
                                        ->label('Square ratio')
                                        ->title('Square crop')
                                        ->poster(self::DEMO_POSTER)
                                        ->ratio('1:1'),
                                    VideoField::make('video__cinema')
                                        ->label('Cinematic ratio')
                                        ->title('Ultra-wide')
                                        ->poster(self::DEMO_POSTER)
                                        ->ratio('21:9'),
                                    VideoField::make('video__poster_only')
                                        ->label('Poster placeholder')
                                        ->placeholder(self::DEMO_POSTER)
                                        ->ratio('16:9')
                                        ->helperText('Shown when no video URL is stored in state.'),
                                ]),
                        ]),
                    Section::make('Sizes')
                        ->compact()
                        ->schema([
                            Grid::make(['default' => 1, 'lg' => 2])
                                ->extraAttributes(['class' => 'fff-playground-variants'])
                                ->schema([
                                    VideoField::make('video__sm')
                                        ->label('Small')
                                        ->size('sm')
                                        ->poster(self::DEMO_POSTER)
                                        ->ratio('16:9')
                                        ->playbackRates([0.75, 1, 1.25])
                                        ->pictureInPictureable(),
                                    VideoField::make('video__lg')
                                        ->label('Large')
                                        ->size('lg')
                                        ->poster(self::DEMO_POSTER)
                                        ->ratio('16:9')
                                        ->pictureInPictureable(),
                                    VideoField::make('video__lg_advanced')
                                        ->label('Large + full toolbar')
                                        ->size('lg')
                                        ->poster(self::DEMO_POSTER)
                                        ->ratio('16:9')
                                        ->playbackRates(true)
                                        ->qualityOptions($this->demoQualityOptions())
                                        ->castable()
                                        ->airPlayable()
                                        ->pictureInPictureable()
                                        ->helperText('Large size with every end-toolbar control — playground grid stays at two columns max so buttons fit.'),
                                    VideoField::make('video__native')
                                        ->label('Native controls')
                                        ->src(self::DEMO_VIDEO)
                                        ->poster(self::DEMO_POSTER)
                                        ->ratio('16:9')
                                        ->nativeControls()
                                        ->controls(false),
                                ]),
                        ]),
                ]),
        ];
    }
}
