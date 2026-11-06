-- Actualización: horas, módulos y lista "incluye" de los cursos.
-- Ejecutar una sola vez. Si se repite verás "Duplicate column name", que es inofensivo.
-- Los textos editables y las fotos del banner se guardan en `configuracion`: no necesitan cambios aquí.

ALTER TABLE `cursos`
  ADD COLUMN `horas` varchar(80) NOT NULL DEFAULT '' AFTER `sesiones`,
  ADD COLUMN `modulos` text DEFAULT NULL AFTER `descripcion`,
  ADD COLUMN `incluye` text DEFAULT NULL AFTER `modulos`;
