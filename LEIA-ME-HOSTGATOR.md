# Mais Viagens — V19 | APIs múltiplas para HostGator

**Leia primeiro `GUIA-INTEGRACOES-API.md`** para configurar token fora da raiz pública, cron de múltiplos feeds e status de parceiros. A nova página mantém todos os arquivos V18 e os controles V17.

# Mais Viagens — versão 16: widget Aviasales em português (HostGator)

## O que foi integrado nesta versão

A página **`promocoes.html`** já inclui, diretamente no HTML, o widget oficial
**Aviasales / Travelpayouts** fornecido pela agência. Esta versão usa os parâmetros
`locale=pt_br`, `currency=brl`, **Project ID (`trs`) 589817** e
**Marker (`shmarker`) 792290**. O widget é carregado pelo domínio `tpemd.com`
e aparece antes dos cartões de tarifas de referência da API.

- O código de incorporação é público e **não usa API Token**.
- A HostGator pode servir esse widget mesmo sem configurar os endpoints PHP.
- O funcionamento visual e a busca real dependem de o navegador conseguir carregar o script externo do Travelpayouts, da conexão do projeto com a Aviasales e das políticas do parceiro.
- Os campos do widget são fornecidos pelo parceiro; **não garantimos resultados, preços ou disponibilidade** e não os simulamos.
- Não há necessidade de voltar a gerar o mesmo widget ou copiar o token para o HTML.
- Para testar: publique o conteúdo extraído do ZIP na pasta do domínio e abra `https://SEU-DOMINIO/promocoes.html` em desktop e celular; confirme português, BRL e a abertura da pesquisa com atribuição ao parceiro.
- Faça backup de `public_html` ou da pasta do domínio antes de substituir arquivos.

**Observação:** os cartões de tarifas automáticas embaixo do buscador continuam sendo
um recurso **separado e opcional**, que exige token privado da API na HostGator.
A Links API de outros parceiros continua disponível conforme o guia abaixo.

---

# Mais Viagens — versão 15 (HostGator + Travelpayouts)

**Este ZIP é para a HostGator.** Leia também `INTEGRACAO-LINKS-API.md` para o novo módulo de conversão automática de links de afiliados. Não há Netlify Functions, Node.js, build ou Supabase.
A aba `promocoes.html` já faz parte do menu de todas as páginas; o visual original foi preservado.

## 1) Publicação no cPanel da HostGator

1. Antes de alterar o site publicado, faça o **backup** da pasta atual pelo Gerenciador de Arquivos.
2. Entre no **cPanel > Gerenciador de Arquivos** e abra a pasta de publicação do domínio (normalmente `public_html`, mas pode ser diferente para subdomínios ou domínios adicionais).
3. Envie este ZIP e use **Extrair** nessa pasta; os arquivos `index.html`, `promocoes.html`, `assets/`, `api/` devem ficar **diretamente** na pasta de publicação, sem uma pasta duplicada. Se o domínio já usa arquivos de site, substitua apenas depois do backup.
4. Abra `https://SEU-DOMINIO/promocoes.html` e verifique menu, imagens e links.
5. Teste `https://SEU-DOMINIO/api/promocoes.php?origem=FLN`. **Antes do token**, a resposta esperada é JSON `{"status":"pending","offers":[],"origin":"FLN"}`. Isso confirma que o PHP está funcionando.
6. Se a URL da API retornar arquivo para download, código PHP como texto ou erro 500, confira o PHP no seu plano e no domínio (de preferência PHP 8.2+). Não publique a chave até corrigir isso.

## 2) Conexão segura com o token (fora da área pública)

**Nunca cole o token em HTML, `conteudo.js`, arquivos `.js`, ou na pasta pública do domínio.**

A API oferece duas formas de ler o token, em ordem de preferência:

- Variável de ambiente `TRAVELPAYOUTS_API_TOKEN` fornecida pelo servidor PHP (se o plano disponibilizar configuração de variáveis);
- Arquivo **privado**, armazenado na pasta HOME da conta do cPanel (normalmente `/home/SEU_USUARIO/`, um nível acima de `public_html`), com o nome **`.mais-viagens-travelpayouts.php`**. Crie-o pelo Gerenciador de Arquivos, habilitando a exibição de arquivos ocultos:

```php
<?php
return ['token' => 'COLE_AQUI_O_TOKEN_DA_SUA_CONTA_TRAVELPAYOUTS'];
```

O token é encontrado no painel Travelpayouts, **Profile > API token**. Mantenha esse arquivo **fora** da pasta pública do site. O endpoint PHP lê o arquivo pela variável `HOME` do servidor. Se a HostGator não disponibilizar essa variável ou bloquear a leitura, o endpoint continuará em `pending`; peça ao suporte o caminho HOME adequado ou configure a variável de ambiente no PHP. Não improvise colocando o arquivo dentro de `public_html`.

Após a configuração, atualize `https://SEU-DOMINIO/api/promocoes.php?origem=FLN`. Retornos possíveis:

- `ready`: ofertas de preços cacheados encontradas e exibidas na página;
- `empty`: consulta concluída mas sem ofertas válidas nessa origem;
- `pending`: falta token no servidor;
- `unavailable`: houve erro na API, na extensão PHP cURL ou na conexão HTTPS.

O endpoint aceita apenas as origens `FLN`, `GRU`, `GIG` e `BSB`. Os preços são valores de referência encontrados anteriormente por usuários, **não garantem disponibilidade para compra**. O site não apresenta preço fictício.

**Observação de compatibilidade:** PHP 8.1+ e extensão cURL habilitada são necessários para consultar as ofertas (recomenda-se PHP 8.2+). O servidor usa cache temporário de 10 minutos para evitar consultas desnecessárias.

## 3) Links e widgets adicionais de afiliado

Widgets e links de afiliado do Travelpayouts **não exigem PHP nem token da API**.

1. O widget Aviasales fornecido pela Mais Viagens já está integrado. Para outros programas, no painel Travelpayouts, encontre **Tools > Widgets** e gere os widgets aprovados. Para o carregador JS legado, copie a URL `src` que comece com `https://tp.media/content?...`; widgets com outro domínio exigem integração específica.
2. No arquivo `assets/js/conteudo.js`, configure `travelpayouts.widgets`:

```javascript
widgets: [
  { titulo: "Buscar passagens aéreas", descricao: "Encontre destinos e datas.", src: "https://tp.media/content?PARAMETROS_REAIS_DO_SEU_WIDGET" },
  { titulo: "Encontrar hotéis", descricao: "Confira hospedagens.", src: "https://tp.media/content?PARAMETROS_REAIS_DO_SEU_WIDGET" }
]
```

3. Para promoções específicas, gere links afiliados em **Tools > Links**, configure `travelpayouts.promocoes` no mesmo arquivo com tipo (`passagens`, `hoteis`, `passeios`, `carros`), título, descrição, imagem opcional, preço/validade **se confirmados**, e URL real de afiliado.
4. Para o botão de pesquisa de passagens, preencha `travelpayouts.linkBuscaPassagens` com o link gerado pelo Travelpayouts. Caso contrário, o botão de cada tarifa leva à equipe pelo WhatsApp.

A seção **adicional** de widgets só aparece quando URLs extras são cadastradas. O buscador principal Aviasales já está incluído com os IDs fornecidos pela agência. Não foram inseridos descontos ou preços inventados.

## 4) Verificação antes de colocar em produção

- Navegue por todas as páginas após publicar; o menu deve conter **Promoções**.
- Teste no celular e em desktop, inclusive botão de WhatsApp.
- Teste a API em cada origem; sem token, `pending` é esperado.
- Confirme que o arquivo secreto **não é acessível por URL pública** (porque está fora da pasta servida).
- Confira domínio com HTTPS e eventuais configurações de cache da HostGator.
- Para acompanhar comissões, use apenas links e widgets gerados na conta da agência.
- **Não substitua arquivos de produção sem backup prévio.**

Fontes oficiais:
- [HostGator — acesso ao cPanel](https://suporte.hostgator.com.br/hc/pt-br/articles/30816449693459-Como-acessar-o-cPanel)
- [HostGator — PHP](https://suporte.hostgator.com.br/hc/pt-br/articles/30807998885395-Como-alterar-a-vers%C3%A3o-do-PHP-na-hospedagem)
- [Travelpayouts — API](https://support.travelpayouts.com/hc/en-us/articles/203956163-Aviasales-Data-API)
- [Travelpayouts — Widgets](https://support.travelpayouts.com/hc/en-us/articles/360031977872-Getting-started-with-widgets)

## Novo na versão 15: Links API de parceiros

Foi incluído o endpoint `api/links-parceiros.php`, que converte os URLs dos itens cadastrados
em `assets/data/promocoes-parceiros.json` em URLs com atribuição à Mais Viagens.
Configure também `marker` e `trs` no arquivo PHP **privado** (veja o guia `INTEGRACAO-LINKS-API.md`).
A Links API não descobre promoções: é necessário cadastrar URLs válidas e estar associado aos programas.

## Versão 17 — Painel central de widgets e ofertas

Acesse `painel-promocoes/` após configurar a senha administrativa fora de `public_html`.
O widget Aviasales pt-BR/BRL já está no novo catálogo `assets/data/widgets-travelpayouts.json`.
O painel permite publicar e pausar widgets oficiais do projeto 589817 / Marker 792290
sem editar o HTML. Também permite cadastrar ofertas para a Links API.

**Leia `GUIA-PAINEL-PROMOCOES.md` antes de publicar e configurar a senha.**
