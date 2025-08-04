# Database Schema Design - Client & Content Management System

## Overview
This document outlines the database schema for a Laravel Filament application that manages clients, projects, invoices, employees, content, and tasks with role-based access control.

## Core Entities

### 1. Users (Extended)
**Purpose**: System users with different roles and permissions
```sql
users:
- id (primary key)
- name (string)
- email (string, unique)
- email_verified_at (timestamp, nullable)
- password (string)
- role (enum: 'manager', 'content_writer', 'designer', 'hr')
- department_id (foreign key, nullable)
- is_active (boolean, default true)
- avatar (string, nullable)
- phone (string, nullable)
- hire_date (date, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

### 2. Clients
**Purpose**: Client profiles and contact information
```sql
clients:
- id (primary key)
- name (string)
- company_name (string, nullable)
- email (string)
- phone (string, nullable)
- address (text, nullable)
- city (string, nullable)
- state (string, nullable)
- postal_code (string, nullable)
- country (string, nullable)
- website (string, nullable)
- industry (string, nullable)
- status (enum: 'active', 'inactive', 'prospective')
- notes (text, nullable)
- created_by (foreign key: users.id)
- created_at (timestamp)
- updated_at (timestamp)
```

### 3. Projects
**Purpose**: Project tracking linked to clients
```sql
projects:
- id (primary key)
- client_id (foreign key: clients.id)
- name (string)
- description (text, nullable)
- status (enum: 'planning', 'in_progress', 'review', 'completed', 'cancelled')
- priority (enum: 'low', 'medium', 'high', 'urgent')
- start_date (date, nullable)
- due_date (date, nullable)
- completed_date (date, nullable)
- budget (decimal, nullable)
- estimated_hours (integer, nullable)
- actual_hours (integer, nullable)
- assigned_to (foreign key: users.id, nullable)
- created_by (foreign key: users.id)
- created_at (timestamp)
- updated_at (timestamp)
```

### 4. Invoices
**Purpose**: Billing and invoicing system
```sql
invoices:
- id (primary key)
- client_id (foreign key: clients.id)
- project_id (foreign key: projects.id, nullable)
- invoice_number (string, unique)
- issue_date (date)
- due_date (date)
- status (enum: 'draft', 'sent', 'paid', 'overdue', 'cancelled')
- subtotal (decimal)
- tax_rate (decimal, default 0)
- tax_amount (decimal, default 0)
- total_amount (decimal)
- paid_amount (decimal, default 0)
- payment_date (date, nullable)
- notes (text, nullable)
- created_by (foreign key: users.id)
- created_at (timestamp)
- updated_at (timestamp)
```

### 5. Invoice Items
**Purpose**: Line items for invoices
```sql
invoice_items:
- id (primary key)
- invoice_id (foreign key: invoices.id)
- description (string)
- quantity (decimal)
- rate (decimal)
- amount (decimal)
- created_at (timestamp)
- updated_at (timestamp)
```

### 6. Employees
**Purpose**: Employee management for HR
```sql
employees:
- id (primary key)
- user_id (foreign key: users.id, nullable) // Links to system user if they have access
- employee_id (string, unique)
- first_name (string)
- last_name (string)
- email (string, unique)
- phone (string, nullable)
- address (text, nullable)
- department_id (foreign key: departments.id)
- position (string)
- salary (decimal, nullable)
- hire_date (date)
- termination_date (date, nullable)
- status (enum: 'active', 'inactive', 'terminated')
- emergency_contact_name (string, nullable)
- emergency_contact_phone (string, nullable)
- notes (text, nullable)
- created_by (foreign key: users.id)
- created_at (timestamp)
- updated_at (timestamp)
```

### 7. Departments
**Purpose**: Company departments
```sql
departments:
- id (primary key)
- name (string)
- description (text, nullable)
- manager_id (foreign key: employees.id, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

### 8. Content
**Purpose**: Media files and marketing materials
```sql
content:
- id (primary key)
- title (string)
- description (text, nullable)
- type (enum: 'image', 'video', 'document', 'marketing_material')
- category (string, nullable)
- file_path (string)
- file_name (string)
- file_size (integer)
- mime_type (string)
- alt_text (string, nullable) // For images
- tags (json, nullable)
- status (enum: 'draft', 'approved', 'archived')
- client_id (foreign key: clients.id, nullable) // If client-specific
- project_id (foreign key: projects.id, nullable) // If project-specific
- created_by (foreign key: users.id)
- approved_by (foreign key: users.id, nullable)
- approved_at (timestamp, nullable)
- created_at (timestamp)
- updated_at (timestamp)
```

### 9. Tasks
**Purpose**: Daily task management for designers
```sql
tasks:
- id (primary key)
- title (string)
- description (text, nullable)
- status (enum: 'todo', 'in_progress', 'review', 'completed', 'cancelled')
- priority (enum: 'low', 'medium', 'high', 'urgent')
- type (enum: 'design', 'development', 'content', 'meeting', 'other')
- assigned_to (foreign key: users.id)
- project_id (foreign key: projects.id, nullable)
- client_id (foreign key: clients.id, nullable)
- due_date (date, nullable)
- estimated_hours (decimal, nullable)
- actual_hours (decimal, nullable)
- start_time (datetime, nullable)
- end_time (datetime, nullable)
- notes (text, nullable)
- created_by (foreign key: users.id)
- created_at (timestamp)
- updated_at (timestamp)
```

### 10. Documents
**Purpose**: Document storage linked to clients/projects
```sql
documents:
- id (primary key)
- title (string)
- description (text, nullable)
- file_path (string)
- file_name (string)
- file_size (integer)
- mime_type (string)
- category (enum: 'contract', 'proposal', 'invoice', 'receipt', 'other')
- client_id (foreign key: clients.id, nullable)
- project_id (foreign key: projects.id, nullable)
- uploaded_by (foreign key: users.id)
- is_confidential (boolean, default false)
- created_at (timestamp)
- updated_at (timestamp)
```

## Role Permissions

### Manager
- Full access to all modules
- Can create, read, update, delete all records
- Dashboard with overview of all data

### Content Writer
- Full access to Content module
- Read access to Clients and Projects (for context)
- Can create/edit content, marketing materials
- Limited dashboard focused on content metrics

### Designer  
- Full access to Tasks module
- Read access to Projects and Clients (for context)
- Can view and update assigned tasks
- Dashboard focused on task management and daily schedule

### HR
- Full access to Employees and Departments modules
- Read access to Users (for employee-user linking)
- Can manage employee records, departments
- Dashboard with HR metrics and employee information

## Relationships

1. **One-to-Many**:
   - Client → Projects
   - Client → Invoices
   - Client → Documents
   - Project → Tasks
   - Project → Documents
   - Invoice → Invoice Items
   - Department → Employees
   - User → Created Records (clients, projects, etc.)

2. **Many-to-One**:
   - Employee → Department
   - Employee → User (optional)
   - Task → User (assigned_to)
   - Task → Project
   - Content → User (created_by)

3. **Polymorphic** (optional future enhancement):
   - Comments (can belong to projects, tasks, clients)
   - Activities/Audit logs

## Indexes for Performance

```sql
-- Essential indexes
CREATE INDEX idx_clients_status ON clients(status);
CREATE INDEX idx_projects_client_id ON projects(client_id);
CREATE INDEX idx_projects_status ON projects(status);
CREATE INDEX idx_invoices_client_id ON invoices(client_id);
CREATE INDEX idx_invoices_status ON invoices(status);
CREATE INDEX idx_tasks_assigned_to ON tasks(assigned_to);
CREATE INDEX idx_tasks_status ON tasks(status);
CREATE INDEX idx_content_type ON content(type);
CREATE INDEX idx_documents_client_id ON documents(client_id);
CREATE INDEX idx_employees_department_id ON employees(department_id);
```

This schema provides a solid foundation for the client and content management system with proper relationships, role-based access control, and efficient querying capabilities.