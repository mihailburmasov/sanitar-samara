// Фоновые картинки первого экрана: по одной на каждую страницу (16 разных).
// Источники — материалы клиента: обложки («Фото обложек», вырезки без текста), кадры из видео работ, реальные фото.
// Режимы:  cover — заполнить кадр;  strip — полоса по центру, верх и низ растворяются в фон;
//          panel — (запасной) вертикальный кадр резко справа на размытом фоне из него же.
// Запуск: node _src/tools/make-art.cjs   (нужны sharp и ffmpeg-static: npm i sharp ffmpeg-static)
const sharp = require('sharp');
const ffmpeg = require('ffmpeg-static');
const { spawnSync } = require('child_process');
const fs = require('fs');
const os = require('os');
const path = require('path');

const SRC = 'D:/Claude/Создание сайтов/Клиенты/Юра Самара Стройград/Дез. центр';
const COVERS = SRC + '/Фото обложек/';
const ROOT = path.resolve(__dirname, '..', '..');
const OUT = path.join(ROOT, 'images', 'hero');
fs.mkdirSync(OUT, { recursive: true });

const COV = {
  A: '0ad3a996-6586-4c0d-90be-688ad950230c.jpg', // лечение деревьев
  C: '5192035b-842a-41ba-9fc2-661a76997f5b.jpg', // специалист у дома
  G: '9aa95828-c8c2-42c8-b2ca-bdc7970bd0f8.jpg', // дезинфекция
  H: 'a2a184dc-bbd7-4a85-ade5-c4c28387d3f7.jpg', // семья с питомцами
  J: 'e25f7c34-2e81-40ab-b5c2-0b12d21efefc.jpg', // крысы (крупный план)
  K: 'e6f3cee4-ab7a-436f-a509-afc8afe39ae4.jpg'  // дератизация
};
const VID = {
  interior: 'Видео/19458868185733.mp4', yard: 'Видео/19458848524933.mp4', fence: 'Видео/19458850491013.mp4',
  office: 'Видео/19789657213573.mp4', bedroom: 'Видео/19609346378373.mp4', corridor: 'Видео/19789655116421.mp4',
  garden: 'Видео/19792622127749.mp4', hall: 'Видео/19609328945797.mp4', road: 'Фунгицидная обработка/IMG_2004.MP4'
};

// name: источник + режим.  cover: [файл, left, top, width, height]
const ART = {
  // главная: фон во весь экран (16:9, крупнее остальных)
  'worker-house': { cover: ['C', 0, 420, 690, 370], mode: 'cover', size: [1920, 1080], mw: 800 },
  'room-fog': { cover: ['G', 0, 640, 700, 225], mode: 'strip' },
  'tree-spray': { cover: ['A', 520, 640, 504, 165], mode: 'strip' },
  rats: { cover: ['K', 240, 805, 700, 160], mode: 'strip' },
  'family-pets': { cover: ['H', 620, 900, 466, 290], mode: 'cover' },
  'rats-close': { cover: ['J', 640, 640, 384, 215], mode: 'cover', flip: true },
  'interior-house': { video: 'interior', t: 6, mode: 'cover' },
  'yard-shed': { video: 'yard', t: 6, mode: 'cover' },
  'fog-fence': { video: 'fence', t: 5, mode: 'cover' },
  'fog-lawn': { photo: SRC + '/Дезинфекция/8724f429-5fe4-41e9-b39d-bbb04da0590e.jpg', mode: 'cover' },
  // вертикальные кадры (720×1280): вырезаем широкую полосу с самим действием
  'office-kitchen': { video: 'office', t: 5, rect: [0, 480, 720, 324], mode: 'cover' },
  bedroom: { video: 'bedroom', t: 2, rect: [0, 320, 720, 324], mode: 'cover' },
  'green-wall': { video: 'corridor', t: 4, rect: [0, 650, 720, 324], mode: 'cover', flip: true },
  'garden-dusk': { video: 'garden', t: 5, rect: [0, 430, 720, 324], mode: 'cover', flip: true },
  'city-window': { video: 'hall', t: 10, rect: [0, 420, 720, 324], mode: 'cover' },
  'autumn-road': { video: 'road', t: 10, rect: [0, 300, 480, 216], mode: 'cover' }
};
const W = 1600, H = 720, BG = { r: 7, g: 26, b: 72 }; // #071A48 — фон первого экрана

function source(spec) {
  if (spec.cover) { const [f, left, top, w, h] = spec.cover; return { input: sharp(COVERS + COV[f]).extract({ left, top, width: w, height: h }), w, h }; }
  if (spec.photo) return { input: sharp(spec.photo), w: null, h: null };
  const tmp = path.join(os.tmpdir(), 'sanitar-frame-' + spec.video + '.png');
  const r = spawnSync(ffmpeg, ['-hide_banner', '-loglevel', 'error', '-y', '-ss', String(spec.t), '-i', SRC + '/' + VID[spec.video], '-frames:v', '1', tmp]);
  if (r.status) throw new Error('ffmpeg: ' + r.stderr);
  const input = sharp(tmp);
  return { input: spec.rect ? input.extract({ left: spec.rect[0], top: spec.rect[1], width: spec.rect[2], height: spec.rect[3] }) : input, w: null, h: null };
}

async function build(name, spec) {
  const src = source(spec);
  let base = await src.input.toBuffer(); // исходник целиком (для повторного использования)
  if (spec.flip) base = await sharp(base).flop().toBuffer(); // отражение: объект справа, слева место под текст
  let img;
  if (spec.mode === 'cover') {
    const [cw, ch] = spec.size || [W, H];
    img = await sharp(base).resize(cw, ch, { fit: 'cover', kernel: 'lanczos3' }).sharpen({ sigma: 0.8 }).png().toBuffer();
  } else if (spec.mode === 'strip') {
    const m = await sharp(base).metadata();
    const sh = Math.round(W * m.height / m.width);
    const strip = await sharp(base).resize(W, sh, { kernel: 'lanczos3' }).sharpen({ sigma: 0.8 }).toBuffer();
    const y = Math.round((H - sh) / 2);
    const fade = Math.round(sh * 0.28);
    const mask = Buffer.from(`<svg width="${W}" height="${sh}"><defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset="${fade / sh}" stop-color="#fff" stop-opacity="1"/><stop offset="${1 - fade / sh}" stop-color="#fff" stop-opacity="1"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient></defs><rect width="${W}" height="${sh}" fill="url(#g)"/></svg>`);
    const faded = await sharp(strip).ensureAlpha().composite([{ input: mask, blend: 'dest-in' }]).png().toBuffer();
    img = await sharp({ create: { width: W, height: H, channels: 3, background: BG } }).composite([{ input: faded, left: 0, top: y }]).png().toBuffer();
  } else { // panel
    const back = await sharp(base).resize(W, H, { fit: 'cover' }).blur(26).modulate({ brightness: 0.55, saturation: 1.1 }).png().toBuffer();
    const m = await sharp(base).metadata();
    const k = Math.min(760 / m.width, 520 / m.height);
    const pw = Math.round(m.width * k), ph = Math.round(m.height * k);
    const round = Buffer.from(`<svg width="${pw}" height="${ph}"><rect width="${pw}" height="${ph}" rx="18" fill="#fff"/></svg>`);
    const panel = await sharp(base).resize(pw, ph, { kernel: 'lanczos3' }).ensureAlpha().composite([{ input: round, blend: 'dest-in' }]).png().toBuffer();
    const border = Buffer.from(`<svg width="${pw}" height="${ph}"><rect x="1" y="1" width="${pw - 2}" height="${ph - 2}" rx="17" fill="none" stroke="#fff" stroke-opacity=".55" stroke-width="2"/></svg>`);
    const left = W - 90 - pw, top = Math.round((H - ph) / 2);
    img = await sharp(back).composite([{ input: panel, left, top }, { input: border, left, top }]).png().toBuffer();
  }
  await sharp(img).webp({ quality: 78 }).toFile(path.join(OUT, `${name}.webp`));
  await sharp(img).resize({ width: spec.mw || 800 }).webp({ quality: 68 }).toFile(path.join(OUT, `${name}-m.webp`));
}

(async () => {
  for (const [name, spec] of Object.entries(ART)) await build(name, spec);
  const dims = {};
  for (const name of Object.keys(ART)) {
    const f = path.join(OUT, `${name}.webp`);
    const m = await sharp(f).metadata();
    dims[name] = { w: m.width, h: m.height, mw: ART[name].mw || 800, kb: Math.round(fs.statSync(f).size / 1024), kbm: Math.round(fs.statSync(path.join(OUT, `${name}-m.webp`)).size / 1024) };
  }
  fs.writeFileSync(path.join(ROOT, '_src', 'hero.json'), JSON.stringify(dims, null, 1));
  console.log(Object.entries(dims).map(([n, d]) => `${n}: ${d.kb} КБ / ${d.kbm} КБ`).join('\n'));
})();
