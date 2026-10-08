<div align="center">

# 🩺 VitalTrace · API

### Backend de la plataforma de seguimiento clínico continuo

API RESTful para la gestión de pacientes con enfermedades crónicas, discapacidad o condiciones que requieren controles frecuentes. Conecta a pacientes, familiares y personal de salud bajo un mismo expediente.

<br>

![Laravel](https://img.shields.io/badge/Laravel-10.50.2-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Sanctum](https://img.shields.io/badge/Laravel_Sanctum-3.3-F9322C?style=for-the-badge&logo=laravel&logoColor=white)

![Estado](https://img.shields.io/badge/estado-en_producción-017D84?style=flat-square)
![API](https://img.shields.io/badge/API-REST_%2Fapi%2Fv1-01305E?style=flat-square)
![Respuestas](https://img.shields.io/badge/respuestas-JSON-60CEC8?style=flat-square)
![Equipo](https://img.shields.io/badge/equipo-QuantumMinds-283137?style=flat-square)

</div>

---

## 📑 Tabla de contenido

- [Descripción general](#-descripción-general)
- [Stack tecnológico](#-stack-tecnológico)
- [Arquitectura](#-arquitectura)
- [Autenticación y activación](#-autenticación-y-activación)
- [Roles y autorización](#-roles-y-autorización)
- [Implementaciones destacadas](#-implementaciones-destacadas)
- [Mapa de endpoints](#-mapa-de-endpoints)
- [Modelo de datos](#-modelo-de-datos)
- [Auditoría y trazabilidad](#-auditoría-y-trazabilidad)
- [Convenciones de la API](#-convenciones-de-la-api)
- [Instalación](#-instalación)
- [Despliegue](#-despliegue)
- [Pruebas](#-pruebas)
- [Reglas de negocio](#-reglas-de-negocio)

---

## 🎯 Descripción general

**VitalTrace** nace para resolver un problema concreto: el seguimiento de pacientes crónicos suele vivir disperso entre libretas, mensajes y documentos sueltos, lo que provoca olvidos de citas, interrupción de tratamientos y detección tardía de complicaciones.

Esta API es el **núcleo del sistema**. Es el único componente autorizado para leer o escribir la base de datos clínica; concentra toda la lógica de negocio, la seguridad, la persistencia y la exposición de datos a dos clientes:

<table>
<tr>
<td width="50%" valign="top">

### 💻 Aplicación web
Para el **personal de salud y administrativo** — médicos, admisión y administración del sistema. Consume la API por sesión de cookie (SPA).

</td>
<td width="50%" valign="top">

### 📱 Aplicación móvil
Para **pacientes y familiares**. Consume la API mediante token (Bearer).

</td>
</tr>
</table>

---

## 🛠 Stack tecnológico

| Componente | Tecnología | Versión |
|------------|-----------|---------|
| **Framework** | Laravel | `10.50.2` |
| **Lenguaje** | PHP | `8.2` |
| **Base de datos** | MySQL | — |
| **Autenticación** | Laravel Sanctum | `^3.3` |
| **Cliente HTTP** | Guzzle | `^7.2` |
| **Pruebas** | PHPUnit | `^10.1` |
| **Arquitectura** | API RESTful · sin vistas Blade | `/api/v1` |

---

## 🏗 Arquitectura

La API sigue una arquitectura en capas con separación estricta de responsabilidades. Ningún controlador concentra reglas complejas: la lógica vive en servicios, la validación en Form Requests y la autorización en middleware y traits reutilizables.

```
Cliente (web / móvil)
        │  HTTPS · JSON
        ▼
┌─────────────────────────────────────────────┐
│  Routes (/api/v1)  →  middleware por rol      │
│        ▼                                       │
│  Controllers  →  Form Requests (validación)    │
│        ▼                                       │
│  Services / Traits  (lógica de dominio)        │
│        ▼                                       │
│  Models (Eloquent)                             │
└─────────────────────────────────────────────┘
        │
        ▼
   MySQL / MariaDB  +  Auditoría (Observer)
```

<details>
<summary><b>📂 Estructura del proyecto</b></summary>

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Auth/            # Login, activación, recuperación de contraseña
│   │   ├── Portal/          # Portales de paciente, familiar y enfermería
│   │   └── Concerns/        # ResolvesAssignedPatients (trait de alcance RN-06)
│   ├── Requests/            # 66 Form Requests de validación
│   └── Resources/           # Serialización JSON (AuditLogResource, etc.)
├── Models/                  # 30 modelos Eloquent
├── Observers/               # AuditableObserver (auditoría automática)
├── Services/
│   ├── ActivationService
│   ├── PasswordResetService
│   ├── ProfessionalRegistrationService
│   └── Portal/              # NursePatientAccessService, RelativePatientAccessService
└── Support/
    └── Auditing/            # AuditLogger (motor central de auditoría)

database/
├── migrations/              # Esquema normalizado hasta 3FN
└── seeders/                 # Datos de arranque y demo

routes/api.php               # Endpoints versionados, agrupados por rol
lang/es/                     # Mensajes en español (validación, auth)
tests/Feature/               # Pruebas de portales, activación, registro
```

</details>

---

## 🔐 Autenticación y activación

### Autenticación híbrida

La API usa **Laravel Sanctum** con una estrategia híbrida según el cliente:

| Cliente | Mecanismo |
|---------|-----------|
| **Aplicación web (SPA)** | Sesión basada en cookie, con cookie CSRF y dominios *stateful* |
| **Aplicación móvil** | Token Bearer (`createToken`) |

Las rutas públicas de autenticación están protegidas con **rate limiting** (`throttle:6,1`) contra ataques de fuerza bruta.

### Activación de cuentas por código

> 🔑 **El primer acceso de pacientes y familiares se realiza mediante un código numérico de seis dígitos enviado al correo**, nunca por entrega manual de PIN.

<table>
<tr><th>Característica</th><th>Regla</th></tr>
<tr><td>Formato</td><td>Código numérico de 6 dígitos</td></tr>
<tr><td>Entrega</td><td>Correo electrónico registrado por Admisión</td></tr>
<tr><td>Vigencia</td><td>24 horas desde su generación</td></tr>
<tr><td>Uso</td><td>Un solo uso; se invalida al activarse</td></tr>
<tr><td>Almacenamiento</td><td>Hash seguro — <b>nunca</b> en texto plano</td></tr>
<tr><td>Intentos</td><td>Máximo de intentos antes de invalidarse</td></tr>
<tr><td>Reenvío</td><td>El nuevo código invalida cualquier código pendiente anterior</td></tr>
</table>

El flujo se realiza en **dos pasos** para que la contraseña nunca viaje junto al código:

```
1. POST /auth/activation/verify-code    →  email + código  →  token temporal
2. POST /auth/activation/set-password   →  token + contraseña  →  cuenta ACTIVA
```

La **recuperación de contraseña** sigue el mismo patrón: código de seis dígitos por correo (en lugar de un enlace con token), con su propia tabla, vigencia y máximo de intentos.

---

## 👥 Roles y autorización

El sistema define **seis roles**. La autorización combina el rol del usuario con la **relación vigente** entre profesional y paciente.

<div align="center">

`PACIENTE` · `FAMILIAR` · `MÉDICO` · `ENFERMERO` · `ADMISIÓN` · `ADMINISTRADOR DEL SISTEMA`

</div>

<br>

| Rol | Responsabilidad principal |
|-----|---------------------------|
| **Paciente** | Consulta su información, registra mediciones, confirma medicación, autoriza familiares |
| **Familiar** | Acompaña al paciente según autorización y alcance definido |
| **Médico** | Diagnósticos, tratamientos, evoluciones, rangos clínicos y cierre de alertas |
| **Enfermero** | Signos vitales, observaciones, mediciones y escalamiento de alertas |
| **Admisión** | Registra pacientes y familiares, asigna personal, controla activaciones y correcciones |
| **Administrador** | Roles, permisos, catálogos, auditoría y configuración técnica |

> ⚖️ **Separación de responsabilidades:** Admisión gestiona el ingreso y los datos administrativos; el personal clínico se concentra en el seguimiento de sus pacientes asignados; el Administrador del sistema mantiene la operación técnica y **no obtiene acceso clínico automático**.

### 🛡️ Control de acceso por asignación (RN-06)

Uno de los pilares de seguridad: **un profesional clínico solo accede a los pacientes que tiene asignados de forma vigente**. Esto se implementa de forma centralizada:

- **Médicos** → el trait `ResolvesAssignedPatients` filtra y rechaza (`403`) operaciones sobre pacientes no asignados.
- **Enfermería** → el `NursePatientAccessService` aplica un control aún más estricto: valida rol, perfil activo **y vigencia de fechas** de la asignación.

---

## ⭐ Implementaciones destacadas

Estas son las funcionalidades más relevantes desarrolladas en el proyecto:

### 1️⃣ Sistema de auditoría automática e inmutable

Se implementó un **motor de auditoría transversal** que registra automáticamente toda operación relevante del sistema, sin que cada controlador tenga que invocarlo manualmente.

- **Observer de Eloquent** (`AuditableObserver`) que captura `CREATE`, `UPDATE` y `DELETE` de los modelos auditables.
- **Servicio central** (`AuditLogger`) que captura el contexto completo: usuario, rol, acción, tabla, registro, **valores anteriores y nuevos**, IP, *user agent* e **identificador de correlación de la solicitud**.
- **Redacción de datos sensibles**: contraseñas, códigos y tokens nunca se almacenan en la auditoría.
- **Tabla inmutable** (*append-only*): sin `update` ni `delete` expuestos.
- **Identificación de portal y módulo**: los eventos `LOGIN` / `LOGOUT` registran el portal de acceso (Portal médico, administrativo, de admisión…) y los accesos a módulos sensibles (Auditoría, Catálogos, expediente de paciente) generan eventos `ACCESS` específicos.
- **Filtros de búsqueda**: por usuario, acción, tabla, registro, módulo y rango de fechas.

### 2️⃣ Corrección administrativa directa

El módulo de correcciones se rediseñó de un flujo de "solicitudes" (que ningún cliente podía generar) a un **flujo de mantenimiento administrativo directo y presencial**:

- Admisión busca al paciente (por nombre, documento o expediente), consulta sus datos y edita **solo campos administrativos autorizados**.
- **Motivo obligatorio** en cada corrección.
- Actualización **transaccional** de persona + paciente.
- Cada campo corregido queda **registrado con valor anterior y nuevo**, además de la auditoría automática.

### 3️⃣ Portal de enfermería seguro

Portal independiente para enfermería con acceso clínico acotado por asignación vigente (con validación de fechas), para registrar signos vitales, mediciones y clasificar o escalar alertas, sin capacidad de diagnosticar ni modificar prescripciones.

### 4️⃣ Evaluación clínica del médico

Registro clínico completo acotado por asignación: diagnósticos, evoluciones, tratamientos con medicamentos, **rangos clínicos** (base del motor de alertas) y gestión de citas.

### 5️⃣ Consistencia de límites de datos

Validación coherente de longitud de campos en las **tres capas** (frontend, API y base de datos): los Form Requests validan los mismos límites que el esquema, con mensajes claros que indican el máximo permitido.

### 6️⃣ Internacionalización al español

Toda la API responde en español: mensajes de validación, autenticación, respuestas de controladores y correos de activación.

---

## 🗺 Mapa de endpoints

Todos los endpoints se versionan bajo **`/api/v1`** y agrupan por rol mediante middleware.

<details open>
<summary><b>🔓 Autenticación (público · rate-limited)</b></summary>

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `POST` | `/auth/login` | Inicia sesión (cookie SPA o token móvil) |
| `GET`  | `/auth/me` | Usuario autenticado, roles y permisos |
| `POST` | `/auth/logout` | Cierra sesión |
| `POST` | `/auth/activation/verify-code` | Paso 1: verifica el código → token temporal |
| `POST` | `/auth/activation/set-password` | Paso 2: establece la contraseña → cuenta activa |
| `POST` | `/auth/activation/resend-code` | Reenvía el código de activación |
| `POST` | `/auth/forgot-password` | Envía código de recuperación |
| `POST` | `/auth/reset-password` | Restablece la contraseña con código |

</details>

<details>
<summary><b>📱 Portal del paciente <code>role:PATIENT</code></b></summary>

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/patient/summary · /profile · /clinical-history` | Resumen, perfil e historial autorizado |
| `GET` | `/patient/appointments · /treatments` | Próximas citas y tratamientos vigentes |
| `GET/POST` | `/patient/measurements` | Consultar y registrar mediciones del plan |
| `POST` | `/patient/correction-requests` | Solicitar corrección de datos |
| `GET/PATCH` | `/patient/notifications` | Notificaciones y marcado como leído |
| `GET/PUT/DELETE` | `/patient/relatives` | Autorizar y revocar acceso familiar |

</details>

<details>
<summary><b>👨‍👩‍👧 Portal del familiar <code>role:RELATIVE</code></b></summary>

Acceso de **solo lectura**, acotado por cada paciente autorizado (`RelativePatientAccessService`):
`/relative/patients` y, por paciente, `summary` · `profile` · `appointments` · `measurements` · `treatments` · `clinical-history`.

</details>

<details>
<summary><b>🩺 Portal de enfermería <code>role:NURSE</code></b></summary>

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/nurse/summary · /patients` | Panel y pacientes asignados (con vigencia) |
| `GET/POST` | `/nurse/patients/{p}/measurements` | Consultar y registrar signos/mediciones |
| `GET` | `/nurse/patients/{p}/...` | Perfil, resumen, citas, diagnósticos, tratamientos, historial, alertas |
| `GET/POST` | `/nurse/alerts · /alerts/{a}/classify · /escalate` | Alertas: clasificar y escalar |

</details>

<details>
<summary><b>📋 Admisión <code>role:ADMISSION</code></b></summary>

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET/POST/PUT` | `/admission/patients{/id}` | Registrar y actualizar pacientes |
| `GET/POST` | `/admission/patients/{p}/relatives` | Familiares (máx. 2 activos, con cuenta y código) |
| `POST` | `/admission/patients/{p}/assignments` | Asignar médico o enfermero |
| `GET/POST` | `/admission/accounts` | Cuentas de acceso y reenvío de códigos |
| `GET/POST` | `/admission/appointments` | Citas para cualquier paciente y profesional |
| `POST` | `/admission/patients/{p}/corrections` | **Corrección administrativa directa** (motivo obligatorio) |
| `GET/POST` | `/admission/corrections` | Solicitudes de corrección y su resolución |

</details>

<details>
<summary><b>⚕️ Clínico · Médico <code>role:DOCTOR</code></b></summary>

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `GET` | `/clinical/patients(+{p}/summary)` | Pacientes asignados y su expediente |
| `POST` | `/clinical/patients/{p}/diagnoses` | Registrar diagnóstico |
| `POST` | `/clinical/patients/{p}/evolutions` | Registrar evolución clínica |
| `POST` | `/clinical/patients/{p}/treatments` | Registrar tratamiento con medicamentos |
| `POST` | `/clinical/patients/{p}/ranges` | Definir rango clínico (para alertas) |
| `GET/POST/PUT` | `/clinical/.../appointments` | Gestión de citas (acotada por asignación) |
| `GET/POST` | `/alerts · /alerts/{a}/classify\|escalate\|close` | Bandeja de alertas y flujo de atención |

</details>

<details>
<summary><b>⚙️ Administración <code>role:SYSTEM_ADMIN</code></b></summary>

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| `apiResource` | `/roles · /permissions · /users · /user-roles` | Gestión de acceso |
| `apiResource` | `/specialties · /medications · /measurement-types` | Catálogos administrables (CRUD) |
| `POST` | `/admin/professionals/register` | Registro de personal de salud |
| `GET/POST/DELETE` | `/admin/users/{u}/roles` | Asignar/revocar roles (con salvaguarda de auto-bloqueo) |
| `GET` | `/audit-logs` | Auditoría (con filtros por usuario, acción, módulo, fecha) |

</details>

---

## 🗃 Modelo de datos

Esquema relacional **normalizado hasta Tercera Forma Normal (3FN)**. Separa persona, cuenta, expediente, roles, relaciones y datos clínicos.

> 💡 **Decisiones de diseño clave:**
> - La **edad no se almacena** — se calcula a partir de `fecha_nacimiento`.
> - El **estado clínico se conserva como historial de evoluciones**, nunca se sobrescribe.
> - Los registros clínicos usan **borrado lógico** (*soft deletes*) con trazabilidad de autoría.

| Grupo | Tablas |
|-------|--------|
| **Identidad y acceso** | `people` · `users` · `roles` · `permissions` · `role_permissions` · `user_roles` |
| **Actores** | `patients` · `relatives` · `patient_relatives` · `health_staff` · `administrative_staff` · `specialties` |
| **Vínculos y activación** | `professional_assignments` · `account_activations` · `password_reset_codes` · `correction_requests` |
| **Clínico** | `diagnoses` · `clinical_evolutions` · `treatments` · `medications` · `treatment_medications` · `clinical_ranges` |
| **Seguimiento** | `measurement_types` · `measurements` · `appointments` · `alerts` · `alert_histories` |
| **Operación y auditoría** | `app_notifications` · `integration_logs` · `audit_logs` · `sessions` |

---

## 📜 Auditoría y trazabilidad

Cada entrada de `audit_logs` captura:

```json
{
  "user_id": 3,
  "role_snapshot": "SYSTEM_ADMIN",
  "action": "UPDATE",
  "module": "Portal administrativo",
  "table": "users",
  "record_id": 1,
  "old_values": { "status": "ACTIVE" },
  "new_values": { "status": "BLOCKED" },
  "ip_address": "198.54.115.69",
  "user_agent": "...",
  "request_id": "d72058da-ec85-4796-8608-95d0fafd2c24",
  "created_at": "2026-09-27 05:00:47"
}
```

| Acción | Qué registra |
|--------|--------------|
| `CREATE` / `UPDATE` / `DELETE` | Tabla, registro y valores anteriores/nuevos |
| `LOGIN` / `LOGOUT` | Portal de acceso según el rol |
| `ACCESS` | Módulo o recurso sensible consultado |

---

## 📐 Convenciones de la API

Todas las respuestas siguen una estructura **consistente** en español:

```json
{
  "data": { },
  "message": "Operación realizada correctamente.",
  "errors": null
}
```

| Código HTTP | Uso |
|:-----------:|-----|
| `200` / `201` / `204` | Éxito / creado / sin contenido |
| `401` | Sesión ausente o vencida |
| `403` | Autenticado sin permiso (rol o asignación) |
| `404` | Recurso no encontrado o no visible |
| `409` | Conflicto de estado o regla de negocio |
| `422` | Error de validación (con `errors` por campo) |
| `429` | Demasiados intentos |

---

## 🚀 Instalación

```bash
# 1. Clonar e instalar dependencias
git clone https://github.com/daamaleman/api-vitaltrace.git
cd api-vitaltrace
composer install

# 2. Configurar entorno
cp .env.example .env
php artisan key:generate
# Editar .env con credenciales de base de datos y correo

# 3. Migraciones y datos de arranque
php artisan migrate --seed

# 4. Servir en desarrollo
php artisan serve
```

**Requisitos:** PHP 8.2+, Composer, MySQL/MariaDB, extensión `pdo_mysql`.

---

## 🌐 Despliegue

La plataforma se aloja en **Namecheap** bajo el subdominio `api.vitaltrace.lat`.

```bash
# Secuencia de publicación
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

| Elemento | Configuración |
|----------|---------------|
| **Document root** | → `public` |
| **`.env`** | Fuera del repositorio |
| **Correo** | SMTP (códigos de activación y recuperación) |
| **Tareas programadas** | Cron: invalidar códigos vencidos, recordatorios, respaldos |
| **Seguridad** | HTTPS obligatorio · cookies *stateful* de Sanctum |

---

## 🧪 Pruebas

```bash
php artisan test
```

Cobertura de los flujos críticos mediante pruebas de característica (*Feature*):

- ✅ Portal de enfermería y su alcance por asignación
- ✅ Portal del familiar y acceso acotado por paciente
- ✅ Activación inicial del paciente por código
- ✅ Lecturas y escritura del portal del paciente
- ✅ Registro de profesionales

---

## 📋 Reglas de negocio

<table>
<tr><th>Código</th><th>Regla</th></tr>
<tr><td><code>RN-01</code></td><td>Solo Admisión crea pacientes y gestiona sus datos administrativos</td></tr>
<tr><td><code>RN-02</code></td><td>El paciente proporciona los datos del familiar; Admisión los registra y el paciente autoriza el alcance</td></tr>
<tr><td><code>RN-03</code></td><td>Máximo dos familiares activos por paciente</td></tr>
<tr><td><code>RN-04</code></td><td>Cada familiar tiene cuenta, correo, contraseña y código propios</td></tr>
<tr><td><code>RN-05</code></td><td>La edad se calcula desde la fecha de nacimiento; no se almacena</td></tr>
<tr><td><code>RN-06</code></td><td>Médicos y enfermeros solo acceden a pacientes con asignación profesional vigente</td></tr>
<tr><td><code>RN-09</code></td><td>El estado clínico se conserva mediante evoluciones históricas; no se sobrescribe</td></tr>
<tr><td><code>RN-10</code></td><td>El código de activación se guarda con hash, vence, es de un solo uso y se invalida al reenviarse</td></tr>
<tr><td><code>RN-11</code></td><td>Una alerta no constituye un diagnóstico; requiere revisión profesional</td></tr>
<tr><td><code>RN-12</code></td><td>Los registros clínicos usan borrado lógico y mantienen trazabilidad</td></tr>
</table>

---

<div align="center">

<br>

**VitalTrace API** · Seguimiento clínico continuo

Desarrollado por **QuantumMinds**

<sub>Prototipo académico · Laravel 10 · PHP 8.2 · Datos ficticios</sub>

</div>