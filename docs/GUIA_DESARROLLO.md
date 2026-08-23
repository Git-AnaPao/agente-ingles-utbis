# GUIA DE DESARROLLO - Agente Ingles UTBIS

> **Fecha de actualizacion:** 18 de Agosto, 2026
> **Para cualquier IA o desarrollador que retome este proyecto.**
> Leer primero: `CONTEXTO_PROYECTO.md`

---

## 1. STACK TECNOLOGICO

| Capa | Tecnologia | Version |
|------|-----------|---------|
| Backend | Laravel | 12.x |
| PHP | PHP | 8.2+ |
| Frontend | Blade + Tailwind CSS | 3.x |
| Interactividad | Alpine.js | 3.4.2 |
| Build | Vite | 7.x |
| Auth Web | Laravel Breeze (Sesiones) | 2.4 |
| Auth API | JWT (tymon/jwt-auth) | 2.3 |
| OAuth | Laravel Socialite (Google) | 5.28 |
| IA | Google Gemini 2.0 Flash | API REST |
| BD | MySQL / SQLite (dev) | - |

---

## 2. ESTRUCTURA DEL PROYECTO (Estado Actual)

```
agente-ingles-utbis/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/                          # 10 controllers - NO MODIFICAR
│   │   │   │   ├── AuthenticatedSessionController.php
│   │   │   │   ├── ConfirmablePasswordController.php
│   │   │   │   ├── EmailVerificationNotificationController.php
│   │   │   │   ├── EmailVerificationPromptController.php
│   │   │   │   ├── GoogleController.php       # Google OAuth
│   │   │   │   ├── NewPasswordController.php
│   │   │   │   ├── PasswordController.php
│   │   │   │   ├── PasswordResetLinkController.php
│   │   │   │   ├── RegisteredUserController.php
│   │   │   │   └── VerifyEmailController.php
│   │   │   ├── Api/
│   │   │   │   ├── AuthController.php          # JWT: login/me/logout/refresh
│   │   │   │   ├── ExamController.php          # Evaluacion con IA
│   │   │   │   └── ProgressController.php      # Progreso y estadisticas
│   │   │   ├── AdminController.php             # CRUD usuarios, dashboard admin
│   │   │   ├── LevelController.php             # Mapa de niveles, completar leccion
│   │   │   ├── ListeningController.php         # Listening practice con Drive
│   │   │   ├── PlacementController.php         # Test de ubicacion (75 preguntas)
│   │   │   ├── ProfessorController.php         # Dashboard profesor, progreso
│   │   │   └── ProfileController.php           # Editar perfil
│   │   ├── Middleware/
│   │   │   └── CheckRole.php                   # Middleware de roles
│   │   └── Requests/
│   │       ├── LoginRequest.php
│   │       └── ProfileUpdateRequest.php
│   ├── Models/                                 # 12 modelos (todos con UUID)
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Lesson.php
│   │   ├── ListeningLesson.php                 # Lecciones de listening (Drive)
│   │   ├── Questionnaire.php
│   │   ├── Question.php
│   │   ├── QuestionOption.php
│   │   ├── Resource.php
│   │   ├── PlacementTest.php
│   │   ├── StudentProgress.php
│   │   ├── AttemptLog.php
│   │   └── StudentResponse.php
│   ├── Services/
│   │   ├── GeminiService.php                   # Integracion con Google Gemini
│   │   ├── GoogleDriveService.php              # Conexion con Google Drive API
│   │   └── ExcelReaderService.php              # Lectura de archivos Excel
│   ├── Console/
│   │   └── Commands/
│   │       ├── ImportListeningFromDrive.php    # Importar datos desde Drive
│   │       └── ImportListeningQuestions.php    # Migrar preguntas a questions/question_options
│   ├── View/Components/
│   │   ├── AppLayout.php
│   │   └── GuestLayout.php
│   └── Providers/
│       └── AppServiceProvider.php
├── config/
│   ├── app.php
│   ├── auth.php                                # Guard: web (session) + api (jwt)
│   ├── jwt.php                                 # TTL: 60min, Refresh: 2 semanas
│   ├── services.php                            # Google OAuth + Gemini API key
│   └── ... (demas configs de Laravel)
├── database/
│   ├── ingles_db.sql                           # Schema completo + seed data
│   ├── factories/
│   │   └── UserFactory.php
│   ├── migrations/                             # 12 migraciones
│   └── seeders/
│       ├── DatabaseSeeder.php
│       ├── RoleSeeder.php
│       └── LessonSeeder.php
├── resources/
│   ├── css/app.css
│   ├── js/app.js + bootstrap.js
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php                   # Layout principal autenticado
│       │   ├── guest.blade.php                 # Layout para auth
│       │   └── navigation.blade.php            # Navbar completa
│       ├── auth/                               # 6 vistas de autenticacion
│       ├── admin/                              # 3 vistas (dashboard, users, user-form)
│       ├── professor/                          # 2 vistas (dashboard, student-progress)
│       ├── levels/                             # 2 vistas (index, learn)
│       ├── listening/                          # 2 vistas (index, show)
│       │   ├── index.blade.php                 # Lista de lecciones por nivel
│       │   └── show.blade.php                  # Leccion con audio y preguntas
│       ├── placement/                          # 1 vista (index - 75 preguntas)
│       ├── profile/                            # 4 vistas (edit + 3 partials)
│       ├── components/                         # 13 componentes Blade reutilizables
│       ├── dashboard.blade.php                 # Dashboard gamificado del estudiante
│       └── welcome.blade.php                   # Landing page publica
├── routes/
│   ├── web.php                                 # 20+ rutas web
│   ├── api.php                                 # 11 rutas API (JWT)
│   ├── auth.php                                # Rutas de autenticacion Breeze
│   └── console.php
├── tests/
│   ├── Feature/
│   │   ├── Auth/                               # 6 tests de autenticacion
│   │   │   ├── AuthenticationTest.php
│   │   │   ├── EmailVerificationTest.php
│   │   │   ├── PasswordConfirmationTest.php
│   │   │   ├── PasswordResetTest.php
│   │   │   ├── PasswordUpdateTest.php
│   │   │   └── RegistrationTest.php
│   │   ├── ExampleTest.php
│   │   └── ProfileTest.php
│   └── Unit/
│       └── ExampleTest.php
├── .env.example
├── composer.json
├── package.json
├── vite.config.js
├── tailwind.config.js
└── docs/
    ├── CONTEXTO_PROYECTO.md
    └── GUIA_DESARROLLO.md                      # Este archivo
```

---

## 3. AUTENTICACION

### Sistema Doble: Sesiones (Web) + JWT (API)

```
Web (Sesiones - Breeze):
  Login → Session guard → Middleware auth → Dashboard
  Register → Solo emails @utbispuebla.edu.mx → Rol: student
  Google OAuth → Solo dominio utbispuebla.edu.mx → Rol: student

API (JWT - tymon/jwt-auth):
  POST /api/auth/login → Token JWT (60min TTL, 2 semanas refresh)
  GET /api/auth/me → Usuario autenticado
  POST /api/auth/logout → Invalida token
  POST /api/auth/refresh → Renueva token
```

### Middleware de Roles (`CheckRole`)
- Registrado en `bootstrap/app.php`
- Uso: `middleware('role:admin')`, `middleware('role:professor,admin')`
- Verifica si el usuario autenticado tiene el rol requerido

### Dashboard por Rol
- **Student** → Sin placement test: redirige a placement. Con placement: dashboard gamificado
- **Professor** → Lista de estudiantes con progreso
- **Admin** → Estadisticas + CRUD de usuarios

---

## 4. BASE DE DATOS

### Modelo Entidad-Relacion

```
users (UUID PK)
  ├── user_roles (pivot) ── roles (UUID PK)
  ├── placement_tests (UUID PK)
  ├── student_progress (UUID PK) ── lessons
  ├── attempt_logs (UUID PK) ── lessons
  │     └── student_responses (UUID PK) ── questions
  └── (auth: sessions, password_reset_tokens)

lessons (UUID PK)
  ├── questionnaires (UUID PK)
  │     ├── questions (UUID PK)
  │     │     └── question_options (UUID PK)
  │     └── resources (UUID PK)
  ├── student_progress
  └── attempt_logs
```

### Lecciones Existentes (13)

| Nivel | Sub | Tema |
|-------|-----|------|
| A1 | 1 | Greetings |
| A1 | 2 | The Alphabet & Verb To Be |
| A1 | 3 | Numbers & Introductions |
| A2 | 1 | Present Simple & Daily Routine |
| A2 | 2 | Family Members |
| A2 | 3 | Listening Practice *(creada por el import)* |
| B1 | 1 | Future Tense & Travel |
| B1 | 2 | Comparatives & Hotel |
| B2 | 1 | Passive Voice & News |
| B2 | 2 | Phrasal Verbs & Debate |
| C1 | 1 | Idioms & Conversation |
| C1 | 2 | Nuanced Grammar & Exam Prep |
| C2 | 1 | Advanced Listening & Speaking |

### Usuarios de Prueba

| Email | Password | Rol |
|-------|----------|-----|
| admin@utbis.edu | password | admin |
| profesor@utbis.edu | password | professor |
| test@example.com | password | student |

---

## 5. RUTAS

### Web Routes

| Metodo | Ruta | Nombre | Middleware |
|--------|------|--------|-----------|
| GET | / | home | guest |
| GET | /dashboard | dashboard | auth, verified |
| GET | /levels | levels.index | auth |
| POST | /lessons/{lesson}/complete | lessons.complete | auth |
| GET | /lessons/{lesson}/learn | lessons.learn | auth |
| POST | /lessons/{lesson}/check-practice | lessons.check-practice | auth |
| GET | /placement | placement.index | auth, verified |
| POST | /placement | placement.submit | auth, verified |
| GET | /placement/skip | placement.skip | auth, verified |
| GET | /listening | listening.index | auth |
| GET | /listening/{listeningLesson} | listening.show | auth |
| POST | /listening/{listeningLesson}/check | listening.check | auth |
| GET | /chat | chat.index | auth |
| POST | /chat/send | chat.send | auth |
| GET | /professor/dashboard | professor.dashboard | auth, role:professor,admin |
| GET | /professor/students/{user}/progress | professor.student-progress | auth, role:professor,admin |
| GET | /admin/dashboard | admin.dashboard | auth, role:admin |
| GET | /admin/users | admin.users | auth, role:admin |
| GET/POST | /admin/users/create | admin.users.create/store | auth, role:admin |
| GET/PATCH | /admin/users/{user}/edit | admin.users.edit/update | auth, role:admin |
| DELETE | /admin/users/{user} | admin.users.delete | auth, role:admin |
| GET/PATCH/DELETE | /profile | profile.* | auth |

### API Routes (JWT)

| Metodo | Ruta | Descripcion |
|--------|------|-------------|
| POST | /api/auth/login | Login JWT |
| GET | /api/auth/me | Usuario autenticado |
| POST | /api/auth/logout | Cerrar sesion JWT |
| POST | /api/auth/refresh | Refrescar token |
| GET | /api/progress/levels | Niveles + progreso |
| POST | /api/progress/lessons/{lesson}/complete | Completar sub-nivel |
| POST | /api/progress/lessons/{lesson}/attempt | Crear intento |
| POST | /api/progress/attempts/batch | Crear multiples intentos |
| GET | /api/progress/stats | Estadisticas totales |
| POST | /api/exam/submit | Enviar examen con IA |
| GET | /api/exam/{attempt}/result | Ver resultado |

---

## 6. INTEGRACION CON GEMINI

### Ubicacion: `app/Services/GeminiService.php`

### Servicios Disponibles

1. **evaluateSpeakingAudio()** - Evalua audio del estudiante
   - Envia audio en base64 + texto de la pregunta + respuesta esperada
   - Retorna: `{transcription, is_correct, feedback}`
   - Modelo: gemini-2.0-flash | Timeout: 60 segundos

2. **generateGeneralFeedback()** - Feedback general del intento
   - Recibe: score, total, correct, errores
   - Retorna: parrafo amigable de retroalimentacion

### Flujo de Evaluacion

```
1. Estudiante envia respuestas + audio (opcional)
2. multiple_choice → Se califica por matching de option_id
3. speaking → Se envia a Gemini para evaluar
4. Se calcula score (>=90% para aprobar)
5. Se genera feedback general con Gemini
6. Se guarda AttemptLog + StudentResponses
```

---

## 7. CONVENCIONES DE CODIGO

### IDs
Todos los modelos usan UUIDs (trait `HasUuids`).

### Nomenclatura
- **BD:** snake_case (`user_email`, `lesson_cefr_level`)
- **PHP:** camelCase en propiedades, PascalCase en clases
- **Clases:** PascalCase (`PlacementController`)
- **Metodos:** camelCase (`studentProgress`)

### Modelos - Siempre definir:
```php
protected $fillable = [...];
protected $hidden = [...];
protected function casts(): array { ... }

// Relaciones con tipos
public function roles(): BelongsToMany { ... }

// Scopes para consultas comunes
public function scopeByLevel($query, string $level) {
    return $query->where('lesson_cefr_level', $level);
}
```

### Controllers - Retorno explicito:
```php
public function index(): View
{
    // ...
    return view('levels.index', compact('levels'));
}

public function store(Request $request): RedirectResponse
{
    // ...
    return redirect()->route('admin.users');
}
```

### Vistas Blade
```blade
{{-- Usar componentes existentes --}}
<x-primary-button>{{ __('Texto') }}</x-primary-button>

{{-- Traducciones con __() --}}
<h1>{{ __('Dashboard') }}</h1>

{{-- Alpine.js para interactividad --}}
<div x-data="{ open: false }">
    <button @click="open = !open">Toggle</button>
    <div x-show="open" x-transition>Contenido</div>
</div>
```

### Rutas
```php
// Web: Prefijo por rol
Route::prefix('admin')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('admin.dashboard');
});

// API: Siempre bajo /api con JWT
Route::middleware('auth:api')->group(function () {
    Route::get('/progress/levels', [ProgressController::class, 'levels']);
});
```

### Tests
```php
// Siempre testear:
// 1. Happy path (funciona correctamente)
// 2. Auth (no autenticado → redirect/401)
// 3. Authorization (rol incorrecto → 403)
// 4. Validacion (datos invalidos → 422)

public function test_student_can_access_levels()
{
    $student = User::factory()->create();
    $student->roles()->attach(Role::where('role_name', 'student')->first());

    $this->actingAs($student)
         ->get('/levels')
         ->assertOk();
}
```

---

## 8. COMANDOS UTILES

```bash
# Instalacion y setup
composer install                           # Dependencias PHP
npm install                                # Dependencias JS
cp .env.example .env                       # Copiar entorno
php artisan key:generate                    # Generar APP_KEY
php artisan migrate --seed                 # Migrar + poblar BD

# Desarrollo
php artisan serve                          # Servidor local (:8000)
npm run dev                                # Vite en desarrollo (hot reload)
php artisan migrate:fresh --seed           # Resetear BD completa
php artisan tinker                         # Consola interactiva

# Production
npm run build                              # Compilar assets
php artisan config:cache                   # Cachear configuracion
php artisan route:cache                    # Cachear rutas

# Testing
php artisan test                           # Todos los tests
php artisan test --filter=Auth             # Tests de un grupo
php artisan test tests/Feature/Auth        # Tests de un archivo

# Limpieza
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Debug
php artisan route:list                     # Ver todas las rutas
php artisan route:list --path=admin        # Filtrar por path
php artisan migrate:status                 # Estado de migraciones
```

---

## 9. ARCHIVOS CLAVE PARA ENTENDER EL PROYECTO

| Archivo | Que contiene |
|---------|-------------|
| `docs/CONTEXTO_PROYECTO.md` | Contexto completo del proyecto |
| `database/ingles_db.sql` | Schema de BD + datos de prueba |
| `app/Models/User.php` | Modelo central con auth, roles, JWT |
| `app/Http/Controllers/Api/ExamController.php` | Logica de evaluacion con IA |
| `app/Http/Controllers/Api/ProgressController.php` | Progreso y estadisticas |
| `app/Services/GeminiService.php` | Integracion con Google Gemini |
| `app/Http/Middleware/CheckRole.php` | Control de acceso por roles |
| `routes/web.php` | Todas las rutas web |
| `routes/api.php` | Todas las rutas API |
| `config/jwt.php` | Configuracion JWT |
| `config/services.php` | API keys (Google, Gemini) |
| `resources/views/layouts/navigation.blade.php` | Navbar completa |

---

## 10. FEATURES IMPLEMENTADOS

- [x] Autenticacion por sesiones (Breeze)
- [x] Autenticacion JWT para API
- [x] Google OAuth restringido a @utbispuebla.edu.mx
- [x] Registro restringido al mismo dominio
- [x] 3 roles: admin, professor, student
- [x] Middleware de roles (CheckRole)
- [x] Test de ubicacion (75 preguntas, A1-C1)
- [x] 12 lecciones CEFR (A1-C2)
- [x] Dashboard gamificado con XP y barras de progreso
- [x] Mapa visual de niveles (estilo juego)
- [x] Evaluacion de speaking con Gemini AI
- [x] Feedback general con Gemini AI
- [x] CRUD de usuarios (admin)
- [x] Vista de progreso para profesores
- [x] Dark mode + grayscale mode
- [x] Landing page con owl mascot
- [x] Responsive design (Tailwind)
- [x] 13 componentes Blade reutilizables
- [x] Tests de autenticacion (6 tests Feature)
- [x] Listening Practice con 99 lecciones (A1-A2, 3114 preguntas)
- [x] Listening integrado en dashboard y mapa de niveles
- [x] Motor de preguntas unificado: 3114 preguntas en `questions`/`question_options` (99 cuestionarios, 180 recursos)
- [x] Comando `import:listening-questions` (idempotente, `--force`, `--dry-run`)
- [x] Practica interactiva en `lessons.learn` (MC + fill_blank, 1 pregunta a la vez, auto-completado al 70%+)
- [x] `listening.check` guarda intentos + respuestas + progreso (70%+ auto-completa la leccion vinculada)
- [x] Gamificacion real: rachas de dias consecutivos y actividades semanales (`GamificationService`)
- [x] Constraint de progreso corregida a `unique(student_id, lesson_id)`
- [x] Chat IA funcional (`/chat`, tutor Gemini, degrada sin API key)
- [x] Feedback de tutor IA por intento (`attempt_logs.ai_feedback`)
- [x] TTS navegador (speechSynthesis) + `generate:listening-audio` (Google Cloud TTS)

---

## 11. FEATURES PENDIENTES

### Alta Prioridad
- [ ] Contenido real de lecciones (lesson_prompt_payload JSON)
- [ ] Vista de ejercicios en web usando los cuestionarios importados
- [ ] Recursos multimedia reales (los audios se generan con `generate:listening-audio` o se suben a Drive; faltan IDs reales)
- [ ] Tests automatizados (solo hay tests de autenticacion basica)
- [ ] Migrar placement test a base de datos (actualmente hardcodeado)

### Media Prioridad
- [ ] Leaderboards e insignias
- [ ] XP por actividad ponderada (hoy XP = conteo de lecciones completadas)
- [ ] Dashboard mejorado con graficos (Chart.js)
- [ ] Paginacion y busqueda en vistas de admin
- [ ] Exportar reportes de progreso

### Baja Prioridad
- [ ] App movil (la API JWT esta lista para conectarse)
- [ ] Sistema de notificaciones
- [ ] Sistema de tareas/actividades asignadas por profesor
- [ ] Documentacion OpenAPI/Swagger

---

## 12. INTEGRACION CON GOOGLE DRIVE API

### Archivos Implementados

| Archivo | Descripcion |
|---------|-------------|
| `app/Services/GoogleDriveService.php` | Servicio para conectar con Google Drive API |
| `app/Services/ExcelReaderService.php` | Servicio para leer archivos Excel |
| `app/Models/ListeningLesson.php` | Modelo para lecciones de listening |
| `app/Console/Commands/ImportListeningFromDrive.php` | Comando artisan para importar datos |
| `app/Console/Commands/ImportListeningQuestions.php` | Comando para migrar preguntas a `questions`/`question_options` |
| `app/Http/Controllers/ListeningController.php` | Controlador de listening practice |
| `resources/views/listening/index.blade.php` | Vista de lista de lecciones |
| `resources/views/listening/show.blade.php` | Vista de leccion con audio y preguntas |
| `database/migrations/2026_08_18_000001_create_listening_lessons_table.php` | Migracion de tabla |

### Configuracion en Google Cloud Console

Para usar Google Drive API, necesitas configurar credenciales en [Google Cloud Console](https://console.cloud.google.com/):

#### Opcion 1: API Key (para archivos publicos)
1. Ir a Credenciales → Crear credencial → API Key
2. Restriccion: **IP addresses** (Web servers, cron jobs)
3. Agregar la IP del servidor donde corre Laravel

#### Opcion 2: Service Account (para archivos privados - RECOMENDADA)
1. Ir a Credenciales → Crear credencial → Service Account
2. Descargar el JSON de la service account
3. En Google Drive, **compartir la carpeta** con el email de la service account
4. Configurar en `.env`:
```env
GOOGLE_SERVICE_ACCOUNT_PATH=/ruta/al/archivo-service-account.json
```

### Variables de Entorno para Google Drive

```env
# Google Drive API (for listening lessons import)
GOOGLE_DRIVE_API_KEY=tu_api_key_aqui
GOOGLE_DRIVE_EXCEL_FILE_ID=id_del_archivo_excel_en_drive
GOOGLE_DRIVE_AUDIO_FOLDER_ID=id_de_la_carpeta_de_audios
GOOGLE_SERVICE_ACCOUNT_PATH=/ruta/al/archivo-service-account.json (opcional)
```

### Estructura Esperada en Google Drive

#### Excel de Lecciones
- **Una hoja por rango de lecciones** (ej: "A1 Lessons 1-17", "A2 Lessons 18-34")
- Cada fila = una pregunta (no una leccion completa)
- Columnas:
  - `Nivel` - Nivel CEFR (A1, A2, etc.)
  - `Leccion` - Numero de leccion (ej: "Leccion 1", "18")
  - `Titulo_Tema` - Titulo/tema de la leccion
  - `Habilidad` - Tipo de habilidad (Reading, Listening, etc.)
  - `Tipo_Pregunta` - multiple_choice, fill_blank, etc.
  - `Pregunta_Texto` - Texto de la pregunta
  - `Respuesta_Correcta` - Respuesta correcta
  - `Opciones` - Opciones separadas por pipe (ej: "Opcion A | Opcion B | Opcion C")
  - `Tipo_Recurso` - Texto, Audio, etc.
  - `URL_Recurso` - URL del recurso
  - `Transcripcion` - Transcripcion del audio/texto

#### Carpeta de Audios
```
Carpeta Raiz/
├── A1/
│   ├── audio1.mp3
│   ├── audio2.mp3
│   └── ...
├── A2/
│   ├── audio1.mp3
│   └── ...
└── ...
```

### Comandos para Importar

```bash
# Importar todo desde Google Drive
php artisan import:listening

# Solo un nivel / vista previa / IDs manuales
php artisan import:listening --level=A1
php artisan import:listening --dry-run
php artisan import:listening --excel-file-id=XXX --audio-folder-id=YYY

# Migrar preguntas de listening_lessons hacia questions/question_options
php artisan import:listening-questions

# Nivel especifico / vista previa / re-importar
php artisan import:listening-questions --level=A1 --dry-run
php artisan import:listening-questions --force

# Generar audios MP3 con Google Cloud TTS (requiere GOOGLE_SERVICE_ACCOUNT_PATH)
php artisan generate:listening-audio
php artisan generate:listening-audio --level=A1 --dry-run
php artisan generate:listening-audio --force
```

### Flujo de la Aplicacion

```
1. Admin ejecuta comando de importacion
2. Se descarga Excel desde Google Drive
3. Se leen datos por hoja (cada hoja = un nivel)
4. Se crean/actualizan registros en listening_lessons
5. Se vinculan audios por matching de nombre (o se generan con generate:listening-audio)
6. Admin ejecuta import:listening-questions (migra a questions/question_options)
7. Estudiante accede a /listening
8. Selecciona nivel (A1, A2, etc.)
9. Ve lista de lecciones disponibles
10. Selecciona una leccion
11. Escucha audio desde Google Drive (o usa el boton "Escuchar" TTS del navegador)
12. Responde preguntas
13. Sistema verifica respuestas y muestra score
```

---

## 13. NOTAS IMPORTANTES

1. **El placement test esta hardcodeado** en PlacementController con 75 preguntas. Futura migracion a BD.
2. **QuestionnaireOption fue eliminado** (apuntaba a tabla inexistente; era duplicado de QuestionOption).
3. **El "Chat IA"** aparece en la navegacion pero no tiene implementacion aun.
4. **Los resources (audio/imagenes)** estan importados con URLs del Excel; faltan archivos reales subidos y audios en Drive.
5. **La API JWT** esta lista para una app movil futura.
6. **El owl mascot** es un elemento visual recurrente en toda la app.
7. **Dominio restringido:** Solo emails terminados en @utbispuebla.edu.mx pueden registrarse.
8. **Laravel 12:** Usa `bootstrap/app.php` en vez de `Kernel.php` para configurar middleware.

---

## 14. VARIABLES DE ENTORNO NECESARIAS (.env)

```env
APP_NAME="Agente Ingles UTBIS"
APP_ENV=local
APP_KEY=...                                    # php artisan key:generate
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql                            # o sqlite para dev
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ingles_db
DB_USERNAME=root
DB_PASSWORD=

GOOGLE_CLIENT_ID=...                          # Google Cloud Console
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

GEMINI_API_KEY=...                            # Google AI Studio

JWT_SECRET=...                                # php artisan jwt:secret
JWT_TTL=60                                    # Minutos
JWT_REFRESH_TTL=20160                         # 2 semanas en minutos
```

---

## 15. FLUJOS DE USO PRINCIPALES

### Flujo del Estudiante
```
1. Registro/Login (@utbispuebla.edu.mx)
2. Dashboard → Si no tiene placement → Redirige a test
3. Placement Test (75 preguntas, A1-C1)
4. Dashboard gamificado con nivel asignado
5. Mapa de niveles → Selecciona leccion
6. Contenido de leccion → Evaluacion
7. Evaluacion con IA (speaking evaluado por Gemini)
8. Feedback + Actualizacion de progreso
```

### Flujo del Profesor
```
1. Login → Dashboard profesor
2. Lista de estudiantes con progreso
3. Click en estudiante → Progreso detallado
```

### Flujo del Admin
```
1. Login → Dashboard admin
2. Estadisticas generales
3. CRUD de usuarios
4. Gestion de roles
```
