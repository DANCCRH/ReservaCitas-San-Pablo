<?php
require_once __DIR__.'/../config/conexion.php';
$usuario = exigir_usuario(['medico'], true);
$lista = $_GET['ids'] ?? '';
if (!is_string($lista) || strlen($lista) > 3000 || !preg_match('/\A[1-9]\d{0,9}(?:,[1-9]\d{0,9}){0,249}\z/', $lista)) {
    responder_json(['error' => 'Selecciona las citas que aparecen en la agenda.'], 422);
}
$ids = array_unique(explode(',', $lista));
$marcas = implode(',', array_fill(0, count($ids), '?'));
$q = $conexion->prepare("SELECT c.id, CONCAT(c.estado, '@', c.hora_cita)
    FROM citas c JOIN medicos m ON m.id=c.medico_id
    WHERE m.usuario_id=? AND c.fecha_cita=? AND c.id IN ($marcas)
    AND c.estado IN ('pendiente','confirmado') AND c.llegada_en IS NULL");
$q->execute([$usuario['id'], date('Y-m-d'), ...$ids]);
responder_json(['esperando' => (object)$q->fetchAll(PDO::FETCH_KEY_PAIR)]);
