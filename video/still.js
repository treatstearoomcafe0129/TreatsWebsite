const { chromium } = require('playwright');
(async () => {
  const shots = JSON.parse(process.argv[2]); const out = process.argv[3];
  const b = await chromium.launch({ args: ['--use-gl=angle', '--use-angle=swiftshader', '--enable-unsafe-swiftshader', '--ignore-gpu-blocklist'] });
  const p = await b.newPage({ viewport: { width: 1920, height: 1080 } });
  p.on('pageerror', e => console.error('PAGE ERROR', e.message)); p.on('console', m => { if (m.type() === 'error' || m.type()==='warning') console.log(m.text()); });
  await p.goto('http://localhost:8765/video/tea_test.html'); await p.waitForFunction(() => window.ready, null, { timeout: 120000 });
  for (let i = 0; i < shots.length; i++) {
    const t0 = Date.now(); await p.evaluate(s => window.S.shot(s), shots[i]);
    await p.screenshot({ path: `${out}/shot${i}.jpg`, quality: 90 }); console.log('shot', i, Date.now() - t0, 'ms');
  }
  await b.close();
})();
