# Mais Viagens V19 — Integrações oficiais via API / Feed na HostGator

## Objetivo

A vitrine utiliza as APIs disponíveis e não exige gerar widget manualmente para cada oferta.
Projeto `Portal`: `trs=589817`; parceiro/marker: `792290`.
**Nenhuma integração faz reservas automáticas nem promete preços em tempo real.**

**Extensões PHP necessárias:** cURL para chamadas HTTPS e XMLReader + DOM para interpretar o feed Airalo. Confira no cPanel > Select PHP Version. Sem essas extensões, os respectivos conectores ficam pendentes; não são mostrados cartões fictícios.

## Configurar o API Token com segurança (obrigatório)

1. Entre em https://app.travelpayouts.com/ > **Profile > API token**. Copie seu token. Não o envie em chat, GitHub ou capturas.
2. No **cPanel da HostGator > Gerenciador de Arquivos**, abra a pasta `HOME` do seu usuário, **fora** de `public_html` ou outra pasta pública do domínio.
3. Ative mostrar arquivos ocultos. Crie `.mais-viagens-travelpayouts.php` com permissão de acesso restrita (por exemplo, 600):

```php
<?php
return [
  'token' => 'INSIRA_O_TOKEN_REAL_AQUI',
  'trs' => 589817,
  'marker' => 792290,
];
```

O projeto também aceita a variável PHP `TRAVELPAYOUTS_API_TOKEN`. Não compartilhe essa chave em mensagens nem a inclua em arquivos enviados ao GitHub.
**Não clique em Update token no painel Travelpayouts** se houver outros sistemas usando a chave, porque isso revoga a anterior.

## Endpoints e conectores

| Integração | Implantação | Requisitos / observações |
|---|---|---|
| Aviasales Data API | `api/promocoes.php` + `scripts/atualizar-promocoes.php` | token TP; dados históricos, exibidos como referências |
| Links API multi-parceiros | `api/links-parceiros.php` e `api/lib/integracoes.php` | token TP e adesão ao programa; processamento máximo de 10 links por requisição |
| WeGoTrip produtos | `api/lib/integracoes.php` / Cron de feeds | Catálogo público; links via Links API, somente se convertidos |
| Airalo produtos | `api/lib/integracoes.php` / Cron de feeds | XML em `https://www.airalo.com/products.xml`; pode bloquear acessos (403). Necessário participar do programa |
| Viator ofertas | Leitor opcional de JSON.gz via cron | Solicitar link/feed a support@travelpayouts.com; colocar `.mais-viagens-viator.json.gz` na HOME fora de `public_html` |
| Financeiro TP | `finance/v2/get_user_balance`, `get_user_next_payout`, `get_user_payments` | Consultado SOMENTE no painel autenticado, com cache |
| Estatísticas TP | `statistics/v1/get_fields_list`, `execute_query` | Implementadas no painel somente para Aviasales (programa 100), último mês; outros IDs devem ser verificados antes de conectar |
| Tiqets | Planilha oficial com city/product IDs | Dados não incluem feed de preço; usar ID para configurar widget de produto |
| Kiwi.com, GetTransfer | Integrações condicionais | Aprovação + chaves de APIs próprias, **não habilitadas pela chave TP** |

## Cron Jobs (HostGator)

No cPanel > Cron Jobs agendar ambos, com caminhos ajustados para sua conta:

```
0 * * * * /usr/local/bin/php /home/USUARIO/public_html/scripts/atualizar-promocoes.php
17 */6 * * * /usr/local/bin/php /home/USUARIO/public_html/scripts/atualizar-integracoes.php
```

Em alguns servidores HostGator o caminho do executável PHP é diferente: consulte **Select PHP Version** ou o suporte. O segundo script só executa em CLI e atualiza os catálogos com cache em pasta temporária restrita do servidor. O painel está em `https://SEU_DOMINIO/painel-promocoes/?aba=apis` e exige a senha administrativa do V17.

## Limitações reais

* A chave principal TP **não dá acesso irrestrito às APIs de terceiros**. Os direitos de cada projeto/programa são verificados em chamadas reais.
* O feed Airalo pode responder 403 ou mudar de formato; o estado aparece no painel. Isso não significa que outro parceiro está indisponível.
* O feed de Viator é obtido pelo suporte e somente ofertas válidas e convertidas pela Links API aparecem no site.
* O catálogo WeGoTrip é em inglês por padrão; preço só é mostrado com a moeda de origem.
* Feed Tiqets oferece IDs, não preços. Não gerar anúncios de tarifa/desconto sem dados verificados.
* As APIs de saldo e próximo pagamento devem ficar na sessão admin, nunca ser expostas como JSON público.
* Não implementamos Search API Aviasales em tempo real ou Kiwi.com sem autorização explícita dos fornecedores.
* Os testes locais não verificam acesso da sua conta à API, à HostGator ou aos programas. Após configurar, consultar status do painel e logs do Cron para validar.

### Documentação oficial

- https://support.travelpayouts.com/hc/en-us/articles/20384016664594-Brands-that-provide-access-to-APIs-and-data-feeds-for-Travelpayouts-partners
- https://support.travelpayouts.com/hc/en-us/articles/25289759198226-API-for-Travelpayouts-partner-links
- https://support.travelpayouts.com/hc/en-us/articles/203956163-Aviasales-Data-API
- https://support.travelpayouts.com/hc/en-us/articles/360019864079-API-of-affiliate-programs-booking-statistics
- https://support.travelpayouts.com/hc/en-us/articles/5169505760402-API-of-affiliates-balance-and-payment
- https://support.travelpayouts.com/hc/en-us/articles/17131439719826-Data-from-Airalo
- https://support.travelpayouts.com/hc/en-us/articles/23940843009426-Data-from-Viator
- https://support.travelpayouts.com/hc/en-us/articles/360010455179-Data-from-Tiqets
- https://support.travelpayouts.com/hc/en-us/articles/360016170039-WeGoTrip-affiliate-program-API
