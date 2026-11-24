<?php

require_once __DIR__.'/../config/conexion.php';
require_once __DIR__.'/../models/agenda_modelo.php';
$u = exigir_usuario();
$id = (int)($_GET['id'] ?? 0);
$q = $conexion->prepare('SELECT c.*,p.nombres,p.dni,m.nombres medico FROM citas c JOIN pacientes p ON p.id=c.paciente_id JOIN medicos m ON m.id=c.medico_id WHERE c.id=?');
$q->execute([$id]);
$c = $q->fetch();
if($c){$q=$conexion->prepare('SELECT nombre,ubicacion FROM consultorios WHERE id=?');$q->execute([$c['consultorio_id']]);$sala=$q->fetch();$c['consultorio']=$sala?($sala['nombre'].' · '.$sala['ubicacion']):'No registrado';}
if (!$c || !puede_ver_cita($u, $c)) {
    http_response_code(404);
    exit('Cita no disponible.');
}
if (($_GET['formato'] ?? 'pdf') === 'ics') {
    if (in_array($c['estado'], ['cancelado','rechazado'], true)) {
        http_response_code(422);
        exit('No se exporta una cita cancelada o rechazada.');
    }
    $esc = fn ($x) => str_replace(["\\","\r","\n",';',','], ["\\\\",'',"\\n","\\;","\\,"], $x);
    $inicio = new DateTimeImmutable($c['fecha_cita'].' '.$c['hora_cita'], new DateTimeZone('America/Lima'));
    $utc = new DateTimeZone('UTC');
    $fin = $inicio->modify('+'.$c['duracion'].' minutes');
    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="'.codigo_cita($id).'.ics"');
    echo "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//ReservaCitas//Agenda//ES\r\nCALSCALE:GREGORIAN\r\nBEGIN:VEVENT\r\nUID:rc-$id@reservacitas.local\r\nDTSTAMP:".gmdate('Ymd\THis\Z')."\r\nDTSTART:".$inicio->setTimezone($utc)->format('Ymd\THis\Z')."\r\nDTEND:".$fin->setTimezone($utc)->format('Ymd\THis\Z')."\r\nSUMMARY:".$esc('Cita '.$c['especialidad'])."\r\nLOCATION:".$esc($c['consultorio'])."\r\nDESCRIPTION:".$esc(codigo_cita($id).' · '.$c['medico'].' · Estado '.ESTADOS_CITA[$c['estado']])."\r\nSTATUS:".($c['estado'] === 'confirmado' ? 'CONFIRMED' : 'TENTATIVE')."\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";
    exit;
}
// PDF nativo de una página con tipografía estándar y codificación WinAnsi.
$pdftexto = function ($t) {
    $t = iconv('UTF-8', 'Windows-1252//TRANSLIT', $t);
    return str_replace(['\\','(',')',"\r","\n"], ['\\\\','\\(','\\)',' ',' '], $t);
};
$lineas = ['ReservaCitas','Clínica San Pablo',codigo_cita($id),'Paciente: '.$c['nombres'],'Documento: '.$c['dni'],'Especialidad: '.$c['especialidad'],'Profesional: '.$c['medico'],'Fecha: '.date('d/m/Y', strtotime($c['fecha_cita'])),'Hora: '.substr($c['hora_cita'], 0, 5).'  |  Duración: '.$c['duracion'].' minutos','Estado: '.ESTADOS_CITA[$c['estado']],'Consultorio: '.$c['consultorio'],'Turno: '.($c['codigo_turno']?:'Se asigna al llegar'),'Zona horaria: America/Lima','Emitido: '.date('d/m/Y H:i'),'Conserva este comprobante y revisa las novedades en tu cuenta.'];
$lineas_pdf=[];foreach($lineas as $linea)foreach(explode("\n",wordwrap($linea,78,"\n",true)) as $parte)$lineas_pdf[]=$parte;$lineas=$lineas_pdf;
$stream = "0.07 0.21 0.37 rg\n54 700 504 50 re f\n1 1 1 rg\nBT /F1 23 Tf 70 718 Td (ReservaCitas) Tj ET\n";
$y = 665;
foreach (array_slice($lineas, 1) as $i => $l) {
    $size = $i === 1 ? 19 : 12;
    $stream .= "0.07 0.21 0.37 rg BT /F1 $size Tf 58 $y Td (".$pdftexto($l).") Tj ET\n";
    $y -= ($i === 1 ? 42 : 31);
}
$objetos = ['<< /Type /Catalog /Pages 2 0 R >>','<< /Type /Pages /Kids [3 0 R] /Count 1 >>','<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>','<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>','<< /Length '.strlen($stream)." >>\nstream\n".$stream.'endstream'];
$pdf = "%PDF-1.4\n";
$offset = [0];
foreach ($objetos as $i => $o) {
    $offset[] = strlen($pdf);
    $pdf .= ($i + 1)." 0 obj\n$o\nendobj\n";
}$xref = strlen($pdf);
$pdf .= "xref\n0 6\n0000000000 65535 f \n";
foreach (array_slice($offset, 1) as $p) {
    $pdf .= sprintf('%010d 00000 n ', $p)."\n";
}$pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="'.codigo_cita($id).'.pdf"');
echo $pdf;
exit;
