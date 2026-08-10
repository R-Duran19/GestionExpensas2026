Contexto General:
El servidor es un VPS alojado en Contabo, configurado desde cero para entornos de producción. Alojará múltiples sistemas modernos, incluyendo una arquitectura Multi-Tenant en NestJS (para gestión inmobiliaria) y un sistema de tickets de alta concurrencia con bloqueo de asientos y pasarelas de pago. Todo el tráfico y la seguridad se centralizan mediante Nginx Proxy Manager sobre contenedores Docker.

1. Credenciales y Acceso Base:

IP del Servidor: 169.58.133.43

Sistema Operativo: Ubuntu (actualizado).

Usuario Principal: richy (con privilegios de sudo sin contraseña requerida para operaciones habituales).

2. Seguridad y Firewall (UFW):
El firewall UFW está habilitado con una política estricta de denegación por defecto. Solo los siguientes puertos TCP están abiertos al exterior:

22 (OpenSSH)

80 (HTTP - Tráfico web entrante)

443 (HTTPS - Tráfico web seguro)

81 (Panel de Administración Web de Nginx Proxy Manager)

3. Infraestructura Docker:

Motor: Docker y Docker Compose instalados mediante el script oficial.

Permisos: El usuario richy pertenece al grupo docker, permitiendo la ejecución de contenedores sin usar sudo.

Red Global: Existe una red puente (bridge) externa creada manualmente llamada proxy_network. Esta red es vital para la comunicación interna entre el proxy inverso y los microservicios sin exponer puertos al host físico.

4. Proxy Inverso (Nginx Proxy Manager):

Ubicación: /home/richy/nginx-proxy-manager/docker-compose.yml

Estado: Contenedor activo (jc21/nginx-proxy-manager:latest).

Configuración: Volúmenes mapeados para ./data y ./letsencrypt.

Redes: El servicio está explícitamente conectado a la red proxy_network.

Rol: Se encarga de recibir las peticiones de los dominios (ej. acmanalytics.online, eventosalfa.site), gestionar los certificados SSL automatizados de Let's Encrypt y enrutar el tráfico internamente hacia los contenedores correspondientes.

5. Flujo de Despliegue Estándar (Reglas para la IA asistente):
Para desplegar un nuevo sistema en este servidor, se deben seguir obligatoriamente estos pasos:

Clonación: Clonar el repositorio en el directorio home (/home/richy/).

Entorno: Configurar el archivo .env de producción.

Aislamiento de Red: El docker-compose.yml del proyecto NO debe mapear puertos al host físico (evitar ports: - "3000:3000"). En su lugar, el servicio debe exponer el puerto internamente y unirse a la red global.

Ejemplo de configuración requerida al final del compose del proyecto:

YAML
networks:
  proxy_network:
    external: true
Enrutamiento: Una vez levantado el contenedor (docker compose up -d), se debe configurar un Proxy Host en el panel de NPM apuntando al nombre del contenedor (ej. api_tickets) y su puerto interno, forzando la redirección HTTPS.