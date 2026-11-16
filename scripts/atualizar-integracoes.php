<?php
/** Rodar via Cron Jobs do cPanel de 6 em 6 horas (CLI somente). */
declare(strict_types=1);
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require_once __DIR__.'/../api/lib/integracoes.php';
if (!mv_tp_config()) {fwrite(STDERR,"API Token ausente; configurar na HOME do cPanel.\n");exit(2);}
$d=mv19_refresh_feeds();
echo 'Fontes: '.json_encode($d['sources'],JSON_UNESCAPED_UNICODE).' | Encontradas: '.$d['total_found'].' | Afiliadas: '.count($d['offers']).PHP_EOL;
exit(0);
