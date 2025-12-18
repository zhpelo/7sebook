@extends('admin.layout')

@section('content')
<div class="max-w-md mx-auto">
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg p-6 md:p-8 text-center border border-gray-200 dark:border-gray-700">
        <div class="w-16 h-16 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-400 text-2xl"></i>
        </div>
        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100 mb-2">确认删除</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-6">
            确定要删除用户 <strong class="text-gray-900 dark:text-gray-100">{{ $delete_user->username }}</strong> 吗？
        </p>
        <p class="text-sm text-red-600 dark:text-red-400 mb-6">此操作不可恢复！</p>

        <div class="flex gap-3">
            <a href="{{ route('admin.delete_user', ['id' => $delete_user->id, 'confirm' => 'yes']) }}" 
               class="flex-1 bg-red-600 hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600 text-white py-3 rounded-lg transition-all font-medium text-center shadow-md hover:shadow-lg">
                确认删除
            </a>
            <a href="{{ route('admin.users') }}" class="flex-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-300 py-3 rounded-lg transition-all font-medium text-center">
                取消
            </a>
        </div>
    </div>
</div>
@endsection
