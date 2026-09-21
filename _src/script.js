/* ДЕЗЦЕНТР SANITAR — клиентский скрипт (без зависимостей) */
(function () {
  'use strict';
  var CFG = window.SANITAR || {};
  var $ = function (s, r) { return (r || document).querySelector(s); };
  var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
  var store = {
    get: function (k) { try { return localStorage.getItem(k); } catch (e) { return null; } },
    set: function (k, v) { try { localStorage.setItem(k, v); } catch (e) { /* приватный режим */ } }
  };

  /* ---- Яндекс.Метрика: цели работают только если она загружена (после согласия) ---- */
  var ymLoaded = false;
  function goal(name) {
    if (ymLoaded && window.ym && CFG.ym) { try { window.ym(Number(CFG.ym), 'reachGoal', name); } catch (e) { /* noop */ } }
  }
  function loadMetrika() {
    if (ymLoaded || !CFG.ym) return;
    ymLoaded = true;
    (function (m, e, t, r, i, k, a) {
      m[i] = m[i] || function () { (m[i].a = m[i].a || []).push(arguments); };
      m[i].l = 1 * new Date();
      k = e.createElement(t); a = e.getElementsByTagName(t)[0]; k.async = 1; k.src = r; a.parentNode.insertBefore(k, a);
    })(window, document, 'script', 'https://mc.yandex.ru/metrika/tag.js', 'ym');
    window.ym(Number(CFG.ym), 'init', { clickmap: true, trackLinks: true, accurateTrackBounce: true, webvisor: true });
  }

  /* ---- Тема ---- */
  var root = document.documentElement;
  $$('[data-theme-toggle]').forEach(function (b) {
    b.addEventListener('click', function () {
      var dark = root.getAttribute('data-theme') === 'dark' ||
        (!root.getAttribute('data-theme') && window.matchMedia('(prefers-color-scheme: dark)').matches);
      var next = dark ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      store.set('theme', next);
    });
  });

  /* ---- Мобильное меню ---- */
  var burger = $('[data-burger]'), mnav = $('#mnav');
  if (burger && mnav) {
    burger.addEventListener('click', function () {
      var open = mnav.hasAttribute('hidden');
      if (open) mnav.removeAttribute('hidden'); else mnav.setAttribute('hidden', '');
      burger.setAttribute('aria-expanded', String(open));
    });
    mnav.addEventListener('click', function (e) { if (e.target.closest('a')) { mnav.setAttribute('hidden', ''); burger.setAttribute('aria-expanded', 'false'); } });
  }
  /* выпадающее меню «Услуги» (тач и клавиатура) */
  $$('.nav__group').forEach(function (g) {
    var btn = $('.nav__btn', g);
    btn.addEventListener('click', function (e) {
      e.stopPropagation();
      var o = g.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', String(o));
    });
    document.addEventListener('click', function () { g.classList.remove('is-open'); btn.setAttribute('aria-expanded', 'false'); });
    g.addEventListener('keydown', function (e) { if (e.key === 'Escape') { g.classList.remove('is-open'); btn.focus(); } });
  });

  /* ---- Диалоги ---- */
  function openDialog(d) { if (!d) return; if (typeof d.showModal === 'function') { if (!d.open) d.showModal(); } else { d.setAttribute('open', ''); } }
  function closeDialog(d) { if (!d) return; if (typeof d.close === 'function') d.close(); else d.removeAttribute('open'); }
  var leadModal = $('#lead-modal'), lightbox = $('#lightbox');
  document.addEventListener('click', function (e) {
    var t = e.target;
    var openBtn = t.closest && t.closest('[data-open-modal]');
    if (openBtn) {
      e.preventDefault();
      if (mnav) mnav.setAttribute('hidden', '');
      var svc = openBtn.getAttribute('data-service');
      if (svc && leadModal) { var sel = $('select[name="service"]', leadModal); if (sel) sel.value = svc; }
      openDialog(leadModal);
      var first = leadModal && $('input[name="name"]', leadModal); if (first) setTimeout(function () { first.focus(); }, 50);
      return;
    }
    if (t.closest && t.closest('[data-close]')) { closeDialog(t.closest('dialog')); return; }
    if (t.tagName === 'DIALOG') { closeDialog(t); return; } /* клик по подложке */
    var lb = t.closest && t.closest('[data-lightbox]');
    if (lb && lightbox) {
      var img = $('img', lightbox); img.src = lb.getAttribute('data-lightbox'); img.alt = lb.getAttribute('data-alt') || '';
      $('p', lightbox).textContent = lb.getAttribute('data-caption') || '';
      openDialog(lightbox);
    }
  });

  /* ---- Видео по клику (без автозвука: ролики беззвучные) ---- */
  document.addEventListener('click', function (e) {
    var btn = e.target.closest && e.target.closest('button.vid[data-video]');
    if (!btn) return;
    $$('video').forEach(function (v) { v.pause(); });
    var wrap = document.createElement('div');
    wrap.className = btn.className; wrap.style.cursor = 'default'; wrap.setAttribute('data-cat', btn.getAttribute('data-cat') || '');
    var poster = $('img', btn);
    var v = document.createElement('video');
    v.src = btn.getAttribute('data-video'); v.controls = true; v.muted = true; v.loop = true; v.playsInline = true; v.autoplay = true;
    v.setAttribute('playsinline', ''); v.setAttribute('aria-label', btn.getAttribute('aria-label') || 'Видео');
    if (poster) v.poster = poster.src;
    wrap.appendChild(v); btn.replaceWith(wrap);
    var p = v.play(); if (p && p.catch) p.catch(function () { /* пользователь запустит вручную */ });
    v.focus();
  });

  /* ---- Фильтр видео ---- */
  $$('[data-filter]').forEach(function (chip) {
    chip.addEventListener('click', function () {
      var f = chip.getAttribute('data-filter');
      $$('[data-filter]').forEach(function (c) { c.setAttribute('aria-pressed', String(c === chip)); });
      $$('.gallery [data-cat]').forEach(function (v) { v.hidden = !(f === 'all' || v.getAttribute('data-cat') === f); });
    });
  });

  /* ---- Плавное появление блоков ---- */
  var revealEls = $$('.reveal');
  var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!('IntersectionObserver' in window) || reduce) {
    revealEls.forEach(function (el) { el.classList.add('is-in'); });
  } else {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('is-in'); io.unobserve(en.target); } });
    }, { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
    revealEls.forEach(function (el) { io.observe(el); });
    setTimeout(function () { revealEls.forEach(function (el) { el.classList.add('is-in'); }); }, 4000); /* страховка */
  }

  /* ---- Cookie-баннер ---- */
  var cookie = $('#cookie');
  var accepted = store.get('cookie-ok') === '1';
  if (accepted) loadMetrika();
  else if (cookie) cookie.hidden = false;
  var ok = $('[data-cookie-ok]');
  if (ok) ok.addEventListener('click', function () { store.set('cookie-ok', '1'); cookie.hidden = true; loadMetrika(); });

  /* ---- Цели по кликам ---- */
  document.addEventListener('click', function (e) {
    var el = e.target.closest && e.target.closest('[data-goal]');
    if (el) goal(el.getAttribute('data-goal'));
  });

  /* ---- Формы ---- */
  function formatPhone(raw) {
    var d = raw.replace(/\D/g, '');
    if (!d) return '';
    if (d[0] === '8') d = '7' + d.slice(1); /* 8 927… → +7 927… */
    else if (d[0] !== '7') d = '7' + d; /* 927… → +7 927… */
    d = d.slice(0, 11);
    var out = '+7';
    if (d.length > 1) out += ' (' + d.slice(1, 4);
    if (d.length >= 5) out += ') ' + d.slice(4, 7);
    if (d.length >= 8) out += '-' + d.slice(7, 9);
    if (d.length >= 10) out += '-' + d.slice(9, 11);
    return out;
  }
  function phoneDigits(v) { return v.replace(/\D/g, ''); }

  function initForm(form) {
    var t0 = Date.now();
    var phone = $('input[name="phone"]', form), name = $('input[name="name"]', form);
    var consent = $('input[name="consent"]', form), btn = $('button[type="submit"]', form);
    var status = $('.form__status', form);
    var label = $('.btn__t', btn);
    var labelText = label ? label.textContent : '';

    function setErr(field, msg) {
      var box = $('[data-err="' + field + '"]', form); if (box) box.textContent = msg || '';
      var input = $('[name="' + field + '"]', form);
      if (input && field !== 'consent') { if (msg) input.setAttribute('aria-invalid', 'true'); else input.removeAttribute('aria-invalid'); }
    }
    function sync() { btn.disabled = !consent.checked; }
    sync(); consent.addEventListener('change', function () { sync(); if (consent.checked) setErr('consent', ''); });

    var prevDigits = '';
    phone.addEventListener('input', function (e) {
      var d = phoneDigits(phone.value);
      /* Backspace по символу маски ("(", ")", пробел, "-") удаляет предыдущую цифру */
      if (e.inputType === 'deleteContentBackward' && d === prevDigits) d = d.slice(0, -1);
      phone.value = formatPhone(d);
      prevDigits = phoneDigits(phone.value);
      if (prevDigits.length === 11) setErr('phone', '');
    });
    phone.addEventListener('blur', function () { if (phoneDigits(phone.value).length <= 1) { phone.value = ''; prevDigits = ''; } });
    name.addEventListener('input', function () { if (name.value.trim().length >= 2) setErr('name', ''); });

    function validate() {
      var okAll = true;
      if (name.value.trim().length < 2) { setErr('name', 'Введите имя (не короче 2 букв)'); okAll = false; } else setErr('name', '');
      var d = phoneDigits(phone.value);
      if (d.length !== 11) { setErr('phone', 'Введите номер полностью: 11 цифр'); okAll = false; } else setErr('phone', '');
      if (!consent.checked) { setErr('consent', 'Нужно согласие на обработку данных'); okAll = false; } else setErr('consent', '');
      if (!okAll) { var bad = $('[aria-invalid="true"]', form); if (bad) bad.focus(); }
      return okAll;
    }
    function show(kind, html) { status.className = 'form__status ' + (kind === 'ok' ? 'is-ok' : 'is-err'); status.innerHTML = html; }
    function busy(on) { form.classList.toggle('is-sending', on); btn.disabled = on || !consent.checked; if (label) label.textContent = on ? 'Отправляем…' : labelText; }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      status.className = 'form__status'; status.innerHTML = '';
      if (!validate()) return;
      var hp = $('input[name="website"]', form);
      if (hp && hp.value) { show('ok', 'Заявка принята. Мы скоро перезвоним.'); return; } /* бот: делаем вид, что успех */
      var elapsed = Date.now() - t0;
      if (elapsed < 3000) { show('err', 'Проверьте данные и нажмите «Отправить» ещё раз.'); return; }
      $('input[name="t"]', form).value = String(elapsed);
      var fd = new FormData(form);
      fd.append('page', location.pathname);
      busy(true);
      var ctl = window.AbortController ? new AbortController() : null;
      var timer = setTimeout(function () { if (ctl) ctl.abort(); }, 15000);
      fetch(CFG.endpoint || '/api/send.php', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' }, signal: ctl ? ctl.signal : undefined })
        .then(function (r) { return r.json().then(function (j) { return { ok: r.ok && j && j.ok !== false, j: j }; }); })
        .then(function (res) {
          clearTimeout(timer); busy(false);
          if (!res.ok) throw new Error('server');
          goal('form_submit');
          show('ok', '<b>Заявка принята.</b> Мы скоро перезвоним.');
          form.reset(); sync();
          if (form.closest('#lead-modal')) setTimeout(function () { closeDialog(leadModal); status.className = 'form__status'; status.innerHTML = ''; }, 3500);
        })
        .catch(function () {
          clearTimeout(timer); busy(false);
          show('err', 'Не удалось отправить заявку. Позвоните нам: <a href="tel:' + (CFG.telHref || '') + '">' + (CFG.tel || '') + '</a> или напишите в <a href="' + (CFG.maxUrl || '#') + '" target="_blank" rel="noopener">MAX</a>.');
        });
    });
  }
  $$('form[data-lead]').forEach(initForm);
})();
