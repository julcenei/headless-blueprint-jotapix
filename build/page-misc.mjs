import { icon } from './icons.mjs';
import { data, esc, img, waLink } from './data.mjs';
import { contactLines, ctaBand, pageHero, statsBlock } from './layout.mjs';

const c = data.contact;

/* ==========================================================================
   A ELOTEC
   ========================================================================== */
const DIFERENCIAIS = [
  { ico: 'bolt', title: 'Assistência técnica 24h', text: 'Atendimento emergencial para reduzir o tempo de parada da linha de produção.' },
  { ico: 'pin', title: 'Atendimento em todo o Brasil', text: 'Duas unidades — Chapecó (SC) e Toledo (PR) — com deslocamento para outras regiões.' },
  { ico: 'wrench', title: 'Execução própria', text: 'Vulcanização, colagem a frio, revestimento e personalização feitos pela nossa equipe.' },
  { ico: 'clipboardCheck', title: 'Acompanhamento preventivo', text: 'Avaliações periódicas de correias, transportadores, rolos e roletes.' },
  { ico: 'target', title: 'Foco em frigoríficos', text: 'Principal área de atuação, com correias sanitárias e alto padrão de higienização.' },
  { ico: 'users', title: 'Conhecimento de chão de fábrica', text: 'Mais de duas décadas resolvendo problemas reais dentro da indústria.' },
];

export const aElotecPage = {
  file: 'a-elotec.html',
  nav: 'a-elotec',
  title: 'A Elotec | Serviços técnicos em correias desde 2001',
  description:
    'Desde 2001 a Elotec atua com correias transportadoras, instalação, conserto, manutenção e avaliação de sistemas industriais. Assistência 24h, duas unidades e atendimento nacional.',
  image: '/images/servicos/vulcanizacao.webp',
  breadcrumb: [
    { label: 'Início', href: '' },
    { label: 'A Elotec', href: 'a-elotec.html' },
  ],
  body: () => `
  <main id="conteudo">
    ${pageHero({
      title: 'Soluções técnicas construídas',
      accent: 'no chão de fábrica.',
      text: 'Desde 2001 a Elotec atua com correias transportadoras, instalação, conserto, manutenção e avaliação de sistemas industriais.',
      crumb: 'A Elotec',
    })}

    <section class="section">
      <div class="container about-grid">
        <div class="reveal">
          <p class="eyebrow">Nossa história</p>
          <h2 class="h-2 section-title" style="margin-top:1.25rem">
            <span class="t-soft">mais de duas décadas</span>
            <span class="t-strong">dentro da indústria</span>
          </h2>
          <div class="stack" style="margin-top:1.5rem">
            <p class="lead">A Elotec nasceu em 2001 em Chapecó, Santa Catarina, atendendo a demanda das indústrias da região por manutenção rápida e confiável de correias transportadoras.</p>
            <p class="muted">Com o tempo, a atuação se ampliou para o fornecimento de correias em PU, PVC, borracha, nylon e transmissão, além de canecas, rolos, roletes e materiais de revestimento — sempre com a mesma lógica: entender o processo do cliente antes de indicar o material.</p>
            <p class="muted">Hoje a empresa mantém duas unidades, Chapecó (SC) e Toledo (PR), com assistência técnica 24h e atendimento em todo o Brasil. Os frigoríficos seguem como principal área de atuação, com correias sanitárias desenvolvidas para higiene, resistência e confiabilidade.</p>
          </div>
          <div class="trust-row" style="margin-top:2.25rem">
            <p class="trust-badge">${icon('clock', 18, 1.8)} Desde 2001</p>
            <p class="trust-badge">${icon('bolt', 18, 1.8)} Assistência 24h</p>
            <p class="trust-badge">${icon('pin', 18, 1.8)} Atendimento nacional</p>
          </div>
        </div>
        <div class="about-media reveal" style="--i:1">
          <div class="media chamfer-lg"><img src="images/setores/frigorificos.webp" alt="Linha de produção industrial atendida pela Elotec" loading="lazy" width="900" height="760"></div>
        </div>
      </div>
    </section>

    <section class="section section--navy">
      <div class="container">
        <div class="reveal" style="max-width:44rem">
          <p class="eyebrow">Diferenciais</p>
          <h2 class="h-2" style="margin-top:1.25rem">Por que as indústrias chamam a Elotec</h2>
        </div>
        <div class="card-grid" style="margin-top:3rem">
          ${DIFERENCIAIS.map(
            (d, i) => `
          <article class="feature-card reveal" style="--i:${i % 3}">
            <span class="feature-card__icon">${icon(d.ico, 28, 1.6)}</span>
            <h3 class="h-4">${esc(d.title)}</h3>
            <p>${esc(d.text)}</p>
          </article>`
          ).join('')}
        </div>

        ${statsBlock('margin-top:3.5rem')}
      </div>
    </section>

    <section class="section section--alt">
      <div class="container">
        <div class="reveal" style="max-width:44rem">
          <p class="eyebrow">Onde atuamos</p>
          <h2 class="h-2" style="margin-top:1.25rem">Setores atendidos</h2>
          <p class="lead" style="margin-top:1.25rem">Da linha de abate ao elevador de grãos, cada setor tem uma exigência diferente de material, higiene e resistência.</p>
        </div>
        <div class="chips" style="margin-top:2rem">
          ${data.sectors
            .map((s) => `<a class="chip" href="setores.html#${s.slug}">${icon(s.slug, 16, 1.7)} ${esc(s.name)}</a>`)
            .join('')}
        </div>
      </div>
    </section>

    ${ctaBand(
      'Quer conhecer a Elotec <span class="text-accent">de perto?</span>',
      'Agende uma visita técnica ou envie os dados do seu equipamento para uma avaliação inicial da equipe.',
      'Falar com a equipe'
    )}
  </main>`,
};

/* ==========================================================================
   SOLICITAR ORÇAMENTO
   ========================================================================== */
const PASSOS = [
  { t: 'Informe seus dados', d: 'Nome, empresa e um contato direto para retorno.' },
  { t: 'Selecione o produto ou serviço', d: 'Escolha entre correias, componentes, revestimentos ou serviços técnicos.' },
  { t: 'Descreva a aplicação', d: 'Medidas, produto transportado e condições do equipamento.' },
  { t: 'Envie fotos ou arquivos', d: 'Fotos, ficha técnica ou desenho ajudam na avaliação inicial.' },
  { t: 'Aguarde o contato da equipe', d: 'Retornamos com a recomendação técnica e o orçamento.' },
];

const FAQ = [
  {
    q: 'Quanto tempo leva para receber o retorno?',
    a: 'O contato é feito pela equipe técnica assim que a solicitação chega. Para situações emergenciais, use o WhatsApp — a assistência técnica funciona 24h.',
  },
  {
    q: 'Preciso saber a medida exata da correia?',
    a: 'Não. Envie o que tiver: fotos do equipamento, largura aproximada, produto transportado e o tipo de processo. A partir disso a equipe indica o dimensionamento correto.',
  },
  {
    q: 'A Elotec atende fora de Santa Catarina?',
    a: 'Sim. Além das unidades de Chapecó (SC) e Toledo (PR), a Elotec atende indústrias em todo o Brasil.',
  },
  {
    q: 'Vocês fazem apenas o conserto ou também fornecem a correia?',
    a: 'Ambos. A Elotec fornece correias em PU, PVC, borracha, nylon e transmissão, além de canecas, rolos, roletes e materiais de revestimento — e executa a instalação e a manutenção.',
  },
  {
    q: 'Como envio fotos e desenhos técnicos?',
    a: 'Ao enviar o formulário, abrimos uma conversa no WhatsApp com seus dados já preenchidos. Basta anexar os arquivos nessa conversa.',
  },
];

const UFS = ['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'];

export const orcamentoPage = {
  file: 'solicitar-orcamento.html',
  nav: 'orcamento',
  title: 'Solicitar Orçamento | Elotec Serviços Técnicos em Correias',
  description:
    'Envie os dados da sua aplicação e receba uma avaliação técnica da Elotec. Formulário rápido que abre o WhatsApp com as informações já preenchidas.',
  image: '/images/servicos/consultoria.webp',
  breadcrumb: [
    { label: 'Início', href: '' },
    { label: 'Solicitar orçamento', href: 'solicitar-orcamento.html' },
  ],
  body: () => `
  <main id="conteudo">
    ${pageHero({
      title: 'Solicite um orçamento',
      accent: 'sem complicação.',
      text: 'Preencha os campos abaixo. Ao enviar, abrimos uma conversa no WhatsApp com seus dados já preenchidos — é onde você anexa fotos, fichas e desenhos técnicos.',
      crumb: 'Solicitar orçamento',
    })}

    <section class="section" style="padding-bottom:0">
      <div class="container">
        <div class="steps">
          ${PASSOS.map(
            (p, i) => `
          <article class="step reveal" style="--i:${i}">
            <p class="step__num">PASSO ${i + 1}</p>
            <h2 class="h-4">${esc(p.t)}</h2>
            <p>${esc(p.d)}</p>
          </article>`
          ).join('')}
        </div>
      </div>
    </section>

    <section class="section">
      <div class="container form-layout">
        <div class="form-card reveal">
          <div class="form-success" id="form-success" tabindex="-1" role="status">
            ${icon('check', 26, 2.6)}
            <div>
              <h3>Solicitação encaminhada!</h3>
              <p>Abrimos uma conversa no WhatsApp com seus dados preenchidos. Se anexou arquivos, envie-os na conversa. Nossa equipe entrará em contato.</p>
            </div>
          </div>

          <p class="form-alert" id="form-erros" role="alert"></p>

          <h2 class="h-3">Dados da solicitação</h2>
          <p class="muted" style="margin-top:0.6rem;font-size:0.94rem">Campos marcados com <span style="color:var(--accent-ink);font-weight:700">*</span> são obrigatórios.</p>

          <form id="form-orcamento" data-whatsapp="${c.whatsapp}" novalidate style="margin-top:2rem">
            <div class="form-grid">
              <div class="field">
                <label for="nome">Nome <span class="req">*</span></label>
                <input type="text" id="nome" name="nome" autocomplete="name" required>
                <p class="field__error" aria-live="polite"></p>
              </div>
              <div class="field">
                <label for="empresa">Empresa <span class="req">*</span></label>
                <input type="text" id="empresa" name="empresa" autocomplete="organization" required>
                <p class="field__error" aria-live="polite"></p>
              </div>
              <div class="field">
                <label for="telefone">Telefone ou WhatsApp <span class="req">*</span></label>
                <input type="tel" id="telefone" name="telefone" autocomplete="tel" placeholder="(49) 9 9999-0000" required>
                <p class="field__error" aria-live="polite"></p>
              </div>
              <div class="field">
                <label for="email">E-mail <span class="req">*</span></label>
                <input type="email" id="email" name="email" autocomplete="email" required>
                <p class="field__error" aria-live="polite"></p>
              </div>
              <div class="field">
                <label for="cidade">Cidade</label>
                <input type="text" id="cidade" name="cidade" autocomplete="address-level2">
              </div>
              <div class="field">
                <label for="uf">Estado (UF)</label>
                <select id="uf" name="uf">
                  <option value="">Selecione</option>
                  ${UFS.map((uf) => `<option value="${uf}">${uf}</option>`).join('')}
                </select>
              </div>
              <div class="field">
                <label for="setor">Setor industrial</label>
                <select id="setor" name="setor">
                  <option value="">Selecione</option>
                  ${data.sectors.map((s) => `<option value="${esc(s.name)}" data-slug="${s.slug}">${esc(s.name)}</option>`).join('')}
                  <option value="Outro">Outro</option>
                </select>
              </div>
              <div class="field">
                <label for="produto">Produto de interesse</label>
                <select id="produto" name="produto">
                  <option value="">Selecione</option>
                  ${data.products.map((p) => `<option value="${esc(p.name)}" data-slug="${p.slug}">${esc(p.name)}</option>`).join('')}
                </select>
              </div>
              <div class="field">
                <label for="servico">Serviço de interesse</label>
                <select id="servico" name="servico">
                  <option value="">Selecione</option>
                  ${data.services.map((s) => `<option value="${esc(s.name)}" data-slug="${s.slug}">${esc(s.name)}</option>`).join('')}
                </select>
              </div>
              <div class="field field--full">
                <label for="mensagem">Mensagem <span class="req">*</span></label>
                <textarea id="mensagem" name="mensagem" required placeholder="Descreva a aplicação: largura e comprimento da correia, produto transportado, tipo de equipamento, condições de operação e o problema observado."></textarea>
                <p class="field__error" aria-live="polite"></p>
              </div>
            </div>

            <div class="field-note" style="margin-top:1.5rem">
              ${icon('paperclip', 20, 1.8)}
              <p>Anexar fotos, ficha ou desenho técnico (enviados na conversa do WhatsApp).</p>
            </div>

            <div style="margin-top:1.5rem">
              <div class="field">
              <label class="checkbox-field">
                <input type="checkbox" id="lgpd" name="lgpd" required>
                <span>Autorizo o uso dos meus dados para retorno comercial e técnico, conforme a <a href="politica-de-privacidade.html">Política de Privacidade</a>. <span class="req" style="color:var(--accent-ink)">*</span></span>
              </label>
              <p class="field__error" aria-live="polite"></p>
              </div>
            </div>

            <div style="margin-top:2rem;display:flex;flex-wrap:wrap;gap:1rem;align-items:center">
              <button class="btn btn--primary" type="submit">Enviar pelo WhatsApp ${icon('arrowRight', 16, 2.4)}</button>
              <a class="link-arrow" href="mailto:${c.email}">Prefiro enviar por e-mail ${icon('arrowRight', 15, 2.4)}</a>
            </div>
          </form>
        </div>

        <aside class="reveal" style="--i:1">
          <div class="aside-card on-dark">
            <h2 class="h-4">Precisa de atendimento agora?</h2>
            <p class="muted" style="margin-top:0.75rem;font-size:0.94rem">A assistência técnica funciona 24h para emergências que param a produção.</p>
            ${contactLines(waLink())}
            <a class="btn btn--whats btn--block" href="${waLink()}" target="_blank" rel="noopener">${icon('whatsapp', 18, 1.7)} Abrir conversa</a>
          </div>

          <div class="aside-card aside-card--light">
            <h2 class="h-4">Dúvidas frequentes</h2>
            <div style="margin-top:1rem">
              ${FAQ.map(
                (f, i) => `
              <div class="faq-item">
                <button class="faq-item__btn" type="button" aria-expanded="${i === 0}" aria-controls="faq-${i}">
                  ${esc(f.q)}
                  <span class="ico" aria-hidden="true">${icon('plus', 14, 2.6)}</span>
                </button>
                <div class="faq-item__panel" id="faq-${i}"><div><p>${esc(f.a)}</p></div></div>
              </div>`
              ).join('')}
            </div>
          </div>

          <div class="aside-card aside-card--light">
            <h2 class="h-4">Unidades</h2>
            <p style="margin-top:0.9rem;font-size:0.94rem"><strong>Chapecó — SC (matriz)</strong><br>${esc(c.address.street)}<br>${esc(c.address.district)} — CEP ${esc(c.address.zip)}</p>
            <p style="margin-top:1rem;font-size:0.94rem"><strong>Toledo — PR (unidade)</strong><br>Atendimento técnico regional.</p>
          </div>
        </aside>
      </div>
    </section>
  </main>`,
};

/* ==========================================================================
   POLÍTICA DE PRIVACIDADE (LGPD)
   ========================================================================== */
export const privacidadePage = {
  file: 'politica-de-privacidade.html',
  nav: '',
  title: 'Política de Privacidade | Elotec Correias',
  description: 'Como a Elotec coleta, utiliza e protege os dados pessoais informados no site, conforme a Lei Geral de Proteção de Dados (LGPD).',
  breadcrumb: [
    { label: 'Início', href: '' },
    { label: 'Política de Privacidade', href: 'politica-de-privacidade.html' },
  ],
  body: () => `
  <main id="conteudo">
    ${pageHero({
      title: 'Política de',
      accent: 'Privacidade',
      text: 'Esta política descreve como a Elotec trata os dados pessoais informados por meio deste site, em conformidade com a Lei nº 13.709/2018 (LGPD).',
      crumb: 'Política de Privacidade',
    })}

    <section class="section">
      <div class="container prose">
        <h2>1. Quem é o controlador dos dados</h2>
        <p>Elotec Serviços Técnicos em Correias, com sede na ${esc(c.address.street)}, ${esc(c.address.district)}, ${esc(c.address.city)}/${esc(c.address.state)}, CEP ${esc(c.address.zip)}. Contato: <a href="mailto:${c.email}">${esc(c.email)}</a>.</p>

        <h2>2. Quais dados coletamos</h2>
        <p>Coletamos apenas os dados que você informa espontaneamente no formulário de solicitação de orçamento:</p>
        <ul>
          <li>Nome e empresa;</li>
          <li>Telefone ou WhatsApp e e-mail;</li>
          <li>Cidade e estado;</li>
          <li>Setor industrial, produto e serviço de interesse;</li>
          <li>Mensagem e arquivos que você optar por enviar na conversa do WhatsApp.</li>
        </ul>

        <h2>3. Para que utilizamos</h2>
        <p>Os dados são usados exclusivamente para responder à sua solicitação, elaborar orçamentos, prestar atendimento técnico e manter o histórico do relacionamento comercial. Não vendemos nem cedemos dados a terceiros para fins publicitários.</p>

        <h2>4. Base legal</h2>
        <p>O tratamento se apoia no consentimento fornecido no envio do formulário (art. 7º, I da LGPD) e na execução de procedimentos preliminares a contrato a pedido do titular (art. 7º, V).</p>

        <h2>5. Compartilhamento</h2>
        <p>O envio do formulário abre uma conversa no aplicativo WhatsApp, operado pela Meta Platforms, que possui política de privacidade própria. Fora isso, os dados podem ser acessados apenas pela equipe interna da Elotec envolvida no atendimento.</p>

        <h2>6. Armazenamento e segurança</h2>
        <p>Este site é estático e não mantém banco de dados: nenhuma informação preenchida no formulário fica armazenada no servidor do site. As mensagens recebidas são guardadas pelo tempo necessário ao atendimento e às obrigações legais, com medidas razoáveis de proteção contra acesso não autorizado.</p>

        <h2>7. Cookies</h2>
        <p>Não utilizamos cookies de rastreamento ou publicidade neste site. Recursos de navegação usam apenas armazenamento local do navegador quando estritamente necessário.</p>

        <h2>8. Seus direitos</h2>
        <p>Você pode solicitar a qualquer momento a confirmação do tratamento, o acesso, a correção, a portabilidade, a anonimização ou a exclusão dos seus dados, além de revogar o consentimento. Basta escrever para <a href="mailto:${c.email}">${esc(c.email)}</a> ou ligar para ${esc(c.phoneAdmin)}.</p>

        <h2>9. Atualizações</h2>
        <p>Esta política pode ser revisada periodicamente. A versão vigente é sempre a publicada nesta página.</p>
      </div>
    </section>
  </main>`,
};

/* ==========================================================================
   404
   ========================================================================== */
export const naoEncontradaPage = {
  file: '404.html',
  nav: '',
  noindex: true,
  title: 'Página não encontrada | Elotec Correias',
  description: 'A página que você procurava não existe ou foi movida.',
  breadcrumb: [],
  body: () => `
  <main id="conteudo">
    <section class="error-page">
      <div class="container">
        <p class="error-code">404</p>
        <h1 class="h-2" style="margin-top:1.5rem">Esta página saiu da linha de produção</h1>
        <p class="lead" style="margin-top:1.25rem;max-width:38rem;margin-inline:auto">O endereço acessado não existe ou foi movido. Use os atalhos abaixo para encontrar o que procura.</p>
        <div style="display:flex;flex-wrap:wrap;gap:1rem;justify-content:center;margin-top:2.5rem">
          <a class="btn btn--primary" href="index.html">Voltar para a home ${icon('arrowRight', 16, 2.4)}</a>
          <a class="btn btn--ghost-light" href="produtos.html">Ver produtos</a>
          <a class="btn btn--ghost-light" href="solicitar-orcamento.html">Solicitar orçamento</a>
        </div>
      </div>
    </section>
  </main>`,
};
