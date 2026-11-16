/* Comportamento do site Mais Viagens. O conteúdo editável fica em conteudo.js. */
(function () {
  var C = window.MAIS_VIAGENS || {};
  var contato = C.contato || {};
  var pagina = document.body.getAttribute("data-pagina") || "inicio";

  /* ---------- Medição ---------- */
  window.dataLayer = window.dataLayer || [];
  function medir(evento, dados) {
    var d = Object.assign({ event: evento, pagina: pagina }, dados || {});
    window.dataLayer.push(d);
    if (typeof window.gtag === "function") window.gtag("event", evento, d);
  }
  var med = C.medicao || {};
  if (med.googleTagManager) {
    window.dataLayer.push({ "gtm.start": Date.now(), event: "gtm.js" });
    var s = document.createElement("script"); s.async = true;
    s.src = "https://www.googletagmanager.com/gtm.js?id=" + encodeURIComponent(med.googleTagManager);
    document.head.appendChild(s);
  } else if (med.googleAnalytics) {
    var g = document.createElement("script"); g.async = true;
    g.src = "https://www.googletagmanager.com/gtag/js?id=" + encodeURIComponent(med.googleAnalytics);
    document.head.appendChild(g);
    window.gtag = function () { window.dataLayer.push(arguments); };
    window.gtag("js", new Date()); window.gtag("config", med.googleAnalytics);
  }

  /* ---------- Utilidades ---------- */
  function esc(t) { return String(t == null ? "" : t).replace(/[&<>"']/g, function (c) { return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c]; }); }
  function linkWhats(texto) { return "https://wa.me/" + String(contato.whatsapp || "").replace(/\D/g, "") + "?text=" + encodeURIComponent(texto || ""); }
  function msgWhats(chave) { var m = C.mensagensWhatsapp || {}; return m[chave] || m.contato || ""; }

  /* ---------- Menu no celular ---------- */
  var botaoMenu = document.querySelector(".botao-menu");
  var menu = document.getElementById("menu");
  if (botaoMenu && menu) {
    botaoMenu.addEventListener("click", function () {
      var aberto = menu.classList.toggle("aberto");
      botaoMenu.setAttribute("aria-expanded", aberto ? "true" : "false");
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape" && menu.classList.contains("aberto")) { menu.classList.remove("aberto"); botaoMenu.setAttribute("aria-expanded", "false"); botaoMenu.focus(); }
    });
  }

  /* ---------- Links de WhatsApp (data-whats="chave") ---------- */
  document.querySelectorAll("[data-whats]").forEach(function (a) {
    var chave = a.getAttribute("data-whats");
    a.href = linkWhats(msgWhats(chave));
    a.target = "_blank"; a.rel = "noopener";
    a.addEventListener("click", function () { medir("clique_whatsapp", { origem: chave }); });
  });

  /* ---------- Botão flutuante: Grupo VIP ---------- */
  document.querySelectorAll("[data-vip]").forEach(function (a) {
    if (!C.grupoVip) return;
    a.href = C.grupoVip; a.target = "_blank"; a.rel = "noopener";
    a.addEventListener("click", function () { medir("clique_grupo_vip"); });
  });

  /* ---------- Contatos exibidos ---------- */
  document.querySelectorAll("[data-contato]").forEach(function (el) {
    var k = el.getAttribute("data-contato");
    if (k === "whatsapp") el.textContent = contato.whatsappExibicao || "";
    if (k === "instagram") el.textContent = contato.instagram ? "@" + contato.instagram : "";
    if (k === "instagram-link") { el.href = "https://instagram.com/" + (contato.instagram || ""); el.target = "_blank"; el.rel = "noopener"; el.addEventListener("click", function () { medir("clique_instagram"); }); }
    if (k === "cidade") { el.textContent = contato.cidade || ""; el.hidden = !contato.cidade; }
  });
  document.querySelectorAll("[data-versao]").forEach(function (el) { el.textContent = C.versao || ""; });

  /* ---------- Topo rotativo da página inicial ---------- */
  document.querySelectorAll("[data-hero-slides]").forEach(function (caixa) {
    var slides = caixa.querySelectorAll(".hero-slide"), pontos = caixa.querySelectorAll(".hero-controles .ponto");
    if (slides.length < 2) return;
    var atual = 0, timer = null;
    var reduzir = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    function mostrar(n) {
      slides[atual].classList.remove("ativo"); slides[atual].setAttribute("inert", ""); slides[atual].setAttribute("aria-hidden", "true");
      pontos[atual].removeAttribute("aria-current");
      atual = (n + slides.length) % slides.length;
      slides[atual].classList.add("ativo"); slides[atual].removeAttribute("inert"); slides[atual].removeAttribute("aria-hidden");
      pontos[atual].setAttribute("aria-current", "true");
    }
    function iniciar() { if (!reduzir && !timer) timer = setInterval(function () { mostrar(atual + 1); }, 6000); }
    function parar() { clearInterval(timer); timer = null; }
    pontos.forEach(function (p, i) { p.addEventListener("click", function () { mostrar(i); parar(); iniciar(); }); });
    caixa.addEventListener("mouseenter", parar); caixa.addEventListener("mouseleave", iniciar);
    caixa.addEventListener("focusin", parar); caixa.addEventListener("focusout", iniciar);
    var x0 = null;
    caixa.addEventListener("touchstart", function (e) { x0 = e.touches[0].clientX; }, { passive: true });
    caixa.addEventListener("touchend", function (e) {
      if (x0 === null) return; var dx = e.changedTouches[0].clientX - x0; x0 = null;
      if (Math.abs(dx) > 40) { mostrar(atual + (dx < 0 ? 1 : -1)); parar(); iniciar(); }
    });
    iniciar();
  });

  /* ---------- Slideshow do topo ---------- */
  document.querySelectorAll("[data-slideshow]").forEach(function (caixa) {
    var slides = caixa.querySelectorAll(".slide"), pontos = caixa.querySelectorAll(".ponto");
    if (slides.length < 2) return;
    var atual = 0, timer = null;
    var reduzir = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
    function mostrar(n) {
      slides[atual].classList.remove("ativo"); pontos[atual].removeAttribute("aria-current");
      atual = (n + slides.length) % slides.length;
      slides[atual].classList.add("ativo"); pontos[atual].setAttribute("aria-current", "true");
    }
    function iniciar() { if (!reduzir && !timer) timer = setInterval(function () { mostrar(atual + 1); }, 5000); }
    function parar() { clearInterval(timer); timer = null; }
    pontos.forEach(function (p, i) { p.addEventListener("click", function () { mostrar(i); parar(); iniciar(); }); });
    caixa.addEventListener("mouseenter", parar); caixa.addEventListener("mouseleave", iniciar);
    caixa.addEventListener("focusin", parar); caixa.addEventListener("focusout", iniciar);
    iniciar();
  });

  /* ---------- Caravanas ---------- */
  var listaCaravanas = document.querySelectorAll("[data-caravanas]");
  listaCaravanas.forEach(function (alvo) {
    var limite = parseInt(alvo.getAttribute("data-caravanas"), 10) || 99;
    var itens = (C.caravanas || []).slice(0, limite);
    if (!itens.length) { alvo.innerHTML = '<p class="vazio">Novas caravanas em breve. Fale com a gente pelo WhatsApp para saber das próximas saídas.</p>'; return; }
    alvo.innerHTML = itens.map(function (c, i) {
      var whats = c.tipo === "atendimento";
      var botao = whats
        ? '<a class="botao botao-contorno botao-whats" href="' + esc(linkWhats(c.mensagem || msgWhats("caravanas"))) + '" target="_blank" rel="noopener" data-grupo="' + i + '">' + esc(c.botao || "Falar com atendente") + '</a>'
        : '<button type="button" class="botao botao-contorno" data-ultravel="' + i + '">' + esc(c.botao || "Ver detalhes na Ultravel") + '</button>';
      return '<article class="card card-caravana">' +
        '<div class="card-foto"><img src="' + esc(c.foto) + '" alt="Arte da caravana ' + esc(c.nome) + '" loading="lazy" width="1600" height="900"></div>' +
        '<div class="card-corpo">' +
          (c.data ? '<span class="selo">' + esc(c.data) + '</span>' : '') +
          '<h3>' + esc(c.nome) + '</h3>' +
          '<span class="traco traco-curto" aria-hidden="true"></span>' +
          '<div class="card-rodape">' + botao +
          '<p class="externo">' + (whats ? 'Abre o WhatsApp do atendimento.' : 'Abre o site da Ultravel, parceira responsável pela reserva.') + '</p></div>' +
        '</div></article>';
    }).join("");
  });
  document.addEventListener("click", function (e) {
    var g = e.target.closest("[data-grupo]");
    if (g) { var c = (C.caravanas || [])[+g.getAttribute("data-grupo")]; medir("clique_whatsapp", { origem: "caravana", caravana: c && c.nome }); }
  });

  /* Aviso antes de ir para o site externo */
  var fundo = null, ultimoFoco = null;
  function fecharJanela() { if (fundo) { fundo.remove(); fundo = null; if (ultimoFoco) ultimoFoco.focus(); } }
  document.addEventListener("click", function (e) {
    var b = e.target.closest("[data-ultravel]");
    if (!b) return;
    var c = (C.caravanas || [])[+b.getAttribute("data-ultravel")];
    if (!c) return;
    ultimoFoco = b;
    var temLink = /^https?:\/\//.test(c.link || "");
    fundo = document.createElement("div");
    fundo.className = "janela-fundo";
    fundo.innerHTML = '<div class="janela" role="dialog" aria-modal="true" aria-labelledby="janela-titulo">' +
      '<h2 id="janela-titulo">Você vai para o site da Ultravel</h2>' +
      '<p>Os detalhes e a reserva de <strong>' + esc(c.nome) + '</strong> ficam no site da Ultravel, nossa parceira. A página abre em uma nova aba.</p>' +
      (temLink ? '' : '<p class="selo">Link desta caravana ainda não cadastrado</p>') +
      '<div class="acoes">' +
        (temLink ? '<a class="botao botao-primario" href="' + esc(c.link) + '" target="_blank" rel="noopener" data-ir>Continuar para a Ultravel</a>' : '<button type="button" class="botao botao-primario" disabled>Continuar para a Ultravel</button>') +
        '<button type="button" class="botao botao-claro" data-fechar>Ficar aqui</button>' +
      '</div></div>';
    document.body.appendChild(fundo);
    var primeiro = fundo.querySelector("[data-ir]") || fundo.querySelector("[data-fechar]");
    primeiro.focus();
    fundo.addEventListener("click", function (ev) {
      if (ev.target === fundo || ev.target.closest("[data-fechar]")) fecharJanela();
      if (ev.target.closest("[data-ir]")) { medir("acesso_ultravel", { caravana: c.nome, link: c.link }); setTimeout(fecharJanela, 50); }
    });
    fundo.addEventListener("keydown", function (ev) {
      if (ev.key === "Escape") fecharJanela();
      if (ev.key === "Tab") {
        var f = fundo.querySelectorAll("a, button:not([disabled])");
        var p = f[0], u = f[f.length - 1];
        if (ev.shiftKey && document.activeElement === p) { ev.preventDefault(); u.focus(); }
        else if (!ev.shiftKey && document.activeElement === u) { ev.preventDefault(); p.focus(); }
      }
    });
  });

  /* ---------- Depoimentos (só aparecem quando cadastrados em conteudo.js) ---------- */
  var depos = (C.depoimentos || []).filter(function (d) { return d && d.texto; });
  document.querySelectorAll("[data-depoimentos]").forEach(function (secao) {
    if (!depos.length) return;
    var lista = secao.querySelector("[data-depoimentos-lista]");
    lista.innerHTML = depos.slice(0, 6).map(function (d) {
      return '<figure class="depoimento">' +
        (d.foto ? '<img src="' + esc(d.foto) + '" alt="" loading="lazy">' : '') +
        '<blockquote>“' + esc(d.texto) + '”</blockquote>' +
        '<figcaption>' + esc(d.nome || "") + (d.destino ? ' · ' + esc(d.destino) : '') + '</figcaption></figure>';
    }).join("");
    secao.hidden = false;
  });

  /* ---------- Planejador do topo: monta a mensagem e abre o WhatsApp ---------- */
  document.querySelectorAll("form[data-planejador]").forEach(function (form) {
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var destino = form.querySelector("[name=destino]").value;
      var quando = form.querySelector("[name=quando]").value.trim();
      var quem = form.querySelector("[name=quem]").value;
      var linhas = ["Olá, Mais Viagens! Vim pelo site e quero montar um roteiro.", "Destino: " + destino];
      if (quando) linhas.push("Quando: " + quando);
      linhas.push("Quem vai: " + quem);
      medir("planejador_whatsapp", { destino: destino, quem: quem });
      var url = linkWhats(linhas.join("\n"));
      var janela = window.open(url, "_blank");
      if (janela) { try { janela.opener = null; } catch (x) {} } else { window.location.href = url; }
    });
  });

  /* ---------- Vídeos em reprodução automática ---------- */
  document.querySelectorAll("video[data-auto]").forEach(function (v) {
    v.muted = true;
    if (window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches) { v.removeAttribute("autoplay"); v.pause(); return; }
    var tentar = function () { var p = v.play(); if (p && p.catch) p.catch(function () {}); };
    if ("IntersectionObserver" in window) {
      new IntersectionObserver(function (es) { es.forEach(function (en) { if (en.isIntersecting) tentar(); else v.pause(); }); }, { threshold: 0.2 }).observe(v);
    } else { tentar(); }
  });

  /* ---------- Formulários ---------- */
  var abertoEm = Date.now();
  document.querySelectorAll("form[data-form]").forEach(function (form) {
    var tipo = form.getAttribute("data-form");
    var titulo = form.getAttribute("data-titulo") || "Solicitação pelo site";

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      // Proteção contra spam: campo invisível preenchido ou envio rápido demais.
      if (form.querySelector(".pote input").value) return;
      if (Date.now() - abertoEm < 2500) { mostrarErroGeral(form, "Confira os dados e envie novamente."); return; }

      var primeiroErro = null;
      form.querySelectorAll("[required]").forEach(function (campo) {
        var ok = campo.type === "checkbox" ? campo.checked : campo.value.trim() !== "";
        if (ok && campo.type === "tel") ok = campo.value.replace(/\D/g, "").length >= 10;
        var erro = document.getElementById(campo.id + "-erro");
        campo.setAttribute("aria-invalid", ok ? "false" : "true");
        if (erro) erro.hidden = ok;
        if (!ok && !primeiroErro) primeiroErro = campo;
      });
      if (primeiroErro) { primeiroErro.focus(); return; }

      var dados = {}, linhas = [titulo];
      form.querySelectorAll("[data-rotulo]").forEach(function (campo) {
        var rot = campo.getAttribute("data-rotulo"), val;
        if (campo.tagName === "FIELDSET") val = Array.prototype.map.call(campo.querySelectorAll("input:checked"), function (i) { return i.value; }).join(", ");
        else val = campo.value.trim();
        dados[rot] = val;
        if (val) linhas.push(rot + ": " + val);
      });
      var mensagem = linhas.join("\n");
      var botao = form.querySelector("button[type=submit]");

      function concluir(enviado) {
        medir("envio_formulario", { formulario: tipo, destino: enviado ? "servidor" : "whatsapp" });
        var caixa = document.createElement("div");
        caixa.className = "sucesso"; caixa.setAttribute("role", "status"); caixa.tabIndex = -1;
        caixa.innerHTML = enviado
          ? '<h3>Recebemos sua solicitação!</h3><p>Obrigado, ' + esc((dados["Nome"] || "").split(" ")[0]) + '. A equipe da Mais Viagens vai responder pelo WhatsApp informado.</p><a class="botao botao-contorno" data-whats-direto>Falar agora pelo WhatsApp</a>'
          : '<h3>Tudo pronto, falta só um passo</h3><p>Preparamos sua mensagem com as informações preenchidas. Toque no botão para enviá-la à Mais Viagens pelo WhatsApp.</p><a class="botao botao-primario botao-whats" data-whats-direto>Enviar pelo WhatsApp</a>';
        var link = caixa.querySelector("[data-whats-direto]");
        link.href = linkWhats(mensagem); link.target = "_blank"; link.rel = "noopener";
        link.addEventListener("click", function () { medir("clique_whatsapp", { origem: "formulario_" + tipo }); });
        form.replaceChildren(caixa);
        caixa.focus();
      }

      if (C.formularioEndpoint) {
        botao.disabled = true; botao.textContent = "Enviando...";
        fetch(C.formularioEndpoint, { method: "POST", headers: { "Content-Type": "application/json", "Accept": "application/json" }, body: JSON.stringify(Object.assign({ formulario: tipo, pagina: pagina }, dados)) })
          .then(function (r) { if (!r.ok) throw new Error(); concluir(true); })
          .catch(function () { botao.disabled = false; botao.textContent = "Tentar novamente"; mostrarErroGeral(form, "Não conseguimos enviar agora. Tente de novo ou fale pelo WhatsApp."); });
      } else {
        concluir(false);
      }
    });
  });

  function mostrarErroGeral(form, texto) {
    var el = form.querySelector(".erro-geral");
    if (!el) { el = document.createElement("p"); el.className = "erro-geral msg-erro"; el.setAttribute("role", "alert"); form.querySelector("button[type=submit]").before(el); }
    el.textContent = texto; el.style.color = "var(--erro)";
  }
})();
