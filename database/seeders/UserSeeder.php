<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Customer;
use App\Models\Washerman;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create Admin
        $admin = User::create([
            'name' => 'Recco Admin',
            'email' => 'admin@recco.com',
            'password' => Hash::make('password'),
            'phone' => '+92 300 1234567',
            'role' => 'admin',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        // Create Demo Customer
        $customerUser = User::create([
            'name' => 'John Customer',
            'email' => 'customer@recco.com',
            'password' => Hash::make('password'),
            'phone' => '+92 301 1234567',
            'role' => 'customer',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        Customer::create([
            'user_id' => $customerUser->id,
        ]);

        // Create Demo Washerman (Approved)
        $washermanUser = User::create([
            'name' => 'Ahmed Washerman',
            'email' => 'washerman@recco.com',
            'password' => Hash::make('password'),
            'phone' => '+92 302 1234567',
            'role' => 'washerman',
            'status' => 'active',
            'email_verified_at' => now(),
        ]);

        Washerman::create([
            'user_id' => $washermanUser->id,
            'shop_name' => 'Ahmed Laundry Services',
            'approval_status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $admin->id,
            'is_available' => true,
        ]);

        // Create Pending Washerman
        $pendingWasherman = User::create([
            'name' => 'Ali Laundry',
            'email' => 'pending@recco.com',
            'password' => Hash::make('password'),
            'phone' => '+92 303 1234567',
            'role' => 'washerman',
            'status' => 'pending',
        ]);

        Washerman::create([
            'user_id' => $pendingWasherman->id,
            'shop_name' => 'Ali Laundry Shop',
            'approval_status' => 'pending',
        ]);
    }
}