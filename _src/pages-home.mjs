import { icon } from './icons.mjs';
import { SERVICES, PESTS, CLIENTS, VIDEOS, VIDEO_CATS, REVIEWS, STEPS, METHODS, FAQ_HOME, DOCS, PRICE } from './data.mjs';
import * as L from './layout.mjs';

export function home(C) {
  const hero = VIDEOS[0];
  const rating = `${C.rating.value}`;
  const services = SERVICES.map((s) => `<article class="card svc reveal">
      <span class="svc__ico">${icon(s.icon)}</span>
      <h3><a class="stretch" href="/${s.slug}/">${s.name}</a></h3>
      <p>${s.short}</p>
      <div class="svc__price">${s.price} <small style="font-weight:500;color:var(--muted)">${s.priceNote}</small></div>
      <span class="svc__more">Подробнее ${icon('arrow')}</span></article>`).join('');
  const pests = PESTS.map((p) => `<a class="pest reveal" href="${p.href}">${icon(p.icon)}<span>${p.name}</span></a>`).join('');
  const clients = CLIENTS.map((c) => `<div class="client reveal">${icon(c.icon)}<b>${c.name}</b><span style="color:var(--muted);font-size:.95rem">${c.text}</span></div>`).join('');
  const steps = STEPS.map((s) => `<li class="reveal"><b>${s.t}</b><span>${s.d}</span></li>`).join('');
  const methods = METHODS.map((m) => `<article class="card method reveal"><h3>${icon(m.icon)}${m.t}</h3><p>${m.d}</p><span class="when">${m.when}</span></article>`).join('');
  const chips = VIDEO_CATS.map((c) => `<button type="button" class="chip" data-filter="${c.id}" aria-pressed="${c.id === 'all'}">${c.label}</button>`).join('');
  const vids = VIDEOS.map((v) => L.vidTile(v)).join('');
  const docs = DOCS.map((d) => `<button type="button" class="doc reveal" data-lightbox="${d.img}" data-caption="${L.esc(d.t)}" data-alt="${L.esc(d.alt)}"><img src="${d.img}" width="${d.w}" height="${d.h}" alt="${L.esc(d.alt)}" loading="lazy"><span>${d.t}</span></button>`).join('');
  const homeReviews = ['Максим Хорьяков', 'Maxim M', 'Александр Ермулин', 'Senko', 'Артём Зноев', 'Эля Ярмаркина']
    .map((n) => REVIEWS.find((r) => r.name === n));
  const cities = C.cities.map((c) => `<li class="chip">${icon('pin')}${c}</li>`).join('');
  const p = C.phones;

  const body = `
<section class="hero">
  <div class="container">
  <div class="hero__grid">
    <div>
      <span class="hero__eyebrow">${icon('shield-check')} Самара и Самарская область</span>
      <h1>Уничтожение <span class="hl">насекомых и грызунов</span> в Самаре и области</h1>
      <p class="hero__lead">Дезинсекция, дератизация, дезинфекция и фунгицидная обработка. Гарантия до 6 месяцев. Профессиональные препараты, безопасность для детей и животных при соблюдении правил.</p>
      <div class="hero__cta">
        <button type="button" class="btn btn--lime" data-open-modal>${icon('phone')} Вызвать специалиста</button>
        <a class="btn btn--ghost" href="#ceny">${icon('calc')} Рассчитать стоимость</a>
      </div>
      ${L.leadForm({ variant: 'mini', cta: 'Перезвоните мне' })}
    </div>
    <div class="hero__media">
      ${L.vidTile(hero, { hero: true })}
      <span class="badge badge--a">${icon('shield-check')}<span>Гарантия<small>до 6 месяцев</small></span></span>
      <span class="badge badge--b">${icon('star')}<span>${rating} на Яндексе<small>${C.rating.count} отзывов</small></span></span>
    </div>
  </div>
  <ul class="facts">
    <li>${icon('badge')}<span>${C.experience}<small>Самара и область</small></span></li>
    <li>${icon('star')}<span>${rating} на Яндексе<small>${C.rating.count} отзывов</small></span></li>
    <li>${icon('shield-check')}<span>Гарантия<small>до 6 месяцев</small></span></li>
    <li>${icon('ruble')}<span>Наличные и безнал<small>для физ. и юр. лиц</small></span></li>
  </ul>
  </div>
</section>

<section class="section" id="uslugi"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Услуги</span><h2>Что мы делаем</h2><p>Четыре направления санитарной обработки для квартир, домов, участков и организаций.</p></div>
  <div class="grid services">${services}</div>
</div></section>

<section class="section section--alt" id="vrediteli"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Кого уничтожаем</span><h2>С какими вредителями работаем</h2><p>Выберите вредителя — расскажем, как проходит обработка и что подготовить.</p></div>
  <div class="grid pests">${pests}</div>
</div></section>

<section class="section" id="ceny"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Цены</span><h2>Сколько стоит обработка</h2><p>Тараканы, клопы, блохи, муравьи, комары. Чем больше квартир в заказе, тем ниже цена за квартиру.</p></div>
  <div class="reveal">${L.priceTable()}</div>
  <p class="price-note">Цены в рублях. Итоговая стоимость зависит от метража, вида насекомых и места обработки. Действует система скидок от объёма.</p>
  <div class="price-extra">
    <div class="price-card reveal">${icon('sprout')}<div><b>от ${PRICE.land} ₽ за сотку</b><span>Комплексная обработка участка: клещи, комары, садовые вредители. Минимальный выезд. Дополнительно: муравьи, мухи, осы.</span></div></div>
    <div class="price-card reveal">${icon('max')}<div><b>Расчёт за 5 минут</b><span>Напишите в MAX: местонахождение, метраж и вид насекомого. Дератизация, дезинфекция и фунгицидная обработка рассчитываются индивидуально.</span></div></div>
  </div>
  <div style="margin-top:20px;display:flex;flex-wrap:wrap;gap:12px">
    <a class="btn btn--lime" href="${L.esc(C.max)}" target="_blank" rel="noopener" data-goal="max_click">${icon('max')} Рассчитать в MAX</a>
    <a class="btn btn--ghost" href="/ceny/">Все цены</a>
  </div>
</div></section>

<section class="section section--alt" id="kak-rabotaem"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Как мы работаем</span><h2>От заявки до гарантийного срока</h2></div>
  <ol class="steps">${steps}</ol>
</div></section>

<section class="section" id="raboty"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Видео работ</span><h2>Так выглядит обработка</h2><p>Реальные объекты: квартиры, помещения организаций, участки. Видео запускается по нажатию, без звука.</p></div>
  <div class="chips gal-filter" role="group" aria-label="Фильтр видео">${chips}</div>
  <div class="grid gallery">${vids}</div>
</div></section>

<section class="section section--alt" id="metody"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Методы обработки</span><h2>Чем и как обрабатываем</h2><p>Метод подбираем под объект: специалист объяснит выбор при осмотре.</p></div>
  <div class="grid methods">${methods}</div>
</div></section>

<section class="section" id="garantiya"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Гарантия</span><h2>Гарантия на результат</h2></div>
  <div class="guar">
    <div class="reveal" style="display:grid;gap:14px">
      <div class="guar__big"><b>6</b><span>месяцев гарантии на обработку помещений</span></div>
      <div class="guar__big"><b>1</b><span>месяц гарантии на открытые территории: участки, дворы, дачи</span></div>
    </div>
    <div class="card reveal">
      <h3>Что важно знать</h3>
      <ul class="checklist">
        <li>${icon('check')}<span>Повторную обработку рекомендуем проводить не раньше чем через месяц.</span></li>
        <li>${icon('check')}<span>Препарат в микрокапсулах работает несколько месяцев, поэтому не смывайте обработанные поверхности сразу после обработки.</span></li>
        <li>${icon('check')}<span>Если в гарантийный срок насекомые появились снова, позвоните нам.</span></li>
        <li>${icon('check')}<span>Наличный и безналичный расчёт, документы для организаций.</span></li>
      </ul>
    </div>
  </div>
</div></section>

<section class="section section--alt" id="dokumenty"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Документы</span><h2>Документы на препараты</h2><p>Обработку проводим препаратами, на которые есть декларации и свидетельства. Нажмите на документ, чтобы увеличить.</p></div>
  <div class="grid docs">${docs}</div>
  <p class="price-note"><a href="/dokumenty/">Все документы →</a></p>
</div></section>

<section class="section" id="otzyvy"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Отзывы</span><h2>Что говорят клиенты</h2></div>
  <div class="rating-line reveal"><span class="stars" role="img" aria-label="Оценка 5 из 5">${icon('star').repeat(5)}</span><b>${rating}</b><span>на Яндексе по ${C.rating.count} отзывам (${C.rating.asOf})</span></div>
  <div class="grid reviews">${L.reviewCards(homeReviews)}</div>
  <div style="margin-top:22px;display:flex;flex-wrap:wrap;gap:12px"><a class="btn btn--ghost" href="/otzyvy/">Все отзывы</a>${L.yandexLink()}</div>
</div></section>

<section class="section section--alt" id="klienty"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Для кого</span><h2>Кому мы помогаем</h2></div>
  <div class="grid clients">${clients}</div>
</div></section>

<section class="section" id="geografiya"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">География</span><h2>Работаем в Самаре и Самарской области</h2><p>Выезжаем в города и районы области. Если вашего населённого пункта нет в списке, позвоните — уточним возможность выезда.</p></div>
  <ul class="chips reveal">${cities}</ul>
</div></section>

<section class="section section--alt" id="faq"><div class="container">
  <div class="section__head reveal"><span class="eyebrow">Вопросы</span><h2>Частые вопросы</h2></div>
  ${L.faqBlock(FAQ_HOME)}
</div></section>

${L.formSection({ title: 'Оставьте заявку — перезвоним и рассчитаем стоимость' })}
`;

  return {
    path: '/',
    title: 'Дезинсекция и дератизация в Самаре | ДЕЗЦЕНТР SANITAR',
    description: 'Уничтожение тараканов, клопов, клещей, крыс и мышей в Самаре и области. Квартира от @@PRICE_FROM@@ ₽, участок от @@PRICE_LAND@@ ₽/сотка. Гарантия до 6 месяцев.',
    preload: `<link rel="preload" as="image" href="/video/work-${hero.id}.webp" fetchpriority="high">`,
    body,
    ld: [L.ldLocalBusiness(), L.ldFaq(FAQ_HOME)]
  };
}
