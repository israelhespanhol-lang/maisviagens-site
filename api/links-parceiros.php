<?php
/**
 * Mais Viagens v15 — vitrine de promoções com Links API Travelpayouts.
 * GET /api/links-parceiros.php
 * Lê ofertas cadastradas em assets/data/promocoes-parceiros.json.
 * Converte SOMENTE os URLs desse catálogo; o navegador não pode informar URLs
 * a serem convertidos. O token fica exclusivamente fora de public_html.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=180');

function responder(int $status, array $dados): never {
    http_response_code($status);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}
function httpsValida(mixed $url): bool {
    if (!is_string($url) || strlen($url) > 2048 || !filter_var($url, FILTER_VALIDATE_URL)) {
        return false;
    }
    $componentes = parse_url($url);
    return is_array($componentes) && strtolower((string)($componentes['scheme'] ?? '')) === 'https'
        && isset($componentes['host']) && !isset($componentes['user']) && !isset($componentes['pass']);
}
function resumoItem(array $item): array {
    $cortar = static fn(mixed $s, int $max): string => mbCut(is_string($s) ? trim($s) : '', $max);
    $imagem = $cortar($item['imagem'] ?? '', 120);
    if ($imagem !== '' && !preg_match('~^assets/img/[a-zA-Z0-9_-]+\.(?:jpg|jpeg|png|webp)$~', $imagem)) {
        $imagem = '';
    }
    return [
        'id' => $cortar($item['id'] ?? '', 40),
        'tipo' => $cortar($item['tipo'] ?? '', 20),
        'titulo' => $cortar($item['titulo'] ?? '', 120),
        'descricao' => $cortar($item['descricao'] ?? '', 300),
        'imagem' => $imagem,
        'preco' => $cortar($item['preco'] ?? '', 65),
        'validade' => $cortar($item['validade'] ?? '', 100),
    ];
}
function mbCut(string $s, int $max): string {
    return function_exists('mb_substr') ? mb_substr($s, 0, $max) : substr($s, 0, $max);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET');
    responder(405, ['status' => 'method_not_allowed', 'offers' => []]);
}

$arquivo = dirname(__DIR__) . '/assets/data/promocoes-parceiros.json';
$conteudo = @file_get_contents($arquivo);
$catalogo = is_string($conteudo) ? json_decode($conteudo, true) : null;
if (!is_array($catalogo) || !array_is_list($catalogo)) {
    responder(503, ['status' => 'invalid_catalog', 'offers' => []]);
}

// Aceita no máximo 30 ofertas. O catálogo não contém segredos nem preços fictícios.
$catalogo = array_slice($catalogo, 0, 30);
$itens = [];
foreach ($catalogo as $registro) {
    if (!is_array($registro)) continue;
    $item = resumoItem($registro);
    if (!preg_match('/^[a-z0-9][a-z0-9-]{1,39}$/', $item['id']) || $item['titulo'] === ''
        || !in_array($item['tipo'], ['passagens', 'hoteis', 'passeios', 'carros', 'traslados', 'esim'], true)
        || isset($itens[$item['id']])) continue;

    $urlAfiliada = $registro['urlAfiliada'] ?? '';
    $urlOriginal = $registro['urlOriginal'] ?? '';
    // URL de afiliado manual: insira apenas se já foi gerado pela conta da Mais Viagens.
    if (httpsValida($urlAfiliada)) {
        $item['url'] = $urlAfiliada;
    } elseif (httpsValida($urlOriginal)) {
        $item['urlOriginal'] = $urlOriginal;
    } else {
        continue;
    }
    $itens[$item['id']] = $item;
}
if (!$itens) responder(200, ['status' => 'empty', 'offers' => []]);

// Configurações privadas lidas da HOME ou das variáveis de ambiente do PHP.
$privado = [];
$home = $_SERVER['HOME'] ?? getenv('HOME');
if (is_string($home) && $home !== '') {
    $caminho = realpath(rtrim($home, DIRECTORY_SEPARATOR) . '/.mais-viagens-travelpayouts.php');
    $documentRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($caminho !== false && ($documentRoot === false ||
        ($caminho !== $documentRoot && !str_starts_with($caminho, $documentRoot . DIRECTORY_SEPARATOR)))) {
        $lido = require $caminho;
        if (is_array($lido)) $privado = $lido;
    }
}
$token = getenv('TRAVELPAYOUTS_API_TOKEN') ?: ($privado['token'] ?? '');
$marker = getenv('TRAVELPAYOUTS_MARKER') ?: ($privado['marker'] ?? null);
$trs = getenv('TRAVELPAYOUTS_TRS') ?: ($privado['trs'] ?? null);
$token = is_string($token) ? trim($token) : '';
$marker = filter_var($marker, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$trs = filter_var($trs, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

$semLink = [];
$prontas = [];
foreach ($itens as $item) {
    if (isset($item['url'])) {
        $prontas[$item['id']] = $item;
    } else {
        $semLink[$item['id']] = $item;
    }
}

$temCredenciais = ($token !== '' && is_int($marker) && is_int($trs));
if ($semLink && $temCredenciais && function_exists('curl_init')) {
    // O hash inclui credenciais sem expô-las e muda ao alterar ofertas ou conta.
    $hash = hash('sha256', __FILE__ . $conteudo . $token . $marker . $trs);
    $cache = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/mv-links-' . $hash . '.json';
    $convertidas = null;
    if (is_file($cache) && (time() - (int)@filemtime($cache)) < 1800) {
        $cacheConteudo = @file_get_contents($cache);
        $cacheDados = is_string($cacheConteudo) ? json_decode($cacheConteudo, true) : null;
        if (is_array($cacheDados)) $convertidas = $cacheDados;
    }
    if ($convertidas === null) {
        $convertidas = [];
        // API oficial aceita no máximo 10 URLs por requisição.
        foreach (array_chunk($semLink, 10, true) as $grupo) {
            $links = [];
            foreach ($grupo as $id => $item) {
                $links[] = ['url' => $item['urlOriginal'], 'sub_id' => 'site_promocoes_' . $id];
            }
            $corpo = json_encode([
                'trs' => (int)$trs, 'marker' => (int)$marker,
                'shorten' => false, 'links' => $links,
            ], JSON_UNESCAPED_SLASHES);
            if (!is_string($corpo)) continue;
            $curl = curl_init('https://api.travelpayouts.com/links/v1/create');
            curl_setopt_array($curl, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $corpo,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_HTTPHEADER => [
                    'X-Access-Token: ' . $token,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
            ]);
            $retorno = curl_exec($curl);
            $http = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            curl_close($curl);
            if (!is_string($retorno) || $http !== 200) continue;
            $dados = json_decode($retorno, true);
            $results = $dados['result']['links'] ?? null;
            if (!is_array($results)) continue;
            // Corresponder as URLs originais, e não confiar na posição da resposta.
            foreach ($results as $link) {
                if (!is_array($link) || ($link['code'] ?? '') !== 'success') continue;
                if (!httpsValida($link['partner_url'] ?? null)) continue;
                foreach ($grupo as $id => $item) {
                    if (($link['url'] ?? '') === $item['urlOriginal']) {
                        $convertidas[$id] = $link['partner_url'];
                        break;
                    }
                }
            }
        }
        // Permissão restrita desde a criação, para servidores compartilhados.
        $old = umask(0077);
        @file_put_contents($cache, json_encode($convertidas, JSON_UNESCAPED_SLASHES), LOCK_EX);
        umask($old);
    }
    foreach ($convertidas as $id => $url) {
        if (isset($semLink[$id]) && httpsValida($url)) {
            $item = $semLink[$id];
            unset($item['urlOriginal']);
            $item['url'] = $url;
            $prontas[$id] = $item;
        }
    }
}

// Não publicar links originais se a conversão falhar. Evita perder atribuição.
$ordenadas = [];
foreach (array_keys($itens) as $id) {
    if (isset($prontas[$id])) $ordenadas[] = $prontas[$id];
}
$pendentes = count($itens) - count($ordenadas);
$status = $ordenadas ? ($pendentes ? 'partial' : 'ready') : ($temCredenciais ? 'unavailable' : 'pending');
responder(200, [
    'status' => $status,
    'offers' => $ordenadas,
    'total' => count($itens),
    'pending' => $pendentes,
]);
