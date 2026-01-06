import { useEffect, useState } from 'react';
import { Routes, Route, useNavigate, useSearchParams } from 'react-router-dom';
import './App.css';

// イベントデータの型定義
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
function EventDashboard() {
  const [events, setEvents] = useState<EventData[]>([]);

  const [activeTab, setActiveTab] = useState('recommend');
  const [isLoggedIn, setIsLoggedIn] = useState(false);
  const [isLoading, setIsLoading] = useState(false);

  const [filterRegistered, setFilterRegistered] = useState(false);

  useEffect(() => {
    const token = localStorage.getItem('auth_token');
    setIsLoggedIn(!!token); // 強制的に真偽値に変換
    fetchEvents(activeTab, filterRegistered);
  }, [activeTab, filterRegistered]);

  // データ取得関数
  const fetchEvents = (type: string, registered: boolean) => {
    setIsLoading(true);
    const token = localStorage.getItem('auth_token');
    const headers: HeadersInit = {};
    
    // ログインしていればトークンを送信（これで is_registered が判定される）
    if (token) {
      headers['Authorization'] = `Bearer ${token}`;
    }

    // クエリパラメータの構築
    let url = `http://localhost:8001/api/events?type=${type}`;
    if (type === 'schedule' && registered) {
      url += '&registered_only=1';
    }

    fetch(url, { headers })
      .then((res) => res.json())
      .then((data) => {
        setEvents(data);
        setIsLoading(false);
      })
      .catch((error) =>{
        console.error('エラー:', error)
        setIsLoading(false);
      });
  };

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
      fetchEvents(activeTab, filterRegistered);
    } catch (error) {
      console.error('保存失敗:', error);
    }
  };

  const handleLogout = () => {
    localStorage.removeItem('auth_token');
    setIsLoggedIn(false);
    window.location.reload();
  };

  const getButtonStyle = (tabName: string) => ({
    display: 'black',
    width: '100%',
    padding: '12px 20px',
    textAlign: 'left' as const,
    background: activeTab === tabName ? '#e6f0ff' : 'transparent',
    color: activeTab === tabName ? '#3182ce' : '#333',
    border: 'none',
    borderLeft: activeTab === tabName ? '4px solid #3182ce' : '4px solid transparent',
    cursor: 'pointer',
    fontSize: '1rem',
    fontWeight: activeTab === tabName ? 'bold' : 'normal',
    marginBottom: '5px',
    transition: 'background 0.2s',
  });

  return (
    <div style={{ display: 'flex', minHeight: '100vh', fontFamily: 'sans-serif', backgroundColor: '#f8f9fa', color: '#333' }}>
      
      {/* === 左サイドバー === */}
      <aside style={{ width: '250px', backgroundColor: '#fff', borderRight: '1px solid #e2e8f0', display: 'flex', flexDirection: 'column', position: 'sticky', top: 0, height: '100vh' }}>
        <div style={{ padding: '20px', borderBottom: '1px solid #eee' }}>
          <h1 style={{ fontSize: '1.2rem', margin: 0, color: '#1a202c' }}>🎉 Event App</h1>
        </div>

        <nav style={{ flex: 1, padding: '20px 0' }}>
          <button style={getButtonStyle('recommend')} onClick={() => setActiveTab('recommend')}>
            おすすめ
          </button>
          <button style={getButtonStyle('schedule')} onClick={() => setActiveTab('schedule')}>
            日程
          </button>
          <button style={getButtonStyle('new')} onClick={() => setActiveTab('new')}>
            新着
          </button>
          <button style={getButtonStyle('history')} onClick={() => setActiveTab('history')}>
            履歴
          </button>
        </nav>

        <div style={{ padding: '20px', borderTop: '1px solid #eee' }}>
          {isLoggedIn ? (
            <button onClick={handleLogout} style={{ width: '100%', padding: '10px', border: '1px solid #ccc', background: '#fff', borderRadius: '6px', cursor: 'pointer', color: '#333' }}>
              ログアウト
            </button>
          ) : (
            <a href="http://localhost:8001/auth/google" style={{ display: 'block', padding: '10px', backgroundColor: '#4285F4', color: 'white', borderRadius: '6px', textAlign: 'center', textDecoration: 'none', fontWeight: 'bold' }}>
              Googleでログイン
            </a>
          )}
        </div>
      </aside>

      {/* === メインコンテンツ === */}
      <main style={{ flex: 1, padding: '40px', overflowY: 'auto' }}>
        
        <header style={{ marginBottom: '30px', display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
          <h2 style={{ fontSize: '1.5rem', margin: 0 }}>
            {activeTab === 'recommend' && '✨ あなたへのおすすめ'}
            {activeTab === 'schedule' && '📅 今後の開催日程'}
            {activeTab === 'new' && '🆕 新着イベント'}
            {activeTab === 'history' && '📜 参加・興味ありの履歴'}
          </h2>

          {/* 日程タブの時だけフィルターを表示 */}
          {activeTab === 'schedule' && isLoggedIn && (
            <label style={{ display: 'flex', alignItems: 'center', cursor: 'pointer', userSelect: 'none', background: '#fff', padding: '5px 10px', borderRadius: '20px', border: '1px solid #ccc' }}>
              <input 
                type="checkbox" 
                checked={filterRegistered} 
                onChange={(e) => setFilterRegistered(e.target.checked)}
                style={{ marginRight: '8px', width: '16px', height: '16px' }}
              />
              登録済みのみ表示
            </label>
          )}
        </header>

        {isLoading ? (
          <p>読み込み中...</p>
        ) : events.length === 0 ? (
          <div style={{ padding: '60px', textAlign: 'center', color: '#777', backgroundColor: '#fff', borderRadius: '12px' }}>
            {activeTab === 'history' && !isLoggedIn 
              ? '履歴を見るにはログインしてください' 
              : '表示するイベントがありません'}
          </div>
        ) : (
          <div style={{ display: 'grid', gap: '20px', gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))' }}>
            {events.map((event) => (
              <div key={event.id} style={{ 
                backgroundColor: '#fff', 
                borderRadius: '12px', 
                padding: '20px',
                border: '1px solid #e2e8f0',
                boxShadow: '0 2px 4px rgba(0,0,0,0.05)',
                display: 'flex',
                flexDirection: 'column',
                justifyContent: 'space-between',
                color: '#333'
              }}>
                <div>
                  <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '10px' }}>
                    <span style={{ fontSize: '0.85rem', color: '#718096', fontWeight: 'bold' }}>
                      {event.started_at ? new Date(event.started_at).toLocaleString() : '日時未定'}
                    </span>
                    <button onClick={() => toggleInterest(event.id)} style={{ background: 'none', border: 'none', cursor: 'pointer', fontSize: '1.2rem', padding: 0 }}>
                      {event.is_registered ? '❤️' : '🤍'}
                    </button>
                  </div>
                  <h3 style={{ margin: '0 0 10px 0', fontSize: '1.1rem', lineHeight: '1.4' }}>
                    <a href={event.event_url} target="_blank" rel="noreferrer" style={{ color: '#2d3748', textDecoration: 'none' }}>
                      {event.title}
                    </a>
                  </h3>
                  <p style={{ fontSize: '0.9rem', color: '#718096', margin: 0 }}>
                    📍 {event.location_text || 'オンライン'}
                  </p>
                </div>
                <div style={{ marginTop: '15px', paddingTop: '15px', borderTop: '1px solid #f7fafc', textAlign: 'right' }}>
                  <a href={event.event_url} target="_blank" rel="noreferrer" style={{ color: '#3182ce', fontSize: '0.9rem', fontWeight: 'bold', textDecoration: 'none' }}>
                    詳細へ →
                  </a>
                </div>
              </div>
            ))}
          </div>
        )}
      </main>
    </div>
  );
}

function App() {
  return (
    <Routes>
      <Route path="/" element={<EventDashboard />} />
      <Route path="/login/callback" element={<LoginCallback />} />
    </Routes>
  );
}

export default App;