<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Playground;

use Bjanczak\FilamentFlexFields\Support\GravityIcon;

class PlaygroundOptions
{
    /**
     * @return array<string, string>
     */
    public static function hero(): array
    {
        return [
            'dashboard' => 'Dashboard',
            'analytics' => 'Analytics',
            'reports' => 'Reports',
            'settings' => 'Settings',
        ];
    }

    /**
     * @return array<string, array{label: string, icon: string}>
     */
    public static function heroWithIcons(): array
    {
        return [
            'dashboard' => ['label' => 'Dashboard', 'icon' => GravityIcon::LayoutCells],
            'analytics' => ['label' => 'Analytics', 'icon' => GravityIcon::ChartBar],
            'reports' => ['label' => 'Reports', 'icon' => GravityIcon::SquareChartBar],
            'settings' => ['label' => 'Settings', 'icon' => GravityIcon::Gear],
        ];
    }

    /**
     * @return array<string, array{label: string, icon: string}>
     */
    public static function devices(): array
    {
        return [
            'mobile' => ['label' => 'Mobile', 'icon' => GravityIcon::Smartphone],
            'tablet' => ['label' => 'Tablet', 'icon' => GravityIcon::LayoutColumns],
            'desktop' => ['label' => 'Desktop', 'icon' => GravityIcon::Display],
        ];
    }

    /**
     * @return array<string, array{label: string, icon: string}>
     */
    public static function iconExpand(): array
    {
        return [
            'home' => ['label' => 'Home', 'icon' => GravityIcon::House],
            'chat' => ['label' => 'Chat', 'icon' => GravityIcon::Comments],
            'video' => ['label' => 'Video', 'icon' => GravityIcon::Video],
            'print' => ['label' => 'Print', 'icon' => GravityIcon::Printer],
        ];
    }
}
