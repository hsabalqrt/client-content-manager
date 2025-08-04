# Client & Content Management System

A comprehensive Laravel Filament application for managing clients, projects, content, and internal operations with role-based access control.

## Overview

This system provides a complete business management solution with modules for:
- **Client Management**: Client profiles, contact information, and relationship tracking
- **Project Management**: Project tracking with time estimates, budgets, and status monitoring
- **Financial Management**: Invoicing, billing, and payment tracking
- **Document Management**: File storage and organization with categorization
- **Content Management**: Media files and marketing materials with approval workflows
- **HR Management**: Employee records, departments, and organizational structure
- **Task Management**: Daily task tracking with time logging and progress monitoring

## Technology Stack

- **Framework**: Laravel 11
- **Admin Panel**: Filament 3.3.34
- **Authentication**: Laravel Breeze with Spatie Laravel Permission
- **Database**: MySQL/PostgreSQL compatible
- **Frontend**: React 19 + TypeScript (if using the web template)
- **Package Manager**: Bun
- **Styling**: TailwindCSS V4

## User Roles & Permissions

### Manager
- **Full Access**: Complete system administration
- **Permissions**: All CRUD operations on all modules
- **Dashboard**: Overview of clients, projects, invoices, and employees
- **Navigation**: Access to all navigation groups

### Content Writer
- **Focus**: Content creation and management
- **Permissions**: 
  - Full access to content management
  - Read access to clients and projects (for context)
- **Dashboard**: Content approval status, project associations
- **Navigation**: Content Management, Client Management (read-only), Project Management (read-only)

### Designer
- **Focus**: Task management and daily operations
- **Permissions**:
  - Full access to task management
  - Read access to projects, clients, and content
- **Dashboard**: Personal task statistics, overdue alerts, progress tracking
- **Navigation**: Task Management, Project Management (read-only), Content Management (read-only)

### HR
- **Focus**: Employee and department management
- **Permissions**:
  - Full access to employee and department management
  - User account creation and management
- **Dashboard**: Employee statistics, anniversaries, new hires
- **Navigation**: HR Management, limited access to other modules

## Features by Module

### Client Management
- Complete client profiles with contact information
- Industry categorization and status tracking
- Address management and communication history
- Relationship with projects and invoices
- Advanced filtering and search capabilities

### Project Management
- Project lifecycle tracking (Planning → In Progress → Review → Completed)
- Client association and team assignment
- Budget and time estimation with actual tracking
- Priority management (Low, Medium, High, Urgent)
- Progress calculation and overdue detection
- Comprehensive filtering by status, client, assignee

### Financial Management
#### Invoices
- Professional invoice generation with unique numbering
- Client and project association
- Automatic tax calculations
- Payment tracking and balance management
- Status workflow (Draft → Sent → Paid/Overdue)
- Bulk operations and overdue notifications

#### Invoice Items
- Detailed line item management
- Automatic amount calculations (quantity × rate)
- Integration with parent invoices
- Category-based organization

### Document Management
- Secure file upload and storage
- Category-based organization (Contract, Proposal, Invoice, Receipt, Other)
- Client and project associations
- Confidentiality flags for sensitive documents
- File type validation and size limits
- Automatic file cleanup on deletion
- Preview and download capabilities

### Content Management
- Media file handling (Images, Videos, Documents, Marketing Materials)
- Approval workflow system (Draft → Approved → Archived)
- Tag-based organization for better discoverability
- Alt text support for accessibility
- File size optimization and preview generation
- Client and project associations
- Bulk approval operations

### HR Management
#### Employees
- Complete employee profiles with personal information
- Department assignments and position tracking
- Salary management and hire date records
- Emergency contact information
- Status management (Active, Inactive, Terminated)
- User account integration for system access
- Anniversary and milestone tracking

#### Departments
- Organizational structure management
- Manager assignments and employee counts
- Department descriptions and responsibilities
- Employee distribution analytics

### Task Management
- Comprehensive task tracking system
- Assignment and priority management
- Time tracking with start/end timestamps
- Progress calculation against estimates
- Project and client associations
- Status workflow (To Do → In Progress → Review → Completed)
- Personal task dashboards for team members
- Overdue detection and notifications

## Installation & Setup

### Prerequisites
- PHP 8.2+
- Composer 2.x
- Node.js 20.x
- Bun package manager
- MySQL 8.0+ or PostgreSQL 13+

### Installation Steps

1. **Clone and Install Dependencies**
   ```bash
   cd client-content-manager
   composer install
   bun install
   ```

2. **Environment Configuration**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

3. **Database Setup**
   ```bash
   # Configure database in .env file
   php artisan migrate
   php artisan db:seed
   ```

4. **Storage Setup**
   ```bash
   php artisan storage:link
   ```

5. **Development Server**
   ```bash
   php artisan serve
   # Application available at http://localhost:8000/admin
   ```

### Default Accounts

After seeding, the following accounts are available:

- **Manager**: admin@company.com
- **Content Writer**: writer@company.com  
- **Designer**: designer@company.com
- **HR**: hr@company.com

*Default password for all accounts: Check the seeder file for current password*

## Database Schema

### Core Entities
- **Users**: System authentication and role management
- **Clients**: Customer information and contact details
- **Projects**: Work tracking and management
- **Invoices/Invoice Items**: Financial transaction records
- **Employees**: Staff information and HR data
- **Departments**: Organizational structure
- **Content**: Media and marketing materials
- **Documents**: File storage and categorization
- **Tasks**: Work item tracking and time management

### Relationships
- Users → Employees (1:1 optional)
- Clients → Projects (1:many)
- Projects → Tasks (1:many)
- Projects → Invoices (1:many)
- Departments → Employees (1:many)
- Users → Created Records (1:many for audit trails)

## Navigation Structure

### Client Management
- **Clients**: Complete client database with search and filtering

### Project Management  
- **Projects**: Project tracking with status and progress monitoring

### Financial Management
- **Invoices**: Billing and payment management
- **Invoice Items**: Detailed line item administration

### Document Management
- **Documents**: Secure file storage and organization

### Content Management
- **Content**: Media files and marketing material workflows

### HR Management
- **Employees**: Staff records and personal information
- **Departments**: Organizational structure management

### Task Management
- **Tasks**: Daily work tracking and progress monitoring

## Security Features

- **Role-Based Access Control**: Granular permissions for each user role
- **File Upload Security**: Type validation and size limits
- **Audit Trails**: Created by/updated by tracking on all records
- **Session Management**: Secure authentication with Laravel Sanctum
- **Data Validation**: Comprehensive form validation and sanitization
- **Permission Gates**: Resource-level access control

## Performance Features

- **Eager Loading**: Optimized database queries with relationship preloading
- **Indexing**: Strategic database indexes for common query patterns
- **Caching**: Built-in Laravel caching for improved performance
- **File Optimization**: Automatic file cleanup and storage management
- **Pagination**: Efficient data loading for large datasets

## Customization Options

### Adding New Roles
1. Create role in `RolePermissionSeeder.php`
2. Define permissions for the role
3. Update navigation groups in `AdminPanelProvider.php`
4. Customize dashboard widgets in `StatsOverview.php`

### Adding New Modules
1. Create Eloquent model with relationships
2. Generate Filament resource
3. Add permissions to seeder
4. Update navigation groups
5. Add relevant dashboard statistics

### Theme Customization
- Primary colors configured in `AdminPanelProvider.php`
- Navigation groups and icons customizable
- Dashboard widgets role-specific
- Branding and favicon configurable

## API & Integrations

The system is built with Laravel's standard architecture, allowing easy integration with:
- External CRM systems
- Accounting software
- Time tracking tools
- Document management systems
- Email marketing platforms

## Maintenance & Updates

### Regular Tasks
- Database backup and maintenance
- File storage cleanup
- Permission audits
- Performance monitoring
- Security updates

### Monitoring
- User activity logs
- System performance metrics
- File storage usage
- Database growth monitoring
- Error tracking and resolution

## Support & Documentation

### Resources
- Laravel Documentation: https://laravel.com/docs
- Filament Documentation: https://filamentphp.com/docs
- Spatie Permission: https://spatie.be/docs/laravel-permission

### Development
- Follow Laravel coding standards
- Use Filament best practices for admin interfaces
- Implement proper error handling
- Write comprehensive tests for new features
- Document any customizations

## License

This project is proprietary software developed for internal business management. All rights reserved.

---

**Version**: 1.0.0  
**Last Updated**: August 4, 2025  
**Author**: Scout AI Development Team