@extends('layout')

@section('content')
<div class="py-8 md:py-12">
    <div class="max-w-md mx-auto px-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 md:p-8 lg:p-10 border border-gray-200 dark:border-gray-700">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-lock text-green-600 dark:text-green-400 text-2xl"></i>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100">重置密码</h1>
            </div>
            
            @if($reset_password_error)
                <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ $reset_password_error }}
                </div>
            @endif

            @if($reset_password_success)
                <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 px-4 py-3 rounded-xl text-sm">
                    <i class="fas fa-check-circle mr-2"></i>{{ $reset_password_success }}
                </div>
            @endif

            @if($reset_token && $reset_email)
                <form method="POST" action="{{ route('reset_password.post') }}">
                    @csrf
                    <input type="hidden" name="token" value="{{ $reset_token }}">
                    <input type="hidden" name="email" value="{{ $reset_email }}">
                    
                    <div class="mb-4">
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">新密码</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400 dark:text-gray-500"></i>
                            </div>
                            <input type="password" id="password" name="password" required
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">至少6个字符</p>
                    </div>
                    
                    <div class="mb-6">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">确认新密码</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-lock text-gray-400 dark:text-gray-500"></i>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        </div>
                    </div>
                    
                    <button type="submit" class="w-full bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white py-3 rounded-xl transition-all font-medium text-base shadow-md hover:shadow-lg mb-4">
                        重置密码
                    </button>
                </form>
            @endif

            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    <a href="{{ route('login') }}" class="text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300">返回登录</a>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
