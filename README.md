# ReservaCitas

Sistema de citas médicas para consultorios externos. El paciente solicita un horario y consulta el estado de su cita. Recepción coordina la llegada, el profesional registra la atención y administración gestiona los consultorios, turnos y reportes.

**Taller de Programación Web · Universidad Tecnológica del Perú**  
[Ver el sistema publicado](https://reservadecitasclinicasanpablo.alwaysdata.net/)

## Base de datos con registros

La copia pública conserva el volumen y las relaciones entre pacientes, profesionales, solicitudes y movimientos del historial. Las identidades y los campos de contacto son ficticios. Las fechas se han desplazado y los textos libres se han sustituido. Las cuentas incluidas en esta copia carecen de una contraseña utilizable.

Al importar se obtienen **294.506 registros en 28 tablas**, entre ellos 52.052 citas, 225.220 movimientos de historial, 5.001 pacientes y 49 médicos. La tabla `cuentas_acceso` se rellena automáticamente mediante los disparadores del esquema.

Para abrir la copia en XAMPP, crea una **base vacía** con cotejamiento `utf8mb4_unicode_ci` en phpMyAdmin. Selecciónala y utiliza **Importar** dos veces, en este orden:

1. [esquema_actual.sql](database/esquema_actual.sql) crea las 28 tablas y los dos disparadores que sincronizan las cuentas de acceso.
2. [datos_publicos.sql.gz](database/datos_publicos.sql.gz) carga los registros. phpMyAdmin admite el archivo comprimido. Si el límite de carga de tu instalación es menor que el archivo, descomprímelo e impórtalo con el cliente de MariaDB.

En la pestaña **SQL** puedes consultar la distribución de las citas:

```sql
SELECT estado, COUNT(*) AS cantidad
FROM citas
GROUP BY estado
ORDER BY cantidad DESC;
```

Para usar los paneles en tu propia instalación, configura la conexión local a esa base y crea una cuenta nueva desde **Registrarse**, con una contraseña elegida por ti. Si necesitas un administrador local, selecciona tu base en phpMyAdmin y ejecuta la siguiente consulta reemplazando el correo por el de esa **cuenta recién creada**:

```sql
UPDATE pacientes
SET rol = 'admin', correo_verificado = 1, sesion_version = sesion_version + 1
WHERE correo = 'tu-cuenta@ejemplo.invalid'
  AND rol = 'paciente' AND es_invitado = 0
  AND NOT EXISTS (SELECT 1 FROM citas WHERE paciente_id = pacientes.id);
```

Vuelve a iniciar sesión con tu cuenta local para abrir Administración.

## Organización del código

| Ubicación | Responsabilidad |
| --- | --- |
| `public/` | Punto de entrada del sitio publicado, estilos CSS, JavaScript, imágenes y bibliotecas. |
| `app/routes.php` | Relación entre cada ruta y el archivo que la atiende. |
| `app/views/` | Plantillas HTML y PHP del portal público, formularios y paneles por rol. |
| `app/controllers/` | Validación de peticiones, preparación de páginas y procesamiento de formularios. |
| `app/models/` y `app/services/` | Disponibilidad, reservas, seguimientos, indicadores y comunicaciones. |
| `app/support/` | Sesiones, permisos, validaciones comunes y direcciones públicas. |
| `config/config.example.php` | Parámetros de conexión, URL, correo y almacenamiento. |
| `database/esquema_actual.sql` | Definiciones de las 28 tablas, sus claves, índices y dos disparadores de sincronización de cuentas. |
| `resources/css/` y `bin/` | Fuentes de los estilos compartidos y herramientas de compilación y mantenimiento. |
| `docs/RECURSOS.md` y `docs/INVENTARIO_VISUAL.json` | Procedencia de las imágenes, identificación de las 14 escenas originales, identidad gráfica y licencias de los recursos de terceros. |

## Recorrido de una reserva

El formulario de `app/views/paciente/reserva.php` permite elegir el profesional, la fecha y el horario. `public/js/reserva.js` consulta la disponibilidad y presenta el resumen. Al recibir la solicitud, `app/controllers/procesar_cita.php` comprueba la sesión, el token del formulario y los campos. La lógica en `app/models/agenda_modelo.php` verifica que el paciente, el médico y el consultorio tengan el intervalo disponible. La cita y su historial se guardan dentro de una transacción.

La interfaz usa HTML5, CSS3, JavaScript y componentes de Bootstrap. En el alojamiento, PHP procesa las peticiones y se conecta a MariaDB mediante PDO. Las licencias de las bibliotecas están en `public/vendor/` y la procedencia de las imágenes en `docs/RECURSOS.md`.
