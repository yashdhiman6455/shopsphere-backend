<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Roles and permissions
    |--------------------------------------------------------------------------
    |
    | Defines the permissions granted to each role. A role may be granted the
    | "*" wildcard permission, which allows every permission defined below.
    |
    */

    'roles' => [
        'customer' => [
            'shop',
            'manage_cart',
            'manage_wishlist',
            'write_reviews',
            'manage_own_orders',
            'manage_profile',
        ],

        'seller' => [
            'manage_products',
            'manage_orders',
            'view_revenue',
            'view_notifications',
            'manage_profile',
        ],

        'admin' => [
            '*',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission labels
    |--------------------------------------------------------------------------
    |
    | A human readable description for each permission, used for documentation
    | and debugging purposes.
    |
    */

    'permissions' => [
        'shop' => 'Browse catalog and place orders',
        'manage_cart' => 'Manage own shopping cart',
        'manage_wishlist' => 'Manage own wishlist',
        'write_reviews' => 'Write and delete product reviews',
        'manage_own_orders' => 'View, download invoices for and cancel own orders',
        'manage_products' => 'Create, update and delete own products',
        'manage_orders' => 'Update status of own orders',
        'view_revenue' => 'View revenue and sales reports',
        'view_notifications' => 'View and manage notifications',
        'manage_profile' => 'Update own profile',
    ],

];
