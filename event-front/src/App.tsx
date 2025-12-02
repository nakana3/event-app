import { useEffect, useState } from 'react';
import './App.css';

// 1. データの形（型）を定義
type EventData = {
  id: number;
  title: string;
  started_at: string;
  location_text: string;
  event_url: string;
};

function App() {
  // 2. データを保存する箱 (初期値は空っぽの配列)
  const [events, setEvents] = useState<EventData[]>([]);

  // 3. 画面が開かれた瞬間に1回だけ実行される処理
  useEffect(() => {
    // LaravelのAPI (ポート8001) にアクセス！
    fetch('http://localhost:8001/api/events')
      .then((res) => res.json())       // 返事をJSONとして読み込む
      .then((data) => setEvents(data)) // 読み込んだデータを箱(events)に入れる
      .catch((error) => console.error('エラーが発生しました:', error));
  }, []);

  return (
    <div style={{ padding: '40px', fontFamily: 'sans-serif' }}>
      <h1>🎉 イベント一覧</h1>

      <div style={{ marginBottom: '20px' }}>
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
            {/* 日付を見やすく整形 */}
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

export default App;
