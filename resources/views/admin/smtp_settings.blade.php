@extends('admin.layout')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md p-4 md:p-6 lg:p-8 border border-gray-200 dark:border-gray-700">
        <h2 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-gray-100 mb-6">SMTP邮件发送设置</h2>

        <form method="POST" action="{{ route('admin.smtp_settings.post') }}">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="smtp_host" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP主机</label>
                    <input type="text" id="smtp_host" name="smtp_host" value="{{ old('smtp_host', $settings['smtp_host']) }}" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div>
                    <label for="smtp_port" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">端口</label>
                    <input type="number" id="smtp_port" name="smtp_port" min="1" max="65535" value="{{ old('smtp_port', $settings['smtp_port']) }}" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="smtp_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP账号</label>
                    <input type="text" id="smtp_username" name="smtp_username" value="{{ old('smtp_username', $settings['smtp_username']) }}" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div>
                    <label for="smtp_encryption" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">加密方式</label>
                    <select id="smtp_encryption" name="smtp_encryption"
                            class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        <option value="ssl" {{ old('smtp_encryption', $settings['smtp_encryption']) === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="tls" {{ old('smtp_encryption', $settings['smtp_encryption']) === 'tls' ? 'selected' : '' }}>TLS</option>
                        <option value="none" {{ old('smtp_encryption', $settings['smtp_encryption']) === 'none' ? 'selected' : '' }}>无</option>
                    </select>
                </div>
            </div>

            <div class="mb-4">
                <label for="smtp_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SMTP密码</label>
                <input type="password" id="smtp_password" name="smtp_password" autocomplete="new-password"
                       class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">留空则保持当前密码不变</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <div>
                    <label for="smtp_from_address" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">发件人邮箱</label>
                    <input type="email" id="smtp_from_address" name="smtp_from_address" value="{{ old('smtp_from_address', $settings['smtp_from_address']) }}" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
                <div>
                    <label for="smtp_from_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">发件人名称</label>
                    <input type="text" id="smtp_from_name" name="smtp_from_name" value="{{ old('smtp_from_name', $settings['smtp_from_name']) }}" required
                           class="w-full px-4 py-3 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                </div>
            </div>

            <button type="submit" class="w-full bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white py-3 rounded-lg transition-all font-medium shadow-md hover:shadow-lg">
                保存SMTP设置
            </button>
        </form>
    </div>
</div>
@endsection
