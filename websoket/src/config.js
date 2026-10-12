'use strict';

/**
 * Cấu hình đọc từ biến môi trường, rồi tới websoket/.env, rồi tới .env của
 * web (thư mục cha) — để chạy thử trên máy khỏi phải chép REALTIME_SECRET hai nơi.
 */
const fs = require('fs');
const path = require('path');

function docEnv(file) {
  const ra = {};
  if (!fs.existsSync(file)) return ra;
  for (const dong of fs.readFileSync(file, 'utf8').split(/\r?\n/)) {
    const s = dong.trim();
    if (!s || s.startsWith('#') || !s.includes('=')) continue;
    const i = s.indexOf('=');
    ra[s.slice(0, i).trim()] = s.slice(i + 1).trim().replace(/^["']|["']$/g, '');
  }
  return ra;
}

const rieng = docEnv(path.join(__dirname, '..', '.env'));
const web = docEnv(path.join(__dirname, '..', '..', '.env'));
const lay = (k, macDinh = '') => process.env[k] ?? rieng[k] ?? web[k] ?? macDinh;

module.exports = {
  secret: lay('REALTIME_SECRET'),
  port: parseInt(lay('REALTIME_PORT', '3001'), 10),
  host: lay('REALTIME_HOST', '0.0.0.0'),
  // Địa chỉ PHP để báo "đã đọc" khi trình duyệt gửi chat.read
  appUrl: lay('REALTIME_APP_URL', lay('APP_URL', 'http://127.0.0.1:8123')).replace(/\/+$/, ''),
};
