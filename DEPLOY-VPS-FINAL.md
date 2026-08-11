# 🚀 Guía Definitiva de Deploy en VPS — Gestión de Expensas

> Documento generado tras resolver 5 bugs consecutivos durante el deploy de agosto 2026. Esta es la configuración **probada y blindada** que funciona en el VPS de Contabo.

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

---

## ✅ Las 5 reglas de oro (post-mortem)

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
| Backup BD | `docker compose exec mysql mysqldump -u root -p"$DB_ROOT_PASSWORD" expensas_db > backup_$(date +%Y%m%d).sql` |
| Restaurar BD | `docker compose exec -T mysql mysql -u root -p"$DB_ROOT_PASSWORD" expensas_db < backup.sql` |

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
| 502 Bad Gateway | `docker exec nginx-proxy-manager-app-1 curl -I http://gestion_expensas_app:80`. Si da conexión rechazada → problema red. Si 200/302 → config NPM. |
| 500 Internal Server Error | Verificar permisos (ver arriba) + `docker compose exec app cat storage/logs/laravel.log \| tail -50` |
| `Access denied for user` | Las credenciales del `.env` no coinciden con las que MySQL tiene. `docker compose down -v && docker compose up -d --build` |
| Migraciones colgadas | `docker compose exec app php artisan migrate:status` |
| APP_KEY vacía | Generar con `docker compose exec app php artisan key:generate --force` |

---

## 📍 Versión documentada

- Última actualización: agosto 2026
- Commit base: `f467b63` (post-fix dotenv)
- Repositorio: https://github.com/R-Duran19/GestionExpensas2026
- Dominio: https://expensas.acmanalytics.online
