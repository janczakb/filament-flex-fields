<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Enums;

enum PhoneNationalFormat: string
{
    /**
     * libphonenumber NATIONAL formatting (spaces / punctuation). Default — BC lock.
     */
    case National = 'national';

    /**
     * Digits only (no spaces). Opt-in storage shape for CRM / SMS pipelines.
     */
    case Digits = 'digits';
}
