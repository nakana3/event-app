import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App' // .tsx 拡張子を削除
import './index.css'
import { BrowserRouter } from 'react-router-dom';

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <BrowserRouter>
      <App />
    </BrowserRouter>
  </React.StrictMode>,
)
