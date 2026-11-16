<?php
/** Mais Viagens v19 — Conectores reais e independentes, PHP 8.1+ / HostGator.
 * Fonte: Travelpayouts (API token), Airalo (XML), WeGoTrip (catalog API),
 * Viator (feed JSON.gz fornecido sob solicitação) e consultas financeiras privadas.
 * Nunca expor token ou dados financeiros em endpoints públicos.
 */
declare(strict_types=1);
require_once __DIR__ . '/travelpayouts.php';

const MV19_FEEDS_TTL = 21600; // 6 horas; atualizar pelo cron para não sobrecarregar visitantes.
const MV19_FEEDS_STALE = 86400; // máximo 24h desde a coleta
const MV19_CACHE_ID = 'v19-' . '589817';

function mv19_path(string $name): string {
    if (!preg_match('/^[a-z0-9_-]{2,40}$/', $name)) throw new InvalidArgumentException('Identificador inválido');
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . '/mv-' . MV19_CACHE_ID . '-' . substr(hash('sha256', __DIR__), 0, 12) . '-' . $name . '.json';
}
function mv19_cache_get(string $key): ?array {
    $raw = @file_get_contents(mv19_path($key));
    $d = is_string($raw) ? json_decode($raw, true) : null;
    return is_array($d) && isset($d['updated_at']) && is_int($d['updated_at']) ? $d : null;
}
function mv19_cache_put(string $key, array $value): bool {
    $path = mv19_path($key);
    $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    if (!is_string($json)) return false;
    $prev = umask(0077);
    $tmp = tempnam(sys_get_temp_dir(), 'mv19_');
    $ok = $tmp !== false && @file_put_contents($tmp, $json, LOCK_EX) !== false;
    if ($tmp !== false) { @chmod($tmp, 0600); if ($ok) $ok = @rename($tmp, $path); if (!$ok) @unlink($tmp); }
    umask($prev);
    return $ok;
}
function mv19_url_ok(mixed $url): bool {
    if (!is_string($url) || strlen($url) > 2000 || !filter_var($url, FILTER_VALIDATE_URL)) return false;
    $p = parse_url($url);
    return is_array($p) && ($p['scheme'] ?? '') === 'https' && isset($p['host']) && !isset($p['user']) && !isset($p['pass']) && !isset($p['port']);
}
/** Somente provedores fixos: URL de visitante nunca chega a essa função. */
function mv19_http_get(string $url, string $kind, string $token = ''): array {
    $allow = [
      'wegotrip' => ['host'=>'app.wegotrip.com','path'=>'/api/v3/products/popular/'],
      'airalo' => ['host'=>'www.airalo.com','path'=>'/products.xml'],
      'finance' => ['host'=>'api.travelpayouts.com','path'=>'/finance/v2/'],
    ];
    if (!isset($allow[$kind]) || !mv19_url_ok($url)) return ['error'=>'invalid_source'];
    $p = parse_url($url);
    if (strtolower($p['host']) !== $allow[$kind]['host'] || !str_starts_with($p['path'] ?? '', $allow[$kind]['path'])) return ['error'=>'invalid_source'];
    if (!function_exists('curl_init')) return ['error'=>'curl_unavailable'];
    $ch = curl_init($url);
    $headers = ['Accept: application/json'];
    if ($kind === 'airalo') $headers = ['Accept: application/xml, text/xml'];
    if ($kind === 'wegotrip') $headers[] = 'Accept-Language: en';
    if ($kind === 'finance') { if (!$token) return ['error'=>'missing_token']; $headers[] = 'X-Access-Token: '.$token; }
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER=>true, CURLOPT_CONNECTTIMEOUT=>5,
        CURLOPT_TIMEOUT=>18, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_MAXREDIRS=>0,
        CURLOPT_SSL_VERIFYPEER=>true, CURLOPT_SSL_VERIFYHOST=>2,
        CURLOPT_PROTOCOLS=>CURLPROTO_HTTPS, CURLOPT_ENCODING=>'',
        CURLOPT_HTTPHEADER=>$headers, CURLOPT_USERAGENT=>'MaisViagens/19 (Travelpayouts catalog)']);
    // Remote feeds may be large. Limit downloaded bytes without leaking data.
    $buffer = '';
    curl_setopt($ch, CURLOPT_WRITEFUNCTION, static function($ch, string $chunk) use (&$buffer): int {
      if (strlen($buffer) + strlen($chunk) > 5000000) return 0;
      $buffer .= $chunk;
      return strlen($chunk);
    });
    $ok = curl_exec($ch);
    $http = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    if ($ok !== true || $http !== 200) return ['error'=>($http===403?'not_authorized':($http===429?'rate_limited':'provider_unavailable'))];
    return ['raw'=>$buffer];
}
function mv19_convert_links(array $originals, string $subid, string $token, ?callable $request = null): array {
    // Retorna somente URLs que a API confirmou; nada vai ao público sem atribuição.
    $urls = array_values(array_unique(array_filter($originals, 'mv19_url_ok')));
    $urls = array_slice($urls, 0, 30); // no máximo três chamadas x 10 links
    $converted=[];
    if ($token==='') return [];
    $request ??= 'mv_tp_request';
    foreach (array_chunk($urls, 10) as $chunk) {
      $payload=['trs'=>MV_TP_TRS,'marker'=>MV_TP_MARKER,'shorten'=>true,'links'=>[]];
      foreach ($chunk as $url) $payload['links'][]=['url'=>$url,'sub_id'=>$subid];
      $r=$request('POST','https://api.travelpayouts.com/links/v1/create',$token,$payload);
      foreach (($r['result']['links'] ?? []) as $item) {
        if (is_array($item) && ($item['code'] ?? '')==='success' && mv19_url_ok($item['partner_url'] ?? null)
          && in_array($item['url'] ?? '', $chunk, true)) $converted[$item['url']]=$item['partner_url'];
      }
    }
    return $converted;
}
function mv19_wegotrip_records(array $json): array {
    $list=$json['data']['results'] ?? null;
    if (!is_array($list)) return [];
    $offers=[];
    foreach (array_slice($list,0,30) as $p) {
      if (!is_array($p) || empty($p['id']) || empty($p['slug']) || !is_array($p['city']??null)) continue;
      $city=$p['city'];
      $cslug=$city['slug']??''; $pslug=$p['slug']; $pid=(int)$p['id']; $cid=(int)($city['id']??0);
      if (!$cid || !$pid || !preg_match('/^[a-z0-9-]+$/i', $cslug) || !preg_match('/^[a-z0-9-]+$/i', $pslug)) continue;
      if (($p['tags']['available']??true) === false) continue;
      $url='https://wegotrip.com/'.$cslug.'-d'.$cid.'/'.$pslug.'-p'.$pid.'/';
      $title=trim((string)($p['title']??'')); if ($title==='') continue;
      $currency = strtoupper((string)($p['currencyCode']??''));
      $price = $p['price']??null;
      $offers[]=['id'=>'wego-'.$pid,'tipo'=>'passeios','titulo'=>mb19($title,110),
        'descricao'=>'Audioguia ou experiência em '.mb19((string)($city['name']??'destino'),90).' · WeGoTrip',
        'preco'=>in_array($currency,['EUR','USD','BRL'],true) && is_numeric($price) && (float)$price > 0 ? number_format((float)$price,2,',','.').' '.$currency : '',
        'urlOriginal'=>$url,'fornecedor'=>'WeGoTrip'];
    }
    return $offers;
}
function mb19(string $v,int $size): string {return function_exists('mb_substr')?mb_substr($v,0,$size):substr($v,0,$size);}
function mv19_airalo_records(string $xml): array {
    // Evita entidades externas e expansão de entidades: somente XML do feed oficial.
    if (strlen($xml) > 5000000 || !class_exists('XMLReader') || !class_exists('DOMDocument')) return [];
    $reader=new XMLReader();
    if (!@$reader->XML($xml, null, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING)) return [];
    $items=[];
    try {
      while (@$reader->read() && count($items)<24) {
        if ($reader->nodeType!==XMLReader::ELEMENT || $reader->localName!=='item') continue;
        $node = @$reader->expand();
        if (!$node) continue;
        $dom=new DOMDocument();
        $copy=$dom->importNode($node,true); $dom->appendChild($copy);
        $get=static function(string $name) use ($dom):string {
          foreach ($dom->getElementsByTagName('*') as $element) {
            if ($element->localName===$name) return trim($element->textContent);
          }
          return '';
        };
        $link=$get('link'); $title=$get('title');
        if (!mv19_url_ok($link) || !preg_match('/(^|\.)airalo\.com$/',strtolower(parse_url($link,PHP_URL_HOST)?:'')) || !$title) continue;
        if (strtolower($get('availability'))==='out of stock') continue;
        $price=$get('sale_price')?:$get('price');
        $items[]=['id'=>'airalo-'.substr(sha1($link),0,12),'tipo'=>'esim','titulo'=>mb19($title,110),
          'descricao'=>mb19($get('description')?:'eSIM internacional Airalo',250),
          'preco'=>mb19($price,36),'urlOriginal'=>$link,'fornecedor'=>'Airalo'];
      }
    } finally {$reader->close();}
    return $items;
}
function mv19_viator_records(array $feed): array {
    $offers=[];
    foreach (array_slice($feed,0,40) as $row) {
      if (!is_array($row) || !mv19_url_ok($row['merchant_deep_link']??null)
        || !in_array(strtolower(parse_url($row['merchant_deep_link'],PHP_URL_HOST)?:''),['www.viator.com','viator.com'],true)) continue;
      if ((string)($row['is_for_sale']??'1')==='0') continue;
      $until=(string)($row['valid_to']??'');
      if ($until!=='' && preg_match('/^\d{4}-\d\d-\d\d$/',$until) && $until<gmdate('Y-m-d')) continue;
      $offers[]=['id'=>'viator-'.substr(sha1($row['merchant_deep_link']),0,12),
       'tipo'=>'passeios','titulo'=>mb19((string)($row['product_name']??'Passeio Viator'),110),
       'descricao'=>mb19((string)($row['promotional_text']??'Passeio da Viator'),220),
       'preco'=>(isset($row['search_price'],$row['currency']) && is_numeric($row['search_price'])?number_format((float)$row['search_price'],2,',','.').' '.mb19((string)$row['currency'],4):''),
       'urlOriginal'=>$row['merchant_deep_link'],'fornecedor'=>'Viator'];
    }
    return $offers;
}
function mv19_private_feed_file(): ?string {
  // Feed Viator NÃO vem com o token principal: suporte fornece arquivo .json.gz;
  // salvá-lo fora de public_html na pasta HOME é a maneira mais segura.
  $home=$_SERVER['HOME']??getenv('HOME');
  if (!is_string($home) || !$home) return null;
  $file=realpath(rtrim($home,'/').'/.mais-viagens-viator.json.gz');
  $root=realpath((string)($_SERVER['DOCUMENT_ROOT']??''));
  return $file && ($root===false || !str_starts_with($file,$root.'/')) && is_file($file) ? $file : null;
}
function mv19_refresh_feeds(?callable $get = null, ?callable $request = null): array {
  $get ??= 'mv19_http_get';
  $token=(string)(mv_tp_config()['token']??'');
  $sources=['wegotrip'=>'unavailable','airalo'=>'unavailable','viator'=>'waiting_for_feed'];
  $items=[];
  $wg=$get('https://app.wegotrip.com/api/v3/products/popular/?lang=en&currency=USD&page=1','wegotrip');
  if (isset($wg['raw'])) {
    $json=json_decode($wg['raw'],true);
    if (is_array($json)) {$rows=mv19_wegotrip_records($json); $items=array_merge($items,array_slice($rows,0,8));$sources['wegotrip']='ok';}
  }
  $airalo=$get('https://www.airalo.com/products.xml','airalo');
  if (isset($airalo['raw']) && !class_exists('XMLReader')) {
    $sources['airalo']='xml_extension_missing';
  } elseif (isset($airalo['raw'])) {
    $rows=mv19_airalo_records($airalo['raw']);
    $items=array_merge($items,array_slice($rows,0,8));
    $sources['airalo']='ok';
  } elseif (($airalo['error']??'')==='not_authorized') $sources['airalo']='access_denied';
  $file=mv19_private_feed_file();
  if ($file && filesize($file) < 3000000) {
    $data=@file_get_contents($file);
    $decoded=is_string($data)?@gzdecode($data):false;
    if (is_string($decoded) && strlen($decoded)<9000000) {
      $feed=json_decode($decoded,true);
      if (is_array($feed)) {$rows=mv19_viator_records(array_is_list($feed)?$feed:($feed['products']??[]));$items=array_merge($items,array_slice($rows,0,8));$sources['viator']='ok';}
    }
  }
  $originals=array_column($items,'urlOriginal');
  $mapped=mv19_convert_links($originals,'vitrine-maisviagens',$token,$request);
  $public=[];
  foreach ($items as $o) {
    if (isset($mapped[$o['urlOriginal']])) {
      $o['url']=$mapped[$o['urlOriginal']];unset($o['urlOriginal']);
      $public[]=$o;
    }
  }
  $cache=['updated_at'=>time(),'sources'=>$sources,'total_found'=>count($items),
      'unattributed'=>count($items)-count($public),'offers'=>array_slice($public,0,30)];
  mv19_cache_put('feeds',$cache);
  return $cache;
}
function mv19_public_feed_response(): array {
  $cache=mv19_cache_get('feeds');
  if (!$cache) return ['status'=>'pending','offers'=>[],'sources'=>[]];
  $age=time()-$cache['updated_at'];
  if ($age>MV19_FEEDS_STALE) return ['status'=>'stale_expired','offers'=>[],'updated_at'=>gmdate('c',$cache['updated_at'])];
  return ['status'=>$age>MV19_FEEDS_TTL?'stale':(count($cache['offers'])?'ready':'empty'),
    'offers'=>$cache['offers'],'updated_at'=>gmdate('c',$cache['updated_at']),
    'sources'=>$cache['sources']];
}
/** Somente deve ser chamada dentro do painel após validar sessão. */
function mv19_finance_summary(bool $refresh = false, ?callable $get = null): array {
  $config=mv_tp_config(); if (!$config) return ['status'=>'pending'];
  $cache=mv19_cache_get('finance');
  if (!$refresh && $cache && time()-$cache['updated_at']<900) return $cache;
  $get ??= 'mv19_http_get';
  $token=$config['token'];
  $balance=$get('https://api.travelpayouts.com/finance/v2/get_user_balance','finance',$token);
  $payout=$get('https://api.travelpayouts.com/finance/v2/get_user_next_payout','finance',$token);
  $payments=$get('https://api.travelpayouts.com/finance/v2/get_user_payments','finance',$token);
  if (!isset($balance['raw'],$payout['raw'])) return $cache ?? ['status'=>'unavailable'];
  $b=json_decode($balance['raw'],true);$p=json_decode($payout['raw'],true);
  if (!is_array($b) || !is_array($p) || !is_array($b['balance']??null) || !is_array($p['next_payout']??null)) return ['status'=>'invalid_data'];
  $pay=isset($payments['raw']) ? json_decode($payments['raw'],true) : null;
  $result=['status'=>'ready','updated_at'=>time(),'balance'=>$b['balance'],'next_payout'=>$p['next_payout'],
     'payment_count'=>is_array($pay)&&array_is_list($pay)?count($pay):null];
  mv19_cache_put('finance',$result);
  return $result;
}

/** Chamadas autenticadas de estatísticas com endpoint fixo; somente no painel. */
function mv19_statistics_request(string $path, string $token, ?array $body = null): array {
  if (!in_array($path,['get_fields_list','execute_query'],true) || !$token) return ['error'=>'invalid_request'];
  $url='https://api.travelpayouts.com/statistics/v1/'.$path;
  if ($path==='get_fields_list') $url.='?data_type=aggregated';
  return mv_tp_request($body ? 'POST':'GET',$url,$token,$body);
}
function mv19_statistics_summary(?callable $request=null): array {
  $config=mv_tp_config(); if (!$config) return ['status'=>'pending'];
  $cache=mv19_cache_get('statistics');
  if ($cache && time() - $cache['updated_at'] < 3600) return $cache;
  $request ??= 'mv19_statistics_request';
  $token=$config['token'];
  $fields=$request('get_fields_list',$token);
  if (isset($fields['error']) || !is_array($fields['fields']??null)) return $cache??['status'=>'unavailable'];
  $available=array_column($fields['fields'],'name');
  // Campanha 100 = Aviasales (documentação). Outros IDs precisam ser identificados
  // no URL do programa antes de solicitar seus dados, sem supor IDs.
  $wanted=['paid_actions_count','processing_actions_count','cancelled_actions_count','paid_profit_eur_sum','processing_profit_eur_sum','redirects_count','searches_count','inits_count'];
  $selected=array_values(array_intersect($wanted,$available));
  if (!$selected) return ['status'=>'fields_unavailable'];
  $end=gmdate('Y-m-d'); $start=gmdate('Y-m-d',strtotime('-30 days'));
  $payload=['fields'=>$selected,'filters'=>[
    ['field'=>'date','op'=>'ge','value'=>$start],
    ['field'=>'date','op'=>'le','value'=>$end],
    ['field'=>'campaign_id','op'=>'eq','value'=>100]
  ],'offset'=>0,'limit'=>100];
  $res=$request('execute_query',$token,$payload);
  if (isset($res['error']) || !is_array($res['results']??null)) return $cache??['status'=>'unavailable'];
  $result=['status'=>'ready','updated_at'=>time(),'program_id'=>100,'since'=>$start,'until'=>$end,
    'rows'=>array_slice($res['results'],0,10),'fields'=>$selected];
  mv19_cache_put('statistics',$result);
  return $result;
}
