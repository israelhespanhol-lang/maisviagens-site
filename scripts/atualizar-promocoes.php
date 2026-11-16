<?php
/**
 * CRON CLI EXCLUSIVO: executar a cada hora com PHP da HostGator.
 * Não é possível chamar via navegador mesmo que a URL seja descoberta.
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../api/lib/travelpayouts.php';
$falhas = 0;
foreach (MV_TP_ORIGINS as $origin) {
    $result = mv_tp_get_fares($origin, true);
    $status = (string)($result['status'] ?? 'error');
    echo $origin . ': ' . $status . ', ' . count($result['offers'] ?? []) . " tarifas\n";
    if (in_array($status, ['pending','unavailable'], true)) $falhas++;
}
exit($falhas ? 1 : 0);
