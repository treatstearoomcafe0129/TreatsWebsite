// Procedural, physically-lit afternoon tea. Units: 1 = 10 cm.
import * as THREE from 'three';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';
import { EffectComposer } from 'three/addons/postprocessing/EffectComposer.js';
import { RenderPass } from 'three/addons/postprocessing/RenderPass.js';
import { BokehPass } from 'three/addons/postprocessing/BokehPass.js';
import { OutputPass } from 'three/addons/postprocessing/OutputPass.js';

// ---------- deterministic noise ----------
let s = 12345; const rnd = () => (s = (s * 16807) % 2147483647) / 2147483647;
const R = (a, b) => a + (b - a) * rnd();
function hash3(x, y, z) { const h = Math.sin(x * 127.1 + y * 311.7 + z * 74.7) * 43758.5453; return h - Math.floor(h); }
function vnoise(x, y, z) {
  const xi = Math.floor(x), yi = Math.floor(y), zi = Math.floor(z), xf = x - xi, yf = y - yi, zf = z - zi;
  const u = xf * xf * (3 - 2 * xf), v = yf * yf * (3 - 2 * yf), w = zf * zf * (3 - 2 * zf);
  const L = (a, b, t) => a + (b - a) * t;
  const c = (i, j, k) => hash3(xi + i, yi + j, zi + k);
  return L(L(L(c(0,0,0), c(1,0,0), u), L(c(0,1,0), c(1,1,0), u), v), L(L(c(0,0,1), c(1,0,1), u), L(c(0,1,1), c(1,1,1), u), v), w);
}
const fbm = (x, y, z) => vnoise(x, y, z) * .55 + vnoise(x * 2.1, y * 2.1, z * 2.1) * .28 + vnoise(x * 4.3, y * 4.3, z * 4.3) * .17;
function displace(geo, amt, freq, seed = 0, filter = () => 1) {
  const p = geo.attributes.position, n = new THREE.Vector3();
  geo.computeVertexNormals(); const nr = geo.attributes.normal;
  for (let i = 0; i < p.count; i++) {
    const x = p.getX(i), y = p.getY(i), z = p.getZ(i);
    const d = (fbm(x * freq + seed, y * freq, z * freq) - .5) * amt * filter(x, y, z);
    n.set(nr.getX(i), nr.getY(i), nr.getZ(i));
    p.setXYZ(i, x + n.x * d, y + n.y * d, z + n.z * d);
  }
  geo.computeVertexNormals(); return geo;
}

// ---------- canvas textures ----------
function canvasTex(w, h, draw, srgb = true, repeat = null) {
  const c = document.createElement('canvas'); c.width = w; c.height = h; const g = c.getContext('2d'); draw(g, w, h);
  const t = new THREE.CanvasTexture(c); if (srgb) t.colorSpace = THREE.SRGBColorSpace; t.anisotropy = 8;
  if (repeat) { t.wrapS = t.wrapT = THREE.RepeatWrapping; t.repeat.set(...repeat); }
  return t;
}
function speckle(g, w, h, n, rmin, rmax, cols, alpha = 1) {
  for (let i = 0; i < n; i++) { g.globalAlpha = alpha * R(.3, 1); g.fillStyle = cols[Math.floor(rnd() * cols.length)]; g.beginPath(); g.ellipse(rnd() * w, rnd() * h, R(rmin, rmax), R(rmin, rmax), rnd() * 3, 0, 6.3); g.fill(); }
  g.globalAlpha = 1;
}
const linenMap = canvasTex(512, 512, (g, w, h) => {
  g.fillStyle = '#a2b09c'; g.fillRect(0, 0, w, h);
  for (let y = 0; y < h; y += 2) { g.fillStyle = `rgba(120,100,70,${R(.02, .07)})`; g.fillRect(0, y, w, 1); }
  for (let x = 0; x < w; x += 2) { g.fillStyle = `rgba(255,255,255,${R(.02, .08)})`; g.fillRect(x, 0, 1, h); }
}, true, [14, 14]);
const linenBump = canvasTex(256, 256, (g, w, h) => {
  g.fillStyle = '#808080'; g.fillRect(0, 0, w, h);
  for (let y = 0; y < h; y += 4) for (let x = 0; x < w; x += 4) { const o = ((x + y) / 4) % 2; g.fillStyle = o ? '#a0a0a0' : '#606060'; g.fillRect(x, y, 4, 2); g.fillStyle = o ? '#606060' : '#a0a0a0'; g.fillRect(x, y + 2, 4, 2); }
}, false, [40, 40]);
const crumbMap = canvasTex(512, 512, (g, w, h) => {
  g.fillStyle = '#f7e7c2'; g.fillRect(0, 0, w, h);
  speckle(g, w, h, 2600, 1, 4, ['#e2d3b2', '#fbf5e8', '#d9c7a0'], .7);
  speckle(g, w, h, 500, 1, 3, ['#c9b48a'], .5);
}, true, [1, 1]);
const crustMap = canvasTex(512, 512, (g, w, h) => { // scone: golden top/bottom, pale split side
  const gr = g.createLinearGradient(0, 0, 0, h);
  gr.addColorStop(0, '#93491c'); gr.addColorStop(.08, '#a4561f'); gr.addColorStop(.14, '#bd7533'); gr.addColorStop(.2, '#ddb57b'); gr.addColorStop(.3, '#efd8aa');
  gr.addColorStop(.68, '#ecd2a0'); gr.addColorStop(.8, '#c88a48'); gr.addColorStop(1, '#a0602c');
  g.fillStyle = gr; g.fillRect(0, 0, w, h);
  speckle(g, w, h, 3000, 1, 3, ['#8e5424', '#f4dfb5', '#c47f3e'], .35);
  speckle(g, w, h, 220, 2, 6, ['#6f3f1a'], .25); // sultana-ish
});
const crustBump = canvasTex(256, 256, (g, w, h) => { g.fillStyle = '#808080'; g.fillRect(0, 0, w, h); speckle(g, w, h, 4000, 1, 3, ['#4a4a4a', '#c0c0c0', '#999'], .6); }, false);
const strawberryBump = canvasTex(256, 256, (g, w, h) => { g.fillStyle = '#909090'; g.fillRect(0, 0, w, h); for (let y = 8; y < h; y += 16) for (let x = (y / 16 % 2) * 8; x < w; x += 16) { g.fillStyle = '#404040'; g.beginPath(); g.ellipse(x, y, 2.2, 3, 0, 0, 6.3); g.fill(); } }, false);
const sageWall = canvasTex(1024, 512, (g, w, h) => {
  const v = g.createLinearGradient(0, 0, 0, h); v.addColorStop(0, '#3d453b'); v.addColorStop(.45, '#6f7c6b'); v.addColorStop(.62, '#9aa795'); v.addColorStop(1, '#a3b09e');
  g.fillStyle = v; g.fillRect(0, 0, w, h);
  for (const [cx, rr, a] of [[.18, .22, .5], [.52, .16, .35], [.8, .2, .4]]) { const gr = g.createRadialGradient(w * cx, h * .55, 0, w * cx, h * .55, w * rr); gr.addColorStop(0, `rgba(226,232,218,${a})`); gr.addColorStop(1, 'rgba(226,232,218,0)'); g.fillStyle = gr; g.fillRect(0, 0, w, h); }
});

// ---------- materials ----------
const M = {
  porcelain: new THREE.MeshPhysicalMaterial({ color: 0xf3f0e8, roughness: .16, clearcoat: 1, clearcoatRoughness: .06, side: THREE.DoubleSide }),
  gold: new THREE.MeshStandardMaterial({ color: 0xd8ae58, metalness: 1, roughness: .24 }),
  linen: new THREE.MeshStandardMaterial({ map: linenMap, bumpMap: linenBump, bumpScale: .6, roughness: .95 }),
  wall: new THREE.MeshBasicMaterial({ map: sageWall }),
  bread: new THREE.MeshStandardMaterial({ map: crumbMap, roughness: .92, bumpMap: crustBump, bumpScale: .4 }),
  scone: new THREE.MeshPhysicalMaterial({ map: crustMap, roughness: .7, clearcoat: .25, clearcoatRoughness: .5, bumpMap: crustBump, bumpScale: 2 }),
  cream: new THREE.MeshPhysicalMaterial({ color: 0xfbf0d2, roughness: .45, sheen: .6, sheenColor: 0xffffff }),
  jam: new THREE.MeshPhysicalMaterial({ color: 0x7a0d1a, roughness: .08, clearcoat: 1, transmission: .0 }),
  tea: new THREE.MeshPhysicalMaterial({ color: 0x3a1806, roughness: .02, clearcoat: 1, metalness: 0, emissive: 0x2a0e02, emissiveIntensity: .25 }),
  choc: new THREE.MeshPhysicalMaterial({ color: 0x2c160b, roughness: .18, clearcoat: .8 }),
  choux: new THREE.MeshStandardMaterial({ color: 0xcf9150, roughness: .8, bumpMap: crustBump, bumpScale: 1 }),
  pastry: new THREE.MeshStandardMaterial({ color: 0xdaa560, roughness: .8, bumpMap: crustBump, bumpScale: .8 }),
  strawberry: new THREE.MeshPhysicalMaterial({ color: 0xb3101e, roughness: .22, clearcoat: .7, bumpMap: strawberryBump, bumpScale: 1.2 }),
  leaf: new THREE.MeshStandardMaterial({ color: 0x4f7a32, roughness: .6, side: THREE.DoubleSide }),
  icing: new THREE.MeshPhysicalMaterial({ color: 0xfffaf0, roughness: .35, clearcoat: .4 }),
  sponge: new THREE.MeshStandardMaterial({ color: 0xf0cf86, roughness: .9, bumpMap: crustBump, bumpScale: .6 }),
};
const fills = {
  cucumber: new THREE.MeshPhysicalMaterial({ color: 0xa9c77a, roughness: .35, clearcoat: .5 }),
  egg: new THREE.MeshStandardMaterial({ color: 0xf1d77b, roughness: .7 }),
  salmon: new THREE.MeshPhysicalMaterial({ color: 0xec8a63, roughness: .4, clearcoat: .3 }),
  ham: new THREE.MeshStandardMaterial({ color: 0xe9a3a0, roughness: .55 }),
};
const macaronCols = [0xb8cf96, 0xe9a3b6, 0xf1e19b, 0xc9b6dc, 0xf3c39a];

// ---------- builders ----------
const shadowy = o => { o.traverse(m => { if (m.isMesh) { m.castShadow = true; m.receiveShadow = true; } }); return o; };
function lathe(profile, mat, seg = 96) { const g = new THREE.LatheGeometry(profile.map(([x, y]) => new THREE.Vector2(x, y)), seg); return new THREE.Mesh(g, mat); }

function plate(Rr) {
  const g = new THREE.Group();
  const prof = [[0, -.05], [Rr * .72, -.05], [Rr * .88, -.01], [Rr * .97, .1], [Rr, .15], [Rr * .985, .165], [Rr * .95, .145], [Rr * .86, .06], [Rr * .76, .02], [0, .02]];
  g.add(lathe(prof, M.porcelain, 128));
  const rim = new THREE.Mesh(new THREE.TorusGeometry(Rr * .992, .012, 12, 160), M.gold); rim.rotation.x = Math.PI / 2; rim.position.y = .158; g.add(rim);
  const band = new THREE.Mesh(new THREE.TorusGeometry(Rr * .6, .005, 8, 160), M.gold); band.rotation.x = Math.PI / 2; band.position.y = .025; g.add(band);
  return g;
}

function finger(fill) {
  const g = new THREE.Group(), L = .58, W = .25;
  const b1 = new THREE.Mesh(new RoundedBoxGeometry(L, .085, W, 3, .02), M.bread); b1.position.y = .0425;
  const f = new THREE.Mesh(new RoundedBoxGeometry(L * .99, .045, W * 1.02, 2, .015), fills[fill]); f.position.y = .105;
  const b2 = b1.clone(); b2.position.y = .17;
  g.add(b1, f, b2); return g;
}

function scone() {
  const prof = [[0, 0], [.33, 0], [.36, .015], [.365, .06], [.37, .16], [.388, .2], [.398, .23], [.39, .27], [.376, .36], [.37, .42], [.35, .465], [.3, .485], [.15, .495], [0, .5]];
  const geo = new THREE.LatheGeometry(prof.map(([x, y]) => new THREE.Vector2(x, y)), 64, 0, Math.PI * 2);
  displace(geo, .06, 4.5, rnd() * 50, (x, y) => y > .02 ? 1 : 0); geo.rotateZ(R(-.04, .04));
  const m = new THREE.Mesh(geo, M.scone); m.rotation.y = rnd() * 6; m.scale.setScalar(R(.92, 1.06)); return m;
}

function ramekin(fillMat, blob) {
  const g = new THREE.Group();
  g.add(lathe([[0, 0], [.2, 0], [.23, .02], [.24, .16], [.225, .165], [.215, .03], [0, .03]], M.porcelain, 64));
  const rim = new THREE.Mesh(new THREE.TorusGeometry(.233, .006, 8, 64), M.gold); rim.rotation.x = Math.PI / 2; rim.position.y = .162; g.add(rim);
  if (blob) {
    const geo = new THREE.SphereGeometry(.2, 48, 32); geo.scale(1, .55, 1); displace(geo, .08, 6, rnd() * 9);
    const b = new THREE.Mesh(geo, fillMat); b.position.y = .14; g.add(b);
  } else {
    const geo = new THREE.CircleGeometry(.212, 48); displace(geo, .01, 9); const d = new THREE.Mesh(geo, fillMat); d.rotation.x = -Math.PI / 2; d.position.y = .14; g.add(d);
    const blob2 = new THREE.SphereGeometry(.07, 24, 16); blob2.scale(1, .5, 1); const bb = new THREE.Mesh(blob2, fillMat); bb.position.set(.05, .15, -.03); g.add(bb);
  }
  return g;
}

function macaron(col) {
  const g = new THREE.Group(), mat = new THREE.MeshStandardMaterial({ color: col, roughness: .55 });
  const shell = [[0, 0], [.18, 0], [.2, .015], [.205, .03], [.19, .05], [.15, .075], [.08, .088], [0, .09]];
  const top = lathe(shell, mat, 48); top.position.y = .085;
  const bot = lathe(shell, mat, 48); bot.rotation.x = Math.PI; bot.position.y = .065;
  const feet = new THREE.Mesh(new THREE.TorusGeometry(.19, .018, 8, 48), mat); feet.rotation.x = Math.PI / 2; feet.position.y = .085;
  displace(feet.geometry, .02, 30);
  const fill = new THREE.Mesh(new THREE.CylinderGeometry(.17, .17, .045, 48), M.cream); fill.position.y = .075;
  g.add(top, bot, fill, feet); g.position.y = -.0; return g;
}

function eclair() {
  const g = new THREE.Group();
  const body = new THREE.CapsuleGeometry(.085, .42, 12, 24); body.rotateZ(Math.PI / 2); displace(body, .025, 9, rnd() * 9);
  const b = new THREE.Mesh(body, M.choux); b.position.y = .085;
  const top = new THREE.CapsuleGeometry(.088, .42, 12, 24); top.rotateZ(Math.PI / 2); top.scale(1.01, .55, 1.04);
  const t = new THREE.Mesh(top, M.choc); t.position.y = .125;
  g.add(b, t); return g;
}

function tart() {
  const g = new THREE.Group();
  const case_ = new THREE.LatheGeometry([[0, 0], [.17, 0], [.2, .03], [.215, .1], [.2, .11], [.18, .05], [0, .05]].map(([x, y]) => new THREE.Vector2(x, y)), 72);
  const p = case_.attributes.position; for (let i = 0; i < p.count; i++) { const x = p.getX(i), z = p.getZ(i), a = Math.atan2(z, x), r = Math.hypot(x, z); if (r > .15) { const k = 1 + .045 * Math.cos(a * 18); p.setX(i, x * k); p.setZ(i, z * k); } } case_.computeVertexNormals();
  g.add(new THREE.Mesh(case_, M.pastry));
  const cr = new THREE.Mesh(new THREE.CylinderGeometry(.18, .18, .05, 48), M.cream); cr.position.y = .08; g.add(cr);
  const berry = (x, z, sc, rot) => {
    const geo = new THREE.LatheGeometry([[0, 0], [.03, .006], [.06, .03], [.075, .07], [.07, .1], [.05, .125], [0, .135]].map(([x, y]) => new THREE.Vector2(x, y)), 32);
    geo.rotateX(Math.PI); geo.translate(0, .135, 0);
    const m = new THREE.Mesh(geo, M.strawberry); m.position.set(x, .1, z); m.scale.setScalar(sc); m.rotation.set(rot, rnd() * 6, rot * .5); return m;
  };
  g.add(berry(0, 0, 1.15, 0)); for (let i = 0; i < 5; i++) { const a = i / 5 * 6.28; g.add(berry(Math.cos(a) * .11, Math.sin(a) * .11, .75, .5)); }
  return g;
}

function lemonSlice() {
  const g = new THREE.Group();
  const s1 = new THREE.Mesh(new RoundedBoxGeometry(.32, .2, .32, 3, .02), M.sponge); s1.position.y = .1;
  const ic = new THREE.BoxGeometry(.33, .04, .33, 20, 2, 20); displace(ic, .03, 12); const i1 = new THREE.Mesh(ic, M.icing); i1.position.y = .215;
  g.add(s1, i1);
  const zest = new THREE.MeshStandardMaterial({ color: 0xf3cf3a, roughness: .5 });
  for (let i = 0; i < 9; i++) { const z = new THREE.Mesh(new THREE.BoxGeometry(.05, .008, .008), zest); z.position.set(R(-.12, .12), .238, R(-.12, .12)); z.rotation.y = rnd() * 3; g.add(z); }
  return g;
}

function teapot() {
  const g = new THREE.Group();
  const body = [[0, 0], [.5, 0], [.56, .03], [.62, .12], [.78, .4], [.82, .6], [.76, .85], [.6, 1.0], [.42, 1.07], [.40, 1.1], [.43, 1.12], [.42, 1.14], [0, 1.14]];
  g.add(lathe(body, M.porcelain));
  const lid = lathe([[0, 1.14], [.42, 1.14], [.38, 1.2], [.25, 1.26], [.1, 1.29], [.08, 1.33], [.11, 1.38], [.08, 1.43], [0, 1.44]], M.porcelain); g.add(lid);
  const knob = new THREE.Mesh(new THREE.SphereGeometry(.07, 24, 16), M.gold); knob.position.y = 1.42; g.add(knob);
  for (const [y, r] of [[1.11, .425], [.6, .82]]) { const t = new THREE.Mesh(new THREE.TorusGeometry(r, .008, 8, 128), M.gold); t.rotation.x = Math.PI / 2; t.position.y = y; g.add(t); }
  const spoutCurve = new THREE.CatmullRomCurve3([new THREE.Vector3(.7, .35, 0), new THREE.Vector3(1.05, .55, 0), new THREE.Vector3(1.2, .95, 0), new THREE.Vector3(1.38, 1.12, 0)]);
  const sp = new THREE.TubeGeometry(spoutCurve, 64, .1, 24); const pp = sp.attributes.position;
  // taper the spout
  for (let i = 0; i < pp.count; i++) { const seg = Math.floor(i / 25) / 64, c = spoutCurve.getPoint(seg), k = 1 - seg * .55; pp.setXYZ(i, c.x + (pp.getX(i) - c.x) * k, c.y + (pp.getY(i) - c.y) * k, c.z + (pp.getZ(i) - c.z) * k); }
  sp.computeVertexNormals(); g.add(new THREE.Mesh(sp, M.porcelain));
  const hc = new THREE.CatmullRomCurve3([new THREE.Vector3(-.72, .95, 0), new THREE.Vector3(-1.08, 1.0, 0), new THREE.Vector3(-1.2, .7, 0), new THREE.Vector3(-1.02, .38, 0), new THREE.Vector3(-.76, .28, 0)]);
  g.add(new THREE.Mesh(new THREE.TubeGeometry(hc, 64, .055, 16), M.porcelain));
  return g;
}

function cupAndSaucer() {
  const g = new THREE.Group();
  g.add(lathe([[0, 0], [.4, 0], [.46, .02], [.62, .06], [.66, .085], [.6, .08], [.45, .045], [.17, .035], [0, .035]], M.porcelain));
  const sr = new THREE.Mesh(new THREE.TorusGeometry(.655, .007, 8, 128), M.gold); sr.rotation.x = Math.PI / 2; sr.position.y = .083; g.add(sr);
  const cup = lathe([[0, .04], [.15, .04], [.17, .07], [.19, .08], [.3, .14], [.39, .25], [.44, .38], [.46, .5], [.455, .51], [.43, .38], [.37, .24], [.28, .15], [.17, .1], [0, .1]], M.porcelain);
  g.add(cup);
  const cr = new THREE.Mesh(new THREE.TorusGeometry(.458, .008, 8, 128), M.gold); cr.rotation.x = Math.PI / 2; cr.position.y = .505; g.add(cr);
  const tea = new THREE.Mesh(new THREE.CircleGeometry(.425, 64), M.tea); tea.rotation.x = -Math.PI / 2; tea.position.y = .4; g.add(tea);
  const hc = new THREE.CatmullRomCurve3([new THREE.Vector3(.43, .42, 0), new THREE.Vector3(.64, .44, 0), new THREE.Vector3(.64, .26, 0), new THREE.Vector3(.37, .2, 0)]);
  g.add(new THREE.Mesh(new THREE.TubeGeometry(hc, 48, .035, 12), M.porcelain));
  // teaspoon
  const spoon = new THREE.Group();
  const bowl = new THREE.SphereGeometry(.08, 24, 12, 0, Math.PI * 2, 0, Math.PI / 2); bowl.scale(1.5, .35, 1); const bm = new THREE.Mesh(bowl, M.gold); bm.rotation.x = Math.PI; spoon.add(bm);
  const handle = new THREE.Mesh(new THREE.BoxGeometry(.55, .012, .035), M.gold); handle.position.set(.36, .02, 0); spoon.add(handle);
  spoon.position.set(-.05, .1, .5); spoon.rotation.y = .5; g.add(spoon);
  return g;
}

// ---------- scene ----------
export function buildScene(canvas, W, H) {
  const renderer = new THREE.WebGLRenderer({ canvas, antialias: true, preserveDrawingBuffer: true });
  renderer.setSize(W, H, false); renderer.setPixelRatio(1);
  renderer.toneMapping = THREE.ACESFilmicToneMapping; renderer.toneMappingExposure = .82;
  renderer.shadowMap.enabled = true; renderer.shadowMap.type = THREE.VSMShadowMap;
  const scene = new THREE.Scene(); scene.background = new THREE.Color(0x8f9c8a);
  const pmrem = new THREE.PMREMGenerator(renderer);
  scene.environment = pmrem.fromScene(new RoomEnvironment(), .04).texture; scene.environmentIntensity = .4;

  // light: soft window key from the left-back, warm fill
  const key = new THREE.DirectionalLight(0xfff0d8, 3.8); key.position.set(-9, 12, -4); key.castShadow = true;
  key.shadow.mapSize.set(2048, 2048); key.shadow.radius = 14; key.shadow.blurSamples = 24; key.shadow.bias = -.0004; key.shadow.normalBias = .02;
  Object.assign(key.shadow.camera, { left: -6, right: 6, top: 6, bottom: -6, near: 1, far: 40 });
  scene.add(key);
  const fill = new THREE.DirectionalLight(0xdfe8d8, .6); fill.position.set(8, 5, 8); scene.add(fill);
  scene.add(new THREE.HemisphereLight(0xe9efe4, 0x8a7a60, .35));

  // table + wall
  const table = new THREE.Mesh(new THREE.PlaneGeometry(60, 60), M.linen); table.rotation.x = -Math.PI / 2; table.receiveShadow = true; scene.add(table);
  const wall = new THREE.Mesh(new THREE.CylinderGeometry(22, 22, 40, 96, 1, true), new THREE.MeshBasicMaterial({ map: sageWall, side: THREE.BackSide })); wall.position.y = 12; wall.rotation.y = -1.2; scene.add(wall);
  // table edge with drape so the far background reads as a room
  

  // stand
  const stand = new THREE.Group();
  const tiers = [{ r: 1.5, y: .02 }, { r: 1.25, y: 1.35 }, { r: 1.05, y: 2.55 }];
  tiers.forEach(t => { const p = plate(t.r); p.position.y = t.y; stand.add(p); });
  const rod = new THREE.Mesh(new THREE.CylinderGeometry(.03, .03, 3.25, 24), M.gold); rod.position.y = 1.78; stand.add(rod);
  for (const y of [1.39, 2.59]) { const n = new THREE.Mesh(new THREE.SphereGeometry(.06, 24, 16), M.gold); n.position.y = y + .05; stand.add(n); }
  const ring = new THREE.Mesh(new THREE.TorusGeometry(.2, .026, 16, 64), M.gold); ring.position.y = 3.62; stand.add(ring);
  const fin = new THREE.Mesh(new THREE.SphereGeometry(.05, 16, 12), M.gold); fin.position.y = 3.4; stand.add(fin);

  // bottom tier: finger sandwiches in a ring, alternate fillings
  const fl = ['cucumber', 'egg', 'salmon', 'ham'];
  for (let i = 0; i < 8; i++) {
    const a = i / 8 * Math.PI * 2 + .2, f = finger(fl[i % 4]);
    f.position.set(Math.cos(a) * .8, .05, Math.sin(a) * .8); f.rotation.y = -a + R(-.05, .05);
    stand.add(f);
  }
  // middle: scones + cream + jam
  for (let i = 0; i < 4; i++) { const a = i / 4 * Math.PI * 2 + .6, sc = scone(); sc.position.set(Math.cos(a) * .66, 1.38, Math.sin(a) * .66); stand.add(sc); }
  const rc = ramekin(M.cream, true); rc.position.set(Math.cos(1.4) * .62 + .02, 1.38, Math.sin(1.4) * .62); rc.scale.setScalar(.85);
  // place ramekins between scones
  rc.position.set(Math.cos(.6 + Math.PI / 4) * .7, 1.37, Math.sin(.6 + Math.PI / 4) * .7); stand.add(rc);
  const rj = ramekin(M.jam, false); rj.position.set(Math.cos(.6 + Math.PI * 5 / 4) * .7, 1.37, Math.sin(.6 + Math.PI * 5 / 4) * .7); rj.scale.setScalar(.85); stand.add(rj);
  // top: cakes
  const top = [];
  for (let i = 0; i < 4; i++) top.push(macaron(macaronCols[i]));
  top.push(eclair(), tart(), lemonSlice(), tart(), eclair());
  top.forEach((o, i) => { const a = i / top.length * Math.PI * 2 + .3, r = o.children.length > 4 && i < 4 ? .62 : .58; o.position.set(Math.cos(a) * .5, 2.57, Math.sin(a) * .5); o.rotation.y = -a + R(-.3, .3); o.scale.setScalar(1.05); stand.add(o); });
  shadowy(stand); scene.add(stand);

  const pot = shadowy(teapot()); pot.position.set(-3.2, 0, -2.6); pot.rotation.y = 2.4; pot.scale.setScalar(1.25); scene.add(pot);
  const cup = shadowy(cupAndSaucer()); cup.position.set(2.2, 0, 1.6); cup.rotation.y = -.4; scene.add(cup);
  const cup2 = shadowy(cupAndSaucer()); cup2.position.set(-2.4, 0, 1.9); cup2.rotation.y = 2.6; scene.add(cup2);
  // a single side plate with a scone, cream and jam, for the foreground
  const side = shadowy(plate(.85)); side.position.set(3.3, 0, -1.4); scene.add(side);
  const sideSc = shadowy(scone()); sideSc.position.set(3.15, .04, -1.3); scene.add(sideSc);

  const camera = new THREE.PerspectiveCamera(30, W / H, .1, 200);
  const composer = new EffectComposer(renderer, new THREE.WebGLRenderTarget(W, H, { samples: 4, type: THREE.HalfFloatType }));
  composer.addPass(new RenderPass(scene, camera));
  const bokeh = new BokehPass(scene, camera, { focus: 10, aperture: .0025, maxblur: .008 }); composer.addPass(bokeh);
  composer.addPass(new OutputPass());

  return {
    THREE, renderer, scene, camera, composer, bokeh,
    shot({ pos, target, fov = 30, focus, aperture = .002, maxblur = .008 }) {
      camera.fov = fov; camera.updateProjectionMatrix();
      camera.position.set(...pos); camera.lookAt(new THREE.Vector3(...target));
      const f = focus ?? camera.position.distanceTo(new THREE.Vector3(...target));
      bokeh.uniforms.focus.value = f; bokeh.uniforms.aperture.value = aperture; bokeh.uniforms.maxblur.value = maxblur;
      composer.render();
    },
  };
}
