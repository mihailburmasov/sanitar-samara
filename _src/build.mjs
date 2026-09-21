// Сборка статического сайта ДЕЗЦЕНТР SANITAR.  Запуск: node _src/build.mjs
// Зависимостей нет (только встроенные модули Node.js 18+). Результат — обычные HTML-файлы в корне проекта.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import * as L from './layout.mjs';
import { home } from './pages-home.mjs';
import { servicePages, landingPages } from './pages-services.mjs';
import * as misc from './pages-misc.mjs';

const SRC = path.dirname(fileURLToPath(import.meta.url));
// Режим превью (например, GitHub Pages в подпапке):
//   BASE_PATH=/repo SITE_DOMAIN=user.github.io/repo NOINDEX=1 OUT_DIR=dist-pages node _src/build.mjs
const ROOT = path.resolve(SRC, '..');
const OUT = process.env.OUT_DIR ? path.resolve(ROOT, process.env.OUT_DIR) : ROOT;
const BASE = (process.env.BASE_PATH || '').replace(/\/$/, '');
const C = JSON.parse(fs.readFileSync(path.join(SRC, 'company.json'), 'utf8'));
if (process.env.SITE_DOMAIN) C.domain = process.env.SITE_DOMAIN;
if (process.env.NOINDEX) C.noindex = true;
fs.mkdirSync(OUT, { recursive: true });
const rebase = (s) => (BASE ? s.replace(/url\(\/fonts\//g, `url(${BASE}/fonts/`) : s);

/* ---------- CSS ---------- */
const crit0 = fs.readFileSync(path.join(SRC, 'css/00-critical.css'), 'utf8');
const rest = fs.readFileSync(path.join(SRC, 'css/10-rest.css'), 'utf8');
const critBlocks = [...rest.matchAll(/\/\*@critical\*\/([\s\S]*?)\/\*@end\*\//g)].map((m) => m[1]).join('\n');
const clean = (css) => css.replace(/\/\*[\s\S]*?\*\//g, '').replace(/\s*\n\s*/g, '').replace(/\s{2,}/g, ' ').replace(/\s*([{};])\s*/g, '$1');
const CRITICAL = rebase(clean(crit0 + '\n' + critBlocks));
const FULL = rebase(clean(crit0 + '\n' + rest.replace(/\/\*@(critical|end)\*\//g, '')));
fs.writeFileSync(path.join(OUT, 'styles.css'), FULL);

/* ---------- JS ---------- */
fs.copyFileSync(path.join(SRC, 'script.js'), path.join(OUT, 'script.js'));

/* ---------- Страницы ---------- */
L.init(C, CRITICAL);
const pages = [
  home(C), ...servicePages(C), ...landingPages(C),
  misc.ceny(C), misc.dokumenty(C), misc.otzyvy(C), misc.kontakty(C), misc.politika(C), misc.notFound(C)
];

const warnings = [];
const written = [];
for (const p of pages) {
  let html = L.page(p);
  if (BASE) html = html.replace(/(\s(?:href|src|action|data-video|data-lightbox)=")\/(?!\/)/g, `$1${BASE}/`);
  const file = p.path === '/' ? 'index.html' : p.path.endsWith('.html') ? p.path.slice(1) : path.join(p.path.slice(1), 'index.html');
  const full = path.join(OUT, file);
  fs.mkdirSync(path.dirname(full), { recursive: true });
  fs.writeFileSync(full, html);
  written.push(file);

  const h1 = (html.match(/<h1[\s>]/g) || []).length;
  if (h1 !== 1) warnings.push(`${p.path}: H1 = ${h1} (нужен ровно один)`);
  const tl = L.subst(p.title).length, dl = L.subst(p.description).length;
  if (tl > 60) warnings.push(`${p.path}: title ${tl} симв. (>60)`);
  if (dl > 160) warnings.push(`${p.path}: description ${dl} симв. (>160)`);
  const textLen = html.replace(/<script[\s\S]*?<\/script>|<style[\s\S]*?<\/style>|<svg[\s\S]*?<\/svg>|<[^>]+>/g, ' ').replace(/\s+/g, ' ').length;
  if (p.path !== '/404.html' && textLen < 2500) warnings.push(`${p.path}: мало текста (${textLen} симв.)`);
}

/* ---------- sitemap.xml, robots.txt ---------- */
const today = new Date().toISOString().slice(0, 10);
const indexable = pages.filter((p) => !p.noindex);
const sitemap = `<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
${indexable.map((p) => `  <url><loc>${L.url(p.path)}</loc><lastmod>${today}</lastmod><priority>${p.path === '/' ? '1.0' : p.path.split('/').length <= 3 ? '0.8' : '0.6'}</priority></url>`).join('\n')}
</urlset>
`;
fs.writeFileSync(path.join(OUT, 'sitemap.xml'), sitemap);
fs.writeFileSync(path.join(OUT, 'robots.txt'), C.noindex ? 'User-agent: *\nDisallow: /\n' : `User-agent: *\nAllow: /\nDisallow: /api/\nDisallow: /_src/\n\nSitemap: ${L.url('/sitemap.xml')}\n`);

/* ---------- Статика для отдельной папки вывода (превью) ---------- */
if (OUT !== ROOT) {
  for (const d of ['fonts', 'images', 'video']) fs.cpSync(path.join(ROOT, d), path.join(OUT, d), { recursive: true });
  for (const f of ['favicon.svg', 'apple-touch-icon.png']) fs.copyFileSync(path.join(ROOT, f), path.join(OUT, f));
  fs.writeFileSync(path.join(OUT, '.nojekyll'), '');
}

if (!C.domain) L.PLACEHOLDERS.add('ДОМЕН');
if (!C.metrikaId) L.PLACEHOLDERS.add('YM_ID (необязательно, Метрика выключена)');

console.log(`Готово: ${written.length} страниц`);
console.log(`styles.css ${(FULL.length / 1024).toFixed(1)} КБ, критический CSS ${(CRITICAL.length / 1024).toFixed(1)} КБ`);
if (warnings.length) console.log('\nПредупреждения:\n - ' + warnings.join('\n - '));
console.log('\nНе заполнено (плейсхолдеры {{...}}):\n - ' + [...L.PLACEHOLDERS].join('\n - '));
