#!/usr/bin/env node
'use strict';
// Compare a clone capture with a reference capture and say, with numbers, whether
// it passes. Exit code 0 = pass, 1 = fail, 2 = could not run. See --help.
const fs = require('fs');
const path = require('path');
const { die, parseArgs, num, launch, listFrames, readJson, outPath, resetFrames, frameName } = require('./lib.cjs');
const A = require('./analysis.cjs');

const HELP = `compare.cjs --ref <capture> --clone <capture> --out <folder> [options]

  --level motion          motion: timing, order, curve shape and direction must match
                          pixel:  the above, plus positions, sizes and the picture itself
  --fps 30                only for frames without a track.json (video or gif reference)
  --tolerance-ms <n>      allowed timing error (default: two frames, at least 70 ms)
  --threshold 0.1         how different a pixel's colour must be to count (0-1)
  --noise 0.0002          share of pixels that may flicker without counting as motion;
                          raise to about 0.01 for compressed video references
  --every N               put every Nth frame on the sheets

Writes <out>/report.md, <out>/report.json, <out>/diff/ and <out>/sheets/.`;

(async () => {
  const a = parseArgs(process.argv.slice(2), { flags: ['help'] });
  if (a.help || !a.ref || !a.clone || !a.out) { console.log(HELP); process.exit(a.help ? 0 : 2); }
  const level = a.level || 'motion';
  if (!['motion', 'pixel'].includes(level)) die('--level must be motion or pixel');
  const refFiles = listFrames(a.ref); const cloneFiles = listFrames(a.clone);
  const refTrack = readJson(path.join(a.ref, 'track.json')); const cloneTrack = readJson(path.join(a.clone, 'track.json'));
  const fps = num(a.fps, 'fps', { min: 1, max: 120, def: (refTrack || cloneTrack) ? (refTrack || cloneTrack).meta.fps : 30 });
  if (refTrack && cloneTrack && refTrack.meta.fps !== cloneTrack.meta.fps) die(`reference was captured at ${refTrack.meta.fps} fps and the clone at ${cloneTrack.meta.fps}; capture both the same way`);
  const frameMs = 1000 / fps;
  const tol = num(a['tolerance-ms'], 'tolerance-ms', { min: 0, max: 5000, def: Math.max(70, Math.ceil(2 * frameMs)) });
  const threshold = num(a.threshold, 'threshold', { min: 0, max: 1, def: 0.1 });
  const noise = num(a.noise, 'noise', { min: 0, max: 1, def: 0.0002 });
  const every = num(a.every, 'every', { min: 1, max: 1000 });
  const n = Math.min(refFiles.length, cloneFiles.length);
  const checks = []; const notes = [];
  const check = (name, pass, detail) => checks.push({ name, pass: !!pass, detail });
  if (refFiles.length !== cloneFiles.length) notes.push(`Frame counts differ (reference ${refFiles.length}, clone ${cloneFiles.length}); the first ${n} were compared. Capture both with the same --duration and --fps.`);
  for (const k of ['viewport', 'trigger', 'clock']) {
    if (refTrack && cloneTrack && JSON.stringify(refTrack.meta[k]) !== JSON.stringify(cloneTrack.meta[k])) notes.push(`The two captures used a different ${k} (${JSON.stringify(refTrack.meta[k])} vs ${JSON.stringify(cloneTrack.meta[k])}); differences below may come from that, not from the code.`);
  }

  const diffDir = outPath(a.out, 'diff', 'x').replace(/x$/, '');
  resetFrames(diffDir);
  const browser = await launch();
  const per = [];
  try {
    const page = await A.blankPage(browser);
    let resized = false;
    for (let i = 0; i < n; i++) {
      const r = await A.frameStats(page, 'ref', refFiles[i], { threshold });
      const c = await A.frameStats(page, 'clone', cloneFiles[i], { threshold, otherKey: 'ref', wantDiff: true });
      fs.writeFileSync(outPath(a.out, 'diff', frameName(i + 1)), Buffer.from(c.diff, 'base64'));
      resized = resized || c.resized;
      if (i === 0 && c.resized) notes.push(`Frame sizes differ (reference ${r.w}x${r.h}, clone ${c.w}x${c.h}); the clone was scaled to fit, which inflates the pixel mismatch. Use the same --viewport and crop.`);
      per.push({ i, refMotion: r.motion, cloneMotion: c.motion, mismatch: c.mismatch });
    }

    // Whole-picture timing: works without tracked elements, so it also covers video references.
    const rw = A.motionWindow(per.map((p) => p.refMotion), frameMs, noise);
    const cw = A.motionWindow(per.map((p) => p.cloneMotion), frameMs, noise);
    const lag = Math.round(A.lagFrames(per.map((p) => p.refMotion), per.map((p) => p.cloneMotion)) * frameMs);
    if (!rw) notes.push('The reference never moves in this capture. Check its trigger, selector and duration before judging the clone.');
    const tracked = !!(refTrack && cloneTrack && refTrack.labels.length && cloneTrack.labels.length);
    if (rw && cw) {
      // With tracked elements these are informational: the per-element rows are the precise check.
      const say = tracked ? (name, pass, detail) => { if (!pass) notes.push(`Whole picture: ${name} — ${detail}.`); } : check;
      say('picture starts moving on time', Math.abs(rw.startMs - cw.startMs) <= tol, `reference ${rw.startMs} ms, clone ${cw.startMs} ms`);
      say('picture stops moving on time', Math.abs(rw.endMs - cw.endMs) <= tol, `reference ${rw.endMs} ms, clone ${cw.endMs} ms`);
      const ca = A.cumulative(per.map((p) => p.refMotion), noise); const cb = A.cumulative(per.map((p) => p.cloneMotion), noise);
      let spread = 0; let at = 0; ca.forEach((v, i) => { if (Math.abs(v - cb[i]) > spread) { spread = Math.abs(v - cb[i]); at = i; } });
      say('movement is spread over time the same way', spread <= 0.12, `largest gap ${Math.round(spread * 100)} % of the total movement, at ${Math.round(at * frameMs)} ms`);
      say('overall motion is in step', Math.abs(lag) <= tol, lag === 0 ? 'no lag' : `clone ${lag > 0 ? 'lags' : 'leads'} by about ${Math.abs(lag)} ms`);
    } else if (rw && !cw) check('clone moves at all', false, `reference moves from ${rw.startMs} to ${rw.endMs} ms; the clone never changes`);

    // Per-element checks from the tracks.
    const rows = [];
    if (tracked) {
      const cloneLabels = new Set(cloneTrack.labels.map((l) => l.label));
      for (const { label } of refTrack.labels) {
        if (!cloneLabels.has(label)) { notes.push(`"${label}" is tracked in the reference but not in the clone; pass the same --track labels to both.`); continue; }
        for (const prop of A.PROPS) {
          const r = A.summarise(refTrack.frames, label, prop); const c = A.summarise(cloneTrack.frames, label, prop);
          if (!r || !c) { if (r && r.moving) check(`${label} ${prop}`, false, 'element missing in the clone capture'); continue; }
          const m = Math.min(r.values.length, c.values.length);
          let maxAbs = 0; for (let i = 0; i < m; i++) maxAbs = Math.max(maxAbs, Math.abs(r.values[i] - c.values[i]));
          const unit = A.UNIT[prop];
          if (!r.moving && !c.moving) {
            if (level === 'pixel') check(`${label} ${prop} (static)`, maxAbs <= A.EPS[prop] * 2, `off by ${A.r2(maxAbs)}${unit}`);
            continue;
          }
          const row = { label, prop, ref: r, clone: c, maxAbs: A.r2(maxAbs) };
          rows.push(row);
          if (r.moving !== c.moving) { check(`${label} ${prop}`, false, r.moving ? `reference moves ${r.from} → ${r.to}${unit}; clone does not move` : `clone moves ${c.from} → ${c.to}${unit}; reference does not`); continue; }
          let curve = 0; for (let i = 0; i < m; i++) curve = Math.max(curve, Math.abs(r.progress[i] - c.progress[i]));
          row.curve = A.r2(curve);
          const rd = r.to - r.from; const cd = c.to - c.from;
          const sameDir = Math.abs(rd) <= A.EPS[prop] ? Math.abs(cd) <= A.EPS[prop] * 4 : rd * cd > 0;
          const rRange = r.max - r.min; const cRange = c.max - c.min; const ratio = cRange / rRange;
          const startOk = Math.abs(r.startMs - c.startMs) <= tol; const endOk = Math.abs(r.endMs - c.endMs) <= tol;
          const problems = [];
          if (!startOk) problems.push(`starts at ${c.startMs} ms, reference ${r.startMs} ms`);
          if (!endOk) problems.push(`ends at ${c.endMs} ms, reference ${r.endMs} ms`);
          if (!sameDir) problems.push(`direction differs (${c.from} → ${c.to} vs ${r.from} → ${r.to})`);
          if (curve > 0.1) problems.push(`curve differs by up to ${Math.round(curve * 100)} % of the distance (shape ${c.shape} vs ${r.shape})`);
          if (r.overshoot !== c.overshoot) problems.push(c.overshoot ? 'clone overshoots, reference does not' : 'reference overshoots, clone does not');
          if (ratio < 0.8 || ratio > 1.25) {
            const msg = `distance ${A.r2(cRange)}${unit} vs ${A.r2(rRange)}${unit}`;
            if (level === 'pixel' || !['x', 'y', 'w', 'h'].includes(prop)) problems.push(msg); else notes.push(`${label} ${prop}: ${msg} (allowed at motion level when the clone's layout is a different size).`);
          }
          if (level === 'pixel' && maxAbs > A.EPS[prop] * 2) problems.push(`value off by up to ${A.r2(maxAbs)}${unit}`);
          check(`${label} ${prop}`, !problems.length, problems.length ? problems.join('; ') : `${r.startMs}–${r.endMs} ms, shape ${r.shape}, within ${A.r2(maxAbs)}${unit}`);
        }
      }
      for (const l of cloneLabels) if (!refTrack.labels.some((x) => x.label === l)) notes.push(`"${l}" is tracked in the clone but not in the reference.`);
    } else if (!tracked) notes.push('No element tracks on both sides, so only whole-picture timing and pixels were compared. For a website reference, capture both with the same --track labels.');

    const mean = per.reduce((s, p) => s + p.mismatch, 0) / n;
    const worst = per.reduce((b, p) => (p.mismatch > b.mismatch ? p : b), per[0]);
    const pct = (v) => `${(v * 100).toFixed(2)} %`;
    if (level === 'pixel') {
      check('mean pixel mismatch ≤ 2 %', mean <= 0.02 && !resized, pct(mean) + (resized ? ' (frame sizes differ)' : ''));
      check('worst frame mismatch ≤ 5 %', worst.mismatch <= 0.05, `${pct(worst.mismatch)} at frame ${worst.i + 1} (${Math.round(worst.i * frameMs)} ms)`);
    }
    if (!checks.length) check('something to compare', false, 'nothing moved and nothing was tracked');

    const plan = A.sheetPlan(n, every);
    for (let s = 0; s < plan.length; s++) {
      await A.sheet(page, [
        { name: 'reference', files: plan[s].map((i) => refFiles[i]) },
        { name: 'clone', files: plan[s].map((i) => cloneFiles[i]) },
        { name: 'difference', files: plan[s].map((i) => path.join(diffDir, frameName(i + 1))) },
      ], plan[s].map((i) => `#${i + 1} · ${Math.round(i * frameMs)} ms · ${pct(per[i].mismatch)}`), outPath(a.out, 'sheets', `sheet-${String(s + 1).padStart(2, '0')}.png`), `reference vs clone — sheet ${s + 1} of ${plan.length}`);
    }

    const failed = checks.filter((c) => !c.pass);
    const verdict = failed.length ? 'FAIL' : 'PASS';
    const md = [`# Comparison: ${verdict} (${level} level)`, '', `${n} frames at ${fps} fps, timing tolerance ${tol} ms. ${checks.length - failed.length} of ${checks.length} checks pass.`,
      `Pixels: mean mismatch ${pct(mean)}, worst ${pct(worst.mismatch)} at frame ${worst.i + 1}${level === 'motion' ? ' (informational at motion level)' : ''}.`, '',
      '| check | result | detail |', '|---|---|---|', ...checks.map((c) => `| ${c.name} | ${c.pass ? 'pass' : '**FAIL**'} | ${c.detail} |`)];
    if (notes.length) md.push('', '## Notes', ...notes.map((x) => `- ${x}`));
    md.push('', `Sheets: ${plan.length} in ${path.join(a.out, 'sheets')} — look at them; the numbers say that something is off, the sheets show what.`);
    fs.writeFileSync(outPath(a.out, 'report.md'), md.join('\n') + '\n');
    fs.writeFileSync(outPath(a.out, 'report.json'), JSON.stringify({ verdict, level, fps, toleranceMs: tol, frames: n, meanMismatch: mean, worstMismatch: worst.mismatch, worstFrame: worst.i + 1, pictureMotion: { reference: rw, clone: cw, lagMs: lag }, checks, notes, elements: rows.map((r) => ({ label: r.label, prop: r.prop, maxAbs: r.maxAbs, curve: r.curve, reference: { startMs: r.ref.startMs, endMs: r.ref.endMs, from: r.ref.from, to: r.ref.to, shape: r.ref.shape }, clone: { startMs: r.clone.startMs, endMs: r.clone.endMs, from: r.clone.from, to: r.clone.to, shape: r.clone.shape } })), perFrameMismatch: per.map((p) => +p.mismatch.toFixed(5)) }, null, 1));
    console.log(md.join('\n'));
    process.exitCode = failed.length ? 1 : 0;
  } finally { await browser.close(); }
})().catch((e) => die(String(e && e.message ? e.message : e).split('\n')[0]));
