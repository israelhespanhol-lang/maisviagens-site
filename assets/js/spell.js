/* =====================================================================
   Mais Viagens · Camada "Spell UI" (V22)
   Versão em JavaScript puro de efeitos da biblioteca Spell UI (spell.sh, MIT).
   Carregar DEPOIS de site.js. Não altera textos, links nem formulários.
   ===================================================================== */
(function () {
  var reduzir = window.matchMedia && window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  if (reduzir) return; // sem animações para quem pediu menos movimento
  var raiz = document.documentElement;
  raiz.classList.add("spell-js");
  var temIO = "IntersectionObserver" in window;
  var PASSO = 55; // ms entre palavras (Blur Reveal)

  /* ---------- Utilitário: divide um título em palavras, mantendo <span class="destaque"> ---------- */
  function dividir(el) {
    if (el.getAttribute("data-spell-dividido")) return el._spellPalavras;
    var sr = document.createElement("span");
    sr.className = "sr";
    sr.textContent = el.textContent.replace(/\s+/g, " ").trim();
    var vis = document.createElement("span");
    vis.setAttribute("aria-hidden", "true");
    while (el.firstChild) vis.appendChild(el.firstChild);
    var palavras = [];
    (function andar(no) {
      Array.prototype.slice.call(no.childNodes).forEach(function (filho) {
        if (filho.nodeType === 3) {
          var partes = filho.textContent.split(/(\s+)/), frag = document.createDocumentFragment();
          partes.forEach(function (p) {
            if (!p) return;
            if (/^\s+$/.test(p)) { frag.appendChild(document.createTextNode(" ")); return; }
            var s = document.createElement("span");
            s.className = "spell-palavra"; s.textContent = p;
            palavras.push(s); frag.appendChild(s);
          });
          no.replaceChild(frag, filho);
        } else if (filho.nodeType === 1) { andar(filho); }
      });
    })(vis);
    palavras.forEach(function (p, i) { p.style.setProperty("--i", i); });
    el.appendChild(sr); el.appendChild(vis);
    el.setAttribute("data-spell-dividido", "1");
    el._spellPalavras = palavras;
    return palavras;
  }

  function reiniciar(el, classe) { el.classList.remove(classe); void el.offsetWidth; el.classList.add(classe); }

  /* ---------- 1. Topo: Blur Reveal no título + Shimmer no destaque ---------- */
  function revelarTopo(texto) {
    var titulo = texto.querySelector("h1, h2");
    if (!titulo) return;
    var palavras = dividir(titulo);
    var total = palavras.length * PASSO + 620;
    var destaques = titulo.querySelectorAll(".destaque");
    destaques.forEach(function (d) { d.classList.remove("spell-brilho"); });
    reiniciar(titulo, "spell-revelar");
    texto.querySelectorAll(".sub, .acoes, .traco").forEach(function (el, i) {
      el.style.setProperty("--atraso", (total * 0.55 + i * 90) + "ms");
      reiniciar(el, "spell-revelar-junto");
    });
    clearTimeout(titulo._spellTimer);
    titulo._spellTimer = setTimeout(function () {
      destaques.forEach(function (d) { d.classList.add("spell-brilho"); });
    }, total + 100);
  }

  document.querySelectorAll("[data-hero-slides]").forEach(function (caixa) {
    var slides = caixa.querySelectorAll(".hero-slide");
    slides.forEach(function (slide) {
      var texto = slide.querySelector(".hero-texto");
      if (!texto) return;
      if (slide.classList.contains("ativo")) revelarTopo(texto);
      new MutationObserver(function () {
        var ativo = slide.classList.contains("ativo");
        if (ativo && !slide._spellAtivo) revelarTopo(texto);
        slide._spellAtivo = ativo;
      }).observe(slide, { attributes: true, attributeFilter: ["class"] });
      slide._spellAtivo = slide.classList.contains("ativo");
    });
  });
  document.querySelectorAll(".hero:not([data-hero-slides]) .hero-texto").forEach(revelarTopo);

  /* ---------- 2. Ao rolar: títulos de seção (Words Stagger) e cartões surgindo ---------- */
  if (temIO) {
    var obsTitulos = new IntersectionObserver(function (es) {
      es.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add("spell-revelar"); obsTitulos.unobserve(en.target); } });
    }, { threshold: 0.35 });
    document.querySelectorAll(".cabeca h2, .texto-lateral h2, .promo-rodape-aviso h2").forEach(function (h) {
      if (h.closest(".hero")) return;
      dividir(h); h.classList.add("spell-titulo-rolagem"); obsTitulos.observe(h);
    });

    var obsCartoes = new IntersectionObserver(function (es) {
      es.forEach(function (en) {
        if (!en.isIntersecting) return;
        var el = en.target; obsCartoes.unobserve(el);
        el.classList.add("spell-visivel");
        // Ao terminar, devolve o transform para o efeito de inclinação
        setTimeout(function () { el.classList.remove("spell-aparecer", "spell-visivel"); el.style.removeProperty("--i"); }, 900 + (+el.style.getPropertyValue("--i") || 0) * 90);
      });
    }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });
    var grupos = ".grade, .grade-3, .grade-pacotes, .etapas, .promo-widget-grade, .canais, .duas, .promo-home-interno, .bento, .confianca-grade, .passos";
    document.querySelectorAll(grupos).forEach(function (g) {
      if (g.closest(".hero")) return;
      Array.prototype.forEach.call(g.children, function (filho, i) {
        if (filho.closest(".spell-aparecer")) return;
        filho.classList.add("spell-aparecer");
        filho.style.setProperty("--i", Math.min(i, 5));
        obsCartoes.observe(filho);
      });
    });
  }

  /* ---------- 3. Highlighted Text: faixa amarela em frases-chave ---------- */
  if (temIO) {
    var obsMarca = new IntersectionObserver(function (es) {
      es.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add("spell-visivel"); obsMarca.unobserve(en.target); } });
    }, { threshold: 0.8 });
    document.querySelectorAll(".chamada h2 strong, .promo-home-interno h2 .destaque").forEach(function (el, i) {
      el.classList.add("spell-marca");
      el.style.setProperty("--atraso", "250ms");
      obsMarca.observe(el);
    });
  }

  /* ---------- 4. Rotador de destinos na chamada final (estilo Logos Carousel) ---------- */
  document.querySelectorAll("[data-spell-destinos]").forEach(function (h2) {
    var lista = h2.getAttribute("data-spell-destinos").split("|").map(function (s) { return s.trim(); }).filter(Boolean);
    var forte = h2.querySelector("strong");
    if (lista.length < 2 || !forte) return;
    var caixa = document.createElement("span");
    caixa.className = "spell-rotador"; caixa.setAttribute("aria-hidden", "true");
    var itens = lista.map(function (nome) { var s = document.createElement("span"); s.textContent = nome + "?"; caixa.appendChild(s); return s; });
    h2.insertBefore(caixa, forte);
    h2.insertBefore(document.createTextNode(" "), forte);
    var atual = 0;
    itens[0].classList.add("fixo");
    function largura() { caixa.style.width = Math.ceil(itens[atual].getBoundingClientRect().width) + "px"; }
    largura();
    window.addEventListener("resize", largura);
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(largura);
    setInterval(function () {
      if (document.hidden) return;
      var velho = itens[atual];
      atual = (atual + 1) % itens.length;
      var novo = itens[atual];
      velho.classList.remove("fixo", "entra"); reiniciar(velho, "sai");
      novo.classList.remove("sai"); reiniciar(novo, "entra");
      largura();
    }, 2400);
  });

  /* ---------- 5. Flow Button: borda tracejada nos botões de contorno ---------- */
  function prepararFluxo(b) {
    if (b._spellFluxo) return; b._spellFluxo = true;
    var ns = "http://www.w3.org/2000/svg";
    var svg = document.createElementNS(ns, "svg");
    svg.setAttribute("class", "spell-fluxo"); svg.setAttribute("aria-hidden", "true"); svg.setAttribute("focusable", "false");
    var r = document.createElementNS(ns, "rect");
    r.setAttribute("x", "0"); r.setAttribute("y", "0"); r.setAttribute("width", "100%"); r.setAttribute("height", "100%");
    svg.appendChild(r);
    b.classList.add("spell-fluxo-host");
    b.appendChild(svg);
    function raio() {
      var css = parseFloat(getComputedStyle(b).borderTopLeftRadius) || 8;
      var v = Math.min(css + 2, (b.offsetHeight + 4) / 2);
      r.setAttribute("rx", v); r.setAttribute("ry", v);
    }
    raio();
    if ("ResizeObserver" in window) new ResizeObserver(raio).observe(b);
  }
  document.querySelectorAll(".botao-contorno, .botao-claro").forEach(prepararFluxo);
  // Botões criados depois (ex.: mensagens de sucesso dos formulários)
  new MutationObserver(function (ms) {
    ms.forEach(function (m) { m.addedNodes.forEach(function (n) {
      if (n.nodeType !== 1) return;
      if (n.matches && n.matches(".botao-contorno, .botao-claro")) prepararFluxo(n);
      if (n.querySelectorAll) n.querySelectorAll(".botao-contorno, .botao-claro").forEach(prepararFluxo);
    }); });
  }).observe(document.body, { childList: true, subtree: true });

  /* ---------- 6. Tilt Card com holofote (somente mouse) ---------- */
  var mouse = window.matchMedia && window.matchMedia("(hover: hover) and (pointer: fine)").matches;
  if (mouse) {
    var LIMITE = 4, ESCALA = 1.015;
    function prepararTilt(card) {
      if (card._spellTilt) return; card._spellTilt = true;
      card.classList.add("spell-tilt");
      var luz = document.createElement("span"); luz.className = "spell-holofote"; luz.setAttribute("aria-hidden", "true");
      card.appendChild(luz);
      var quadro = null;
      card.addEventListener("pointermove", function (e) {
        if (quadro) return;
        quadro = requestAnimationFrame(function () {
          quadro = null;
          var r = card.getBoundingClientRect();
          var px = (e.clientX - r.left) / r.width, py = (e.clientY - r.top) / r.height;
          card.style.setProperty("--rx", ((py - 0.5) * LIMITE * 2).toFixed(2) + "deg");
          card.style.setProperty("--ry", ((px - 0.5) * -LIMITE * 2).toFixed(2) + "deg");
          card.style.setProperty("--escala", ESCALA);
          card.style.setProperty("--hx", (px * 100).toFixed(1) + "%");
          card.style.setProperty("--hy", (py * 100).toFixed(1) + "%");
        });
      });
      card.addEventListener("pointerleave", function () {
        card.style.setProperty("--rx", "0deg"); card.style.setProperty("--ry", "0deg"); card.style.setProperty("--escala", 1);
      });
    }
    document.querySelectorAll(".card, .promo-oferta, .tile").forEach(prepararTilt);
    new MutationObserver(function (ms) {
      ms.forEach(function (m) { m.addedNodes.forEach(function (n) {
        if (n.nodeType !== 1) return;
        if (n.matches && n.matches(".card, .promo-oferta, .tile")) prepararTilt(n);
        if (n.querySelectorAll) n.querySelectorAll(".card, .promo-oferta, .tile").forEach(prepararTilt);
      }); });
    }).observe(document.body, { childList: true, subtree: true });
  }
})();
