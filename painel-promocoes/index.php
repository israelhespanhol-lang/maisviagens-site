<?php
/**
 * Mais Viagens v17 - painel de publicação de widgets e ofertas.
 * Não aceita HTML arbitrário nem armazena credenciais na pasta pública.
 * Configure o hash em $HOME/.mais-viagens-admin.php (fora do DocumentRoot).
 */
declare(strict_types=1);
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
session_name('MV_PROMOS_ADMIN');
session_set_cookie_params(['lifetime' => 0, 'path' => '/painel-promocoes', 'secure' => $https,
    'httponly' => true, 'samesite' => 'Strict']);
session_start();
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate');
header("Content-Security-Policy: default-src 'none'; style-src 'self'; form-action 'self'; base-uri 'none'; frame-ancestors 'none'");
header('Content-Type: text/html; charset=utf-8');

const TRS = '589817';
const MARKER = '792290';
const CATEGORIAS = [
 'passagens'=>'Passagens aéreas','hoteis'=>'Hotéis','passeios'=>'Passeios e ingressos',
 'carros'=>'Aluguel de carros','traslados'=>'Traslados','esim'=>'Internet/eSIM'
];
const WIDGETS = __DIR__ . '/../assets/data/widgets-travelpayouts.json';
const OFERTAS = __DIR__ . '/../assets/data/promocoes-parceiros.json';
require_once __DIR__ . '/../api/lib/integracoes.php';

function e(mixed $x): string { return htmlspecialchars((string)$x, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function corta(mixed $str, int $max): string {
 $s = is_string($str) ? trim($str) : '';
 return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
}
function carregar(string $file): array {
 $dados = @file_get_contents($file);
 $lido = $dados === false ? [] : json_decode($dados, true);
 return is_array($lido) && array_is_list($lido) ? $lido : [];
}
function salvar(string $file, array $value): bool {
 $json = json_encode(array_values($value), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE |
   JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
 if (!is_string($json)) return false;
 $folder = dirname($file);
 if (!is_writable($folder)) return false;
 $tmp = tempnam($folder, '.tmp-mv-');
 if (!$tmp) return false;
 $bytes = @file_put_contents($tmp, $json . "\n", LOCK_EX);
 @chmod($tmp, 0644);
 if (!$bytes || !@rename($tmp, $file)) { @unlink($tmp); return false; }
 return true;
}
function adminHash(): string {
 $env = getenv('MAIS_VIAGENS_ADMIN_PASSWORD_HASH');
 if (is_string($env) && $env !== '') return $env;
 $home = $_SERVER['HOME'] ?? getenv('HOME');
 if (!is_string($home) || $home === '') return '';
 $file = realpath(rtrim($home, '/') . '/.mais-viagens-admin.php');
 $public = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
 if (!$file || ($public && ($file === $public || str_starts_with($file, $public . '/')))) return '';
 $settings = require $file;
 return is_array($settings) && is_string($settings['password_hash'] ?? null) ? $settings['password_hash'] : '';
}
function srcWidget(string $codigo): string {
 if (strlen($codigo) > 8000) return '';
 $raw = trim($codigo);
 if (str_contains($raw, '<')) {
   if (!preg_match('~<script\b[^>]*\bsrc\s*=\s*(["\'])(.*?)\1~is', $raw, $m)) return '';
   $raw = $m[2];
 }
 $raw = html_entity_decode(trim($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');
 $parts = parse_url($raw);
 if (!is_array($parts) || strtolower((string)($parts['scheme'] ?? '')) !== 'https' ||
     !in_array(strtolower((string)($parts['host'] ?? '')), ['tp.media','tpemd.com'], true) ||
     ($parts['path'] ?? '') !== '/content' || isset($parts['user']) || isset($parts['pass']) ||
     isset($parts['port']) || isset($parts['fragment']) || strlen($raw) > 4000) return '';
 parse_str($parts['query'] ?? '', $q);
 if (!is_string($q['trs'] ?? null) || $q['trs'] !== TRS ||
     !is_string($q['shmarker'] ?? null) || $q['shmarker'] !== MARKER ||
     !ctype_digit((string)($q['promo_id'] ?? '')) || !ctype_digit((string)($q['campaign_id'] ?? ''))) return '';
 return $raw;
}
function validUrl(string $url): bool {
 if (strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) return false;
 $p = parse_url($url);
 return is_array($p) && ($p['scheme'] ?? '') === 'https' && !empty($p['host']) &&
   !isset($p['user']) && !isset($p['pass']);
}
function csrf(): string {
 if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
 return $_SESSION['csrf'];
}
function postar(string $name): string { return is_string($_POST[$name] ?? null) ? trim($_POST[$name]) : ''; }
function vaipara(string $target = 'widgets'): never {
 header('Location: index.php?aba=' . urlencode($target), true, 303); exit;
}
function falhaLogin(): bool {
 $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
 $path = sys_get_temp_dir() . '/mv-login-' . hash('sha256', __FILE__ . $ip) . '.json';
 $data = json_decode((string)@file_get_contents($path), true);
 return is_array($data) && (int)($data['until'] ?? 0) > time() && (int)($data['count'] ?? 0) >= 6;
}
function registrarFalha(): void {
 $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
 $path = sys_get_temp_dir() . '/mv-login-' . hash('sha256', __FILE__ . $ip) . '.json';
 $data = json_decode((string)@file_get_contents($path), true);
 if (!is_array($data) || ($data['until'] ?? 0) < time()) $data = ['count'=>0,'until'=>time() + 900];
 $data['count']++;
 $old=umask(0077); @file_put_contents($path,json_encode($data),LOCK_EX); umask($old);
}
function limparFalhas(): void {
 $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
 @unlink(sys_get_temp_dir() . '/mv-login-' . hash('sha256', __FILE__ . $ip) . '.json');
}
$hash = adminHash();
$erro = '';
$autenticado = !empty($_SESSION['admin']) && is_string($hash) && $hash !== '';
$aba = in_array(($_GET['aba'] ?? ''), ['widgets','ofertas','apis'], true) ? $_GET['aba'] : 'widgets';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
 $action = postar('acao');
 if (!hash_equals(csrf(), postar('csrf'))) { http_response_code(403); $erro='Sessão expirada. Recarregue a página.'; }
 elseif ($action === 'entrar' && $hash !== '' && !$autenticado) {
   if (falhaLogin()) $erro='Muitas tentativas. Tente novamente mais tarde.';
   elseif (password_verify(postar('senha'), $hash)) {
     session_regenerate_id(true); $_SESSION['admin']=true; limparFalhas(); csrf(); vaipara();
   } else { registrarFalha(); $erro='Senha incorreta.'; }
 } elseif ($action === 'sair' && $autenticado) {
   $_SESSION=[]; session_destroy(); vaipara();
 } elseif (!$autenticado) { http_response_code(403); $erro='Acesso não autorizado.'; }
 else {
   if (in_array($action,['adicionar_widget','widget_acao'],true)) {
     $itens = carregar(WIDGETS);
     if ($action === 'adicionar_widget') {
       $src = srcWidget(postar('codigo'));
       $cat = postar('categoria');
       $pos = postar('posicao') === 'destaque' ? 'destaque' : 'grade';
       if (!$src) $erro='Código inválido. Cole um script oficial tp.media/tpemd.com gerado para o projeto 589817 e marker 792290.';
       elseif (!isset(CATEGORIAS[$cat])) $erro='Categoria inválida.';
       elseif (count($itens) >= 40) $erro='Limite de 40 widgets atingido.';
       elseif ($pos === 'destaque' && $cat !== 'passagens') $erro='O destaque é reservado ao buscador de passagens.';
       elseif (in_array($src, array_column($itens,'src'),true)) $erro='Esse widget já está cadastrado.';
       else {
         if ($pos === 'destaque') foreach ($itens as &$x) { if (($x['posicao'] ?? '') === 'destaque') $x['posicao']='grade'; } unset($x);
         $itens[] = ['id'=>'widget-'.bin2hex(random_bytes(5)), 'parceiro'=>corta(postar('parceiro'),60) ?: 'Parceiro',
            'categoria'=>$cat,'titulo'=>corta(postar('titulo'),100) ?: 'Pesquisar com parceiro',
            'descricao'=>corta(postar('descricao'),220),'src'=>$src,'posicao'=>$pos,'ativo'=>true];
         if (salvar(WIDGETS,$itens)) vaipara(); else $erro='Não foi possível salvar. Verifique permissão de escrita em assets/data.';
       }
     } else {
       $id = postar('id'); $mov = postar('operacao');
       $found = null;
       foreach ($itens as $i=>$w) { if (($w['id'] ?? '') === $id) { $found=$i; break; } }
       if ($found === null) $erro='Widget não encontrado.';
       else {
         if ($mov==='excluir') array_splice($itens,$found,1);
         elseif ($mov==='alternar') $itens[$found]['ativo']=!($itens[$found]['ativo'] ?? false);
         elseif ($mov==='subir' && $found > 0) [$itens[$found-1],$itens[$found]]=[$itens[$found],$itens[$found-1]];
         elseif ($mov==='descer' && $found < count($itens)-1) [$itens[$found+1],$itens[$found]]=[$itens[$found],$itens[$found+1]];
         elseif ($mov==='destaque' && ($itens[$found]['categoria'] ?? '') === 'passagens') {
           foreach ($itens as &$x) { if (($x['posicao'] ?? '') === 'destaque') $x['posicao']='grade'; } unset($x);
           $itens[$found]['posicao']='destaque';
         } else $erro='Operação inválida.';
         if (!$erro) { if(salvar(WIDGETS,$itens)) vaipara(); else $erro='Falha ao salvar widgets.'; }
       }
     }
     $aba='widgets';
   } elseif (in_array($action,['adicionar_oferta','excluir_oferta'],true)) {
     $itens = carregar(OFERTAS);
     if ($action==='excluir_oferta') {
       $itens=array_values(array_filter($itens,fn($x)=>($x['id'] ?? '') !== postar('id')));
       if(salvar(OFERTAS,$itens)) vaipara('ofertas'); else $erro='Falha ao excluir oferta.';
     } else {
       $url=postar('url'); $cat=postar('categoria');
       if (!validUrl($url)) $erro='Informe uma URL HTTPS válida do programa parceiro.';
       elseif (!isset(CATEGORIAS[$cat])) $erro='Categoria inválida.';
       elseif (postar('titulo')==='') $erro='Informe um título.';
       elseif (count($itens)>=30) $erro='Limite de 30 ofertas atingido.';
       else {
         $item=['id'=>'oferta-'.bin2hex(random_bytes(5)), 'tipo'=>$cat,
           'titulo'=>corta(postar('titulo'),120),'descricao'=>corta(postar('descricao'),300),
           'imagem'=>'', 'preco'=>corta(postar('preco'),65), 'validade'=>corta(postar('validade'),100),
           'urlOriginal'=>$url];
         // Valores nunca são inventados pelo site: o operador é responsável por confirmar preço e validade.
         $itens[]=$item;
         if(salvar(OFERTAS,$itens)) vaipara('ofertas'); else $erro='Falha ao salvar oferta.';
       }
     }
     $aba='ofertas';
   } else $erro='Ação desconhecida.';
 }
}
$widgets = $autenticado ? carregar(WIDGETS) : [];
$ofertas = $autenticado ? carregar(OFERTAS) : [];
?><!doctype html>
<html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex,nofollow,noarchive"><title>Painel de Promoções | Mais Viagens</title><link rel="icon" type="image/x-icon" href="/favicon-m-v24.ico?v=26"><link rel="icon" type="image/png" sizes="32x32" href="/favicon-m-v24-32.png?v=26">
<link rel="stylesheet" href="painel.css"></head><body>
<header><div class="topbar"><div><span class="micro">MAIS VIAGENS · HOSTGATOR</span><h1>Painel de Promoções</h1></div><a href="/promocoes">Ver site ↗</a></div></header>
<main>
<?php if ($erro): ?><div class="alerta" role="alert"><?=e($erro)?></div><?php endif; ?>
<?php if ($hash === ''): ?>
<section class="bloco"><h2>Configure a senha antes de usar</h2><p>Este painel está bloqueado por segurança. Crie o arquivo <code>.mais-viagens-admin.php</code> na pasta HOME do cPanel, <strong>fora de public_html</strong>, com o hash da sua senha. Veja <code>GUIA-PAINEL-PROMOCOES.md</code> no ZIP.</p></section>
<?php elseif (!$autenticado): ?>
<section class="bloco acesso"><h2>Acesso restrito</h2><p>Entre com a senha administrativa configurada no servidor.</p><form method="post"><input type="hidden" name="acao" value="entrar"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><label>Senha<input type="password" name="senha" required autocomplete="current-password"></label><button class="primario">Entrar</button></form></section>
<?php else: ?>
<div class="navegacao"><nav><a class="<?= $aba==='widgets' ? 'ativo':'' ?>" href="?aba=widgets">Widgets (<?=count($widgets)?>)</a><a class="<?= $aba==='ofertas' ? 'ativo':'' ?>" href="?aba=ofertas">Ofertas (<?=count($ofertas)?>)</a><a class="<?= $aba==='apis' ? 'ativo':'' ?>" href="?aba=apis">APIs automáticas</a></nav><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="acao" value="sair"><button class="discreto">Sair</button></form></div>
<?php if ($aba==='apis'): ?>
<section class="bloco"><span class="micro">CENTRAL DE INTEGRAÇÕES · V19</span><h2>Parceiros, feeds e receitas</h2>
<p>Apenas esta área autenticada apresenta informações financeiras. Nenhum token é enviado ao navegador.</p>
<?php $feed = mv19_cache_get('feeds'); $finance = mv19_finance_summary(); $stats = mv19_statistics_summary(); ?>
<p><strong>API Token Travelpayouts:</strong> <?= mv_tp_config() ? 'Configurado no servidor' : 'Pendente de configuração segura' ?></p>
<div class="itens">
<?php $states=[
'Aviasales (tarifas aéreas)'=>'Pronto para consultar com token',
'Links API (conversão de ofertas)'=>'Pronto para consultar com token + programas habilitados',
'Estatísticas por programa'=>'Consulta automática implementada para a campanha Aviasales (ID 100)',
'Airalo (feed XML)'=>($feed['sources']['airalo']??'Aguardando atualização do Cron'),
'WeGoTrip (catálogo API)'=>($feed['sources']['wegotrip']??'Aguardando atualização do Cron'),
'Viator (feed de promoções)'=>($feed['sources']['viator']??'Aguardando feed do suporte'),
'Tiqets (IDs para widgets)'=>'Planilha de IDs fornecida pelo parceiro; não traz ofertas precificadas',
'Kiwi.com (Search API)'=>'Precisa de aprovação e credenciais do parceiro',
'GetTransfer (API)'=>'Precisa de aprovação e credenciais do parceiro'];
foreach($states as $name=>$status): ?><div class="item"><div><strong><?=e($name)?></strong><p><?=e($status)?></p></div></div><?php endforeach; ?>
</div>
<p><strong>Última atualização dos feeds:</strong> <?= $feed ? e(gmdate('d/m/Y H:i',$feed['updated_at']).' UTC') : 'Não executada' ?>;
<?= $feed ? (int)count($feed['offers']) . ' ofertas com atribuição' : 'sem cache' ?>.</p>
<h3>Financeiro de afiliados (restrito à equipe)</h3>
<?php if (($finance['status']??'') === 'ready'): ?>
<p>Consulta do balanço e próximo pagamento, diretamente da API Finance do Travelpayouts. Moedas originais do provedor:</p>
<div class="itens"><?php foreach(['balance'=>'Saldo','next_payout'=>'Próximo pagamento'] as $key=>$label): ?>
<div class="item"><strong><?=e($label)?></strong><div><?php foreach (($finance[$key]??[]) as $currency=>$amount): ?> <p><?=e(strtoupper((string)$currency))?>: <?=e($amount)?></p><?php endforeach; ?></div></div>
<?php endforeach; ?></div>
<?php else: ?><p>Dados financeiros: <?=e($finance['status']??'pendente')?>. O painel não mostra valores simulados.</p><?php endif; ?>
<p><strong>Histórico de pagamentos:</strong> <?=isset($finance['payment_count']) ? (int)$finance['payment_count'].' registros encontrados' : 'Não consultado' ?>.</p>
<h3>Indicadores Aviasales (últimos 30 dias)</h3>
<?php if (($stats['status']??'')==='ready'): ?>
<?php if (!$stats['rows']): ?><p>Sem indicadores registrados para o período.</p><?php else: ?>
<div class="itens"><?php foreach($stats['rows'] as $row): ?><div class="item"><div><?php foreach($stats['fields'] as $field): if(array_key_exists($field,$row)): ?>
<p><?=e($field)?>: <strong><?=e($row[$field])?></strong></p><?php endif;endforeach; ?></div></div><?php endforeach; ?></div><?php endif; ?>
<?php else: ?><p>Estatísticas: <?=e($stats['status']??'pendente')?>. Outros programas poderão ser incluídos ao obter seus IDs oficiais.</p><?php endif; ?>
<p>O cron de coleta não é executado por visitantes. Verifique <code>GUIA-INTEGRACOES-API.md</code> para ativá-lo na HostGator.</p>
</section>
<section class="bloco"><span class="micro">HOSTGATOR · TRAVELPAYOUTS</span><h2>APIs automáticas</h2>
<p>As tarifas aéreas vêm da Aviasales Data API (preços pesquisados anteriormente); a Links API tenta gerar URLs afiliadas. Nenhuma credencial é mostrada aqui.</p>
<p><strong>Token privado:</strong> <?=mv_tp_config() ? 'Configurado no servidor' : 'Pendente (crie o arquivo privado na HOME do cPanel)'?></p>
<p><strong>Projeto:</strong> Portal 589817 · <strong>Marker:</strong> 792290</p>
<p><strong>Atualização:</strong> cache com validade de uma hora; pode atualizar no acesso ou pelo Cron Jobs da HostGator.</p>
<div class="itens">
<?php foreach(MV_TP_ORIGINS as $orig): $dados = mv_tp_read_cache($orig); $idade = $dados ? time() - $dados['cached_at'] : null; ?>
<div class="item"><div><strong><?=e($orig)?></strong>
<p><?= $dados ? count($dados['offers']) . ' tarifas · ' . (int)($dados['affiliate_links'] ?? 0) . ' links convertidos' : 'Nenhum resultado no cache' ?></p>
<p><?= $dados ? 'Última atualização: ' . e(gmdate('d/m/Y H:i', $dados['cached_at']) . ' UTC') . ($idade > MV_TP_TTL ? ' · Atualização vencida' : ' · Cache válido') : 'Aguardando primeira consulta' ?></p>
</div></div>
<?php endforeach; ?>
</div>
<p><a href="/promocoes">Ver a vitrine de promoções ↗</a></p>
</section>
<?php elseif ($aba==='widgets'): ?>
<section class="bloco"><span class="micro">CADASTRO SEM EDITAR HTML</span><h2>Adicionar widget do Travelpayouts</h2><p>Cole o código completo <code>&lt;script ...&gt;</code> que o Travelpayouts fornecer ou somente a URL <code>src</code>. O painel valida os identificadores, salva e publica na página automaticamente.</p>
<form method="post" class="gridform"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="acao" value="adicionar_widget">
<label>Parceiro<input name="parceiro" maxlength="60" placeholder="Ex.: Tiqets" required></label>
<label>Categoria<select name="categoria"><?php foreach(CATEGORIAS as $val=>$nome): ?><option value="<?=e($val)?>"><?=e($nome)?></option><?php endforeach; ?></select></label>
<label class="largura">Título exibido no site<input name="titulo" maxlength="100" placeholder="Encontre passeios para sua próxima viagem" required></label>
<label class="largura">Descrição (opcional)<input name="descricao" maxlength="220" placeholder="Pesquise atrações disponíveis no destino"></label>
<label class="largura">Código ou URL oficial do widget<textarea name="codigo" maxlength="8000" rows="4" placeholder="&lt;script async src=&quot;https://tp.media/content?...&quot;&gt;&lt;/script&gt;" required></textarea></label>
<label>Exibir em<select name="posicao"><option value="grade">Grade de parceiros</option><option value="destaque">Buscador principal (apenas passagens)</option></select></label>
<div class="alinhabaixo"><button class="primario">Salvar e publicar widget</button></div></form></section>
<section class="bloco"><h2>Widgets cadastrados</h2><p>A ativação e a ordem são atualizadas imediatamente no catálogo do site (sem novo ZIP).</p>
<?php if (!$widgets): ?><p>Nenhum widget cadastrado.</p><?php else: ?><div class="itens">
<?php foreach($widgets as $w): ?><div class="item"><div><strong><?=e($w['titulo'] ?? '')?></strong><p><?=e($w['parceiro'] ?? '')?> · <?=e(CATEGORIAS[$w['categoria'] ?? ''] ?? '')?> · <?=($w['posicao'] ?? '') === 'destaque' ? 'Buscador principal' : 'Grade'?></p><span class="status <?=empty($w['ativo'])?'off':'on'?>"><?=empty($w['ativo'])?'Pausado':'Ativo'?></span></div>
<form method="post" class="acoes"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="acao" value="widget_acao"><input type="hidden" name="id" value="<?=e($w['id'] ?? '')?>">
<button name="operacao" value="alternar"><?=empty($w['ativo'])?'Ativar':'Pausar'?></button><button name="operacao" value="subir" title="Mover para cima">↑</button><button name="operacao" value="descer" title="Mover para baixo">↓</button><?php if (($w['categoria'] ?? '')==='passagens' && ($w['posicao'] ?? '')!=='destaque'): ?><button name="operacao" value="destaque">Destacar</button><?php endif; ?><button class="perigo" name="operacao" value="excluir">Excluir</button></form></div><?php endforeach; ?>
</div><?php endif; ?></section>
<?php else: ?>
<section class="bloco"><span class="micro">CARTÕES AUTOMÁTICOS VIA LINKS API</span><h2>Cadastrar oferta</h2><p>Cole a página oficial do parceiro. Quando a API Token estiver configurada na HostGator, o site tentará convertê-la para link afiliado. O cartão só aparece se a conversão tiver sucesso. <strong>Não invente preços nem descontos.</strong></p>
<form method="post" class="gridform"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="acao" value="adicionar_oferta">
<label>Categoria<select name="categoria"><?php foreach(CATEGORIAS as $val=>$nome): ?><option value="<?=e($val)?>"><?=e($nome)?></option><?php endforeach; ?></select></label>
<label>Título<input name="titulo" maxlength="120" placeholder="Passeio em Roma" required></label>
<label class="largura">Descrição<input name="descricao" maxlength="300" placeholder="Consulte datas e condições no parceiro"></label>
<label class="largura">URL da oferta no parceiro<input type="url" name="url" maxlength="2048" placeholder="https://site-do-parceiro.com/oferta" required></label>
<label>Preço confirmado (opcional)<input name="preco" maxlength="65" placeholder="Deixe vazio se não verificado"></label>
<label>Validade confirmada (opcional)<input name="validade" maxlength="100" placeholder="Somente se verificada"></label>
<div class="alinhabaixo"><button class="primario">Cadastrar oferta</button></div></form></section>
<section class="bloco"><h2>Ofertas cadastradas</h2><p>A Links API ainda exige uma API Token no servidor e programa compatível com essa função.</p>
<?php if (!$ofertas): ?><p>Nenhuma oferta cadastrada.</p><?php else: ?><div class="itens">
<?php foreach($ofertas as $o): ?><div class="item"><div><strong><?=e($o['titulo'] ?? '')?></strong><p><?=e(CATEGORIAS[$o['tipo'] ?? ''] ?? '')?> · <?=e($o['urlOriginal'] ?? ($o['urlAfiliada'] ?? ''))?></p></div><form method="post"><input type="hidden" name="csrf" value="<?=e(csrf())?>"><input type="hidden" name="acao" value="excluir_oferta"><input type="hidden" name="id" value="<?=e($o['id'] ?? '')?>"><button class="perigo">Excluir</button></form></div><?php endforeach; ?>
</div><?php endif; ?></section>
<?php endif; ?>
<aside class="rodape"><strong>Projeto Portal</strong> · TRS 589817 · Marker 792290<br>O painel não cria códigos oficiais de widgets: esses códigos variam por parceiro. Depois do primeiro cadastro, gerenciar sua publicação fica centralizado aqui.</aside>
<?php endif; ?>
</main></body></html>
