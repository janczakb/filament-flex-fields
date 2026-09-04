<?php

declare(strict_types=1);

namespace Bjanczak\FilamentFlexFields\Support\Schema;

final class SchemaBlueprintPacks
{
    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return ['crm', 'hr', 'booking', 'inventory', 'support', 'onboarding'];
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function pack(string $name): ?array
    {
        return match (strtolower(trim($name))) {
            'crm' => self::crm(),
            'hr' => self::hr(),
            'booking' => self::booking(),
            'inventory' => self::inventory(),
            'support' => self::support(),
            'onboarding' => self::onboarding(),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    public static function crm(): array
    {
        return [
            'key' => 'crm_contact',
            'label' => 'CRM contact profile',
            'target' => 'App\\Models\\Contact',
            'version' => 1,
            'sections' => [
                ['id' => 'company', 'label' => 'Company', 'type' => 'section', 'sort' => 0],
                ['id' => 'pipeline', 'label' => 'Pipeline', 'type' => 'section', 'sort' => 1],
            ],
            'fields' => [
                [
                    'slug' => 'company_name',
                    'label' => 'Company name',
                    'type' => 'single_line_text',
                    'sort' => 0,
                    'section_id' => 'company',
                ],
                [
                    'slug' => 'deal_stage',
                    'label' => 'Deal stage',
                    'type' => 'select',
                    'sort' => 1,
                    'section_id' => 'pipeline',
                    'config' => [
                        'options' => [
                            'lead' => 'Lead',
                            'qualified' => 'Qualified',
                            'won' => 'Won',
                        ],
                    ],
                ],
                [
                    'slug' => 'lead_source',
                    'label' => 'Lead source',
                    'type' => 'single_line_text',
                    'sort' => 2,
                    'section_id' => 'pipeline',
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function hr(): array
    {
        return [
            'key' => 'hr_employee',
            'label' => 'HR employee profile',
            'target' => 'App\\Models\\Employee',
            'version' => 1,
            'fields' => [
                [
                    'slug' => 'employee_id',
                    'label' => 'Employee ID',
                    'type' => 'single_line_text',
                    'sort' => 0,
                ],
                [
                    'slug' => 'department',
                    'label' => 'Department',
                    'type' => 'select',
                    'sort' => 1,
                    'config' => [
                        'options' => [
                            'engineering' => 'Engineering',
                            'sales' => 'Sales',
                            'operations' => 'Operations',
                        ],
                    ],
                ],
                [
                    'slug' => 'start_date',
                    'label' => 'Start date',
                    'type' => 'date',
                    'sort' => 2,
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function booking(): array
    {
        return [
            'key' => 'booking_reservation',
            'label' => 'Booking reservation',
            'target' => 'App\\Models\\Reservation',
            'version' => 1,
            'fields' => [
                [
                    'slug' => 'check_in',
                    'label' => 'Check-in date',
                    'type' => 'date',
                    'sort' => 0,
                ],
                [
                    'slug' => 'check_out',
                    'label' => 'Check-out date',
                    'type' => 'date',
                    'sort' => 1,
                ],
                [
                    'slug' => 'guest_count',
                    'label' => 'Guest count',
                    'type' => 'integer',
                    'sort' => 2,
                ],
            ],
        ];
    }

    /**
     * Wave-2: inventory / catalog blueprint.
     *
     * @return array<string, mixed>
     */
    public static function inventory(): array
    {
        return [
            'key' => 'inventory_item',
            'label' => 'Inventory item',
            'target' => 'App\\Models\\InventoryItem',
            'version' => 1,
            'fields' => [
                [
                    'slug' => 'sku',
                    'label' => 'SKU',
                    'type' => 'single_line_text',
                    'sort' => 0,
                    'required' => true,
                ],
                [
                    'slug' => 'category',
                    'label' => 'Category',
                    'type' => 'select',
                    'sort' => 1,
                    'config' => [
                        'options' => [
                            'parts' => 'Parts',
                            'consumables' => 'Consumables',
                            'assets' => 'Assets',
                        ],
                    ],
                ],
                [
                    'slug' => 'quantity_on_hand',
                    'label' => 'Quantity on hand',
                    'type' => 'integer',
                    'sort' => 2,
                ],
                [
                    'slug' => 'unit_cost',
                    'label' => 'Unit cost',
                    'type' => 'currency',
                    'sort' => 3,
                ],
            ],
        ];
    }

    /**
     * Wave-2: support ticket blueprint.
     *
     * @return array<string, mixed>
     */
    public static function support(): array
    {
        return [
            'key' => 'support_ticket',
            'label' => 'Support ticket',
            'target' => 'App\\Models\\SupportTicket',
            'version' => 1,
            'sections' => [
                ['id' => 'summary', 'label' => 'Summary', 'type' => 'section', 'sort' => 0],
                ['id' => 'details', 'label' => 'Details', 'type' => 'section', 'sort' => 1],
            ],
            'fields' => [
                [
                    'slug' => 'subject',
                    'label' => 'Subject',
                    'type' => 'single_line_text',
                    'sort' => 0,
                    'required' => true,
                    'section_id' => 'summary',
                ],
                [
                    'slug' => 'priority',
                    'label' => 'Priority',
                    'type' => 'select',
                    'sort' => 1,
                    'section_id' => 'summary',
                    'config' => [
                        'options' => [
                            'low' => 'Low',
                            'normal' => 'Normal',
                            'high' => 'High',
                            'urgent' => 'Urgent',
                        ],
                    ],
                ],
                [
                    'slug' => 'channel',
                    'label' => 'Channel',
                    'type' => 'select',
                    'sort' => 2,
                    'section_id' => 'details',
                    'config' => [
                        'options' => [
                            'email' => 'Email',
                            'chat' => 'Chat',
                            'phone' => 'Phone',
                        ],
                    ],
                ],
                [
                    'slug' => 'body',
                    'label' => 'Description',
                    'type' => 'multi_line_text',
                    'sort' => 3,
                    'section_id' => 'details',
                ],
            ],
        ];
    }

    /**
     * Wave-2: customer onboarding blueprint.
     *
     * @return array<string, mixed>
     */
    public static function onboarding(): array
    {
        return [
            'key' => 'customer_onboarding',
            'label' => 'Customer onboarding',
            'target' => 'App\\Models\\OnboardingProfile',
            'version' => 1,
            'fields' => [
                [
                    'slug' => 'company_name',
                    'label' => 'Company name',
                    'type' => 'single_line_text',
                    'sort' => 0,
                    'required' => true,
                ],
                [
                    'slug' => 'primary_contact_email',
                    'label' => 'Primary contact email',
                    'type' => 'email',
                    'sort' => 1,
                    'required' => true,
                ],
                [
                    'slug' => 'plan',
                    'label' => 'Plan',
                    'type' => 'select',
                    'sort' => 2,
                    'config' => [
                        'options' => [
                            'starter' => 'Starter',
                            'growth' => 'Growth',
                            'enterprise' => 'Enterprise',
                        ],
                    ],
                ],
                [
                    'slug' => 'go_live_date',
                    'label' => 'Go-live date',
                    'type' => 'date',
                    'sort' => 3,
                ],
            ],
        ];
    }
}
