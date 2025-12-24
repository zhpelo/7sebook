<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    use HasFactory;

    protected $table = 'email_logs';

    protected $fillable = [
        'to_email',
        'subject',
        'content',
        'type',
        'status',
        'error_message',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    /**
     * 邮件状态常量
     */
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    /**
     * 邮件类型常量
     */
    const TYPE_PASSWORD_RESET = 'password_reset';

    /**
     * 记录邮件发送
     */
    public static function logEmail($toEmail, $subject, $content, $type = self::TYPE_PASSWORD_RESET, $status = self::STATUS_PENDING, $errorMessage = null)
    {
        return self::create([
            'to_email' => $toEmail,
            'subject' => $subject,
            'content' => $content,
            'type' => $type,
            'status' => $status,
            'error_message' => $errorMessage,
            'sent_at' => $status === self::STATUS_SENT ? now() : null,
        ]);
    }

    /**
     * 更新邮件发送状态
     */
    public function updateStatus($status, $errorMessage = null)
    {
        $this->status = $status;
        $this->error_message = $errorMessage;
        if ($status === self::STATUS_SENT) {
            $this->sent_at = now();
        }
        $this->save();
    }
}
