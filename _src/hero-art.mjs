// Фоновые картинки первого экрана: на каждой странице своя. Файлы делает _src/tools/make-art.cjs
// из материалов клиента (обложки, кадры из видео работ, реальные фото).
import fs from 'node:fs';

export const heroDims = JSON.parse(fs.readFileSync(new URL('./hero.json', import.meta.url), 'utf8'));

// путь страницы → [картинка, object-position]. Позиция важна на телефоне, где виден узкий участок кадра.
export const HERO_ART = {
  '/': ['worker-spray', 'center'],
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

// На главной фон занимает всю ширину экрана, на внутренних — правые 72%.
// На телефоне (до 767px) — отдельный вертикальный файл -m, иначе широкая картинка растягивается в 4–5 раз.
const MOBILE = '(max-width:767px)', DESKTOP = '(min-width:768px)';
const sizesFor = (path) => (path === '/' ? '100vw' : '(min-width:920px) 72vw, 100vw');
const art = (path) => {
  const [name, pos] = HERO_ART[path] || HERO_ART['/'];
  const d = heroDims[name];
  const base = `/images/hero/${name}`;
  return { pos, d, src: `${base}.webp`, srcM: `${base}-m.webp`, srcset: `${base}-1200.webp 1200w, ${base}.webp ${d.w}w`, sizes: sizesFor(path) };
};

// картинки-полосы (make-art.cjs, mode: 'strip'): верх и низ уже растворены в фон
const STRIP = new Set(['room-fog', 'tree-spray', 'rats']);

// fade — затенить верх и низ картинки, как у полос (страницы услуг)
export function heroBg(path, fade = false) {
  const a = art(path);
  const [name] = HERO_ART[path] || HERO_ART['/'];
  const cls = fade && !STRIP.has(name) ? ' hero__bg--fade' : '';
  return `<div class="hero__bg${cls}" aria-hidden="true"><picture><source media="${MOBILE}" srcset="${a.srcM}" width="${a.d.mw}" height="${a.d.mh}"><img src="${a.src}" srcset="${a.srcset}" sizes="${a.sizes}" width="${a.d.w}" height="${a.d.h}" alt="" fetchpriority="high" decoding="async" style="object-position:${a.pos}"></picture></div>`;
}

export function heroPreload(path) {
  const a = art(path);
  return `<link rel="preload" as="image" href="${a.srcM}" media="${MOBILE}" fetchpriority="high"><link rel="preload" as="image" href="${a.src}" imagesrcset="${a.srcset}" imagesizes="${a.sizes}" media="${DESKTOP}" fetchpriority="high">`;
}
