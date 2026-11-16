# Mais Viagens — V23 · Página inicial no "Modelo A"

Mantém tudo da V22 (efeitos Spell UI) e reorganiza a página inicial.

## O que mudou
- **Topo:** novo texto ("Viagens planejadas do seu jeito.") no primeiro slide. Os outros slides continuam iguais.
- **Planejador (novo):** Para onde / Quando / Quem vai → botão "Montar meu roteiro" abre o WhatsApp com a mensagem pronta.
  Gera o evento `planejador_whatsapp` no Google Analytics/GTM, quando a medição estiver configurada.
- **Faixa de confiança:** WhatsApp, roteiro sob medida, caravanas e parceiros (Ultravel e Aviasales).
- **Mosaico de destinos:** Orlando em destaque, Nova York e Europa com os vídeos, Israel (caravana), Sob medida, Aluguel de carros e Promoções.
  No celular, os destinos deslizam para o lado.
- **Como funciona:** 3 passos.
- **Caravanas:** agora mostram a data num selo (campo `data` em `conteudo.js`).
- **Depoimentos:** a seção fica escondida até você cadastrar depoimentos reais em `conteudo.js` → `depoimentos`.
- **Todas as páginas:** botão de WhatsApp no cabeçalho (computador). No celular, o botão redondo virou uma barra fixa "Falar no WhatsApp".

## Para preencher em `assets/js/conteudo.js`
1. `caravanas` → `data` da imersão no Paraguai (hoje vazia, então o selo não aparece).
2. `depoimentos` → apague `/*` e `*/` do exemplo e troque pelos textos reais (com autorização do cliente).

## Seções removidas da página inicial
- Os 3 cartões (Parques, Personalizadas, Carros), o banner de Promoções e o bloco "Nova York e Europa".
  O conteúdo deles foi para o mosaico de destinos.

## Publicação
Faça backup e substitua os arquivos na pasta do domínio, como de costume.
Os arquivos CSS e JS têm `?v=23` no endereço, então o navegador já baixa a versão nova.
