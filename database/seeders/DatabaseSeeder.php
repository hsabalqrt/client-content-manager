<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles and permissions first
        $this->call(RolePermissionSeeder::class);
        
        // Create basic departments
        \App\Models\Department::create([
            'name' => 'Management',
            'description' => 'Executive and management team'
        ]);
        
        \App\Models\Department::create([
            'name' => 'Creative',
            'description' => 'Design and content creation team'
        ]);
        
        \App\Models\Department::create([
            'name' => 'Human Resources',
            'description' => 'HR and employee management'
        ]);
        
        // Create admin user
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@company.com',
            'role' => 'manager',
            'is_active' => true,
        ]);
        
        // Assign manager role to admin
        $admin->assignRole('manager');
        
        // Create sample users for other roles
        $contentWriter = User::factory()->create([
            'name' => 'Content Writer',
            'email' => 'writer@company.com',
            'role' => 'content_writer',
            'is_active' => true,
        ]);
        $contentWriter->assignRole('content_writer');
        
        $designer = User::factory()->create([
            'name' => 'Designer',
            'email' => 'designer@company.com',
            'role' => 'designer',
            'is_active' => true,
        ]);
        $designer->assignRole('designer');
        
        $hr = User::factory()->create([
            'name' => 'HR Manager',
            'email' => 'hr@company.com',
            'role' => 'hr',
            'is_active' => true,
        ]);
        $hr->assignRole('hr');
    }
}
