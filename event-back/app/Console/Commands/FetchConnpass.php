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

        $this->info(count($events) . '件のイベントが見つかりました。保存を開始します...');

        foreach ($events as $apiEvent) {
            // event_id がない場合はスキップ（念のため）
            if (!isset($apiEvent['event_id'])) {
                continue;
            }

            // DBに保存（あれば更新、なければ新規作成）
            Event::updateOrInsert(
                // 1. 検索条件 (重複チェック)
                [
                    'source_name' => 'connpass',
                    'source_event_id' => (string)$apiEvent['event_id'],
                ],
                // 2. 保存するデータ内容
                [
                    'title' => $apiEvent['title'] ?? 'タイトルなし',
                    'event_url' => $apiEvent['event_url'] ?? '',
                    'description' => $apiEvent['description'] ?? '',
                    'started_at' => $apiEvent['started_at'] ?? null,
                    'ended_at' => $apiEvent['ended_at'] ?? null,

                    // 住所と会場名を結合して保存
                    'location_text' => ($apiEvent['address'] ?? '') . ' ' . ($apiEvent['place'] ?? ''),

                    'updated_at' => now(),
                    // created_at は updateOrInsert では自動設定されないため、
                    // 厳密にはここに含まないのが一般的ですが、簡易的に updated_at で代用します
                ]
            );

            $this->info("保存完了: " . ($apiEvent['title'] ?? '不明なタイトル'));
        }

        $this->info('全ての処理が完了しました！');
    }
}
