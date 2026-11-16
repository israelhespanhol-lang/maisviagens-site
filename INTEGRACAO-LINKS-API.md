# Mais Viagens — versão 15: integração Links API Travelpayouts na HostGator

A nova aba `promocoes.html` mantém a consulta de passagens aéreas da versão 14 e agora suporta
**links de parceiros criados automaticamente** com a Links API do Travelpayouts.
Não depende do Netlify, Vercel ou Supabase. Requer **HostGator com PHP 8.1+ e cURL**.

> Importante: a Links API **não entrega uma lista de promoções** nem verifica preços.
> Ela **converte** URLs originais de marcas com as quais o projeto está associado em URLs
> de afiliado. Para popular uma vitrine de ofertas, cadastre URLs de ofertas verificadas,
> widgets ou feeds de parceiros. Não informe preços ou descontos não confirmados.

## Publicação com segurança

1. Faça backup de todos os arquivos atuais no cPanel antes de substituir o site.
2. Envie e extraia o ZIP na pasta que serve o domínio, geralmente `public_html` (verifique).
3. Confira a URL `https://SEU-DOMINIO/promocoes.html`.
4. Confira `https://SEU-DOMINIO/api/links-parceiros.php`. Enquanto o catálogo estiver vazio,
   retornará `{"status":"empty","offers":[]}`. Isso testa se o PHP está executando.
5. **Nunca** inclua o token em JavaScript, HTML, JSON público ou repositório Git.

## Credenciais no servidor

No Travelpayouts, em `Profile > API token`, localize a credencial (não compartilhe em chat).
Neste projeto, o **Marker é 792290** e o **Project ID (`trs`) é 589817**, conforme o widget fornecido pela agência. O Marker identifica seu afiliado, e o Project ID identifica o projeto que
está conectado aos programas dos parceiros. Não confundir os dois identificadores.

No **cPanel > Gerenciador de Arquivos**, na pasta HOME da conta **fora de `public_html`**,
crie o arquivo oculto `.mais-viagens-travelpayouts.php`:

```php
<?php
return [
    'token' => 'PREENCHA_SOMENTE_NA_HOSTGATOR',
    'marker' => 123456, // SUBSTITUA pelo marker verdadeiro
    'trs' => 123456,    // SUBSTITUA pelo Project ID verdadeiro
];
```

Ou use as variáveis de ambiente do PHP `TRAVELPAYOUTS_API_TOKEN`,
`TRAVELPAYOUTS_MARKER`, `TRAVELPAYOUTS_TRS`, se o plano oferecer.

**Não crie esse arquivo dentro da pasta pública.** A mesma credencial `token`
continua servindo à API de tarifas aéreas que já existia na versão 14.

## Como cadastrar promoções de parceiros

Edite `assets/data/promocoes-parceiros.json` pelo Gerenciador de Arquivos da HostGator.
Por padrão o catálogo contém `[]` (nenhuma oferta fictícia). Abaixo, apenas um **modelo de preenchimento**;
as URLs, nomes e dados são EXEMPLOS, não ofertas verificadas:

```json
[
  {
    "id": "hotel-exemplo-01",
    "tipo": "hoteis",
    "titulo": "Hotel em cidade exemplo",
    "descricao": "Consulte valores e disponibilidade no fornecedor.",
    "imagem": "assets/img/viagem-personalizada-roma.jpg",
    "preco": "",
    "validade": "",
    "urlOriginal": "https://site-do-parceiro.example/hotel/123"
  }
]
```

Campos:

- `id`: identificador único, com letras minúsculas, números e hífens (mínimo 2 caracteres).
- `tipo`: `passagens`, `hoteis`, `passeios` ou `carros`.
- `titulo`, `descricao`: informações confirmadas da promoção.
- `imagem`: opcional. Somente arquivo existente em `assets/img/`.
- `preco`, `validade`: opcionais. Informe SOMENTE valores e condições verificados.
- `urlOriginal`: URL completa `https://` do parceiro, não um link encurtado.
- **Alternativa**: se já possui URL afiliada criada no Travelpayouts, use
  `"urlAfiliada": "https://..."` no lugar de `urlOriginal`. Nesse caso, não é necessário
  converter esse item novamente.

A página carrega os cartões via `api/links-parceiros.php`, que lê **apenas os itens
previamente cadastrados no arquivo**. Não existe formulário público que permita
mandar URLs arbitrárias para a API usando seu token.

O endpoint faz `POST https://api.travelpayouts.com/links/v1/create` com
`X-Access-Token` no cabeçalho, `trs`, `marker`, `shorten=false` e até 10 links por
requisição, com `sub_id` para identificação da promoção. Limita a 30 itens, valida
URLs HTTPS, processa falhas individualmente e guarda cache temporário por 30 minutos.
Só publica o `partner_url` retornado com sucesso; **não substitui por links comuns**
se a conversão falhar. O catálogo pode continuar vazio até a conexão estar pronta.

## Diagnóstico

- `empty`: nenhum item cadastrado.
- `pending`: há links originais cadastrados, mas faltam token, Project ID ou Marker.
- `ready`: todos os links disponíveis foram convertidos ou eram afiliados prontos.
- `partial`: parte das ofertas está convertida e algumas ainda não estão.
- `unavailable`: todas as conversões falharam; confira inscrições nos programas,
  as URLs completas, credenciais e extensões PHP no servidor.
- `invalid_catalog`: o JSON foi editado com erro de sintaxe.

A documentação oficial lista limite de **100 requisições por minuto por marker** e
**10 links por requisição**; alguns programas/marcas não são compatíveis com o recurso.
Programas precisam estar vinculados ao projeto (`trs`) para converter seus links.

Para receber promoções realmente automáticas, ainda será necessário integrar um
**feed de ofertas** disponibilizado pelo programa, como feeds de passeios ou widgets.
A Links API isoladamente não seleciona nem descobre novas ofertas.

Documentação oficial:
https://support.travelpayouts.com/hc/en-us/articles/25289759198226-API-for-Travelpayouts-partner-links
