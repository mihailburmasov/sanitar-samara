// Фоновые картинки первого экрана: по одной на страницу. Файлы делает _src/tools/make-art.cjs из обложек клиента.
import fs from 'node:fs';

export const heroDims = JSON.parse(fs.readFileSync(new URL('./hero.json', import.meta.url), 'utf8'));

// путь страницы → [картинка, object-position]
export const HERO_ART = {
  '/': ['worker-house', 'center'],
  '/dezinsekciya/': ['worker-house', '60% center'],
  '/unichtozhenie-tarakanov/': ['worker-house', 'center'],
  '/unichtozhenie-klopov/': ['worker-house', '40% center'],
  '/dezinfekciya/': ['room-fog', 'center'],
  '/obrabotka-ot-pleseni/': ['room-fog', '70% center'],
  '/dokumenty/': ['room-fog', '30% center'],
  '/fungicidnaya-obrabotka/': ['tree-spray', 'center'],
  '/obrabotka-ot-kleshchey/': ['tree-spray', '70% center'],
  '/politika-konfidencialnosti/': ['tree-spray', '30% center'],
  '/deratizaciya/': ['rats', 'center'],
  '/unichtozhenie-krys-i-myshey/': ['rats', '60% center'],
  '/ceny/': ['family-pets', 'center 40%'],
  '/otzyvy/': ['family-pets', 'center 55%'],
  '/kontakty/': ['family-pets', 'center 30%'],
  '/404.html': ['family-pets', 'center 45%']
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
