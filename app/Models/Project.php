<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Project extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'client_id',
        'name',
        'description',
        'status',
        'priority',
        'start_date',
        'due_date',
        'completed_date',
        'budget',
        'estimated_hours',
        'actual_hours',
        'assigned_to',
        'created_by',
    ];
    
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_date' => 'date',
            'budget' => 'decimal:2',
            'estimated_hours' => 'integer',
            'actual_hours' => 'integer',
        ];
    }
    
    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
    
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
    
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
    
    public function content(): HasMany
    {
        return $this->hasMany(Content::class);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'in_progress');
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
}
