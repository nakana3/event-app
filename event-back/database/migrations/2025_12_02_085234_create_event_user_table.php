<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_user', function (Blueprint $table) {
            $table->id();
            // 誰が？ (usersテーブルのidと紐付け)
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // どのイベント？ (eventsテーブルのidと紐付け)
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();

            // 状態 (例: 'interested' = 気になる, 'participating' = 参加予定)
            // 今回はシンプルに「いいね」機能として作るので、デフォルトを入れておきます
            $table->string('status')->default('interested');

            $table->timestamps();

            // 重複防止（同じ人が同じイベントを2回登録できないようにする）
            $table->unique(['user_id', 'event_id']);
        });
    }
};
