-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 06-11-2026 21:45:19
-- Compatibilidad: XAMPP (MariaDB 10.4+) / Laragon (MySQL 8.x) / phpMyAdmin
-- Arquitectura: IESP Cap. FAP José Abelardo Quiñones - Continuidad TI
--

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `iesp_continuidad_demo`
--
CREATE DATABASE IF NOT EXISTS `iesp_continuidad_demo` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `iesp_continuidad_demo`;

SET FOREIGN_KEY_CHECKS=0;

-- --------------------------------------------------------
--
-- Estructura de tabla para la tabla `audit_logs`
--
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `properties` json DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_foreign` (`user_id`),
  KEY `audit_logs_subject_type_subject_id_index` (`subject_type`,`subject_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_created_at_index` (`created_at`),
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `audit_logs`
--
INSERT INTO `audit_logs` (`id`, `user_id`, `action`, `subject_type`, `subject_id`, `description`, `properties`, `ip_address`, `created_at`) VALUES
(1, 1, 'backup.created', 'App\\Models\\DemoBackup', 6, 'Respaldo #6 generado con éxito y verificado con SHA-256.', '{\"sha256\": \"c80f27c070946ade0c49d13be450056f8d80001c32803ad2ab0f3e82887adc8c\", \"size_bytes\": 4436, \"duration_ms\": 3840}', '127.0.0.1', '2026-11-06 09:00:28'),
(2, NULL, 'backup.created', 'App\\Models\\DemoBackup', 7, 'Respaldo #7 de [iesp_continuidad_demo] (mysql) cifrado y replicado con SHA-256. [AUTOMATIZADO]', '{\"type\": \"mysql\", \"sha256\": \"c295c38cc52ce3ac5bdf72f585332501c07ad6a0e7b2156aa8fabfe3f4bc1bfc\", \"database\": \"iesp_continuidad_demo\", \"automated\": true, \"replicated\": true, \"size_bytes\": 5664, \"duration_ms\": 1082}', '127.0.0.1', '2026-11-06 09:14:34'),
(3, 1, 'backup.created', 'App\\Models\\DemoBackup', 8, 'Respaldo #8 de [iesp_continuidad_demo] (mysql) cifrado y replicado con SHA-256.', '{\"type\": \"mysql\", \"sha256\": \"8b76bd0d7cba9ce4c810850994820a7ddcfc0d69ec624dd1f9dfb1476074da53\", \"database\": \"iesp_continuidad_demo\", \"automated\": false, \"replicated\": true, \"size_bytes\": 6116, \"duration_ms\": 831}', '127.0.0.1', '2026-11-06 09:47:12'),
(4, 1, 'backup.created', 'App\\Models\\DemoBackup', 9, 'Respaldo #9 de [novaflor_sali] (mysql) cifrado y replicado con SHA-256.', '{\"type\": \"mysql\", \"sha256\": \"3b8dcf2235d8bc49e1d6da631e55761b2657e24960519ac0776c00876a122287\", \"database\": \"novaflor_sali\", \"automated\": false, \"replicated\": true, \"size_bytes\": 44856, \"duration_ms\": 2204}', '127.0.0.1', '2026-11-06 09:48:05'),
(5, 1, 'backup.created', 'App\\Models\\DemoBackup', 10, 'Respaldo #10 de [novaflor_sali] (mysql) cifrado y replicado con SHA-256.', '{\"type\": \"mysql\", \"sha256\": \"259ddd662a17b79b4ffe007202b193777741b96f785a1f1d57b628d110e6d436\", \"database\": \"novaflor_sali\", \"automated\": false, \"replicated\": true, \"size_bytes\": 44856, \"duration_ms\": 1511}', '127.0.0.1', '2026-11-06 10:54:01'),
(6, 1, 'auth.logout', 'App\\Models\\User', 1, 'Cierre de sesión del usuario Admin.', NULL, '127.0.0.1', '2026-11-06 19:41:58'),
(7, NULL, 'backup.restored', 'App\\Models\\DemoBackup', 11, 'Recuperación verificada de [iesp_continuidad_demo] completada en 1264 ms (RTO verificado).', '{\"database\": \"iesp_continuidad_demo\", \"duration_ms\": 1264, \"restored_path\": \"demo-restores/a3d45505-e5fa-4228-97dd-1db38ddcf76b.iesp-continuidad-demo.restored.sql\"}', '127.0.0.1', '2026-11-06 21:12:02'),
(8, 1, 'auth.login', 'App\\Models\\User', 1, 'Inicio de sesión correcto del usuario Admin.', '{\"email\": \"admin@demo.local\"}', '127.0.0.1', '2026-11-06 21:34:56');

-- --------------------------------------------------------
--
-- Estructura de tabla para la tabla `cache`
--
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Estructura de tabla para la tabla `cache_locks`
--
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks` (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint NOT NULL,
  PRIMARY KEY (`key`),
  KEY `cache_locks_expiration_index` (`expiration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Estructura de tabla para la tabla `demo_backups`
--
DROP TABLE IF EXISTS `demo_backups`;
CREATE TABLE `demo_backups` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `database_name` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'iesp_continuidad_demo',
  `database_type` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT 'mysql',
  `artifact_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `artifact_sha256` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `secondary_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `replicated` tinyint(1) NOT NULL DEFAULT '0',
  `is_automated` tinyint(1) NOT NULL DEFAULT '0',
  `source_sha256` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `size_bytes` bigint unsigned NOT NULL DEFAULT '0',
  `backup_duration_ms` int unsigned DEFAULT NULL,
  `restored_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `restore_duration_ms` int unsigned DEFAULT NULL,
  `error_message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `demo_backups_status_index` (`status`),
  KEY `demo_backups_restored_path_index` (`restored_path`),
  KEY `demo_backups_is_automated_index` (`is_automated`),
  KEY `demo_backups_created_at_index` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `demo_backups`
--
INSERT INTO `demo_backups` (`id`, `status`, `database_name`, `database_type`, `artifact_path`, `artifact_sha256`, `secondary_path`, `replicated`, `is_automated`, `source_sha256`, `size_bytes`, `backup_duration_ms`, `restored_path`, `restore_duration_ms`, `error_message`, `created_at`, `updated_at`) VALUES
(1, 'success', 'iesp_continuidad_demo', 'mysql', 'demo-backups/06986389-e660-4c32-8901-f1b4b348a7e9.backup.enc', '2cba2ac08cf125de1956fb17f71fc1f7bcfd452b1f4a20941d98893b383aab9d', NULL, 0, 0, '8a7e6e5f4aba095ab85db334fd80b601522b2dc81081248da1d2409eb1f7cdd9', 4436, 122, 'demo-restores/cbbcec42-35ad-45cb-9a8b-f01a7e3bcefa.sqlite', 60, NULL, '2026-10-23 17:14:09', '2026-10-23 17:14:16'),
(2, 'success', 'iesp_continuidad_demo', 'mysql', 'demo-backups/41a9b1d4-7962-42d3-946f-27d06f984be2.backup.enc', 'f9d798e285dbf3575bcebe1546ad350706ce65abdb32315e1a0f94cfdea97d95', NULL, 0, 0, '8a7e6e5f4aba095ab85db334fd80b601522b2dc81081248da1d2409eb1f7cdd9', 4436, 59, 'demo-restores/65d1db14-0065-468b-a21d-689dc5d3f8bc.sqlite', 33, NULL, '2026-10-23 17:15:35', '2026-10-23 17:15:36'),
(3, 'success', 'iesp_continuidad_demo', 'mysql', 'demo-backups/735e496f-ed2f-4f5e-8124-282db643bf7f.backup.enc', '5b27017982f1d4a5137a9857badcf989e11c81de3924ebb1789a81a6fd5d6190', NULL, 0, 0, '8a7e6e5f4aba095ab85db334fd80b601522b2dc81081248da1d2409eb1f7cdd9', 4436, 117, NULL, NULL, NULL, '2026-10-23 18:21:00', '2026-10-23 18:21:00'),
(4, 'success', 'iesp_continuidad_demo', 'mysql', 'demo-backups/51378957-5a93-4db9-af0e-8331d5b107ca.backup.enc', '0aef6da7fcb990ab1f618d5648bfa5699866f0e909001ced71c0217833075088', NULL, 0, 0, '8a7e6e5f4aba095ab85db334fd80b601522b2dc81081248da1d2409eb1f7cdd9', 4436, 82, 'demo-restores/eb2866ea-11d8-4f34-a87b-0bfecd17bf69.sqlite', 48, NULL, '2026-10-23 18:21:01', '2026-10-23 18:21:03'),
(5, 'success', 'iesp_continuidad_demo', 'mysql', 'demo-backups/11b5007a-6c5c-404e-92ee-63f308fca9fd.backup.enc', 'ce289c65f40e9035751dab667d56974b49af6b394c362604e04e27e60caba028', NULL, 0, 0, '8a7e6e5f4aba095ab85db334fd80b601522b2dc81081248da1d2409eb1f7cdd9', 4436, 57, NULL, NULL, NULL, '2026-10-30 16:30:06', '2026-10-30 16:30:06'),
(6, 'success', 'iesp_continuidad_demo', 'mysql', 'demo-backups/703dd509-5e8e-4739-a6e7-cf086a2bef65.backup.enc', 'c80f27c070946ade0c49d13be450056f8d80001c32803ad2ab0f3e82887adc8c', NULL, 0, 0, '8a7e6e5f4aba095ab85db334fd80b601522b2dc81081248da1d2409eb1f7cdd9', 4436, 3840, NULL, NULL, NULL, '2026-11-06 09:00:24', '2026-11-06 09:00:28'),
(7, 'success', 'iesp_continuidad_demo', 'mysql', 'demo-backups/2ff4f530-922a-49b7-81cc-6dac5c82bdad.iesp-continuidad-demo.backup.enc', 'c295c38cc52ce3ac5bdf72f585332501c07ad6a0e7b2156aa8fabfe3f4bc1bfc', 'backups/secondary/2ff4f530-922a-49b7-81cc-6dac5c82bdad.iesp-continuidad-demo.backup.enc', 1, 1, '87d0f675d8c9d18b232ca57cce0909c5235b95898440666aa15b768f759430bb', 5664, 1082, NULL, NULL, NULL, '2026-11-06 09:14:33', '2026-11-06 09:14:34'),
(8, 'success', 'iesp_continuidad_demo', 'mysql', 'demo-backups/245774ed-95e7-40a8-b536-32ad9a766ef7.iesp-continuidad-demo.backup.enc', '8b76bd0d7cba9ce4c810850994820a7ddcfc0d69ec624dd1f9dfb1476074da53', 'backups/secondary/245774ed-95e7-40a8-b536-32ad9a766ef7.iesp-continuidad-demo.backup.enc', 1, 0, 'cb37f4d24ed8f508ae6e1a714d65245883f2d04f38b8e584d5fb42d7217c5f01', 6116, 831, NULL, NULL, NULL, '2026-11-06 09:47:11', '2026-11-06 09:47:12'),
(9, 'success', 'novaflor_sali', 'mysql', 'demo-backups/2bb9d248-9f9b-4414-a524-7afda22b1fc8.novaflor-sali.backup.enc', '3b8dcf2235d8bc49e1d6da631e55761b2657e24960519ac0776c00876a122287', 'backups/secondary/2bb9d248-9f9b-4414-a524-7afda22b1fc8.novaflor-sali.backup.enc', 1, 0, '1fd7fface0ef0a7befa393ccda19926a65c6898ac71ae24f2e64897783586bb6', 44856, 2204, NULL, NULL, NULL, '2026-11-06 09:48:03', '2026-11-06 09:48:05'),
(10, 'success', 'novaflor_sali', 'mysql', 'demo-backups/402a8102-4a73-4234-8a72-7774a5cbffef.novaflor-sali.backup.enc', '259ddd662a17b79b4ffe007202b193777741b96f785a1f1d57b628d110e6d436', 'backups/secondary/402a8102-4a73-4234-8a72-7774a5cbffef.novaflor-sali.backup.enc', 1, 0, '1fd7fface0ef0a7befa393ccda19926a65c6898ac71ae24f2e64897783586bb6', 44856, 1511, NULL, NULL, NULL, '2026-11-06 10:53:59', '2026-11-06 10:54:01'),
(11, 'running', 'iesp_continuidad_demo', 'mysql', NULL, NULL, NULL, 0, 0, NULL, 0, NULL, 'demo-restores/a3d45505-e5fa-4228-97dd-1db38ddcf76b.iesp-continuidad-demo.restored.sql', 1264, NULL, '2026-11-06 20:57:18', '2026-11-06 21:12:02');

-- --------------------------------------------------------
--
-- Estructura de tabla para la tabla `estudiantes`
--
DROP TABLE IF EXISTS `estudiantes`;
CREATE TABLE `estudiantes` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `dni` varchar(8) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `nombres` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `carrera` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `correo` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `estudiantes_dni_unique` (`dni`),
  UNIQUE KEY `estudiantes_correo_unique` (`correo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estudiantes`
-- AVISO DE PRIVACIDAD: Los registros a continuación son 100% SINTÉTICOS Y FICTICIOS
-- generados exclusivamente para pruebas técnicas institucionales (Ley N° 29733).
--
INSERT INTO `estudiantes` (`id`, `dni`, `nombres`, `carrera`, `correo`, `created_at`, `updated_at`) VALUES
(1, '72345678', 'Carlos Raúl Peña Alva', 'Plataformas y Servicios de TI', 'cpena@iesp.edu.pe', '2026-11-06 14:19:03', '2026-11-06 14:19:03'),
(2, '73456789', 'María Belén Quiñones Ramos', 'Desarrollo de Sistemas de Información', 'mquinones@iesp.edu.pe', '2026-11-06 14:19:03', '2026-11-06 14:19:03'),
(3, '74567890', 'José Manuel Ñique Flores', 'Plataformas y Servicios de TI', 'jnique@iesp.edu.pe', '2026-11-06 14:19:03', '2026-11-06 14:19:03'),
(4, '75678901', 'Lucía Gómez Zúñiga', 'Contabilidad y Finanzas', 'lzuniga@iesp.edu.pe', '2026-11-06 14:19:03', '2026-11-06 14:19:03'),
(5, '76789012', 'Ángel David Fernández Morales', 'Mecatrónica Automotriz', 'afernandez@iesp.edu.pe', '2026-11-06 14:19:03', '2026-11-06 14:19:03'),
(6, '77890123', 'Sofía Abigail Ortiz Núñez', 'Administración de Empresas', 'sortiz@iesp.edu.pe', '2026-11-06 14:19:03', '2026-11-06 14:19:03'),
(7, '78901234', 'Cristian Jesús Córdova Ríos', 'Plataformas y Servicios de TI', 'ccordova@iesp.edu.pe', '2026-11-06 14:19:03', '2026-11-06 14:19:03'),
(8, '79012345', 'Valeria Jimena Cañari Benítez', 'Desarrollo de Sistemas de Información', 'vcanari@iesp.edu.pe', '2026-11-06 14:19:03', '2026-11-06 14:19:03'),
(29, '71000001', 'Mateo Alejandro Morales Castro', 'Plataformas y Servicios de Tecnologías de la Información', 'mateo.morales@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(30, '71000002', 'Valeria Nicole Sánchez Rivas', 'Desarrollo de Sistemas de Información', 'valeria.sanchez@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(31, '71000003', 'Joaquín Alonso Herrera Ñáñez', 'Plataformas y Servicios de Tecnologías de la Información', 'joaquin.herrera@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(32, '71000004', 'Camila Esperanza Gómez Zúñiga', 'Contabilidad y Finanzas', 'camila.gomez@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(33, '71000005', 'Diego Sebastián Mendoza Cárdenas', 'Mecatrónica Automotriz', 'diego.mendoza@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(34, '71000006', 'Luciana Belén Flores Peña', 'Administración de Empresas', 'luciana.flores@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(35, '71000007', 'Álvaro Gabriel Romero Núñez', 'Plataformas y Servicios de Tecnologías de la Información', 'alvaro.romero@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(36, '71000008', 'Fiorella Yamileth Cruz Alva', 'Desarrollo de Sistemas de Información', 'fiorella.cruz@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(37, '71000009', 'Rodrigo Andrés Medina Ríos', 'Mecatrónica Automotriz', 'rodrigo.medina@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(38, '71000010', 'Andrea Stephanie Benítez Quiñones', 'Contabilidad y Finanzas', 'andrea.benitez@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(39, '71000011', 'Sebastián Ignacio Paredes Cañari', 'Plataformas y Servicios de Tecnologías de la Información', 'sebastian.paredes@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(40, '71000012', 'Daniela Alejandra Ramos Huamán', 'Administración de Empresas', 'daniela.ramos@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(41, '71000013', 'Christian Jesús Espinoza Morales', 'Desarrollo de Sistemas de Información', 'christian.espinoza@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(42, '71000014', 'Romina Beatriz Delgado Carranza', 'Contabilidad y Finanzas', 'romina.delgado@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(43, '71000015', 'Esteban Darío Salazar Vílchez', 'Mecatrónica Automotriz', 'esteban.salazar@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(44, '71000016', 'Melany Alexandra Quispe Tello', 'Plataformas y Servicios de Tecnologías de la Información', 'melany.quispe@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(45, '71000017', 'Gonzalo David Castillo Barreto', 'Desarrollo de Sistemas de Información', 'gonzalo.castillo@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(46, '71000018', 'Ariana Nicole Vásquez Córdova', 'Administración de Empresas', 'ariana.vasquez@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(47, '71000019', 'Fabricio Emmanuel Luna Ñique', 'Mecatrónica Automotriz', 'fabricio.luna@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(48, '71000020', 'Génesis Milagros Lozano Chávez', 'Plataformas y Servicios de Tecnologías de la Información', 'genesis.lozano@iesp.edu.pe', '2026-11-06 16:17:55', '2026-11-06 16:17:55'),
(49, '71484241', 'Jimena Adriana Lozano Luna', 'Plataformas y Servicios de Tecnologías de la Información', 'jimena.lozano@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(50, '75488567', 'Óscar José Lozano Vílchez', 'Desarrollo de Sistemas de Información', 'oscar.lozano@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(51, '71896322', 'Sebastián Mauricio García Peña', 'Contabilidad y Finanzas', 'sebastian.garcia@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(52, '77601511', 'Mateo Darío Fernández Vásquez', 'Administración de Empresas', 'mateo.fernandez@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(53, '73153875', 'Ignacio Víctor Luna Quiñones', 'Mecatrónica Automotriz', 'ignacio.luna@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(54, '72900684', 'Ximena Camila Gutiérrez Gómez', 'Plataformas y Servicios de Tecnologías de la Información', 'ximena.gutierrez@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(55, '71776217', 'Raúl Miguel Medina Huamán', 'Desarrollo de Sistemas de Información', 'raul.medina@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(56, '70770499', 'Milagros Natalia Fajardo Chunga', 'Contabilidad y Finanzas', 'milagros.fajardo@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(57, '78548162', 'Gabriel Juan Medina Zavala', 'Administración de Empresas', 'gabriel.medina@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(58, '79561806', 'Elena Melany Quispe Medina', 'Mecatrónica Automotriz', 'elena.quispe@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(59, '75723023', 'Rubén Rodrigo Flores Tello', 'Plataformas y Servicios de Tecnologías de la Información', 'ruben.flores@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(60, '72133013', 'Fiorella María Espinoza Benítez', 'Desarrollo de Sistemas de Información', 'fiorella.espinoza@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(61, '70463370', 'Mauricio Rubén Olivares Peña', 'Contabilidad y Finanzas', 'mauricio.olivares@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(62, '79016164', 'Fátima Carmen Medina Paredes', 'Administración de Empresas', 'fatima.medina@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(63, '75373637', 'Bianca Isabel Vásquez Ríos', 'Mecatrónica Automotriz', 'bianca.vasquez@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(64, '73105146', 'Alejandra Lucía Ñique Torres', 'Plataformas y Servicios de Tecnologías de la Información', 'alejandra.nique@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(65, '78794024', 'Mateo Carlos Gómez Lozano', 'Desarrollo de Sistemas de Información', 'mateo.gomez@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(66, '72674506', 'Gabriela Elena Lozano Ñique', 'Contabilidad y Finanzas', 'gabriela.lozano@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(67, '76796553', 'Álvaro Esteban Ñáñez Morán', 'Administración de Empresas', 'alvaro.nanez@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(68, '75721704', 'Rocío Ariana Quispe Castillo', 'Mecatrónica Automotriz', 'rocio.quispe@iesp.edu.pe', '2026-11-06 21:42:28', '2026-11-06 21:42:28'),
(69, '79404966', 'Valeria Abigail Medina Lozano', 'Plataformas y Servicios de Tecnologías de la Información', 'valeria.medina@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(70, '72346655', 'Álvaro Óscar Córdova Carranza', 'Desarrollo de Sistemas de Información', 'alvaro.cordova@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(71, '75830120', 'Valeska Andrea Sánchez Tello', 'Contabilidad y Finanzas', 'valeska.sanchez@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(72, '79329398', 'Carmen Sofía Huamán Córdova', 'Administración de Empresas', 'carmen.huaman@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(73, '76886614', 'Bianca Romina Torres Medina', 'Mecatrónica Automotriz', 'bianca.torres@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(74, '79935662', 'Alejandra Romina Olivares Quiñones', 'Plataformas y Servicios de Tecnologías de la Información', 'alejandra.olivares@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(75, '78872905', 'David Sebastián Herrera Lozano', 'Desarrollo de Sistemas de Información', 'david.herrera@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(76, '74729871', 'Ana Elena Herrera Luna', 'Contabilidad y Finanzas', 'ana.herrera@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(77, '74752652', 'Patricia Fátima Flores Cárdenas', 'Administración de Empresas', 'patricia.flores@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(78, '78457408', 'Fernando Manuel Chunga Palacios', 'Mecatrónica Automotriz', 'fernando.chunga@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(79, '72979479', 'Elena Isabel Sánchez Castro', 'Plataformas y Servicios de Tecnologías de la Información', 'elena.sanchez@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(80, '74497297', 'Ariana Natalia Benítez Gutiérrez', 'Desarrollo de Sistemas de Información', 'ariana.benitez@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(81, '75286027', 'Mateo Óscar Flores Díaz', 'Contabilidad y Finanzas', 'mateo.flores@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(82, '79246428', 'María Abigail Quiñones Cañari', 'Administración de Empresas', 'maria.quinones@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(83, '75180619', 'Leonardo Fabricio Alva Vargas', 'Mecatrónica Automotriz', 'leonardo.alva@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(84, '70120993', 'Darío Fabricio Peña Vargas', 'Plataformas y Servicios de Tecnologías de la Información', 'dario.pena@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(85, '77076176', 'Carolina Diana Flores Cañari', 'Desarrollo de Sistemas de Información', 'carolina.flores@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(86, '77976971', 'Patricia Elena Rojas Rodríguez', 'Contabilidad y Finanzas', 'patricia.rojas@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(87, '77448247', 'Carlos Raúl Ñique Zúñiga', 'Administración de Empresas', 'carlos.nique@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31'),
(88, '79101847', 'Hugo Leonardo Herrera Zavala', 'Mecatrónica Automotriz', 'hugo.herrera@iesp.edu.pe', '2026-11-06 21:43:31', '2026-11-06 21:43:31');

-- --------------------------------------------------------
--
-- Estructura de tabla para la tabla `failed_jobs`
--
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`),
  KEY `failed_jobs_connection_queue_failed_at_index` (`connection`,`queue`,`failed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Estructura de tabla para la tabla `job_batches`
--
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int NOT NULL,
  `pending_jobs` int NOT NULL,
  `failed_jobs` int NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `cancelled_at` int DEFAULT NULL,
  `created_at` int NOT NULL,
  `finished_at` int DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Estructura de tabla para la tabla `jobs`
--
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint unsigned NOT NULL,
  `reserved_at` int unsigned DEFAULT NULL,
  `available_at` int unsigned NOT NULL,
  `created_at` int unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `jobs_queue_index` (`queue`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Estructura de tabla para la tabla `migrations`
--
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations` (
  `id` int unsigned NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `migrations`
--
INSERT INTO `migrations` (`id`, `migration`, `batch`) VALUES
(1, '0001_01_01_000000_create_users_table', 1),
(2, '0001_01_01_000001_create_cache_table', 1),
(3, '0001_01_01_000002_create_jobs_table', 1),
(4, '2026_09_01_203902_create_page_views_table', 1),
(5, '2026_09_14_000000_create_estudiantes_table', 1),
(6, '2026_10_23_220452_create_demo_backups_table', 2),
(7, '2026_11_03_194244_create_audit_logs_table', 3),
(8, '2026_11_03_194245_drop_page_views_table', 3),
(9, '2026_11_06_141015_add_database_and_replication_to_demo_backups_table', 4),
(10, '2026_11_06_175211_add_performance_indexes_to_demo_backups_table', 5);

-- --------------------------------------------------------
--
-- Estructura de tabla para la tabla `password_reset_tokens`
--
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens` (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Estructura de tabla para la tabla `sessions`
--
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions` (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint unsigned DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sessions_user_id_index` (`user_id`),
  KEY `sessions_last_activity_index` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Estructura de tabla para la tabla `users`
--
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--
INSERT INTO `users` (`id`, `name`, `email`, `email_verified_at`, `password`, `remember_token`, `created_at`, `updated_at`) VALUES
(1, 'Admin', 'admin@demo.local', NULL, '$2y$12$VLc2FKHp42xKCJISYFZwfe908QI8kXIPutyJNIHlQxnS/exto2lV2', 'bjgIbDFAS2vrtcDcEdNDDdE957Efvwg2bIl0F19S4ODiEFs1jPySuSGL6NYr', '2026-10-23 16:50:49', '2026-10-23 16:50:49');

SET FOREIGN_KEY_CHECKS=1;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
