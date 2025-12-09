import { useEffect, useState } from 'react';
import { Routes, Route, useNavigate, useSearchParams } from 'react-router-dom';
import './App.css';

type EventData = {
  id: number;
  title: string;
  started_at: string;
  location_text: string;
  event_url: string;
  is_registered: boolean; // バックエンドから届く「登録済みか？」の情報
};

// ログイン処理用コンポーネント
function LoginCallback() {
  const [searchParams] = useSearchParams();
  const navigate = useNavigate();

  useEffect(() => {
    const token = searchParams.get('token');
    if (token) {
      localStorage.setItem('auth_token', token);
      navigate('/');
    } else {
      navigate('/');
    }
  }, [searchParams, navigate]);

  return <div>ログイン処理中...</div>;
}

// メイン画面
function EventList() {
  const [events, setEvents] = useState<EventData[]>([]);
  const [isLoggedIn, setIsLoggedIn] = useState(false);

  // データ取得関数
  const fetchEvents = () => {
    const token = localStorage.getItem('auth_token');
    const headers: HeadersInit = {};
    
    // ログインしていればトークンを送信（これで is_registered が判定される）
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    fetch('http://localhost:8001/api/events', { headers })
      .then((res) => res.json())
      .then((data) => setEvents(data))
      .catch((error) => console.error('エラー:', error));
  };

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    setIsLoggedIn(!!token);
    fetchEvents();
  }, []);

  // お気に入りボタンを押した時の処理
  const toggleInterest = async (eventId: number) => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
      alert('ログインしてください');
      return;
    }

    try {
      await fetch(`http://localhost:8001/api/events/${eventId}/interest`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json',
        },
      });
      // 画面を最新の状態に更新
      fetchEvents();
    } catch (error) {
      console.error('保存失敗:', error);
    }
  };

  const handleLogout = () => {
    localStorage.removeItem('auth_token');
    setIsLoggedIn(false);
    fetchEvents(); // ログアウト状態で再取得
  };

  return (
    <div style={{ padding: '40px', fontFamily: 'sans-serif' }}>
      <header style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '30px' }}>
        <h1>🎉 Evently</h1>
        {isLoggedIn ? (
          <button onClick={handleLogout} style={{ padding: '8px 16px', cursor: 'pointer' }}>ログアウト</button>
        ) : (
          <a 
            href="http://localhost:8001/auth/google"
            style={{ padding: '10px 20px', backgroundColor: '#4285F4', color: 'white', borderRadius: '5px', textDecoration: 'none', fontWeight: 'bold' }}
          >
            Googleでログイン
          </a>
        )}
      </header>

      <div style={{ display: 'grid', gap: '20px' }}>
        {events.map((event) => (
          <div key={event.id} style={{ border: '1px solid #ddd', padding: '20px', borderRadius: '12px', background: event.is_registered ? '#fff0f5' : '#fff' }}>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <div style={{ color: '#666', fontSize: '0.9em' }}>
                {new Date(event.started_at).toLocaleString()}
              </div>
              
              {/* ▼▼▼ お気に入りボタン ▼▼▼ */}
              <button 
                onClick={() => toggleInterest(event.id)}
                style={{ 
                  cursor: 'pointer', 
                  background: 'none', 
                  border: 'none', 
                  fontSize: '1.5rem' 
                }}
              >
                {event.is_registered ? '❤️ 登録済み' : '🤍 気になる'}
              </button>
            </div>

            <h2 style={{ margin: '10px 0' }}>{event.title}</h2>
            <p>📍 {event.location_text}</p>
            <a href={event.event_url} target="_blank" rel="noreferrer" style={{ color: '#3b82f6' }}>公式ページ →</a>
          </div>
        ))}
      </div>
    </div>
  );
}

function App() {
  return (
    <Routes>
      <Route path="/" element={<EventList />} />
      <Route path="/login/callback" element={<LoginCallback />} />
    </Routes>
  );
}

export default App;