#!/usr/bin/env node
/**
 * Gera uma versão SINGLE-FILE do site (dist/elotec-single.html).
 *
 * Serve para publicar o projeto como Artifact / link compartilhável: as 8
 * páginas viram rotas em hash (#/produtos/correia-pvc), o CSS e o JS são
 * embutidos e as 22 fotos entram como data URIs — uma única vez cada,
 * atribuídas em runtime a partir de um mapa (evita duplicar base64 nas
 * imagens que aparecem em vários lugares).
 *
 * Uso: `node build/build-artifact.mjs`
 */
import { readFileSync, writeFileSync, mkdirSync } from 'node:fs';
import { join } from 'node:path';
import { ROOT } from './data.mjs';
import { header, footer } from './layout.mjs';
import { homePage } from './page-home.mjs';
import { produtosPage, servicosPage, setoresPage } from './page-catalog.mjs';
import { aElotecPage, orcamentoPage, privacidadePage, naoEncontradaPage } from './page-misc.mjs';

const PAGES = [homePage, aElotecPage, produtosPage, servicosPage, setoresPage, orcamentoPage, privacidadePage, naoEncontradaPage];

const routeOf = (file) => (file === 'index.html' ? '/' : '/' + file.replace(/\.html$/, ''));

/* ---------- 1. Corpo de cada página vira uma rota ---------- */
const routes = PAGES.map((page) => {
  const route = routeOf(page.file);
  let html = page.body();

  // Só pode existir um #conteudo no documento: ele passa a ser o wrapper das rotas
  html = html.replace(' id="conteudo"', '');

  // Âncoras internas da página (barra de âncoras) passam a carregar a rota
  html = html.replace(/href="#([a-z0-9-]+)"/g, (m, id) => `href="#${route === '/' ? '' : route}/${id}"`);

  return { page, route, html };
});

/* ---------- 2. Reescreve todos os links .html para rotas em hash ---------- */
function toHashLinks(html) {
  return html
    .replace(/href="index\.html"/g, 'href="#/"')
    .replace(/href="([a-z0-9-]+)\.html\?([^"]+)"/g, 'href="#/$1?$2"')
    .replace(/href="([a-z0-9-]+)\.html#([a-z0-9-]+)"/g, 'href="#/$1/$2"')
    .replace(/href="([a-z0-9-]+)\.html"/g, 'href="#/$1"');
}

/* ---------- 3. Imagens: cada arquivo entra uma única vez como data URI ---------- */
const imgMap = new Map();
function collectImages(html) {
  return html.replace(/<img([^>]*?)src="(images\/[^"]+)"/g, (m, attrs, path) => {
    if (!imgMap.has(path)) {
      const b64 = readFileSync(join(ROOT, path)).toString('base64');
      imgMap.set(path, `data:image/webp;base64,${b64}`);
    }
    // src vazio dispararia requisição à própria página; o script preenche depois
    return `<img${attrs}data-img="${path}"`;
  });
}

const headerHtml = collectImages(toHashLinks(header(homePage)));
// o footer traz a tag <script src="js/main.js">: aqui o JS já vai embutido
const footerHtml = collectImages(toHashLinks(footer())).replace(/<script src="js\/main\.js" defer><\/script>/, '');
const routesHtml = routes
  .map((r) => {
    const body = collectImages(toHashLinks(r.html));
    const active = r.route === '/';
    return `<div class="route${active ? ' is-active' : ''}" data-route="${r.route}" data-title="${r.page.title.replace(/"/g, '&quot;')}"${active ? '' : ' hidden'}>${body}</div>`;
  })
  .join('\n');

/* ---------- 4. Monta o arquivo ---------- */
const css = readFileSync(join(ROOT, 'css', 'style.css'), 'utf8');
const js = readFileSync(join(ROOT, 'js', 'main.js'), 'utf8');

const router = `
/* Roteador em hash da versão single-file: mostra uma rota por vez,
   rola até a âncora quando houver e mantém o título do documento. */
(function () {
  var routes = Array.prototype.slice.call(document.querySelectorAll('.route'));
  var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function parse() {
    var raw = (location.hash || '#/').replace(/^#/, '');
    var query = raw.split('?')[1] || '';
    var parts = raw.split('?')[0].split('/').filter(Boolean);
    return { route: parts.length ? '/' + parts[0] : '/', anchor: parts[1] || '', query: query };
  }

  function apply() {
    var p = parse();
    var target = routes.filter(function (r) { return r.getAttribute('data-route') === p.route; })[0];
    if (!target) target = routes.filter(function (r) { return r.getAttribute('data-route') === '/404'; })[0] || routes[0];

    routes.forEach(function (r) {
      r.classList.toggle('is-active', r === target);
      r.hidden = r !== target;
    });
    document.title = target.getAttribute('data-title') || document.title;

    // marca o item de navegação correspondente
    document.querySelectorAll('.nav__link, .mobile-menu__top a').forEach(function (a) {
      var href = a.getAttribute('href');
      if (!href) return;
      if (href === '#' + p.route) a.setAttribute('aria-current', 'page');
      else a.removeAttribute('aria-current');
    });

    requestAnimationFrame(function () {
      var el = p.anchor && document.getElementById(p.anchor);
      if (el) el.scrollIntoView({ behavior: reduce ? 'auto' : 'smooth', block: 'start' });
      else window.scrollTo({ top: 0, behavior: 'auto' });
    });
  }

  window.addEventListener('hashchange', apply);
  apply();
})();`;

const html = `<title>Elotec Correias</title>
<meta name="description" content="Redesign premium do site da Elotec — Serviços Técnicos em Correias. Versão navegável em arquivo único.">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700;800&family=Cabin:ital,wght@0,400;0,500;0,600;0,700;1,400&display=swap" rel="stylesheet">
<style>
${css}

/* --- específico da versão single-file: uma rota visível por vez --- */
.route { display: none; }
.route.is-active { display: block; }
</style>

<a class="skip-link" href="#conteudo">Ir para o conteúdo</a>
${headerHtml}
<div id="conteudo">
${routesHtml}
</div>
${footerHtml}

<script>window.__ELOTEC_IMG = ${JSON.stringify(Object.fromEntries(imgMap))};</script>
<script>
/* Preenche as fotos a partir do mapa de data URIs (cada arquivo embutido uma vez). */
document.querySelectorAll('img[data-img]').forEach(function (img) {
  var src = window.__ELOTEC_IMG[img.getAttribute('data-img')];
  if (src) img.src = src;
});
</script>
<script>
${js}
</script>
<script>
${router}
</script>`;

mkdirSync(join(ROOT, 'dist'), { recursive: true });
const out = join(ROOT, 'dist', 'elotec-single.html');
writeFileSync(out, html, 'utf8');
console.log(`${out}\n${(html.length / 1024 / 1024).toFixed(2)} MB · ${imgMap.size} imagens embutidas · ${routes.length} rotas`);
