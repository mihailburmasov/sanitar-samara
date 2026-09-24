const sharp = require('sharp');
const fs = require('fs');
const OUT = 'D:/Claude/Projects/Sanitar/public';
const SRC = 'D:/Claude/Создание сайтов/Клиенты/Юра Самара Стройград/Дез. центр/Фото обложек';
(async () => {
  // OG 1200x630 из верхней части обложки (логотип + специалист)
  await sharp(SRC + '/5192035b-842a-41ba-9fc2-661a76997f5b.jpg')
    .extract({ left: 0, top: 30, width: 1024, height: 538 })
    .resize(1200, 630, { fit: 'cover' }).jpeg({ quality: 82 }).toFile(OUT + '/images/og.jpg');
  console.log('ok', fs.statSync(OUT + '/images/og.jpg').size);
})();
