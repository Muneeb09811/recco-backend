<?php

return [
    'name' => env('APP_NAME', 'Recco'),
    'version' => '1.0.0',
    
    'order' => [
        'minimum_items' => 1,
        'default_delivery_hours' => 48,
        'max_images_per_order' => 5,
    ],
    
    'payment' => [
        'tax_percentage' => env('TAX_PERCENTAGE', 0),
        'currency' => env('CURRENCY', 'PKR'),
        'currency_symbol' => env('CURRENCY_SYMBOL', 'Rs.'),
    ],
    
    'washerman' => [
        'max_active_orders' => 50,
        'require_approval' => true,
    ],
    
    'upload' => [
        'max_file_size' => 2048, // KB
        'allowed_image_types' => ['jpg', 'jpeg', 'png', 'webp'],
    ],
];