<?php
require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../support/buscadores.php';

try {
    $u = usuario_actual();
    if ($u) $u = exigir_usuario(false, true);
    $tipo = sanitizar($_GET['tipo'] ?? '');
    $texto = sanitizar($_GET['q'] ?? '');
    $pagina = filter_var($_GET['pagina'] ?? 1, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1,'max_range'=>1000000]]);
    if (!$pagina || mb_strlen($texto) > 100) {
        throw new InvalidArgumentException('Revisa el texto y la página de búsqueda.');
    }
    responder_json(opciones_buscador($tipo, $u, sanitizar($_GET['alcance'] ?? ''), $texto,
        $pagina, (int)($_GET['especialidad'] ?? 0), (int)($_GET['usuario'] ?? 0)));
} catch (DomainException $e) {
    responder_json(['error'=>$e->getMessage()], 403);
} catch (InvalidArgumentException $e) {
    responder_json(['error'=>$e->getMessage()], 422);
} catch (Throwable $e) {
    error_log($e);
    responder_json(['error'=>'No se pudo completar la búsqueda.'], 500);
}
