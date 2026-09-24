// Фоновые картинки первого экрана: по одной на каждую страницу (16 разных).
// Источники — материалы клиента: обложки («Фото обложек», вырезки без текста), кадры из видео работ, реальные фото.
// Исходники маленькие (обложки 1024×1536, видео 720–1280 px), поэтому их увеличиваем нейросетью Real-ESRGAN,
// а не растягиваем: укажите путь к realesrgan-ncnn-vulkan.exe (релиз v0.2.5.0 на github.com/xinntao/Real-ESRGAN)
// в переменной REALESRGAN. Без неё — обычное увеличение lanczos (хуже).
// На каждую картинку три файла: name.webp (2000 px, компьютер), name-1200.webp, name-m.webp (вертикальный, для телефона).
// Режимы:  cover — заполнить кадр;  strip — полоса по центру, верх и низ растворяются в фон.
// Запуск: REALESRGAN=.../realesrgan-ncnn-vulkan.exe node _src/tools/make-art.cjs [имя ...]
//         (нужны sharp и ffmpeg-static: npm i sharp ffmpeg-static)
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
const TMP = fs.mkdtempSync(path.join(os.tmpdir(), 'sanitar-art-'));
const ESRGAN = process.env.REALESRGAN || '';
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

// cover: [файл, left, top, width, height];  rect — полоса из вертикального кадра для компьютера;
// mrect — участок кадра для телефона (по умолчанию вертикальный вырез из картинки для компьютера);
// mx — где брать вертикальный вырез по горизонтали (0…1);  ai:false — не увеличивать нейросетью (туман выходит пятнами)
const ART = {
  // главная: фон во весь экран (16:9, крупнее остальных)
  'worker-spray': { photo: COVERS + 'Новая.png', mode: 'cover', size: [2400, 1350], msize: [810, 1440], mx: 0.35 },
  'room-fog': { cover: ['G', 0, 640, 700, 225], mode: 'strip', mx: 0.35 },
  'tree-spray': { cover: ['A', 520, 640, 504, 165], mode: 'strip', mx: 0.5 },
  rats: { cover: ['K', 240, 805, 700, 160], mode: 'strip', mx: 0.55 },
  'family-pets': { cover: ['H', 620, 900, 466, 290], mode: 'cover', mx: 0.5 },
  'rats-close': { cover: ['J', 640, 640, 384, 215], mode: 'cover', flip: true, mx: 0.75 },
  'interior-house': { video: 'interior', t: 6, mode: 'cover', mx: 0.68 },
  'yard-shed': { video: 'yard', t: 6, mode: 'cover', mx: 0.75 },
  'fog-fence': { video: 'fence', t: 5, mode: 'cover', mx: 0.5 },
  'fog-lawn': { photo: SRC + '/Дезинфекция/8724f429-5fe4-41e9-b39d-bbb04da0590e.jpg', mode: 'cover', mx: 0.5 },
  // вертикальные кадры (720×1280): для компьютера широкая полоса с действием, для телефона — почти весь кадр
  'office-kitchen': { video: 'office', t: 5, rect: [0, 480, 720, 324], mrect: [0, 160, 720, 960], mode: 'cover' },
  bedroom: { video: 'bedroom', t: 2, rect: [0, 320, 720, 324], mrect: [0, 160, 720, 960], mode: 'cover' },
  'green-wall': { video: 'corridor', t: 4, rect: [0, 650, 720, 324], mrect: [0, 240, 720, 960], mode: 'cover', flip: true },
  'garden-dusk': { video: 'garden', t: 5, rect: [0, 430, 720, 324], mrect: [0, 160, 720, 960], mode: 'cover', flip: true },
  'city-window': { video: 'hall', t: 10, rect: [0, 420, 720, 324], mrect: [0, 160, 720, 960], mode: 'cover' },
  'autumn-road': { video: 'road', t: 10, rect: [0, 300, 480, 216], mrect: [0, 104, 480, 640], mode: 'cover' }
};
const W = 2000, H = 900, MW = 900, MH = 1200, BG = { r: 7, g: 26, b: 72 }; // #071A48 — фон первого экрана

// резкость кадра: дисперсия лапласиана на уменьшенной копии
async function sharpness(file) {
  const { data } = await sharp(file).resize({ width: 480 }).greyscale()
    .convolve({ width: 3, height: 3, kernel: [0, 1, 0, 1, -4, 1, 0, 1, 0], offset: 128 }).raw().toBuffer({ resolveWithObject: true });
  let s = 0, s2 = 0;
  for (const v of data) { s += v; s2 += v * v; }
  const n = data.length; return s2 / n - (s / n) ** 2;
}

// самый резкий кадр в окне ±1 с вокруг t (смаз от движения нейросеть не убирает)
async function bestFrame(video, t) {
  let best = null, bestS = -1;
  for (let dt = -1; dt <= 1.001; dt += 0.2) {
    const ts = Math.max(0, t + dt).toFixed(2);
    const f = path.join(TMP, `${video}-${ts}.png`);
    const r = spawnSync(ffmpeg, ['-hide_banner', '-loglevel', 'error', '-y', '-ss', ts, '-i', SRC + '/' + VID[video], '-frames:v', '1', f]);
    if (r.status || !fs.existsSync(f)) continue;
    const s = await sharpness(f);
    if (s > bestS) { bestS = s; best = f; }
  }
  if (!best) throw new Error('ffmpeg: нет кадра ' + video);
  return best;
}

// увеличение ×4 нейросетью (или lanczos, если Real-ESRGAN не указан/не сработал)
let n = 0;
async function upscale(buf, ai) {
  const m = await sharp(buf).metadata();
  if (ESRGAN && ai !== false) {
    const i = path.join(TMP, `in-${n}.png`), o = path.join(TMP, `out-${n++}.png`);
    await sharp(buf).png().toFile(i);
    const r = spawnSync(ESRGAN, ['-i', i, '-o', o, '-n', 'realesrgan-x4plus', '-t', '128'], { cwd: path.dirname(ESRGAN) });
    if (!r.status && fs.existsSync(o)) return fs.readFileSync(o);
    console.warn('Real-ESRGAN не сработал, lanczos');
  }
  return sharp(buf).resize(m.width * 4, m.height * 4, { kernel: 'lanczos3' }).png().toBuffer();
}

async function sources(spec) {
  if (spec.cover) {
    const [f, left, top, width, height] = spec.cover;
    return { base: await sharp(COVERS + COV[f]).extract({ left, top, width, height }).png().toBuffer() };
  }
  if (spec.photo) return { base: await sharp(spec.photo).png().toBuffer() };
  const frame = await bestFrame(spec.video, spec.t);
  const cut = (r) => (r ? sharp(frame).extract({ left: r[0], top: r[1], width: r[2], height: r[3] }) : sharp(frame)).png().toBuffer();
  return { base: await cut(spec.rect), mob: spec.mrect ? await cut(spec.mrect) : null };
}

async function build(name, spec) {
  const src = await sources(spec);
  const flip = async (b) => (spec.flip ? sharp(b).flop().png().toBuffer() : b); // объект справа, слева место под текст
  const up = await upscale(await flip(src.base), spec.ai);
  const [cw, ch] = spec.size || [W, H];
  let img;
  if (spec.mode === 'strip') {
    const m = await sharp(up).metadata();
    const sh = Math.round(cw * m.height / m.width);
    const strip = await sharp(up).resize(cw, sh, { kernel: 'lanczos3' }).toBuffer();
    const y = Math.round((ch - sh) / 2);
    const fade = Math.round(sh * 0.28);
    const mask = Buffer.from(`<svg width="${cw}" height="${sh}"><defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset="${fade / sh}" stop-color="#fff" stop-opacity="1"/><stop offset="${1 - fade / sh}" stop-color="#fff" stop-opacity="1"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient></defs><rect width="${cw}" height="${sh}" fill="url(#g)"/></svg>`);
    const faded = await sharp(strip).ensureAlpha().composite([{ input: mask, blend: 'dest-in' }]).png().toBuffer();
    img = await sharp({ create: { width: cw, height: ch, channels: 3, background: BG } }).composite([{ input: faded, left: 0, top: y }]).png().toBuffer();
  } else {
    img = await sharp(up).resize(cw, ch, { fit: 'cover', kernel: 'lanczos3' }).png().toBuffer();
  }
  await sharp(img).webp({ quality: 82 }).toFile(path.join(OUT, `${name}.webp`));
  await sharp(img).resize({ width: 1200 }).webp({ quality: 80 }).toFile(path.join(OUT, `${name}-1200.webp`));

  // телефон: вертикальный кадр
  const [mw, mh] = spec.msize || [MW, MH];
  let mob;
  if (src.mob) {
    mob = await sharp(await upscale(await flip(src.mob), spec.ai)).resize(mw, mh, { fit: 'cover', kernel: 'lanczos3' }).png().toBuffer();
  } else {
    // из увеличенного исходника (для strip — из его средней полосы) вырезаем участок нужной пропорции
    const m = await sharp(up).metadata();
    const hh = m.height, ww = Math.min(m.width, Math.round(hh * mw / mh));
    const left = Math.round(Math.max(0, Math.min(m.width - ww, (spec.mx ?? 0.5) * m.width - ww / 2)));
    mob = await sharp(up).extract({ left, top: 0, width: ww, height: hh }).resize(mw, mh, { fit: 'cover', kernel: 'lanczos3' }).png().toBuffer();
  }
  await sharp(mob).webp({ quality: 76 }).toFile(path.join(OUT, `${name}-m.webp`));
}

(async () => {
  const only = process.argv.slice(2);
  if (!ESRGAN) console.warn('REALESRGAN не задан — увеличение lanczos');
  for (const [name, spec] of Object.entries(ART)) if (!only.length || only.includes(name)) { await build(name, spec); console.log('готово', name); }
  const dims = {};
  const kb = (f) => Math.round(fs.statSync(path.join(OUT, f)).size / 1024);
  for (const name of Object.keys(ART)) {
    const d = await sharp(path.join(OUT, `${name}.webp`)).metadata();
    const m = await sharp(path.join(OUT, `${name}-m.webp`)).metadata();
    dims[name] = { w: d.width, h: d.height, mw: m.width, mh: m.height, kb: kb(`${name}.webp`), kb1200: kb(`${name}-1200.webp`), kbm: kb(`${name}-m.webp`) };
  }
  fs.writeFileSync(path.join(ROOT, '_src', 'hero.json'), JSON.stringify(dims, null, 1));
  console.log(Object.entries(dims).map(([k, d]) => `${k}: ${d.kb} / ${d.kb1200} / ${d.kbm} КБ`).join('\n'));
  fs.rmSync(TMP, { recursive: true, force: true });
})();
