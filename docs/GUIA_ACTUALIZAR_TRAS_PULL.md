# Qué hacer después de clonar / hacer `git pull` de estos cambios

No hay dependencias nuevas (ni Composer ni npm), ni variables de entorno nuevas.
Lo único que cambia es el esquema de base de datos. Con eso alcanza:

```bash
php artisan migrate
```

Eso es todo para que el código funcione. El resto de esta guía es para
evitar sorpresas.

## 1. Requisito de versión de PHP

`composer.json` dice `^8.2`, pero el `vendor/` con el que corre Railway en
producción usa **PHP 8.4** (ver `nixpacks.toml`). Si tu PHP local es 8.2 o
8.3, `composer install` funcionará, pero para evitar diferencias de
comportamiento respecto a producción, usa PHP 8.4+ localmente si puedes.

En Windows con Laragon, si `php` en el PATH es una versión vieja, usa la
ruta completa a una versión más nueva que tengas instalada, por ejemplo:

```bash
"C:\laragon\bin\php\php-8.4.x-...\php.exe" artisan migrate
```

(No hay un launch.json compartido para esto — cada quien tiene sus rutas
de PHP en lugares distintos, así que no vale la pena versionarlo.)

## 2. La migración nueva

**Archivo:** `database/migrations/2026_08_28_000001_add_listening_lesson_scope_to_student_progress.php`

Hace dos cosas:

1. Agrega `listening_lesson_id` a `student_progress` (antes el progreso solo
   se anclaba al paquete de importación `lessons`; ahora se ancla a la
   lección real `listening_lessons`).
2. Agrega un `UNIQUE(cefr_level, sort_order)` a `listening_lessons`, para
   que nunca pueda haber dos lecciones en la misma posición del mismo
   nivel (eso rompería el bloqueo secuencial).

**Posible problema:** si tu `listening_lessons` local tiene datos de una
importación distinta a la mía y por casualidad hay `sort_order` repetidos
dentro del mismo `cefr_level`, la migración va a fallar con un error de
`Duplicate entry` en ese índice. Si te pasa:

```sql
-- Encuentra los duplicados
SELECT cefr_level, sort_order, COUNT(*)
FROM listening_lessons
GROUP BY cefr_level, sort_order
HAVING COUNT(*) > 1;
```

Corrige los `sort_order` repetidos (o borra/reimporta esas filas) y vuelve
a correr `php artisan migrate`.

No hace falta ningún backfill de datos: en la base compartida no había
ninguna fila de progreso (`student_progress` estaba en 0 registros), así
que nadie pierde avance.

## 3. Qué cambió a nivel de código (por si su editor marca cosas raras)

- La ruta `/lessons/{lesson}/learn` ahora se llama `/lessons/{listeningLesson}/learn`
  y apunta a un modelo distinto (`ListeningLesson`, no `Lesson`). Si alguien
  tenía un branch viejo con cambios en `LevelController`, va a haber
  conflictos de merge ahí — es esperado, no es un bug.
- `route('lessons.speaking-feedback', ...)` ahora recibe **un solo**
  parámetro (la lección), antes recibía dos (`[$lesson, $listeningLesson]`).

## 4. Cómo probar que quedó bien

```bash
php artisan test --filter=LevelsProgressTest
php artisan test --filter=SpeakingFeedbackValidationTest
```

Si tu `phpunit.xml` usa SQLite en memoria y te da error de
`information_schema.TABLE_CONSTRAINTS` o `could not find driver`, es un
problema de entorno preexistente (no relacionado a este cambio) — corre
los tests contra una base MySQL de prueba en su lugar:

```bash
DB_CONNECTION=mysql DB_DATABASE=ingles_test php artisan test
```
