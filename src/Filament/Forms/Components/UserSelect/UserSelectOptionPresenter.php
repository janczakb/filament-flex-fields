<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Filament\Support\Enums\IconSize;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Str;

class UserSelectOptionPresenter
{
    public function __construct(
        protected UserSelect $select,
    ) {}

    /**
     * @param  array{
     *     label: string,
     *     description?: ?string,
     *     image?: ?string,
     *     verified?: bool,
     * }  $option
     */
    public function renderUserOption(array $option, string $layout = 'list'): string
    {
        /** @var View $view */
        $view = view('filament-flex-fields::forms.components.partials.user-select-option', [
            'label' => $option['label'],
            'description' => $option['description'] ?? null,
            'image' => filled($option['image'] ?? null) ? (string) $option['image'] : null,
            'verified' => (bool) ($option['verified'] ?? false),
            'initials' => $this->initialsForName($option['label']),
            'layout' => $layout,
            'iconSize' => match ($this->select->getSize()) {
                'sm' => IconSize::Small,
                'lg' => IconSize::Large,
                default => IconSize::Medium,
            },
        ]);

        return $view->render();
    }

    /**
     * @return array{
     *     triggerHtml: ?string,
     *     tagsHtml: ?string,
     *     entries: list<array{value: string, user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string}}>,
     * }
     */
    public function initialDisplay(): array
    {
        if (! $this->select->isMultiple()) {
            return [
                'triggerHtml' => null,
                'tagsHtml' => null,
                'entries' => [],
            ];
        }

        $users = $this->select->resolveSelectedUsersForDisplay();

        if ($users === []) {
            return [
                'triggerHtml' => null,
                'tagsHtml' => null,
                'entries' => [],
            ];
        }

        $triggerHtml = count($users) === 1
            ? $this->renderUserOption([
                'label' => $users[0]['user']['name'],
                'description' => $users[0]['user']['email'],
                'image' => $users[0]['user']['avatarUrl'],
                'verified' => $users[0]['user']['verified'],
            ], layout: 'trigger')
            : $this->renderMultipleTriggerNamesHtml($users);

        $tagsHtml = count($users) < 2
            ? null
            : $this->renderSelectedUserTagsHtml($users);

        return [
            'triggerHtml' => $triggerHtml,
            'tagsHtml' => $tagsHtml,
            'entries' => $users,
        ];
    }

    /**
     * @param  list<array{value: string, user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string}}>  $users
     */
    public function renderMultipleTriggerNamesHtml(array $users): string
    {
        $names = array_map(
            fn (array $user): string => $user['user']['name'],
            $users,
        );

        return '<span class="fff-user-select__trigger-names" data-fff-user-select-names="'
            .e(implode("\n", $names))
            .'">'
            .e(implode(', ', $names))
            .'</span>';
    }

    /**
     * @param  list<array{value: string, user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string}}>  $users
     */
    public function renderSelectedUserTagsHtml(array $users): string
    {
        $html = '<div class="fff-user-select__selected-tags" data-fff-user-select-tags>';

        foreach ($users as $user) {
            $html .= $this->renderUserSelectedTag($user['value'], [
                'label' => $user['user']['name'],
                'description' => $user['user']['email'],
                'image' => $user['user']['avatarUrl'],
                'verified' => $user['user']['verified'],
            ]);
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * @param  array{
     *     label: string,
     *     description?: ?string,
     *     image?: ?string,
     *     verified?: bool,
     * }  $shape
     * @return array{
     *     value: string,
     *     user: array{name: string, email: ?string, avatarUrl: ?string, verified: bool, initials: string},
     * }
     */
    public function buildSelectedUserDisplayEntry(string|int $value, array $shape): array
    {
        return [
            'value' => (string) $value,
            'user' => $this->userShapeToClientPayload($shape),
        ];
    }

    /**
     * @param  array{
     *     label: string,
     *     description?: ?string,
     *     image?: ?string,
     *     verified?: bool,
     * }  $option
     */
    public function renderUserSelectedTag(string|int $value, array $option): string
    {
        $isDisabled = false;

        try {
            $isDisabled = $this->select->isDisabled();
        } catch (\Throwable) {
            $isDisabled = false;
        }

        /** @var View $view */
        $view = view('filament-flex-fields::forms.components.partials.user-select-selected-tag', [
            'value' => (string) $value,
            'name' => $option['label'],
            'tagHtml' => $this->renderUserOption($option, layout: 'tag'),
            'removable' => ! $isDisabled,
        ]);

        return $view->render();
    }

    /**
     * @param  array{
     *     label: string,
     *     description?: ?string,
     *     image?: ?string,
     *     verified?: bool,
     *     disabled?: bool,
     * }  $shape
     * @return array{
     *     name: string,
     *     email: ?string,
     *     avatarUrl: ?string,
     *     verified: bool,
     *     initials: string,
     * }
     */
    public function userShapeToClientPayload(array $shape): array
    {
        $name = (string) $shape['label'];

        return [
            'name' => $name,
            'email' => filled($shape['description'] ?? null) ? (string) $shape['description'] : null,
            'avatarUrl' => filled($shape['image'] ?? null) ? (string) $shape['image'] : null,
            'verified' => (bool) ($shape['verified'] ?? false),
            'initials' => $this->initialsForName($name),
        ];
    }

    public function initialsForName(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        if ($parts === []) {
            return '';
        }

        if (count($parts) === 1) {
            return Str::upper(Str::substr($parts[0], 0, 2));
        }

        $initials = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= Str::upper(Str::substr($part, 0, 1));
        }

        return $initials;
    }
}
