PRAGMA foreign_keys = ON;

CREATE TABLE carreras (
    id INTEGER PRIMARY KEY,
    codigo TEXT NOT NULL UNIQUE,
    nombre TEXT NOT NULL UNIQUE
);

CREATE TABLE estudiantes (
    id INTEGER PRIMARY KEY,
    codigo TEXT NOT NULL UNIQUE,
    nombres TEXT NOT NULL,
    carrera_id INTEGER NOT NULL REFERENCES carreras (id),
    correo TEXT NOT NULL UNIQUE,
    estado TEXT NOT NULL CHECK (estado IN ('activo', 'egresado', 'retirado')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE asignaturas (
    id INTEGER PRIMARY KEY,
    codigo TEXT NOT NULL UNIQUE,
    nombre TEXT NOT NULL,
    ciclo INTEGER NOT NULL CHECK (ciclo BETWEEN 1 AND 10)
);

CREATE TABLE matriculas (
    id INTEGER PRIMARY KEY,
    estudiante_id INTEGER NOT NULL REFERENCES estudiantes (id),
    asignatura_id INTEGER NOT NULL REFERENCES asignaturas (id),
    periodo TEXT NOT NULL,
    estado TEXT NOT NULL CHECK (estado IN ('matriculado', 'retirado', 'completado')),
    UNIQUE (estudiante_id, asignatura_id, periodo)
);

CREATE TABLE notas (
    id INTEGER PRIMARY KEY,
    matricula_id INTEGER NOT NULL REFERENCES matriculas (id),
    evaluacion TEXT NOT NULL,
    nota REAL NOT NULL CHECK (nota BETWEEN 0 AND 20),
    updated_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (matricula_id, evaluacion)
);

CREATE TABLE asistencias (
    id INTEGER PRIMARY KEY,
    matricula_id INTEGER NOT NULL REFERENCES matriculas (id),
    fecha TEXT NOT NULL,
    estado TEXT NOT NULL CHECK (estado IN ('presente', 'tardanza', 'falta', 'justificada')),
    UNIQUE (matricula_id, fecha)
);

CREATE TABLE expedientes (
    id INTEGER PRIMARY KEY,
    estudiante_id INTEGER NOT NULL REFERENCES estudiantes (id),
    tipo TEXT NOT NULL,
    referencia TEXT NOT NULL UNIQUE,
    estado TEXT NOT NULL CHECK (estado IN ('registrado', 'en_revision', 'archivado')),
    created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO carreras (id, codigo, nombre) VALUES
    (1, 'DEMO-ADM', 'Administración'),
    (2, 'DEMO-COM', 'Computación e Informática');

INSERT INTO estudiantes (id, codigo, nombres, carrera_id, correo, estado) VALUES
    (1, 'DEMO-EST-0001', 'Estudiante Demostración 01', 1, 'estudiante01@example.invalid', 'activo'),
    (2, 'DEMO-EST-0002', 'Estudiante Demostración 02', 1, 'estudiante02@example.invalid', 'activo'),
    (3, 'DEMO-EST-0003', 'Estudiante Demostración 03', 2, 'estudiante03@example.invalid', 'activo'),
    (4, 'DEMO-EST-0004', 'Estudiante Demostración 04', 2, 'estudiante04@example.invalid', 'egresado'),
    (5, 'DEMO-EST-0005', 'Estudiante Demostración 05', 1, 'estudiante05@example.invalid', 'activo');

INSERT INTO asignaturas (id, codigo, nombre, ciclo) VALUES
    (1, 'DEMO-ASG-001', 'Fundamentos de Administración', 1),
    (2, 'DEMO-ASG-002', 'Ofimática Aplicada', 2),
    (3, 'DEMO-ASG-003', 'Fundamentos de Bases de Datos', 3),
    (4, 'DEMO-ASG-004', 'Gestión de Información', 4);

INSERT INTO matriculas (id, estudiante_id, asignatura_id, periodo, estado) VALUES
    (1, 1, 1, '2026-I', 'matriculado'),
    (2, 1, 2, '2026-I', 'matriculado'),
    (3, 2, 1, '2026-I', 'matriculado'),
    (4, 3, 3, '2026-I', 'matriculado'),
    (5, 3, 4, '2026-I', 'matriculado'),
    (6, 4, 3, '2025-II', 'completado'),
    (7, 5, 2, '2026-I', 'matriculado');

INSERT INTO notas (id, matricula_id, evaluacion, nota) VALUES
    (1, 1, 'Parcial', 16.5),
    (2, 1, 'Practica 1', 18.0),
    (3, 2, 'Parcial', 14.0),
    (4, 3, 'Parcial', 12.5),
    (5, 4, 'Parcial', 17.0),
    (6, 5, 'Practica 1', 15.5),
    (7, 6, 'Final', 18.0),
    (8, 7, 'Parcial', 19.0);

INSERT INTO asistencias (id, matricula_id, fecha, estado) VALUES
    (1, 1, '2026-10-05', 'presente'),
    (2, 1, '2026-10-12', 'tardanza'),
    (3, 2, '2026-10-06', 'presente'),
    (4, 3, '2026-10-05', 'falta'),
    (5, 4, '2026-10-07', 'presente'),
    (6, 5, '2026-10-08', 'justificada'),
    (7, 6, '2025-11-15', 'presente'),
    (8, 7, '2026-10-06', 'presente');

INSERT INTO expedientes (id, estudiante_id, tipo, referencia, estado) VALUES
    (1, 1, 'Solicitud académica', 'DEMO-EXP-2026-0001', 'registrado'),
    (2, 2, 'Constancia de estudios', 'DEMO-EXP-2026-0002', 'en_revision'),
    (3, 3, 'Actualización de datos', 'DEMO-EXP-2026-0003', 'registrado'),
    (4, 4, 'Certificado de egreso', 'DEMO-EXP-2025-0004', 'archivado'),
    (5, 5, 'Solicitud académica', 'DEMO-EXP-2026-0005', 'registrado');
