<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookFavorite extends Model
{
    use HasFactory;

    protected $table = 'book_favorites';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'book_id',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * 获取所属的用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 获取所属的书籍
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
