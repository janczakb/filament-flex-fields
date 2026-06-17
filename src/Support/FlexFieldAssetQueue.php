<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support;

/**
 * Shared queue bookkeeping for lazy flex-field assets.
 */
trait FlexFieldAssetQueue
{
    /** @var array<string, true> */
    protected array $enqueued = [];

    /** @var array<string, true> */
    protected array $emitted = [];

    public function queue(string $key): bool
    {
        if (isset($this->enqueued[$key])) {
            return false;
        }

        $this->enqueued[$key] = true;

        return true;
    }

    public function hasKey(string $key): bool
    {
        return isset($this->enqueued[$key]);
    }

    /**
     * @param  list<string>  $keys
     */
    public function markEmitted(array $keys): void
    {
        foreach ($keys as $key) {
            $this->emitted[$key] = true;
        }
    }

    /**
     * @return list<string>
     */
    public function pendingRegistered(): array
    {
        $pending = [];

        foreach (array_keys($this->enqueued) as $key) {
            if (! isset($this->emitted[$key])) {
                $pending[] = $key;
            }
        }

        return $pending;
    }

    /**
     * @return list<string>
     */
    public function registeredKeys(): array
    {
        return array_keys($this->enqueued);
    }

    public function clear(): void
    {
        $this->enqueued = [];
        $this->emitted = [];
    }
}
