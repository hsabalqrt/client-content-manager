<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Task extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'description',
        'status',
        'priority',
        'type',
        'assigned_to',
        'project_id',
        'client_id',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'start_time',
        'end_time',
        'notes',
        'created_by',
    ];
    
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'start_time' => 'datetime',
            'end_time' => 'datetime',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
        ];
    }
    
    // Relationships
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    // Scopes
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }
    
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }
    
    public function scopePending($query)
    {
        return $query->whereIn('status', ['todo', 'in_progress']);
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
                    ->whereNotIn('status', ['completed', 'cancelled']);
    }
    
    public function scopeToday($query)
    {
        return $query->whereDate('due_date', today());
    }
    
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('due_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }
    
    // Accessors
    public function getIsOverdueAttribute()
    {
        return $this->due_date && $this->due_date < now() && !in_array($this->status, ['completed', 'cancelled']);
    }
    
    public function getTimeSpentAttribute()
    {
        if ($this->start_time && $this->end_time) {
            return $this->start_time->diffInHours($this->end_time);
        }
        return 0;
    }
    
    public function getProgressPercentageAttribute()
    {
        if (!$this->estimated_hours) return 0;
        
        $progress = ($this->actual_hours / $this->estimated_hours) * 100;
        return min($progress, 100);
    }
}
