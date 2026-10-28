<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlogLink extends Model
{
    public const KIND_INTERNAL = 'internal';

    public const KIND_EXTERNAL = 'external';

    protected $fillable = [
        'blog_post_id',
        'label',
        'url',
        'kind',
        'sort_order',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(BlogPost::class, 'blog_post_id');
    }

    public function isExternal(): bool
    {
        return $this->kind === self::KIND_EXTERNAL;
    }
}
