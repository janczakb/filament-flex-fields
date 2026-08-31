---
title: "Compliance pack"
---

# Compliance pack

This document tracks **WCAG 2.2 Level AA** conformance for Flex Fields lazy asset components. Status values are exported programmatically via `CompliancePack::exportReport()`.

## Scope

| Item | Value |
|------|-------|
| Field types inventoried | `CompliancePack::inventoryFields()` — all `FieldType` enum cases |
| Supported locales | `FlexFieldsLocale::supportedLocales()` — `en`, `pl`, `de`, `fr`, `es`, `pt_BR`, `nl`, `it` |
| Matrix key | Lazy asset component id (e.g. `select-field`, `flex-date-time-field`) |
| Standard label | `WCAG 2.2 Level AA (product baseline)` |

## Status legend

| Status | Meaning |
|--------|---------|
| `pending` | Not yet audited |
| `pass` | Product baseline smoke coverage (keyboard / focus / attach) — not a legal VPAT claim |
| `fail` | Known gap; set via `CompliancePack::mark()` during audits |

## Product baseline

`CompliancePack::baselinePassComponents()` marks smoke-covered surfaces (`select-field`, `phone-field`, `schedule-field`, uploads, teleported menus, …) as `pass`. Remaining components stay `pending` until audited. Override any cell with:

```php
CompliancePack::mark('select-field', CompliancePack::STATUS_FAIL);
```

## Audit criteria (exported)

The report includes a fixed criteria list: keyboard operable, visible focus, name/role/value, labels, error identification, and contrast targets for theme tokens.

## Export

```php
use Bjanczak\FilamentFlexFields\Support\Compliance\CompliancePack;

$report = CompliancePack::exportReport();
// [
//     'generated_at' => '2026-08-27T…',
//     'standard' => 'WCAG 2.2 Level AA (product baseline)',
//     'locales' => [...],
//     'field_type_count' => …,
//     'aa_matrix' => ['select-field' => 'pass', …],
//     'summary' => ['pass' => …, 'pending' => …, 'fail' => 0],
//     'criteria' => [...],
// ]
```

Open the **Compliance pack** playground hub for a live sample table.

## Locale formatting helpers

`FlexFieldsLocale` centralizes locale resolution and display formatting:

- `resolve(?string $locale): string` — normalizes codes and falls back to `en`
- `formatMoney(float|string $amount, string $currency, ?string $locale): string`
- `formatDate(\DateTimeInterface|string $date, ?string $locale): string`

Uses PHP `ext-intl` when available; deterministic fallbacks otherwise.

## Media, PCI & retention (operational)

Enterprise media posture: [Media & Capture OS](/docs/media-capture-os).

| Control | Env / hook |
|---------|------------|
| Fail-closed AV | `FLEX_FIELDS_REQUIRE_VIRUS_SCAN=true` + `MediaCaptureOs::registerVirusScanCallback()` |
| Quarantine | `FLEX_FIELDS_QUARANTINE_DISK` |
| PCI never store PAN | `FLEX_FIELDS_PCI_NEVER_STORE_PAN=true` (default) |
| Tokenization required | `FLEX_FIELDS_PCI_REQUIRE_TOKENIZATION=true` |
| Retention cron | `flex-fields:prune-capture-media` (scheduled when `FLEX_FIELDS_RETENTION_SCHEDULE=true`) |
| Multi-tenant disk | `FLEX_FIELDS_MEDIA_TENANT_DISK` + `FLEX_FIELDS_MEDIA_TENANT_AUTO_DISK=true` |

This WCAG pack does not replace a PCI SAQ or e-sign legal review.

## eIDAS / ESIGN operational checklist

Use alongside [SignatureField](/docs/signaturefield) legal pack hooks:

| Requirement | Flex Fields support |
|-------------|---------------------|
| Signer intent (ink required) | `SignatureField::legalPack()` → `requiresInk()` |
| Timestamp seal | `timestampSeal()` UTC ISO-8601 |
| Audit trail | `legalAuditSeal()` — IP, user agent, signer id, document hash |
| Host identity binding | `MediaCaptureOs::registerLegalSignerIdResolver()` |
| Document integrity | `MediaCaptureOs::registerDocumentHashResolver()` |
| Long-term retention | `flex-fields:prune-capture-media` + category policies |

Legal review remains the host app's responsibility for jurisdiction-specific ESIGN / eIDAS compliance.

## Hosted payment fields (PCI)

Flex Fields **does not** embed Stripe Elements or iframe-hosted PAN fields. For SAQ A-EP / reduced PCI scope:

1. Keep `FLEX_FIELDS_PCI_NEVER_STORE_PAN=true` (default).
2. Register `MediaCaptureOs::registerTokenizeCreditCardCallback()` — dehydrate stores `token` + `last4` only.
3. Set `FLEX_FIELDS_PCI_REQUIRE_TOKENIZATION=true` in production.
4. Prefer gateway-hosted fields (Stripe Elements, Adyen Drop-in) for raw PAN entry; use `CreditCardField` only when tokenization callback is wired.

See [CreditCardField](/docs/creditcardfield) and [Media & Capture OS](/docs/media-capture-os).
