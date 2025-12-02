<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 自動化スケジュール設定

// 1. [毎日 00:00] 新規で追加されたイベントの取得
Schedule::command('fetch:connpass --mode=daily')->dailyAt('00:00');

// 2. [毎週日曜 12:00] 1週間分の更新をチェック
// 条件：今日が１日の場合はスキップ
Schedule::command('fetch:connpass --mode=weekly')->weeklyOn(0, '12:00')->when(function () {
    return Carbon::now()->day !== 1;
});

// 3. [毎月１日 12:00] １ヶ月分の全チェック
Schedule::command('fetch:connpass --mode=monthly')->monthlyOn(1, '12:00');
