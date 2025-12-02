<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    // イベント一覧を返すメソッド
    public function index()
    {
        // eventsテーブルの全データを取得して返す
        return Event::all();
    }
}
