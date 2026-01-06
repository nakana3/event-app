<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EventController extends Controller
{
    public function index(Request $request)
    {
        // クエリビルダの開始
        $query = Event::query();
        
        // リクエストから「タイプ」を取得 (デフォルトはおすすめ)
        // type: recommend, schedule, new, history
        $type = $request->query('type', 'recommend');
        
        // ログインユーザーのID
        $userId = $request->user('sanctum')?->id;

        switch ($type) {
            case 'new':
                // --- 新規 ---
                // 作成日が新しい順
                $query->orderBy('created_at', 'desc');
                break;

            case 'schedule':
                // --- 日程 ---
                // これから開催されるイベントを、開催日が近い順に
                $query->where('started_at', '>=', now())
                      ->orderBy('started_at', 'asc');
                
                // フィルター: 登録済みのみ表示 (AND/OR検索の簡易実装)
                if ($request->boolean('registered_only') && $userId) {
                    $query->whereHas('users', function($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
                }
                break;

            case 'history':
                // --- 経歴 ---
                // 過去のイベントで、自分が登録したもの
                $query->where('started_at', '<', now())
                      ->orderBy('started_at', 'desc');
                
                // ログインしていれば、自分が「いいね」したものを優先表示、またはそれのみ表示
                if ($userId) {
                    $query->whereHas('users', function($q) use ($userId) {
                        $q->where('user_id', $userId);
                    });
                } else {
                    // 未ログインなら履歴は見せない（空にする）
                    $query->whereRaw('1 = 0');
                }
                break;

            case 'recommend':
            default:
                // --- おすすめ ---
                // 未来のイベントからランダムに（簡易的なおすすめロジック）
                $query->where('started_at', '>=', now())
                      ->inRandomOrder();
                break;
        }

        // 件数制限（ページネーションなしで多めに取得）
        return $query->take(50)->get();
    }
}
