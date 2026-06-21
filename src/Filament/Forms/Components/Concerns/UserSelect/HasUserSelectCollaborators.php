<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Forms\Components\Concerns\UserSelect;

use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect\UserSelectOptionPresenter;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect\UserSelectQueryEngine;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect\UserSelectRecordMapper;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect\UserSelectRuntimeState;
use Bjanczak\FilamentFlexFields\Filament\Forms\Components\UserSelect\UserSelectShapeResolver;
use Bjanczak\FilamentFlexFields\Support\UserSelectQueryCache;

/**
 * @mixin UserSelect
 */
trait HasUserSelectCollaborators
{
    protected ?UserSelectRuntimeState $runtimeState = null;

    protected ?UserSelectRecordMapper $recordMapper = null;

    protected ?UserSelectQueryEngine $queryEngine = null;

    protected ?UserSelectShapeResolver $shapeResolver = null;

    protected ?UserSelectOptionPresenter $optionPresenter = null;

    protected function runtimeState(): UserSelectRuntimeState
    {
        return $this->runtimeState ??= new UserSelectRuntimeState;
    }

    protected function recordMapper(): UserSelectRecordMapper
    {
        return $this->recordMapper ??= new UserSelectRecordMapper($this);
    }

    protected function queryEngine(): UserSelectQueryEngine
    {
        return $this->queryEngine ??= new UserSelectQueryEngine(
            $this,
            $this->recordMapper(),
            $this->runtimeState(),
            app(UserSelectQueryCache::class),
        );
    }

    protected function shapeResolver(): UserSelectShapeResolver
    {
        return $this->shapeResolver ??= new UserSelectShapeResolver(
            $this,
            $this->recordMapper(),
            $this->queryEngine(),
            $this->runtimeState(),
        );
    }

    protected function optionPresenter(): UserSelectOptionPresenter
    {
        return $this->optionPresenter ??= new UserSelectOptionPresenter($this);
    }
}
