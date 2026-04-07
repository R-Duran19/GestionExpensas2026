# 🐳 Estructura Docker - GestionExpensas2026

## 📁 Archivos Creados

```
GestionExpensas2026/
├── Dockerfile                          # Imagen del contenedor principal
├── docker-compose.yml                  # Orquestación de servicios
├── .env.docker.example                 # Template de variables de entorno
├── .dockerignore                       # Archivos a excluir del build
├── deploy.sh                          # Script de deployment (bash)
├── Makefile                           # Atajos de comandos (make)
├── DOCKER_DEPLOYMENT.md               # Guía completa de deployment
└── docker/
    ├── entrypoint.sh                  # Script de inicio del contenedor
    ├── nginx/
    │   └── default.conf               # Configuración de Nginx interno
    ├── supervisor/
    │   └── supervisord.conf           # Configuración de Supervisor
    └── mysql/
        └── init.sql                   # Script de inicialización de MySQL
```

## 🏗️ Arquitectura de Contenedores

```
┌─────────────────────────────────────────────────────────────┐
│                    geopricer_365soft_app-network            │
│                     (Red Externa Compartida)                │
├─────────────────────────────────────────────────────────────┤
│                                                               │
│  ┌──────────────────────┐        ┌──────────────────────┐  │
│  │  Nginx Proxy Manager │◄──────►│  gestion_expensas_app │  │
│  │    (npm_network)     │        │   (Puerto 80 interno) │  │
│  │   :80, :443, :81     │        │   (Puerto 8001 host)  │  │
│  └──────────────────────┘        └──────────┬───────────┘  │
│                                             │               │
│                                    (PHP-FPM + Nginx interno)│
└─────────────────────────────────────────────┼───────────────┘
                                              │
                    ┌─────────────────────────┴─────────────────┐
                    │   gestion_expensas_network (Red interna)  │
                    ├────────────────────────────────────────────┤
                    │                                            │
                    │  ┌──────────────────────────────────────┐ │
                    │  │    gestion_expensas_mysql            │ │
                    │  │   (Solo accesible internamente)      │ │
                    │  │       Puerto 3307 en host            │ │
                    │  └──────────────────────────────────────┘ │
                    └────────────────────────────────────────────┘
```

## 🚀 Comandos Rápidos

### Usando Make (Recomendado)
```bash
make help              # Ver todos los comandos
make install           # Instalación inicial
make deploy            # Deployment completo
make update            # Actualizar aplicación
make logs              # Ver logs
make shell             # Acceder a consola del contenedor
make migrate           # Ejecutar migraciones
make backup            # Crear backup de BD
```

### Usando el Script deploy.sh
```bash
chmod +x deploy.sh
./deploy.sh deploy     # Deployment completo
./deploy.sh update     # Actualizar
./deploy.sh logs       # Ver logs
./deploy.sh shell      # Acceder a contenedor
```

### Usando Docker Compose Directo
```bash
docker-compose up -d           # Levantar
docker-compose down            # Detener
docker-compose logs -f         # Ver logs
docker-compose exec app bash   # Shell
```

## 🔌 Integración con Nginx Proxy Manager

### Configuración del Proxy Host

**Panel:** `http://tu-vps-ip:81`

**Nuevo Proxy Host:**
- **Domain Names:** `expensas.tu-dominio.com`
- **Scheme:** `http`
- **Forward Hostname/IP:** `gestion_expensas_app`
- **Forward Port:** `80`
- **Cache Assets:** ✅
- **Block Common Exploits:** ✅
- **Websockets Support:** ❌ (a menos que lo necesites)

**SSL:**
- **SSL Certificate:** Let's Encrypt
- **Force SSL:** ✅
- **HTTP/2 Support:** ✅

## 🔐 Variables de Entorno Principales

### Requeridas (configurar en .env.docker):
```bash
DB_DATABASE=expensas_db
DB_USERNAME=expensas_user
DB_PASSWORD=tu_contraseña_segura
DB_ROOT_PASSWORD=tu_contraseña_root_segura
```

### Opcionales:
```bash
APP_PORT=8001              # Puerto de acceso directo (defecto: 8001)
DB_PORT_HOST=3307          # Puerto MySQL en host (defecto: 3307)
RUN_MIGRATIONS=false       # Ejecutar migraciones al inicio
RUN_SEEDERS=false          # Ejecutar seeders al inicio
```

## 📊 Puertos Utilizados

| Servicio | Puerto Interno | Puerto Host | Descripción |
|----------|----------------|-------------|-------------|
| gestion_expensas_app | 80 | 8001 | Aplicación web (Nginx) |
| gestion_expensas_app | 9000 | - | PHP-FPM (interno) |
| gestion_expensas_mysql | 3306 | 3307 | MySQL |

## 💾 Persistencia de Datos

### Volúmenes Docker:
- `gestion_expensas_mysql_data` → Datos de MySQL
- Montura `./storage/app/public` → Archivos públicos de Laravel

### Backups:
```bash
make backup
# Archivos guardados en ./backups/
```

## 🔧 Troubleshooting

### La aplicación no responde:
```bash
make status              # Ver estado
make logs                # Ver logs
make clear-cache         # Limpiar caché
```

### Error de conexión a MySQL:
```bash
make logs-mysql          # Ver logs de MySQL
docker network inspect geopricer_365soft_app-network
```

### Assets no cargan:
```bash
make storage-link        # Crear enlace simbólico
make clear-cache         # Limpiar caché
```

### Performance lenta:
```bash
make optimize            # Optimizar para producción
```

## 📝 Checklist Pre-Deployment

- [ ] Red `geopricer_365soft_app-network` existe
- [ ] Archivo `.env.docker` configurado correctamente
- [ ] Contraseñas seguras configuradas
- [ ] Nginx Proxy Manager funcionando
- [ ] Dominio/Subdominio apunta al VPS
- [ ] Backup de base de datos (si es actualización)

## 🎯 Próximos Pasos

1. **Configurar .env.docker** con tus datos reales
2. **Ejecutar `make install`** o `make deploy`
3. **Configurar Nginx Proxy Manager** con los datos indicados
4. **Ejecutar `make migrate`** para crear las tablas
5. **Verificar acceso** a través del dominio configurado

## 📞 Soporte

- Ver `DOCKER_DEPLOYMENT.md` para guía detallada
- `make help` para ver todos los comandos disponibles
- Revisar logs con `make logs` para identificar problemas