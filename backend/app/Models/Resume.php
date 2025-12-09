<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Resume extends Model
{
    use HasFactory, HasUuids;

    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'file_path',
        's3_url',
        'parsed_json',
        'skills',
        'experience',
        'education',
        'is_default',
    ];

    protected $casts = [
        'skills' => 'array',
        'experience' => 'array',
        'education' => 'array',
        'parsed_json' => 'array',
        'is_default' => 'boolean',
    ];

    /**
     * Get the user that owns the resume.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the applications for the resume.
     */
    public function applications(): HasMany
    {
        return $this->hasMany(Application::class);
    }
}

