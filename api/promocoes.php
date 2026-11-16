<?php
/** API pública somente de leitura; dados públicos, nunca o token. */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: public, max-age=120');
require_once __DIR__ . '/lib/travelpayouts.php';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    header('Allow: GET'); http_response_code(405);
    echo json_encode(['status'=>'method_not_allowed','offers'=>[]]); exit;
}
$origin = strtoupper(trim((string)($_GET['origem'] ?? 'FLN')));
if (!in_array($origin, MV_TP_ORIGINS, true)) {
    http_response_code(400);
    echo json_encode(['status'=>'invalid_origin','offers'=>[]]); exit;
}
try {
    $response = mv_tp_get_fares($origin);
} catch (Throwable $error) {
    error_log('Mais Viagens Travelpayouts: falha na consulta de tarifas.');
    $response = ['status'=>'unavailable','origin'=>$origin,'offers'=>[]];
}
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
