<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'author',
        'description',
        'cover_url',
        'publish_date',
        'category',
        'isbn',
        'publisher',
        'published_at',
        'page_count',
        'languages',
        'identifiers',
        'price',
        'file_name',
        'file_path',
        'file_size',
        'file_format',
        'file_md5',
        'rating',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'publish_date' => 'date',
        'price' => 'decimal:2',
        'rating' => 'decimal:2',
        'page_count' => 'integer',
    ];

    /**
     * 获取书籍的标签
     */
    public function tags(): HasMany
    {
        return $this->hasMany(BookTag::class);
    }

    /**
     * 获取点赞此书籍的用户
     */
    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'book_likes')
            ->withTimestamps()
            ->withPivot('created_at');
    }

    /**
     * 获取收藏此书籍的用户
     */
    public function favoritedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'book_favorites')
            ->withTimestamps()
            ->withPivot('created_at');
    }

    /**
     * 获取下载记录
     */
    public function downloadLogs(): HasMany
    {
        return $this->hasMany(DownloadLog::class);
    }
}
