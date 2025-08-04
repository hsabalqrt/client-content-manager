<?php

namespace App\Filament\Widgets;

use App\Models\Client;
use App\Models\Project;
use App\Models\Invoice;
use App\Models\Task;
use App\Models\Employee;
use App\Models\Content;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        $stats = [];
        
        // Manager gets overview of everything
        if ($user->hasRole('manager')) {
            $stats = [
                Stat::make('Total Clients', Client::count())
                    ->description('Active and prospective clients')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('success'),
                    
                Stat::make('Active Projects', Project::active()->count())
                    ->description('Currently in progress')
                    ->descriptionIcon('heroicon-m-briefcase')
                    ->color('primary'),
                    
                Stat::make('Outstanding Invoices', Invoice::unpaid()->count())
                    ->description('Awaiting payment')
                    ->descriptionIcon('heroicon-m-banknotes')
                    ->color('warning'),
                    
                Stat::make('Active Employees', Employee::active()->count())
                    ->description('Current workforce')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('info'),
            ];
        }
        
        // Content writer sees content-related stats
        elseif ($user->hasRole('content_writer')) {
            $stats = [
                Stat::make('Pending Content', Content::where('status', 'draft')->count())
                    ->description('Awaiting approval')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),
                    
                Stat::make('Approved Content', Content::where('status', 'approved')->count())
                    ->description('Ready to use')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),
                    
                Stat::make('My Projects', Project::whereHas('content', function($query) use ($user) {
                        $query->where('created_by', $user->id);
                    })->count())
                    ->description('Projects with my content')
                    ->descriptionIcon('heroicon-m-briefcase')
                    ->color('primary'),
                    
                Stat::make('Total Clients', Client::count())
                    ->description('Available for projects')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('info'),
            ];
        }
        
        // Designer sees task-related stats
        elseif ($user->hasRole('designer')) {
            $stats = [
                Stat::make('My Open Tasks', Task::assignedTo($user->id)->pending()->count())
                    ->description('Tasks to complete')
                    ->descriptionIcon('heroicon-m-clipboard-document-list')
                    ->color('warning'),
                    
                Stat::make('Overdue Tasks', Task::assignedTo($user->id)->overdue()->count())
                    ->description('Past due date')
                    ->descriptionIcon('heroicon-m-exclamation-triangle')
                    ->color('danger'),
                    
                Stat::make('Completed This Week', Task::assignedTo($user->id)->completed()
                        ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
                        ->count())
                    ->description('Tasks finished')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),
                    
                Stat::make('Active Projects', Project::whereHas('tasks', function($query) use ($user) {
                        $query->where('assigned_to', $user->id);
                    })->count())
                    ->description('Projects I\'m working on')
                    ->descriptionIcon('heroicon-m-briefcase')
                    ->color('primary'),
            ];
        }
        
        // HR sees employee-related stats
        elseif ($user->hasRole('hr')) {
            $stats = [
                Stat::make('Total Employees', Employee::active()->count())
                    ->description('Active employees')
                    ->descriptionIcon('heroicon-m-users')
                    ->color('success'),
                    
                Stat::make('New Hires This Month', Employee::whereMonth('hire_date', now()->month)
                        ->whereYear('hire_date', now()->year)
                        ->count())
                    ->description('Recent additions')
                    ->descriptionIcon('heroicon-m-user-plus')
                    ->color('info'),
                    
                Stat::make('Work Anniversaries', Employee::whereMonth('hire_date', now()->month)
                        ->whereYear('hire_date', '<', now()->year)
                        ->count())
                    ->description('This month')
                    ->descriptionIcon('heroicon-m-gift')
                    ->color('warning'),
                    
                Stat::make('Departments', \App\Models\Department::count())
                    ->description('Company departments')
                    ->descriptionIcon('heroicon-m-building-office')
                    ->color('primary'),
            ];
        }
        
        // Default stats for other roles
        else {
            $stats = [
                Stat::make('Total Clients', Client::count())
                    ->description('All clients')
                    ->descriptionIcon('heroicon-m-user-group')
                    ->color('primary'),
                    
                Stat::make('Active Projects', Project::active()->count())
                    ->description('In progress')
                    ->descriptionIcon('heroicon-m-briefcase')
                    ->color('success'),
                    
                Stat::make('Total Tasks', Task::count())
                    ->description('All tasks')
                    ->descriptionIcon('heroicon-m-clipboard-document-list')
                    ->color('info'),
                    
                Stat::make('Total Content', Content::count())
                    ->description('All content items')
                    ->descriptionIcon('heroicon-m-photo')
                    ->color('warning'),
            ];
        }
        
        return $stats;
    }
}