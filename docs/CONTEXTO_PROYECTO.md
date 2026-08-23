# CONTEXTO COMPLETO DEL PROYECTO: Agente Ingles UTBIS

> **Fecha de creacion del documento:** 17 de Agosto, 2026
> **Proposito:** Documentacion completa para que cualquier IA o desarrollador entienda el proyecto desde cero.

---

## 1. QUE ES ESTE PROYECTO

Es una **aplicacion web para aprender ingles** dirigida a estudiantes de la **Universidad Tecnologica de Puebla (UTBIS)**. Utiliza **inteligencia artificial (Google Gemini)** para evaluar la speaking (hablado) de los estudiantes y dar retroalimentacion personalizada.

### Stack Tecnologico

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
| BD | MySQL | (basado en ingles_db.sql) |

---

## 2. BASE DE DATOS (16 tablas)

### Modelo Entidad-Relacion Simplificado

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

### Tablas Principales

#### `users` - Usuarios del sistema
| Campo | Tipo | Descripcion |
|-------|------|-------------|
| user_id | CHAR(36) PK | UUID |
| user_email | VARCHAR(255) UNIQUE | Email para login |
| google_id | VARCHAR(255) NULL | ID de Google OAuth |
| user_cel | VARCHAR(12) NULL | Telefono |
| user_password | VARCHAR(255) NULL | Hash bcrypt (nulo si solo Google) |
| user_name | VARCHAR(255) | Nombre |
| user_last_name | VARCHAR(255) | Apellido paterno |
| user_middle_name | VARCHAR(255) | Apellido materno |
| user_status | ENUM | 'active' / 'inactive' |
| email_verified_at | TIMESTAMP NULL | Verificacion de email |

#### `roles` - Roles del sistema
- **admin** - Administrador del sistema
- **professor** - Profesor / instructor
- **student** - Estudiante

#### `lessons` - Lecciones por nivel CEFR
| Campo | Tipo | Descripcion |
|-------|------|-------------|
| lesson_id | CHAR(36) PK | UUID |
| lesson_cefr_level | ENUM | 'A1','A2','B1','B2','C1','C2' |
| lesson_sub_level | INT | Numero de sub-nivel dentro del nivel |
| lesson_prompt_payload | JSON | Contenido/prompt de la leccion |

**Lecciones existentes (12):**
| Nivel | Sub | Tema |
|-------|-----|------|
| A1 | 1 | Greetings |
| A1 | 2 | The Alphabet & Verb To Be |
| A1 | 3 | Numbers & Introductions |
| A2 | 1 | Present Simple & Daily Routine |
| A2 | 2 | Family Members |
| B1 | 1 | Future Tense & Travel |
| B1 | 2 | Comparatives & Hotel |
| B2 | 1 | Passive Voice & News |
| B2 | 2 | Phrasal Verbs & Debate |
| C1 | 1 | Idioms & Conversation |
| C1 | 2 | Nuanced Grammar & Exam Prep |
| C2 | 1 | Advanced Listening & Speaking |

#### `placement_tests` - Examenes de ubicacion
- 75 preguntas que evaluan del A1 al C1
- Cada nivel tiene un threshold del 60% para aprobarlo
- El resultado determina el nivel CEFR del estudiante

#### `questionnaires` - Cuestionarios asociados a lecciones
- Cada leccion de listening tiene su propio cuestionario (campo `listening_lesson_id` para trazabilidad)
- 99 cuestionarios importados (3114 preguntas)
#### `questions` - Preguntas con tipos: multiple_choice, fill_blank, speaking, listening
- Importadas desde `listening_lessons.questions_data` via `import:listening-questions`
- 3114 preguntas: 1091 MC, 1940 fill_blank, 83 speaking
- `correct_answer` = texto exacto (null para speaking, evaluadas por IA)
#### `question_options` - Opciones para preguntas de opcion multiple
- 4364 opciones; toda pregunta MC tiene exactamente 1 opcion `is_correct = true`
#### `resources` - Recursos multimedia (audio, texto, imagen) por cuestionario
#### `student_progress` - Progreso del estudiante por nivel/habilidad
#### `attempt_logs` - Intentos de examen con calificacion y feedback de IA
#### `student_responses` - Respuestas individuales por pregunta

---

## 3. AUTENTICACION Y ROLES

### Flujo de Autenticacion

```
Web (Sesiones):
  Login → Session guard → Middleware auth → Dashboard
  Register → Solo emails @utbispuebla.edu.mx → Rol: student
  Google OAuth → Solo dominio utbispuebla.edu.mx → Rol: student

API (JWT):
  POST /api/auth/login → Token JWT (60min TTL, 2 semanas refresh)
  GET /api/auth/me → Usuario autenticado
  POST /api/auth/logout → Invalida token
```

### Middleware de Roles (`CheckRole`)
- Registrado como `role` en bootstrap/app.php
- Uso: `middleware('role:admin')`, `middleware('role:professor,admin')`
- Verifica si el usuario autenticado tiene el rol requerido

### Dashboard por Rol
- **Student** → Sin placement test: redirige a placement. Con placement: dashboard gamificado
- **Professor** → Lista de estudiantes con progreso
- **Admin** → Estadisticas + CRUD de usuarios

---

## 4. INTEGRACION CON GOOGLE GEMINI

### Ubicacion: `app/Services/GeminiService.php`

### Servicios Disponibles

1. **evaluateSpeakingAudio()** - Evalua audio del estudiante
   - Envia audio en base64 + texto de la pregunta + respuesta esperada
   - Retorna: `{transcription, is_correct, feedback}`
   - Modelo: gemini-2.0-flash
   - Timeout: 60 segundos

2. **generateGeneralFeedback()** - Feedback general del intento
   - Recibe: score, total, correct, errores
   - Retorna: parrafo amigable de retroalimentacion

### Flujo de Evaluacion (Api/ExamController)

```
1. Estudiante envia respuestas + audio (opcional)
2. multiple_choice → Se califica por matching de option_id
3. speaking → Se envia a Gemini para evaluar
4. Se calcula score (>=90% para aprobar)
5. Se genera feedback general con Gemini
6. Se guarda AttemptLog + StudentResponses
```

---

## 5. ESTRUCTURA DE ARCHIVOS ACTUAL

```
agente-ingles-utbis/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── AdminController.php          # CRUD usuarios, dashboard admin
│   │   │   ├── ChatController.php           # Chat IA (vista + endpoint Gemini)
│   │   │   ├── Controller.php               # Base controller (vacia)
│   │   │   ├── LevelController.php          # Mapa de niveles, leccion + practica (learn/checkPractice)
│   │   │   ├── PlacementController.php      # Test de ubicacion (75 preguntas)
│   │   │   ├── ProfessorController.php      # Dashboard profesor, progreso estudiante
│   │   │   ├── ProfileController.php        # Editar perfil
│   │   │   ├── Auth/                        # Controllers de autenticacion (8 archivos)
│   │   │   │   ├── AuthenticatedSessionController.php
│   │   │   │   ├── ConfirmablePasswordController.php
│   │   │   │   ├── EmailVerificationNotificationController.php
│   │   │   │   ├── EmailVerificationPromptController.php
│   │   │   │   ├── GoogleController.php     # Google OAuth
│   │   │   │   ├── NewPasswordController.php
│   │   │   │   ├── PasswordController.php
│   │   │   │   ├── PasswordResetLinkController.php
│   │   │   │   ├── RegisteredUserController.php
│   │   │   │   └── VerifyEmailController.php
│   │   │   └── Api/
│   │   │       ├── AuthController.php       # JWT login/me/logout/refresh
│   │   │       ├── ExamController.php       # Evaluacion con IA
│   │   │       └── ProgressController.php   # Progreso y estadisticas
│   │   ├── Middleware/
│   │   │   └── CheckRole.php               # Middleware de roles
│   │   └── Requests/
│   │       ├── LoginRequest.php
│   │       └── ProfileUpdateRequest.php
│   ├── Models/                              # 12 modelos (todos con UUID)
│   │   ├── User.php
│   │   ├── Role.php
│   │   ├── Lesson.php
│   │   ├── Questionnaire.php
│   │   ├── Question.php
│   │   ├── QuestionOption.php
│   │   ├── QuestionnaireOption.php          # (eliminado: tabla inexistente, codigo muerto)
│   │   ├── Resource.php
│   │   ├── PlacementTest.php
│   │   ├── StudentProgress.php
│   │   ├── AttemptLog.php
│   │   └── StudentResponse.php
│   ├── Services/
│   │   ├── GeminiService.php               # Integracion con Google Gemini
│   │   ├── GamificationService.php         # Rachas, actividades semanales, snapshot
│   │   └── TtsService.php                  # Google Cloud TTS (service account, JWT propio)
│   ├── Support/
│   │   └── AnswerNormalizer.php            # Normalizacion tolerante de respuestas
│   ├── View/Components/
│   │   ├── AppLayout.php
│   │   └── GuestLayout.php
│   └── Providers/
│       └── AppServiceProvider.php
├── config/
│   ├── app.php
│   ├── auth.php                            # Guard: web (session) + api (jwt)
│   ├── jwt.php                             # TTL: 60min, Refresh: 2 semanas
│   ├── services.php                        # Google OAuth + Gemini API key
│   └── ... (demas configs de Laravel)
├── database/
│   ├── ingles_db.sql                       # Schema completo + seed data
│   ├── factories/
│   │   └── UserFactory.php
│   └── migrations/
│       └── (migraciones de Laravel y custom)
├── public/
│   └── build/                              # Assets compilados (Vite)
├── resources/
│   ├── css/app.css
│   ├── js/app.js + bootstrap.js
│   └── views/
│       ├── layouts/
│       │   ├── app.blade.php               # Layout principal autenticado
│       │   ├── guest.blade.php             # Layout para auth
│       │   └── navigation.blade.php        # Navbar completa
│       ├── auth/                            # 6 vistas de autenticacion
│       ├── admin/                           # 3 vistas (dashboard, users, user-form)
│       ├── professor/                       # 2 vistas (dashboard, student-progress)
│       ├── levels/                          # 2 vistas (index, learn)
│       ├── placement/                       # 1 vista (index - 75 preguntas)
│       ├── profile/                         # 4 vistas (edit + 3 partials)
│       ├── components/                      # 14 componentes Blade reutilizables
│       ├── dashboard.blade.php             # Dashboard gamificado del estudiante
│       └── welcome.blade.php               # Landing page publica
├── routes/
│   ├── web.php                             # 20+ rutas web
│   ├── api.php                             # 11 rutas API (JWT)
│   ├── auth.php                            # Rutas de autenticacion
│   └── console.php
├── tests/
│   ├── Feature/ProfileTest.php
│   └── Unit/ExampleTest.php
├── .env                                    # Variables de entorno
├── composer.json
├── package.json
├── vite.config.js
├── tailwind.config.js
└── README.md
```

---

## 6. RUTAS EXISTENTES

### Web Routes (20+)

| Metodo | Ruta | Nombre | Descripcion | Middleware |
|--------|------|--------|-------------|-----------|
| GET | / | home | Landing page | guest |
| GET | /dashboard | dashboard | Dashboard por rol | auth, verified |
| GET | /levels | levels.index | Mapa de niveles CEFR | auth |
| POST | /lessons/{lesson}/complete | lessons.complete | Marcar leccion completa | auth |
| GET | /lessons/{lesson}/learn | lessons.learn | Ver contenido + practica de leccion | auth |
| POST | /lessons/{lesson}/check-practice | lessons.check-practice | Verificar respuestas de practica | auth |
| GET | /placement | placement.index | Test de ubicacion | auth, verified |
| POST | /placement | placement.submit | Enviar respuestas test | auth, verified |
| GET | /placement/skip | placement.skip | Saltar test (A1) | auth, verified |
| GET | /professor/dashboard | professor.dashboard | Dashboard profesor | auth, role:professor,admin |
| GET | /professor/students/{user}/progress | professor.student-progress | Progreso detallado | auth, role:professor,admin |
| GET | /admin/dashboard | admin.dashboard | Dashboard admin | auth, role:admin |
| GET | /admin/users | admin.users | Lista de usuarios | auth, role:admin |
| GET/POST | /admin/users/create | admin.users.create/store | Crear usuario | auth, role:admin |
| GET/PATCH | /admin/users/{user}/edit | admin.users.edit/update | Editar usuario | auth, role:admin |
| DELETE | /admin/users/{user} | admin.users.delete | Eliminar usuario | auth, role:admin |
| GET/PATCH/DELETE | /profile | profile.* | Gestionar perfil | auth |

### API Routes (11) - JWT

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

### Listening Routes (Nuevas)

| Metodo | Ruta | Nombre | Descripcion | Middleware |
|--------|------|--------|-------------|-----------|
| GET | /listening | listening.index | Lista de lecciones por nivel | auth |
| GET | /listening/{listeningLesson} | listening.show | Leccion con audio y preguntas | auth |
| POST | /listening/{listeningLesson}/check | listening.check | Verificar respuestas | auth |

---

## 7. FEATURES IMPLEMENTADOS

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
- [x] 14 componentes Blade reutilizables
- [x] Listening Practice con 99 lecciones (A1-A2, 3114 preguntas)
- [x] Listening integrado en dashboard y mapa de niveles
- [x] Motor de preguntas unificado: 3114 preguntas importadas a `questions`/`question_options` (99 cuestionarios, 180 recursos)
- [x] Comando `import:listening-questions` (idempotente, con `--force` y `--dry-run`)
- [x] Practica interactiva en `lessons.learn` (MC + fill_blank, auto-completado al 70%+)
- [x] `listening.check` guarda intentos, respuestas y progreso real (fuente: cuestionarios importados)
- [x] Gamificacion real: rachas (dias consecutivos) y actividades semanales en `GamificationService`
- [x] Constraint de progreso corregida a `unique(student_id, lesson_id)` (antes rompia al completar 2 lecciones del mismo nivel)
- [x] Chat IA funcional (`/chat` + `chat.send`, tutor con Gemini, degrada sin API key)
- [x] Feedback de tutor IA por intento (`attempt_logs.ai_feedback`) en practica y listening
- [x] TTS: boton "Escuchar" con speechSynthesis del navegador en lecciones sin audio
- [x] `TtsService` + comando `generate:listening-audio` (Google Cloud TTS via service account, sin dependencias)

---

## 8. INTEGRACION CON GOOGLE DRIVE API

### Objetivo
Conectar los datos de lecciones de ingles (Excel con preguntas) y audios que estan en Google Drive para mostrarlos en la app como ejercicios de listening.

### Archivos Implementados

| Archivo | Descripcion |
|---------|-------------|
| `app/Services/GoogleDriveService.php` | Servicio para conectar con Google Drive API |
| `app/Services/ExcelReaderService.php` | Servicio para leer archivos Excel |
| `app/Models/ListeningLesson.php` | Modelo para lecciones de listening |
| `app/Console/Commands/ImportListeningFromDrive.php` | Comando artisan para importar datos |
| `app/Console/Commands/ImportListeningQuestions.php` | Comando para migrar preguntas a `questions`/`question_options` |
| `app/Http/Controllers/LevelController.php` | Controlador de lecciones |
| `app/Http/Controllers/ListeningController.php` | Controlador de listening practice |
| `resources/views/listening/index.blade.php` | Vista de lista de lecciones |
| `resources/views/listening/show.blade.php` | Vista de leccion con audio y preguntas |
| `database/migrations/2026_08_18_000001_create_listening_lessons_table.php` | Migracion de tabla |
| `database/migrations/2026_08_19_000002_add_listening_lesson_id_to_questionnaires_table.php` | Vincula cuestionarios con lecciones de listening |
| `database/migrations/2026_08_19_000003_add_gamification_columns_to_users_table.php` | `last_activity_at`, `current_streak`, `longest_streak` en users |
| `database/migrations/2026_08_19_000004_fix_student_progress_unique_constraint.php` | Unique de progreso ahora es `(student_id, lesson_id)` |

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

# Importar solo un nivel especifico
php artisan import:listening --level=A1

# Vista previa sin guardar en BD
php artisan import:listening --dry-run

# Especificar IDs manualmente
php artisan import:listening --excel-file-id=XXX --audio-folder-id=YYY

# Migrar preguntas de listening_lessons hacia questions/question_options
php artisan import:listening-questions

# Solo un nivel + vista previa + re-importar existentes
php artisan import:listening-questions --level=A1 --dry-run
php artisan import:listening-questions --force
```

### Flujo de la Aplicacion

```
1. Admin ejecuta comando de importacion
2. Se descarga Excel desde Google Drive
3. Se leen datos por hoja (cada hoja = un nivel)
4. Se crean/actualizan registros en listening_lessons
5. Se vinculan audios por matching de nombre
6. Estudiante accede a /listening
7. Selecciona nivel (A1, A2, etc.)
8. Ve lista de lecciones disponibles
9. Selecciona una leccion
10. Escucha audio desde Google Drive
11. Responde preguntas
12. Sistema verifica respuestas y muestra score
```

---

## 9. FEATURES PENDIENTES / POR MEJORAR

- [ ] Contenido real de lecciones (lesson_prompt_payload JSON)
- [ ] Sistema de cuestionarios dinamicos (cuestionarios importados; falta la vista de ejercicio en web)
- [ ] Recursos multimedia reales (recursos importados de Excel; faltan audios reales en Drive)
- [ ] Gamificacion completa (leaderboards, rachas, insignias)
- [ ] Sistema de notificaciones
- [ ] Chat con IA (el boton "Chat IA" en nav esta referenciado pero no implementado)
- [ ] App movil (la API JWT esta lista para conectarse)
- [ ] Tests automatizados (solo hay tests de ejemplo)
- [ ] Paginacion y busqueda en vistas de admin
- [ ] Exportar reportes de progreso
- [ ] Sistema de tareas/actividades asignadas por profesor
- [ ] Integrar mas preguntas en placement test (actualmente hardcodeadas)
- [ ] Migrar placement test a base de datos

---

## 10. VARIABLES DE ENTORNO NECESARIAS (.env)

```env
APP_NAME="Agente Ingles UTBIS"
APP_ENV=local
APP_KEY=...
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ingles_db
DB_USERNAME=root
DB_PASSWORD=

GOOGLE_CLIENT_ID=...
GOOGLE_CLIENT_SECRET=...
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

GEMINI_API_KEY=...

JWT_SECRET=...
JWT_TTL=60
JWT_REFRESH_TTL=20160
```

---

## 11. COMO EJECUTAR EL PROYECTO

```bash
# Instalar dependencias
composer install
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Crear base de datos y migrar
php artisan migrate --seed
# O importar directamente: database/ingles_db.sql

# Compilar frontend
npm run dev

# Ejecutar servidor
php artisan serve
```

### Usuarios de prueba
| Email | Password | Rol |
|-------|----------|-----|
| admin@utbis.edu | password | admin |
| profesor@utbis.edu | password | professor |
| test@example.com | password | student |

---

## 12. CONVENCIONES DE CODIGO

- **IDs:** Todos los modelos usan UUIDs (trait HasUuids)
- **Nomenclatura:** snake_case en BD, camelCase en PHP, PascalCase en clases
- **Auth:** Doble sistema - sesiones para web, JWT para API
- **Vistas:** Blade con componentes reutilizables
- **Estilos:** Tailwind CSS + Alpine.js para interactividad
- **Roles:** Middleware `role:admin`, `role:professor,admin`
- **Archivos de config:** Laravel 12 usa bootstrap/app.php en vez de Kernel.php

---

## 13. NOTAS IMPORTANTES

1. **El placement test esta hardcodeado** en PlacementController con 75 preguntas. Futura migracion a BD.
2. **QuestionnaireOption fue eliminado** (apuntaba a tabla inexistente; era duplicado de QuestionOption).
3. **El "Chat IA"** aparece en la navegacion pero no tiene implementacion aun.
4. **Los resources (audio/imagenes)** estan importados con URLs del Excel; faltan archivos reales subidos y audios en Drive.
5. **La API JWT** esta lista para una app movil futura.
6. **El owl mascot** es un elemento visual recurrente en toda la app.
7. **Dominio restringido:** Solo emails terminados en @utbispuebla.edu.mx pueden registrarse.
