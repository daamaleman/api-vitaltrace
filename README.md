<div align="center">

<h1>🩺 VitalTrace API</h1>

<p><strong>Continuous clinical follow-up platform · RESTful API</strong></p>

<p><em>Traza el recorrido del dato de salud desde que el paciente lo registra hasta que un profesional lo revisa.</em></p>

<p>
  <img alt="Laravel" src="https://img.shields.io/badge/Laravel-10.50.2-FF2D20?style=for-the-badge&logo=laravel&logoColor=white">
  <img alt="PHP" src="https://img.shields.io/badge/PHP-8.2-777BB4?style=for-the-badge&logo=php&logoColor=white">
  <img alt="MySQL" src="https://img.shields.io/badge/MySQL-MariaDB-4479A1?style=for-the-badge&logo=mysql&logoColor=white">
  <img alt="Sanctum" src="https://img.shields.io/badge/Auth-Sanctum_SPA-EF4444?style=for-the-badge">
</p>

<p>
  <img alt="Status" src="https://img.shields.io/badge/status-in_development-01305E?style=flat-square">
  <img alt="API version" src="https://img.shields.io/badge/API-v1-017D84?style=flat-square">
  <img alt="Tables" src="https://img.shields.io/badge/tables-29_functional-60CEC8?style=flat-square">
  <img alt="License" src="https://img.shields.io/badge/license-academic_prototype-283137?style=flat-square">
</p>

</div>

<hr>

<blockquote>
  <strong>⚠️ Aviso de alcance.</strong> VitalTrace es un prototipo académico con datos ficticios (QuantumMinds · Hackathon Nicaragua 2026). Registra información para <strong>seguimiento</strong>: no interpreta, no diagnostica y no sustituye la valoración de un profesional de la salud.
</blockquote>

<h2>📋 Tabla de contenido</h2>

<table>
  <tr>
    <td>
      <a href="#-sobre-el-proyecto">Sobre el proyecto</a><br>
      <a href="#-stack-tecnológico">Stack tecnológico</a><br>
      <a href="#-arquitectura">Arquitectura</a><br>
      <a href="#-modelo-de-datos">Modelo de datos</a><br>
    </td>
    <td>
      <a href="#-roles-y-permisos">Roles y permisos</a><br>
      <a href="#-autenticación">Autenticación</a><br>
      <a href="#-endpoints">Endpoints</a><br>
      <a href="#-instalación">Instalación</a><br>
    </td>
    <td>
      <a href="#-convenciones-de-código">Convenciones de código</a><br>
      <a href="#-despliegue">Despliegue</a><br>
      <a href="#-reglas-de-negocio">Reglas de negocio</a><br>
      <a href="#-equipo">Equipo</a><br>
    </td>
  </tr>
</table>

<hr>

<h2>🎯 Sobre el proyecto</h2>

<p>
<strong>VitalTrace API</strong> es el backend RESTful que sostiene la plataforma de seguimiento clínico continuo para personas con enfermedades crónicas o discapacidad. Centraliza en un solo lugar el seguimiento que suele quedar disperso entre libretas, mensajes y agendas.
</p>

<table>
  <tr>
    <td align="center" width="25%">
      <h3>🔐</h3>
      <strong>API pura</strong><br>
      <sub>Sin vistas Blade. Solo JSON versionado bajo <code>/api/v1</code>.</sub>
    </td>
    <td align="center" width="25%">
      <h3>🧩</h3>
      <strong>Modular</strong><br>
      <sub>Controladores delgados, Services, Form Requests, Policies y Resources.</sub>
    </td>
    <td align="center" width="25%">
      <h3>🛡️</h3>
      <strong>Segura</strong><br>
      <sub>Sanctum SPA por cookies, control por rol y por relación.</sub>
    </td>
    <td align="center" width="25%">
      <h3>📊</h3>
      <strong>Auditable</strong><br>
      <sub>Trazabilidad completa: created_by / updated_by / deleted_by.</sub>
    </td>
  </tr>
</table>

<hr>

<h2>⚙️ Stack tecnológico</h2>

<table>
  <thead>
    <tr>
      <th align="left">Capa</th>
      <th align="left">Tecnología</th>
      <th align="left">Detalle</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Framework</td>
      <td><strong>Laravel 10.50.2</strong></td>
      <td>API RESTful, sin Blade</td>
    </tr>
    <tr>
      <td>Lenguaje</td>
      <td><strong>PHP 8.2</strong></td>
      <td>Tipado estricto, PSR-12</td>
    </tr>
    <tr>
      <td>Base de datos</td>
      <td><strong>MySQL / MariaDB</strong></td>
      <td>Modelo relacional normalizado (3FN)</td>
    </tr>
    <tr>
      <td>Autenticación</td>
      <td><strong>Laravel Sanctum</strong></td>
      <td>SPA con cookies seguras</td>
    </tr>
    <tr>
      <td>Colas / tareas</td>
      <td><strong>Database queue + Cron</strong></td>
      <td>Correos de activación, tareas programadas</td>
    </tr>
    <tr>
      <td>Hosting</td>
      <td><strong>Namecheap (cPanel + LiteSpeed)</strong></td>
      <td>Subdominios <code>api.</code> y <code>app.</code></td>
    </tr>
  </tbody>
</table>

<hr>

<h2>🏛️ Arquitectura</h2>

<p>El proyecto sigue una separación estricta de responsabilidades bajo el patrón <strong>MVVM</strong> en el ecosistema y una arquitectura por capas en el backend:</p>

<pre>
┌─────────────────────────────────────────────────────────────┐
│                      HTTP  ·  /api/v1                        │
└───────────────────────────────┬─────────────────────────────┘
                                │
        ┌───────────────────────▼───────────────────────┐
        │              Form Requests                      │
        │   Validación + normalización de entrada         │
        └───────────────────────┬─────────────────────────┘
                                │
        ┌───────────────────────▼───────────────────────┐
        │              Controllers (thin)                 │
        │   Orquestan; no contienen reglas de negocio     │
        └───────────────────────┬─────────────────────────┘
                                │
        ┌───────────────────────▼───────────────────────┐
        │              Services + Policies                │
        │   Reglas clínicas · autorización · transacciones│
        └───────────────────────┬─────────────────────────┘
                                │
        ┌───────────────────────▼───────────────────────┐
        │              Eloquent Models                    │
        │   SoftDeletes · casts · relaciones · auditoría  │
        └───────────────────────┬─────────────────────────┘
                                │
        ┌───────────────────────▼───────────────────────┐
        │              API Resources (JSON)               │
        │   Estructura estandarizada de salida            │
        └─────────────────────────────────────────────────┘
</pre>

<h3>📦 Contrato de respuesta</h3>

<p>Todas las respuestas mantienen una estructura estandarizada:</p>

<table>
  <thead>
    <tr><th align="left">Llave</th><th align="left">Descripción</th></tr>
  </thead>
  <tbody>
    <tr><td><code>data</code></td><td>Recurso o colección solicitada (o <code>null</code>)</td></tr>
    <tr><td><code>message</code></td><td>Mensaje legible de resultado</td></tr>
    <tr><td><code>errors</code></td><td>Detalle de validación (solo en HTTP 422)</td></tr>
  </tbody>
</table>

<details>
<summary><strong>Ver ejemplo de respuesta</strong></summary>

```json
{
  "data": {
    "id": 1,
    "record_number": "VT-2026-014",
    "administrative_status": "ACTIVE",
    "admission_date": "2026-07-14"
  },
  "message": "Patient registered successfully.",
  "errors": null
}
```
</details>

<hr>

<h2>🗄️ Modelo de datos</h2>

<p>La base de datos consta de <strong>29 tablas funcionales</strong> normalizadas, organizadas por dominio. Todas las tablas modificables incluyen columnas de auditoría; las de bitácora son inmutables.</p>

<table>
  <thead>
    <tr><th align="left">Dominio</th><th align="left">Tablas</th></tr>
  </thead>
  <tbody>
    <tr>
      <td><strong>🔑 Acceso y control</strong></td>
      <td><code>roles</code> · <code>permissions</code> · <code>role_permission</code> · <code>users</code> · <code>user_role</code></td>
    </tr>
    <tr>
      <td><strong>👤 Personas y perfiles</strong></td>
      <td><code>people</code> · <code>patients</code> · <code>relatives</code> · <code>patient_relative</code> · <code>administrative_staff</code> · <code>health_staff</code> · <code>specialties</code></td>
    </tr>
    <tr>
      <td><strong>🩺 Clínico</strong></td>
      <td><code>professional_assignments</code> · <code>diagnoses</code> · <code>clinical_evolutions</code> · <code>treatments</code> · <code>medications</code> · <code>treatment_medication</code></td>
    </tr>
    <tr>
      <td><strong>📈 Mediciones y alertas</strong></td>
      <td><code>measurement_types</code> · <code>measurements</code> · <code>clinical_ranges</code> · <code>alerts</code> · <code>alert_history</code> 🔒</td>
    </tr>
    <tr>
      <td><strong>🔔 Operación</strong></td>
      <td><code>appointments</code> · <code>account_activations</code> · <code>correction_requests</code> · <code>notifications</code></td>
    </tr>
    <tr>
      <td><strong>📝 Bitácoras</strong></td>
      <td><code>integration_logs</code> 🔒 · <code>audit_logs</code> 🔒</td>
    </tr>
  </tbody>
</table>

<p><sub>🔒 = tabla inmutable (append-only, solo <code>created_at</code>, sin update ni delete).</sub></p>

<blockquote>
<strong>Regla de diseño:</strong> la edad <strong>nunca</strong> se almacena como columna; se calcula siempre a partir de <code>date_of_birth</code>.
</blockquote>

<hr>

<h2>👥 Roles y permisos</h2>

<p>El sistema define <strong>seis roles</strong>. La autorización se valida en el backend mediante <em>Gates</em> por rol y verificación de relación; el frontend nunca es la autoridad final.</p>

<table>
  <thead>
    <tr>
      <th align="left">Rol</th>
      <th align="left">Puede</th>
      <th align="left">No puede</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td><code>PATIENT</code></td>
      <td>Registrar sus mediciones, autorizar familiares, solicitar correcciones</td>
      <td>Acceder a datos de otros pacientes</td>
    </tr>
    <tr>
      <td><code>RELATIVE</code></td>
      <td>Consultar pacientes con autorización activa</td>
      <td>Acceder sin autorización vigente</td>
    </tr>
    <tr>
      <td><code>DOCTOR</code></td>
      <td>Diagnósticos, tratamientos, evoluciones, cerrar alertas</td>
      <td>Registrar pacientes o datos administrativos</td>
    </tr>
    <tr>
      <td><code>NURSE</code></td>
      <td>Tareas clínicas, clasificar y escalar alertas</td>
      <td>Cerrar alertas · datos administrativos</td>
    </tr>
    <tr>
      <td><code>ADMISSION</code></td>
      <td>Registrar pacientes, familiares, cuentas y asignaciones</td>
      <td>Diagnosticar o prescribir</td>
    </tr>
    <tr>
      <td><code>SYSTEM_ADMIN</code></td>
      <td>Usuarios, roles, catálogos, auditoría, configuración</td>
      <td>Obtener acceso clínico automático</td>
    </tr>
  </tbody>
</table>

<hr>

<h2>🔐 Autenticación</h2>

<p>Autenticación <strong>SPA basada en cookies</strong> con Laravel Sanctum. El flujo de acceso sigue tres pasos:</p>

<table>
  <tr>
    <td align="center"><strong>1</strong></td>
    <td><code>GET /sanctum/csrf-cookie</code></td>
    <td>Obtiene la cookie CSRF (XSRF-TOKEN)</td>
  </tr>
  <tr>
    <td align="center"><strong>2</strong></td>
    <td><code>POST /api/v1/auth/login</code></td>
    <td>Autentica y crea la sesión por cookie</td>
  </tr>
  <tr>
    <td align="center"><strong>3</strong></td>
    <td><code>GET /api/v1/auth/me</code></td>
    <td>Devuelve el usuario autenticado</td>
  </tr>
</table>

<h3>🎫 Activación de cuenta (RN-10)</h3>

<p>El acceso se habilita mediante un <strong>código de seis dígitos</strong> enviado por correo:</p>

<ul>
  <li>✅ Un solo uso · vigencia inicial de 24 horas</li>
  <li>✅ Almacenado con hash seguro (el código plano solo viaja en el correo)</li>
  <li>✅ Máximo 5 intentos antes de invalidación</li>
  <li>✅ Se invalidan los códigos pendientes al reenviar</li>
  <li>🚫 Prohibida la entrega manual de PINs</li>
</ul>

<hr>

<h2>🛣️ Endpoints</h2>

<p>Todos los recursos se exponen bajo <code>/api/v1</code> mediante <code>Route::apiResource()</code>, protegidos por middleware de rol.</p>

<details open>
<summary><strong>🔓 Autenticación (público, rate-limited)</strong></summary>

| Método | Ruta | Descripción |
|--------|------|-------------|
| `POST` | `/auth/login` | Iniciar sesión (Doctor / Admission) |
| `POST` | `/auth/activate-account` | Activar cuenta con código |
| `POST` | `/auth/resend-code` | Reenviar código de activación |
| `GET`  | `/auth/me` | Usuario autenticado |
| `POST` | `/auth/logout` | Cerrar sesión |
</details>

<details>
<summary><strong>🩺 Área clínica (DOCTOR / NURSE)</strong></summary>

| Recurso | Ruta |
|---------|------|
| Diagnósticos | `/diagnoses` |
| Evoluciones clínicas | `/clinical-evolutions` |
| Tratamientos | `/treatments` |
| Medicación de tratamiento | `/treatment-medications` |
| Mediciones | `/measurements` |
| Rangos clínicos | `/clinical-ranges` |
| Citas | `/appointments` |
| Alertas | `/alerts` |
| Historial de alertas | `/alert-history` |

**Acciones de flujo de alertas:**

| Método | Ruta | Rol |
|--------|------|-----|
| `POST` | `/alerts/{alert}/classify` | Doctor / Nurse |
| `POST` | `/alerts/{alert}/escalate` | Doctor / Nurse |
| `POST` | `/alerts/{alert}/close` | Doctor |
</details>

<details>
<summary><strong>📋 Admisión (ADMISSION)</strong></summary>

| Recurso | Ruta |
|---------|------|
| Pacientes | `/patients` |
| Familiares | `/relatives` |
| Relación paciente-familiar | `/patient-relatives` |
| Asignaciones profesionales | `/professional-assignments` |
| Solicitudes de corrección | `/correction-requests` |
</details>

<details>
<summary><strong>⚙️ Administración (SYSTEM_ADMIN)</strong></summary>

| Recurso | Ruta |
|---------|------|
| Usuarios | `/users` |
| Roles | `/roles` |
| Permisos | `/permissions` |
| Personal administrativo | `/administrative-staff` |
| Personal de salud | `/health-staff` |
| Especialidades | `/specialties` |
| Medicamentos | `/medications` |
| Tipos de medición | `/measurement-types` |
| Notificaciones | `/notifications` |
| Logs de integración | `/integration-logs` |
| Logs de auditoría | `/audit-logs` |
</details>

<hr>

<h2>🚀 Instalación</h2>

<h3>Requisitos</h3>

<ul>
  <li>PHP 8.2+</li>
  <li>Composer 2.x</li>
  <li>MySQL 8 / MariaDB</li>
</ul>

<h3>Pasos</h3>

```bash
# 1. Clonar el repositorio
git clone https://github.com/daamaleman/api-vitaltrace.git
cd api-vitaltrace
git checkout develop

# 2. Instalar dependencias
composer install

# 3. Configurar el entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar la base de datos en .env
#    DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 5. Migrar y sembrar datos base
php artisan migrate --seed

# 6. Levantar el servidor de desarrollo
php artisan serve
```

<blockquote>
La API quedará disponible en <code>http://localhost:8000/api/v1</code>.
</blockquote>

<hr>

<h2>📐 Convenciones de código</h2>

<table>
  <thead>
    <tr><th align="left">Aspecto</th><th align="left">Convención</th></tr>
  </thead>
  <tbody>
    <tr><td>Idioma del código</td><td>Inglés (modelos, columnas, variables, commits)</td></tr>
    <tr><td>Estilo</td><td>PSR-12 · <code>declare(strict_types=1)</code></td></tr>
    <tr><td>Generación de entidades</td><td><code>php artisan make:model X -mcr --api</code></td></tr>
    <tr><td>Borrado</td><td><code>SoftDeletes</code> en datos clínicos</td></tr>
    <tr><td>Validación</td><td>Form Requests dedicados (Store / Update)</td></tr>
    <tr><td>Salida</td><td>API Resources · fechas formateadas explícitamente</td></tr>
    <tr><td>Rutas</td><td>Solo <code>Route::apiResource()</code> bajo <code>/v1</code></td></tr>
    <tr><td>Commits</td><td>Conventional Commits en inglés</td></tr>
  </tbody>
</table>

<h3>Convención de commits</h3>

```
feat(database): create patients table migration with audit columns
feat(model):    add Patient model with soft deletes and relations
feat(api):      implement Patient CRUD endpoints
fix(database):  restrict non-nullable audit foreign keys
```

<table>
  <tr>
    <td><code>database</code></td><td>migraciones, seeders</td>
    <td><code>model</code></td><td>modelos Eloquent</td>
  </tr>
  <tr>
    <td><code>validation</code></td><td>Form Requests</td>
    <td><code>resource</code></td><td>API Resources</td>
  </tr>
  <tr>
    <td><code>api</code></td><td>controladores</td>
    <td><code>routes</code></td><td>rutas</td>
  </tr>
  <tr>
    <td><code>auth</code></td><td>Sanctum, permisos</td>
    <td><code>fix</code></td><td>correcciones</td>
  </tr>
</table>

<hr>

<h2>☁️ Despliegue</h2>

<p>La API se despliega en <strong>Namecheap</strong> (hosting compartido con cPanel + LiteSpeed) sobre el subdominio <code>api.vitaltrace.lat</code>, con el document root apuntando a <code>public/</code>.</p>

<table>
  <thead>
    <tr><th align="left">Componente</th><th align="left">Configuración</th></tr>
  </thead>
  <tbody>
    <tr><td>Backend</td><td><code>api.vitaltrace.lat</code> → Laravel</td></tr>
    <tr><td>Frontend</td><td><code>app.vitaltrace.lat</code> → Vue SPA</td></tr>
    <tr><td>Sesión</td><td>Cookie compartida vía <code>SESSION_DOMAIN=.vitaltrace.lat</code></td></tr>
    <tr><td>Tareas programadas</td><td>Cron → <code>php artisan schedule:run</code></td></tr>
    <tr><td>Cola</td><td>Cron → <code>php artisan queue:work --stop-when-empty</code></td></tr>
    <tr><td>HTTPS</td><td>Obligatorio · cookies <code>Secure</code></td></tr>
  </tbody>
</table>

<details>
<summary><strong>Checklist de producción</strong></summary>

- [x] `APP_DEBUG=false`
- [x] `.env` fuera del webroot y no versionado
- [x] Solo `public/` accesible por URL
- [x] HTTPS forzado con cookies `Secure`
- [x] Usuario de BD con privilegios acotados
- [x] `config:cache` y `route:cache` activos
</details>

<hr>

<h2>📖 Reglas de negocio</h2>

<table>
  <thead>
    <tr><th align="left">ID</th><th align="left">Regla</th></tr>
  </thead>
  <tbody>
    <tr><td><code>RN-01</code></td><td>Solo Admisión registra pacientes y datos administrativos</td></tr>
    <tr><td><code>RN-02</code></td><td>El familiar accede solo con autorización activa del paciente</td></tr>
    <tr><td><code>RN-03</code></td><td>Máximo dos familiares activos por paciente</td></tr>
    <tr><td><code>RN-06</code></td><td>Médicos/enfermeros acceden solo a pacientes con asignación vigente</td></tr>
    <tr><td><code>RN-07</code></td><td>Solo el médico registra diagnósticos y prescribe tratamientos</td></tr>
    <tr><td><code>RN-09</code></td><td>El estado clínico se conserva como historial, no se sobrescribe</td></tr>
    <tr><td><code>RN-10</code></td><td>Activación por código: hash, un solo uso, 24h, máx. 5 intentos</td></tr>
    <tr><td><code>RN-11</code></td><td>Una alerta es un seguimiento, no un diagnóstico</td></tr>
    <tr><td><code>RN-15</code></td><td>Trazabilidad: registrador original y valores anterior/nuevo auditados</td></tr>
  </tbody>
</table>

<blockquote>
Regla operativa clave: existe <strong>un único médico principal activo por paciente</strong> y <strong>máximo dos familiares activos</strong>, validados de forma transaccional.
</blockquote>

<hr>

<h2>👨‍💻 Equipo</h2>

<div align="center">

<strong>QuantumMinds</strong> · Categoría Avanzado<br>
<sub>Hackathon Nicaragua 2026 · Prototipo académico con datos ficticios</sub>

</div>

<hr>

<div align="center">
  <sub>Construido con Laravel · Documentado con cuidado · VitalTrace registra información para seguimiento y no sustituye la valoración de un profesional de la salud.</sub>
</div>