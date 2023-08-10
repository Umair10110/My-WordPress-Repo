<?php

return [
    // implementation of the HostingPlanRepositoryContract interface
    'repository' => GoDaddy\WordPress\MWC\Core\HostingPlans\Repositories\HostingPlanRepository::class,

    // maximum number of plans history to keep
    'max_plans_to_keep' => 3,

    // hosting plan grades for all known plans in MWP and MWCS
    'mappings' => [
        [
            'name'  => 'pro-5',
            'grade' => 100,
        ],
        [
            'name'  => 'pro-10',
            'grade' => 200,
        ],
        [
            'name'  => 'pro-25',
            'grade' => 300,
        ],
        [
            'name'  => 'pro-50',
            'grade' => 400,
        ],
        [
            'name'  => 'basic',
            'grade' => 500,
        ],
        [
            'name'  => 'delux',
            'grade' => 600,
        ],
        [
            'name'  => 'ultimate',
            'grade' => 700,
        ],
        [
            'name'  => 'ecommerce',
            'grade' => 800,
        ],
        [
            'name'  => 'essentials',
            'grade' => 850,
        ],
        [
            'name'  => 'essentials_GDGCPP',
            'grade' => 850,
        ],
        [
            'name'  => 'flex',
            'grade' => 900,
        ],
        [
            'name'  => 'flex_GDGCPP',
            'grade' => 900,
        ],
        [
            'name'  => 'expand',
            'grade' => 1000,
        ],
        [
            'name'  => 'expand_GDGCPP',
            'grade' => 1000,
        ],
        [
            'name'  => 'premier',
            'grade' => 1100,
        ],
    ],
];
