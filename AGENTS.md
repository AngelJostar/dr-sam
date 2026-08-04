# Instrucciones para Codex

## Alcance

- Este repositorio es la aplicación moderna Laravel de Dr. Sam.
- Trabaja dentro de este repositorio salvo autorización expresa para otro proyecto.
- Este repositorio es la única fuente funcional y visual del sistema.

## Forma de trabajo

- Antes de editar, revisa las rutas, controladores, modelos, vistas y pruebas relacionadas.
- Conserva los cambios existentes del usuario y evita reescrituras ajenas a la tarea.
- Sigue las convenciones de Laravel 12 y reutiliza componentes y estilos existentes.
- No ejecutes `migrate:fresh`, `db:wipe`, restablecimientos de Git ni eliminaciones masivas sin autorización explícita.
- No muestres ni agregues a Git valores de `.env`, credenciales, tokens, sesiones o datos productivos.
- Ejecuta pruebas enfocadas y después `php artisan test` cuando el alcance lo justifique.
- Informa archivos modificados, verificaciones realizadas y pendientes reales.

## Entorno local

- PHP 8.2 o superior, Laravel 12 y MySQL con base local `dr_sam`.
- `dr_sam.sql` es una instantánea de datos de demostración, no una copia para producción.
- Actualmente no se requiere Node.js porque no existe `package.json`.

## Comandos seguros habituales

```powershell
composer install
php artisan optimize:clear
php artisan migrate:status
php artisan test
php artisan serve --host=127.0.0.1 --port=8000
```
