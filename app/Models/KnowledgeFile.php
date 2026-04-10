<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'status',
        'ingested_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'ingested_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(Agent::class);
    }
}
