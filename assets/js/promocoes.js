/* Mais Viagens V18 | Promoções. Token privado permanece fora da pasta pública da HostGator. */
(function () {
  'use strict';
  var conf = (window.MAIS_VIAGENS || {}).travelpayouts || {};
  var contato = ((window.MAIS_VIAGENS || {}).contato || {}).whatsapp || '';
  var lista = document.getElementById('ofertas-automaticas');
  var estado = document.getElementById('status-ofertas');
  var origem = document.querySelector('[data-origem]');
  var etiquetas = {FLN:'Florianópolis', GRU:'São Paulo', GIG:'Rio de Janeiro', BSB:'Brasília'};
  var tipos = {passagens:'Passagens', hoteis:'Hotéis', passeios:'Passeios', carros:'Carros', traslados:'Traslados', esim:'Internet/eSIM'};

  function escapeHtml(valor) {
    return String(valor == null ? '' : valor).replace(/[&<>"']/g, function (c) {
      return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c];
    });
  }
  function urlSegura(valor, hostRestrito) {
    try {
      var parsed = new URL(valor);
      if (parsed.protocol !== 'https:') return '';
      if (hostRestrito && parsed.hostname !== hostRestrito) return '';
      return parsed.href;
    } catch (_) { return ''; }
  }
  function whatsapp(mensagem) { return 'https://wa.me/' + String(contato).replace(/\D/g,'') + '?text=' + encodeURIComponent(mensagem); }
  function medicao(evento, dados) { window.dataLayer = window.dataLayer || []; window.dataLayer.push(Object.assign({event:evento, pagina:'promocoes'}, dados)); }
  function formatarValor(numero) { return new Intl.NumberFormat('pt-BR',{style:'currency',currency:'BRL',maximumFractionDigits:0}).format(numero); }
  function dataBr(texto) {
    if (!texto || Number.isNaN(Date.parse(texto))) return '';
    return new Intl.DateTimeFormat('pt-BR', {day:'2-digit',month:'short',year:'numeric',timeZone:'UTC'}).format(new Date(texto));
  }
  var pedido = 0;
  function mostrarStatus(texto) { estado.hidden = false; estado.textContent = texto; lista.hidden = true; lista.innerHTML = ''; }
  function cartaoVoo(o) {
    var partida = etiquetas[o.origin] || o.origin;
    var destino = o.destination_name || o.destination;
    var mensagem = 'Olá, Mais Viagens! Vi uma oportunidade de passagem de ' + partida + ' para ' + destino +
      ', saída em ' + dataBr(o.departure_at) + ', com preço de referência ' + formatarValor(o.price) + '. Gostaria de verificar a disponibilidade e as condições.';
    var linkParceiro = urlSegura(o.partner_url || '');
    return '<article class="promo-oferta"><div class="promo-topo"><span class="promo-categoria">Passagem aérea</span></div>' +
      '<div class="promo-corpo"><h3>' + escapeHtml(partida) + ' <span class="destaque">→</span> ' + escapeHtml(destino) + '</h3>' +
      '<p class="promo-rota">' + escapeHtml(o.origin) + ' → ' + escapeHtml(o.destination) + '</p>' +
      '<div class="promo-valor"><small>Tarifa de referência encontrada</small>' + escapeHtml(formatarValor(o.price)) + '</div>' +
      '<div class="promo-data"><span>Saída: ' + escapeHtml(dataBr(o.departure_at)) + '</span>' +
      (o.return_at ? '<span>Volta: ' + escapeHtml(dataBr(o.return_at)) + '</span>' : '<span>Somente ida</span>') +
      '</div><p>Valor sujeito a alteração; pode não incluir todas as taxas ou bagagens. Confirme antes de reservar.</p>' +
      '<div class="promo-acoes"><a class="botao botao-primario botao-whats" href="' + escapeHtml(whatsapp(mensagem)) + '" target="_blank" rel="noopener noreferrer" data-evento-promo="whatsapp_voo">Consultar esta tarifa</a>' +
      (linkParceiro ? '<a class="promo-link" href="' + escapeHtml(linkParceiro) + '" target="_blank" rel="noopener noreferrer sponsored" data-evento-promo="parceiro_voo">Conferir no parceiro ↗</a>' : '') +
      '</div></div></article>';
  }
  async function obterOfertas() {
    if (!lista || !estado || !origem) return;
    var id = ++pedido;
    var cidade = origem.value;
    mostrarStatus('Consultando oportunidades de passagens para ' + (etiquetas[cidade] || cidade) + '…');
    var controle = typeof AbortController !== 'undefined' ? new AbortController() : null;
    var limite = controle ? setTimeout(function () { controle.abort(); }, 11000) : null;
    try {
      var resp = await fetch('api/promocoes.php?origem=' + encodeURIComponent(cidade), {headers:{Accept:'application/json'}, signal:controle ? controle.signal : undefined});
      if (!resp.ok) throw new Error('Falha no serviço de ofertas');
      var result = await resp.json();
      if (pedido !== id) return;
      var ofertas = Array.isArray(result.offers) ? result.offers : [];
      if (result.status === 'stale') { estado.hidden = false; estado.textContent = 'A consulta está temporariamente indisponível. Abaixo estão referências anteriores; confirme os valores antes de reservar.'; }
      if (!ofertas.length) {
        var texto = result.status === 'pending'
          ? 'Ativação pendente: configure o token privado da API Travelpayouts na HostGator para liberar a vitrine de tarifas. O buscador acima já pode ser usado.'
          : 'Não há tarifas promocionais verificadas para este embarque neste momento. Fale com nossa equipe para consultar novas datas e destinos.';
        mostrarStatus(texto);
        return;
      }
      lista.innerHTML = ofertas.map(cartaoVoo).join('');
      lista.hidden = false;
      if (result.status !== 'stale') {
        estado.hidden = false;
        estado.textContent = 'Tarifas de pesquisas anteriores da Aviasales, atualizadas pelo site em ' + (result.updated_at ? new Date(result.updated_at).toLocaleString('pt-BR',{dateStyle:'short',timeStyle:'short'}) : 'horário não informado') + '. Preços e disponibilidade sujeitos a confirmação.';
      }
      medicao('promocoes_voos_carregados', {origem:cidade,quantidade:ofertas.length});
    } catch (e) {
      if (pedido === id) mostrarStatus('As tarifas automáticas estão indisponíveis no momento. Você pode consultar preços e condições com nossa equipe pelo WhatsApp.');
    } finally { if (limite) clearTimeout(limite); }
  }
  if (origem) { origem.addEventListener('change', obterOfertas); obterOfertas(); }

  // Links manuais já afiliados continuam compatíveis com versões anteriores.
  // O novo catálogo é convertido pelo PHP usando somente URLs cadastradas pela equipe.
  var manuais = Array.isArray(conf.promocoes) ? conf.promocoes.filter(function (p) { return p && tipos[p.tipo] && urlSegura(p.url); }) : [];
  var remotas = [];
  var area = document.getElementById('promocoes-selecionadas');
  var grade = document.getElementById('promo-selecionadas-grade');
  var vazio = document.getElementById('promo-vazio-filtro');
  var filtroAtual = 'todas';
  function cartaoManual(p) {
    var img = /^assets\/img\/[\w-]+\.(?:jpg|jpeg|png|webp)$/i.test(p.imagem || '') ? p.imagem : '';
    var nome = p.titulo || 'Promoção de viagem';
    return '<article class="promo-oferta">' + (img ? '<img class="promo-imagem" loading="lazy" src="' + escapeHtml(img) + '" alt="' + escapeHtml(nome) + '">' : '') +
      '<div class="promo-topo"><span class="promo-categoria">' + escapeHtml(tipos[p.tipo]) + '</span></div><div class="promo-corpo">' +
      '<h3>' + escapeHtml(nome) + '</h3><p>' + escapeHtml(p.descricao || 'Consulte as condições no parceiro.') + '</p>' +
      (p.preco ? '<div class="promo-valor">' + escapeHtml(p.preco) + '</div>' : '') +
      (p.validade ? '<div class="promo-data">' + escapeHtml(p.validade) + '</div>' : '') +
      '<div class="promo-acoes"><a class="botao botao-primario" href="' + escapeHtml(urlSegura(p.url)) + '" target="_blank" rel="noopener noreferrer sponsored" data-evento-promo="parceiro_' + escapeHtml(p.tipo) + '">Ver condições no parceiro</a></div></div></article>';
  }
  function renderizarManuais() {
    if (!area || !grade || !vazio) return;
    var todos = manuais.concat(remotas);
    area.hidden = !todos.length;
    var itens = todos.filter(function (p) { return filtroAtual === 'todas' || p.tipo === filtroAtual; });
    grade.innerHTML = itens.map(cartaoManual).join('');
    vazio.hidden = !!itens.length;
  }
  if (area && grade) {
    area.querySelectorAll('[data-filtro]').forEach(function (botao) {
      botao.addEventListener('click', function () {
        filtroAtual = botao.getAttribute('data-filtro');
        area.querySelectorAll('[data-filtro]').forEach(function (outro) {
          var ativo = outro === botao;
          outro.classList.toggle('ativo', ativo);
          outro.setAttribute('aria-pressed', String(ativo));
        });
        renderizarManuais();
      });
    });
    renderizarManuais();
    fetch('api/links-parceiros.php', {headers: {Accept: 'application/json'}})
      .then(function (r) { if (!r.ok) throw new Error('API indisponível'); return r.json(); })
      .then(function (resposta) {
        var recebidas = Array.isArray(resposta.offers) ? resposta.offers : [];
        var urls = new Set(manuais.concat(remotas).map(function (p) { return p.url; }));
        remotas = remotas.concat(recebidas.filter(function (p) { return p && tipos[p.tipo] && urlSegura(p.url) && !urls.has(p.url); }));
        renderizarManuais();
        if (remotas.length) medicao('promocoes_parceiros_carregadas', {quantidade:remotas.length});
      })
      .catch(function () { renderizarManuais(); }); // Outras funcionalidades permanecem ativas.
  }

  // V19: catálogo de APIs/feeds (WeGoTrip, Airalo, Viator) pré-coletado pelo cron.
  // Somente links confirmados pela Links API são incluídos na vitrine pública.
  if (area && grade) {
    fetch('api/ofertas-integradas.php', {headers: {Accept:'application/json'}})
      .then(function (r) { if (!r.ok) throw new Error('Feed indisponível'); return r.json(); })
      .then(function (data) {
        var ofertas = Array.isArray(data.offers) ? data.offers : [];
        var urls = new Set(manuais.concat(remotas).map(function (o) { return o.url; }));
        ofertas.forEach(function (o) {
          if (!o || !tipos[o.tipo] || !urlSegura(o.url) || urls.has(o.url)) return;
          remotas.push(o); urls.add(o.url);
        });
        renderizarManuais();
      }).catch(function () {});
  }

  // O painel administrativo gerencia somente URLs verificadas de widgets oficiais.
  // Mantemos o buscador principal em destaque sem duplicá-lo na grade de parceiros.
  var secaoWidgets = document.getElementById('pesquisa-parceiros');
  var widgets = document.getElementById('widgets-travelpayouts');
  var principal = document.getElementById('widget-principal-aviasales');
  function validarWidget(src) {
    try {
      var u = new URL(src);
      if (u.protocol !== 'https:' || !['tp.media','tpemd.com'].includes(u.hostname) || u.pathname !== '/content') return '';
      if (u.searchParams.get('trs') !== '589817' || u.searchParams.get('shmarker') !== '792290') return '';
      return u.href;
    } catch (_) { return ''; }
  }
  function carregarWidget(src, parent) {
    var url = validarWidget(src);
    if (!url) return false;
    var script = document.createElement('script');
    script.src = url; script.async = true; script.charset = 'utf-8';
    script.addEventListener('error', function () { parent.textContent = 'Ferramenta do parceiro indisponível. Consulte a agência.'; });
    parent.replaceChildren(script);
    return true;
  }
  function exibirWidgets(listaWidgets) {
    var cadastrados = (Array.isArray(listaWidgets) ? listaWidgets : []).filter(function(w) { return w && w.ativo !== false && validarWidget(w.src); });
    if (principal) {
      var principalCfg = cadastrados.find(function(w) { return w.posicao === 'destaque'; });
      if (!principalCfg || !carregarWidget(principalCfg.src, principal)) {
        principal.textContent = 'O buscador está temporariamente indisponível. Solicite sua cotação pelo WhatsApp.';
      }
    }
    if (widgets && secaoWidgets) {
      var observador = 'IntersectionObserver' in window ? new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
          if (!entry.isIntersecting) return;
          var destino = entry.target;
          var scriptUrl = destino.getAttribute('data-src-widget');
          destino.removeAttribute('data-src-widget');
          observador.unobserve(destino);
          carregarWidget(scriptUrl, destino);
        });
      }, {rootMargin:'220px'}) : null;
      cadastrados.filter(function(w) { return w.posicao !== 'destaque'; }).forEach(function(w) {
        var article = document.createElement('article'); article.className = 'promo-widget';
        var titulo = document.createElement('h3'); titulo.textContent = w.titulo || w.parceiro || 'Pesquisar com parceiro'; article.appendChild(titulo);
        if (w.descricao) { var desc = document.createElement('p'); desc.textContent = w.descricao; article.appendChild(desc); }
        var wrapper = document.createElement('div'); wrapper.className = 'promo-widget-conteudo'; article.appendChild(wrapper);
        widgets.appendChild(article);
        if (observador) { wrapper.setAttribute('data-src-widget', w.src); observador.observe(wrapper); }
        else carregarWidget(w.src, wrapper);
      });
      secaoWidgets.hidden = !widgets.children.length;
    }
  }
  fetch('assets/data/widgets-travelpayouts.json', {cache:'no-cache'})
    .then(function(r) { if(!r.ok) throw new Error('Não foi possível carregar os widgets'); return r.json(); })
    .then(exibirWidgets)
    .catch(function() { // Fallback para os widgets da configuração legado, quando houver.
      var configurados = Array.isArray(conf.widgets) ? conf.widgets : [];
      exibirWidgets(configurados);
    });
  document.addEventListener('click', function (event) {
    var link = event.target.closest('[data-evento-promo]');
    if (link) medicao('clique_promocao', {tipo:link.getAttribute('data-evento-promo')});
  });
})();
