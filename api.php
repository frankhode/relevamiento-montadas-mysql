<?php
require __DIR__.'/backend.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
function reply($code,$data) { http_response_code($code); echo encode($data); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(405,['error'=>'Método no permitido']);
if (!hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) reply(403,['error'=>'Sesión vencida. Descargá un respaldo antes de recargar.']);
session_write_close();
try {
    $raw=file_get_contents('php://input');
    if(strlen($raw)>32*1024*1024) reply(413,['error'=>'El lote supera 32 MB. Descargá un respaldo.']);
    $data=json_decode($raw,true,512,JSON_THROW_ON_ERROR);
    if (!is_array($data) || !isset($data['revision'],$data['records'],$data['vocabulary']) || !is_int($data['revision']) || !is_array($data['records']) || !is_array($data['vocabulary'])) reply(400,['error'=>'Datos incompletos']);
    $rows=[]; $ids=[];
    foreach($data['records'] as $r) {
        if(!is_array($r) || !isset($r['id']) || !is_string($r['id']) || trim($r['id'])==='') reply(400,['error'=>'Cada ficha necesita un identificador.']);
        $id=trim($r['id']);
        if(preg_match_all('/./us',$id)>191 || isset($ids[$id])) reply(400,['error'=>'Identificador duplicado o demasiado largo: '.$id]);
        $ids[$id]=true; $r['id']=$id;
        foreach(['frontUrl','backUrl','_frontFile','_backFile','frontError','backError'] as $key) unset($r[$key]);
        $rows[]=[$id,encode($r)];
    }
    $db=db(); $db->beginTransaction();
    $value=$db->query('SELECT revision FROM montadas_estado WHERE id=1 FOR UPDATE')->fetchColumn();
    if($value===false) throw new RuntimeException('Falta instalar la base');
    $revision=(int)$value;
    if($revision!==$data['revision']) { $db->rollBack(); reply(409,['error'=>'Otra pestaña guardó cambios. Descargá un Respaldo de esta pestaña y recargá para revisar la versión de MySQL.']); }
    $db->exec('DELETE FROM montadas_fichas');
    $stmt=$db->prepare('INSERT INTO montadas_fichas (identificador,posicion,contenido) VALUES (?,?,?)');
    foreach($rows as $i=>$r) $stmt->execute([$r[0],$i,$r[1]]);
    $stmt=$db->prepare('UPDATE montadas_estado SET revision=revision+1,vocabulary=?,actualizado=CURRENT_TIMESTAMP WHERE id=1');
    $stmt->execute([encode($data['vocabulary'])]);
    $db->commit(); reply(200,['revision'=>$revision+1,'count'=>count($rows)]);
} catch (JsonException $e) { reply(400,['error'=>'JSON inválido']); }
catch(Throwable $e) {
    if(isset($db) && $db->inTransaction()) $db->rollBack();
    error_log('Relevamiento MySQL: '.$e->getMessage());
    reply(500,['error'=>'No se pudo guardar en MySQL. Verificá que esté iniciado; tus cambios siguen pendientes.']);
}
