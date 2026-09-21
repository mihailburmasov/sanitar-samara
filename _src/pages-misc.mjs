import { icon } from './icons.mjs';
import { PRICE, REVIEWS, DOCS, SERVICES } from './data.mjs';
import * as L from './layout.mjs';

const hero = (title, lead, crumbItems) => `<section class="page-hero on-dark">${L.heroBg(crumbItems[crumbItems.length - 1].href)}<div class="container">${L.crumbs(crumbItems, true)}<h1>${title}</h1>${lead ? `<p class="lead">${lead}</p>` : ''}</div></section>`;
const crumb = (name, href) => [{ name: 'Главная', href: '/' }, { name, href }];

export function ceny(C) {
  const items = crumb('Цены', '/ceny/');
  const body = `${hero('Цены на дезинсекцию, дератизацию и дезинфекцию в Самаре', 'Прозрачные цены на обработку квартир и участков. Для остальных объектов рассчитываем стоимость по заявке.', items)}
<section class="section"><div class="container">
  <div class="section__head"><span class="eyebrow">Квартиры</span><h2>Дезинсекция квартир</h2><p>Тараканы, клопы, муравьи, блохи, комары. Цена за квартиру зависит от количества квартир в заказе.</p></div>
  ${L.priceTable()}
  <p class="price-note">Цены в рублях. Итоговая стоимость зависит от метража, вида насекомых и места обработки. Действует система скидок от объёма. Групповые заказы: ТСЖ, УК, соседи, застройщики.</p>
</div></section>
<section class="section section--alt"><div class="container">
  <div class="section__head"><span class="eyebrow">Участки</span><h2>Обработка участков и дач</h2></div>
  <div class="price-extra"><div class="price-card">${icon('sprout')}<div><b>от ${PRICE.land} ₽ за сотку</b><span>Комплексная обработка: клещи, комары, садовые вредители. Минимальный выезд. Дополнительно: муравьи, мухи, осы.</span></div></div>
  <div class="price-card">${icon('leaf')}<div><b>Лечение деревьев и кустарников</b><span>Стоимость — по запросу, рассчитаем по заявке.</span></div></div></div>
</div></section>
<section class="section"><div class="container">
  <div class="section__head"><span class="eyebrow">Индивидуальный расчёт</span><h2>Дератизация, дезинфекция, коммерческие объекты</h2><p>Для домов, офисов, магазинов, общепита, складов, а также для дератизации, дезинфекции и обработки от плесени цена зависит от объекта. Рассчитаем стоимость по вашей заявке.</p></div>
  <div class="grid" style="grid-template-columns:repeat(auto-fit,minmax(240px,1fr))">${SERVICES.filter((s) => s.slug !== 'dezinsekciya').map((s) => `<a class="pest" href="/${s.slug}/" style="align-items:flex-start;text-align:left">${icon(s.icon)}<span>${s.name}</span><small>по запросу</small></a>`).join('')}</div>
  <div style="margin-top:22px;display:flex;flex-wrap:wrap;gap:12px"><a class="btn btn--lime" href="#zayavka">${icon('clipboard')} Оставить заявку на расчёт</a><button type="button" class="btn btn--ghost" data-open-modal>Вызвать специалиста</button></div>
  <div class="callout" style="margin-top:22px"><p><b>Как получить расчёт.</b> Оставьте заявку в форме ниже и укажите три вещи: местонахождение, метраж и вид насекомого или грызуна. Мы рассчитаем стоимость и предложим время выезда.</p></div>
</div></section>
${L.formSection({ alt: true })}`;
  return { path: '/ceny/', title: 'Цены на дезинсекцию в Самаре — от @@PRICE_FROM@@ ₽ | SANITAR', description: 'Цены на обработку квартир от @@PRICE_FROM@@ ₽ и участков от @@PRICE_LAND@@ ₽ за сотку в Самаре. Групповые скидки для соседей и ТСЖ. Расчёт по заявке.', body, ld: [L.ldBreadcrumbs(items)] };
}

export function dokumenty(C) {
  const items = crumb('Документы', '/dokumenty/');
  const docs = DOCS.map((d) => `<button type="button" class="doc" data-lightbox="${d.img}" data-caption="${L.esc(d.t)}" data-alt="${L.esc(d.alt)}"><img src="${d.img}" width="${d.w}" height="${d.h}" alt="${L.esc(d.alt)}" loading="lazy"><span>${d.t}</span></button>`).join('');
  const body = `${hero('Документы на препараты и услуги', 'Мы работаем препаратами, на которые оформлены декларации и свидетельства. Нажмите на документ, чтобы рассмотреть его.', items)}
<section class="section"><div class="container">
  <div class="grid docs" style="max-width:720px">${docs}</div>
  <div class="prose" style="margin-top:30px">
    <h2>Документы для организаций</h2>
    <p>Для организаций (общепит, торговля, склады, транспорт, медицинские и социальные учреждения) мы предоставляем сопроводительную документацию для контролирующих органов. Работаем по наличному и безналичному расчёту.</p>
    <h2>Документы компании</h2>
    <p>Лицензии и разрешения компании: ${L.val('СКАН_ЛИЦЕНЗИИ_КОМПАНИИ', C.licenseNote)}</p>
  </div>
</div></section>
${L.formSection({ alt: true })}`;
  return { path: '/dokumenty/', title: 'Документы и сертификаты на препараты | ДЕЗЦЕНТР SANITAR', description: 'Декларации и свидетельства на препараты, которыми работает ДЕЗЦЕНТР SANITAR в Самаре. Документы для организаций и контролирующих органов.', body, ld: [L.ldBreadcrumbs(items)] };
}

export function otzyvy(C) {
  const items = crumb('Отзывы', '/otzyvy/');
  const body = `${hero('Отзывы клиентов', 'Реальные отзывы о нашей работе с Яндекса: квартиры, участки, ТСЖ и организации.', items)}
<section class="section"><div class="container">
  <div class="rating-line"><span class="stars" role="img" aria-label="Оценка 5 из 5">${icon('star').repeat(5)}</span><b>${C.rating.value}</b><span>на Яндексе по ${C.rating.count} отзывам (${C.rating.asOf})</span></div>
  <div class="grid reviews">${L.reviewCards(REVIEWS)}</div>
  <p class="price-note">Отзывы приведены дословно, с сохранением авторской пунктуации. Источник — карточка компании на Яндексе.</p>
  <div style="margin-top:18px">${L.yandexLink('Читать все отзывы на Яндексе')}</div>
</div></section>
${L.formSection({ alt: true })}`;
  return { path: '/otzyvy/', title: 'Отзывы о ДЕЗЦЕНТР SANITAR в Самаре | Яндекс', description: `Отзывы клиентов о дезинсекции, дезинфекции и обработке участков в Самаре: квартиры, ТСЖ, организации. Оценка ${C.rating.value} на Яндексе.`, body, ld: [L.ldBreadcrumbs(items)] };
}

export function kontakty(C) {
  const items = crumb('Контакты', '/kontakty/');
  const p = C.phones;
  const map = C.address
    ? `<a class="btn btn--ghost" href="https://yandex.ru/maps/?text=${encodeURIComponent(C.city + ', ' + C.address)}" target="_blank" rel="noopener">${icon('pin')} Открыть на Яндекс Картах</a>`
    : `<p>Карта: ${L.val('КАРТА_АДРЕС', '')}</p>`;
  const body = `${hero('Контакты ДЕЗЦЕНТР SANITAR', 'Оставьте заявку в форме или позвоните: рассчитаем стоимость и согласуем время выезда.', items)}
<section class="section"><div class="container two-col">
  <div>
    <div class="contact-card">
      ${L.phoneLink(p[0], '', `${icon('phone')}<span>${L.esc(p[0].display)}<small>Позвонить</small></span>`)}
      ${L.phoneLink(p[1], '', `${icon('phone')}<span>${L.esc(p[1].display)}<small>Позвонить</small></span>`)}
    </div>
    <div class="prose" style="margin-top:24px">
      <p><b>Адрес:</b> ${L.val('АДРЕС', C.address)}</p>
      <p><b>Режим работы:</b> ${L.val('ЧАСЫ_РАБОТЫ', C.hours)}</p>
      <p><b>E-mail:</b> ${L.val('EMAIL', C.email)}</p>
      <p><b>Территория работы:</b> Самара и Самарская область.</p>
    </div>
    <div class="map-box">${map}</div>
  </div>
  <div>${L.leadForm({ variant: 'full' })}</div>
</div></section>`;
  return { path: '/kontakty/', title: 'Контакты ДЕЗЦЕНТР SANITAR — Самара | Телефоны, заявка', description: `Телефоны ${C.phones[0].display} и ${C.phones[1].display} и форма заявки. Вызов специалиста по дезинсекции и дератизации в Самаре и области.`, body, ld: [L.ldBreadcrumbs(items)] };
}

export function politika(C) {
  const items = crumb('Политика конфиденциальности', '/politika-konfidencialnosti/');
  const today = new Date().toLocaleDateString('ru-RU', { day: '2-digit', month: 'long', year: 'numeric' });
  const op = `${L.val('ЮР_НАЗВАНИЕ', C.legal.name)} (ИНН ${L.val('ИНН', C.legal.inn)}, ОГРН ${L.val('ОГРН', C.legal.ogrn)}), адрес: ${L.val('АДРЕС', C.address)}`;
  const mail = L.val('EMAIL', C.email);
  const body = `${hero('Политика конфиденциальности', `Редакция от ${today}`, items)}
<section class="section"><div class="container"><div class="prose">
<h2>1. Общие положения</h2>
<p>Настоящая политика описывает, как сайт «${L.esc(C.brand)}» обрабатывает персональные данные посетителей в соответствии с Федеральным законом от 27.07.2006 № 152-ФЗ «О персональных данных». Оператор персональных данных: ${op}. Контакты для обращений: телефоны ${L.esc(C.phones[0].display)}, ${L.esc(C.phones[1].display)}, e-mail ${mail}.</p>
<h2>2. Какие данные мы собираем</h2>
<p>Через формы на сайте вы можете передать: имя, номер телефона, выбранную услугу, тип объекта, адрес или район, комментарий и удобное время звонка. Дополнительно автоматически могут обрабатываться технические данные: IP-адрес, тип браузера и устройства, посещённые страницы, данные файлов cookie.</p>
<h2>3. Цели обработки</h2>
<ul><li>связаться с вами, ответить на заявку и рассчитать стоимость услуги;</li><li>заключить и исполнить договор на оказание услуг;</li><li>обеспечить работу сайта и улучшать его${C.metrikaId ? ' (в том числе с помощью Яндекс.Метрики)' : ''}.</li></ul>
<h2>4. Правовое основание</h2>
<p>Обработка осуществляется на основании вашего согласия, которое вы даёте, отметив соответствующий флажок в форме и нажав кнопку отправки, а также для заключения и исполнения договора.</p>
<h2>5. Срок хранения</h2>
<p>Данные хранятся не дольше, чем этого требуют цели обработки, или до отзыва вами согласия. Заявки, не приведшие к заключению договора, удаляются по достижении цели обработки.</p>
<h2>6. Передача данных третьим лицам</h2>
<p>Мы не продаём и не передаём ваши данные третьим лицам в маркетинговых целях. Заявка отправляется на электронную почту оператора и дублируется в мессенджер Telegram для оперативной обработки. Данные могут обрабатываться хостинг-провайдером сайта${C.metrikaId ? ' и сервисом Яндекс.Метрика (ООО «Яндекс»)' : ''} в объёме, необходимом для предоставления сервиса.</p>
<h2>7. Ваши права</h2>
<p>Вы вправе получить сведения об обработке ваших данных, требовать их уточнения, блокирования или удаления, а также отозвать согласие. Для этого направьте обращение на e-mail ${mail} или позвоните по указанным телефонам. Мы ответим в сроки, установленные законом.</p>
<h2>8. Защита данных</h2>
<p>Мы принимаем организационные и технические меры для защиты данных от неправомерного доступа, изменения и удаления. Передача данных между вашим браузером и сайтом осуществляется по защищённому соединению (HTTPS).</p>
<h2 id="cookie-policy">9. Файлы cookie</h2>
<p>${C.metrikaId ? 'Сайт использует файлы cookie для корректной работы и сбора статистики посещаемости с помощью Яндекс.Метрики (включая вебвизор). Метрика запускается только после вашего согласия в баннере.' : 'Сайт использует файлы cookie для корректной работы. Сервисы веб-аналитики на сайте сейчас не подключены.'} Вы можете отключить cookie в настройках браузера, но некоторые функции сайта могут работать некорректно.</p>
<h2 id="soglasie">10. Согласие на обработку персональных данных</h2>
<p>Отправляя форму на сайте, я свободно, своей волей и в своём интересе даю ${L.val('ЮР_НАЗВАНИЕ', C.legal.name)} согласие на обработку моих персональных данных (имя, номер телефона, адрес или район, тип объекта, комментарий, удобное время звонка) в целях обратной связи, расчёта стоимости и оказания услуг, в том числе на их передачу в мессенджер Telegram для оперативной обработки заявки. Согласие действует до достижения целей обработки или до его отзыва. Согласие можно отозвать, направив обращение на ${mail}.</p>
<h2>11. Изменение политики</h2>
<p>Мы можем обновлять политику. Актуальная редакция всегда размещена на этой странице.</p>
</div></div></section>`;
  return { path: '/politika-konfidencialnosti/', title: 'Политика конфиденциальности | ДЕЗЦЕНТР SANITAR', description: 'Политика обработки персональных данных сайта ДЕЗЦЕНТР SANITAR: какие данные мы собираем, как используем и как защищаем.', body, ld: [L.ldBreadcrumbs(items)] };
}

export function notFound(C) {
  const body = `<section class="page-hero on-dark">${L.heroBg('/404.html')}<div class="container">
  <b class="err-code">404</b><h1>Страница не найдена</h1>
  <p class="lead">Возможно, адрес изменился или введён с ошибкой. Перейдите на главную или выберите услугу.</p>
  <div class="page-hero__cta">
    <a class="btn btn--lime" href="/">На главную</a>
    ${SERVICES.map((s) => `<a class="btn btn--ghost" href="/${s.slug}/">${s.name}</a>`).join('')}
  </div>
  <p style="margin:18px 0 0">Или позвоните: ${L.phoneLink(C.phones[0], '', L.esc(C.phones[0].display))}</p>
</div></section>`;
  return { path: '/404.html', title: 'Страница не найдена | ДЕЗЦЕНТР SANITAR', description: 'Страница не найдена. Перейдите на главную страницу или выберите услугу.', body, noindex: true, ld: [] };
}
