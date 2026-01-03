@extends('layout')

@section('content')
<div class="py-8 md:py-12">
    <div class="max-w-md mx-auto px-4">
        <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 md:p-8 lg:p-10 border border-gray-200 dark:border-gray-700">
            <div class="text-center mb-6">
                <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-user-plus text-green-600 dark:text-green-400 text-2xl"></i>
                </div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-gray-100">用户注册</h1>
            </div>
            
            @if(session('error'))
                <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 px-4 py-3 rounded-xl text-sm">
                    <i class="fas fa-exclamation-circle mr-2"></i>{{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('register.post') }}" id="registerForm">
                @csrf
                <input type="hidden" name="_token" value="{{ csrf_token() }}" id="csrfToken">
                <div class="mb-4">
                    <label for="reg_username" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">用户名</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <input type="text" id="reg_username" name="reg_username" required autofocus
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">4-20个字符，以字母开头</p>
                </div>
                
                <div class="mb-4">
                    <label for="reg_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">邮箱</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <input type="email" id="reg_email" name="reg_email" required
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                </div>
                
                <div class="mb-6">
                    <label for="reg_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">密码</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400 dark:text-gray-500"></i>
                        </div>
                        <input type="password" id="reg_password" name="reg_password" required
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-xl focus:outline-none focus:ring-2 focus:ring-red-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">至少6个字符</p>
                </div>
                
                <button type="submit" class="w-full bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white py-3 rounded-xl transition-all font-medium text-base shadow-md hover:shadow-lg mb-4">
                    注册
                </button>
            </form>

            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    已有账号？
                    <a href="{{ route('login', request()->has('intended') ? ['intended' => request()->get('intended')] : (session('intended_url') ? ['intended' => session('intended_url')] : [])) }}" class="text-red-600 dark:text-red-400 font-medium hover:text-red-700 dark:hover:text-red-300">立即登录</a>
                </p>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const registerForm = document.getElementById('registerForm');
    const csrfTokenInput = document.getElementById('csrfToken');
    const metaToken = document.querySelector('meta[name="csrf-token"]');
    
    // 确保CSRF token是最新的
    function updateCsrfToken() {
        if (metaToken && csrfTokenInput) {
            const newToken = metaToken.getAttribute('content');
            if (newToken) {
                csrfTokenInput.value = newToken;
                const formToken = registerForm?.querySelector('input[name="_token"]');
                if (formToken) {
                    formToken.value = newToken;
                }
            }
        }
    }
    
    if (registerForm && csrfTokenInput) {
        // 页面加载时更新token
        updateCsrfToken();
        
        // 监听表单提交，确保使用最新的token
        registerForm.addEventListener('submit', function(e) {
            updateCsrfToken();
        });
        
        // 如果页面提示token过期，自动刷新页面
        @if(session('error'))
            const errorMsg = @json(session('error'));
            if (errorMsg && (errorMsg.includes('过期') || errorMsg.includes('419'))) {
                setTimeout(function() {
                    window.location.reload();
                }, 3000);
            }
        @endif
    }
});
</script>
@endpush
@endsection
