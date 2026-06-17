<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Tables\Columns;

use Bjanczak\FilamentFlexFields\Concerns\ResolvesUserDisplay;
use Bjanczak\FilamentFlexFields\Support\FlexFieldStylesheetQueue;
use Bjanczak\FilamentFlexFields\Support\UserColumnRenderCache;
use Bjanczak\FilamentFlexFields\Support\UserColumnSharedStackCache;
use Bjanczak\FilamentFlexFields\Support\UserColumnStackState;
use Closure;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class UserColumn extends TextColumn
{
    use ResolvesUserDisplay;

    protected int|Closure $maxVisibleAvatars = 4;

    protected int|Closure $stackedRing = 2;

    protected int|Closure $stackedOverlap = 10;

    protected bool|Closure $stackTooltips = true;

    /** @var string|array<int, string>|Closure|null */
    protected string|array|Closure|null $eagerLoad = null;

    protected ?Closure $sharedStackUsing = null;

    protected function setUp(): void
    {
        parent::setUp();

        FlexFieldStylesheetQueue::enqueueFor('user-column');

        $this->html();

        $this->formatStateUsing(fn (mixed $state, UserColumn $column): string => $column->formatUserDisplay($state));
    }

    public function getState(): mixed
    {
        $state = parent::getState();

        if ($this->stateContainsMultipleUsers($state)) {
            return new UserColumnStackState($state);
        }

        return $state;
    }

    /**
     * @param  string|array<int, string>|Closure  $relationships
     */
    public function eagerLoad(string|array|Closure $relationships): static
    {
        $this->eagerLoad = $relationships;

        return $this;
    }

    /**
     * Resolve the same multi-user stack once per table page instead of per row.
     * Ideal for preview/demo columns that show identical members on every record.
     */
    public function sharedStackUsing(Closure $resolver): static
    {
        $this->sharedStackUsing = $resolver;

        $this->getStateUsing(fn (): mixed => $this->resolveSharedStackState());

        return $this;
    }

    public function applyEagerLoading(EloquentBuilder|Relation $query): EloquentBuilder|Relation
    {
        if ($this->eagerLoad !== null) {
            foreach (Arr::wrap($this->evaluate($this->eagerLoad)) as $relationship) {
                if (! is_string($relationship) || $relationship === '') {
                    continue;
                }

                if (array_key_exists($relationship, $query->getEagerLoads())) {
                    continue;
                }

                $query = $query->with([$relationship]);
            }
        }

        $relationshipName = $this->resolveDirectRelationshipName($query->getModel());

        if (
            filled($relationshipName)
            && ! array_key_exists($relationshipName, $query->getEagerLoads())
        ) {
            $query = $query->with([$relationshipName]);
        }

        return parent::applyEagerLoading($query);
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
        if ($state instanceof UserColumnStackState) {
            $state = $state->users;
        }

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
        $cacheKey = $this->renderCacheKey('rich', [$user]);

        return UserColumnRenderCache::remember($cacheKey, function () use ($user): string {
            /** @var View $view */
            $view = view('filament-flex-fields::tables.columns.user-column-rich', [
                'user' => $user,
            ]);

            return $view->render();
        });
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

        $cacheKey = $this->renderCacheKey('stack', [
            'users' => $visibleUsers,
            'overflow' => $overflow,
        ]);

        return UserColumnRenderCache::remember($cacheKey, function () use ($visibleUsers, $overflow): string {
            /** @var View $view */
            $view = view('filament-flex-fields::tables.columns.user-column-stack', [
                'users' => $visibleUsers,
                'overflow' => $overflow,
                'ring' => $this->getStackedRing(),
                'overlap' => $this->getStackedOverlap(),
                'showTooltips' => $this->shouldShowStackTooltips(),
            ]);

            return $view->render();
        });
    }

    protected function stateContainsMultipleUsers(mixed $state): bool
    {
        if ($state instanceof UserColumnStackState) {
            return true;
        }

        if ($state instanceof Collection) {
            $state = $state->all();
        }

        if (! is_array($state)) {
            return false;
        }

        $modelCount = 0;

        foreach ($state as $item) {
            if ($item instanceof Model) {
                $modelCount++;
            }
        }

        return $modelCount > 1;
    }

    protected function resolveDirectRelationshipName(Model $record): ?string
    {
        $name = $this->getName();

        if (! filled($name) || str($name)->contains('.')) {
            return null;
        }

        if ($record->hasAttribute($name)) {
            return null;
        }

        if (! $record->isRelation($name)) {
            return null;
        }

        return $name;
    }

    protected function resolveSharedStackState(): mixed
    {
        if ($this->sharedStackUsing === null) {
            return null;
        }

        return UserColumnSharedStackCache::remember(
            $this->sharedStackCacheKey(),
            fn (): mixed => $this->evaluate($this->sharedStackUsing),
        );
    }

    protected function sharedStackCacheKey(): string
    {
        $livewire = $this->getLivewire();

        return hash('xxh128', implode('|', [
            $livewire::class,
            spl_object_id($livewire),
            $this->getName(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function renderCacheKey(string $type, array $payload): string
    {
        return hash('xxh128', json_encode([
            'column' => $this->getName(),
            'type' => $type,
            'payload' => $payload,
            'ring' => $this->getStackedRing(),
            'overlap' => $this->getStackedOverlap(),
            'tooltips' => $this->shouldShowStackTooltips(),
            'nameColumn' => $this->getNameColumn(),
            'emailColumn' => $this->getEmailColumn(),
            'avatarColumn' => $this->getAvatarColumn(),
            'verificationColumn' => $this->getVerificationColumn(),
        ], JSON_THROW_ON_ERROR));
    }
}
