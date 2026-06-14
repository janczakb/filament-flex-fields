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
        ];
    }

    /**
     * @return list<Component>
     */
    public function components(): array
    {
        return [
            Section::make('Video field')
                ->description('Native HTML5 MP4 with Apple-style controls, plus lightweight YouTube facade (no heavy player library).')
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
                        ->helperText('Direct MP4 — default layout with title, progress times, PiP and pill play button.')
                        ->columnSpanFull(),
                    VideoField::make('video__compact')
                        ->label('Compact controls')
                        ->poster(self::DEMO_POSTER)
                        ->ratio('16:9')
                        ->fullWidth()
                        ->compactControls()
                        ->pictureInPictureable()
                        ->helperText('Single-line toolbar: play, hover volume, duration pill, PiP and fullscreen.')
                        ->columnSpanFull(),
                    VideoField::make('video__youtube')
                        ->label('YouTube facade')
                        ->subtitle('Streaming')
                        ->title('Big Buck Bunny')
                        ->ratio('16:9')
                        ->fullWidth()
                        ->helperText('YouTube URL auto-detected — thumbnail facade, iframe loads on play. Zero extra JS.')
                        ->columnSpanFull(),
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
                    Grid::make(['default' => 1, 'sm' => 2, 'lg' => 3])
                        ->extraAttributes(['class' => 'fff-playground-variants'])
                        ->schema([
                            VideoField::make('video__sm')
                                ->label('Small')
                                ->size('sm')
                                ->poster(self::DEMO_POSTER)
                                ->ratio('16:9'),
                            VideoField::make('video__lg')
                                ->label('Large')
                                ->size('lg')
                                ->poster(self::DEMO_POSTER)
                                ->ratio('16:9')
                                ->fullWidth(),
                            VideoField::make('video__native')
                                ->label('Native controls')
                                ->src(self::DEMO_VIDEO)
                                ->poster(self::DEMO_POSTER)
                                ->ratio('16:9')
                                ->nativeControls()
                                ->controls(false),
                        ]),
                ]),
        ];
    }
}
