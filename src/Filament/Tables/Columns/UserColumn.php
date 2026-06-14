<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Tables\Columns;

use Bjanczak\FilamentFlexFields\Concerns\ResolvesUserDisplay;
use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class UserColumn extends TextColumn
{
    use ResolvesUserDisplay;

    protected int|Closure $maxVisibleAvatars = 4;

    protected int|Closure $stackedRing = 2;

    protected int|Closure $stackedOverlap = 10;

    protected bool|Closure $stackTooltips = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->html();

        $this->formatStateUsing(fn (mixed $state, UserColumn $column): string => $column->formatUserDisplay($state));
    }

    public function maxVisibleAvatars(int|Closure $limit): static
    {
        $this->maxVisibleAvatars = $limit;

        return $this;
    }

    public function stackedRing(int|Closure $ring): static
    {
        $this->stackedRing = $ring;

        return $this;
    }

    public function stackedOverlap(int|Closure $overlap): static
    {
        $this->stackedOverlap = $overlap;

        return $this;
    }

    public function stackTooltips(bool|Closure $condition = true): static
    {
        $this->stackTooltips = $condition;

        return $this;
    }

    public function getMaxVisibleAvatars(): int
    {
        return max(1, (int) $this->evaluate($this->maxVisibleAvatars));
    }

    public function getStackedRing(): int
    {
        return max(0, (int) $this->evaluate($this->stackedRing));
    }

    public function getStackedOverlap(): int
    {
        return max(0, (int) $this->evaluate($this->stackedOverlap));
    }

    public function shouldShowStackTooltips(): bool
    {
        return (bool) $this->evaluate($this->stackTooltips);
    }

    public function formatUserDisplay(mixed $state): string
    {
        $users = $this->normalizeUsersFromState($state);

        if ($users === []) {
            return '';
        }

        if (count($users) === 1) {
            return $this->renderRichUser($users[0]);
        }

        return $this->renderAvatarStack($users);
    }

    /**
     * @return list<array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     *     initials: string,
     * }>
     */
    public function normalizeUsersFromState(mixed $state): array
    {
        if ($state === null || $state === '' || $state === []) {
            return [];
        }

        if ($state instanceof Model) {
            return [$this->recordToDisplayArray($state)];
        }

        if ($state instanceof Collection) {
            $state = $state->all();
        }

        if (! is_array($state)) {
            return [];
        }

        $users = [];

        foreach ($state as $item) {
            if ($item instanceof Model) {
                $users[] = $this->recordToDisplayArray($item);
            }
        }

        return $users;
    }

    /**
     * @param  array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     *     initials: string,
     * }  $user
     */
    public function renderRichUser(array $user): string
    {
        /** @var View $view */
        $view = view('filament-flex-fields::tables.columns.user-column-rich', [
            'user' => $user,
        ]);

        return $view->render();
    }

    /**
     * @param  list<array{
     *     label: string,
     *     description: ?string,
     *     image: ?string,
     *     verified: bool,
     *     initials: string,
     * }>  $users
     */
    public function renderAvatarStack(array $users): string
    {
        $limit = $this->getMaxVisibleAvatars();
        $overflow = max(0, count($users) - $limit);
        $visibleUsers = $overflow > 0 ? array_slice($users, 0, $limit) : $users;

        /** @var View $view */
        $view = view('filament-flex-fields::tables.columns.user-column-stack', [
            'users' => $visibleUsers,
            'overflow' => $overflow,
            'ring' => $this->getStackedRing(),
            'overlap' => $this->getStackedOverlap(),
            'showTooltips' => $this->shouldShowStackTooltips(),
        ]);

        return $view->render();
    }
}
