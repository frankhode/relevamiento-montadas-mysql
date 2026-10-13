<?php
// Aplicación local: no expone la base a otras computadoras sin autenticación.
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true)) {
    http_response_code(403); exit('Esta instalación está habilitada para localhost.');
}
ini_set('display_errors', '0');
session_start();
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
function db() {
    $c = require __DIR__.'/config/database.php';
    return new PDO('mysql:host='.$c['host'].';port='.$c['port'].';dbname='.$c['database'].';charset=utf8mb4', $c['user'], $c['password'], [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false]);
}
function encode($v) { return json_encode($v, JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR); }
function readState($db) {
    $db->beginTransaction();
    try {
        $row=$db->query('SELECT revision, vocabulary FROM montadas_estado WHERE id=1 FOR UPDATE')->fetch(PDO::FETCH_ASSOC);
        if (!$row) throw new RuntimeException('Falta instalar la base');
        $records=[];
        foreach($db->query('SELECT contenido FROM montadas_fichas ORDER BY posicion') as $r) $records[]=json_decode($r['contenido'],true,512,JSON_THROW_ON_ERROR);
        $db->commit();
        return ['revision'=>(int)$row['revision'],'vocabulary'=>json_decode($row['vocabulary'],true,512,JSON_THROW_ON_ERROR),'records'=>$records];
    } catch(Throwable $e) { if($db->inTransaction()) $db->rollBack(); throw $e; }
}
