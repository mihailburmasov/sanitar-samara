const sharp = require('sharp');
const fs = require('fs');
const OUT = 'D:/Claude/Projects/Sanitar';
const SRC = 'D:/Claude/Создание сайтов/Клиенты/Юра Самара Стройград/Дез. центр/Фото обложек';
const mark = `<path d="M40 12A19 19 0 1 0 43 27" fill="none" stroke="#1F4FBF" stroke-width="5" stroke-linecap="round"/><path d="M14 33C14 20 22 13 36 12c0 13-7 21-19 21z" fill="#2E9B2E"/><path d="M15 32 28 19" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/>`;
const favicon = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48"><rect width="48" height="48" rx="11" fill="#fff"/><g transform="translate(4 4) scale(.83)">${mark}</g></svg>`;
fs.writeFileSync(OUT + '/favicon.svg', favicon);
(async () => {
  await sharp(Buffer.from(favicon), { density: 600 }).resize(180, 180).png().toFile(OUT + '/apple-touch-icon.png');
  // OG 1200x630 из верхней части обложки (логотип + специалист)
  await sharp(SRC + '/5192035b-842a-41ba-9fc2-661a76997f5b.jpg')
    .extract({ left: 0, top: 30, width: 1024, height: 538 })
    .resize(1200, 630, { fit: 'cover' }).jpeg({ quality: 82 }).toFile(OUT + '/images/og.jpg');
  console.log('ok', fs.statSync(OUT + '/images/og.jpg').size);
})();
