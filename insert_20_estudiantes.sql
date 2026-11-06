-- ==============================================================================
-- SCRIPT DE INSERCIÓN DE PRUEBA: 20 ESTUDIANTES INSTITUCIONALES
-- IESP CAP. FAP JOSÉ ABELARDO QUIÑONES - TUMBES
-- Plataformas y Servicios de Tecnologías de la Información
-- Compatibilidad: phpMyAdmin, XAMPP (MariaDB), Laragon (MySQL 8.x)
-- Codificación: UTF-8 Puro (Preserva tildes: á, é, í, ó, ú y eñes: ñ, Ñ)
-- ==============================================================================

SET NAMES utf8mb4;
USE `iesp_continuidad_demo`;

INSERT INTO `estudiantes` (`dni`, `nombres`, `carrera`, `correo`, `created_at`, `updated_at`) VALUES
('71000001', 'Mateo Alejandro Morales Castro', 'Plataformas y Servicios de Tecnologías de la Información', 'mateo.morales@iesp.edu.pe', NOW(), NOW()),
('71000002', 'Valeria Nicole Sánchez Rivas', 'Desarrollo de Sistemas de Información', 'valeria.sanchez@iesp.edu.pe', NOW(), NOW()),
('71000003', 'Joaquín Alonso Herrera Ñáñez', 'Plataformas y Servicios de Tecnologías de la Información', 'joaquin.herrera@iesp.edu.pe', NOW(), NOW()),
('71000004', 'Camila Esperanza Gómez Zúñiga', 'Contabilidad y Finanzas', 'camila.gomez@iesp.edu.pe', NOW(), NOW()),
('71000005', 'Diego Sebastián Mendoza Cárdenas', 'Mecatrónica Automotriz', 'diego.mendoza@iesp.edu.pe', NOW(), NOW()),
('71000006', 'Luciana Belén Flores Peña', 'Administración de Empresas', 'luciana.flores@iesp.edu.pe', NOW(), NOW()),
('71000007', 'Álvaro Gabriel Romero Núñez', 'Plataformas y Servicios de Tecnologías de la Información', 'alvaro.romero@iesp.edu.pe', NOW(), NOW()),
('71000008', 'Fiorella Yamileth Cruz Alva', 'Desarrollo de Sistemas de Información', 'fiorella.cruz@iesp.edu.pe', NOW(), NOW()),
('71000009', 'Rodrigo Andrés Medina Ríos', 'Mecatrónica Automotriz', 'rodrigo.medina@iesp.edu.pe', NOW(), NOW()),
('71000010', 'Andrea Stephanie Benítez Quiñones', 'Contabilidad y Finanzas', 'andrea.benitez@iesp.edu.pe', NOW(), NOW()),
('71000011', 'Sebastián Ignacio Paredes Cañari', 'Plataformas y Servicios de Tecnologías de la Información', 'sebastian.paredes@iesp.edu.pe', NOW(), NOW()),
('71000012', 'Daniela Alejandra Ramos Huamán', 'Administración de Empresas', 'daniela.ramos@iesp.edu.pe', NOW(), NOW()),
('71000013', 'Christian Jesús Espinoza Morales', 'Desarrollo de Sistemas de Información', 'christian.espinoza@iesp.edu.pe', NOW(), NOW()),
('71000014', 'Romina Beatriz Delgado Carranza', 'Contabilidad y Finanzas', 'romina.delgado@iesp.edu.pe', NOW(), NOW()),
('71000015', 'Esteban Darío Salazar Vílchez', 'Mecatrónica Automotriz', 'esteban.salazar@iesp.edu.pe', NOW(), NOW()),
('71000016', 'Melany Alexandra Quispe Tello', 'Plataformas y Servicios de Tecnologías de la Información', 'melany.quispe@iesp.edu.pe', NOW(), NOW()),
('71000017', 'Gonzalo David Castillo Barreto', 'Desarrollo de Sistemas de Información', 'gonzalo.castillo@iesp.edu.pe', NOW(), NOW()),
('71000018', 'Ariana Nicole Vásquez Córdova', 'Administración de Empresas', 'ariana.vasquez@iesp.edu.pe', NOW(), NOW()),
('71000019', 'Fabricio Emmanuel Luna Ñique', 'Mecatrónica Automotriz', 'fabricio.luna@iesp.edu.pe', NOW(), NOW()),
('71000020', 'Génesis Milagros Lozano Chávez', 'Plataformas y Servicios de Tecnologías de la Información', 'genesis.lozano@iesp.edu.pe', NOW(), NOW());

