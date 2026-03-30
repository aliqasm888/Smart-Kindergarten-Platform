<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    protected $fillable = [
        'type',
        'url',
        'original_name',
        'mime_type',
        'size',
        'link_title',
        'link_description'
    ];

    protected $appends = ['file_url'];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getFileUrlAttribute()
    {
        if ($this->type === 'link') {
            return $this->url;
        }

        return $this->url ? Storage::url($this->url) : null;
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isFile(): bool
    {
        return $this->type === 'file';
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isLink(): bool
    {
        return $this->type === 'link';
    }
}
