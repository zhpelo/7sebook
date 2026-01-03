<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>密码重置 - 七秒电子书</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            padding: 40px;
        }
        .email-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .email-header h1 {
            color: #dc2626;
            font-size: 24px;
            margin: 0;
        }
        .email-content {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 30px;
            margin-bottom: 30px;
        }
        .email-content p {
            margin: 0 0 15px 0;
            color: #4b5563;
        }
        .reset-button {
            display: inline-block;
            background-color: #dc2626;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            margin: 20px 0;
            font-weight: 500;
        }
        .reset-button:hover {
            background-color: #b91c1c;
        }
        .reset-link {
            word-break: break-all;
            color: #dc2626;
            text-decoration: none;
            background-color: #fee2e2;
            padding: 10px;
            border-radius: 4px;
            display: block;
            margin: 15px 0;
        }
        .email-footer {
            text-align: center;
            color: #6b7280;
            font-size: 12px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
        }
        .warning {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning p {
            margin: 0;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>七秒电子书</h1>
        </div>
        
        <div class="email-content">
            @if($username)
                <p>尊敬的 <strong>{{ $username }}</strong>，您好！</p>
            @else
                <p>您好！</p>
            @endif
            
            <p>我们收到了您的密码重置请求。请点击下面的按钮重置您的密码：</p>
            
            <div style="text-align: center;">
                <a href="{{ $resetUrl }}" class="reset-button">重置密码</a>
            </div>
            
            <p>如果按钮无法点击，请复制以下链接到浏览器中打开：</p>
            <a href="{{ $resetUrl }}" class="reset-link">{{ $resetUrl }}</a>
            
            <div class="warning">
                <p><strong>重要提示：</strong></p>
                <p>• 此链接将在 24 小时后过期</p>
                <p>• 如果您没有请求重置密码，请忽略此邮件</p>
                <p>• 为了您的账户安全，请不要将链接分享给他人</p>
            </div>
        </div>
        
        <div class="email-footer">
            <p>此邮件由系统自动发送，请勿回复。</p>
            <p>&copy; {{ date('Y') }} 七秒电子书 版权所有</p>
        </div>
    </div>
</body>
</html>
