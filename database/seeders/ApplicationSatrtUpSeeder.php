<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Tenant;
use App\Models\TenantSetting;

class ApplicationSatrtUpSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
        $superAdmin->assignRole('super_admin');


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
                'enabled_modules' => '["leads","forms","webhooks","api_keys","ai_chats"]',
            ]
        );

        // ---------------------------------------------------------
        // 4. Create Agency Owner
        // ---------------------------------------------------------
        $owner = User::firstOrCreate(
            ['email' => 'owner@demo.com'],
            [
                'name' => 'John Agency',
                'password' => bcrypt('abc@1234'),
                'role' => 'agency_owner',
                'tenant_id' => $tenant->id,
            ]
        );
        $owner->assignRole('agency_owner');


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
        $staff = User::firstOrCreate(
            ['email' => 'staff@demo.com'],
            [
                'name' => 'Jane Staff',
                'password' => bcrypt('abc@1234'),
                'role' => 'staff',
                'tenant_id' => $tenant->id,
            ]
        );
        $staff->assignRole('staff');

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
