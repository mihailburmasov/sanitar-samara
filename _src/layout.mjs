import { sprite, icon } from './icons.mjs';
import { heroBg as heroBgHtml, heroPreload } from './hero-art.mjs';
import { NAV, SERVICES, PESTS, PRICE, VIDEOS, VIDEO_CATS, REVIEWS, STEPS, METHODS, DOCS } from './data.mjs';

let C = null;
let CRITICAL = '';
let formSeq = 0;
export const PLACEHOLDERS = new Set();

export function init(company, criticalCss) { C = company; CRITICAL = criticalCss; }
export const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

// Плейсхолдер: видимый жёлтый маркер, если значение не задано в company.json
export function val(key, value, html = true) {
  if (value) return html ? esc(value) : value;
  PLACEHOLDERS.add(key);
  return html ? `<mark class="ph">{{${key}}}</mark>` : `{{${key}}}`;
}
export const site = () => `https://${C.domain || '{{ДОМЕН}}'}`;
export const url = (path) => `${site()}${path}`;
export const phone1 = () => C.phones[0];
// Подстановка цен из data.mjs в тексты (токены @@PRICE_FROM@@ / @@PRICE_LAND@@)
export const subst = (s) => String(s).replace(/@@PRICE_FROM@@/g, PRICE.from).replace(/@@PRICE_LAND@@/g, PRICE.land);

export function phoneLink(p, cls = '', inner) {
  return `<a class="${cls}" href="tel:${p.tel}" data-goal="phone_click">${inner ?? esc(p.display)}</a>`;
}

/* ---------- Логотип ---------- */
export function logoMark() {
  return `<svg viewBox="0 0 48 48" aria-hidden="true" focusable="false"><path d="M40 12A19 19 0 1 0 43 27" fill="none" stroke="#1F4FBF" stroke-width="5" stroke-linecap="round"/><path d="M14 33C14 20 22 13 36 12c0 13-7 21-19 21z" fill="#2E9B2E"/><path d="M15 32 28 19" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/></svg>`;
}
export function logo(cls = '') {
  return `<a class="logo ${cls}" href="/" title="На главную">${logoMark()}<span class="logo__t"><small>ДЕЗЦЕНТР</small> <b>SANITAR</b></span></a>`;
}

/* ---------- Шапка ---------- */
export function header(current) {
  const groupActive = SERVICES.some((s) => `/${s.slug}/` === current);
  const sub = SERVICES.map((s) => `<a href="/${s.slug}/"${current === `/${s.slug}/` ? ' aria-current="page"' : ''}>${icon(s.icon)}<span>${s.name}</span></a>`).join('');
  const links = [['/ceny/', 'Цены'], ['/dokumenty/', 'Документы'], ['/otzyvy/', 'Отзывы'], ['/kontakty/', 'Контакты']]
    .map(([h, l]) => `<a href="${h}"${current === h ? ' aria-current="page"' : ''}>${l}</a>`).join('');
  const mob = [...SERVICES.map((s) => [`/${s.slug}/`, s.name]), ['/ceny/', 'Цены'], ['/dokumenty/', 'Документы'], ['/otzyvy/', 'Отзывы'], ['/kontakty/', 'Контакты']]
    .map(([h, l]) => `<a href="${h}">${l}${icon('arrow')}</a>`).join('');
  const p = phone1();
  return `<a class="skip" href="#main">Перейти к содержимому</a>
<header class="header">
  <div class="container header__in">
    ${logo()}
    <nav class="nav" aria-label="Основное меню">
      <div class="nav__group">
        <button type="button" class="nav__btn${groupActive ? ' is-current' : ''}" aria-haspopup="true" aria-expanded="false">Услуги ${icon('arrow', 'nav__chev')}</button>
        <div class="nav__sub">${sub}</div>
      </div>
      ${links}
    </nav>
    ${phoneLink(p, 'header__phone', `${icon('phone')}<span>${esc(p.display)}</span>`)}
    <button type="button" class="btn btn--lime btn--sm header__cta" data-open-modal>Вызвать специалиста</button>
    <button type="button" class="icon-btn burger" aria-label="Открыть меню" aria-expanded="false" aria-controls="mnav" data-burger>${icon('menu')}</button>
  </div>
  <nav class="mnav" id="mnav" aria-label="Мобильное меню" hidden>
    ${mob}
    <button type="button" class="btn btn--lime btn--block" data-open-modal>Вызвать специалиста</button>
  </nav>
</header>`;
}

/* ---------- Подвал ---------- */
export function footer() {
  const p = C.phones;
  const year = new Date().getFullYear();
  const svc = SERVICES.map((s) => `<li><a href="/${s.slug}/">${s.name}</a></li>`).join('');
  const credit = `https://sitomika.ru/?utm_source=${C.slug}&amp;utm_medium=footer&amp;utm_campaign=client-sites`;
  return `<footer class="footer">
  <div class="container">
    <div class="footer__grid">
      <div>
        ${logo('logo--footer')}
        <p style="margin-top:14px">Санитарная обработка в Самаре и Самарской области: дезинсекция, дератизация, дезинфекция, фунгицидная обработка.</p>
        <p class="footer__soc">Наши группы с полезными материалами:</p>
        <div class="social">
          <a href="${esc(C.telegram)}" target="_blank" rel="noopener" data-goal="telegram_click">${icon('telegram')} Telegram-канал</a>
          <a href="${esc(C.max)}" target="_blank" rel="noopener" data-goal="max_click">${icon('max')} Группа в MAX</a>
        </div>
      </div>
      <div><p class="footer__h">Услуги</p><ul>${svc}</ul></div>
      <div><p class="footer__h">Компания</p><ul>
        <li><a href="/ceny/">Цены</a></li><li><a href="/dokumenty/">Документы</a></li><li><a href="/otzyvy/">Отзывы</a></li><li><a href="/kontakty/">Контакты</a></li></ul></div>
      <div><p class="footer__h">Контакты</p>
        <p>${phoneLink(p[0], '', esc(p[0].display))}<br>${phoneLink(p[1], '', esc(p[1].display))}</p>
        <p>Адрес: ${val('АДРЕС', C.address)}</p>
        <p>Режим работы: ${val('ЧАСЫ_РАБОТЫ', C.hours)}</p>
        <p>E-mail: ${val('EMAIL', C.email)}</p>
      </div>
    </div>
    <div class="footer__bottom">
      <span>© ${year} ${val('ЮР_НАЗВАНИЕ', C.legal.name)}. ИНН ${val('ИНН', C.legal.inn)}, ОГРН ${val('ОГРН', C.legal.ogrn)}</span>
      <span class="footer__bottom-links">
        <a href="/politika-konfidencialnosti/">Политика конфиденциальности</a>
        <a href="/politika-konfidencialnosti/#soglasie">Согласие на обработку данных</a>
        <a class="footer-credit" href="${credit}" target="_blank" rel="noopener">Разработано в sitomika.ru</a>
      </span>
    </div>
  </div>
</footer>`;
}

/* ---------- Нижняя панель, модалки, cookie ---------- */
export function bottomBar() {
  const p = phone1();
  return `<div class="bar" role="navigation" aria-label="Быстрая связь">
  <a class="btn btn--lime" href="tel:${p.tel}" data-goal="phone_click">${icon('phone')} Позвонить</a>
  <button type="button" class="btn btn--ghost" data-open-modal>${icon('clipboard')} Заявка</button>
</div>`;
}

export function modals() {
  return `<dialog id="lead-modal" aria-labelledby="lead-modal-title">
  <div class="modal">
    <button type="button" class="icon-btn modal__x" data-close aria-label="Закрыть">${icon('close')}</button>
    <h2 id="lead-modal-title">Вызвать специалиста</h2>
    <p style="color:var(--muted)">Оставьте телефон — перезвоним и рассчитаем стоимость.</p>
    ${leadForm({ variant: 'modal' })}
  </div>
</dialog>
<dialog id="lightbox" class="lightbox" aria-label="Просмотр документа">
  <button type="button" class="icon-btn modal__x" data-close aria-label="Закрыть">${icon('close')}</button>
  <img alt="" width="800" height="1000">
  <p></p>
</dialog>`;
}

export function cookieBanner() {
  const text = C.metrikaId
    ? 'Сайт использует файлы cookie для работы и Яндекс.Метрику (статистика и вебвизор). Продолжая, вы соглашаетесь с этим.'
    : 'Сайт использует файлы cookie для корректной работы.';
  return `<div class="cookie" id="cookie" role="region" aria-label="Уведомление о cookie" hidden>
  <p>${text} <a href="/politika-konfidencialnosti/#cookie-policy">Подробнее</a></p>
  <button type="button" class="btn btn--blue btn--sm" data-cookie-ok>Понятно</button>
</div>`;
}

/* ---------- Формы ---------- */
const SERVICE_OPTS = ['Дезинсекция', 'Дератизация', 'Дезинфекция', 'Фунгицидная обработка', 'Не знаю, нужна консультация'];
const OBJECT_OPTS = ['Квартира', 'Дом', 'Участок', 'Коммерческий объект'];

export function leadForm({ variant = 'full', service = '', cta = 'Отправить заявку', id } = {}) {
  const n = ++formSeq;
  const p = (s) => `f${n}-${s}`;
  const opts = (arr, sel) => arr.map((o) => `<option${o === sel ? ' selected' : ''}>${o}</option>`).join('');
  const nameField = `<div class="field"><label for="${p('name')}">Имя <i>*</i></label><input class="input" id="${p('name')}" name="name" type="text" autocomplete="given-name" required minlength="2" maxlength="80"><span class="err" data-err="name"></span></div>`;
  const phoneField = `<div class="field"><label for="${p('phone')}">Телефон <i>*</i></label><input class="input" id="${p('phone')}" name="phone" type="tel" inputmode="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" required><span class="err" data-err="phone"></span></div>`;
  const serviceField = `<div class="field"><label for="${p('service')}">Услуга</label><select class="input" id="${p('service')}" name="service">${opts(SERVICE_OPTS, service)}</select></div>`;
  const consent = `<label class="consent"><input type="checkbox" name="consent" value="1" required><span>Я согласен(-на) на обработку персональных данных в соответствии с <a href="/politika-konfidencialnosti/" target="_blank" rel="noopener">политикой конфиденциальности</a>.</span></label><span class="err" data-err="consent"></span>`;
  const hp = `<div class="hp" aria-hidden="true"><label>Не заполняйте это поле <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div><input type="hidden" name="t" value=""><input type="hidden" name="source" value="${variant}">`;
  const btn = `<button class="btn btn--lime btn--block" type="submit" disabled><span class="spinner" aria-hidden="true"></span><span class="btn__t">${cta}</span></button>`;
  const status = '<div class="form__status" role="status" aria-live="polite"></div>';
  const submit = btn + status;

  if (variant === 'mini') {
    return `<form class="mini-form" data-lead novalidate method="post" action="${esc(C.formEndpoint)}">${hp}
  <div class="mini-form__row">${nameField}${phoneField}<div class="mini-btn">${btn}</div></div>
  <div style="margin-top:10px">${consent}</div>${status}</form>`;
  }
  if (variant === 'modal') {
    return `<form class="form" data-lead novalidate method="post" action="${esc(C.formEndpoint)}" style="box-shadow:none;border:0;padding:0">${hp}
  <div class="form__grid">${nameField}${phoneField}<div class="full">${serviceField}</div><div class="full">${consent}</div><div class="full">${submit}</div></div></form>`;
  }
  // full
  return `<form class="form" data-lead novalidate method="post" action="${esc(C.formEndpoint)}">${hp}
  <div class="form__grid">
    ${nameField}${phoneField}
    ${serviceField}
    <div class="field"><label for="${p('obj')}">Тип объекта</label><select class="input" id="${p('obj')}" name="object"><option value="">Выберите</option>${opts(OBJECT_OPTS, '')}</select></div>
    <div class="field"><label for="${p('addr')}">Адрес или район</label><input class="input" id="${p('addr')}" name="address" type="text" autocomplete="street-address" maxlength="200"></div>
    <div class="field"><label for="${p('time')}">Удобное время звонка</label><input class="input" id="${p('time')}" name="call_time" type="text" placeholder="например, после 18:00" maxlength="80"></div>
    <div class="field full"><label for="${p('msg')}">Комментарий</label><textarea class="input" id="${p('msg')}" name="message" maxlength="1000" placeholder="Какие насекомые или грызуны, площадь, что уже пробовали"></textarea></div>
    <div class="full">${consent}</div>
    <div class="full">${submit}</div>
  </div></form>`;
}

/* Блок «Заявка» — форма + контакты */
export function formSection({ title = 'Оставьте заявку', lead = 'Укажите, что нужно обработать: перезвоним, уточним детали и рассчитаем стоимость.', service = '', alt = false } = {}) {
  const p = C.phones;
  return `<section class="section${alt ? ' section--alt' : ''}" id="zayavka"><div class="container">
  <div class="section__head"><span class="eyebrow">Заявка</span><h2>${title}</h2><p>${lead}</p></div>
  <div class="form-wrap">
    ${leadForm({ variant: 'full', service })}
    <div class="contact-card">
      ${phoneLink(p[0], '', `${icon('phone')}<span>${esc(p[0].display)}<small>Позвонить</small></span>`)}
      ${phoneLink(p[1], '', `${icon('phone')}<span>${esc(p[1].display)}<small>Позвонить</small></span>`)}
    </div>
  </div></div></section>`;
}

/* ---------- Компоненты контента ---------- */
export function vidTile(v, { hero = false } = {}) {
  const src = `/video/work-${v.id}.mp4`;
  const poster = `/video/work-${v.id}.webp`;
  const cap = hero ? '' : `<span class="vid__cap">${esc(v.cap)}</span>`;
  return `<button type="button" class="vid" data-video="${src}" data-cat="${v.cat}" aria-label="Смотреть видео: ${esc(v.cap)}">
    <img src="${poster}" width="${v.w}" height="${v.h}" alt="${esc(v.cap)} — кадр из видео"${hero ? ' fetchpriority="high"' : ' loading="lazy"'} decoding="async">
    <span class="vid__play">${icon('play')}</span>${cap}</button>`;
}

export function reviewCards(list) {
  return list.map((r) => `<article class="card review reveal">
    <div class="review__top"><span class="stars" role="img" aria-label="Оценка 5 из 5">${icon('star').repeat(5)}</span></div>
    <blockquote>${esc(r.text)}</blockquote>
    <div class="review__who">${esc(r.name)}<small>Отзыв на Яндексе, ${esc(r.date)}</small></div></article>`).join('');
}
export function reviewsFor(tags, max = 3) {
  const pool = REVIEWS.filter((r) => r.tags.some((t) => tags.includes(t)));
  const rest = REVIEWS.filter((r) => !pool.includes(r));
  return [...pool, ...rest].slice(0, max);
}
export function yandexLink(text = 'Все отзывы на Яндексе') {
  return C.yandexReviewsUrl
    ? `<a class="btn btn--ghost" href="${esc(C.yandexReviewsUrl)}" target="_blank" rel="noopener">${text}</a>`
    : '';
}

export function faqBlock(items) {
  return `<div class="faq">${items.map((f) => `<details><summary>${esc(f.q)}</summary><div class="ans">${f.a}</div></details>`).join('')}</div>`;
}

export const heroBg = heroBgHtml;
export function crumbs(items, inner = false) {
  const ol = `<ol>${items.map((c, i) => (i === items.length - 1 ? `<li aria-current="page">${esc(c.name)}</li>` : `<li><a href="${c.href}">${esc(c.name)}</a></li>`)).join('')}</ol>`;
  return `<nav class="crumbs" aria-label="Хлебные крошки">${inner ? ol : `<div class="container">${ol}</div>`}</nav>`;
}

export function priceTable() {
  const head = PRICE.head.map((h) => `<th scope="col">${h}</th>`).join('');
  const rows = PRICE.rows.map((r) => `<tr><th scope="row">${r.name}</th>${r.v.map((x) => `<td class="num">${x}&nbsp;₽</td>`).join('')}</tr>`).join('');
  const ask = (name) => `<tr><th scope="row">${name}</th><td class="ask" colspan="3">по запросу</td></tr>`;
  return `<div class="table-wrap"><table class="price"><caption class="sr-only">Цены на дезинсекцию квартир</caption><thead><tr>${head}</tr></thead><tbody>${rows}${ask('Дом, коттедж')}${ask('Офис, магазин, общепит, склад')}</tbody></table></div>`;
}

export function relatedLinks(items) {
  return `<div class="related">${items.map(([h, l]) => `<a href="${h}">${l}${icon('arrow')}</a>`).join('')}</div>`;
}

/* ---------- JSON-LD ---------- */
export function ldLocalBusiness() {
  const o = {
    '@context': 'https://schema.org', '@type': 'LocalBusiness',
    '@id': `${site()}/#business`, name: C.brand, url: `${site()}/`,
    telephone: C.phones.map((p) => p.tel), image: `${site()}${C.ogImage}`,
    areaServed: [{ '@type': 'City', name: C.city }, { '@type': 'AdministrativeArea', name: C.region }],
    address: { '@type': 'PostalAddress', addressLocality: C.city, addressRegion: C.region, addressCountry: 'RU' },
    sameAs: [C.telegram, C.max]
  };
  if (C.address) o.address.streetAddress = C.address;
  if (C.email) o.email = C.email;
  return o;
}
export const ldFaq = (items) => ({
  '@context': 'https://schema.org', '@type': 'FAQPage',
  mainEntity: items.map((f) => ({ '@type': 'Question', name: f.q, acceptedAnswer: { '@type': 'Answer', text: f.a.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim() } }))
});
export const ldBreadcrumbs = (items) => ({
  '@context': 'https://schema.org', '@type': 'BreadcrumbList',
  itemListElement: items.map((c, i) => ({ '@type': 'ListItem', position: i + 1, name: c.name, item: url(c.href) }))
});
export const ldService = (name, description, path) => ({
  '@context': 'https://schema.org', '@type': 'Service', name, description, serviceType: name,
  provider: { '@id': `${site()}/#business` },
  areaServed: [{ '@type': 'City', name: C.city }, { '@type': 'AdministrativeArea', name: C.region }],
  url: url(path)
});

/* ---------- Каркас страницы ---------- */
export function page({ path, title, description, body, ld = [], preload = '', noindex = false, ogImage }) {
  const canonical = url(path === '/404.html' ? '/' : path);
  const img = `${site()}${ogImage || C.ogImage}`;
  const lds = ld.map((o) => `<script type="application/ld+json">${JSON.stringify(o)}</script>`).join('\n');
  const cfg = JSON.stringify({ ym: C.metrikaId || '', endpoint: C.formEndpoint, tel: C.phones[0].display, telHref: C.phones[0].tel });
  const html = `<!doctype html>
<html lang="ru">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>${esc(title)}</title>
<meta name="description" content="${esc(description)}">
${noindex || C.noindex ? '<meta name="robots" content="noindex,follow">' : ''}
<link rel="canonical" href="${canonical}">
<meta name="theme-color" content="#0A2A6E">
<meta property="og:type" content="website">
<meta property="og:locale" content="ru_RU">
<meta property="og:site_name" content="${esc(C.brand)}">
<meta property="og:title" content="${esc(title)}">
<meta property="og:description" content="${esc(description)}">
<meta property="og:url" content="${canonical}">
<meta property="og:image" content="${img}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="${esc(title)}">
<meta name="twitter:description" content="${esc(description)}">
<meta name="twitter:image" content="${img}">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">
<link rel="preload" href="/fonts/golos-cyrillic.woff2" as="font" type="font/woff2" crossorigin>
${preload || heroPreload(path)}
<script>document.documentElement.className+=' js'</script>
<style>${CRITICAL}</style>
<link rel="stylesheet" href="/styles.css" media="print" onload="this.media='all'">
<noscript><link rel="stylesheet" href="/styles.css"></noscript>
${lds}
</head>
<body>
${sprite()}
${header(path)}
<main id="main">
${body}
</main>
${footer()}
${bottomBar()}
${modals()}
${cookieBanner()}
<script>window.SANITAR=${cfg};</script>
<script src="/script.js" defer></script>
</body>
</html>
`;
  return subst(html);
}
