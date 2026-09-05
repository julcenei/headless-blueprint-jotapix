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
  var suave = reduceMotion ? 'auto' : 'smooth';

  /** Observa uma lista, executa a ação na primeira aparição e desobserva. */
  function observeOnce(els, acao, opts) {
    if (!els.length) return;
    if (!('IntersectionObserver' in window)) { els.forEach(acao); return; }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        acao(entry.target);
        io.unobserve(entry.target);
      });
    }, opts);
    els.forEach(function (el) { io.observe(el); });
  }

  /** Liga/desliga um componente conforme ele entra e sai da viewport. */
  function whileVisible(el, aoEntrar, aoSair) {
    if (!('IntersectionObserver' in window)) { aoEntrar(); return; }
    new IntersectionObserver(function (entries) {
      // o lote pode trazer várias entradas: vale a mais recente
      entries[entries.length - 1].isIntersecting ? aoEntrar() : aoSair();
    }, { threshold: 0 }).observe(el);
  }

  /** Agenda uma escrita de layout para o próximo frame, sem enfileirar duplicatas. */
  function porFrame(fn) {
    var agendado = false;
    return function () {
      if (agendado) return;
      agendado = true;
      requestAnimationFrame(function () { agendado = false; fn(); });
    };
  }

  var aoRedimensionar = [];
  window.addEventListener('resize', porFrame(function () {
    aoRedimensionar.forEach(function (fn) { fn(); });
  }));

  /* ------------------------------------------------------------------
     1. Header: encolhe/ganha sombra ao rolar + CTA flutuante contextual
     ------------------------------------------------------------------ */
  (function header() {
    var root = document.documentElement;
    var siteHeader = $('.site-header');
    var floatQuote = $('.float-btn--quote');
    var last = 0;
    var ultimoEscondido = null;

    // Publica a altura real do header em --header-h: as âncoras e a barra
    // sticky das páginas de catálogo se posicionam a partir dela.
    var ultimaAltura = 0;
    function syncHeaderHeight() {
      if (!siteHeader) return;
      var altura = siteHeader.offsetHeight;
      // escrever custom property na raiz invalida a árvore inteira: só na mudança
      if (altura === ultimaAltura) return;
      ultimaAltura = altura;
      root.style.setProperty('--header-h', altura + 'px');
    }

    function onScroll() {
      var y = window.scrollY;
      var wasScrolled = root.classList.contains('is-scrolled');
      root.classList.toggle('is-scrolled', y > 40);
      if (wasScrolled !== root.classList.contains('is-scrolled')) {
        // a top bar colapsa com transição: mede depois que ela termina
        setTimeout(syncHeaderHeight, 420);
      }
      // O CTA flutuante só aparece depois da primeira dobra e some ao subir rápido.
      // setAttribute sempre invalida estilo, então só escreve na transição.
      if (floatQuote) {
        var esconder = y < 600 || y < last - 40;
        if (esconder !== ultimoEscondido) {
          ultimoEscondido = esconder;
          floatQuote.setAttribute('data-hidden', String(esconder));
        }
      }
      last = y;
    }
    onScroll();
    syncHeaderHeight();
    window.addEventListener('scroll', onScroll, { passive: true });
    aoRedimensionar.push(syncHeaderHeight);
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
      if (!open) $$('.mnav__row[aria-controls]', menu).forEach(function (b) { b.setAttribute('aria-expanded', 'false'); });
      if (open) {
        var first = menu.querySelector('a, button');
        if (first) first.focus({ preventScroll: true });
      }
    }

    toggle.addEventListener('click', function () {
      setOpen(toggle.getAttribute('aria-expanded') !== 'true');
    });

    // Acordeão: um grupo aberto por vez, para a lista não voltar a crescer.
    var grupos = $$('.mnav__row[aria-controls]', menu);
    grupos.forEach(function (btn) {
      btn.addEventListener('click', function () {
        var abrindo = btn.getAttribute('aria-expanded') !== 'true';
        grupos.forEach(function (outro) { outro.setAttribute('aria-expanded', 'false'); });
        btn.setAttribute('aria-expanded', String(abrindo));
      });
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
    var focaveis = slides.map(function (slide) { return $$('a, button', slide); });
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
        focaveis[i].forEach(function (el) {
          if (active) el.removeAttribute('tabindex');
          else el.setAttribute('tabindex', '-1');
        });
      });
      dots.forEach(function (dot, i) {
        dot.setAttribute('aria-selected', String(i === index));
        // reinicia a animação da barra do indicador sem forçar layout síncrono
        if (i === index) {
          dot.style.animation = 'none';
          requestAnimationFrame(function () { dot.style.animation = ''; });
        }
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
    var heroNaTela = true;
    document.addEventListener('visibilitychange', function () {
      if (document.hidden) stop();
      else if (heroNaTela) play();
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
    }, { passive: true });

    // Fora da viewport o carrossel não precisa girar
    whileVisible(hero, function () { heroNaTela = true; play(); }, function () { heroNaTela = false; stop(); });

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
     5b. Carrossel de produtos: arrastar, setas, avanço automático e
         barra de progresso. Usa scroll nativo + scroll-snap.
     ------------------------------------------------------------------ */
  (function productRail() {
    $$('[data-rail]').forEach(function (rail) {
      var track = $('[data-rail-track]', rail);
      var bar = $('[data-rail-bar]', rail);
      var prev = $('[data-rail-prev]', rail);
      var next = $('[data-rail-next]', rail);
      if (!track) return;

      var passo = 300;
      var maxScroll = 0;
      var fatia = 1;

      // Largura do card, gap e limites só mudam em resize: medidos uma vez.
      function medir() {
        var card = track.firstElementChild;
        if (card) {
          var gap = parseFloat(getComputedStyle(track).columnGap) || 0;
          passo = card.getBoundingClientRect().width + gap;
        }
        maxScroll = track.scrollWidth - track.clientWidth;
        fatia = track.clientWidth / track.scrollWidth;
        if (bar) bar.style.width = Math.max(fatia * 100, 12) + '%';
        update();
      }

      var update = porFrame(function () {
        var ratio = maxScroll > 0 ? track.scrollLeft / maxScroll : 0;
        if (bar) bar.style.transform = 'translateX(' + (ratio * (100 / Math.max(fatia, 0.12) - 100)) + '%)';
        if (prev) prev.disabled = track.scrollLeft <= 2;
        if (next) next.disabled = track.scrollLeft >= maxScroll - 2;
      });

      function scrollBy(dir) {
        track.scrollBy({ left: dir * passo, behavior: suave });
      }

      if (prev) prev.addEventListener('click', function () { scrollBy(-1); });
      if (next) next.addEventListener('click', function () { scrollBy(1); });
      track.addEventListener('scroll', update, { passive: true });

      /* Arrastar com o mouse (no touch o scroll nativo já resolve) */
      var dragging = false;
      var startX = 0;
      var startScroll = 0;
      var moved = 0;

      // Nada de marcar arrasto no pointerdown: `is-dragging` zera o
      // pointer-events dos links e a captura de ponteiro reentrega o clique ao
      // trilho — as duas coisas juntas faziam um clique simples no card nunca
      // chegar ao <a>. O arrasto só começa quando o ponteiro anda de verdade.
      var LIMIAR = 6;
      track.addEventListener('pointerdown', function (e) {
        if (e.pointerType === 'touch') return;
        dragging = true;
        moved = 0;
        startX = e.clientX;
        startScroll = track.scrollLeft;
      });
      track.addEventListener('pointermove', function (e) {
        if (!dragging) return;
        var dx = e.clientX - startX;
        moved = Math.abs(dx);
        if (moved <= LIMIAR) return;
        if (!track.hasPointerCapture(e.pointerId)) {
          track.setPointerCapture(e.pointerId);
          track.classList.add('is-dragging');
        }
        track.scrollLeft = startScroll - dx;
      });
      ['pointerup', 'pointercancel'].forEach(function (evt) {
        track.addEventListener(evt, function (e) {
          if (!dragging) return;
          dragging = false;
          if (track.hasPointerCapture(e.pointerId)) track.releasePointerCapture(e.pointerId);
          track.classList.remove('is-dragging');
        });
      });
      // um arrasto não deve virar clique no card
      track.addEventListener('click', function (e) {
        if (moved > LIMIAR) { e.preventDefault(); moved = 0; }
      }, true);

      // O trilho não anda sozinho: o marquee de setores, logo acima, já é o
      // único loop ambiente da página. Aqui o movimento é sempre do visitante.
      // Só remede ao aparecer, porque em rota oculta o trilho mede zero.
      whileVisible(rail, medir, function () {});

      medir();
      aoRedimensionar.push(medir);
    });
  })();

  /* ------------------------------------------------------------------
     6. Animações de entrada com IntersectionObserver (fade-up + stagger)
     ------------------------------------------------------------------ */
  (function reveal() {
    var els = $$('.reveal');
    if (reduceMotion) {
      els.forEach(function (el) { el.classList.add('is-visible'); });
      return;
    }
    observeOnce(els, function (el) { el.classList.add('is-visible'); }, {
      rootMargin: '0px 0px -12% 0px',
      threshold: 0.08,
    });
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

    observeOnce(nums, run, { threshold: 0.6 });
  })();

  /* ------------------------------------------------------------------
     8. Barra de progresso de leitura (páginas de catálogo)
     ------------------------------------------------------------------ */
  (function readProgress() {
    var bars = $$('.read-progress');
    if (!bars.length) return;
    // scrollHeight força layout do documento: medido em resize, não a cada scroll
    var max = 0;
    function medir() { max = document.documentElement.scrollHeight - window.innerHeight; update(); }
    var update = porFrame(function () {
      var p = max > 0 ? window.scrollY / max : 0;
      var scale = 'scaleX(' + Math.min(Math.max(p, 0), 1) + ')';
      bars.forEach(function (bar) { bar.style.transform = scale; });
    });
    medir();
    window.addEventListener('scroll', update, { passive: true });
    aoRedimensionar.push(medir);
    window.addEventListener('load', medir);
  })();

  /* ------------------------------------------------------------------
     9. Scrollspy da barra de âncoras
     ------------------------------------------------------------------ */
  (function scrollSpy() {
    $$('.anchor-bar').forEach(setupSpy);

    function setupSpy(bar) {
    var scroller = $('.anchor-bar__scroller', bar);
    // id de cada link resolvido uma vez (o href muda de forma na versão single-file)
    var links = $$('.anchor-item', bar).map(function (a) {
      var href = a.getAttribute('href');
      return { el: a, id: href.slice(href.lastIndexOf('/') + 1).replace(/^#/, '') };
    });
    var sections = links.map(function (l) { return document.getElementById(l.id); }).filter(Boolean);
    if (!sections.length) return;

    var atual = null;
    function setCurrent(id) {
      if (id === atual) return; // evita reiniciar o scroll suave à toa
      atual = id;
      links.forEach(function (l) {
        var on = l.id === id;
        l.el.classList.toggle('is-current', on);
        if (on && scroller) {
          // mantém o item ativo visível no scroller horizontal
          scroller.scrollTo({ left: l.el.offsetLeft - scroller.clientWidth / 2 + l.el.clientWidth / 2, behavior: suave });
        }
      });
    }

    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) setCurrent(entry.target.id);
      });
    }, { rootMargin: '-45% 0px -50% 0px' });
    sections.forEach(function (s) { io.observe(s); });
    }
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
    var alerta = $('#form-erros');
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
        // Anuncia quantos campos faltam antes de mandar o foco para o primeiro:
        // quem usa leitor de tela precisa do total, não só do campo atual.
        if (alerta) {
          var n = $$('[data-invalid="true"]', form).length;
          var aviso = n === 1
            ? 'Um campo precisa ser corrigido antes de enviar.'
            : n + ' campos precisam ser corrigidos antes de enviar.';
          // o texto precisa entrar DEPOIS de o elemento estar visível:
          // leitores de tela ignoram mudanças em nó com display:none
          alerta.textContent = '';
          alerta.setAttribute('data-visible', 'true');
          requestAnimationFrame(function () { alerta.textContent = aviso; });
        }
        invalid.focus();
        invalid.scrollIntoView({ block: 'center', behavior: suave });
        return;
      }
      if (alerta) alerta.setAttribute('data-visible', 'false');

      var d = new FormData(form);
      var get = function (k) { return (d.get(k) || '').toString().trim(); };
      var cidade = get('cidade');
      var uf = get('uf');
      var local = cidade && uf ? cidade + '/' + uf : cidade || uf;

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
        success.scrollIntoView({ block: 'center', behavior: suave });
      }
      form.reset();
    });

    // Pré-seleciona produto/serviço/setor a partir da URL (?produto=slug).
    // Na versão single-file os parâmetros chegam no hash (#/rota?produto=slug).
    function applyPreselect() {
      var params = new URLSearchParams(window.location.search);
      var hashQuery = window.location.hash.split('?')[1];
      if (hashQuery) {
        new URLSearchParams(hashQuery).forEach(function (v, k) {
          if (!params.has(k)) params.set(k, v);
        });
      }
      ['produto', 'servico', 'setor'].forEach(function (key) {
        var value = params.get(key);
        var select = form.elements[key];
        if (!value || !select) return;
        var match = $$('option', select).find(function (o) {
          return o.value === value || o.getAttribute('data-slug') === value;
        });
        if (match) select.value = match.value;
      });
    }
    applyPreselect();
    window.addEventListener('hashchange', applyPreselect);
  })();

  /* ------------------------------------------------------------------
     12. Ano corrente no rodapé
     ------------------------------------------------------------------ */
  $$('[data-year]').forEach(function (el) { el.textContent = new Date().getFullYear(); });
})();
