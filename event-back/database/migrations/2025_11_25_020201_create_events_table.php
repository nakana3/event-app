<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id(); // あなたのDB内での管理ID

            // 情報源の管理
            $table->string('source_name', 50); // connpass, doorkeeper等
            $table->string('source_event_id'); // 向こう側のID

            // イベント基本情報
            $table->string('title');
            $table->text('event_url');
            $table->longText('description')->nullable(); // 詳細はない場合もあるのでnullable

            // 日時情報
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('application_deadline')->nullable();

            // 場所情報（まずは生テキストで保存）
            $table->text('location_text')->nullable();

            $table->timestamps(); // 作成日時・更新日時を自動管理

            // 【重要】同じサイトの同じイベントIDは重複登録させない設定
            $table->unique(['source_name', 'source_event_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
