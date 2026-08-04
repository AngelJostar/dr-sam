# Guía completa para instalar y usar Dr. Sam con Codex

Esta guía está escrita para una persona que utilizará el sistema por primera vez y no necesita conocimientos de programación. Sigue los pasos en orden y copia en Codex los mensajes incluidos.

El proyecto se instalará en:

```text
C:\laragon\www\dr-sam-laravel
```

La aplicación se abrirá normalmente en:

```text
http://127.0.0.1:8000
```

> El repositorio y `dr_sam.sql` contienen información de demostración y deben mantenerse privados. No deben usarse como una base de producción.

## Antes de comenzar: qué hace Codex

Codex puede revisar el proyecto, ejecutar comandos, detectar errores y hacer cambios. Sin embargo, necesita que Windows tenga instaladas las herramientas que ejecutan la aplicación:

- **Git** descarga y actualiza el proyecto.
- **Laragon** proporciona PHP y MySQL.
- **Composer** descarga las dependencias de Laravel.
- **Codex Desktop** ayuda a realizar y verificar el procedimiento.

No es necesario entender estas herramientas. Codex comprobará si están disponibles, pero algunas instalaciones muestran ventanas de Windows que requieren hacer clic en **Sí**, **Next** o **Install**.

## Parte A. Preparar la computadora una sola vez

### 1. Tener acceso al repositorio

El propietario del repositorio debe agregar la cuenta de GitHub de la persona como colaborador. Antes de continuar:

1. Abre la invitación recibida por correo o GitHub.
2. Presiona **Accept invitation / Aceptar invitación**.
3. Inicia sesión en GitHub si se solicita.

Si no se acepta la invitación, Git mostrará errores como `Repository not found` o `Permission denied`.

### 2. Abrir Codex y pedir la revisión inicial

1. Abre **Codex Desktop**.
2. Crea una tarea nueva.
3. Si solicita una carpeta, selecciona temporalmente `C:\Users\TU_USUARIO\Documents`.
4. Copia y envía este mensaje:

```text
Quiero instalar por primera vez un proyecto Laravel en Windows.
No tengo conocimientos de programación. Comprueba, sin modificar nada todavía,
si tengo disponibles Git, PHP 8.2 o superior, Composer y MySQL.
Explícame claramente cuáles faltan y ayúdame a instalarlas usando Laragon.
No ejecutes eliminaciones ni comandos destructivos.
```

Codex ejecutará comprobaciones. Si todas las herramientas aparecen disponibles, continúa en la **Parte B**.

### 3. Instalar Git si Codex indica que falta

1. Abre [Git para Windows](https://git-scm.com/download/win).
2. Descarga la versión de 64 bits.
3. Abre el instalador descargado.
4. Acepta el permiso de Windows.
5. Conserva las opciones predeterminadas y presiona **Next** hasta llegar a **Install**.
6. Al terminar presiona **Finish**.
7. Cierra y vuelve a abrir Codex para que detecte Git.

No es necesario abrir la aplicación llamada Git Bash.

### 4. Instalar Laragon si faltan PHP, MySQL o Composer

1. Abre [Laragon](https://laragon.org/download/).
2. Descarga la edición completa para Windows, no la edición portátil mínima.
3. Ejecuta el instalador y acepta el permiso de Windows.
4. Usa como carpeta de instalación `C:\laragon`.
5. Mantén activada la opción para agregar Laragon al PATH si aparece.
6. Termina la instalación y abre **Laragon**.
7. Presiona el botón **Start All / Iniciar todo**.
8. Espera hasta que Apache y MySQL aparezcan iniciados.

Si Windows Firewall solicita permiso, permite el acceso en redes privadas.

Después cierra y vuelve a abrir Codex. Envía:

```text
Ya instalé Laragon en C:\laragon y presioné Start All.
Vuelve a comprobar Git, PHP, Composer y MySQL.
Si alguna herramienta no aparece en PATH, localízala dentro de Laragon
y dime la corrección más segura. No cambies la base de datos todavía.
```

No continúes hasta que Codex confirme:

- Git disponible.
- PHP 8.2 o superior.
- Composer disponible.
- MySQL disponible o localizable dentro de Laragon.

## Parte B. Descargar el proyecto por primera vez

### 5. Pedir a Codex que lo descargue

En Codex copia este mensaje:

```text
Descarga por primera vez el repositorio privado
https://github.com/AngelJostar/dr-sam.git
en C:\laragon\www\dr-sam-laravel.
Antes confirma que la carpeta destino no contiene otro proyecto.
No sobrescribas ninguna carpeta existente.
Avísame si GitHub solicita autenticación.
```

Si GitHub abre el navegador:

1. Inicia sesión con la cuenta que aceptó la invitación.
2. Autoriza Git Credential Manager si aparece.
3. Regresa a Codex.

Codex debe confirmar que existe:

```text
C:\laragon\www\dr-sam-laravel\artisan
```

### 6. Abrir la carpeta correcta en Codex

1. En Codex selecciona **File > Open Folder** o **Abrir carpeta**.
2. Abre `C:\laragon\www\dr-sam-laravel`.
3. Si Codex pregunta si confías en la carpeta, confirma que sí.
4. Crea una tarea nueva dentro de esa carpeta.
5. Envía:

```text
Lee README.md, AGENTS.md y docs/MANUAL_INSTALACION_CODEX.md completos.
Estoy instalando el sistema por primera vez y no tengo conocimientos técnicos.
Verifica que estás trabajando únicamente en C:\laragon\www\dr-sam-laravel.
Guíame con mensajes claros y no ejecutes comandos destructivos.
```

## Parte C. Configurar automáticamente el proyecto

### 7. Instalar dependencias y preparar `.env`

Con Laragon abierto y **Start All** activo, copia en Codex:

```text
Prepara esta instalación local por primera vez.
1. Ejecuta composer install.
2. Si no existe .env, copia .env.example como .env; no lo sobrescribas si ya existe.
3. Genera APP_KEY únicamente si todavía falta.
4. Configura APP_URL como http://127.0.0.1:8000.
5. Configura MySQL local con base dr_sam, usuario root y contraseña vacía,
   salvo que detectes que MySQL usa otra contraseña.
No importes aún la base y nunca agregues .env a Git.
Al finalizar, dime qué comprobaste.
```

Codex ejecutará normalmente:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
```

Es normal que `composer install` tarde varios minutos la primera vez.

### 8. Crear e importar `dr_sam.sql`

Confirma que Laragon siga abierto con MySQL iniciado. Luego envía a Codex:

```text
Configura la base de datos de esta primera instalación.
Comprueba que dr_sam.sql exista en la raíz.
Crea la base MySQL dr_sam con utf8mb4 si no existe.
Antes de importar, comprueba que sea una base nueva o vacía.
Importa dr_sam.sql, ejecuta solamente las migraciones pendientes,
crea el enlace de storage y limpia las cachés de Laravel.
No uses migrate:fresh, db:wipe, DROP DATABASE ni elimines datos.
Si MySQL pide contraseña, detente y pregúntame.
```

Los comandos normales son:

```powershell
mysql -u root -e "CREATE DATABASE IF NOT EXISTS dr_sam CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
cmd /c "mysql -u root dr_sam < dr_sam.sql"
php artisan migrate
php artisan storage:link
php artisan optimize:clear
```

No ejecutes `php artisan migrate:fresh`, porque borraría los datos incluidos.

### 9. Verificar que la instalación funciona

Envía a Codex:

```text
Verifica la instalación sin modificar datos:
ejecuta php artisan about, php artisan migrate:status y php artisan test.
Si algo falla, diagnostica la causa y explícamela antes de cambiar archivos.
```

La verificación puede tardar alrededor de un minuto. Debe terminar sin pruebas fallidas.

## Parte D. Abrir y cerrar el sistema

### 10. Abrirlo cada día

1. Abre Laragon.
2. Presiona **Start All**.
3. Abre Codex y la carpeta `C:\laragon\www\dr-sam-laravel`.
4. Envía:

```text
Inicia la aplicación Dr. Sam en http://127.0.0.1:8000.
Comprueba primero que MySQL esté disponible.
Mantén el servidor ejecutándose y avísame cuando pueda abrir la dirección.
```

Codex ejecutará:

```powershell
php artisan serve --host=127.0.0.1 --port=8000
```

5. Abre [http://127.0.0.1:8000](http://127.0.0.1:8000) en Chrome o Edge.

No cierres la terminal del servidor mientras uses el sistema. Si Codex administra el proceso, pídele que confirme que sigue activo.

### 11. Cerrar el sistema

1. Cierra la pestaña del navegador.
2. Pide a Codex:

```text
Detén únicamente el servidor local de Dr. Sam que iniciaste en el puerto 8000.
No detengas otros procesos ni borres archivos.
```

3. En Laragon presiona **Stop** si ya no usarás MySQL.

Cerrar el servidor no elimina información; los datos quedan guardados en MySQL.

## Parte E. Actualizar el proyecto

Cuando se informe que hay nuevos cambios, abre Codex en la carpeta del proyecto y envía:

```text
Antes de actualizar, revisa git status y dime si existen cambios locales.
Si no hay cambios sin guardar, descarga la versión más reciente con git pull --ff-only,
ejecuta composer install, aplica solamente migraciones pendientes,
limpia las cachés y ejecuta las pruebas.
No descartes ni sobrescribas cambios locales.
```

Si Codex informa que existen cambios locales, no continúes hasta consultar con el responsable técnico.

## Parte F. Solicitar cambios a Codex

Para pedir una modificación usa lenguaje cotidiano, pero incluye:

- La dirección de la pantalla donde ocurre.
- Qué botón o sección debe cambiar.
- Qué sucede actualmente.
- Qué debería suceder.
- Una captura cuando sea posible.

Ejemplo:

```text
Trabaja únicamente en este proyecto.
En http://127.0.0.1:8000/institution el botón Editar no abre el formulario.
Analiza la causa antes de modificar, implementa la corrección siguiendo la estructura actual,
conserva los demás cambios y ejecuta las pruebas relacionadas.
Al finalizar dime los archivos modificados y el resultado de las pruebas.
```

Antes de aceptar el resultado pide:

```text
Revisa el diff, confirma que no se incluyan .env, contraseñas, sesiones,
archivos de vendor ni cambios ajenos. Ejecuta las pruebas pertinentes y resume el resultado.
```

## Parte G. Guardar cambios en GitHub

Si el jefe está autorizado para publicar cambios, puede pedir a Codex:

```text
Revisa git status y muéstrame un resumen entendible de los cambios.
No incluyas secretos, .env, vendor, logs ni archivos temporales.
Si todo es correcto, prepara un commit con un mensaje descriptivo,
pero no hagas push hasta que yo lo confirme.
```

Después de revisar el resumen puede escribir:

```text
Publica ese commit en el repositorio remoto. Si GitHub solicita autenticación,
detente para que yo pueda completarla.
```

Nunca autorices estos comandos sin apoyo técnico:

- `git reset --hard`
- `git clean -fd`
- `php artisan migrate:fresh`
- `php artisan db:wipe`
- `DROP DATABASE`

## Solución de problemas

### Codex dice que `git`, `php`, `composer` o `mysql` no se reconoce

1. Confirma que Git y Laragon estén instalados.
2. Cierra completamente Codex.
3. Abre Laragon y presiona **Start All**.
4. Vuelve a abrir Codex.
5. Pide que localice la herramienta dentro de `C:\laragon` y configure el PATH de manera segura.

### La página no abre

Pide a Codex:

```text
Diagnostica por qué http://127.0.0.1:8000 no responde.
Comprueba si el servidor Laravel está activo y si el puerto 8000 está ocupado.
No cambies archivos hasta identificar la causa.
```

Si el puerto está ocupado, Codex puede usar temporalmente:

```powershell
php artisan serve --host=127.0.0.1 --port=8001
```

La dirección sería `http://127.0.0.1:8001`.

### Aparece un error de conexión con la base

1. Abre Laragon y confirma que MySQL esté iniciado.
2. Pide a Codex que revise la configuración sin mostrar contraseñas.
3. No vuelvas a importar `dr_sam.sql` sobre una base con trabajo existente.

### Aparece `No application encryption key has been specified`

Pide a Codex ejecutar:

```powershell
php artisan key:generate
php artisan optimize:clear
```

### Aparece `Base table or view not found`

No permitas que Codex borre la base. Pídele comprobar primero la importación y ejecutar únicamente:

```powershell
php artisan migrate
```

### Los cambios no aparecen

Pide a Codex:

```powershell
php artisan optimize:clear
```

Después actualiza el navegador con `Ctrl + F5`.

### Cuándo detenerse y pedir ayuda

No continúes sin apoyo técnico si aparece alguno de estos casos:

- Codex propone borrar o recrear la base.
- Git informa conflictos.
- Se solicita una contraseña desconocida de MySQL.
- El repositorio no permite acceso.
- Las pruebas fallan después de actualizar.
- Codex detecta cambios locales que nadie reconoce.

## Lista final de la primera instalación

- [ ] La invitación privada de GitHub fue aceptada.
- [ ] Git está instalado.
- [ ] Laragon está instalado en `C:\laragon`.
- [ ] Laragon puede iniciar MySQL.
- [ ] El proyecto existe en `C:\laragon\www\dr-sam-laravel`.
- [ ] Codex tiene abierta exactamente esa carpeta.
- [ ] `composer install` terminó correctamente.
- [ ] `.env` existe y no está agregado a Git.
- [ ] `dr_sam.sql` se importó en la base `dr_sam`.
- [ ] Las migraciones y pruebas terminaron correctamente.
- [ ] La aplicación abre en `http://127.0.0.1:8000`.
