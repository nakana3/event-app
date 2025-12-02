<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 既存データを一度空にする（重複エラー防止）
        DB::table('events')->truncate();

        DB::table('events')->insert([
            [
                'source_name' => 'manual',
                'source_event_id' => 'sample-001',
                'title' => 'はじめてのLaravel勉強会',
                'event_url' => 'https://example.com/event/1',
                'description' => 'みんなで楽しくLaravelを学びましょう！',
                'started_at' => now()->addDays(3), // 3日後
                'ended_at' => now()->addDays(3)->addHours(2),
                'location_text' => '東京ドーム',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'source_name' => 'manual',
                'source_event_id' => 'sample-002',
                'title' => 'React x Laravel ハンズオン',
                'event_url' => 'https://example.com/event/2',
                'description' => 'API連携をマスターします。',
                'started_at' => now()->addWeek(), // 1週間後
                'ended_at' => now()->addWeek()->addHours(5),
                'location_text' => 'オンライン',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
