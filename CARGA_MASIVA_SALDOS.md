# Carga Masiva de Saldos para QR Empleados

Sistema de carga masiva de saldos desde Excel que permite cargar o descontar saldos de múltiples QR Empleados simultáneamente.

## Características

### Tipos de Operación
- **Carga**: Suma saldo a la tarjeta - use números positivos (ejemplos: `500` o `+500`)
- **Descuento**: Resta saldo a la tarjeta - use números negativos (ejemplo: `-200`)

### Validaciones Automáticas
- QR Empleado debe existir (por UUID)
- QR Empleado debe estar activo
- No permite saldos negativos
- Sucursal requerida para descuentos
- Validación de formato de montos

### Funcionalidades Avanzadas
- Múltiples cargas al mismo QR en una sola importación
- Procesamiento en chunks de 100 filas
- Reporte detallado con 3 hojas (Resumen, Detalle, Errores)
- Estadísticas en tiempo real
- Generación automática de transacciones

## Uso

### 1. Acceder a la Función
En el módulo **QR Empleados**, encontrarás dos botones en la parte superior:
- **"Carga Masiva de Saldos"** - Importar Excel
- **"Descargar Plantilla"** - Obtener formato de ejemplo

### 2. Descargar y Preparar Plantilla

Haz clic en **"Descargar Plantilla"** para obtener el archivo Excel con:
- Formato correcto de columnas
- Ejemplos con UUIDs reales de tu sistema
- Instrucciones visuales
- Comentarios en cada columna

#### Estructura del Excel

```
| uuid                                 | monto   | descripcion      | sucursal         |
|--------------------------------------|---------|------------------|------------------|
| 123e4567-e89b-12d3-a456-426614174000 | 500     | Bono mensual     |                  |
| 234e5678-e89b-12d3-a456-426614174001 | -200    | Descuento comida | Mochomos MTY     |
| 345e6789-e89b-12d3-a456-426614174002 | 1000.50 | Carga inicial    |                  |
| 456e7890-e89b-12d3-a456-426614174003 | -50.25  | Ajuste           | Sucursal Centro  |
```

#### Columnas

1. **uuid** (requerido)
   - UUID del QR Empleado
   - Lo puedes copiar desde el panel de administración
   - Formato: `123e4567-e89b-12d3-a456-426614174000`

2. **monto** (requerido)
   - Para cargas: use números positivos (`500` o `+500`)
   - Para descuentos: use números negativos (`-200`)
   - `500` o `+500` = Carga $500 al saldo
   - `-200` = Descuenta $200 del saldo
   - Acepta decimales: `250.50`, `+250.50`, `-100.25`
   - No usar comas de miles

3. **descripcion** (opcional)
   - Motivo de la transacción
   - Si se omite, se genera: "Carga masiva del DD/MM/YYYY"
   - Máximo 500 caracteres

4. **sucursal** (condicional)
   - **REQUERIDA** solo para montos negativos (descuentos)
   - Nombre exacto de la sucursal (debe existir en el sistema)
   - Ejemplo: "Mochomos Monterrey", "Sucursal Centro"
   - Para cargas (+) se puede dejar vacía

### 3. Formato Correcto de Montos

#### ✅ Correcto
```
500         (número positivo = carga)
+500        (con signo + explícito = carga)
1000.50     (decimales permitidos)
-200        (número negativo = descuento)
-150.25     (descuento con decimales)
```

#### ❌ Incorrecto
```
-200.00.00  (formato inválido, múltiples puntos)
+ 500       (espacio entre signo y número)
$500        (símbolo de moneda)
1,000       (coma de miles)
```

### 4. Ejecutar Importación

1. En **QR Empleados**, clic en **"Carga Masiva de Saldos"**
2. Se abre un modal con:
   - Instrucciones breves
   - Campo para subir Excel
   - Toggle "Permitir múltiples cargas al mismo QR"
   - Advertencia sobre formato

3. Configurar opciones:
   - **Permitir múltiples cargas**: Si está activado, puedes tener varias filas para el mismo UUID (se procesan en orden)

4. Seleccionar archivo Excel

5. Clic en botón de acción

## Resultados

### Notificación Visual

Al finalizar la importación, verás una notificación con:

#### Importación Exitosa (sin errores)
```
¡Carga masiva exitosa!

✅ Procesados: 15
💰 Total Cargado: $12,500.00
💸 Total Descontado: $3,200.00
📊 Cambio Neto: $9,300.00

[Botón: Descargar Reporte Completo]
```

#### Importación con Errores
```
Importación completada con errores

✅ Procesados: 12
💰 Total Cargado: $10,000.00
💸 Total Descontado: $2,500.00
📊 Cambio Neto: $7,500.00
❌ Errores: 3

[Botón: Descargar Reporte Completo]
```

### Reporte Detallado Excel

El reporte contiene 3 hojas:

#### Hoja 1: Resumen
```
RESUMEN DE IMPORTACIÓN DE SALDOS

Fecha y Hora: 30/09/2025 22:45:30
Usuario: Juan Pérez

ESTADÍSTICAS
Total de QR Procesados: 12
Total de Errores: 3

MONTOS
Total Cargado (+): $10,000.00
Total Descontado (-): $2,500.00
Cambio Neto: $7,500.00

Total de Filas Procesadas: 15
```

#### Hoja 2: Detalle de Procesados
```
| Fila | ID Tarjeta  | Empleado      | Tipo      | Monto   | Saldo Anterior | Saldo Nuevo | Sucursal         |
|------|-------------|---------------|-----------|---------|----------------|-------------|------------------|
| 2    | EMCAD20005  | Juan Pérez    | Carga     | +500.00 | 1,000.00       | 1,500.00    | N/A              |
| 3    | EMCAD20006  | María López   | Descuento | -200.00 | 800.00         | 600.00      | Mochomos MTY     |
| 4    | EMCAD20007  | Carlos Ruiz   | Carga     | +1000.00| 0.00           | 1,000.00    | N/A              |
```

#### Hoja 3: Errores
```
| Fila | UUID                                 | Error                                | Monto | Descripción | Sucursal |
|------|--------------------------------------|--------------------------------------|-------|-------------|----------|
| 5    | 999e4567-e89b-12d3-a456-426614174000 | QR Empleado no encontrado            | +500  | Bono        |          |
| 8    | 234e5678-e89b-12d3-a456-426614174001 | Saldo insuficiente                   | -1000 | Descuento   | Centro   |
| 10   | 345e6789-e89b-12d3-a456-426614174002 | Sucursal es requerida para descuentos| -200  | Ajuste      |          |
```

## Validaciones y Errores Comunes

### Tipos de Error

| Error | Causa | Solución |
|-------|-------|----------|
| QR Empleado no encontrado | UUID no existe en el sistema | Verificar UUID desde el panel |
| QR Empleado inactivo | Tarjeta desactivada | Activar tarjeta antes de importar |
| Saldo insuficiente | Descuento mayor que saldo actual | Reducir monto o cargar saldo primero |
| Sucursal es requerida | Falta sucursal en descuento | Agregar nombre de sucursal |
| Sucursal no encontrada | Nombre de sucursal incorrecto | Usar nombre exacto del sistema |
| Monto inválido | Formato incorrecto del monto | Usar formato +500 o -200 |
| El monto no puede ser cero | Monto es 0 | Especificar monto diferente de cero |
| UUID duplicado | Múltiples cargas desactivadas | Activar opción o eliminar duplicados |

### Reglas de Validación

1. **UUID**:
   - Debe existir en la tabla `gift_cards`
   - Formato UUID válido (8-4-4-4-12 caracteres)

2. **Monto**:
   - Debe incluir signo (+ o -)
   - Formato numérico: `+500`, `-200.50`
   - No puede ser cero
   - Decimales permitidos (hasta 2 dígitos)

3. **Sucursal**:
   - Requerida SOLO para descuentos (montos negativos)
   - Debe existir en la tabla `branches`
   - Coincidencia exacta de nombre

4. **Saldo**:
   - Descuentos NO pueden dejar saldo negativo
   - El sistema valida antes de aplicar

## Características Avanzadas

### Múltiples Cargas al Mismo QR

Si activas **"Permitir múltiples cargas al mismo QR"**, puedes procesar varias operaciones para el mismo UUID:

```
| uuid                     | monto  | descripcion       | sucursal         |
|--------------------------|--------|-------------------|------------------|
| uuid-del-empleado-1      | +1000  | Carga inicial     |                  |
| uuid-del-empleado-1      | +500   | Bono productividad|                  |
| uuid-del-empleado-1      | -200   | Descuento comida  | Mochomos MTY     |
```

**Resultado**:
- Saldo inicial: $0.00
- Después de fila 1: $1,000.00
- Después de fila 2: $1,500.00
- Después de fila 3: $1,300.00

Las operaciones se ejecutan **en orden de aparición** en el Excel.

### Procesamiento en Chunks

El sistema procesa en lotes de 100 filas para optimizar memoria y rendimiento en archivos grandes.

### Generación Automática de Transacciones

Cada carga/descuento exitoso genera automáticamente:
- Un registro en la tabla `transactions`
- Con tipo: `credit` (carga) o `debit` (descuento)
- Balance anterior y posterior
- Usuario administrador que ejecutó la importación
- Sucursal (si aplica)
- Descripción

Estas transacciones aparecen en:
- Historial de la tarjeta individual
- Módulo de Transacciones
- Exportaciones de transacciones

## Ejemplos de Uso

### Ejemplo 1: Carga Mensual de Bonos

**Escenario**: Cargar $500 de bono mensual a 50 empleados

```
| uuid              | monto | descripcion           | sucursal |
|-------------------|-------|-----------------------|----------|
| uuid-empleado-1   | 500   | Bono mensual Enero    |          |
| uuid-empleado-2   | 500   | Bono mensual Enero    |          |
| ... (48 más)      | 500   | Bono mensual Enero    |          |
```

**Resultado**:
- 50 QR actualizados
- Total cargado: $25,000.00
- 50 transacciones generadas

### Ejemplo 2: Descuentos por Comidas

**Escenario**: Descontar consumo de comidas del día

```
| uuid              | monto  | descripcion          | sucursal         |
|-------------------|--------|----------------------|------------------|
| uuid-empleado-1   | -150.50| Comida 30/01         | Mochomos MTY     |
| uuid-empleado-2   | -200.00| Comida 30/01         | Mochomos MTY     |
| uuid-empleado-3   | -175.25| Comida 30/01         | Sucursal Centro  |
```

**Resultado**:
- 3 descuentos procesados
- Total descontado: $525.75
- Sucursales registradas en transacciones

### Ejemplo 3: Ajuste Mixto

**Escenario**: Ajuste de inventario mensual (cargas y descuentos)

```
| uuid              | monto   | descripcion              | sucursal         |
|-------------------|---------|--------------------------|------------------|
| uuid-empleado-1   | 500     | Ajuste inventario favor  |                  |
| uuid-empleado-2   | -100.50 | Ajuste inventario contra | Mochomos MTY     |
| uuid-empleado-3   | 250     | Compensación             |                  |
| uuid-empleado-4   | -50.25  | Corrección               | Sucursal Centro  |
```

**Resultado**:
- Total cargado: $750.00
- Total descontado: $150.75
- Cambio neto: $599.25

## Notas Importantes

- ✅ Las transacciones se procesan en **orden de aparición** en el Excel
- ✅ Cada operación se valida individualmente antes de ejecutarse
- ✅ Si una fila tiene error, las demás continúan procesándose
- ✅ El saldo se actualiza en tiempo real después de cada operación
- ✅ No se pueden revertir transacciones desde la importación (usar ajustes manuales)
- ✅ El reporte se genera automáticamente y está disponible para descarga
- ✅ Los reportes temporales se eliminan automáticamente después de la descarga

## Archivos Técnicos

### Clases Principales
- `App\Imports\BalanceImport` - Lógica de importación y validación
- `App\Exports\BalanceTemplateExport` - Plantilla con ejemplos reales
- `App\Exports\BalanceReportExport` - Reporte de resultados (3 hojas)
- `App\Services\TransactionService` - Gestión de transacciones

### Rutas
- `/download/balance-template` - Descargar plantilla
- `/download/balance-report/{file}` - Descargar reporte de resultados

### Tests
- `tests/Feature/BalanceImportTest.php` - Suite de 10 tests:
  - Importación de cargas positivas
  - Importación de descuentos negativos
  - Validación de sucursal en descuentos
  - Prevención de saldos negativos
  - Validación de UUID existente
  - Validación de QR activo
  - Múltiples cargas al mismo QR
  - Prevención de duplicados
  - Validación de sucursal existente
  - Cálculo de estadísticas

## Seguridad

- ✅ Solo usuarios autenticados pueden importar
- ✅ Se registra el usuario administrador en cada transacción
- ✅ Validación de permisos a nivel de Filament
- ✅ Todas las operaciones usan transacciones de base de datos (rollback automático en errores)
- ✅ No permite operaciones que dejen saldos negativos

## Soporte y Troubleshooting

### ¿Cómo obtengo el UUID de un QR Empleado?

1. Ve al módulo **QR Empleados**
2. Haz clic en el registro deseado
3. En el formulario de edición, el UUID aparece como "UUID" (primer campo)
4. Copia el UUID completo

### ¿Por qué mi importación tiene todos los montos como error?

Verifica el formato de los montos:
- ✅ `500` (número positivo para carga)
- ✅ `+500` (también válido para carga)
- ✅ `-200` (número negativo para descuento)
- ❌ `+ 500` (espacio entre signo y número)
- ❌ `$500` (símbolo de moneda)

### ¿Puedo importar más de 1000 registros?

Sí, el sistema procesa en chunks de 100. No hay límite técnico, pero se recomienda no exceder 5,000 por importación para mejor rendimiento.

### ¿Se pueden revertir las transacciones?

No automáticamente. Para revertir:
1. Crea un nuevo Excel con operaciones inversas
2. Ejemplo: Si cargaste +500, descuenta -500

O usa **Ajuste Manual** desde la tarjeta individual.
