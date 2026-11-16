<?php
/** Dados públicos gerados pelo cron: zero tokens, zero chamadas externas. */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=300');
require_once __DIR__.'/lib/integracoes.php';
if (($_SERVER['REQUEST_METHOD']??'GET')!=='GET') {http_response_code(405);header('Allow: GET');echo '{"status":"method_not_allowed","offers":[]}';exit;}
echo json_encode(mv19_public_feed_response(),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_INVALID_UTF8_SUBSTITUTE);
