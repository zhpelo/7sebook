<?php

namespace App\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Support\Facades\Hash;

class CustomUserProvider implements UserProvider
{
    public function retrieveById($identifier)
    {
        return User::find($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // Not implemented
    }

    public function retrieveByCredentials(array $credentials)
    {
        if (!isset($credentials['username'])) {
            return null;
        }

        return User::where('username', $credentials['username'])->first();
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        if (!isset($credentials['password'])) {
            return false;
        }

        return Hash::check($credentials['password'], $user->password_hash);
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false): void
    {
        // 如果需要重新哈希密码（例如密码算法更新），可以在这里实现
        // 当前实现保持原样，不进行重新哈希
    }
}
