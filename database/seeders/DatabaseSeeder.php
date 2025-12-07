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
        $this->call([
            RolesAndPermissionsSeeder::class, // Run Permissions Seeder First
            ApplicationSatrtUpSeeder::class
        ]);
    
    }
}
