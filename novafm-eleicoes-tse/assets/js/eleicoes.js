/* Eleições TSE – atualização automática, painel, busca, compartilhar. Sem dependências. */
(function () {
	'use strict';

	var CFG = window.NFE_TSE || {};

	function norm(s) {
		return String(s || '').toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');
	}

	function reduzMovimento() {
		return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	}

	/* ---------- cabeçalho fixo do tema: as abas grudam logo abaixo dele ---------- */

	var topoAtual = -1;
	function topoFixo() {
		var vw = document.documentElement.clientWidth;
		var y = 0;
		for (var volta = 0; volta < 4; volta++) {
			var achou = false;
			var pilha = document.elementsFromPoint ? document.elementsFromPoint(vw / 2, y + 1) : [];
			for (var i = 0; i < pilha.length && !achou; i++) {
				for (var n = pilha[i]; n && n !== document.body && n !== document.documentElement; n = n.parentElement) {
					if (n.closest && n.closest('.nfe')) break;
					var pos = getComputedStyle(n).position;
					if (pos === 'fixed' || pos === 'sticky') {
						var r = n.getBoundingClientRect();
						if (r.top <= y + 1 && r.bottom > y + 1 && r.height < 240 && r.width > vw * 0.5) {
							y = r.bottom;
							achou = true;
						}
						break;
					}
				}
			}
			if (!achou) break;
		}
		y = Math.round(y);
		if (y !== topoAtual) {
			topoAtual = y;
			document.documentElement.style.setProperty('--nfe-top', y + 'px');
		}
		return y;
	}

	function rolarPara(el) {
		var top = el.getBoundingClientRect().top;
		var off = Math.max(0, topoAtual) + 8;
		if (top < off) {
			window.scrollTo({ top: window.pageYOffset + top - off, behavior: reduzMovimento() ? 'auto' : 'smooth' });
		}
	}

	/* ---------- widget ---------- */

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
		this.ui = { busca: '', eleitos: false, todos: false, venc: '' };
		this.req = 0;
		this.loading = false;
		this.erro = false;

		el.classList.add('nfe-js');
		el.addEventListener('click', this.onClick.bind(this));
		el.addEventListener('input', this.onInput.bind(this));
		el.addEventListener('change', this.onChange.bind(this));

		var sel = el.querySelector('select.nfe-mun');
		if (sel) this.combo = new Combo(sel);
		var tabs = el.querySelector('.nfe-tabs');
		if (tabs) this.initTabs(tabs);
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
		var anterior = null;
		this.loading = true;
		if (trocou) {
			this.out.setAttribute('aria-busy', 'true');
			this.el.classList.add('is-loading');
			// Só mostra o esqueleto se a resposta demorar (cache quente responde antes).
			this.skelTimer = setTimeout(function () {
				if (id !== self.req || !self.loading) return;
				anterior = self.out.innerHTML;
				self.out.innerHTML = skeleton(self.params.cargo);
			}, 150);
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
				if (anterior !== null) self.out.innerHTML = anterior;
				self.erro = true;
			})
			.then(function () {
				if (id !== self.req) return;
				clearTimeout(self.skelTimer);
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

	Widget.prototype.initTabs = function (tabs) {
		function fade() {
			var max = tabs.scrollWidth - tabs.clientWidth;
			tabs.classList.toggle('is-mais-dir', tabs.scrollLeft < max - 4);
			tabs.classList.toggle('is-mais-esq', tabs.scrollLeft > 4);
		}
		tabs.addEventListener('scroll', fade, { passive: true });
		window.addEventListener('resize', fade);
		fade();
		this.fadeTabs = fade;
		var ativa = tabs.querySelector('[aria-pressed="true"]');
		if (ativa) tabs.scrollLeft = Math.max(0, ativa.offsetLeft - 24);
		fade();
	};

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
			chips[i].hidden = (tipo === 'br' && escopo !== 'br') || (escopo === 'mun' && tipo !== 'mun') || escopo === 'mapa';
			if (tipo === 'mun' && !primeiroMun) primeiroMun = chips[i].getAttribute('data-local');
		}
		if (escopo !== 'br' && escopo !== 'mapa' && p.local === 'br') p.local = this.el.getAttribute('data-uf') || '';
		// "Por seção" só faz sentido em município.
		if (escopo === 'mun' && (p.local || '').indexOf('-') === -1 && primeiroMun) p.local = primeiroMun;

		this.setPressed('.nfe-tab', 'data-cargo', p.cargo);
		this.setPressed('.nfe-chip', 'data-local', p.local);
		var naoChip = !this.el.querySelector('.nfe-chip[data-local="' + p.local + '"]');
		if (this.combo) {
			this.combo.wrap.hidden = escopo === 'mapa';
			this.combo.set(naoChip ? p.local : '');
		}
		if (tab && tab.scrollIntoView) tab.scrollIntoView({ block: 'nearest', inline: 'nearest' });
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
		this.ui = { busca: '', eleitos: false, todos: false, venc: this.ui.venc };
		delete this.params.secao;
		this.syncPainel();
		this.load(true);
		rolarPara(this.el);
	};

	Widget.prototype.abrirSecao = function (secao) {
		var venc = this.ui.venc;
		if (secao) {
			this.voltarBusca = this.ui.busca;
			this.params.secao = secao;
			this.ui = { busca: '', eleitos: false, todos: false, venc: venc };
		} else {
			delete this.params.secao;
			this.ui = { busca: this.voltarBusca || '', eleitos: false, todos: false, venc: venc };
		}
		this.syncUrl();
		this.load(true);
		rolarPara(this.el);
	};

	Widget.prototype.onClick = function (ev) {
		var t = ev.target.closest('.nfe-tab, .nfe-chip, .nfe-more, .nfe-sec, .nfe-voltar, .nfe-share, .nfe-venc-tab, [data-ir-cargo]');
		if (!t || !this.el.contains(t)) return;
		if (t.classList.contains('nfe-share')) {
			ev.preventDefault();
			compartilhar(t, this);
		} else if (t.hasAttribute('data-ir-cargo')) {
			// Mapa: abre o resultado do estado (no painel) ou segue o link.
			if (!this.el.querySelector('.nfe-tabs') || ev.metaKey || ev.ctrlKey) return;
			ev.preventDefault();
			this.params.cargo = t.getAttribute('data-ir-cargo');
			this.params.local = t.getAttribute('data-ir-local');
			this.trocar();
		} else if (t.classList.contains('nfe-venc-tab')) {
			this.ui.venc = t.getAttribute('data-cargo');
			this.apply();
		} else if (t.classList.contains('nfe-sec') || t.classList.contains('nfe-voltar')) {
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
			if (!t.value || t.value === this.params.local) return;
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

	/* ---------- filtros dentro do resultado ---------- */

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

	/* Lista de seções: busca por número, escola ou bairro; cargo do "vencedor por local". */
	Widget.prototype.applySecoes = function () {
		var res = this.out.querySelector('.nfe-secoes');
		var tabs = this.out.querySelectorAll('.nfe-venc-tab');
		if (tabs.length) {
			var venc = this.ui.venc;
			if (!venc || !this.out.querySelector('.nfe-venc-tab[data-cargo="' + venc + '"]')) venc = tabs[0].getAttribute('data-cargo');
			res.setAttribute('data-venc', venc);
			for (var t = 0; t < tabs.length; t++) tabs[t].setAttribute('aria-pressed', tabs[t].getAttribute('data-cargo') === venc ? 'true' : 'false');
		}
		var input = this.out.querySelector('.nfe-search input');
		if (input && input.value !== this.ui.busca) input.value = this.ui.busca;
		var termo = norm(this.ui.busca).trim();
		var num = /^\d+$/.test(termo) ? String(parseInt(termo, 10)) : '';
		var grupos = this.out.querySelectorAll('.nfe-local');
		var vis = 0;
		for (var i = 0; i < grupos.length; i++) {
			var g = grupos[i];
			var secs = g.querySelectorAll('.nfe-secs > li');
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

	/* ---------- esqueleto de carregamento ---------- */

	function skeleton(cargo) {
		var linhas = '';
		var n = cargo === 'secoes' || cargo === 'mapa' ? 4 : 5;
		for (var i = 0; i < n; i++) {
			linhas += '<div class="nfe-skel__row"><span class="nfe-skel__c"></span><span class="nfe-skel__l"><i style="width:' + (70 - i * 9) + '%"></i><i style="width:' + (90 - i * 12) + '%"></i></span></div>';
		}
		return '<div class="nfe-res nfe-skel" aria-hidden="true"><i class="nfe-skel__t"></i><i class="nfe-skel__h"></i><i class="nfe-skel__p"></i>' + linhas + '</div>';
	}

	/* ---------- busca de município (combobox sobre o <select>) ---------- */

	function Combo(sel) {
		var self = this;
		this.sel = sel;
		this.opts = [];
		for (var i = 0; i < sel.options.length; i++) {
			if (sel.options[i].value) this.opts.push({ v: sel.options[i].value, t: sel.options[i].text, n: norm(sel.options[i].text) });
		}
		var label = sel.closest('label') || sel;
		this.wrap = document.createElement('div');
		this.wrap.className = 'nfe-combo';
		var id = 'nfe-combo-' + Math.random().toString(36).slice(2, 8);
		this.wrap.innerHTML =
			'<input type="text" class="nfe-combo__in" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="' + id + '" autocomplete="off" spellcheck="false">' +
			'<ul class="nfe-combo__list" role="listbox" id="' + id + '" hidden></ul>';
		this.input = this.wrap.firstChild;
		this.list = this.wrap.lastChild;
		this.input.setAttribute('placeholder', (sel.options[0] && sel.options[0].text.replace(/…$/, '')) || 'Buscar município');
		this.input.setAttribute('aria-label', 'Buscar município');
		label.parentNode.insertBefore(this.wrap, label);
		this.wrap.hidden = label.hidden;
		label.hidden = true;
		this.ativo = -1;

		this.input.addEventListener('input', function () {
			self.filtrar(self.input.value);
		});
		this.input.addEventListener('focus', function () {
			self.input.select();
			self.filtrar(self.input.value === self.nomeAtual ? '' : self.input.value);
		});
		this.input.addEventListener('keydown', function (ev) {
			var itens = self.list.querySelectorAll('li');
			if (ev.key === 'ArrowDown' || ev.key === 'ArrowUp') {
				ev.preventDefault();
				if (self.list.hidden) self.filtrar(self.input.value);
				self.marcar(self.ativo + (ev.key === 'ArrowDown' ? 1 : -1));
			} else if (ev.key === 'Enter') {
				if (!self.list.hidden && itens.length) {
					ev.preventDefault();
					self.escolher(itens[Math.max(0, self.ativo)].getAttribute('data-v'));
				}
			} else if (ev.key === 'Escape') {
				self.fechar();
				self.input.value = self.nomeAtual || '';
			}
		});
		this.input.addEventListener('blur', function () {
			setTimeout(function () {
				self.fechar();
				self.input.value = self.nomeAtual || '';
			}, 150);
		});
		this.list.addEventListener('mousedown', function (ev) {
			var li = ev.target.closest('li');
			if (li) {
				ev.preventDefault();
				self.escolher(li.getAttribute('data-v'));
			}
		});
		this.set(sel.value);
	}

	Combo.prototype.filtrar = function (q) {
		var t = norm(q).trim();
		var comeca = [];
		var contem = [];
		for (var i = 0; i < this.opts.length; i++) {
			var o = this.opts[i];
			if (!t || o.n.indexOf(t) === 0) comeca.push(o);
			else if (o.n.indexOf(t) > 0) contem.push(o);
		}
		var res = comeca.concat(contem).slice(0, t ? 8 : 50);
		var html = '';
		for (var j = 0; j < res.length; j++) {
			html += '<li role="option" id="' + this.list.id + '-' + j + '" data-v="' + res[j].v + '">' + res[j].t.replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }) + '</li>';
		}
		this.list.innerHTML = html || '<li class="is-vazio" aria-disabled="true">Nenhum município encontrado</li>';
		this.list.hidden = false;
		this.input.setAttribute('aria-expanded', 'true');
		this.marcar(t && res.length ? 0 : -1);
	};

	Combo.prototype.marcar = function (i) {
		var itens = this.list.querySelectorAll('li[data-v]');
		if (!itens.length) return;
		this.ativo = Math.max(0, Math.min(itens.length - 1, i));
		for (var j = 0; j < itens.length; j++) itens[j].classList.toggle('is-ativo', j === this.ativo);
		if (i >= 0) {
			this.input.setAttribute('aria-activedescendant', itens[this.ativo].id);
			itens[this.ativo].scrollIntoView({ block: 'nearest' });
		}
	};

	Combo.prototype.fechar = function () {
		this.list.hidden = true;
		this.input.setAttribute('aria-expanded', 'false');
		this.input.removeAttribute('aria-activedescendant');
	};

	Combo.prototype.escolher = function (v) {
		this.fechar();
		this.set(v);
		this.input.blur();
		this.sel.value = v;
		this.sel.dispatchEvent(new Event('change', { bubbles: true }));
	};

	Combo.prototype.set = function (v) {
		this.nomeAtual = '';
		for (var i = 0; i < this.opts.length; i++) {
			if (this.opts[i].v === v) this.nomeAtual = this.opts[i].t;
		}
		this.input.value = this.nomeAtual;
		this.wrap.classList.toggle('is-sel', !!this.nomeAtual);
	};

	/* ---------- compartilhar ---------- */

	function toast(el, msg) {
		var t = document.createElement('div');
		t.className = 'nfe-toast';
		t.setAttribute('role', 'status');
		t.textContent = msg;
		el.appendChild(t);
		setTimeout(function () {
			t.remove();
		}, 2200);
	}

	function copiar(txt) {
		if (navigator.clipboard && window.isSecureContext) return navigator.clipboard.writeText(txt);
		return new Promise(function (ok, erro) {
			var ta = document.createElement('textarea');
			ta.value = txt;
			ta.style.position = 'fixed';
			ta.style.opacity = '0';
			document.body.appendChild(ta);
			ta.select();
			try {
				document.execCommand('copy') ? ok() : erro();
			} catch (e) {
				erro();
			}
			ta.remove();
		});
	}

	function fecharMenus() {
		var ms = document.querySelectorAll('.nfe-share-menu');
		for (var i = 0; i < ms.length; i++) ms[i].remove();
	}

	function compartilhar(btn, w) {
		var res = btn.closest('.nfe-res');
		var texto = (res && res.getAttribute('data-compartilhar')) || document.title;
		var url = window.location.href;
		if (btn.nextElementSibling && btn.nextElementSibling.classList.contains('nfe-share-menu')) {
			fecharMenus();
			return;
		}
		fecharMenus();
		var toque = window.matchMedia && window.matchMedia('(pointer: coarse)').matches;
		if (navigator.share && toque) {
			navigator.share({ title: document.title, text: texto, url: url }).catch(function () {});
			return;
		}
		var m = document.createElement('div');
		m.className = 'nfe-share-menu';
		m.setAttribute('role', 'menu');
		var wa = 'https://wa.me/?text=' + encodeURIComponent(texto + '\n' + url);
		var x = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(texto) + '&url=' + encodeURIComponent(url);
		var fb = 'https://www.facebook.com/sharer/sharer.php?u=' + encodeURIComponent(url);
		m.innerHTML =
			'<button type="button" role="menuitem" data-acao="copiar">Copiar link</button>' +
			'<a role="menuitem" target="_blank" rel="noopener" href="' + wa + '">WhatsApp</a>' +
			'<a role="menuitem" target="_blank" rel="noopener" href="' + fb + '">Facebook</a>' +
			'<a role="menuitem" target="_blank" rel="noopener" href="' + x + '">X (Twitter)</a>';
		btn.parentNode.insertBefore(m, btn.nextSibling);
		m.addEventListener('click', function (ev) {
			var b = ev.target.closest('[data-acao="copiar"]');
			if (b) {
				copiar(url).then(
					function () {
						toast(w.el, 'Link copiado');
					},
					function () {
						toast(w.el, 'Não foi possível copiar');
					}
				);
			}
			fecharMenus();
		});
		var primeiro = m.querySelector('[role="menuitem"]');
		if (primeiro) primeiro.focus();
	}

	document.addEventListener('click', function (ev) {
		if (!ev.target.closest('.nfe-share-menu, .nfe-share')) fecharMenus();
	});
	document.addEventListener('keydown', function (ev) {
		if (ev.key === 'Escape') fecharMenus();
	});

	/* ---------- hemiciclo: destaca o partido sob o mouse/foco ---------- */

	function focoHemi(ev) {
		var alvo = ev.target.closest ? ev.target.closest('.nfe-hemi [data-p]') : null;
		var hemi = ev.target.closest ? ev.target.closest('.nfe-hemi') : null;
		if (!hemi) return;
		var p = alvo && ev.type !== 'mouseout' && ev.type !== 'focusout' ? alvo.getAttribute('data-p') : null;
		if (ev.type === 'mouseout' && ev.relatedTarget && hemi.contains(ev.relatedTarget)) {
			var dest = ev.relatedTarget.closest('[data-p]');
			p = dest ? dest.getAttribute('data-p') : null;
		}
		hemi.classList.toggle('is-foco', p !== null);
		var itens = hemi.querySelectorAll('[data-p]');
		for (var i = 0; i < itens.length; i++) {
			itens[i].classList.toggle('is-on', itens[i].getAttribute('data-p') === p);
		}
	}
	['mouseover', 'mouseout', 'focusin', 'focusout'].forEach(function (t) {
		document.addEventListener(t, focoHemi);
	});

	/* ---------- fotos que não existem no TSE viram iniciais ---------- */

	document.addEventListener(
		'error',
		function (ev) {
			var img = ev.target;
			if (!img || img.tagName !== 'IMG' || !img.classList.contains('nfe-foto')) return;
			var s = document.createElement('span');
			s.className = img.className + ' nfe-foto--ini';
			s.setAttribute('aria-hidden', 'true');
			s.textContent = img.getAttribute('data-ini') || '';
			img.replaceWith(s);
		},
		true
	);

	/* ---------- largura: sai da coluna do tema sem passar da tela ---------- */

	// O CSS já centraliza assumindo coluna centrada; aqui medimos a coluna real
	// (temas com barra lateral ou padding assimétrico) e mantemos 16px de folga.
	function ajustaLargura() {
		var els = document.querySelectorAll('.nfe[data-largo]');
		var vw = document.documentElement.clientWidth;
		for (var i = 0; i < els.length; i++) {
			var el = els[i];
			var par = el.parentElement;
			if (!par) continue;
			var max = parseInt(getComputedStyle(el).getPropertyValue('--nfe-largura'), 10) || 1130;
			var pr = par.getBoundingClientRect();
			var cs = getComputedStyle(par);
			var pl = pr.left + parseFloat(cs.paddingLeft || 0);
			var pw = par.clientWidth - parseFloat(cs.paddingLeft || 0) - parseFloat(cs.paddingRight || 0);
			var w = Math.min(max, vw - 32);
			var ml, mr;
			if (w <= pw) {
				// A coluna já comporta: ocupa a coluna (até o máximo), centralizado nela.
				w = Math.min(max, pw);
				ml = mr = (pw - w) / 2;
			} else {
				var left = Math.max(16, Math.min(vw - 16 - w, pl + pw / 2 - w / 2));
				ml = left - pl;
				mr = pl + pw - (left + w);
			}
			// !important: o layout "constrained" dos temas de blocos força margin:auto !important.
			el.style.setProperty('width', w + 'px', 'important');
			el.style.setProperty('margin-left', ml + 'px', 'important');
			el.style.setProperty('margin-right', mr + 'px', 'important');
		}
	}

	var raf = 0;
	function reflow() {
		if (raf) cancelAnimationFrame(raf);
		raf = requestAnimationFrame(function () {
			raf = 0;
			ajustaLargura();
			topoFixo();
		});
	}
	window.addEventListener('resize', reflow);
	// Cabeçalhos que encolhem/aparecem ao rolar mudam a altura fixa.
	window.addEventListener(
		'scroll',
		function () {
			if (raf) return;
			raf = requestAnimationFrame(function () {
				raf = 0;
				topoFixo();
			});
		},
		{ passive: true }
	);

	/* ---------- boot ---------- */

	function boot() {
		ajustaLargura();
		topoFixo();
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
