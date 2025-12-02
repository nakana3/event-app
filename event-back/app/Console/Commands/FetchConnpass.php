<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Event;
use Carbon\Carbon;

class FetchConnpass extends Command
{
    // コマンド実行時に --mode=daily のようにオプションを指定できるようにする
    protected $signature = 'fetch:connpass {--mode=daily : 取得モード (daily, weekly, monthly)}';

    protected $description = 'Connpass API v2 からイベントを取得・保存します';

    public function handle()
    {
        $mode = $this->option('mode');
        $this->info("=== {$mode} モードで実行開始 ===");

        // 設定の読み込み
        $baseUrl = config('services.connpass.base_url'); // 末尾は events/
        $apiKey = config('services.connpass.api_key');

        if (empty($apiKey)) {
            $this->error('エラー：APIキーが設定されていません。');
            return;
        }

        // --- モードごとのパラメータ設定 ---
        $params = [
            'count' => 100, // 一度の最大取得件数
            'format' => 'json',
        ];

        if ($mode === 'daily') {
            // [日次] 新規チェック: 新着順(order=3)で直近100件だけ確認すればOK
            // ※全件取る必要はないので、このモードだけループしない設定にします
            $params['order'] = 3; // 新着順
            $this->fetchAndSave($baseUrl, $apiKey, $params, false); // false = ループしない

        } elseif ($mode === 'weekly') {
            // [週次] 直近1週間の更新チェック: 開催日順(order=2)で、今日から7日後まで
            $params['order'] = 2; // 開催日順

            // ymdパラメータで範囲指定 (例: 20251201,20251202...)
            // Connpassは範囲指定ができないので、カンマ区切りで7日分指定します
            $dates = [];
            for ($i = 0; $i < 7; $i++) {
                $dates[] = Carbon::today()->addDays($i)->format('Ymd');
            }
            $params['ymd'] = implode(',', $dates);

            // 全件取得モードで実行
            $this->fetchAndSave($baseUrl, $apiKey, $params, true);
        } elseif ($mode === 'monthly') {
            // [月次] 今月の全チェック: ymパラメータで月指定
            $params['order'] = 2;
            $params['ym'] = Carbon::today()->format('Ym'); // 今月 (例: 202512)

            // 全件取得モードで実行
            $this->fetchAndSave($baseUrl, $apiKey, $params, true);
        }

        $this->info("=== 全処理完了 ===");
    }

    /**
     * APIからデータを取得して保存する共通処理
     * @param bool $fetchAll trueならページネーションして全件取得する
     */
    private function fetchAndSave($baseUrl, $apiKey, $params, $fetchAll)
    {
        $start = 1; // 取得開始位置

        do {
            // ページネーション用の位置指定
            $params['start'] = $start;

            $this->info("取得開始: start={$start}...");

            $response = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'User-Agent' => 'LaravelApp/1.0',
            ])->get($baseUrl, $params);

            if ($response->failed()) {
                $this->error('通信失敗: ' . $response->status());
                // エラーなら中断
                return;
            }

            $data = $response->json();
            $events = $data['events'] ?? [];
            $resultsAvailable = $data['results_available'] ?? 0; // 総ヒット件数

            $this->info("取得件数: " . count($events) . " / 総件数: {$resultsAvailable}");

            // --- 保存処理 ---
            foreach ($events as $apiEvent) {
                if (!isset($apiEvent['id'])) continue;

                Event::updateOrInsert(
                    ['source_name' => 'connpass', 'source_event_id' => (string)$apiEvent['id']],
                    [
                        'title' => $apiEvent['title'] ?? 'タイトルなし',
                        'event_url' => $apiEvent['url'] ?? '',
                        'description' => $apiEvent['description'] ?? '',
                        'started_at' => $apiEvent['started_at'] ?? null,
                        'ended_at' => $apiEvent['ended_at'] ?? null,
                        'location_text' => ($apiEvent['address'] ?? '') . ' ' . ($apiEvent['place'] ?? ''),
                        'updated_at' => now(),
                    ]
                );
            }
            $this->info("保存完了 (このページの分)");

            // ループしないモードならここで終了
            if (!$fetchAll) {
                break;
            }

            // 次の開始位置をセット
            $start += 100;

            // ★重要: API制限対策 (1秒に1回制限)
            // 次のページがある場合のみ待機
            if ($start <= $resultsAvailable) {
                $this->info("API制限のため1秒待機中...");
                sleep(1);
            }
        } while ($start <= $resultsAvailable); // 全件取り終わるまでループ
    }
}
