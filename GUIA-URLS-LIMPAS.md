# Mais Viagens — Versao 21 — Enderecos sem .html (HostGator)

A pagina inicial passa a abrir em `https://portal.maisviagensbr.com.br/`.
As demais paginas ficam assim:

- `/viagens-personalizadas`
- `/caravanas`
- `/promocoes`
- `/orlando`
- `/aluguel-de-carros`
- `/contato`
- `/privacidade`

**Os arquivos .html continuam no servidor.** A pasta raiz tem um arquivo `.htaccess` para o Apache servir estes arquivos sem mostrar a extensao. Quem entrar em uma URL antiga, como `/promocoes.html`, sera encaminhado automaticamente para `/promocoes` (HTTP 301). Os links do menu, chamadas para acao, rodape, fragmentos `#` e links do painel foram atualizados. O funcionamento da API e dos widgets foi preservado.

## Como atualizar com seguranca

1. Na HostGator/cPanel, abra o Gerenciador de Arquivos e encontre a pasta do dominio `portal.maisviagensbr.com.br` (onde estao `index.html` e `promocoes.html`).
2. **Faca backup** dos arquivos atuais, especialmente se ja existir `.htaccess`: esse arquivo pode conter regras de SSL ou configuracoes adicionais. Nao substitua um `.htaccess` existente sem antes combinar as regras.
3. Para alterar apenas a navegacao, envie o arquivo ZIP **Correcao-URLs-Limpas-Mais-Viagens-HostGator.zip**, extraia na pasta do dominio e escolha substituir os arquivos iguais. Verifique se o **`.htaccess` oculto** foi enviado. Habilite a exibicao de arquivos ocultos (dotfiles) no gerenciador do cPanel.
4. Alternativamente, envie o pacote completo V21 e extraia no document root do subdominio.
5. Abra `/promocoes`, `/orlando`, `/contato`, `/#conteudo`, `/orlando#formulario`, `/painel-promocoes/`, e confirme que imagens, scripts e widgets continuam funcionando.
6. Teste uma URL antiga `/promocoes.html` e veja se redireciona para `/promocoes`.
7. Caso apareca erro HTTP 500, reverta o `.htaccess` antigo e confira suporte a `mod_rewrite` na sua hospedagem.

## HTTPS e seguranca

A URL limpa nao torna a conexao segura por si so. O aviso "Nao seguro" requer **SSL valido** e redirecionamento HTTPS na HostGator. So ative "Forcar HTTPS" depois de confirmar o certificado. Se existir um `.htaccess` antigo com configuracoes relevantes, preserve-as ao combinar as regras.

## Importante

Este pacote nao altera tokens nem passwords. A pagina `/painel-promocoes/` permanece com sua rota original e os endpoints `/api/*.php` nao sao reescritos. 
