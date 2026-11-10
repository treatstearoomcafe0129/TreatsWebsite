// node render2.js frames <fps> <startFrame> <endFrame> <outdir>   |   node render2.js stills <t1,t2,..> <outdir>
const { chromium } = require('playwright');
(async () => {
  const [mode, ...a] = process.argv.slice(2);
  const b = await chromium.launch({ args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'] });
  const p = await b.newPage({ viewport: { width: 1920, height: 1080 } });
  p.on('pageerror', e => console.error('PAGE ERROR', e.message));
  await p.goto('http://localhost:8765/video/film2.html'); await p.waitForFunction(() => window.ready, null, { timeout: 180000 });
  const snap = async (t, path) => { await p.evaluate(t => window.render(t), t); await p.screenshot({ path, type: 'jpeg', quality: 94 }); };
  if (mode === 'stills') { for (const t of a[0].split(',').map(Number)) await snap(t, `${a[1]}/s${t.toFixed(2).padStart(6, '0')}.jpg`); }
  else {
    const [fps, s, e, dir] = [Number(a[0]), Number(a[1]), Number(a[2]), a[3]]; const t0 = Date.now();
    for (let i = s; i < e; i++) { await snap(i / fps, `${dir}/f${String(i).padStart(5, '0')}.jpg`); if ((i - s) % 40 === 0) console.log(`[${s}] ${i - s}/${e - s} ${((Date.now() - t0) / 1000) | 0}s`); }
  }
  await b.close();
})();
