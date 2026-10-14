'use strict';
const http = require('http');
const https = require('https');
const { WebSocketServer } = require('ws');

/** Separate guest-capable sockets. Only invalidation hints; content always uses authorized HTTP. */
function createGroups(config) {
  const wss = new WebSocketServer({ noServer: true, maxPayload: 1024 });
  const sockets = new Set();
  let flushing = false;
  function php(path, data) {
    return new Promise((resolve) => {
      const url = new URL(config.appUrl + '/internal/chat-groups/' + path);
      const body = JSON.stringify(data);
      const req = (url.protocol === 'https:' ? https : http).request(url, {
        method: 'POST', timeout: 2500,
        headers: {'Content-Type':'application/json','Content-Length':Buffer.byteLength(body),'X-Realtime-Secret':config.secret},
      }, (res) => {
        let text = ''; res.on('data', (c) => { if (text.length < 4096) text += c; });
        res.on('end', () => { try { resolve(res.statusCode === 200 ? JSON.parse(text) : null); } catch { resolve(null); } });
      });
      req.on('timeout', () => req.destroy()); req.on('error', () => resolve(null)); req.end(body);
    });
  }
  async function authorized(ws) {
    if (ws.checking) return false;
    ws.checking = true;
    const result = await php('authorize', {ticket: ws.ticket});
    ws.checking = false;
    if (!result || !result.ok || (ws.group && result.group !== ws.group)) {
      ws.close(4003, 'Access changed'); return false;
    }
    ws.group = result.group; return true;
  }
  wss.on('connection', (ws) => {
    sockets.add(ws); ws.alive = true;
    ws.on('pong', () => { ws.alive = true; });
    ws.on('close', () => sockets.delete(ws)); ws.on('error', () => {});
  });
  const timer = setInterval(async () => {
    for (const ws of sockets) {
      if (!ws.alive) { ws.terminate(); continue; }
      ws.alive = false; ws.ping(); authorized(ws).catch(() => ws.close());
    }
  }, 15000);
  const outbox = setInterval(async () => {
    if (flushing) return;
    flushing = true;
    try { await php('flush', {}); } finally { flushing = false; }
  }, 2000);
  timer.unref(); outbox.unref();
  return {
    async legacyRecipients(ids, sender) {
      const allowed = [];
      for (let start = 0; start < ids.length; start += 1000) {
        const result = await php('legacy', {ids:ids.slice(start,start+1000),sender});
        if (result && result.ok && Array.isArray(result.ids)) allowed.push(...result.ids);
      }
      return allowed;
    },
    upgrade(req, socket, head, ticket) {
      if (!ticket || ticket.length > 1024) return socket.destroy();
      php('authorize', {ticket}).then((result) => {
        if (socket.destroyed) return;
        if (!result || !result.ok) return socket.destroy();
        wss.handleUpgrade(req, socket, head, (ws) => { ws.ticket = ticket; ws.group = result.group; wss.emit('connection', ws); });
      }).catch(() => socket.destroy());
    },
    notify(group) {
      for (const ws of sockets) if (ws.group === Number(group)) authorized(ws).then((ok) => {
        if (ok && ws.readyState === ws.OPEN) ws.send(JSON.stringify({t:'group.changed',groupId:Number(group)}));
      }).catch(() => ws.close());
    },
  };
}
module.exports = { createGroups };
