# 🚀 Guía Definitiva de Deploy en VPS — Gestión de Expensas

> Documento generado tras resolver 7 bugs consecutivos durante el deploy de agosto 2026. Esta es la configuración **probada y blindada** que funciona en el VPS de Contabo.

---

## 📋 Configuración del VPS (referencia)

| Item | Valor |
|---|---|
| Hosting | Contabo |
| IP | `169.58.133.43` |
| Usuario | `richy` (sudo sin password) |
| OS | Ubuntu actualizado |
| Docker + Compose | Instalados |
| Proxy inverso | Nginx Proxy Manager (contenedor `jc21/nginx-proxy-manager:latest`) |
| Red compartida | `proxy_network` (red Docker externa) |
| Puerto panel NPM | `81` |

---

## 🐛 Cronología de bugs resueltos (lecciones aprendidas)

| # | Síntoma | Causa raíz | Fix aplicado |
|---|---|---|---|
| 1 | `TLS/SSL error: SSL is required, but the server does not support it` | Cliente MariaDB 11.x (Debian) exige SSL por defecto; el server no lo ofrecía | `--ssl=0` en server MySQL + `--skip-ssl` en entrypoint del cliente |
| 2 | `mysqladmin: unknown variable 'ssl-mode=DISABLED'` | Esa flag no existe en MariaDB (sí en MySQL oficial) | Usar `--skip-ssl` en su lugar |
| 3 | `dependency failed to start: container gestion_expensas_mysql is unhealthy` | El healthcheck usaba `--skip-ssl` (sintaxis MariaDB) pero el contenedor MySQL tiene cliente MySQL oficial | **Quitar el healthcheck**. El entrypoint.sh ya espera MySQL con su propio bucle |
| 4 | `syntax error near unexpected token 'then'` | Typo: `until ... then` en lugar de `until ... do` | Cambiar `then` por `do` + verificar con `bash -n` antes de commitear |
| 5 | `Failed to parse dotenv file. Encountered unexpected whitespace at [""Gestión de Expensas"}"]` | Doble comilla: docker-compose pasaba `"Gestión de Expensas"` y el entrypoint la volvía a envolver | Quitar comillas internas del compose; solo un par en el `.env` final |
| 6 | `SQLSTATE[23000]: Cannot add or update a child row: foreign key constraint fails factor_prorrateo_creado_por_foreign` | `FactorProrrateoSeeder` y `FacturaAguaSeeder` tenían fallback `?? 2` (ID inexistente) cuando el admin no se encontraba | Patrón robusto en 3 niveles: admin por email → primer usuario → `1` |
| 7 | `livewire.min.js:1 Failed to load resource: 404` + `Livewire is not defined` | Nginx interno del contenedor tenía regex `location ~* \.js$` **sin** `try_files`. `/livewire/livewire.min.js` no existe físicamente (Livewire 4 lo sirve vía PHP) → nginx devolvía 404 directo, sin pasar a Laravel | `location ^~ /livewire/` prioritaria + `try_files` en la location de estáticos |

---

## ✅ Las 8 reglas de oro (post-mortem)

### 1. Nunca hardcodear secretos en `docker-compose.yml`
Usar `${VAR:?mensaje}` → falla temprano si falta. Las credenciales viven en `.env` del host (no commiteado).

### 2. Sin `healthcheck` en MySQL
El entrypoint del contenedor app ya tiene su propio bucle de 60 intentos. Agregar healthcheck duplica lógica y rompe por diferencias de sintaxis entre clientes MySQL oficial ↔ MariaDB.

### 3. `depends_on` en forma simple
```yaml
depends_on:
  - mysql
```
NUNCA usar `condition: service_healthy` a menos que el healthcheck esté 100% probado con el cliente correcto.

### 4. SSL: dos flags, no uno
- **Server MySQL** (`command:` del compose): `--ssl=0`
- **Cliente MariaDB** en entrypoint: `--skip-ssl`

Solo uno de los dos no alcanza con esta combinación de versiones (MariaDB 11 ↔ MySQL 8.0.46).

### 5. Validar scripts bash antes de commitear
```bash
bash -n docker/entrypoint.sh
```
Detecta errores de sintaxis sin ejecutar. Es **gratis** y ahorra un ciclo completo de build (5 minutos) por bug tonto.

### 6. Sin comillas duplicadas en `.env` generado
- En `docker-compose.yml`: valores SIN comillas internas (YAML no las necesita)
- En `entrypoint.sh` (heredoc): un solo par de comillas externas para valores con espacios

```yaml
# docker-compose.yml (sin comillas)
- MAIL_FROM_NAME=Gestión de Expensas
```
```bash
# entrypoint.sh (un solo par)
MAIL_FROM_NAME="${MAIL_FROM_NAME:-Gestión de Expensas}"
```

### 7. Seeders idempotentes con claves foráneas
NUNCA usar un fallback hardcoded para IDs de FK (tipo `?? 2`). Patrón seguro en 3 niveles:

```php
$userId = User::where('email', 'admin@admin.com')->first()?->id
    ?? User::first()?->id
    ?? 1;
```

Cualquier seeder con `firstOrCreate` puede correr en cualquier orden sin romper.

### 8. Nginx: rutas dinámicas de Livewire/Sanctum/etc. deben pasar a Laravel
Livewire 3/4 sirve `/livewire/livewire.min.js` **vía PHP**, no como archivo físico. Si el nginx interno tiene una regex `location ~* \.js$` **sin** `try_files`, esos requests devuelven 404 directo y se rompe todo el frontend.

Solución:
```nginx
# Antes que la regex de estáticos
location ^~ /livewire/ {
    try_files $uri /index.php?$query_string;
}

# Y en la location de estáticos, SIEMPRE agregar try_files como fallback
location ~* \.(...|js|...)$ {
    expires 30d;
    try_files $uri /index.php?$query_string;
}
```

---

## 🔧 Deploy desde cero

### Pre-requisitos en el VPS

```bash
# Verificar red proxy_network (debe existir para NPM)
docker network inspect proxy_network >/dev/null 2>&1 || docker network create proxy_network

# Verificar SSH a GitHub (deploy key)
ssh -T git@github.com   # debe decir "Hi R-Duran19/GestionExpensas2026!"
```

### Pasos

```bash
cd /home/richy
git clone git@github.com:R-Duran19/GestionExpensas2026.git
cd GestionExpensas2026

# 1. Crear .env con credenciales REALES
cp .env.example .env
nano .env
#   - APP_KEY: generar (ver abajo) o usar la del repo
#   - DB_PASSWORD: solo alfanumérico, sin símbolos
#   - DB_ROOT_PASSWORD: distinta de la anterior

# 2. Generar APP_KEY si no la rellenaste a mano
docker compose run --rm --no-deps app php artisan key:generate --show
#   copiar salida como APP_KEY en .env

# 3. Build + up
docker compose up -d --build

# 4. Esperar a que la app termine de inicializar
docker compose logs -f app
#   (Ctrl+C cuando veas "🎉 Contenedor listo")
```

### Generar APP_KEY en local (alternativa)

```bash
php artisan key:generate --show
# copiar la línea "base64:..." como APP_KEY en .env del VPS
```

### Configurar Proxy Host en NPM

Panel: `http://169.58.133.43:81`

1. **SSL Certificates** → Add SSL Certificate → Let's Encrypt
   - Domain Names: `expensas.acmanalytics.online` (escrito a mano, sin espacios)
   - Email: tu correo
   - Agree to Terms ✅

2. **Hosts** → Proxy Hosts → Add Proxy Host
   - **Details:**
     - Domain Names: `expensas.acmanalytics.online`
     - Scheme: `http`
     - Forward Hostname: `gestion_expensas_app`
     - Forward Port: `80`
     - Block Common Exploits ✅
     - Websockets Support ✅ (crítico para Livewire)
   - **SSL:**
     - SSL Certificate: el de Let's Encrypt creado arriba
     - Force SSL ✅
     - HTTP/2 Support ✅
     - HSTS Enabled ✅
   - **Advanced:** DEJAR TOTALMENTE VACÍO

3. Verificar:
   ```bash
   docker exec -it nginx-proxy-manager-app-1 curl -I http://gestion_expensas_app:80
   #   debe devolver 200/302
   curl -I https://expensas.acmanalytics.online
   #   debe devolver 200/302
   ```

---

## 🌱 Ejecutar seeders (datos de enero a julio 2026)

### Estado inicial

El entrypoint.sh ejecuta `php artisan db:seed --force` **automáticamente** en el primer arranque (si la tabla `users` está vacía). Eso cubre todos los seeders declarados en `DatabaseSeeder.php`:

- `AdminRoleSeeder` (rol admin + usuario `admin@admin.com / admin123`)
- `UnidadesMigracionSeeder`
- `DepartamentosFromExcelSeeder`
- `OficinasFromExcelSeeder`
- `LocalesFromExcelSeeder`
- `ParqueosBaulerasFromExcelSeeder`
- `FactorProrrateoSeeder`
- `FacturaAguaSeeder`
- `MedidorGrupoLocalesSeeder`
- `CategoriaIngresoSeeder` + `CategoriaEgresoSeeder`
- **Períodos: Enero, Febrero, Marzo, Abril, Mayo, Junio, Julio 2026**

### Verificar qué se corrió

```bash
docker compose exec app php artisan tinker --execute="echo 'Users: ' . App\\Models\\User::count() . PHP_EOL;"
docker compose exec app php artisan tinker --execute="echo 'Periodos: ' . App\\Models\\PeriodoExpensa::count() . PHP_EOL;"
docker compose exec app php artisan tinker --execute="echo 'Expensas: ' . App\\Models\\Expensa::count() . PHP_EOL;"
```

### Re-correr seeders individuales (sin borrar datos)

```bash
# Un seeder específico (ej: julio)
docker compose exec app php artisan db:seed --class=PeriodoJulio2026DesdeJSONSeeder --force

# Todos los seeders (idempotente para AdminRole; los demás SI pueden duplicar)
docker compose exec app php artisan db:seed --force
```

### Reset completo + seed (⚠️ BORRA TODA LA BD)

Solo si necesitas empezar de cero:

```bash
# Opción A: dentro del contenedor app (mantiene volumen Docker)
docker compose exec app php artisan migrate:fresh --seed --force

# Opción B: bajando el contenedor (más limpio)
docker compose down -v
docker compose up -d --build
docker compose logs -f app    # esperar "🎉 Contenedor listo"
```

### Listar seeders disponibles

```bash
docker compose exec app ls database/seeders/
```

---

## 🔄 Redeploy (cambios futuros)

```bash
cd ~/GestionExpensas2026
git pull
docker compose up -d --build
docker compose logs -f app
```

El entrypoint es **idempotente**:
- Las migraciones corren las nuevas.
- Los seeders **no** se repiten si ya hay usuarios (evita duplicados).
- Las cachés se regeneran en producción.

---

## 🧰 Comandos útiles (chuleta)

| Acción | Comando |
|---|---|
| Logs app en vivo | `docker compose logs -f app` |
| Logs MySQL | `docker compose logs -f mysql` |
| Estado contenedores | `docker compose ps` |
| Reiniciar app | `docker compose restart app` |
| Bajar todo | `docker compose down` |
| Borrar BD también | `docker compose down -v` |
| Entrar al contenedor | `docker compose exec app bash` |
| Tinker | `docker compose exec app php artisan tinker` |
| Limpiar caché | `docker compose exec app php artisan optimize:clear` |
| Listar rutas | `docker compose exec app php artisan route:list` |
| Migraciones pendientes | `docker compose exec app php artisan migrate:status` |

---

## 💾 Backup y restore de la base de datos

### Backup comprimido (recomendado)

```bash
cd ~/GestionExpensas2026

docker compose exec -T mysql mysqldump \
    -u root -p"$DB_ROOT_PASSWORD" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    expensas_db | gzip > "backup_expensas_$(date +%Y%m%d_%H%M%S).sql.gz"

# Verificar integridad del gzip
gunzip -t backup_expensas_*.sql.gz && echo "Backup OK"

# Ver tamaño
ls -lh backup_expensas_*.sql.gz
```

Genera un archivo `backup_expensas_YYYYMMDD_HHMMSS.sql.gz` en el directorio actual.

### Alias para backup con un comando

```bash
echo 'alias backup-expensas="cd ~/GestionExpensas2026 && docker compose exec -T mysql mysqldump -u root -p\"\$DB_ROOT_PASSWORD\" --single-transaction --quick --routines --triggers expensas_db | gzip > backup_expensas_\$(date +%Y%m%d_%H%M%S).sql.gz && ls -lh backup_expensas_*.sql.gz | tail -1"' >> ~/.bashrc
source ~/.bashrc

# Después solo ejecutás:
backup-expensas
```

### Descargar backup a tu PC

```bash
# Desde Windows (PowerShell o Git Bash)
scp richy@169.58.133.43:~/GestionExpensas2026/backup_expensas_*.sql.gz "C:/backups/"
```

### Restaurar un backup

```bash
# En el VPS, dentro del directorio del proyecto
gunzip < backup_expensas_20260811_053000.sql.gz | \
    docker compose exec -T mysql mysql -u root -p"$DB_ROOT_PASSWORD" expensas_db

# Después limpiar caché de Laravel para que refleje los datos nuevos
docker compose exec app php artisan optimize:clear
```

### Backup automático con cron (opcional, set-and-forget)

```bash
# Crear directorio de backups
mkdir -p /home/richy/backups

# Editar crontab
crontab -e

# Agregar backup diario a las 03:00 AM, conservar 7 días (todo en una línea)
0 3 * * * cd /home/richy/GestionExpensas2026 && docker compose exec -T mysql mysqldump -u root -p"$(grep DB_ROOT_PASSWORD .env | cut -d= -f2)" --single-transaction --quick --routines --triggers expensas_db | gzip > /home/richy/backups/backup_expensas_$(date +\%Y\%m\%d).sql.gz && find /home/richy/backups/ -name "backup_expensas_*.sql.gz" -mtime +7 -delete
```

> ⚠️ Si la contraseña tiene caracteres especiales (`$`, `!`, etc.), escaparlos en el crontab.

---

## 🔐 Permisos (si aparece error 500 silencioso)

```bash
docker compose exec app chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
docker compose exec app chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
docker compose exec app php artisan optimize:clear
```

---

## 🆘 Troubleshooting rápido

| Síntoma | Diagnóstico |
|---|---|
| 502 Bad Gateway | `docker exec nginx-proxy-manager-app-1 curl -I http://gestion_expensas_app:80`. Si da conexión rechazada → problema red. Si 200/302 → config NPM (revisar Advanced vacío + Force SSL). |
| 500 Internal Server Error | Verificar permisos (ver arriba) + `docker compose exec app tail -50 storage/logs/laravel.log` |
| `Access denied for user` | Las credenciales del `.env` no coinciden con las que MySQL tiene. `docker compose down -v && docker compose up -d --build` |
| Migraciones colgadas | `docker compose exec app php artisan migrate:status` |
| APP_KEY vacía | Generar con `docker compose exec app php artisan key:generate --force` |
| `Livewire is not defined` / `livewire.min.js 404` | Nginx interno no deriva `/livewire/` a Laravel. Verificar `docker/nginx/default.conf` tenga `location ^~ /livewire/` + `try_files` en estáticos. |
| Componentes Livewire no actualizan URL (`?periodoId=` no aparece) | Mismo bug que arriba (Livewire JS no cargó) o falta de datos en BD (verificar `PeriodoExpensa::count() > 0`). |
| `foreign key constraint fails` al correr seeders | Algún seeder tiene fallback `?? 2` para IDs. Patrón robusto: `Model::where(...)->first()?->id ?? Model::first()?->id ?? 1`. |

---

## 📍 Versión documentada

- Última actualización: agosto 2026
- Commit base: `70a1cb1` (post-fix nginx/Livewire)
- Repositorio: https://github.com/R-Duran19/GestionExpensas2026
- Dominio: https://expensas.acmanalytics.online
