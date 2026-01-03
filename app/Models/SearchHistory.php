<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchHistory extends Model
{
    use HasFactory;

    protected $table = 'search_history';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'ip_address',
        'search_query',
        'search_type',
        'results_count',
        'search_time',
    ];

    protected $casts = [
        'search_time' => 'datetime',
        'results_count' => 'integer',
    ];

    /**
     * 获取所属的用户
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
