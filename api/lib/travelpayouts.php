<?php
/**
 * V18 - Integração Travelpayouts no servidor HostGator.
 * Preços são dados históricos, NÃO reserva/estoque em tempo real.
 * Sem endpoints de entrada de URLs de clientes, token jamais retornado em JSON.
 */
declare(strict_types=1);

const MV_TP_TRS = 589817;
const MV_TP_MARKER = 792290;
const MV_TP_ORIGINS = ['FLN', 'GRU', 'GIG', 'BSB'];
const MV_TP_TTL = 3600; // 1h; ideal também para Cron Jobs da HostGator.
const MV_TP_STALE = 10800; // Limite de exibição de cache após falha (3h).

function mv_tp_config(): array {
    $env = getenv('TRAVELPAYOUTS_API_TOKEN');
    $token = is_string($env) ? trim($env) : '';
    if ($token !== '') return ['token' => $token];
    $home = $_SERVER['HOME'] ?? getenv('HOME');
    if (!is_string($home) || $home === '') return [];
    $file = realpath(rtrim($home, '/\\') . '/.mais-viagens-travelpayouts.php');
    $root = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($file === false || ($root !== false && ($file === $root || str_starts_with($file, $root . DIRECTORY_SEPARATOR)))) return [];
    $config = require $file;
    return is_array($config) && is_string($config['token'] ?? null) && trim($config['token']) !== ''
        ? ['token' => trim($config['token'])] : [];
}

function mv_tp_cache_path(string $origin): string {
    // Escopo da instalação impede colisão de sites hospedados na mesma conta.
    return rtrim(sys_get_temp_dir(), '/') . '/maisviagens-v18-' . substr(hash('sha256', __DIR__), 0, 20) . '-' . strtolower($origin) . '.json';
}

function mv_tp_read_cache(string $origin): ?array {
    $path = mv_tp_cache_path($origin);
    if (!is_file($path)) return null;
    $str = @file_get_contents($path);
    $res = is_string($str) ? json_decode($str, true) : null;
    if (!is_array($res) || !is_array($res['offers'] ?? null)
        || ($res['origin'] ?? null) !== $origin || !is_int($res['cached_at'] ?? null)) return null;
    return $res;
}

function mv_tp_save_cache(string $origin, array $data): bool {
    $path = mv_tp_cache_path($origin);
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($json)) return false;
    $old = umask(0077);
    $name = tempnam(sys_get_temp_dir(), 'mv-tp-temp-');
    if ($name === false) { umask($old); return false; }
    $written = @file_put_contents($name, $json, LOCK_EX);
    @chmod($name, 0600);
    $done = $written !== false && @rename($name, $path);
    if (!$done) @unlink($name);
    umask($old);
    return $done;
}

function mv_tp_request(string $method, string $url, string $token, ?array $body = null): array {
    if (!function_exists('curl_init')) return ['error' => 'curl_unavailable'];
    $ch = curl_init($url);
    if ($ch === false) return ['error' => 'curl_unavailable'];
    $headers = ['X-Access-Token: ' . $token, 'Accept: application/json'];
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 13,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_USERAGENT => 'MaisViagens-Portal/18 (affiliate data API)',
        CURLOPT_ENCODING => '', // gzip/deflate do provedor
    ];
    if ($method === 'POST' && $body !== null) {
        $payload = json_encode($body, JSON_UNESCAPED_SLASHES);
        if (!is_string($payload)) { curl_close($ch); return ['error'=>'encoding']; }
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = $payload;
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
    }
    curl_setopt_array($ch, $options);
    $raw = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if (!is_string($raw) || $status !== 200 || strlen($raw) > 2000000) {
        return ['error' => $status === 429 ? 'rate_limited' : 'provider_unavailable'];
    }
    $res = json_decode($raw, true);
    return is_array($res) ? $res : ['error' => 'invalid_json'];
}

/**
 * Aceita apenas deep links no domínio oficial da Aviasales.
 * Não faz HTTP para URLs recebidas da API: são enviados somente ao host fixo da Links API.
 */
function mv_tp_aviasales_url(mixed $link): string {
    if (!is_string($link) || strlen($link) > 1800 || (preg_match('/[\x00-\x1F]/', $link) || str_contains($link, '\\'))) return '';
    $url = str_starts_with($link, '/search/') ? 'https://www.aviasales.com' . $link : $link;
    $parts = parse_url($url);
    if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
        || strtolower((string)($parts['host'] ?? '')) !== 'www.aviasales.com'
        || !str_starts_with((string)($parts['path'] ?? ''), '/search/')
        || isset($parts['user']) || isset($parts['pass']) || isset($parts['port'])) return '';
    return $url;
}

function mv_tp_parse_fares(array $response, string $origin, ?int $now = null): array {
    if (($response['success'] ?? null) !== true || !is_array($response['data'] ?? null)) return [];
    $currency = strtolower((string)($response['currency'] ?? ''));
    // Dados sem confirmação BRL não serão exibidos como reais.
    if ($currency !== 'brl') return [];
    $now ??= time();
    $items = [];
    foreach ($response['data'] as $o) {
        if (!is_array($o) || strtoupper((string)($o['origin'] ?? '')) !== $origin) continue;
        $dst = strtoupper((string)($o['destination'] ?? ''));
        $date = (string)($o['departure_at'] ?? '');
        $timestamp = $date !== '' ? strtotime($date) : false;
        $price = $o['price'] ?? null;
        if (!preg_match('/^[A-Z]{3}$/', $dst) || !is_numeric($price) || !is_finite((float)$price)
            || (float)$price <= 0 || $timestamp === false || $timestamp < $now + 3600) continue;
        $back = is_string($o['return_at'] ?? null) ? $o['return_at'] : '';
        $link = mv_tp_aviasales_url($o['link'] ?? null);
        $items[] = [
            'origin' => $origin, 'destination' => $dst,
            'destination_name' => $dst,
            'departure_at' => $date,
            'return_at' => $back !== '' && strtotime($back) !== false ? $back : '',
            'price' => round((float)$price, 2),
            'currency' => 'BRL',
            'one_way' => empty($back),
            'source' => 'Aviasales Data API',
            '_original_url' => $link,
        ];
    }
    usort($items, static fn(array $a, array $b): int => $a['price'] <=> $b['price']);
    $seen = []; $offers = [];
    foreach ($items as $o) {
        $key = $o['destination'] . '/' . substr($o['departure_at'], 0, 10);
        if (isset($seen[$key])) continue;
        $seen[$key] = true; $offers[] = $o;
        if (count($offers) >= 9) break;
    }
    return $offers;
}

/** Faz um lote único de até nove URLs: Links API permite até dez. */
function mv_tp_attach_links(array &$offers, string $token, ?callable $request = null): int {
    $request ??= 'mv_tp_request';
    $urls = [];
    foreach ($offers as $i => $offer) if (!empty($offer['_original_url'])) $urls[$i] = $offer['_original_url'];
    if (!$urls) return 0;
    $payload = ['trs' => MV_TP_TRS, 'marker' => MV_TP_MARKER, 'shorten' => true, 'links' => []];
    foreach ($urls as $url) $payload['links'][] = ['url' => $url, 'sub_id' => 'site-promocoes-voos'];
    $result = $request('POST', 'https://api.travelpayouts.com/links/v1/create', $token, $payload);
    $mapped = [];
    foreach (($result['result']['links'] ?? []) as $record) {
        if (!is_array($record) || ($record['code'] ?? '') !== 'success') continue;
        $dest = $record['partner_url'] ?? null;
        // Não entregar URL original quando conversão falha.
        if (!is_string($dest) || !filter_var($dest, FILTER_VALIDATE_URL)) continue;
        $p = parse_url($dest);
        if (!is_array($p) || ($p['scheme'] ?? '') !== 'https' || empty($p['host'])
            || isset($p['user']) || isset($p['pass'])) continue;
        $mapped[(string)($record['url'] ?? '')] = $dest;
    }
    $count = 0;
    foreach ($offers as &$offer) {
        $original = $offer['_original_url'];
        if ($original !== '' && isset($mapped[$original])) {
            $offer['partner_url'] = $mapped[$original]; $count++;
        }
        unset($offer['_original_url']);
    }
    unset($offer);
    return $count;
}

function mv_tp_public_result(array $cache, bool $stale = false): array {
    return [
        'status' => $stale ? 'stale' : (count($cache['offers']) ? 'ready' : 'empty'),
        'offers' => $cache['offers'],
        'origin' => $cache['origin'],
        'currency' => 'BRL',
        'updated_at' => gmdate('c', (int)$cache['cached_at']),
        'affiliate_links' => (int)($cache['affiliate_links'] ?? 0),
        'price_type' => 'cached_search_results',
    ];
}

function mv_tp_get_fares(string $origin, bool $force = false, ?callable $request = null, ?int $now = null): array {
    if (!in_array($origin, MV_TP_ORIGINS, true)) return ['status'=>'invalid_origin','offers'=>[],'origin'=>$origin];
    $now ??= time();
    $config = mv_tp_config();
    $cache = mv_tp_read_cache($origin);
    if (!$force && $cache && ($now - $cache['cached_at']) < MV_TP_TTL && $cache['cached_at'] <= $now) {
        return mv_tp_public_result($cache);
    }
    if (!$config) return ['status'=>'pending','offers'=>[],'origin'=>$origin];
    $request ??= 'mv_tp_request';
    $url = 'https://api.travelpayouts.com/aviasales/v3/prices_for_dates?' . http_build_query([
        'origin' => $origin, 'currency' => 'brl', 'market' => 'br',
        'one_way' => 'true', 'unique' => 'true', 'sorting' => 'price',
        'limit' => 80, 'page' => 1,
    ], '', '&', PHP_QUERY_RFC3986);
    $response = $request('GET', $url, $config['token']);
    if (!isset($response['error']) && ($response['success'] ?? null) === true
        && is_array($response['data'] ?? null) && strtolower((string)($response['currency'] ?? '')) === 'brl') {
        $offers = mv_tp_parse_fares($response, $origin, $now);
        $linked = mv_tp_attach_links($offers, $config['token'], $request);
        $cache = ['origin'=>$origin, 'offers'=>$offers, 'affiliate_links'=>$linked, 'cached_at'=>$now];
        mv_tp_save_cache($origin, $cache);
        return mv_tp_public_result($cache);
    }
    if ($cache && ($now - $cache['cached_at']) >= 0 && ($now - $cache['cached_at']) < MV_TP_STALE) {
        return mv_tp_public_result($cache, true);
    }
    return ['status'=>'unavailable','offers'=>[],'origin'=>$origin];
}
