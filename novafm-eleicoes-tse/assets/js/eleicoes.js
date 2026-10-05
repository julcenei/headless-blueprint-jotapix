/* Eleições TSE – atualização automática, abas do painel e filtros. Sem dependências. */
(function () {
	'use strict';

	var CFG = window.NFE_TSE || {};

	function norm(s) {
		return String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
	}

	function Widget(el) {
		this.el = el;
		this.out = el.querySelector('.nfe-out');
		try {
			this.params = JSON.parse(el.getAttribute('data-nfe') || '{}');
		} catch (e) {
			this.params = {};
		}
		this.interval = (parseInt(el.getAttribute('data-intervalo'), 10) || 60) * 1000;
		var gerado = (parseInt(el.getAttribute('data-gerado'), 10) || 0) * 1000;
		// Página servida de cache antigo: atualiza logo.
		this.nextAt = Math.min(gerado + this.interval, Date.now() + this.interval);
		this.ui = { busca: '', eleitos: false, todos: false };
		this.req = 0;
		this.loading = false;
		this.erro = false;

		el.classList.add('nfe-js');
		el.addEventListener('click', this.onClick.bind(this));
		el.addEventListener('input', this.onInput.bind(this));
		el.addEventListener('change', this.onChange.bind(this));
		this.apply();
	}

	Widget.prototype.url = function () {
		var q = [];
		for (var k in this.params) {
			if (Object.prototype.hasOwnProperty.call(this.params, k) && this.params[k] !== '') {
				q.push(encodeURIComponent(k) + '=' + encodeURIComponent(this.params[k]));
			}
		}
		return CFG.rest + (CFG.rest.indexOf('?') === -1 ? '?' : '&') + q.join('&');
	};

	Widget.prototype.load = function (trocou) {
		var self = this;
		var id = ++this.req;
		this.loading = true;
		if (trocou) {
			this.out.setAttribute('aria-busy', 'true');
			this.el.classList.add('is-loading');
		}
		fetch(this.url(), { headers: { Accept: 'application/json' }, credentials: 'omit' })
			.then(function (r) {
				if (!r.ok) throw new Error('HTTP ' + r.status);
				return r.json();
			})
			.then(function (d) {
				if (id !== self.req) return; // resposta de um pedido antigo (usuário trocou de aba)
				self.out.innerHTML = d.html;
				self.interval = (parseInt(d.intervalo, 10) || 60) * 1000;
				self.erro = false;
				self.apply();
			})
			.catch(function () {
				if (id !== self.req) return;
				self.erro = true;
			})
			.then(function () {
				if (id !== self.req) return;
				self.loading = false;
				self.out.removeAttribute('aria-busy');
				self.el.classList.remove('is-loading');
				self.nextAt = Date.now() + (self.erro ? Math.min(self.interval, 30000) : self.interval);
				self.tick();
			});
	};

	Widget.prototype.tick = function () {
		var c = this.out.querySelector('.nfe-countdown');
		if (!c) return;
		if (this.loading) {
			c.textContent = 'Atualizando…';
			return;
		}
		var s = Math.max(0, Math.round((this.nextAt - Date.now()) / 1000));
		var txt = s >= 120 ? 'Atualiza em ' + Math.round(s / 60) + ' min' : 'Atualiza em ' + s + 's';
		c.textContent = (this.erro ? 'Falha ao atualizar · ' : '') + txt;
	};

	Widget.prototype.step = function () {
		if (!this.loading && !document.hidden && Date.now() >= this.nextAt) {
			this.load(false);
		}
		this.tick();
	};

	/* ---------- painel: abas de cargo e local ---------- */

	Widget.prototype.setPressed = function (sel, attr, val) {
		var bs = this.el.querySelectorAll(sel);
		for (var i = 0; i < bs.length; i++) {
			bs[i].setAttribute('aria-pressed', bs[i].getAttribute(attr) === val ? 'true' : 'false');
		}
	};

	Widget.prototype.syncPainel = function () {
		var p = this.params;
		var tab = this.el.querySelector('.nfe-tab[data-cargo="' + p.cargo + '"]');
		var escopo = tab ? tab.getAttribute('data-escopo') : '';
		var chips = this.el.querySelectorAll('.nfe-chip');
		var primeiroMun = '';
		for (var i = 0; i < chips.length; i++) {
			var tipo = chips[i].getAttribute('data-tipo');
			chips[i].hidden = (tipo === 'br' && escopo !== 'br') || (escopo === 'mun' && tipo !== 'mun');
			if (tipo === 'mun' && !primeiroMun) primeiroMun = chips[i].getAttribute('data-local');
		}
		if (escopo !== 'br' && p.local === 'br') p.local = this.el.getAttribute('data-uf') || '';
		// "Por seção" só faz sentido em município.
		if (escopo === 'mun' && (p.local || '').indexOf('-') === -1 && primeiroMun) p.local = primeiroMun;

		this.setPressed('.nfe-tab', 'data-cargo', p.cargo);
		this.setPressed('.nfe-chip', 'data-local', p.local);
		var sel = this.el.querySelector('.nfe-mun');
		if (sel) sel.value = this.el.querySelector('.nfe-chip[data-local="' + p.local + '"]') ? '' : p.local;
		this.syncUrl();
	};

	/* Links compartilháveis: ?nfe_cargo=&nfe_local=&nfe_secao= */
	Widget.prototype.syncUrl = function () {
		if (!this.el.getAttribute('data-url') || !window.history || !history.replaceState) return;
		try {
			var u = new URL(window.location.href);
			u.searchParams.set('nfe_cargo', this.params.cargo);
			u.searchParams.set('nfe_local', this.params.local || '');
			if (this.params.secao) u.searchParams.set('nfe_secao', this.params.secao);
			else u.searchParams.delete('nfe_secao');
			history.replaceState(null, '', u.toString());
		} catch (e) {}
	};

	Widget.prototype.trocar = function () {
		this.ui = { busca: '', eleitos: false, todos: false };
		delete this.params.secao;
		this.syncPainel();
		this.load(true);
	};

	Widget.prototype.abrirSecao = function (secao) {
		this.ui = { busca: secao ? this.ui.busca : '', eleitos: false, todos: false };
		if (secao) {
			this.voltarBusca = this.ui.busca;
			this.params.secao = secao;
		} else {
			delete this.params.secao;
			this.ui.busca = this.voltarBusca || '';
		}
		this.syncUrl();
		this.load(true);
		var top = this.el.getBoundingClientRect().top;
		if (top < 0 && this.el.scrollIntoView) this.el.scrollIntoView({ block: 'start' });
	};

	Widget.prototype.onClick = function (ev) {
		var t = ev.target.closest('.nfe-tab, .nfe-chip, .nfe-more, .nfe-sec, .nfe-voltar');
		if (!t || !this.el.contains(t)) return;
		if (t.classList.contains('nfe-sec') || t.classList.contains('nfe-voltar')) {
			if (ev.metaKey || ev.ctrlKey || ev.shiftKey || ev.button === 1) return; // nova aba: deixa o link seguir
			ev.preventDefault();
			this.abrirSecao(t.getAttribute('data-secao') || '');
		} else if (t.classList.contains('nfe-tab')) {
			if (this.params.cargo === t.getAttribute('data-cargo')) return;
			this.params.cargo = t.getAttribute('data-cargo');
			this.trocar();
		} else if (t.classList.contains('nfe-chip')) {
			if (this.params.local === t.getAttribute('data-local')) return;
			this.params.local = t.getAttribute('data-local');
			this.trocar();
		} else {
			this.ui.todos = !this.ui.todos;
			this.apply();
		}
	};

	Widget.prototype.onChange = function (ev) {
		var t = ev.target;
		if (t.classList.contains('nfe-mun')) {
			if (!t.value) return;
			this.params.local = t.value;
			this.trocar();
		} else if (t.classList.contains('nfe-only-eleitos')) {
			this.ui.eleitos = t.checked;
			this.apply();
		}
	};

	Widget.prototype.onInput = function (ev) {
		if (ev.target.closest('.nfe-search')) {
			this.ui.busca = ev.target.value;
			this.apply();
		}
	};

	/* ---------- cargos proporcionais: busca, "só eleitos", "mostrar todos" ---------- */

	Widget.prototype.apply = function () {
		if (this.out.querySelector('.nfe-secoes')) return this.applySecoes();
		var list = this.out.querySelector('.nfe-rows');
		if (!list || this.out.querySelector('.nfe-res--compacto')) return;

		var input = this.out.querySelector('.nfe-search input');
		var chk = this.out.querySelector('.nfe-only-eleitos');
		var more = this.out.querySelector('.nfe-more');
		var vazio = this.out.querySelector('.nfe-vazio');
		if (input && input.value !== this.ui.busca) input.value = this.ui.busca;
		if (chk) chk.checked = this.ui.eleitos;

		var termo = norm(this.ui.busca).trim();
		var filtrando = termo !== '' || this.ui.eleitos;
		var limite = parseInt(list.getAttribute('data-limite'), 10) || 0;
		var rows = list.querySelectorAll('.nfe-row');
		var vis = 0;
		for (var i = 0; i < rows.length; i++) {
			var r = rows[i];
			var ok = true;
			if (this.ui.eleitos && !r.hasAttribute('data-eleito')) ok = false;
			if (ok && termo && r.getAttribute('data-busca').indexOf(termo) === -1) ok = false;
			if (ok && !filtrando && !this.ui.todos && limite && i >= limite) ok = false;
			r.hidden = !ok;
			r.classList.remove('is-extra');
			if (ok) vis++;
		}
		if (vazio) vazio.hidden = vis > 0;
		if (more) {
			more.hidden = filtrando;
			more.textContent = this.ui.todos ? 'Mostrar menos' : 'Mostrar todos os ' + more.getAttribute('data-total') + ' candidatos';
		}
	};

	/* Lista de seções: busca por número, escola ou bairro. */
	Widget.prototype.applySecoes = function () {
		var input = this.out.querySelector('.nfe-search input');
		if (input && input.value !== this.ui.busca) input.value = this.ui.busca;
		var termo = norm(this.ui.busca).trim();
		var num = /^\d+$/.test(termo) ? String(parseInt(termo, 10)) : '';
		var grupos = this.out.querySelectorAll('.nfe-local');
		var vis = 0;
		for (var i = 0; i < grupos.length; i++) {
			var g = grupos[i];
			var secs = g.querySelectorAll('li');
			var algum = false;
			for (var j = 0; j < secs.length; j++) {
				var a = secs[j].querySelector('.nfe-sec');
				// Número digitado: mostra só as seções com esse número; texto: o local inteiro.
				var ok = !termo || (num ? a.getAttribute('data-num') === num : g.getAttribute('data-busca').indexOf(termo) !== -1);
				secs[j].hidden = !ok;
				if (ok) algum = true;
			}
			g.hidden = !algum;
			if (algum) vis++;
		}
		var vazio = this.out.querySelector('.nfe-vazio');
		if (vazio) vazio.hidden = vis > 0;
	};

	/* ---------- fotos que não existem no TSE viram iniciais ---------- */

	document.addEventListener(
		'error',
		function (ev) {
			var img = ev.target;
			if (!img || img.tagName !== 'IMG' || !img.classList.contains('nfe-foto')) return;
			var s = document.createElement('span');
			s.className = 'nfe-foto nfe-foto--ini';
			s.setAttribute('aria-hidden', 'true');
			s.textContent = img.getAttribute('data-ini') || '';
			img.replaceWith(s);
		},
		true
	);

	/* ---------- boot ---------- */

	function boot() {
		if (!CFG.rest || !window.fetch) return;
		var widgets = [];
		var els = document.querySelectorAll('.nfe-widget');
		for (var i = 0; i < els.length; i++) {
			if (!els[i].__nfe) {
				els[i].__nfe = new Widget(els[i]);
				widgets.push(els[i].__nfe);
			}
		}
		if (!widgets.length) return;
		setInterval(function () {
			for (var j = 0; j < widgets.length; j++) widgets[j].step();
		}, 1000);
		document.addEventListener('visibilitychange', function () {
			if (!document.hidden) {
				for (var j = 0; j < widgets.length; j++) widgets[j].step();
			}
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();
