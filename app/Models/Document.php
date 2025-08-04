<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Document extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'mime_type',
        'category',
        'client_id',
        'project_id',
        'uploaded_by',
        'is_confidential',
    ];
    
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_confidential' => 'boolean',
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
    
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
    
    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }
    
    public function scopeConfidential($query)
    {
        return $query->where('is_confidential', true);
    }
    
    public function scopePublic($query)
    {
        return $query->where('is_confidential', false);
    }
    
    public function scopeForClient($query, $clientId)
    {
        return $query->where('client_id', $clientId);
    }
    
    public function scopeForProject($query, $projectId)
    {
        return $query->where('project_id', $projectId);
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
    
    public function getFileExtensionAttribute()
    {
        return pathinfo($this->file_name, PATHINFO_EXTENSION);
    }
    
    public function getIsImageAttribute()
    {
        return str_starts_with($this->mime_type, 'image/');
    }
    
    public function getIsPdfAttribute()
    {
        return $this->mime_type === 'application/pdf';
    }
}
