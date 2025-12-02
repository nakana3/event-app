import { useEffect, useState } from 'react';
import { Routes, Route, useNavigate, useSearchParams } from 'react-router-dom';
import './App.css';

// 1. データの形（型）を定義
type EventData = {
  id: number;
  title: string;
  started_at: string;
  location_text: string;
  event_url: string;
};

// --- コンポーネント: ログイン処理用 ---
function LoginCallback() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();

  useEffect(() => {
    // URLから token を取得 (?token=xxxxx)
    const token = searchParams.get('token');
    
    if (token) {
      // トークンをブラウザに保存 (localStorage)
      localStorage.setItem('auth_token', token);
      console.log('ログイン成功！トークンを保存しました');
      
      // トップページに戻る
      navigate('/');
    } else {
      console.error('トークンがありません');
      navigate('/');
    }
  }, [searchParams, navigate]);

  return <div>ログイン処理中...</div>;
}

// --- コンポーネント: イベント一覧 (メイン画面) ---
function EventList() {
  const [events, setEvents] = useState<EventData[]>([]);
  const [isLoggedIn, setIsLoggedIn] = useState(false);

  // 画面が表示された時の処理
  useEffect(() => {
    // 1. ログインチェック
    const token = localStorage.getItem('auth_token');
    setIsLoggedIn(!!token); // トークンがあれば true

    // 2. イベントデータ取得
    fetch('http://localhost:8001/api/events')
      .then((res) => res.json())
      .then((data) => setEvents(data))
      .catch((error) => console.error('エラー:', error));
  }, []);

  // ログアウト処理
  const handleLogout = () => {
    localStorage.removeItem('auth_token');
    setIsLoggedIn(false);
    window.location.reload(); // 画面リロード
  };

  return (
    <div style={{ padding: '40px', fontFamily: 'sans-serif' }}>
      <h1>🎉 イベント一覧</h1>

      {/* ログインボタンエリア */}
      <div style={{ marginBottom: '20px' }}>
        {isLoggedIn ? (
          <div>
            <span style={{ marginRight: '10px', color: 'green', fontWeight: 'bold' }}>
              ✅ ログイン済み
            </span>
            <button onClick={handleLogout} style={{ padding: '5px 10px' }}>
              ログアウト
            </button>
          </div>
        ) : (
          <a 
            href="http://localhost:8001/auth/google"
            style={{
              display: 'inline-block',
              padding: '10px 20px',
              backgroundColor: '#4285F4',
              color: 'white',
              borderRadius: '5px',
              textDecoration: 'none',
              fontWeight: 'bold'
            }}
          >
            Googleでログイン
          </a>
        )}
      </div>

      <p>Laravelから取得したイベントを表示しています</p>
      
      <div style={{ display: 'grid', gap: '20px', marginTop: '20px' }}>
        {events.map((event) => (
          <div key={event.id} style={{ 
            border: '1px solid #ddd', 
            padding: '20px', 
            borderRadius: '12px',
            backgroundColor: '#f9f9f9'
          }}>
            <div style={{ color: '#666', fontSize: '0.9em' }}>
              {new Date(event.started_at).toLocaleString()}
            </div>
            <h2 style={{ margin: '10px 0' }}>{event.title}</h2>
            <p>📍 場所: {event.location_text}</p>
            <a href={event.event_url} target="_blank" rel="noreferrer" style={{ color: '#3b82f6' }}>
              イベントページを開く →
            </a>
          </div>
        ))}
      </div>
    </div>
  );
}

// --- メインアプリ (ルーティング設定) ---
function App() {
  return (
    <Routes>
      {/* トップページ */}
      <Route path="/" element={<EventList />} />
      {/* ログイン完了後に戻ってくるページ */}
      <Route path="/login/callback" element={<LoginCallback />} />
    </Routes>
  );
}

export default App;