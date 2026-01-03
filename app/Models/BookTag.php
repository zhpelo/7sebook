<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookTag extends Model
{
    use HasFactory;

    protected $table = 'book_tags';

    public $timestamps = false;

    protected $fillable = [
        'book_id',
        'tag_name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * 获取所属的书籍
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }
}
