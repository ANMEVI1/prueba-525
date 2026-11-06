# Sistema de Continuidad Operativa y Gestión de Respaldos TI

### **IESP Cap. FAP José Abelardo Quiñones — Tumbes**
*Plataformas y Servicios de Tecnologías de la Información*

---

## 📌 Descripción del Proyecto

El **Sistema de Continuidad Operativa y Gestión de Respaldos TI** es una plataforma integral diseñada para garantizar la resiliencia operativa y la protección de datos institucionales del **IESP Cap. FAP José Abelardo Quiñones**.

Implementa políticas de seguridad de nivel industrial:
* **Cifrado AES-256-CBC**: Protección criptográfica de las copias de seguridad de las bases de datos.
* **Integridad Hash SHA-256**: Detección y validación estricta de alteraciones o corrupción de datos.
* **RTO < 15 minutos**: Tiempo objetivo de recuperación ante fallos o contingencias técnicas.
* **Replicación en Almacenamiento Secundario**: Copias espejo simultáneas en discos aislados o rutas de red.
* **Compatibilidad Universal Multiplataforma**: Compatible de forma nativa con **XAMPP (MariaDB / phpMyAdmin)**, **Laragon (MySQL 8.x)** y **SQLite**.
* **Auditoría Inmutable de Eventos**: Registro de trazabilidad y operaciones de respaldo y restauración.
* **Padrón de Estudiantes (CRUD)**: Mantenimiento y consulta de registros académicos en tiempo real.

---

## 🚀 Inicio Rápido en Cualquier PC (1 Solo Clic)

El repositorio incluye el asistente automatizado universal `iniciar_proyecto.bat`. 

1. Abre el panel de control de **XAMPP** (o **Laragon**) e inicia el módulo de **MySQL** (puerto `3306`).
2. Haz **doble clic en `iniciar_proyecto.bat`**.
3. El script automáticamente:
   * Detectará el intérprete de PHP y el motor MySQL en tu computadora.
   * Creará tu archivo de configuración `.env` si es la primera vez.
   * Generará la clave de cifrado segura de la aplicación (`APP_KEY`).
   * Creará e importará automáticamente la base de datos oficial `iesp_continuidad_demo` con codificación UTF-8.
   * Limpiará la caché y abrirá tu navegador web predeterminado en `http://127.0.0.1:8000`.

---

## 🔐 Credenciales de Acceso al Sistema

| Campo | Valor de Acceso |
|---|---|
| **URL del Sistema** | `http://127.0.0.1:8000` |
| **Usuario** | `Admin` *(o `admin@demo.local`)* |
| **Contraseña** | `Admin` |
| **Rol** | Administrador TI Institucional |

*(El sistema cuenta con un modal interactivo en el encabezado para cambiar la contraseña con visualizador seguro tipo ojito).*

---

## 🛠️ Instalación y Configuración Manual (Opcional)

Si prefieres ejecutar el sistema paso a paso desde la terminal de comandos:

### 1. Clonar el Repositorio
```bash
git clone https://github.com/TU-USUARIO/TU-REPOSITORIO.git
cd TU-REPOSITORIO
```

### 2. Configurar el Entorno
```bash
copy .env.example .env
php artisan key:generate
```

### 3. Importar la Base de Datos Institucional
Tienes dos métodos sencillos y 100% compatibles:

* **Método A (Automático por Artisan):**
  ```bash
  php artisan app:import-clean-sql
  ```
* **Método B (Desde phpMyAdmin en XAMPP):**
  1. Ingresa a `http://localhost/phpmyadmin`.
  2. Crea la base de datos `iesp_continuidad_demo` *(o déjalo automático)*.
  3. Haz clic en la pestaña **Importar**.
  4. Selecciona el archivo `iesp_continuidad_demo.sql` ubicado en la raíz del proyecto y pulsa **Continuar**.

### 4. Iniciar el Servidor Local
```bash
php artisan serve
```
Ingresa desde tu navegador a `http://127.0.0.1:8000`.

---

## 🗄️ Estructura del Archivo SQL (`iesp_continuidad_demo.sql`)

El volcado SQL institucional ubicado en la raíz del proyecto ha sido generado con la sintaxis universal estándar de **phpMyAdmin / XAMPP / MariaDB 10.4+** y **MySQL 8.x**:

* Codificación estricta **UTF-8 puro (`utf8mb4_unicode_ci`)**: No produce errores ni corrupción de caracteres en nombres con tildes (`á`, `é`, `í`, `ó`, `ú`) ni eñes (`ñ`, `Ñ`).
* **Directivas de portabilidad**: Incluye cabeceras de deshabilitación temporal de llaves foráneas (`SET FOREIGN_KEY_CHECKS=0`), creación condicional de la base de datos (`CREATE DATABASE IF NOT EXISTS`) y selección de esquema (`USE \`iesp_continuidad_demo\``).
* Tablas exportadas:
  * `users`: Usuario Administrador del sistema TI.
  * `estudiantes`: Padrón oficial de estudiantes de las distintas carreras del instituto.
  * `demo_backups`: Historial de copias de seguridad con hashes SHA-256, tamaños, duración y rutas de replicación.
  * `audit_logs`: Bitácora inmutable de eventos y operaciones.
  * `migrations`: Control de versiones de base de datos de Laravel.
  * `cache`, `sessions`, `jobs`: Soporte de rendimiento y sesiones del sistema.

---

## 🧪 Verificación y Pruebas Unitarias / Feature

El proyecto cuenta con una suite completa de pruebas automatizadas:

```bash
php artisan test --compact
```
* **44 pruebas exitosas** (171 aserciones).
* Cobertura de autenticación, control de acceso, integridad criptográfica SHA-256, compresión, restauración de respaldos y validaciones.

---

## 👥 Equipo de Desarrollo e Información Académica

* **Institución:** Instituto de Educación Superior Pedagógico Cap. FAP José Abelardo Quiñones
* **Programa de Estudios:** Plataformas y Servicios de Tecnologías de la Información
* **Ubicación:** Tumbes, Perú
* **Año:** 2026
