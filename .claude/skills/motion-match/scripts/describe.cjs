#!/usr/bin/env node
'use strict';
// Turn one capture into something readable: contact sheets of the frames, when the
// picture moves, and a row per tracked element and property. See --help.
const fs = require('fs');
const path = require('path');
const { die, parseArgs, num, launch, listFrames, readJson, outPath } = require('./lib.cjs');
const A = require('./analysis.cjs');

const HELP = `describe.cjs --in <capture folder> [--fps 30] [--every N] [--threshold 0.1] [--noise 0.0002]

Writes <in>/sheets/sheet-NN.png and <in>/describe.md, and prints the summary.
--fps is only needed for frames that did not come from capture.cjs (video or gif frames).`;

(async () => {
  const a = parseArgs(process.argv.slice(2), { flags: ['help'] });
  if (a.help || !a.in) { console.log(HELP); process.exit(a.help ? 0 : 2); }
  const files = listFrames(a.in);
  const track = readJson(path.join(a.in, 'track.json'));
  const fps = num(a.fps, 'fps', { min: 1, max: 120, def: track ? track.meta.fps : 30 });
  const threshold = num(a.threshold, 'threshold', { min: 0, max: 1, def: 0.1 });
  const noise = num(a.noise, 'noise', { min: 0, max: 1, def: 0.0002 });
  const every = num(a.every, 'every', { min: 1, max: 1000 });
  const frameMs = 1000 / fps;
  const browser = await launch();
  try {
    const page = await A.blankPage(browser);
    const motion = [];
    for (const f of files) motion.push((await A.frameStats(page, 'a', f, { threshold })).motion);
    const win = A.motionWindow(motion, frameMs, noise);
    const plan = A.sheetPlan(files.length, every);
    for (let s = 0; s < plan.length; s++) {
      await A.sheet(page, [{ name: 'frame', files: plan[s].map((i) => files[i]) }], plan[s].map((i) => `#${i + 1} · ${Math.round(i * frameMs)} ms`), outPath(a.in, 'sheets', `sheet-${String(s + 1).padStart(2, '0')}.png`), `${path.basename(path.resolve(a.in))} — sheet ${s + 1} of ${plan.length}`);
    }
    const lines = [`# ${path.basename(path.resolve(a.in))}`, '', `${files.length} frames at ${fps} fps (${Math.round((files.length - 1) * frameMs)} ms).`,
      win ? `The picture changes from about ${win.startMs} ms to ${win.endMs} ms.` : 'The picture never changes: nothing was animated in this capture (check the trigger, selector and duration).', ''];
    if (track) {
      const rows = A.trackTable(track);
      if (rows.length) {
        lines.push('| element | property | start | end | from → to | overshoot | shape (25/50/75 %) |', '|---|---|---|---|---|---|---|');
        for (const r of rows) lines.push(`| ${r.label} | ${r.prop} | ${r.startMs} ms | ${r.endMs} ms | ${r.from} → ${r.to}${A.UNIT[r.prop] ? ' ' + A.UNIT[r.prop] : ''} | ${r.overshoot ? 'yes' : 'no'} | ${r.shape} |`);
      } else lines.push('No tracked element moved.');
      const insp = readJson(path.join(a.in, 'inspect.json'));
      if (insp && insp.animations.length) lines.push('', `${insp.animations.length} declared CSS / Web Animations are listed in inspect.json with exact durations, delays and easings.`);
    } else lines.push('No track.json here (frames from a video or gif): read the timing from the sheets.');
    lines.push('', `Sheets: ${plan.length} in ${path.join(a.in, 'sheets')}`);
    fs.writeFileSync(outPath(a.in, 'describe.md'), lines.join('\n') + '\n');
    console.log(lines.join('\n'));
  } finally { await browser.close(); }
})().catch((e) => die(String(e && e.message ? e.message : e).split('\n')[0]));
