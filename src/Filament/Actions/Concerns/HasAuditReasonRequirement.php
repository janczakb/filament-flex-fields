<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Filament\Actions\Concerns;

use Bjanczak\FilamentFlexFields\Support\Admin\HoldConfirmEnterprise;
use Closure;

trait HasAuditReasonRequirement
{
    protected bool|Closure $holdConfirmAuditReasonRequired = false;

    public function auditReasonRequired(bool|Closure $condition = true): static
    {
        $this->holdConfirmAuditReasonRequired = $condition;

        return $this;
    }

    public function isHoldConfirmAuditReasonRequired(): bool
    {
        if ((bool) $this->evaluate($this->holdConfirmAuditReasonRequired)) {
            return true;
        }

        return HoldConfirmEnterprise::requiresAuditReason();
    }
}
