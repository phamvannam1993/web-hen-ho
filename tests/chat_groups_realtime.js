'use strict';
const assert = require('node:assert/strict');
const WebSocket = require('../websoket/node_modules/ws');
const [ticket] = process.argv.slice(2);
const secret = process.env.REALTIME_SECRET;
const deadline = setTimeout(() => { console.error('Realtime test timed out'); process.exit(1); }, 15000);
async function publish() {
  const r = await fetch('http://127.0.0.1:18938/publish', {method:'POST',headers:{'Content-Type':'application/json','X-Realtime-Secret':secret},body:JSON.stringify({items:[{to:'all',event:{t:'group.changed',groupId:2}}]})});
  assert.equal(r.status,200);
}
(async () => {
  await new Promise((resolve,reject) => {
    const invalid = new WebSocket('ws://127.0.0.1:18938/ws/groups?ticket=forged');
    invalid.on('open',()=>{invalid.close();reject(new Error('Forged ticket accepted'));});
    invalid.on('error',()=>resolve());
  });
  console.log('PASS Forged group ticket rejected');
  const ws = new WebSocket('ws://127.0.0.1:18938/ws/groups?ticket='+encodeURIComponent(ticket));
  await new Promise((resolve,reject)=>{ws.once('open',resolve);ws.once('error',reject);});
  const hint = new Promise((resolve)=>ws.once('message',(raw)=>resolve(JSON.parse(raw))));
  await publish();
  assert.deepEqual(await hint,{t:'group.changed',groupId:2});
  console.log('PASS Scoped realtime hint carries no identity or message content');
  const closed = new Promise((resolve)=>ws.once('close',(code)=>resolve(code)));
  console.log('READY_BAN');
  assert.equal(await closed,4003);
  console.log('PASS Revoked guest subscription is disconnected');
  clearTimeout(deadline);
})().catch((e)=>{console.error(e);process.exit(1);});
