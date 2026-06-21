<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

/**
 * Gravity UI icons for Filament fields and actions.
 *
 * @see https://gravity-ui.com/icons
 *
 * Requires `janczakb/blade-gravity-icons` (installed with this plugin via Composer).
 * Use kebab-case icon names from the catalog, prefixed with {@see GravityIcon::PREFIX}.
 *
 * @example GravityIcon::make('arrow-chevron-down') // gravityui-arrow-chevron-down
 * @example GravityIcon::make('Magnifier') // gravityui-magnifier
 */
final class GravityIcon
{
    public const string PREFIX = 'gravityui-';

    public const string Smartphone = 'gravityui-smartphone';

    public const string Handset = 'gravityui-handset';

    public const string Minus = 'gravityui-minus';

    public const string Plus = 'gravityui-plus';

    public const string MagnifierMinus = 'gravityui-magnifier-minus';

    public const string MagnifierPlus = 'gravityui-magnifier-plus';

    public const string Magnifier = 'gravityui-magnifier';

    public const string MapPin = 'gravityui-map-pin';

    public const string CreditCard = 'gravityui-credit-card';

    public const string CircleDollar = 'gravityui-circle-dollar';

    public const string Cube = 'gravityui-cube';

    public const string Flame = 'gravityui-flame';

    public const string Briefcase = 'gravityui-briefcase';

    public const string Box = 'gravityui-box';

    public const string Car = 'gravityui-car';

    public const string Rocket = 'gravityui-rocket';

    public const string Cloud = 'gravityui-cloud';

    public const string Clock = 'gravityui-clock';

    public const string Calendar = 'gravityui-calendar';

    public const string ChartColumn = 'gravityui-chart-column';

    public const string Check = 'gravityui-check';

    public const string SealCheck = 'gravityui-seal-check';

    public const string ShieldCheck = 'gravityui-shield-check';

    public const string Lock = 'gravityui-lock';

    public const string CloudArrowUpIn = 'gravityui-cloud-arrow-up-in';

    public const string Persons = 'gravityui-persons';

    public const string LayoutCells = 'gravityui-layout-cells';

    public const string ChartBar = 'gravityui-chart-bar';

    public const string SquareChartBar = 'gravityui-square-chart-bar';

    public const string Gear = 'gravityui-gear';

    public const string Display = 'gravityui-display';

    public const string LayoutColumns = 'gravityui-layout-columns';

    public const string House = 'gravityui-house';

    public const string Comments = 'gravityui-comments';

    public const string Video = 'gravityui-video';

    public const string PlayFill = 'gravityui-play-fill';

    public const string PauseFill = 'gravityui-pause-fill';

    public const string VolumeFill = 'gravityui-volume-fill';

    public const string VolumeSlashFill = 'gravityui-volume-slash-fill';

    public const string ArrowsExpand = 'gravityui-arrows-expand';

    public const string ChevronsCollapseFromLines = 'gravityui-chevrons-collapse-from-lines';

    public const string ChevronsExpandUpRight = 'gravityui-chevrons-expand-up-right';

    public const string ChevronsCollapseUpRight = 'gravityui-chevrons-collapse-up-right';

    public const string Camera = 'gravityui-camera';

    public const string CopyPicture = 'gravityui-copy-picture';

    public const string Printer = 'gravityui-printer';

    public const string Sun = 'gravityui-sun';

    public const string Moon = 'gravityui-moon';

    public const string Link = 'gravityui-link';

    public const string Thunderbolt = 'gravityui-thunderbolt';

    public const string ThunderboltFill = 'gravityui-thunderbolt-fill';

    public const string Person = 'gravityui-person';

    public const string CircleChevronDown = 'gravityui-circle-chevron-down';

    public const string CircleXmark = 'gravityui-circle-xmark';

    public const string Star = 'gravityui-star';

    public const string OfficeBadge = 'gravityui-office-badge';

    public const string Palette = 'gravityui-palette';

    public const string Heart = 'gravityui-heart';

    public const string Flask = 'gravityui-flask';

    public const string MagicWand = 'gravityui-magic-wand';

    public const string ArrowChevronRight = 'gravityui-arrow-chevron-right';

    public const string ArrowRight = 'gravityui-arrow-right';

    public const string ArrowRightArrowLeft = 'gravityui-arrow-right-arrow-left';

    public const string ArrowLeft = 'gravityui-arrow-left';

    public const string ArrowChevronLeft = 'gravityui-arrow-chevron-left';

    public const string ChevronDown = 'gravityui-chevron-down';

    public const string ChevronUp = 'gravityui-chevron-up';

    public const string Eye = 'gravityui-eye';

    public const string EyeClosed = 'gravityui-eye-closed';

    public const string Copy = 'gravityui-copy';

    public const string FaceSmile = 'gravityui-face-smile';

    public const string Paperclip = 'gravityui-paperclip';

    public const string Globe = 'gravityui-globe';

    public const string Folder = 'gravityui-folder';

    public const string FileText = 'gravityui-file-text';

    public const string Archive = 'gravityui-archive';

    public const string Server = 'gravityui-server';

    public const string Trolley = 'gravityui-trolley';

    public const string Envelope = 'gravityui-envelope';

    public const string Bell = 'gravityui-bell';

    public const string Key = 'gravityui-key';

    public const string Megaphone = 'gravityui-megaphone';

    public const string Hand = 'gravityui-hand';

    public const string Code = 'gravityui-code';

    public const string ChevronRight = 'gravityui-chevron-right';

    public const string ChevronLeft = 'gravityui-chevron-left';

    public const string ArrowUpRightFromSquare = 'gravityui-arrow-up-right-from-square';

    public const string Circles3Plus = 'gravityui-circles-3-plus';

    public const string Microphone = 'gravityui-microphone';

    public const string ArrowUp = 'gravityui-arrow-up';

    public const string FilePlus = 'gravityui-file-plus';

    public const string TrashBin = 'gravityui-trash-bin';

    public const string ArrowRotateLeft = 'gravityui-arrow-rotate-left';

    public const string ArrowRotateRight = 'gravityui-arrow-rotate-right';

    public const string Pencil = 'gravityui-pencil';

    public const string PencilToSquare = 'gravityui-pencil-to-square';

    public const string ArrowsRotateRight = 'gravityui-arrows-rotate-right';

    public const string ArrowDownToSquare = 'gravityui-arrow-down-to-square';

    public const string Xmark = 'gravityui-xmark';

    /**
     * @var array<string, string>
     */
    private static array $makeCache = [];

    public static function make(string $icon): string
    {
        if (isset(self::$makeCache[$icon])) {
            return self::$makeCache[$icon];
        }

        $normalized = str($icon)
            ->replace('_', '-')
            ->kebab()
            ->toString();

        return self::$makeCache[$icon] = self::PREFIX.$normalized;
    }
}
