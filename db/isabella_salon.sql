-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-11-2026 a las 07:58:32
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `isabella_salon`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `bloqueos`
--

CREATE TABLE `bloqueos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `inicio` datetime NOT NULL,
  `fin` datetime NOT NULL,
  `motivo` varchar(180) NOT NULL DEFAULT 'No disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `bloqueos`
--

INSERT INTO `bloqueos` (`id`, `inicio`, `fin`, `motivo`) VALUES
(1, '2026-10-28 11:00:00', '2026-10-28 17:15:00', 'Descanso');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `orden`) VALUES
(1, 'Pestañas', 1),
(2, 'Cejas', 2),
(3, 'Formación', 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `citas`
--

CREATE TABLE `citas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `servicio_id` int(10) UNSIGNED NOT NULL,
  `clienta_id` bigint(20) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `hora_inicio` time NOT NULL,
  `hora_fin` time NOT NULL,
  `estado` enum('pendiente','confirmada','cancelada','atendida') NOT NULL DEFAULT 'pendiente',
  `nota` varchar(1000) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `citas`
--

INSERT INTO `citas` (`id`, `servicio_id`, `clienta_id`, `fecha`, `hora_inicio`, `hora_fin`, `estado`, `nota`, `creado_en`) VALUES
(3, 5, 3, '2026-10-26', '10:00:00', '11:00:00', 'confirmada', 'dejame guapo', '2026-10-20 15:55:19'),
(6, 5, 7, '2026-10-23', '15:00:00', '16:00:00', 'confirmada', 'gaaaa', '2026-10-22 21:12:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientas`
--

CREATE TABLE `clientas` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `nombre` varchar(140) NOT NULL,
  `whatsapp` varchar(25) NOT NULL,
  `correo` varchar(190) DEFAULT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `clientas`
--

INSERT INTO `clientas` (`id`, `nombre`, `whatsapp`, `correo`, `dni`, `creado_en`) VALUES
(3, 'Jose Alarcon', '123456789', NULL, NULL, '2026-10-20 15:55:19'),
(7, 'dfgdfg', '51947020959', NULL, NULL, '2026-10-22 21:12:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

CREATE TABLE `configuracion` (
  `clave` varchar(100) NOT NULL,
  `valor` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`clave`, `valor`) VALUES
('direccion', 'Lima, Perú · Atención con cita previa'),
('hero_subtitulo', 'Pestañas, cejas y formación profesional con atención personalizada en Lima.'),
('hero_titulo', 'Tu mirada, en su mejor versión'),
('horario_texto', 'Lun–Vie 9:00–19:00 · Sáb 9:00–17:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cursos`
--

CREATE TABLE `cursos` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(160) NOT NULL,
  `nivel` varchar(80) NOT NULL DEFAULT 'Inicial',
  `descripcion` text NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_fin` date DEFAULT NULL,
  `sesiones` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
  `precio` decimal(9,2) NOT NULL DEFAULT 0.00,
  `cupos_totales` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `estado` enum('borrador','activo','finalizado') NOT NULL DEFAULT 'activo',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `cursos`
--

INSERT INTO `cursos` (`id`, `nombre`, `nivel`, `descripcion`, `fecha_inicio`, `fecha_fin`, `sesiones`, `precio`, `cupos_totales`, `estado`, `creado_en`) VALUES
(1, 'Lashista profesional', 'Desde cero', 'Formación práctica en aplicación clásica, higiene y diseño de mirada.', '2026-11-08', NULL, 3, 650.00, 8, 'finalizado', '2026-10-19 03:44:31'),
(2, 'Volumen brasileño', 'Intermedio', 'Perfecciona aislamiento, abanicos y retención con práctica guiada.', '2026-11-22', NULL, 2, 480.00, 6, 'activo', '2026-10-19 03:44:31'),
(4, 'Curso de prueba', 'Inicial', 'curso de habla bhal abla', '2026-10-21', NULL, 1, 340.00, 8, 'borrador', '2026-10-20 15:48:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `galeria`
--

CREATE TABLE `galeria` (
  `id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(140) NOT NULL DEFAULT '',
  `archivo` varchar(255) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `galeria`
--

INSERT INTO `galeria` (`id`, `titulo`, `archivo`, `orden`, `activo`) VALUES
(1, 'pruab01', '92230d7a89b29fef0fe61ca0c036e7ad465fec60.jpg', 1, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios_atencion`
--

CREATE TABLE `horarios_atencion` (
  `id` tinyint(3) UNSIGNED NOT NULL,
  `dia_semana` tinyint(3) UNSIGNED NOT NULL,
  `abierto` tinyint(1) NOT NULL DEFAULT 1,
  `hora_inicio` time NOT NULL DEFAULT '09:00:00',
  `hora_fin` time NOT NULL DEFAULT '19:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `horarios_atencion`
--

INSERT INTO `horarios_atencion` (`id`, `dia_semana`, `abierto`, `hora_inicio`, `hora_fin`) VALUES
(1, 0, 0, '09:00:00', '18:00:00'),
(2, 1, 1, '09:00:00', '19:00:00'),
(3, 2, 1, '09:00:00', '19:00:00'),
(4, 3, 1, '09:00:00', '19:00:00'),
(5, 4, 1, '09:00:00', '19:00:00'),
(6, 5, 1, '09:00:00', '19:00:00'),
(7, 6, 1, '09:00:00', '17:00:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ingresos`
--

CREATE TABLE `ingresos` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `fecha` date NOT NULL,
  `concepto` varchar(160) NOT NULL,
  `monto` decimal(9,2) NOT NULL,
  `metodo` enum('efectivo','yape','plin','transferencia','tarjeta','otro') NOT NULL DEFAULT 'efectivo',
  `origen` enum('cita','curso','producto','otro') NOT NULL DEFAULT 'otro',
  `cita_id` bigint(20) UNSIGNED DEFAULT NULL,
  `inscripcion_id` bigint(20) UNSIGNED DEFAULT NULL,
  `nota` varchar(500) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inscripciones`
--

CREATE TABLE `inscripciones` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(140) NOT NULL,
  `dni` varchar(20) NOT NULL,
  `whatsapp` varchar(25) NOT NULL,
  `correo` varchar(190) NOT NULL,
  `voucher` varchar(255) DEFAULT NULL,
  `estado` enum('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `inscripciones`
--

INSERT INTO `inscripciones` (`id`, `curso_id`, `nombre`, `dni`, `whatsapp`, `correo`, `voucher`, `estado`, `creado_en`) VALUES
(1, 2, 'Alexander Fabian Piera Arana', '75838937', '947020959', 'piera655@gmail.com', '8c24265b3dbc6fdae95cc382a51f9ff764528298.jpg', 'aprobada', '2026-10-20 15:52:50');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `intentos_login`
--

CREATE TABLE `intentos_login` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `identificador` varchar(190) NOT NULL,
  `ocurrido_en` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `intentos_login`
--

INSERT INTO `intentos_login` (`id`, `identificador`, `ocurrido_en`) VALUES
(1, 'reserva|::1', '2026-10-22 16:12:51');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_espera`
--

CREATE TABLE `lista_espera` (
  `id` bigint(20) UNSIGNED NOT NULL,
  `curso_id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(140) NOT NULL,
  `whatsapp` varchar(25) NOT NULL,
  `correo` varchar(190) NOT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id` int(10) UNSIGNED NOT NULL,
  `categoria_id` int(10) UNSIGNED DEFAULT NULL,
  `nombre` varchar(140) NOT NULL,
  `descripcion` varchar(500) NOT NULL DEFAULT '',
  `precio` decimal(9,2) NOT NULL DEFAULT 0.00,
  `duracion_minutos` smallint(5) UNSIGNED NOT NULL DEFAULT 60,
  `foto` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `orden` int(11) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id`, `categoria_id`, `nombre`, `descripcion`, `precio`, `duracion_minutos`, `foto`, `activo`, `orden`) VALUES
(1, 1, 'Extensiones clásicas', 'Un acabado delicado y natural, diseñado para realzar tu mirada.', 120.00, 120, NULL, 1, 1),
(2, 1, 'Volumen e híbridas', 'Textura y densidad personalizadas para un resultado con más presencia.', 150.00, 150, NULL, 1, 2),
(3, 1, 'Lifting de pestañas', 'Curvatura y definición para tus pestañas naturales.', 90.00, 75, NULL, 1, 3),
(4, 2, 'Diseño y laminado de cejas', 'Diseño personalizado y acabado ordenado para tus cejas.', 100.00, 90, NULL, 1, 4),
(5, 1, 'Prueba1', 'prueba10101010', 50.00, 60, NULL, 1, 0);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password_hash`, `activo`, `creado_en`) VALUES
(3, 'Alexander', 'piera655@gmail.com', '$2y$10$nFekRPChBg9t6SZYru4J8Odu1fHhgWO2vClHgfxA0fjxM7lNIGy0e', 1, '2026-10-19 03:57:36');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `videos`
--

CREATE TABLE `videos` (
  `id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(140) NOT NULL DEFAULT '',
  `archivo` varchar(255) NOT NULL,
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `videos`
--

INSERT INTO `videos` (`id`, `titulo`, `archivo`, `orden`, `activo`, `creado_en`) VALUES
(1, 'prueba', '5e59814b01932ff47f5791a37741829441805f0a.mp4', 1, 1, '2026-10-22 21:09:54');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `bloqueos`
--
ALTER TABLE `bloqueos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_bloqueos_rango` (`inicio`,`fin`);

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `citas`
--
ALTER TABLE `citas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_citas_servicio` (`servicio_id`),
  ADD KEY `fk_citas_clienta` (`clienta_id`),
  ADD KEY `idx_citas_fecha_estado` (`fecha`,`estado`),
  ADD KEY `idx_citas_rango` (`fecha`,`hora_inicio`,`hora_fin`);

--
-- Indices de la tabla `clientas`
--
ALTER TABLE `clientas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_clientas_whatsapp` (`whatsapp`);

--
-- Indices de la tabla `configuracion`
--
ALTER TABLE `configuracion`
  ADD PRIMARY KEY (`clave`);

--
-- Indices de la tabla `cursos`
--
ALTER TABLE `cursos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_cursos_estado_fecha` (`estado`,`fecha_inicio`);

--
-- Indices de la tabla `galeria`
--
ALTER TABLE `galeria`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `horarios_atencion`
--
ALTER TABLE `horarios_atencion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `dia_semana` (`dia_semana`);

--
-- Indices de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ingresos_cita` (`cita_id`),
  ADD UNIQUE KEY `uq_ingresos_inscripcion` (`inscripcion_id`),
  ADD KEY `idx_ingresos_fecha` (`fecha`);

--
-- Indices de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_inscripciones_curso_estado` (`curso_id`,`estado`);

--
-- Indices de la tabla `intentos_login`
--
ALTER TABLE `intentos_login`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_login_identificador_fecha` (`identificador`,`ocurrido_en`);

--
-- Indices de la tabla `lista_espera`
--
ALTER TABLE `lista_espera`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_espera_curso` (`curso_id`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_servicios_categoria` (`categoria_id`),
  ADD KEY `idx_servicios_activo_orden` (`activo`,`orden`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indices de la tabla `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `bloqueos`
--
ALTER TABLE `bloqueos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `citas`
--
ALTER TABLE `citas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `clientas`
--
ALTER TABLE `clientas`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `cursos`
--
ALTER TABLE `cursos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `galeria`
--
ALTER TABLE `galeria`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `ingresos`
--
ALTER TABLE `ingresos`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `intentos_login`
--
ALTER TABLE `intentos_login`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `lista_espera`
--
ALTER TABLE `lista_espera`
  MODIFY `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `citas`
--
ALTER TABLE `citas`
  ADD CONSTRAINT `fk_citas_clienta` FOREIGN KEY (`clienta_id`) REFERENCES `clientas` (`id`),
  ADD CONSTRAINT `fk_citas_servicio` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`);

--
-- Filtros para la tabla `ingresos`
--
ALTER TABLE `ingresos`
  ADD CONSTRAINT `fk_ingresos_cita` FOREIGN KEY (`cita_id`) REFERENCES `citas` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_ingresos_inscripcion` FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripciones` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `inscripciones`
--
ALTER TABLE `inscripciones`
  ADD CONSTRAINT `fk_inscripciones_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `lista_espera`
--
ALTER TABLE `lista_espera`
  ADD CONSTRAINT `fk_espera_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD CONSTRAINT `fk_servicios_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
