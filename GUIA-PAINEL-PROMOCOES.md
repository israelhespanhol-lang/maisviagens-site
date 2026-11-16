# Mais Viagens — Versão 17 | Painel de Promoções (HostGator)

## O que está automatizado

- O widget **Aviasales em português e BRL** já vem cadastrado no arquivo `assets/data/widgets-travelpayouts.json`, na área de destaque.
- Um **painel autenticado** permite adicionar, pausar, reordenar e excluir outros widgets oficiais, sem editar HTML nem enviar novo ZIP. Caminho: `https://SEU-DOMINIO/painel-promocoes/`.
- Basta colar o **código completo `<script ...></script>`** recebido do Travelpayouts ou a URL `src` correspondente. O servidor extrai somente a URL e valida que pertence ao seu projeto `trs=589817` e `shmarker=792290`.
- Os widgets cadastrados aparecem automaticamente em `promocoes.html`. Alterações podem demorar alguns minutos em navegadores/proxies com cache; o site busca o catálogo com política de atualização.
- A aba **Ofertas** permite cadastrar URLs de páginas dos parceiros (Klook, Tiqets etc.). A Links API existente converte os URLs compatíveis quando o token privado estiver instalado. Sem conversão bem-sucedida, não se publica o link original sem comissão.
- Para preços e cartões de promoções **realmente descobertas automaticamente**, precisamos de APIs/feeds específicos de cada programa. **A Links API não busca ofertas** e o Travelpayouts não fornece um código genérico que crie todos os widgets de qualquer marca.

## Como publicar

1. Faça **backup** da pasta do site no cPanel.
2. Extraia o ZIP da versão 17 na pasta do seu domínio (em geral `public_html`). Não substitua arquivos editados diretamente em produção sem salvar backup.
3. Seu HostGator precisa executar **PHP 8.1+** com suporte a sessões e cURL para as integrações de API.
4. Confira `promocoes.html`. O buscador Aviasales em português e reais virá do catálogo central de widgets.
5. Acesse `https://SEU-DOMINIO/painel-promocoes/`. Ele **deve mostrar 'Configure a senha'** até que a senha seja configurada; não existe senha padrão.

## Configurar a senha administrativa, sem expô-la no site

Crie uma senha forte e única. **Nunca envie a senha pelo chat** e nunca guarde o arquivo abaixo em `public_html`, `www` ou no GitHub.

No **cPanel > Terminal**, execute o comando abaixo substituindo `SUA_SENHA_FORTE` pela senha desejada:

```bash
php -r 'echo password_hash("SUA_SENHA_FORTE", PASSWORD_DEFAULT), PHP_EOL;'
```

Copie **somente a saída hash** (ela começa com `$2y$` ou equivalente). No **Gerenciador de Arquivos do cPanel**, abra a pasta HOME, um nível **acima da pasta pública**. Crie um arquivo `.mais-viagens-admin.php`:

```php
<?php
return [
  'password_hash' => 'COLE_AQUI_O_HASH_GERADO_NO_TERMINAL'
];
```

O painel só será habilitado após encontrar esse arquivo **fora da raiz pública**. Uma alternativa, caso o provedor disponibilize, é configurar a variável de ambiente `MAIS_VIAGENS_ADMIN_PASSWORD_HASH` com o hash.

**Segurança:** use HTTPS, proteja o acesso ao cPanel com MFA, não divulgue o endereço do painel publicamente e mantenha backups. As sessões usam cookie HttpOnly/SameSite=Strict, as ações exigem token CSRF e tentativas de senha são limitadas. O projeto não cria senha padrão nem instala banco de dados.

## Usar o painel

1. Entre em `painel-promocoes/` com a senha que criou.
2. Para **Widgets**, escolha o parceiro, categoria, título, descrição e cole o snippet gerado em **Travelpayouts > Tools > Widgets**. O snippet precisa apontar para `tp.media/content` ou `tpemd.com/content`, conter `trs=589817`, `shmarker=792290`, `promo_id` e `campaign_id`. Scripts em outros formatos não são aceitos automaticamente por segurança.
3. Clique **Salvar e publicar widget**. Um item novo entra na grade de widgets. **Somente widgets de passagens** podem ser promovidos ao buscador em destaque.
4. Use **Pausar**, **Ativar**, as setas de ordem ou **Excluir**. O HTML público não precisa ser alterado.
5. Em **Ofertas**, cadastre o nome e a URL original de uma página que você verificou no parceiro. Preço e prazo são opcionais — deixe vazios se não foram verificados.

## Ativar links de afiliado para os cartões de ofertas

No Travelpayouts, localize a API Token (mantenha privada) e, na HOME do cPanel, fora do diretório público, crie ou atualize `.mais-viagens-travelpayouts.php`:

```php
<?php
return [
    'token' => 'API_TOKEN_CONFIGURADO_SOMENTE_NO_SERVIDOR',
    'marker' => 792290,
    'trs' => 589817,
];
```

A página `api/links-parceiros.php` tentará converter as ofertas cadastradas (somente quando o programa e URL forem suportados), com cache, limites e validações. O visitante não pode pedir conversão de URL livremente.

## Limites desta versão

- Nenhum widget de Klook, Tiqets, carros ou hotéis foi **inventado**. O código Aviasales é o único fornecido até agora. Outros programas podem oferecer links, widgets, APIs ou apenas feeds, de forma diferente. Cada novo widget depende de um código válido do respectivo parceiro.
- A Data API Aviasales retorna tarifas de referência, **não reservas garantidas**. Ela precisa de token privado no cPanel para mostrar cartões de preços; o buscador Aviasales já funciona sem esse token.
- O painel administra somente os formatos de widget oficiais `tp.media/content` e `tpemd.com/content` com o projeto correto. Outras integrações e fontes de feeds precisam de adaptadores dedicados.
- O **Travelpayouts Drive** pode automatizar a conversão de links existentes, mas recomendações nativas e links automáticos por palavras-chave do Drive são limitados a páginas em inglês. Ele pode ser avaliado separadamente para nosso site em português.
- Nunca repasse as API Tokens, senhas administrativas ou cookies de sessão.

## Referências oficiais

- https://support.travelpayouts.com/hc/en-us/articles/360031977872-Getting-started-with-widgets
- https://support.travelpayouts.com/hc/en-us/articles/25289759198226-API-for-Travelpayouts-partner-links
- https://support.travelpayouts.com/hc/en-us/articles/21844777943058-What-is-Travelpayouts-Drive
- https://support.travelpayouts.com/hc/en-us/articles/20384016664594-Brands-that-provide-access-to-APIs-and-data-feeds-for-Travelpayouts-partners
