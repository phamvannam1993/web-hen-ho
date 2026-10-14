'use strict';

/**
 * Máy chủ WebSocket cho chat thời gian thực (web + ứng dụng di động).
 *
 * Không đụng vào cơ sở dữ liệu. PHP vẫn là nơi lưu tin và kiểm tra quyền;
 * máy chủ này chỉ chuyển sự kiện tới đúng người:
 *
 *   PHP  ── POST /publish ──►  Node  ── WebSocket ──►  trình duyệt / ứng dụng
 *
 * Kết nối: ws://host:3001/ws?token=<token PHP cấp>
 *
 * Sự kiện gửi xuống (trường "t"):
 *   chat.message  {conversationId, message}   tin nhắn riêng mới
 *   chat.seen     {conversationId}              đối phương đã đọc tin của bạn
 *   chat.typing   {conversationId, from}        đối phương đang nhập
 *   room.message  {message}                     tin mới ở phòng chat chung
 *   room.presence {online}                      số người đang kết nối
 *   pong
 *
 * Sự kiện nhận lên:
 *   ping
 *   chat.typing {conversationId, to}  — chuyển cho người "to", đóng dấu "from" thật
 *   chat.read   {conversationId}      — nhờ PHP đánh dấu đã đọc (khung chat web cần)
 */
const http = require('http');
const https = require('https');
const { WebSocketServer } = require('ws');

const config = require('./config');
const { verify, sameSecret } = require('./auth');
const { createGroups } = require('./groups');

if (!config.secret) {
  console.error('[realtime] Thiếu REALTIME_SECRET (đặt trong websoket/.env hoặc .env của web).');
  process.exit(1);
}
const groups = createGroups(config);

/** uid -> tập kết nối (một người có thể mở web và app cùng lúc) */
const clients = new Map();

function them(uid, ws) {
  if (!clients.has(uid)) clients.set(uid, new Set());
  clients.get(uid).add(ws);
}

function bot(uid, ws) {
  const ds = clients.get(uid);
  if (!ds) return;
  ds.delete(ws);
  if (!ds.size) clients.delete(uid);
}

function gui(ws, data) {
  if (ws.readyState === ws.OPEN) ws.send(typeof data === 'string' ? data : JSON.stringify(data));
}

function guiCho(uid, data) {
  const s = JSON.stringify(data);
  for (const ws of clients.get(uid) || []) gui(ws, s);
}

function guiTatCa(data, tru = null) {
  const s = JSON.stringify(data);
  for (const [uid, ds] of clients) {
    if (uid === tru) continue;
    for (const ws of ds) gui(ws, s);
  }
}

let onlineCu = -1;
function baoOnline() {
  if (clients.size === onlineCu) return;
  onlineCu = clients.size;
  guiTatCa({ t: 'room.presence', online: clients.size });
}

/* ───────────── HTTP: PHP đẩy sự kiện vào ───────────── */

const server = http.createServer((req, res) => {
  if (req.method === 'GET' && req.url === '/health') {
    res.writeHead(200, { 'Content-Type': 'application/json' });
    return res.end(JSON.stringify({ ok: true, users: clients.size }));
  }

  if (req.method !== 'POST' || req.url !== '/publish') {
    res.writeHead(404);
    return res.end();
  }
  if (!sameSecret(req.headers['x-realtime-secret'], config.secret)) {
    res.writeHead(403);
    return res.end();
  }

  let body = '';
  req.on('data', (c) => {
    body += c;
    if (body.length > 1e6) req.destroy();
  });
  req.on('end', () => {
    let goi;
    try {
      goi = JSON.parse(body);
    } catch {
      res.writeHead(400);
      return res.end();
    }
    // {items: [{to: uid | 'all', except?: uid, event: {...}}]}
    for (const it of Array.isArray(goi.items) ? goi.items : []) {
      if (!it || !it.event || !it.event.t) continue;
      if (it.event.t === 'group.changed') { groups.notify(it.event.groupId); continue; }
      if (it.event.t === 'room.message') {
        const ids = it.to === 'all' ? Array.from(clients.keys()).filter((id) => id !== Number(it.except)) : [Number(it.to)];
        groups.legacyRecipients(ids, Number(it.event.message && it.event.message.user_id)).then((allowed) => {
          for (const id of allowed) guiCho(id, it.event);
        }).catch(() => {});
        continue;
      }
      if (it.to === 'all') guiTatCa(it.event, it.except ? parseInt(it.except, 10) : null);
      else guiCho(parseInt(it.to, 10), it.event);
    }
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end('{"ok":true}');
  });
});

/* ───────────── WebSocket ───────────── */

const wss = new WebSocketServer({ noServer: true, maxPayload: 16 * 1024 });

server.on('upgrade', (req, socket, head) => {
  const url = new URL(req.url, 'http://x');
  if (url.pathname === '/ws/groups') return groups.upgrade(req, socket, head, url.searchParams.get('ticket'));
  if (url.pathname !== '/ws') return socket.destroy();

  const uid = verify(url.searchParams.get('token'), config.secret);
  if (!uid) {
    socket.write('HTTP/1.1 401 Unauthorized\r\n\r\n');
    return socket.destroy();
  }
  wss.handleUpgrade(req, socket, head, (ws) => {
    ws.uid = uid;
    ws.alive = true;
    wss.emit('connection', ws);
  });
});

/** Báo PHP đánh dấu đã đọc — PHP tự kiểm tra người này có trong hội thoại không. */
function nhoPhpDanhDauDoc(uid, conversationId) {
  const body = JSON.stringify({ uid, conversation_id: conversationId });
  const u = new URL(config.appUrl + '/api/realtime/read');
  const req = (u.protocol === 'https:' ? https : http).request(
    {
      hostname: u.hostname,
      port: u.port || (u.protocol === 'https:' ? 443 : 80),
      path: u.pathname,
      method: 'POST',
      timeout: 3000,
      headers: { 'Content-Type': 'application/json', 'Content-Length': Buffer.byteLength(body), 'X-Realtime-Secret': config.secret },
    },
    (res) => res.resume(),
  );
  req.on('error', () => {});
  req.on('timeout', () => req.destroy());
  req.end(body);
}

/** Chống gửi "đang nhập" dồn dập: mỗi người tối đa 1 lần / giây. */
const lanGoCuoi = new Map();

wss.on('connection', (ws) => {
  them(ws.uid, ws);
  baoOnline();

  ws.on('pong', () => (ws.alive = true));

  ws.on('message', (raw) => {
    let m;
    try {
      m = JSON.parse(raw);
    } catch {
      return;
    }
    if (!m || typeof m.t !== 'string') return;

    if (m.t === 'ping') return gui(ws, { t: 'pong' });

    if (m.t === 'chat.typing') {
      const to = parseInt(m.to, 10);
      const cid = parseInt(m.conversationId, 10);
      if (!to || !cid || to === ws.uid) return;
      const now = Date.now();
      if (now - (lanGoCuoi.get(ws.uid) || 0) < 1000) return;
      lanGoCuoi.set(ws.uid, now);
      // "from" lấy từ token chứ không tin dữ liệu gửi lên
      return guiCho(to, { t: 'chat.typing', conversationId: cid, from: ws.uid });
    }

    if (m.t === 'chat.read') {
      const cid = parseInt(m.conversationId, 10);
      if (cid) nhoPhpDanhDauDoc(ws.uid, cid);
    }
  });

  ws.on('close', () => {
    bot(ws.uid, ws);
    baoOnline();
  });
  ws.on('error', () => {});
});

// Dọn kết nối chết (mất mạng đột ngột không kịp gửi close)
setInterval(() => {
  for (const ws of wss.clients) {
    if (!ws.alive) {
      ws.terminate();
      continue;
    }
    ws.alive = false;
    ws.ping();
  }
}, 30000);

server.listen(config.port, config.host, () => {
  console.log(`[realtime] đang chạy ở ${config.host}:${config.port} (WebSocket /ws, PHP đẩy vào POST /publish)`);
});
