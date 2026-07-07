<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'Recco', 'type' => 'text', 'group' => 'general'],
            ['key' => 'site_tagline', 'value' => 'Professional Laundry & Dry Cleaning Services', 'type' => 'text', 'group' => 'general'],
            ['key' => 'site_email', 'value' => 'info@recco.com', 'type' => 'email', 'group' => 'contact'],
            ['key' => 'site_phone', 'value' => '+92 312 2946615', 'type' => 'text', 'group' => 'contact'],
            ['key' => 'site_address', 'value' => 'Karachi, Pakistan', 'type' => 'text', 'group' => 'contact'],
            ['key' => 'minimum_order', 'value' => '500', 'type' => 'number', 'group' => 'order'],
            ['key' => 'free_pickup_threshold', 'value' => '4000', 'type' => 'number', 'group' => 'order'],
            ['key' => 'delivery_time_hours', 'value' => '48', 'type' => 'number', 'group' => 'order'],
            ['key' => 'tax_percentage', 'value' => '0', 'type' => 'number', 'group' => 'payment'],
            ['key' => 'currency', 'value' => 'PKR', 'type' => 'text', 'group' => 'payment'],
            ['key' => 'currency_symbol', 'value' => 'Rs.', 'type' => 'text', 'group' => 'payment'],
        ];

        foreach ($settings as $setting) {
            Setting::create($setting);
        }
    }
}