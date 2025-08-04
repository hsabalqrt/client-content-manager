<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'company_name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
        'website',
        'industry',
        'status',
        'notes',
        'created_by',
    ];
    
    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }
    
    // Relationships
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
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
    
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeProspective($query)
    {
        return $query->where('status', 'prospective');
    }
}
