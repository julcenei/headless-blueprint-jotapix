/* JPX Eleições 2026 · landing page */
(function () {
	'use strict';

	/* ================= CONFIGURE AQUI ================= */
	var CONFIG = {
		// Número do WhatsApp com DDI e DDD, só dígitos. Ex.: '5549999999999'.
		// Vazio: o WhatsApp abre para o visitante escolher o contato.
		whatsapp: '5549991578745',
		precoPortal: 1490,
		precoParceiro: 790
	};
	/* ================================================== */

	var semMovimento = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
	var $ = function (s, el) { return (el || document).querySelector(s); };
	var $$ = function (s, el) { return Array.prototype.slice.call((el || document).querySelectorAll(s)); };

	function brl(v) {
		return 'R$ ' + v.toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
	}
	function pct(v, d) {
		return v.toLocaleString('pt-BR', { minimumFractionDigits: d, maximumFractionDigits: d });
	}

	/* ---------- WhatsApp ---------- */
	function linkWhats(plano) {
		var txt = plano === 'Parceiro'
			? 'Olá! Sou parceiro JPX e quero o JPX Eleições 2026 (plano Parceiro, ' + brl(CONFIG.precoParceiro) + ') no meu portal.'
			: 'Olá! Quero o JPX Eleições 2026 (plano Portal, ' + brl(CONFIG.precoPortal) + ') no meu portal.';
		return 'https://wa.me/' + CONFIG.whatsapp.replace(/\D/g, '') + '?text=' + encodeURIComponent(txt);
	}
	$$('[data-plano]').forEach(function (a) {
		a.href = linkWhats(a.getAttribute('data-plano'));
	});

	/* ---------- topo ---------- */
	var topo = $('#topo');
	function rolou() {
		topo.classList.toggle('is-rolado', window.scrollY > 10);
	}
	window.addEventListener('scroll', rolou, { passive: true });
	rolou();

	/* ---------- contadores ---------- */
	function contar(el) {
		if (el.__contado) return;
		el.__contado = true;
		var alvo = parseFloat(el.getAttribute('data-alvo')) || 0;
		var dec = parseInt(el.getAttribute('data-dec') || '0', 10);
		var suf = el.getAttribute('data-suf') || '';
		if (semMovimento) {
			el.textContent = pct(alvo, dec) + suf;
			return;
		}
		var t0 = performance.now();
		var dur = 1400;
		(function passo(t) {
			var k = Math.min(1, (t - t0) / dur);
			var e = 1 - Math.pow(1 - k, 3);
			el.textContent = pct(alvo * e, dec) + suf;
			if (k < 1) requestAnimationFrame(passo);
		})(t0);
	}

	/* ---------- revelar ao rolar ---------- */
	var obs = 'IntersectionObserver' in window
		? new IntersectionObserver(function (ents) {
			ents.forEach(function (e) {
				if (!e.isIntersecting) return;
				e.target.classList.add('is-visto');
				$$('.contador', e.target).forEach(contar);
				if (e.target.contains($('#mapa'))) pintarMapa();
				obs.unobserve(e.target);
			});
		}, { threshold: 0.2 })
		: null;
	$$('.revela, .numeros').forEach(function (el) {
		if (obs) obs.observe(el);
		else {
			el.classList.add('is-visto');
			$$('.contador', el).forEach(contar);
		}
	});

	/* ---------- hero: navegação pelos resultados detalhados ---------- */
	// [iniciais, nome, partido, %, cor, etiqueta]
	var HERO = [
		{ olho: 'Eleições 2026 · 1º turno', titulo: 'Presidente · Pinhalzinho', aba: 0, chip: 1, meta: '<b>100%</b> das seções totalizadas · Fonte: TSE',
			c: [['AR', 'Ana Ribeiro', 'Partido A', 61.3, 'a', '2º turno'], ['CM', 'Carlos Menezes', 'Partido B', 28.7, 'b', '2º turno'], ['JP', 'Júlia Prado', 'Partido C', 4.8, 'c'], ['RS', 'Rui Souza', 'Partido D', 3.2, 'd']] },
		{ olho: 'Eleições 2026 · 1º turno', titulo: 'Governador · São Lourenço do Oeste', aba: 1, chip: 2, meta: '<b>100%</b> das seções totalizadas · 61 seções',
			c: [['MT', 'Marcos Tavares', 'Partido B', 52.6, 'b', 'Eleito'], ['LS', 'Lia Santos', 'Partido A', 33.9, 'a'], ['PV', 'Paulo Viana', 'Partido C', 13.5, 'c']] },
		{ olho: 'Eleições 2026 · 1º turno', titulo: 'Deputado Estadual · Pinhalzinho', aba: 2, chip: 1, meta: '<b>398</b> candidatos · 40 vagas · busca por nome',
			c: [['FL', 'Fábio Luz', 'Partido B', 21.9, 'b', 'Eleito'], ['MV', 'Marcos Vieira', 'Partido C', 9.8, 'c'], ['AS', 'Alice Silva', 'Partido D', 9.5, 'd', 'Eleito'], ['MN', 'Mauro Nadal', 'Partido A', 9.4, 'a', 'Eleito']] },
		{ olho: 'Boletim de urna · Seção 50', titulo: 'Paróquia Santo Antônio · Zona 66', aba: 3, chip: 1, meta: '<b>374</b> eleitores aptos · comparecimento 85%',
			c: [['AR', 'Ana Ribeiro', '190 votos', 61.3, 'a'], ['CM', 'Carlos Menezes', '89 votos', 28.7, 'b'], ['JP', 'Júlia Prado', '15 votos', 4.8, 'c'], ['RS', 'Rui Souza', '10 votos', 3.2, 'd']] }
	];
	var heroI = 0;
	var lista = $('#heroCands');
	var painelHero = $('#painelHero');

	function mostrarHero(d) {
		$$('.aba', painelHero).forEach(function (a, i) { a.classList.toggle('is-on', i === d.aba); });
		$$('.chip', painelHero).forEach(function (a, i) { a.classList.toggle('is-on', i === d.chip); });
		var cab = [$('#heroTitulo'), $('#heroOlho'), $('#heroMeta')];
		cab.forEach(function (el) { el.style.opacity = 0; });
		setTimeout(function () {
			$('#heroTitulo').textContent = d.titulo;
			$('#heroOlho').textContent = d.olho;
			$('#heroMeta').innerHTML = d.meta;
			cab.forEach(function (el) { el.style.opacity = 1; });
		}, 200);
		lista.innerHTML = d.c.map(function (c) {
			var tag = c[5] ? ' <i class="tag-mini' + (c[5] === '2º turno' ? ' tag-mini--t' : '') + '">' + c[5] + '</i>' : '';
			return '<li class="cand"><span class="avatar avatar--' + c[4] + '">' + c[0] + '</span>' +
				'<span class="cand__nome">' + c[1] + tag + ' <small>' + c[2] + '</small></span>' +
				'<span class="cand__pct">' + pct(c[3], 1) + '%</span><span class="cand__bar"><i></i></span></li>';
		}).join('');
		// As barras crescem até o valor final a cada troca.
		requestAnimationFrame(function () {
			requestAnimationFrame(function () {
				$$('.cand', lista).forEach(function (li, i) {
					$('.cand__bar i', li).style.width = Math.min(100, d.c[i][3] * 1.4) + '%';
				});
			});
		});
	}
	$$('#heroTitulo, #heroOlho, #heroMeta').forEach(function (el) { el.style.transition = 'opacity .25s'; });
	mostrarHero(HERO[0]);
	if (!semMovimento) {
		setInterval(function () {
			if (document.hidden) return;
			heroI = (heroI + 1) % HERO.length;
			mostrarHero(HERO[heroI]);
		}, 4200);
	}

	/* ---------- passos (como funciona) ---------- */
	var passos = $('#passos');
	var botoes = $$('.passo', passos);
	var telas = $$('.tela', passos);
	var URLS = ['seuportal.com.br/wp-admin/plugin-install.php', 'seuportal.com.br/wp-admin/options-general.php?page=jpx-eleicoes', 'seuportal.com.br/wp-admin/post-new.php?post_type=page', 'seuportal.com.br/eleicoes-2026'];
	var DUR = 5500;
	var atual = 0;
	var timer = null;
	var digitando = null;

	function digitar(el, texto, vel) {
		clearInterval(digitando);
		el.textContent = '';
		if (semMovimento) {
			el.textContent = texto;
			return;
		}
		var i = 0;
		digitando = setInterval(function () {
			el.textContent = texto.slice(0, ++i);
			if (i >= texto.length) clearInterval(digitando);
		}, vel);
	}

	function ir(n) {
		atual = n;
		botoes.forEach(function (b, i) {
			b.classList.toggle('is-on', i === n);
			b.setAttribute('aria-selected', i === n ? 'true' : 'false');
			// Reinicia a barrinha de progresso.
			var barra = $('.passo__barra', b);
			barra.style.animation = 'none';
			void barra.offsetWidth;
			barra.style.animation = '';
		});
		telas.forEach(function (t, i) {
			t.classList.remove('is-on');
			if (i === n) {
				void t.offsetWidth; // reinicia as animações CSS da tela
				t.classList.add('is-on');
			}
		});
		$('#passoUrl').textContent = URLS[n];
		if (n === 1) digitar($('#digitaCidades'), 'pinhalzinho, sao-lourenco-do-oeste', 55);
		if (n === 2) digitar($('#digitaShort'), '[eleicoes_tse_painel]', 85);
		if (n === 3) {
			$$('.contador', telas[3]).forEach(function (c) {
				c.__contado = false;
				contar(c);
			});
		}
		agendar();
	}

	function agendar() {
		clearTimeout(timer);
		if (semMovimento || passos.classList.contains('is-pausado')) return;
		timer = setTimeout(function () {
			ir((atual + 1) % botoes.length);
		}, DUR);
	}

	botoes.forEach(function (b, i) {
		b.addEventListener('click', function () {
			ir(i);
		});
	});
	passos.addEventListener('mouseenter', function () {
		passos.classList.add('is-pausado');
		clearTimeout(timer);
	});
	passos.addEventListener('mouseleave', function () {
		passos.classList.remove('is-pausado');
		agendar();
	});
	passos.style.setProperty('--dur', DUR / 1000 + 's');
	// Começa quando a seção aparece na tela.
	if ('IntersectionObserver' in window) {
		var obsPassos = new IntersectionObserver(function (e) {
			if (e[0].isIntersecting) {
				ir(0);
				obsPassos.disconnect();
			}
		}, { threshold: 0.35 });
		obsPassos.observe(passos);
	} else {
		ir(0);
	}

	/* ---------- hemiciclo (40 cadeiras) ---------- */
	(function hemiciclo() {
		var svg = $('#hemi');
		if (!svg) return;
		var partidos = [[16, '#1e3a8a'], [5, '#dc2626'], [4, '#0e7490'], [4, '#059669'], [3, '#0ea5e9'], [3, '#eab308'], [2, '#65a30d'], [1, '#f97316'], [1, '#9333ea'], [1, '#be123c']];
		var n = 40;
		var filas = 5;
		var r0 = 0.5;
		var raios = [];
		for (var i = 0; i < filas; i++) raios.push(r0 + (1 - r0) * i / (filas - 1));
		var soma = raios.reduce(function (a, b) { return a + b; }, 0);
		var qtd = raios.map(function (r) { return Math.floor(n * r / soma); });
		var tot = qtd.reduce(function (a, b) { return a + b; }, 0);
		for (var f = filas - 1; tot < n; f = (f - 1 + filas) % filas) { qtd[f]++; tot++; }
		var pos = [];
		raios.forEach(function (r, i) {
			for (var j = 0; j < qtd[i]; j++) {
				var a = Math.PI * (1 - j / (qtd[i] - 1));
				pos.push([a, r]);
			}
		});
		pos.sort(function (p, q) { return Math.abs(p[0] - q[0]) > 1e-9 ? q[0] - p[0] : p[1] - q[1]; });
		var html = '';
		var k = 0;
		partidos.forEach(function (p) {
			for (var s = 0; s < p[0]; s++, k++) {
				var a = pos[k][0];
				var r = pos[k][1];
				html += '<circle cx="' + (110 + Math.cos(a) * r * 100).toFixed(1) + '" cy="' + (108 - Math.sin(a) * r * 100).toFixed(1) + '" r="7" fill="' + p[1] + '" style="transition-delay:' + (k * 28) + 'ms"/>';
			}
		});
		svg.innerHTML = html;
	})();

	/* ---------- mapa do Brasil (grade de UFs) ---------- */
	var GRADE = { rr: [1, 0], ap: [3, 0], am: [1, 1], pa: [2, 1], ma: [3, 1], ce: [4, 1], rn: [5, 1], ac: [0, 2], ro: [1, 2], to: [2, 2], pi: [3, 2], pe: [4, 2], pb: [5, 2], mt: [1, 3], go: [2, 3], ba: [3, 3], se: [4, 3], al: [5, 3], ms: [1, 4], df: [2, 4], mg: [3, 4], es: [4, 4], sp: [2, 5], rj: [3, 5], pr: [2, 6], sc: [2, 7], rs: [2, 8] };
	// Resultado fictício: [vencedor, % do vencedor].
	var VENC = { rr: ['a', 70], ap: ['b', 46], am: ['b', 48], pa: ['b', 51], ma: ['b', 64], ce: ['b', 63], rn: ['b', 60], ac: ['a', 65], ro: ['a', 67], to: ['a', 50], pi: ['b', 71], pe: ['b', 63], pb: ['b', 61], mt: ['a', 65], go: ['a', 54], ba: ['b', 66], se: ['b', 63], al: ['b', 55], ms: ['a', 59], df: ['a', 51], mg: ['a', 48], es: ['a', 55], sp: ['a', 52], rj: ['a', 53], pr: ['a', 60], sc: ['a', 67], rs: ['a', 56] };
	(function montarMapa() {
		var m = $('#mapa');
		if (!m) return;
		m.innerHTML = Object.keys(GRADE).map(function (uf, i) {
			var g = GRADE[uf];
			return '<span data-uf="' + uf + '" style="grid-column:' + (g[0] + 1) + ';grid-row:' + (g[1] + 1) + ';transition-delay:' + i * 35 + 'ms">' + uf.toUpperCase() + '</span>';
		}).join('');
	})();
	function pintarMapa() {
		$$('#mapa span').forEach(function (s, i) {
			var v = VENC[s.getAttribute('data-uf')];
			var alfa = Math.max(0.38, Math.min(1, (v[1] - 30) / 40));
			var cor = v[0] === 'a' ? '30,58,138' : '220,38,38';
			setTimeout(function () {
				s.style.backgroundColor = 'rgba(' + cor + ',' + alfa.toFixed(2) + ')';
			}, semMovimento ? 0 : 500 + i * 45);
		});
	}

	/* ---------- calculadora ---------- */
	var n = 3;
	function calc() {
		$('#calcN').textContent = n;
		$('#calcPortal').textContent = brl(n * CONFIG.precoPortal);
		$('#calcParceiro').textContent = brl(n * CONFIG.precoParceiro);
	}
	$('#calcMenos').addEventListener('click', function () {
		n = Math.max(1, n - 1);
		calc();
	});
	$('#calcMais').addEventListener('click', function () {
		n = Math.min(50, n + 1);
		calc();
	});
	calc();
})();
