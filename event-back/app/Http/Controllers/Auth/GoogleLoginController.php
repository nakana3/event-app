<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoogleLoginController extends Controller
{
    // 1. Googleのログイン画面へリダイレクト
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // 2. Googleから戻ってきた時の処理
    public function handleGoogleCallback()
    {
        try {
            // Googleからユーザー情報を取得
            $googleUser = Socialite::driver('google')->user();

            // メールアドレスでユーザーを探す、なければ作る
            $user = User::updateOrCreate(
                ['email' => $googleUser->getEmail()],
                [
                    'name' => $googleUser->getName(),
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'password' => null, // パスワードはなし
                ]
            );

            // ログイン状態にする (Sanctumトークン発行)
            $token = $user->createToken('auth_token')->plainTextToken;

            // フロントエンド(React)へリダイレクト（トークンを持たせて返す）
            // ポート5174(React)へ飛ばします
            return redirect("http://localhost:5174/login/callback?token={$token}");
        } catch (\Exception $e) {
            return redirect('http://localhost:5174/login?error=failed');
        }
    }
}
