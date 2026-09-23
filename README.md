# GestionExpensas2026

Sistema web de gestión de expensas consorciales que administra el ciclo completo de expensas de un edificio: unidades funcionales, propietarios e inquilinos con histórico de asignaciones, cálculo mensual de expensas (m² + consumo de agua), gestión de pagos con recibos, control de caja y facturas del servicio de agua (EPSAS).

Antes de este sistema el cálculo era manual, propenso a errores y difícil de auditar. Ahora el cálculo está automatizado, con audit trail completo y visibilidad en tiempo real del estado financiero del consorcio.

## Stack técnico

| Capa | Tecnología |
|------|------------|
| Backend | Laravel 12 (PHP 8.2+) |
| Frontend | Livewire 4 + Tailwind CSS + Alpine.js (mínimo) |
| Base de datos | MySQL 8 |
| PDF | DomPDF (barryvdh) |
| Imágenes | Intervention Image v4 (Imagick con fallback GD, conversión WebP) |
| Excel | maatwebsite/excel |
| Permisos | spatie/laravel-permission |
| Infraestructura | Docker Compose (app + mysql + nginx) |

## Módulos

### Expensas
- Períodos mensuales con cierre/reapertura e inmutabilidad (`periodo_cerrado`)
- Cálculo por unidad: expensa común (m² × factor categoría) + agua (consumo m³ × factor)
- Detalles por unidad y por responsable (propietario/inquilino)
- Boletas y reportes en PDF
- **Regla maestra propietario/inquilino**: el propietario NO ve unidades con inquilino actual; el inquilino solo ve las suyas (aplicada en listados, detalles y PDFs)

### Propiedades (Unidades)
- CRUD de unidades con características: m², categoría (A/B/C), tipo (domiciliaria/comercial), medidor
- Histórico de ocupaciones con fechas (pivot `desde`/`hasta`)
- SoftDeletes y scopes de búsqueda
- Exportación a Excel del prorrateo por unidad

### Personas (Propietarios/Inquilinos)
- CRUD de propietarios e inquilinos con búsqueda por CI, nombre, apellido, email
- Asignación multi-unidad con histórico; valida que la unidad no tenga otro inquilino actual
- Trait `Loggable` para auditoría de cambios

### Caja
- Dashboard con ingresos/egresos del día y del mes
- Ingresos con numeración secuencial y aplicación de pagos a expensas específicas
- Registro estructurado del pagador (`pagador_tipo` + `pagador_id`, propietario o inquilino)
- Egresos categorizados con categorías configurables
- Saldos a favor con aplicación automática **FIFO**
- Comprobantes: conversión a WebP con Imagick (fallback GD), recibos PDF, limpieza de temporales al cancelar

### Agua
- Configuración de facturas EPSAS (3 tipos: domiciliaria_1, domiciliaria_2, comercial) con historial de cambios
- Lecturas de medidor: individuales, compartidas y masivas
- Grupos de medidores compartidos con redistribución de consumo (ej. LOCALES 14-19, LOCALES 2-7-8-11)
- Edición de lecturas ya registradas con redistribución manual
- Importación desde JSON con detección automática de grupos (trait `ImportaLecturasConGrupos`)

### Configuración
- Factores de prorrateo por categoría
- Gestión de medidores y grupos
- Datos del edificio (singleton): nombre, dirección, CUIT, mensaje editable para PDFs

### Reportes
- Reporte general con 9 métricas por unidad (Generado/Cobrado/Deuda × Expensa/Agua/Total)
- Filtro por concepto (ambos / solo expensa / solo agua), cálculo al vuelo
- Exportación a Excel

## Arquitectura

Patrón **Service Layer**: la lógica de negocio vive en servicios (21), no en controladores. La UI son componentes Livewire (~39) y hay 30 modelos Eloquent con relaciones, softDeletes, scopes y enums.

Servicios principales: `ExpensaService`, `CajaService`, `IngresoService`, `EgresoService`, `PagoExpensaService`, `FacturaAguaService`, `MedidorService`, `ReporteService`, `PropietarioService`, `InquilinoService`.

Flujo de datos esencial:

```
Período → ExpensaService → Expensa (por unidad)
Pago → IngresoService → Ingreso + IngresoDetalle → Expensa (aplicación)
Ingreso (sobrepago) → SaldoAFavor → aplicación automática FIFO
FacturaAguaPeriodo → LecturaMedidor → cálculo de agua
Egreso → CajaService → Dashboard
```

## Instalación

### Requisitos
- PHP 8.2+, Composer 2, Node.js, MySQL 8 (local) o Docker (producción)

### Desarrollo local

```bash
composer install
cp .env.example .env
php artisan key:generate
# configurar DB en .env
php artisan migrate --seed
npm install && npm run build
php artisan storage:link
composer dev   # levanta artisan serve + vite
```

### Docker (producción)

```bash
cp .env.docker.example .env.docker   # editar valores
make install    # deploy completo: build + up + migrate + optimize
```

Comandos útiles del `Makefile`: `make up|down|restart|logs`, `make migrate`, `make seed`, `make backup`, `make shell`, `make deploy`, `make update`, `make test`.

## Testing

```bash
composer test          # php artisan test
make test              # dentro de Docker
```

## Alcance y límites

No incluye (por diseño): integración bancaria automática, notificaciones por email/SMS, aplicación móvil (solo web responsive), multiedificio (un consorcio por instalación) ni integración con sistemas contables externos.

Pendiente destacado del roadmap: intereses por mora, portal de propietarios, boletas consolidadas para imprimir (rama `feature/boletas-para-imprimir`), detección de anomalías de consumo.

## Estado

- ~92% del core completado (55+ features implementadas)
- 7 períodos sembrados con datos reales (Enero–Julio 2026)
- Consorcio de referencia: 331 unidades, 12.550,19 m²

## Licencia

MIT
