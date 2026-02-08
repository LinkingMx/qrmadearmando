<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's database with roles and permissions.
     */
    public function run(): void
    {
        // Create BranchTerminal role for scanner access
        Role::firstOrCreate(
            ['name' => 'BranchTerminal'],
            ['guard_name' => 'web']
        );

        // Ensure Employee role exists
        Role::firstOrCreate(
            ['name' => 'Employee'],
            ['guard_name' => 'web']
        );

        $this->command->info('Roles seeded successfully: BranchTerminal, Employee');
    }
}
