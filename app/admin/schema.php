<?php
// Описание форм админки: какие поля есть у каждого раздела и в какое место данных они пишут.
// bind = "файл#путь.в.json". Сохранять можно только поля, описанные здесь.
declare(strict_types=1);

function f(string $type, string $bind, string $label, string $hint = '', array $o = []): array
{
    return ['type' => $type, 'bind' => $bind, 'label' => $label, 'hint' => $hint] + $o;
}
function k(string $type, string $key, string $label, string $hint = '', array $o = []): array
{
    return ['type' => $type, 'key' => $key, 'label' => $label, 'hint' => $hint] + $o;
}
function head(string $title, string $note = ''): array { return ['type' => 'head', 'label' => $title, 'hint' => $note]; }

// Подсказка к полям с разметкой
const MD_HINT = 'Пустая строка — новый абзац. «## Заголовок» — подзаголовок. Строки, начинающиеся с «- », — список. **жирный текст**. Ссылка: [текст](/адрес/). @@PHONE1@@ и @@PHONE2@@ — телефон ссылкой.';
const TOKENS_HINT = 'Можно вставлять @@PRICE_FROM@@ (цена квартиры), @@PRICE_LAND@@ (цена сотки), @@RATING@@, @@REVIEWS@@, @@TEL1@@, @@TEL2@@ — подставятся сами.';

function meta_fields(string $prefix): array
{
    return [
        head('Для поисковиков', 'Заголовок и описание, которые видны в результатах Яндекса и Google. На самой странице не показываются. ' . TOKENS_HINT),
        f('text', $prefix . 'metaTitle', 'Заголовок во вкладке и в поиске', 'Оптимально до 60 символов', ['counter' => 60, 'required' => true]),
        f('textarea', $prefix . 'metaDescription', 'Описание в поиске', 'Оптимально 120–160 символов', ['counter' => 160, 'rows' => 3, 'required' => true]),
    ];
}
function faq_item(): array { return [k('text', 'q', 'Вопрос'), k('textarea', 'a', 'Ответ', MD_HINT, ['rows' => 3])]; }
function page_hero(string $p, string $heroKey): array
{
    return [
        head('Первый экран'),
        f('herobg', "structure.json#hero.$heroKey", 'Фон первого экрана', 'Готовый фон или фото из раздела «Фото»'),
        f('textarea', $p . 'h1', 'Главный заголовок (H1)', '', ['rows' => 2, 'required' => true]),
        f('textarea', $p . 'lead', 'Текст под заголовком', '', ['rows' => 3]),
    ];
}

function admin_sections(): array
{
    $S = [];

    $h = 'site.json#home.';
    $S['home'] = ['title' => 'Главная', 'group' => 'Страницы', 'url' => '/', 'fields' => array_merge([
        head('Первый экран'),
        f('herobg', 'structure.json#hero.home', 'Фон первого экрана'),
        f('text', $h . 'eyebrow', 'Плашка над заголовком'),
        f('text', $h . 'h1', 'Главный заголовок (H1)', 'Слова между ** ** выделяются зелёным', ['required' => true]),
        f('textarea', $h . 'lead', 'Текст под заголовком', '', ['rows' => 3]),
        f('items', $h . 'facts', 'Плашки под кнопками', TOKENS_HINT, ['item' => [k('icon', 'icon', 'Иконка'), k('text', 't', 'Крупно'), k('text', 's', 'Мелко')], 'itemName' => 'Плашка']),
        head('Услуги', 'Карточки — это услуги, отмеченные «Показывать в меню» (раздел «Услуги»)'),
        f('text', $h . 'servicesTitle', 'Заголовок'),
        f('textarea', $h . 'servicesText', 'Текст под заголовком', '', ['rows' => 2]),
        head('С какими вредителями работаем'),
        f('text', $h . 'pestsTitle', 'Заголовок'),
        f('textarea', $h . 'pestsText', 'Текст под заголовком', '', ['rows' => 2]),
        f('items', $h . 'pests', 'Плитки', 'Ссылка — адрес страницы, например /unichtozhenie-tarakanov/ или /dezinsekciya/#pests', ['item' => [k('icon', 'icon', 'Иконка'), k('text', 'name', 'Название'), k('link', 'href', 'Ссылка')], 'itemName' => 'Плитка']),
        head('Цены', 'Сама таблица цен — в разделе «Цены»'),
        f('text', $h . 'priceTitle', 'Заголовок'),
        f('textarea', $h . 'priceText', 'Текст под заголовком', '', ['rows' => 2]),
        f('textarea', $h . 'priceNote', 'Примечание под таблицей', '', ['rows' => 2]),
        f('textarea', $h . 'priceLandText', 'Карточка «за сотку»', '', ['rows' => 2]),
        f('textarea', $h . 'priceAskText', 'Карточка «Расчёт по заявке»', '', ['rows' => 2]),
        head('Как мы работаем', 'Сами шаги — в разделе «Общие блоки»'),
        f('text', $h . 'stepsTitle', 'Заголовок'),
        head('Видео работ', 'Список роликов — в разделе «Видео»'),
        f('text', $h . 'videosTitle', 'Заголовок'),
        f('textarea', $h . 'videosText', 'Текст под заголовком', '', ['rows' => 2]),
        head('Методы обработки', 'Сами методы — в разделе «Общие блоки»'),
        f('text', $h . 'methodsTitle', 'Заголовок'),
        f('textarea', $h . 'methodsText', 'Текст под заголовком', '', ['rows' => 2]),
        head('Гарантия'),
        f('text', $h . 'guarTitle', 'Заголовок'),
        f('items', $h . 'guarBig', 'Крупные цифры', '', ['item' => [k('text', 'n', 'Цифра'), k('text', 't', 'Подпись')], 'itemName' => 'Цифра']),
        f('text', $h . 'guarListTitle', 'Заголовок списка'),
        f('list', $h . 'guarList', 'Пункты списка'),
        head('Документы', 'Сами документы — в разделе «Документы»'),
        f('text', $h . 'docsTitle', 'Заголовок'),
        f('textarea', $h . 'docsText', 'Текст под заголовком', '', ['rows' => 2]),
        head('Отзывы'),
        f('text', $h . 'reviewsTitle', 'Заголовок'),
        f('list', $h . 'reviews', 'Какие отзывы показать', 'Имена авторов из раздела «Отзывы», в нужном порядке'),
        head('Кому мы помогаем'),
        f('text', $h . 'clientsTitle', 'Заголовок'),
        f('items', $h . 'clients', 'Карточки', '', ['item' => [k('icon', 'icon', 'Иконка'), k('text', 'name', 'Заголовок'), k('textarea', 'text', 'Текст', '', ['rows' => 2])], 'itemName' => 'Карточка']),
        head('География', 'Список городов — в разделе «Компания и контакты»'),
        f('text', $h . 'geoTitle', 'Заголовок'),
        f('textarea', $h . 'geoText', 'Текст под заголовком', '', ['rows' => 2]),
        head('Частые вопросы'),
        f('items', $h . 'faq', 'Вопросы', '', ['item' => faq_item(), 'itemName' => 'Вопрос', 'collapsed' => true]),
        head('Заявка внизу страницы'),
        f('text', $h . 'formTitle', 'Заголовок'),
    ], meta_fields($h))];

    $c = 'site.json#ceny.';
    $S['ceny'] = ['title' => 'Страница «Цены»', 'group' => 'Страницы', 'url' => '/ceny/', 'fields' => array_merge(page_hero($c, 'ceny'), [
        head('Квартиры', 'Таблица цен — в разделе «Цены»'),
        f('text', $c . 'flatTitle', 'Заголовок'),
        f('textarea', $c . 'flatText', 'Текст под заголовком', '', ['rows' => 2]),
        f('textarea', $c . 'flatNote', 'Примечание под таблицей', '', ['rows' => 2]),
        head('Участки'),
        f('text', $c . 'landTitle', 'Заголовок'),
        f('textarea', $c . 'landText', 'Карточка «за сотку»', '', ['rows' => 2]),
        f('text', $c . 'treesTitle', 'Вторая карточка: заголовок'),
        f('textarea', $c . 'treesText', 'Вторая карточка: текст', '', ['rows' => 2]),
        head('Индивидуальный расчёт', 'Плитки — услуги из меню, кроме дезинсекции'),
        f('text', $c . 'indTitle', 'Заголовок'),
        f('textarea', $c . 'indText', 'Текст под заголовком', '', ['rows' => 3]),
        f('textarea', $c . 'callout', 'Выделенный блок', '**жирный текст**', ['rows' => 3]),
    ], meta_fields($c))];

    $d = 'site.json#dokumenty.';
    $S['dokumenty'] = ['title' => 'Документы', 'group' => 'Страницы', 'url' => '/dokumenty/', 'fields' => array_merge(page_hero($d, 'dokumenty'), [
        head('Документы на препараты', 'Показываются на этой странице и на главной. Сканы загружайте в «Фото» → папка «Документы»; подпись к фото — описание документа для поисковиков.'),
        f('items', 'site.json#common.docs', 'Документы', '', ['item' => [k('photo', 'photo', 'Скан', '', ['required' => true]), k('textarea', 'title', 'Название под сканом', '', ['rows' => 2])], 'itemName' => 'Документ']),
        head('Текст на странице'),
        f('text', $d . 'orgTitle', 'Заголовок 1'),
        f('textarea', $d . 'orgText', 'Текст 1', '', ['rows' => 3]),
        f('text', $d . 'companyTitle', 'Заголовок 2'),
        f('textarea', $d . 'licenseNote', 'Лицензии и разрешения компании', 'Пусто — «Заголовок 2» и этот текст на сайте не показываются', ['rows' => 2]),
    ], meta_fields($d))];

    $o = 'site.json#otzyvy.';
    $S['otzyvy'] = ['title' => 'Страница «Отзывы»', 'group' => 'Страницы', 'url' => '/otzyvy/', 'fields' => array_merge(page_hero($o, 'otzyvy'), [
        head('Отзывы', 'Сами отзывы — в разделе «Отзывы»; рейтинг — в «Компания и контакты»'),
        f('textarea', $o . 'note', 'Примечание под отзывами', '', ['rows' => 2]),
    ], meta_fields($o))];

    $k = 'site.json#kontakty.';
    $S['kontakty'] = ['title' => 'Контакты', 'group' => 'Страницы', 'url' => '/kontakty/', 'fields' => array_merge(page_hero($k, 'kontakty'), [
        head('Телефоны, адрес, почта', 'Правятся в разделе «Компания и контакты» и меняются сразу на всём сайте'),
    ], meta_fields($k))];

    $co = 'company.json#';
    $S['company'] = ['title' => 'Компания и контакты', 'group' => 'Общее', 'url' => '/kontakty/', 'fields' => [
        head('Контакты', 'Показываются в шапке, подвале, формах, на странице «Контакты» и в политике'),
        f('items', $co . 'phones', 'Телефоны', 'Первый — главный: он в шапке и на кнопке «Позвонить». На сайте показываются два первых.', ['item' => [k('text', 'display', 'Как показывать', 'Например: 8 927 260-73-67')], 'itemName' => 'Телефон']),
        f('email', $co . 'email', 'Электронная почта', 'Сюда же приходят заявки, если в «Настройках» не указана другая почта'),
        f('url', $co . 'telegram', 'Ссылка на Telegram-канал', 'В подвале и на странице «Контакты». Например: https://t.me/имя', ['required' => true]),
        f('url', $co . 'telegramChat', 'Telegram для кнопки в шапке', 'Личный чат: https://t.me/tihiigorodsamara. Пусто — кнопка ведёт в канал'),
        f('url', $co . 'max', 'Ссылка на группу в MAX', 'В подвале и на странице «Контакты». Начинается с https://', ['required' => true]),
        f('url', $co . 'maxChat', 'MAX для кнопки в шапке', 'Личный чат, например https://max.ru/u/… Пусто — кнопка ведёт в группу'),
        f('text', $co . 'address', 'Адрес офиса', 'Пусто — на сайте жёлтая пометка «заполнить»'),
        f('number', $co . 'geo.lat', 'Широта офиса на карте', 'Например: 53.194517', ['float' => true]),
        f('number', $co . 'geo.lon', 'Долгота офиса на карте', 'Например: 50.294742', ['float' => true]),
        f('text', $co . 'hours', 'Режим работы'),
        f('list', $co . 'cities', 'Города на главной (блок «География»)'),
        head('Рейтинг на Яндексе'),
        f('url', $co . 'yandexReviewsUrl', 'Ссылка на отзывы на Яндексе', 'Пусто — кнопка «Все отзывы на Яндексе» не показывается'),
        f('text', $co . 'rating.value', 'Оценка', 'Например: 5,0'),
        f('number', $co . 'rating.count', 'Количество отзывов'),
        f('text', $co . 'rating.asOf', 'По состоянию на', 'Например: март 2026'),
        head('Реквизиты', 'Показываются только в политике конфиденциальности (в подвале — просто «Дезцентр SANITAR»). Пусто — жёлтая пометка «заполнить».'),
        f('text', $co . 'legal.name', 'Наименование (ИП или ООО)'),
        f('text', $co . 'legal.inn', 'ИНН'),
        f('text', $co . 'legal.ogrn', 'ОГРН / ОГРНИП'),
        f('text', $co . 'legalDate', 'Дата редакции политики конфиденциальности', 'В формате ГГГГ-ММ-ДД. Меняйте, когда меняется текст политики или реквизиты', ['required' => true]),
        head('Яндекс Метрика'),
        f('text', $co . 'metrikaId', 'Номер счётчика', 'Пусто — Метрика выключена'),
    ]];

    $S['prices'] = ['title' => 'Цены', 'group' => 'Общее', 'url' => '/ceny/', 'fields' => [
        head('Главные цены', 'Подставляются во все тексты сайта вместо @@PRICE_FROM@@ и @@PRICE_LAND@@'),
        f('text', 'prices.json#from', 'Квартира от, ₽', 'Например: 3 700', ['required' => true]),
        f('text', 'prices.json#land', 'Участок от, ₽ за сотку', 'Например: 400', ['required' => true]),
        head('Таблица цен на дезинсекцию квартир', 'Показывается на главной, на странице «Цены» и на страницах услуг, где включена таблица'),
        f('list', 'prices.json#head', 'Заголовки столбцов', 'Четыре: первый — «Тип объекта», дальше — три колонки цен'),
        f('items', 'prices.json#rows', 'Строки', 'Цены — числом, знак ₽ добавится сам', ['item' => [k('text', 'name', 'Тип объекта'), k('text', 'v1', 'Колонка 1'), k('text', 'v2', 'Колонка 2'), k('text', 'v3', 'Колонка 3')], 'itemName' => 'Строка']),
    ]];

    $S['reviews'] = ['title' => 'Отзывы', 'group' => 'Общее', 'url' => '/otzyvy/', 'fields' => [
        head('Отзывы клиентов', 'Все отзывы — на странице «Отзывы». На страницы услуг попадают отзывы с подходящими метками. Какие показать на главной — в разделе «Главная».'),
        f('items', 'reviews.json#items', 'Отзывы', '', ['itemName' => 'Отзыв', 'collapsed' => true, 'item' => [
            k('text', 'name', 'Автор'),
            k('text', 'date', 'Дата', 'Например: 03 февраля 2026'),
            k('textarea', 'text', 'Текст отзыва', '', ['rows' => 5]),
            k('list', 'tags', 'Метки', 'Для подбора на страницы услуг: flat, tarakan, tsj, biz, land, komar, disinf, general'),
        ]]),
    ]];

    $cm = 'site.json#common.';
    $S['common'] = ['title' => 'Общие блоки', 'group' => 'Общее', 'url' => '/', 'fields' => [
        head('Как мы работаем', 'Шаги на главной'),
        f('items', $cm . 'steps', 'Шаги', '', ['item' => [k('text', 't', 'Заголовок'), k('textarea', 'd', 'Текст', '', ['rows' => 2])], 'itemName' => 'Шаг']),
        head('Методы обработки', 'На главной и на странице услуги, где включено «Показать методы»'),
        f('items', $cm . 'methods', 'Методы', '', ['item' => [k('icon', 'icon', 'Иконка'), k('text', 't', 'Название'), k('textarea', 'd', 'Описание', '', ['rows' => 3]), k('text', 'when', 'Где применяем')], 'itemName' => 'Метод']),
        head('Подвал'),
        f('textarea', $cm . 'footerText', 'Текст под логотипом', '', ['rows' => 2]),
        head('Фоны других страниц'),
        f('herobg', 'structure.json#hero.politika', 'Политика конфиденциальности'),
        f('herobg', 'structure.json#hero.notFound', 'Страница 404 (адрес не найден)'),
    ]];

    $S['videos'] = ['title' => 'Видео', 'group' => 'Общее', 'url' => '/#raboty', 'fields' => [
        head('Видео работ на главной', 'Новые ролики добавляет разработчик (их нужно сжать для сайта). Здесь — подписи, раздел и порядок; ненужный ролик можно удалить из списка.'),
        f('items', 'videos.json#items', 'Ролики', '', ['itemName' => 'Ролик', 'item' => [
            k('text', 'cap', 'Подпись'),
            k('text', 'cat', 'Раздел', 'home — квартиры и дома, biz — коммерция, land — участки'),
            k('text', 'id', 'Номер файла', 'Файл video/work-<номер>.mp4 — лучше не менять'),
        ]]),
        f('items', 'videos.json#cats', 'Кнопки-фильтры над видео', 'Первая кнопка (all) показывает все ролики', ['itemName' => 'Кнопка', 'item' => [k('text', 'id', 'Код раздела'), k('text', 'label', 'Надпись')]]),
    ]];

    return $S;
}

// Форма страницы услуги
function service_fields(): array
{
    $s = 'services/{svc}.json#';
    return array_merge([
        head('Основное'),
        f('bool', $s . 'hidden', 'Скрыть с сайта', 'Скрытая услуга не показывается нигде — удобно, пока страница готовится'),
        f('text', $s . 'name', 'Название', 'В меню, хлебных крошках и карточках', ['required' => true]),
        f('bool', $s . 'menu', 'Показывать в меню, на главной и в подвале', 'Основные направления. Страницы отдельных вредителей обычно без этой галочки'),
        f('service', $s . 'parent', 'Раздел', 'Для страницы вредителя — основная услуга (видно в хлебных крошках). Пусто — самостоятельная услуга'),
        f('service', $s . 'formService', 'Услуга в форме заявки', 'Что будет выбрано в форме внизу страницы (из услуг меню)'),
        head('Карточка на главной', 'Только для услуг меню'),
        f('icon', $s . 'icon', 'Иконка'),
        f('textarea', $s . 'short', 'Короткое описание', '', ['rows' => 2]),
        f('text', $s . 'cardPrice', 'Цена на карточке', 'Например: от @@PRICE_FROM@@ ₽ или «по запросу»'),
        f('text', $s . 'cardPriceNote', 'Подпись к цене', 'Например: квартира 1-комн.'),
        head('Первый экран'),
        f('herobg', $s . 'heroBg', 'Фон первого экрана'),
        f('textarea', $s . 'h1', 'Главный заголовок (H1)', '', ['rows' => 2, 'required' => true]),
        f('textarea', $s . 'lead', 'Текст под заголовком', '', ['rows' => 3]),
        f('text', $s . 'chip', 'Плашка с ценой', TOKENS_HINT),
        head('Основной текст', 'Рядом с ним — форма «Быстрый расчёт»'),
        f('textarea', $s . 'intro', 'Текст', MD_HINT, ['rows' => 14]),
        f('photo', $s . 'introFigure.photo', 'Фото после текста', 'Необязательно'),
        f('text', $s . 'introFigure.caption', 'Подпись к фото'),
        f('bool', $s . 'introFigure.narrow', 'Узкое фото', 'Для вертикальных и почти квадратных фото'),
        head('Второй блок текста', 'На сером фоне. Пусто — блок не показывается'),
        f('textarea', $s . 'how', 'Текст', MD_HINT, ['rows' => 10]),
        f('photo', $s . 'howFigure.photo', 'Фото после текста', 'Необязательно'),
        f('text', $s . 'howFigure.caption', 'Подпись к фото'),
        f('bool', $s . 'howFigure.narrow', 'Узкое фото'),
        f('bool', $s . 'showMethods', 'Показать карточки методов обработки', 'Методы правятся в разделе «Общие блоки»'),
        head('Цена'),
        f('text', $s . 'priceTitle', 'Заголовок', 'Пусто — «Сколько это стоит»'),
        f('bool', $s . 'price.table', 'Показать таблицу цен на квартиры'),
        f('textarea', $s . 'price.tableNote', 'Примечание под таблицей', '', ['rows' => 2]),
        f('bool', $s . 'price.land', 'Показать карточку «от … ₽ за сотку»'),
        f('textarea', $s . 'price.ask', 'Карточка «Стоимость — по запросу»', 'Первое предложение карточки. Пусто — карточка не показывается', ['rows' => 2]),
        f('bool', $s . 'price.askButton', 'Кнопка «Оставить заявку на расчёт»'),
        f('textarea', $s . 'price.note', 'Примечание в конце блока', '', ['rows' => 2]),
        head('Подготовка и гарантия'),
        f('list', $s . 'prep', 'Как подготовиться', '**жирный текст** в начале пункта — например, **Кухня:**'),
        f('list', $s . 'warranty', 'Гарантия и после обработки', '**жирный текст**'),
        head('Отзывы и вопросы'),
        f('list', $s . 'reviewTags', 'Метки отзывов', 'Сначала показываются отзывы с этими метками (метки — в разделе «Отзывы»)'),
        f('items', $s . 'faq', 'Частые вопросы', 'Рекомендуется 3–5 вопросов', ['item' => faq_item(), 'itemName' => 'Вопрос']),
        head('Смотрите также'),
        f('items', $s . 'related', 'Ссылки', 'Ссылки на скрытые и удалённые услуги не показываются', ['item' => [k('link', 'href', 'Адрес', 'Например: /ceny/'), k('text', 'label', 'Текст ссылки')], 'itemName' => 'Ссылка']),
    ], meta_fields($s));
}

// Все поля раздела в плоском виде (для проверки, что сохраняется только разрешённое)
function schema_binds(array $fields): array
{
    $out = [];
    foreach ($fields as $fd) if (!empty($fd['bind'])) $out[$fd['bind']] = $fd;
    return $out;
}
