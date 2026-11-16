# Correção do botão Início — Mais Viagens

Todos os links de navegação de Início e os links nos logotipos foram corrigidos para `/`, mantendo `index.html` como documento inicial servido automaticamente pela HostGator.

## Publicar somente a correção

1. Faça um backup dos arquivos atuais do site.
2. No cPanel da HostGator, abra o **Gerenciador de Arquivos**, na pasta que contém `index.html` e `promocoes.html` (normalmente `public_html` ou a raiz configurada do subdomínio).
3. Envie o ZIP `Correcao-Inicio-Mais-Viagens-HostGator.zip` e extraia-o nessa mesma pasta. Confirme a substituição dos oito arquivos HTML. Não extraia em uma pasta interna adicional.
4. Abra `https://portal.maisviagensbr.com.br/promocoes.html` e clique em **Início** ou no logotipo. O endereço deve aparecer como `https://portal.maisviagensbr.com.br/`.
5. Caso veja a versão antiga, atualize com Ctrl+F5 ou abra janela anônima.

## HTTPS é outra configuração

A mudança de `index.html` para `/` **não ativa HTTPS nem instala certificado SSL**. Antes de forçar HTTPS, confirme no cPanel que o SSL do subdomínio está válido. Depois ative a opção **Force HTTPS Redirect** em Domínios, se disponível.

## URL /index.html digitada diretamente

O ajuste muda apenas os links do site. Para redirecionar também quem digita `/index.html` diretamente, adicione a regra abaixo ao `.htaccess` EXISTENTE do diretório; não substitua o `.htaccess` atual, que pode ter outras configurações essenciais:

```apache
RewriteEngine On
RewriteCond %{THE_REQUEST} \s/+index\.html[?\s] [NC]
RewriteRule ^index\.html$ / [R=301,L]
```

Use essa regra somente se o portal estiver na raiz do domínio ou subdomínio, como no endereço indicado. Não é necessária para corrigir o botão Início.
