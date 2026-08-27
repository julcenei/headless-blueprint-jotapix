/* ==========================================================================
   ELOTEC — comportamento do site
   JavaScript puro, sem dependências. Cada bloco é independente: se um
   componente não existir na página, o módulo simplesmente não faz nada.
   ========================================================================== */
(function () {
  'use strict';

  var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
  var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

  /* ------------------------------------------------------------------
     1. Header: encolhe/ganha sombra ao rolar + CTA flutuante contextual
     ------------------------------------------------------------------ */
  (function header() {
    var root = document.documentElement;
    var siteHeader = $('.site-header');
    var floatQuote = $('.float-btn--quote');
    var last = 0;

    // Publica a altura real do header em --header-h: as âncoras e a barra
    // sticky das páginas de catálogo se posicionam a partir dela.
    function syncHeaderHeight() {
      if (!siteHeader) return;
      root.style.setProperty('--header-h', siteHeader.offsetHeight + 'px');
    }

    function onScroll() {
      var y = window.scrollY;
      var wasScrolled = root.classList.contains('is-scrolled');
      root.classList.toggle('is-scrolled', y > 40);
      if (wasScrolled !== root.classList.contains('is-scrolled')) {
        // a top bar colapsa com transição: mede depois que ela termina
        setTimeout(syncHeaderHeight, 420);
      }
      // O CTA flutuante só aparece depois do primeiro dobra e some ao subir rápido
      if (floatQuote) {
        var goingUpFast = y < last - 40;
        floatQuote.setAttribute('data-hidden', String(y < 600 || goingUpFast));
      }
      last = y;
    }
    onScroll();
    syncHeaderHeight();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.addEventListener('resize', syncHeaderHeight);
    window.addEventListener('load', syncHeaderHeight);
  })();

  /* ------------------------------------------------------------------
     2. Dropdowns do menu (hover no desktop + teclado/click acessível)
     ------------------------------------------------------------------ */
  (function dropdowns() {
    var items = $$('.nav__item--has-menu');
    if (!items.length) return;

    function close(item) {
      item.setAttribute('data-open', 'false');
      var btn = $('.nav__link', item);
      if (btn) btn.setAttribute('aria-expanded', 'false');
    }
    function open(item) {
      items.forEach(function (other) { if (other !== item) close(other); });
      item.setAttribute('data-open', 'true');
      var btn = $('.nav__link', item);
      if (btn) btn.setAttribute('aria-expanded', 'true');
    }

    items.forEach(function (item) {
      var btn = $('.nav__link', item);
      var timer;

      item.addEventListener('mouseenter', function () {
        clearTimeout(timer);
        open(item);
      });
      item.addEventListener('mouseleave', function () {
        timer = setTimeout(function () { close(item); }, 140);
      });
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var isOpen = item.getAttribute('data-open') === 'true';
        isOpen ? close(item) : open(item);
      });
      // Fecha ao sair do último elemento focável com Tab
      item.addEventListener('focusout', function (e) {
        if (!item.contains(e.relatedTarget)) close(item);
      });
    });

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') items.forEach(close);
    });
    document.addEventListener('click', function (e) {
      items.forEach(function (item) { if (!item.contains(e.target)) close(item); });
    });
  })();

  /* ------------------------------------------------------------------
     3. Menu mobile fullscreen
     ------------------------------------------------------------------ */
  (function mobileMenu() {
    var toggle = $('.nav-toggle');
    var menu = $('#mobile-menu');
    if (!toggle || !menu) return;

    function setOpen(open) {
      toggle.setAttribute('aria-expanded', String(open));
      menu.setAttribute('data-open', String(open));
      document.body.classList.toggle('is-locked', open);
      toggle.setAttribute('aria-label', open ? 'Fechar menu' : 'Abrir menu');
      if (open) {
        var first = menu.querySelector('a, button');
        if (first) first.focus({ preventScroll: true });
      }
    }

    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });
    menu.addEventListener('click', function (e) {
      if (e.target.closest('a')) setOpen(false);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && menu.getAttribute('data-open') === 'true') {
        setOpen(false);
        toggle.focus();
      }
    });
    // Fecha ao voltar para o desktop
    window.matchMedia('(min-width: 1081px)').addEventListener('change', function (ev) {
      if (ev.matches) setOpen(false);
    });
  })();

  /* ------------------------------------------------------------------
     4. Hero carrossel: crossfade + ken burns, autoplay 7s, teclado e swipe
     ------------------------------------------------------------------ */
  (function heroCarousel() {
    var hero = $('.hero[data-carousel]');
    if (!hero) return;

    var slides = $$('.hero__slide', hero);
    var dots = $$('.hero__dot', hero);
    var live = $('.hero__live', hero);
    var index = 0;
    var timer = null;
    var DURATION = 7000;

    function go(next, announce) {
      index = (next + slides.length) % slides.length;
      slides.forEach(function (slide, i) {
        var active = i === index;
        slide.classList.toggle('is-active', active);
        slide.setAttribute('aria-hidden', String(!active));
        // Impede que links de slides ocultos recebam foco
        $$('a, button', slide).forEach(function (el) {
          if (active) el.removeAttribute('tabindex');
          else el.setAttribute('tabindex', '-1');
        });
      });
      dots.forEach(function (dot, i) {
        dot.setAttribute('aria-selected', String(i === index));
        // reinicia a animação da barra do indicador
        if (i === index) { dot.style.animation = 'none'; void dot.offsetWidth; dot.style.animation = ''; }
      });
      if (announce && live) live.textContent = 'Slide ' + (index + 1) + ' de ' + slides.length;
    }

    function play() {
      if (reduceMotion) return;
      stop();
      timer = setInterval(function () { go(index + 1); }, DURATION);
      hero.classList.remove('is-paused');
    }
    function stop() {
      if (timer) clearInterval(timer);
      timer = null;
      hero.classList.add('is-paused');
    }

    hero.style.setProperty('--slide-dur', DURATION + 'ms');

    $$('[data-hero-prev]', hero).forEach(function (b) {
      b.addEventListener('click', function () { go(index - 1, true); play(); });
    });
    $$('[data-hero-next]', hero).forEach(function (b) {
      b.addEventListener('click', function () { go(index + 1, true); play(); });
    });
    dots.forEach(function (dot, i) {
      dot.addEventListener('click', function () { go(i, true); play(); });
    });

    // Pausa quando o ponteiro ou o foco está no hero, e quando a aba não está visível
    hero.addEventListener('mouseenter', stop);
    hero.addEventListener('mouseleave', play);
    hero.addEventListener('focusin', stop);
    hero.addEventListener('focusout', function (e) {
      if (!hero.contains(e.relatedTarget)) play();
    });
    document.addEventListener('visibilitychange', function () {
      document.hidden ? stop() : play();
    });

    // Setas do teclado quando o carrossel está focado
    hero.addEventListener('keydown', function (e) {
      if (e.key === 'ArrowLeft') { go(index - 1, true); play(); }
      if (e.key === 'ArrowRight') { go(index + 1, true); play(); }
    });

    // Swipe no touch
    var startX = null;
    hero.addEventListener('touchstart', function (e) { startX = e.touches[0].clientX; }, { passive: true });
    hero.addEventListener('touchend', function (e) {
      if (startX === null) return;
      var dx = e.changedTouches[0].clientX - startX;
      if (Math.abs(dx) > 55) { go(index + (dx < 0 ? 1 : -1), true); play(); }
      startX = null;
    });

    go(0);
    play();
  })();

  /* ------------------------------------------------------------------
     5. Marquee de setores: duplica o conteúdo e permite pausar
     ------------------------------------------------------------------ */
  (function marquee() {
    $$('.marquee').forEach(function (el) {
      var track = $('.marquee__track', el);
      var pauseBtn = $('.marquee__pause', el);
      if (!track) return;

      // Duplica os itens para que o loop de -50% seja contínuo
      track.innerHTML += track.innerHTML;
      // A segunda metade é decorativa: retira do fluxo de leitura e do foco
      var items = $$('.marquee__item', track);
      items.slice(items.length / 2).forEach(function (el) {
        el.setAttribute('aria-hidden', 'true');
        el.setAttribute('tabindex', '-1');
      });

      if (pauseBtn) {
        pauseBtn.addEventListener('click', function () {
          var paused = el.getAttribute('data-paused') === 'true';
          el.setAttribute('data-paused', String(!paused));
          pauseBtn.setAttribute('aria-pressed', String(!paused));
          pauseBtn.setAttribute('aria-label', paused ? 'Pausar rolagem dos setores' : 'Retomar rolagem dos setores');
        });
      }
    });
  })();

  /* ------------------------------------------------------------------
     6. Animações de entrada com IntersectionObserver (fade-up + stagger)
     ------------------------------------------------------------------ */
  (function reveal() {
    var els = $$('.reveal');
    if (!els.length) return;
    if (reduceMotion || !('IntersectionObserver' in window)) {
      els.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        io.unobserve(entry.target);
      });
    }, { rootMargin: '0px 0px -12% 0px', threshold: 0.08 });
    els.forEach(function (el) { io.observe(el); });
  })();

  /* ------------------------------------------------------------------
     7. Contadores animados nas estatísticas
     ------------------------------------------------------------------ */
  (function counters() {
    var nums = $$('[data-count-to]');
    if (!nums.length) return;

    function run(el) {
      var target = parseFloat(el.getAttribute('data-count-to'));
      var prefix = el.getAttribute('data-prefix') || '';
      var suffix = el.getAttribute('data-suffix') || '';
      if (reduceMotion) { el.textContent = prefix + target + suffix; return; }

      var duration = 1400;
      var start = null;
      function step(ts) {
        if (start === null) start = ts;
        var p = Math.min((ts - start) / duration, 1);
        var eased = 1 - Math.pow(1 - p, 3); // ease-out cubic
        el.textContent = prefix + Math.round(target * eased) + suffix;
        if (p < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
    }

    if (!('IntersectionObserver' in window)) { nums.forEach(run); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        run(entry.target);
        io.unobserve(entry.target);
      });
    }, { threshold: 0.6 });
    nums.forEach(function (el) { io.observe(el); });
  })();

  /* ------------------------------------------------------------------
     8. Barra de progresso de leitura (páginas de catálogo)
     ------------------------------------------------------------------ */
  (function readProgress() {
    var bar = $('.read-progress');
    if (!bar) return;
    function update() {
      var max = document.documentElement.scrollHeight - window.innerHeight;
      var p = max > 0 ? window.scrollY / max : 0;
      bar.style.transform = 'scaleX(' + Math.min(Math.max(p, 0), 1) + ')';
    }
    update();
    window.addEventListener('scroll', update, { passive: true });
    window.addEventListener('resize', update);
  })();

  /* ------------------------------------------------------------------
     9. Scrollspy da barra de âncoras
     ------------------------------------------------------------------ */
  (function scrollSpy() {
    var bar = $('.anchor-bar');
    if (!bar) return;
    var links = $$('.anchor-item', bar);
    var sections = links
      .map(function (a) { return document.getElementById(a.getAttribute('href').slice(1)); })
      .filter(Boolean);
    if (!sections.length) return;

    function setCurrent(id) {
      links.forEach(function (a) {
        var on = a.getAttribute('href') === '#' + id;
        a.classList.toggle('is-current', on);
        if (on) {
          // mantém o item ativo visível no scroller horizontal
          var scroller = $('.anchor-bar__scroller', bar);
          var left = a.offsetLeft - scroller.clientWidth / 2 + a.clientWidth / 2;
          scroller.scrollTo({ left: left, behavior: reduceMotion ? 'auto' : 'smooth' });
        }
      });
    }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) setCurrent(entry.target.id);
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    sections.forEach(function (s) { io.observe(s); });
  })();

  /* ------------------------------------------------------------------
     10. FAQ acessível (accordion)
     ------------------------------------------------------------------ */
  (function faq() {
    $$('.faq-item__btn').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var expanded = btn.getAttribute('aria-expanded') === 'true';
        btn.setAttribute('aria-expanded', String(!expanded));
      });
    });
  })();

  /* ------------------------------------------------------------------
     11. Formulário de orçamento → abre o WhatsApp com a mensagem pronta
     ------------------------------------------------------------------ */
  (function quoteForm() {
    var form = $('#form-orcamento');
    if (!form) return;

    var success = $('#form-success');
    var phone = form.getAttribute('data-whatsapp');

    function setError(field, message) {
      var wrap = field.closest('.field') || field.closest('.checkbox-field');
      if (!wrap) return;
      wrap.setAttribute('data-invalid', message ? 'true' : 'false');
      var slot = wrap.querySelector('.field__error');
      if (slot) slot.textContent = message || '';
      field.setAttribute('aria-invalid', message ? 'true' : 'false');
    }

    function validate() {
      var firstInvalid = null;
      $$('[required]', form).forEach(function (field) {
        var value = field.type === 'checkbox' ? field.checked : field.value.trim();
        var message = '';
        if (!value) {
          message = field.type === 'checkbox' ? 'É necessário aceitar para continuar.' : 'Campo obrigatório.';
        } else if (field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(field.value)) {
          message = 'Informe um e-mail válido.';
        } else if (field.type === 'tel' && field.value.replace(/\D/g, '').length < 10) {
          message = 'Informe um telefone com DDD.';
        }
        setError(field, message);
        if (message && !firstInvalid) firstInvalid = field;
      });
      return firstInvalid;
    }

    // Limpa o erro assim que a pessoa corrige o campo
    form.addEventListener('input', function (e) {
      var field = e.target;
      if (field.matches('[required]')) {
        var wrap = field.closest('.field') || field.closest('.checkbox-field');
        if (wrap && wrap.getAttribute('data-invalid') === 'true') setError(field, '');
      }
    });

    function line(label, value) {
      return value ? '*' + label + ':* ' + value + '\n' : '';
    }

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var invalid = validate();
      if (invalid) {
        invalid.focus();
        invalid.scrollIntoView({ block: 'center', behavior: reduceMotion ? 'auto' : 'smooth' });
        return;
      }

      var d = new FormData(form);
      var get = function (k) { return (d.get(k) || '').toString().trim(); };
      var cidade = get('cidade');
      var uf = get('uf');
      var local = cidade && uf ? cidade + ' – ' + uf : cidade || uf;

      var msg =
        'Olá! Vim pelo site da Elotec e gostaria de solicitar um orçamento.\n\n' +
        line('Nome', get('nome')) +
        line('Empresa', get('empresa')) +
        line('Telefone/WhatsApp', get('telefone')) +
        line('E-mail', get('email')) +
        line('Cidade/UF', local) +
        line('Setor industrial', get('setor')) +
        line('Produto de interesse', get('produto')) +
        line('Serviço de interesse', get('servico')) +
        '\n*Mensagem:*\n' + get('mensagem');

      window.open('https://wa.me/' + phone + '?text=' + encodeURIComponent(msg), '_blank', 'noopener');

      if (success) {
        success.setAttribute('data-visible', 'true');
        success.focus();
        success.scrollIntoView({ block: 'center', behavior: reduceMotion ? 'auto' : 'smooth' });
      }
      form.reset();
    });

    // Pré-seleciona produto/serviço/setor a partir da URL (?produto=slug)
    var params = new URLSearchParams(window.location.search);
    ['produto', 'servico', 'setor'].forEach(function (key) {
      var value = params.get(key);
      var select = form.elements[key];
      if (!value || !select) return;
      var match = $$('option', select).find(function (o) {
        return o.value === value || o.getAttribute('data-slug') === value;
      });
      if (match) select.value = match.value;
    });
  })();

  /* ------------------------------------------------------------------
     12. Ano corrente no rodapé
     ------------------------------------------------------------------ */
  $$('[data-year]').forEach(function (el) { el.textContent = new Date().getFullYear(); });
})();
