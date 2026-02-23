<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'username',
        'password_hash',
        'email',
        'role',
        'is_banned',
        'ban_reason',
        'banned_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password_hash',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'is_banned' => 'boolean',
            'banned_at' => 'datetime',
        ];
    }

    /**
     * Get the password for authentication.
     */
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    /**
     * Get the name of the unique identifier for the user.
     */
    public function getAuthIdentifierName()
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user.
     */
    public function getAuthIdentifier()
    {
        return $this->id;
    }

    /**
     * 获取用户点赞的书籍
     */
    public function likedBooks()
    {
        return $this->belongsToMany(Book::class, 'book_likes')
            ->withTimestamps()
            ->withPivot('created_at');
    }

    /**
     * 获取用户收藏的书籍
     */
    public function favoritedBooks()
    {
        return $this->belongsToMany(Book::class, 'book_favorites')
            ->withTimestamps()
            ->withPivot('created_at');
    }

    /**
     * 获取用户的下载记录
     */
    public function downloadLogs()
    {
        return $this->hasMany(DownloadLog::class);
    }

    /**
     * 获取用户的搜索历史
     */
    public function searchHistory()
    {
        return $this->hasMany(SearchHistory::class);
    }

    /**
     * 检查用户是否是管理员
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
}
