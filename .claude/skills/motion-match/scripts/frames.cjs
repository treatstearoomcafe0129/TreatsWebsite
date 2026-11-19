#!/usr/bin/env node
'use strict';
// Turn a video or gif into numbered frames with the ffmpeg already on this machine.
const fs = require('fs');
const path = require('path');
const { execFileSync, spawnSync } = require('child_process');
const { die, parseArgs, num, outPath, resetFrames } = require('./lib.cjs');

const HELP = `frames.cjs --in <video or gif> --out <folder> [--fps 30] [--start s] [--end s] [--crop w:h:x:y] [--probe]

Writes <out>/frames/frame-NNNN.png. --probe only prints the file's size, length and frame rate.
Needs ffmpeg on the PATH; it is not downloaded for you.`;

const a = parseArgs(process.argv.slice(2), { flags: ['probe', 'help'] });
if (a.help || !a.in || (!a.probe && !a.out)) { console.log(HELP); process.exit(a.help ? 0 : 2); }
const input = path.resolve(a.in);
if (!fs.existsSync(input) || !fs.statSync(input).isFile()) die(`no such file: ${input}`);
if (spawnSync('ffmpeg', ['-version']).status !== 0) die('ffmpeg is not installed. Install it (for example "brew install ffmpeg" or "apt install ffmpeg") and run this again.');
if (a.probe) {
  const info = spawnSync('ffmpeg', ['-hide_banner', '-i', input], { encoding: 'utf8' }).stderr || '';
  console.log(info.split('\n').filter((l) => /Duration|Stream.*Video/.test(l)).map((l) => l.trim().slice(0, 300)).join('\n') || 'no video stream found');
  process.exit(0);
}
const fps = num(a.fps, 'fps', { min: 1, max: 60, def: 30 });
const start = num(a.start, 'start', { min: 0, max: 86400 });
const end = num(a.end, 'end', { min: 0, max: 86400 });
if (a.crop && !/^\d+:\d+:\d+:\d+$/.test(a.crop)) die('--crop must be w:h:x:y in pixels');
if (start !== undefined && end !== undefined && end <= start) die('--end must be after --start');
if (((end ?? 60) - (start ?? 0)) * fps > 1800 && end !== undefined) die('that is more than 1800 frames; narrow --start/--end or lower --fps');
const dir = outPath(a.out, 'frames', 'x').replace(/x$/, '');
resetFrames(dir);
const args = ['-hide_banner', '-loglevel', 'error', '-y'];
if (start !== undefined) args.push('-ss', String(start));
if (end !== undefined) args.push('-to', String(end));
args.push('-i', input, '-vf', `fps=${fps}${a.crop ? `,crop=${a.crop}` : ''}`, '-frames:v', '1800', path.join(dir, 'frame-%04d.png'));
try { execFileSync('ffmpeg', args, { stdio: ['ignore', 'ignore', 'pipe'] }); } catch (e) { die(`ffmpeg failed: ${String(e.stderr || e.message).split('\n')[0]}`); }
const n = fs.readdirSync(dir).filter((f) => /^frame-\d{4}\.png$/.test(f)).length;
console.log(`wrote ${n} frames at ${fps} fps -> ${dir}`);
