// Фоновые картинки первого экрана: на каждой странице своя. Файлы делает _src/tools/make-art.cjs
// из материалов клиента (обложки, кадры из видео работ, реальные фото).
import fs from 'node:fs';

export const heroDims = JSON.parse(fs.readFileSync(new URL('./hero.json', import.meta.url), 'utf8'));

// путь страницы → [картинка, object-position]. Позиция важна на телефоне, где виден узкий участок кадра.
export const HERO_ART = {
  '/': ['worker-house', 'center'],
  '/dezinsekciya/': ['interior-house', '68% center'],
  '/deratizaciya/': ['rats', 'center'],
  '/dezinfekciya/': ['room-fog', 'center'],
  '/fungicidnaya-obrabotka/': ['tree-spray', 'center'],
  '/unichtozhenie-tarakanov/': ['office-kitchen', '65% center'],
  '/unichtozhenie-klopov/': ['bedroom', '62% center'],
  '/unichtozhenie-krys-i-myshey/': ['rats-close', '75% center'],
  '/obrabotka-ot-kleshchey/': ['garden-dusk', '80% center'],
  '/obrabotka-ot-pleseni/': ['green-wall', '75% center'],
  '/ceny/': ['yard-shed', '75% center'],
  '/dokumenty/': ['fog-lawn', 'center'],
  '/otzyvy/': ['family-pets', 'center 45%'],
  '/kontakty/': ['city-window', '85% center'],
  '/politika-konfidencialnosti/': ['autumn-road', '58% center'],
  '/404.html': ['fog-fence', 'center']
};

const SIZES = '(min-width:920px) 72vw, 100vw';
const art = (path) => {
  const [name, pos] = HERO_ART[path] || HERO_ART['/'];
  const d = heroDims[name];
  return { name, pos, d, src: `/images/hero/${name}.webp`, srcM: `/images/hero/${name}-m.webp` };
};

export function heroBg(path) {
  const a = art(path);
  return `<div class="hero__bg" aria-hidden="true"><img src="${a.src}" srcset="${a.srcM} 800w, ${a.src} ${a.d.w}w" sizes="${SIZES}" width="${a.d.w}" height="${a.d.h}" alt="" fetchpriority="high" decoding="async" style="object-position:${a.pos}"></div>`;
}

export function heroPreload(path) {
  const a = art(path);
  return `<link rel="preload" as="image" href="${a.src}" imagesrcset="${a.srcM} 800w, ${a.src} ${a.d.w}w" imagesizes="${SIZES}" fetchpriority="high">`;
}
