<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Content extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'description',
        'type',
        'category',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'alt_text',
        'tags',
        'status',
        'client_id',
        'project_id',
        'created_by',
        'approved_by',
        'approved_at',
    ];
    
    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'approved_at' => 'datetime',
            'file_size' => 'integer',
        ];
    }
    
    // Relationships
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
    
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
    
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
    
    // Scopes
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }
    
    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }
    
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
    
    public function scopeImages($query)
    {
        return $query->where('type', 'image');
    }
    
    public function scopeVideos($query)
    {
        return $query->where('type', 'video');
    }
    
    // Accessors
    public function getFileSizeFormattedAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    public function getIsApprovedAttribute()
    {
        return $this->status === 'approved';
    }
}
