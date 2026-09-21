// Фоновые картинки первого экрана из рекламных обложек клиента (папка «Фото обложек»).
// Вырезаем участки БЕЗ текста, увеличиваем, для «узких» вырезок делаем полосу с плавным растворением в фон.
// Запуск: node _src/tools/make-art.cjs   (нужен npm i sharp; исходники — на диске клиента)
const sharp = require('sharp');
const fs = require('fs');
const path = require('path');

const SRC = 'D:/Claude/Создание сайтов/Клиенты/Юра Самара Стройград/Дез. центр/Фото обложек';
const ROOT = path.resolve(__dirname, '..', '..');
const OUT = path.join(ROOT, 'images', 'hero');
fs.mkdirSync(OUT, { recursive: true });

const F = {
  A: '0ad3a996-6586-4c0d-90be-688ad950230c.jpg', // лечение деревьев
  C: '5192035b-842a-41ba-9fc2-661a76997f5b.jpg', // специалист у дома, «Вместе 15 лет»
  G: '9aa95828-c8c2-42c8-b2ca-bdc7970bd0f8.jpg', // дезинфекция
  H: 'a2a184dc-bbd7-4a85-ade5-c4c28387d3f7.jpg', // документы, семья с питомцами
  K: 'e6f3cee4-ab7a-436f-a509-afc8afe39ae4.jpg'  // дератизация
};
// name: [файл, left, top, width, height, режим]  — прямоугольники без текста
//   cover — заполнить кадр; strip — полоса по центру, верх и низ растворяются в фон
const ART = {
  'worker-house': ['C', 0, 420, 690, 370, 'cover'],
  'room-fog': ['G', 0, 640, 700, 225, 'strip'],
  'tree-spray': ['A', 520, 640, 504, 165, 'strip'],
  rats: ['K', 240, 805, 700, 160, 'strip'],
  'family-pets': ['H', 620, 900, 466, 290, 'cover']
};
const W = 1600, H = 720, BG = { r: 7, g: 26, b: 72 }; // #071A48 — фон первого экрана

async function build(name, [f, left, top, w, h, mode]) {
  const crop = sharp(path.join(SRC, F[f])).extract({ left, top, width: w, height: h });
  let img;
  if (mode === 'cover') {
    img = await crop.resize(W, H, { fit: 'cover', kernel: 'lanczos3' }).sharpen({ sigma: 0.8 }).toBuffer();
  } else {
    const sh = Math.round(W * h / w); // высота полосы при полной ширине
    const strip = await crop.resize(W, sh, { kernel: 'lanczos3' }).sharpen({ sigma: 0.8 }).toBuffer();
    const y = Math.round((H - sh) / 2);
    const fade = Math.round(sh * 0.28);
    // маска: верх и низ полосы плавно уходят в прозрачность
    const mask = Buffer.from(`<svg width="${W}" height="${sh}"><defs><linearGradient id="g" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity="0"/><stop offset="${fade / sh}" stop-color="#fff" stop-opacity="1"/><stop offset="${1 - fade / sh}" stop-color="#fff" stop-opacity="1"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient></defs><rect width="${W}" height="${sh}" fill="url(#g)"/></svg>`);
    const faded = await sharp(strip).ensureAlpha().composite([{ input: mask, blend: 'dest-in' }]).png().toBuffer();
    img = await sharp({ create: { width: W, height: H, channels: 3, background: BG } }).composite([{ input: faded, left: 0, top: y }]).png().toBuffer();
  }
  await sharp(img).webp({ quality: 78 }).toFile(path.join(OUT, `${name}.webp`));
  await sharp(img).resize({ width: 800 }).webp({ quality: 66 }).toFile(path.join(OUT, `${name}-m.webp`));
}

(async () => {
  for (const [name, spec] of Object.entries(ART)) await build(name, spec);
  const dims = {};
  for (const name of Object.keys(ART)) {
    const m = await sharp(path.join(OUT, `${name}.webp`)).metadata();
    dims[name] = { w: m.width, h: m.height, kb: Math.round(fs.statSync(path.join(OUT, `${name}.webp`)).size / 1024), kbm: Math.round(fs.statSync(path.join(OUT, `${name}-m.webp`)).size / 1024) };
  }
  fs.writeFileSync(path.join(ROOT, '_src', 'hero.json'), JSON.stringify(dims, null, 1));
  console.log(dims);
})();
