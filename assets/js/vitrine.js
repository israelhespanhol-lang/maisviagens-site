/* Mais Viagens V26 — consulta de ofertas diretamente pelo WhatsApp. */
(function () {
  'use strict';
  var produtos = {
    'costa-diadema': 'Costa Diadema — Natal a bordo (7 noites, saída 19/12)',
    'costa-fascinosa': 'Costa Fascinosa — Caribe a bordo (7 noites, saída 24/01)',
    'montevideu-final': 'Final de campeonato em Montevidéu (28/11 a 29/11)',
    'buenos-aires': 'Buenos Aires — pacote de 13 a 17/01/2027',
    'porto-alegre': 'Porto Alegre — pacote de 16 a 20/01/2027',
    'trend-lazer': 'Natrend Lazer — campanha de voos exclusivos Trend'
  };
  var contato = ((window.MAIS_VIAGENS || {}).contato || {}).whatsapp || '';
  var telefone = String(contato).replace(/\D/g, '');
  if (!/^55\d{10,11}$/.test(telefone)) return;
  document.querySelectorAll('[data-vitrine-produto]').forEach(function (a) {
    var id = a.getAttribute('data-vitrine-produto');
    if (!Object.prototype.hasOwnProperty.call(produtos, id)) return;
    var texto = 'Olá, Mais Viagens! Vi a oferta ' + produtos[id] + ' na vitrine do site. Gostaria de confirmar o valor atualizado, a disponibilidade e as condições de reserva.';
    a.href = 'https://wa.me/' + telefone + '?text=' + encodeURIComponent(texto);
    a.target = '_blank';
    a.rel = 'noopener noreferrer';
  });
})();
