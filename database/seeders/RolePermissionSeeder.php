<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Client management
            'view_clients',
            'create_clients',
            'edit_clients',
            'delete_clients',
            
            // Project management
            'view_projects',
            'create_projects',
            'edit_projects',
            'delete_projects',
            
            // Invoice management
            'view_invoices',
            'create_invoices',
            'edit_invoices',
            'delete_invoices',
            
            // Employee management
            'view_employees',
            'create_employees',
            'edit_employees',
            'delete_employees',
            
            // Department management
            'view_departments',
            'create_departments',
            'edit_departments',
            'delete_departments',
            
            // Content management
            'view_content',
            'create_content',
            'edit_content',
            'delete_content',
            'approve_content',
            
            // Task management
            'view_tasks',
            'create_tasks',
            'edit_tasks',
            'delete_tasks',
            'assign_tasks',
            
            // Document management
            'view_documents',
            'upload_documents',
            'edit_documents',
            'delete_documents',
            
            // User management
            'view_users',
            'create_users',
            'edit_users',
            'delete_users',
            
            // Dashboard access
            'view_dashboard',
            'view_analytics',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Manager - Full access to everything
        $manager = Role::create(['name' => 'manager']);
        $manager->givePermissionTo(Permission::all());
        
        // Content Writer - Focus on content management
        $contentWriter = Role::create(['name' => 'content_writer']);
        $contentWriter->givePermissionTo([
            'view_dashboard',
            'view_content',
            'create_content',
            'edit_content',
            'delete_content',
            'view_clients',      // Read-only for context
            'view_projects',     // Read-only for context
            'upload_documents',
            'view_documents',
        ]);
        
        // Designer - Focus on task management and viewing
        $designer = Role::create(['name' => 'designer']);
        $designer->givePermissionTo([
            'view_dashboard',
            'view_tasks',
            'edit_tasks',        // Can update their assigned tasks
            'view_projects',     // Read-only for context
            'view_clients',      // Read-only for context
            'view_content',      // Can view content for design reference
        ]);
        
        // HR - Focus on employee management
        $hr = Role::create(['name' => 'hr']);
        $hr->givePermissionTo([
            'view_dashboard',
            'view_employees',
            'create_employees',
            'edit_employees',
            'delete_employees',
            'view_departments',
            'create_departments',
            'edit_departments',
            'delete_departments',
            'view_users',        // Can see user accounts linked to employees
            'create_users',      // Can create accounts for new employees
            'edit_users',        // Can update user accounts
        ]);
        
        $this->command->info('Roles and permissions created successfully!');
    }
}