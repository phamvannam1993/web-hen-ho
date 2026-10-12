'use strict';

/**
 * Xác minh token do PHP cấp (application/libraries/Realtime.php::token):
 *   base64url(JSON {uid, exp}) + "." + base64url(HMAC-SHA256(phần đầu, secret))
 */
const crypto = require('crypto');

const b64url = (buf) => buf.toString('base64').replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');

function verify(token, secret) {
  if (!token || !secret || typeof token !== 'string') return null;
  const [payload, sig] = token.split('.');
  if (!payload || !sig) return null;

  const dung = b64url(crypto.createHmac('sha256', secret).update(payload).digest());
  const a = Buffer.from(sig);
  const b = Buffer.from(dung);
  if (a.length !== b.length || !crypto.timingSafeEqual(a, b)) return null;

  let data;
  try {
    data = JSON.parse(Buffer.from(payload.replace(/-/g, '+').replace(/_/g, '/'), 'base64').toString('utf8'));
  } catch {
    return null;
  }
  const uid = parseInt(data.uid, 10);
  if (!uid || !data.exp || data.exp * 1000 < Date.now()) return null;
  return uid;
}

/** So khớp secret PHP gửi kèm khi đẩy sự kiện, không lộ thời gian so sánh. */
function sameSecret(given, secret) {
  if (!given || !secret) return false;
  const a = crypto.createHash('sha256').update(String(given)).digest();
  const b = crypto.createHash('sha256').update(String(secret)).digest();
  return crypto.timingSafeEqual(a, b);
}

module.exports = { verify, sameSecret };
