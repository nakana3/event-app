<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Event;

class FetchConnpass extends Command
{
    protected $signature = 'fetch:connpass';
    // コマンドの説明
    protected $description = 'Connpass API v2 test';

    /**
     * コマンドの実行
     */
    public function handle()
    {
        $this->info('接続テスト開始');

        // 設定の読み込み
        $baseUrl = config('services.connpass.base_url');
        $apiKey = config('services.connpass.api_key');

        // APIキーが設定されているか確認
        if (empty($apiKey)) {
            $this->error('エラー：APIキーが設定されていません。');
            return;
        }

        $url = $baseUrl  . 'events/?count=1';
        $this->info('アクセス先：' . $url);

        $response = Http::withHeaders([
            'X-API-KEY' => $apiKey,
            'User-Agent' => 'LaravelApp/1.0',
        ])->get($url);

        if ($response->failed()) {
            $this->error('通信失敗：' . $response->status());
            $this->error('詳細：' . $response->body());
            return;
        }

        $this->info('通信成功：' . $response->status());

        $data = $response->json();

        $events = $data['events'] ?? [];

        foreach ($events as $apiEvent) {
            $this->info(print_r($apiEvent, true));
        }
    }
}
