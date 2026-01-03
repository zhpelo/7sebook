<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownloadLog extends Model
{
    use HasFactory;

    protected $table = 'download_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'book_id',
        'download_time',
        'ip_address',
    ];

    protected $casts = [
        'download_time' => 'datetime',
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
