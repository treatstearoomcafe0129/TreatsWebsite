'use strict';
// Number-crunching shared by describe.cjs and compare.cjs: pixel diffs (done in a
// blank browser page, so no image libraries are needed), track summaries and sheets.
const fs = require('fs');
const path = require('path');
const { outPath, frameName } = require('./lib.cjs');

const PROPS = ['x', 'y', 'w', 'h', 'opacity', 'scale', 'rotate'];
const EPS = { x: 0.5, y: 0.5, w: 0.5, h: 0.5, opacity: 0.01, scale: 0.005, rotate: 0.5 };
const UNIT = { x: 'px', y: 'px', w: 'px', h: 'px', opacity: '', scale: '', rotate: 'deg' };
const b64 = (p) => fs.readFileSync(p).toString('base64');
const r2 = (v) => Math.round(v * 100) / 100;

async function blankPage(browser) {
  const page = await (await browser.newContext({ deviceScaleFactor: 1, javaScriptEnabled: true })).newPage();
  await page.setContent('<!doctype html><title>motion-match</title>');
  await page.evaluate(() => { window.S = {}; });
  return page;
}

// Diff one frame against the previous frame of the same sequence (motion) and,
// when `other` is given, against the matching frame of the other sequence.
async function frameStats(page, key, file, { threshold, otherKey, wantDiff }) {
  return page.evaluate(async ({ key, data, threshold, otherKey, wantDiff }) => {
    const bmp = await createImageBitmap(await (await fetch('data:image/png;base64,' + data)).blob());
    const ref = otherKey ? window.S[otherKey] : null;
    const w = ref ? ref.w : bmp.width; const h = ref ? ref.h : bmp.height;
    const cv = new OffscreenCanvas(w, h); const cx = cv.getContext('2d', { willReadFrequently: true });
    cx.drawImage(bmp, 0, 0, w, h);
    const px = cx.getImageData(0, 0, w, h).data;
    const lim = threshold * 255;
    const count = (a, b, mark) => {
      let n = 0;
      for (let i = 0; i < a.length; i += 4) {
        if (Math.abs(a[i] - b[i]) > lim || Math.abs(a[i + 1] - b[i + 1]) > lim || Math.abs(a[i + 2] - b[i + 2]) > lim) { n++; if (mark) mark(i); }
      }
      return n / (a.length / 4);
    };
    const prev = window.S[key];
    const out = { w: bmp.width, h: bmp.height, resized: !!ref && (bmp.width !== w || bmp.height !== h) };
    out.motion = prev && prev.px.length === px.length ? count(px, prev.px) : 0;
    if (ref) {
      let img = null;
      if (wantDiff) { img = new ImageData(w, h); for (let i = 0; i < px.length; i += 4) { const g = 255 - (255 - (ref.px[i] * 0.3 + ref.px[i + 1] * 0.59 + ref.px[i + 2] * 0.11)) * 0.25; img.data[i] = img.data[i + 1] = img.data[i + 2] = g; img.data[i + 3] = 255; } }
      out.mismatch = count(px, ref.px, img && ((i) => { img.data[i] = 230; img.data[i + 1] = 30; img.data[i + 2] = 60; }));
      if (img) {
        cx.putImageData(img, 0, 0);
        const blob = await cv.convertToBlob({ type: 'image/png' });
        out.diff = await new Promise((res) => { const fr = new FileReader(); fr.onload = () => res(fr.result.split(',')[1]); fr.readAsDataURL(blob); });
      }
    }
    window.S[key] = { px, w, h };
    return out;
  }, { key, data: b64(file), threshold, otherKey, wantDiff });
}

// First and last frame where the picture changes, as milliseconds.
function motionWindow(motion, frameMs, noise) {
  let first = -1; let last = -1;
  motion.forEach((m, i) => { if (m > noise) { if (first < 0) first = i; last = i; } });
  return first < 0 ? null : { startMs: Math.round((first - 1) * frameMs), endMs: Math.round(last * frameMs) };
}

// Share of the total movement that has happened by each frame (0..1), ignoring flicker below `noise`.
function cumulative(motion, noise) {
  const m = motion.map((v) => Math.max(0, v - noise)); const total = m.reduce((x, y) => x + y, 0);
  let run = 0; return m.map((v) => { run += v; return total ? run / total : 0; });
}

// How many frames the clone's motion curve lags (+) or leads (-) the reference's.
function lagFrames(a, b, max = 20) {
  let best = 0; let bestScore = -Infinity;
  for (let lag = -max; lag <= max; lag++) {
    let s = 0;
    for (let i = 0; i < a.length; i++) { const j = i + lag; if (j >= 0 && j < b.length) s += a[i] * b[j]; }
    if (s > bestScore + 1e-12 || (Math.abs(s - bestScore) <= 1e-12 && Math.abs(lag) < Math.abs(best))) { bestScore = s; best = lag; }
  }
  return bestScore > 0 ? best : 0;
}

// Summarise one property of one tracked element over time.
function summarise(frames, label, prop) {
  const pts = frames.map((f) => ({ t: f.t, v: f.els && f.els[label] ? f.els[label][prop] : null })).filter((p) => p.v !== null && p.v !== undefined);
  if (pts.length < 2) return null;
  const eps = EPS[prop];
  let first = -1; let last = -1; let min = Infinity; let max = -Infinity;
  pts.forEach((p, i) => { min = Math.min(min, p.v); max = Math.max(max, p.v); if (i && Math.abs(p.v - pts[i - 1].v) > eps / 10) { if (first < 0) first = i; last = i; } });
  const from = pts[0].v; const to = pts[pts.length - 1].v;
  const moving = max - min > eps;
  const span = Math.abs(to - from) > eps ? to - from : (max - min) * (Math.abs(max - from) >= Math.abs(min - from) ? 1 : -1);
  const s = { from: r2(from), to: r2(to), min: r2(min), max: r2(max), moving, values: pts.map((p) => p.v), times: pts.map((p) => p.t) };
  if (moving) {
    s.startMs = pts[first - 1].t; s.endMs = pts[last].t; s.durationMs = s.endMs - s.startMs;
    s.progress = pts.map((p) => (p.v - from) / span);
    const lo = Math.min(from, to); const hi = Math.max(from, to);
    s.overshoot = Math.abs(to - from) > eps && (max > hi + Math.abs(span) * 0.02 || min < lo - Math.abs(span) * 0.02);
    const at = (q) => { const t = s.startMs + s.durationMs * q; let k = pts.findIndex((p) => p.t >= t); if (k < 0) k = pts.length - 1; return Math.round(s.progress[k] * 100); };
    s.shape = `${at(0.25)}/${at(0.5)}/${at(0.75)}`; // % of the distance covered at 25/50/75 % of the time
  }
  return s;
}

function trackTable(track) {
  const rows = [];
  for (const { label } of track.labels || []) {
    for (const prop of PROPS) {
      const s = summarise(track.frames, label, prop);
      if (s && s.moving) rows.push({ label, prop, ...s });
    }
  }
  return rows;
}

// A contact sheet: rows of thumbnails with a caption under each column.
async function sheet(page, rows, captions, file, title, thumb = 230) {
  const cols = captions.length;
  const html = `<!doctype html><style>body{margin:0;padding:12px;background:#fff;font:12px/1.3 sans-serif;color:#111;width:max-content}
h1{font-size:13px;margin:0 0 8px}table{border-collapse:collapse}td{padding:3px;text-align:center;vertical-align:top}
th{padding:3px 8px 3px 0;text-align:right;font-weight:600}img{display:block;max-width:${thumb}px;max-height:${thumb}px;border:1px solid #ccc}</style>
<h1></h1><table>${rows.map((r, ri) => `<tr><th data-r="${ri}"></th>${r.files.map((_, ci) => `<td><img data-r="${ri}" data-c="${ci}"></td>`).join('')}</tr>`).join('')}
<tr><th></th>${Array.from({ length: cols }, (_, ci) => `<td data-cap="${ci}"></td>`).join('')}</tr></table>`;
  await page.setContent(html);
  await page.evaluate(async ({ rows, captions, title }) => {
    document.querySelector('h1').textContent = title;
    rows.forEach((r, ri) => { document.querySelector(`th[data-r="${ri}"]`).textContent = r.name; });
    captions.forEach((c, ci) => { document.querySelector(`td[data-cap="${ci}"]`).textContent = c; });
    await Promise.all([...document.querySelectorAll('img')].map((img) => { img.src = 'data:image/png;base64,' + rows[+img.dataset.r].data[+img.dataset.c]; return img.decode().catch(() => {}); }));
  }, { rows: rows.map((r) => ({ name: r.name, data: r.files.map(b64) })), captions, title });
  await page.locator('body').screenshot({ path: file });
}

// Wide frames get fewer, larger thumbnails so that detail stays readable.
function sheetLayout(w, h) { return w / h > 1.8 ? { perSheet: 4, thumb: 470 } : { perSheet: 8, thumb: 230 }; }

// Pick which frames go on the sheets: every Nth, at most `perSheet` per sheet and 6 sheets.
function sheetPlan(count, every, perSheet = 8) {
  perSheet = Math.max(2, perSheet);
  const stepN = every || Math.max(1, Math.ceil(count / (perSheet * 4)));
  const idx = []; for (let i = 0; i < count; i += stepN) idx.push(i);
  if (idx[idx.length - 1] !== count - 1) idx.push(count - 1);
  const sheets = []; for (let i = 0; i < idx.length && sheets.length < 6; i += perSheet) sheets.push(idx.slice(i, i + perSheet));
  return sheets;
}

module.exports = { PROPS, EPS, UNIT, r2, blankPage, frameStats, motionWindow, cumulative, lagFrames, summarise, trackTable, sheet, sheetPlan, sheetLayout, outPath, frameName, path };
