<?php

/**
 * Single source of truth for tenant permission entities, matrix UI, and seeders.
 *
 * - entities: Spatie permission keys + applicable actions + optional i18n label keys
 * - sections: matrix row order and preset toolbar grouping (SPA)
 * - tenant_roles: default system roles that receive all tenant permissions on seed
 */
return [
    'default_actions' => ['view', 'create', 'edit', 'delete'],

    /** Legacy broad keys hidden from the matrix UI. */
    'skip_entities' => ['core'],

    'excluded_entities' => [],

    'tenant_roles' => ['Administrator'],

    'entities' => [
        'core.wardrobe' => [
            'label' => 'menu.wardrobe',
        ],
        'core.outfits' => [
            'actions' => ['view', 'create'],
            'label' => 'menu.outfits',
        ],
        'core.users' => [
            'actions' => ['view', 'create', 'edit'],
            'label' => 'menu.user_management',
        ],
        'core.roles' => [
            'label' => 'menu.user_roles',
        ],
    ],

    'sections' => [
        [
            'title' => 'Core',
            'preset_key' => 'core',
            'child_indent' => false,
            'entities' => [
                'core.wardrobe',
                'core.outfits',
                'core.users',
                'core.roles',
            ],
        ],
    ],
];
