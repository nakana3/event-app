<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class ToggleInterestController extends Controller
{
    // ボタンが押されたら「登録 ⇔ 解除」を切り替える
    public function __invoke(Request $request, $eventId)
    {
        $user = $request->user();
        $event = Event::findOrFail($eventId);

        // toggle: あれば消す、なければ作る（超便利機能）
        $user->events()->toggle([$eventId => ['status' => 'interested']]);

        // 結果を返す
        $isRegistered = $user->events()->where('event_id', $eventId)->exists();

        return response()->json([
            'status' => 'success',
            'is_registered' => $isRegistered,
        ]);
    }
}
