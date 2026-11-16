# Mais Viagens — V22 · Efeitos Spell UI

Efeitos inspirados na biblioteca **Spell UI** (spell.sh, licença MIT), reescritos em CSS + JavaScript puros.
Não exigem React, Node, build nem chave de API. Funcionam direto na HostGator.

## Arquivos novos
- `assets/css/spell.css`
- `assets/js/spell.js`

## Arquivos alterados
- Todas as páginas `.html`: uma linha `<link>` para `spell.css` e uma linha `<script>` para `spell.js` (depois de `site.js`).
- `index.html`: atributo `data-spell-destinos` na chamada final (lista de destinos que alternam).
- `assets/js/conteudo.js`: versão no rodapé → "VERSÃO 22".

## O que mudou no visual
| Efeito Spell | Onde |
|---|---|
| Blur Reveal | Títulos do topo surgem palavra por palavra a cada troca de slide; títulos de seção ao rolar |
| Shimmer Text | Brilho que atravessa o destaque amarelo do topo |
| Rich Button | Botões amarelos com volume, brilho e sombra |
| Flow Button | Botões de contorno ganham borda tracejada animada no hover |
| Tilt Card | Cartões inclinam levemente com holofote seguindo o mouse (só computador) |
| Highlighted Text | Faixa amarela em "Vamos planejar?" e "boa oportunidade." |
| Logos Carousel | "Para onde vai ser? Orlando? / Nova York? / Paris? / Roma? / Jerusalém?" |

Cartões, formulários e etapas entram suavemente ao rolar a página.

## Acessibilidade
- Quem ativou "reduzir movimento" no sistema vê o site sem nenhum efeito.
- Leitores de tela leem os títulos normalmente (o texto dividido é só visual).
- Sem JavaScript, o site aparece igual à V21.

## Como publicar
Faça backup e envie o ZIP para a pasta do domínio, como nas versões anteriores. Basta substituir os arquivos.
Para desfazer, remova as duas linhas `spell.css` e `spell.js` das páginas.
Para trocar os destinos da chamada, edite `data-spell-destinos="Orlando|Nova York|Paris|Roma|Jerusalém"` no `index.html`.
