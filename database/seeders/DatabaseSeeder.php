<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Tenant;
use App\Models\TenantSetting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // ---------------------------------------------------------
        // 1. Create Super Admin
        // ---------------------------------------------------------
        $superAdmin = User::firstOrCreate(
            ['email' => 'admin@saas.com'],
            [
                'name' => 'Super Admin',
                'password' => bcrypt('abc@1234'), // Change this in production
                'role' => 'super_admin',
                'tenant_id' => null,
            ]
        );

        // ---------------------------------------------------------
        // 2. Global Theme Settings (Fallback)
        // ---------------------------------------------------------
        if (class_exists(TenantSetting::class)) {
            TenantSetting::updateOrCreate(
                ['tenant_id' => null],
                [
                    'client_theme' => [
                        'primary' => '#0096FF', // Default Neon Blue
                        'secondary' => '#1e293b',
                        'font' => 'Inter',
                    ]
                ]
            );
        }

        // ---------------------------------------------------------
        // 3. Create a Demo Tenant (Agency)
        // ---------------------------------------------------------
        $tenant = Tenant::firstOrCreate(
            ['domain' => 'demo.saas.local'],
            [
                'name' => 'Demo Agency',
                'status' => 'active',
            ]
        );

        // ---------------------------------------------------------
        // 4. Create Agency Owner
        // ---------------------------------------------------------
        User::firstOrCreate(
            ['email' => 'owner@demo.com'],
            [
                'name' => 'John Agency',
                'password' => bcrypt('abc@1234'),
                'role' => 'agency_owner',
                'tenant_id' => $tenant->id,
            ]
        );

        // ---------------------------------------------------------
        // 5. Tenant Specific Theme (Overrides Global)
        // ---------------------------------------------------------
        if (class_exists(TenantSetting::class)) {
            TenantSetting::updateOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'client_theme' => [
                        'primary' => '#7c3aed', // Violet/Purple for this agency
                        'secondary' => '#1e1b4b',
                    ]
                ]
            );
        }

        // ---------------------------------------------------------
        // 6. Create Agency Staff
        // ---------------------------------------------------------
        User::firstOrCreate(
            ['email' => 'staff@demo.com'],
            [
                'name' => 'Jane Staff',
                'password' => bcrypt('abc@1234'),
                'role' => 'staff',
                'tenant_id' => $tenant->id,
            ]
        );

        // Log Output
        $this->command->info('------------------------------------------');
        $this->command->info('✅ Database Seeding Complete!');
        $this->command->info('------------------------------------------');
        $this->command->info('👤 Super Admin:  admin@saas.com / abc@1234');
        $this->command->info('🏢 Agency Owner: owner@demo.com / abc@1234');
        $this->command->info('👷 Staff User:   staff@demo.com / abc@1234');
        $this->command->info('------------------------------------------');
    
    }
}
