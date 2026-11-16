<?php
/** Testes offline determinísticos. Executar: php scripts/teste-integracoes.php */
declare(strict_types=1);
if (PHP_SAPI!=='cli') exit(404);
require_once __DIR__.'/../api/lib/integracoes.php';
function check19(bool $condition,string $desc):void { if (!$condition) {fwrite(STDERR,"FALHA: $desc\n");exit(1);}echo "OK: $desc\n";}
$data=['data'=>['results'=>[['id'=>25,'slug'=>'museu-do-louvre','title'=>'Passeio pelo museu','price'=>30,'currencyCode'=>'EUR','city'=>['name'=>'Paris','slug'=>'paris','id'=>3]]]]];
$wego=mv19_wegotrip_records($data);
check19(count($wego)===1 && ($wego[0]['urlOriginal']??'')==='https://wegotrip.com/paris-d3/museu-do-louvre-p25/','Normalizar API WeGoTrip');
$testXml=<<<'XML'
<rss xmlns:g="http://base.google.com/ns/1.0"><channel><item><g:title>eSIM Roma 3GB</g:title><g:link>https://www.airalo.com/italy-esim</g:link><g:price>9.00 USD</g:price></item><item><g:link>http://evil.test/</g:link><g:title>Inválido</g:title></item></channel></rss>
XML;
if (class_exists('XMLReader')) {
$airalo=mv19_airalo_records($testXml);
check19(count($airalo)===1 && $airalo[0]['preco']==='9.00 USD','XML Airalo com bloqueio de links inválidos');
} else echo "SKIP: XMLReader ausente no ambiente\n";
$viator=mv19_viator_records([['product_name'=>'Tour de Roma','search_price'=>'99.8','currency'=>'USD','merchant_deep_link'=>'https://www.viator.com/tours/Rome/Example/d123','is_for_sale'=>'1'],['merchant_deep_link'=>'https://other-site.example/foo','product_name'=>'Invasor']]);
check19(count($viator)===1,'Filtro de domínios do feed Viator');
$requests=0;
$mock=static function (string $method,string $url,string $token,?array $body=null) use (&$requests):array {
    ++$requests;
    return ['result'=>['links'=>array_map(static fn($x)=>['url'=>$x['url'],'code'=>'success','partner_url'=>'https://tp.media/r/aff_123'],$body['links'])]];
};
$valid=array_fill(0,11,'https://wegotrip.com/paris-d3/museu-do-louvre-p25/');
$mapped=mv19_convert_links($valid,'teste','token_teste',$mock);
check19($requests===1 && count($mapped)===1,'Links API: deduplicação e validação');
check19(!mv19_url_ok('https://foo.bar:8080/baz') && !mv19_url_ok('http://example.com'),'Bloqueio de URLs não HTTPS/porta');
$pub=mv19_public_feed_response();
check19(isset($pub['status']) && !isset($pub['token']),'Resposta pública não contém token');
echo "SUCESSO: testes offline concluídos\n";
