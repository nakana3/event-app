<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Symfony\Component\DomCrawler\Crawler;
use App\Models\Event;

class FetchScraping extends Command
{
    protected $signature = 'fetch:scraping';

    protected $description = 'イベント情報をスクレイピングして保存します';

    private const MOZILLA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) ';
    private const APPLE_WEBKIT = 'AppleWebKit/537.36 (KHTML, like Gecko) ';
    private const CHROME = 'Chrome/91.0.34472.124 ';
    private const SAFARI = 'Safari/537.36 ';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('スクレイピングを開始します...');

        $url = 'https://talent.supporterz.jp/events/';

        $response = Http::withHeaders([
            'User-Agent' => self::MOZILLA . self::APPLE_WEBKIT . self::CHROME . self::SAFARI,
        ])->get($url);

        if ($response->failed()) {
            $this->error('アクセスに失敗。ステータスコード: ' . $response->status());
            return;
        }

        $crawler = new Crawler($response->body());

        $crawler->filter('#mainContent .eventList li')->each(function (Crawler $node) {
            try {
                // タイトル取得
                // h3タグの中のaタグのテキスト、またはh3直下のテキスト
                $titleNode = $node->filter('h3.title');
                if ($titleNode->count() === 0) return; // タイトルが取れなければスキップ
                $title = trim($titleNode->text());

                // URL取得
                $linkNode = $node->filter('h3.title a');
                $link = $linkNode->count() > 0 ? $linkNode->attr('href') : null;

                if (!$link) return;

                if (strpos($link, 'http') === false) {
                    $link = 'https://talent.supporterz.jp/events/' . $link;
                }

                // ID抽出 (URLから数字部分を抜き出す)
                // 例: https://techplay.jp/event/12345 -> 12345
                preg_match('/\/event\/(\d+)/', $link, $matches);
                $id = $matches[1] ?? md5($link); // IDがなければURLのハッシュ値で代用

                // 日時取得 (例: 2025/12/10 19:00)
                // TechPlayの日時は .datetime クラスなどに入っていることが多い
                $dateText = $node->filter('.event-list__item-date')->count() > 0 ? 
                    trim($node->filter('.event-list__item-date')->text()) : null;
                
                // 簡易的な日時変換（本来はもっと厳密にパースする必要があります）
                $startedAt = null;
                if ($dateText) {
                    // "2025.12.10 (水) 19:00〜21:00" のような形式を想定して整形
                    // 1. 曜日 (月)〜(日) を削除
                    $dateText = preg_replace('/\(.\)/', '', $dateText); 
                    // 2. "." や "〜" を扱いやすい文字に置換
                    $dateText = str_replace(['.', '〜'], ['-', ' '], $dateText); 
                    // 3. 最初の空白で分割して開始日時だけ取る ("2025-12-10 19:00")
                    $parts = explode(' ', $dateText);
                    if (count($parts) >= 2) {
                        $startedAt = date('Y-m-d H:i:s', strtotime($parts[0] . ' ' . $parts[1]));
                    }
                }

                $place = $node->filter('.event-list__item-place')->count() > 0 ? 
                    trim($node->filter('.event-list__item-place')->text()) : 'Webサイト参照';

                $this->info("取得: {$title}");

                // 5. DBに保存
                Event::updateOrInsert(
                    // 検索条件
                    ['source_name' => 'techplay', 'source_event_id' => $id],
                    // 保存内容
                    [
                        'title' => $title,
                        'event_url' => $link, // TechPlayは絶対パスの場合が多いが、相対パスならドメイン結合が必要
                        'description' => 'スクレイピング取得', // 詳細文は一覧からは取れないことが多い
                        'started_at' => $startedAt,
                        'location_text' => $place,
                        'updated_at' => now(),
                    ]
                );

            } catch (\Exception $e) {
                // 個別の要素でエラーが出ても、全体を止めずに次へ進む
                $this->warn('スキップ: ' . $e->getMessage());
            }
        });

        $this->info('スクレイピング完了しました！');
    }
}
