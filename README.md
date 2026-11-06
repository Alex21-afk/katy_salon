# Isabella Lashes & Studio

Sitio responsive en español para un estudio de pestañas y cejas, con reservas, cursos, inscripción con voucher y panel administrativo mobile-first.

## Árbol del proyecto

```text
katy_salon/
├── .htaccess
├── config.php
├── config.example.php
├── db/
│   ├── database.sql                       # instalación nueva
│   ├── actualizacion_videos_ingresos.sql  # actualización 1 (instalaciones existentes)
│   └── actualizacion_ingresos_cursos.sql  # actualización 2 (después de la 1)
├── app/
│   ├── bootstrap.php
│   ├── helpers.php
│   ├── controllers/
│   │   ├── AdminController.php
│   │   └── PublicController.php
│   ├── models/
│   │   ├── Database.php
│   │   └── SalonModel.php
│   ├── storage/uploads/        # vouchers y fotos, fuera del acceso web
│   └── views/
│       ├── admin/
│       └── public/
└── public/
    ├── .htaccess
    ├── index.php               # front controller
    ├── manifest.json           # único manifest; el .htaccess raíz lo sirve también en /manifest.json
    ├── sw.js                   # único service worker; ídem en /sw.js
    └── assets/
        ├── css/app.css
        ├── icon.svg
        └── js/
```

## Instalación en XAMPP

1. Copia la carpeta en `C:\xampp\htdocs\katy_salon`.
2. Abre XAMPP Control Panel e inicia **Apache** y **MySQL**.
3. Entra en `http://localhost/phpmyadmin`, importa `db/database.sql` y confirma que se creó `isabella_salon`.
4. Revisa `config.php`. En una instalación XAMPP estándar, MySQL usa `root` sin contraseña. Cambia host, usuario y contraseña si tu instancia tiene otros datos.
5. Confirma que Apache tenga habilitados `mod_rewrite` y `AllowOverride All`. Reinicia Apache si cambiaste su configuración.
6. Abre `http://localhost/katy_salon/`. En el primer acceso al panel, abre `http://localhost/katy_salon/admin/setup` para crear el usuario. Usa una contraseña de al menos 12 caracteres. La ruta de configuración se bloquea al crear la primera cuenta.
7. Entra al panel desde el enlace **Administración** del pie de página.

Los vouchers e imágenes se guardan en `app/storage/uploads`, bloqueada por Apache, y se sirven desde endpoints con control de sesión. Asegúrate de que Apache pueda escribir en esa carpeta.

## Datos que debes personalizar

- En `config.php`, reemplaza `51999999999` por el WhatsApp del estudio, con código de país y solo dígitos.
- En el panel, abre el engranaje para actualizar dirección, horarios de atención, texto principal y WhatsApp.
- Edita precios, duración y visibilidad desde **Servicios**; administra fotos desde **Servicios → Administrar galería**.
- Todos los textos de la portada se editan en **Configuración → Textos de la web**; si dejas un campo vacío vuelve al texto original. El catálogo de campos está en `app/site_texts.php`.
- Las fotos del banner se cambian en **Configuración → Fotos del banner principal**. Las de `public/assets/img/` solo se usan mientras no subas otras. Las 4 fotos de ejemplo de la galería (Unsplash) solo se muestran mientras no haya fotos subidas desde el panel.
- En **Cursos**, cada curso tiene horas, hasta 3 módulos y la lista "incluye". El proceso de inscripción, los datos de pago y el aviso importante son comunes a todos los cursos y se editan en **Textos de la web → Información común de los cursos**.

## Funciones disponibles

- Servicios y cursos de ejemplo en `database.sql`.
- Reserva en tres pasos con horarios calculados en servidor, anticipación mínima configurable y bloqueo por fecha para evitar reservas simultáneas.
- Inscripción con voucher JPG/PNG/PDF, validación MIME y límite de 5 MB. El cupo solo se ocupa cuando se aprueba la inscripción.
- Lista de espera para cursos sin cupos.
- Videos MP4/WebM subidos desde el panel (**Servicios → Administrar videos**), en una sección propia de la página.
- Logo e icono del sitio configurables desde **Configuración**.
- Registro de ingresos (pestaña **Ingresos**): cobros de citas al marcarlas como atendidas, adelantos de cursos al aprobar el voucher (se quitan si luego se rechaza la inscripción), ingresos manuales (cursos, productos, otros) y totales del mes por método de pago y tipo.
- Agenda por día, confirmación/cancelación/atención de citas, alta manual, bloqueo de horas, gestión de servicios/cupos, revisión de vouchers, clientas e historial.
- Alta inicial de administradora, `password_hash`, sesión protegida contra JavaScript y límite de 5 intentos de acceso por 15 minutos.
- PWA instalable en `localhost` o HTTPS. En XAMPP, abre el panel desde el navegador del celular conectado a la misma red usando la IP local del equipo.

## Despliegue en hosting

Sube el proyecto a una carpeta privada y apunta el document root a `public/` cuando el hosting lo permita; `public/.htaccess` mantiene las rutas amigables y los archivos de PWA quedan publicados allí. Si se conserva la raíz del proyecto como document root, el `.htaccess` raíz envía las rutas al front controller y bloquea `app/`, la configuración y el SQL. Crea una base MySQL con `utf8mb4`, importa `db/database.sql`, configura credenciales y limita permisos de escritura a `app/storage/uploads`.

### Actualizar una instalación existente (videos e ingresos)

Si el sitio ya estaba funcionando con una versión anterior, no vuelvas a importar `database.sql`. En phpMyAdmin selecciona la base del sitio, abre **Importar** y carga `db/actualizacion_videos_ingresos.sql`. Solo crea las tablas `videos` e `ingresos`; no modifica ni borra datos y puede ejecutarse más de una vez.

Después importa `db/actualizacion_ingresos_cursos.sql` (una sola vez): agrega a `ingresos` la columna que vincula cada adelanto con su inscripción. Si lo ejecutas dos veces verás "Duplicate column name", que es inofensivo.

Por último importa `db/actualizacion_cursos_textos.sql` (una sola vez): agrega a `cursos` las columnas de horas, módulos e "incluye". Si lo ejecutas dos veces verás "Duplicate column name", que es inofensivo.

El tamaño máximo de los videos es el menor entre `max_video_mb` (en `config.php`, 50 por defecto) y los límites `upload_max_filesize` / `post_max_size` de PHP del hosting. El panel muestra el límite real al subir un video.

Antes de publicar:

- En `config.php`, pon `'debug' => false` y usa un usuario MySQL propio con contraseña (no `root`).
- Sirve el sitio solo por HTTPS: la cookie de sesión se marca como `secure` automáticamente.
- `admin_idle_minutes` define tras cuántos minutos sin actividad se cierra la sesión del panel (120 por defecto).
- La cabecera `Content-Security-Policy` se envía desde `app/bootstrap.php`; si agregas scripts, fuentes o imágenes de otros dominios, añádelos allí.

Para depuración, usa el PHP incluido en XAMPP desde PowerShell:

```powershell
C:\xampp\php\php.exe -l C:\xampp\htdocs\katy_salon\public\index.php
```# katy_salon
