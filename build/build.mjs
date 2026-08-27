#!/usr/bin/env node
/**
 * Gerador do site estático da Elotec.
 *
 * Por que um gerador? O entregável precisa ser HTML/CSS/JS puro, aberto
 * direto do sistema de arquivos (file://) — o que impede carregar o
 * `dados.json` por fetch. Então o conteúdo real é injetado aqui, em tempo
 * de geração, e o resultado é HTML estático comum, sem dependências.
 *
 * Uso: `node build/build.mjs`
 */
import { writeFileSync } from 'node:fs';
import { join } from 'node:path';
import { ROOT } from './data.mjs';
import { head, header, footer } from './layout.mjs';
import { homePage } from './page-home.mjs';
import { produtosPage, servicosPage, setoresPage } from './page-catalog.mjs';
import { aElotecPage, orcamentoPage, privacidadePage, naoEncontradaPage } from './page-misc.mjs';

const PAGES = [
  homePage,
  aElotecPage,
  produtosPage,
  servicosPage,
  setoresPage,
  orcamentoPage,
  privacidadePage,
  naoEncontradaPage,
];

let total = 0;
for (const page of PAGES) {
  const html = head(page) + header(page) + page.body() + footer();
  writeFileSync(join(ROOT, page.file), html, 'utf8');
  total += html.length;
  console.log(`  ✓ ${page.file.padEnd(30)} ${(html.length / 1024).toFixed(1)} KB`);
}

// sitemap.xml simples com as páginas indexáveis
const SITE_URL = 'https://www.elotec.ind.br';
const urls = PAGES.filter((p) => !p.noindex)
  .map((p) => `  <url><loc>${SITE_URL}/${p.file === 'index.html' ? '' : p.file}</loc></url>`)
  .join('\n');
writeFileSync(
  join(ROOT, 'sitemap.xml'),
  `<?xml version="1.0" encoding="UTF-8"?>\n<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">\n${urls}\n</urlset>\n`,
  'utf8'
);
writeFileSync(join(ROOT, 'robots.txt'), `User-agent: *\nAllow: /\n\nSitemap: ${SITE_URL}/sitemap.xml\n`, 'utf8');

console.log(`\n${PAGES.length} páginas geradas (${(total / 1024).toFixed(0)} KB de HTML) + sitemap.xml + robots.txt`);
