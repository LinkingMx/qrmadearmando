# Manual de Testing - QR Made Armando

## Guia para Agente de Testing (Pruebas desde Navegador)

**URL Base**: `https://qrmadearmando.test`
**Admin Panel**: `https://qrmadearmando.test/admin`
**Credenciales Admin**: `armando.reyes@grupocosteno.com` / `password123`

---

## 1. Roles del Sistema

| Rol | Acceso | Descripcion |
|-----|--------|-------------|
| **super_admin** | Admin panel + Frontend | Control total del sistema |
| **BranchTerminal** | Frontend + Scanner | Cajero de sucursal con acceso a scanner |
| **Empleado (sin rol)** | Frontend (dashboard) | Solo puede ver su dashboard y transacciones |

---

## 2. Pruebas de Autenticacion

### 2.1 Login Frontend
1. Navegar a `https://qrmadearmando.test/login`
2. **Caso exitoso**: Ingresar email y password validos -> Redirige a `/dashboard`
3. **Caso fallido**: Password incorrecto -> Muestra error de validacion
4. **Rate limiting**: Intentar 5+ veces con password incorrecto -> Muestra mensaje de espera
5. **Usuario inactivo**: Si el admin desactiva la cuenta -> Login rechazado con mensaje "Tu cuenta ha sido desactivada"

### 2.2 Login Admin Panel
1. Navegar a `https://qrmadearmando.test/admin/login`
2. **Caso exitoso**: Credenciales de admin -> Accede al panel
3. **Sin permisos**: Usuario sin rol admin -> Acceso denegado
4. **Usuario inactivo**: Cuenta desactivada -> Redirige a login con mensaje

### 2.3 Two-Factor Authentication (2FA)
1. Login con usuario que tiene 2FA habilitado -> Redirige a `/two-factor-challenge`
2. Ingresar codigo TOTP valido -> Acceso concedido
3. Ingresar codigo invalido -> Error de verificacion
4. Usar recovery code -> Acceso concedido (code se consume)

### 2.4 Logout
1. Click en avatar/menu -> Opcion "Cerrar Sesion"
2. Verificar que redirige a `/login`
3. Intentar navegar a `/dashboard` -> Redirige a login

### 2.5 Password Reset
1. En login, click "Olvidaste tu contrasena"
2. Ingresar email -> Muestra mensaje generico (seguridad: no revela si email existe)
3. Revisar email (en dev: `php artisan pail` para ver logs)
4. Click en link del email -> Formulario de nueva password
5. Ingresar nueva password -> Redirige a login

---

## 3. Pruebas del Scanner QR (Rol: BranchTerminal)

### Prerequisitos
- Usuario con rol `BranchTerminal`
- Usuario asignado a una sucursal (`branch_id` no nulo)
- Al menos una gift card activa en el sistema

### 3.1 Acceso al Scanner
1. Login como BranchTerminal -> Ir a `/scanner`
2. **Sin sucursal**: Si el usuario no tiene `branch_id` -> Redirige a dashboard con error
3. Verificar que muestra nombre de sucursal y usuario en el header

### 3.2 Escaneo de QR (Lookup)
1. **Escaneo con camara**: Apuntar camara al QR -> Detecta codigo automaticamente
2. **Busqueda manual**: Ingresar legacy_id (ej: `EMCAD000001`) en campo de texto -> Click buscar
3. **QR valido activo**: Muestra tarjeta con balance, empleado, fecha de expiracion, imagen QR
4. **QR invalido**: Codigo que no existe -> Alerta "Tarjeta no encontrada"
5. **QR inactivo**: Tarjeta desactivada -> Alerta "Este QR esta inactivo"
6. **QR de otra marca/cadena/sucursal**: Validacion de scope -> Alerta de scope invalido

### 3.3 Validacion de Scope (Importante)
El sistema valida que la tarjeta pueda usarse en la sucursal del cajero:

| Scope | Regla | Ejemplo |
|-------|-------|---------|
| **Cadena** | Cualquier sucursal de la misma cadena | QR de Cadena A funciona en Sucursal A1, A2, A3 |
| **Marca** | Solo sucursales de la misma marca | QR de Marca X solo en sucursales de Marca X |
| **Sucursal** | Solo sucursales especificamente asignadas | QR asignado a Sucursal 1 solo funciona ahi |

**Pruebas de scope:**
1. Crear QR scope=cadena -> Probar en sucursal de la misma cadena (OK) y otra cadena (ERROR)
2. Crear QR scope=marca -> Probar en sucursal de la misma marca (OK) y otra marca (ERROR)
3. Crear QR scope=sucursal -> Probar en sucursal asignada (OK) y no asignada (ERROR)

### 3.4 Proceso de Debito
1. Despues del lookup exitoso, se muestra formulario de debito
2. Ingresar monto (ej: `50.00`) y referencia
3. **Debito exitoso**: Muestra modal de recibo con:
   - Sucursal, fecha, cajero
   - Tarjeta y empleado
   - Saldo anterior, descuento, saldo actual
   - Folio y referencia
4. **Saldo insuficiente**: Monto > balance -> Error "Saldo insuficiente"
5. **Monto invalido**: 0 o negativo -> Error de validacion

### 3.5 Impresion de Recibo
1. En el modal de recibo, click "Imprimir Ticket"
2. Se abre dialogo de impresion del navegador
3. Formato optimizado para impresora termica (80mm)
4. Click "Cerrar" -> Regresa a modo escaneo

### 3.6 Reimpresion de Recibo
1. En la seccion "Historial de Transacciones" debajo del scanner
2. Click "Reimprimir" en cualquier transaccion
3. Se abre modal con los datos de la transaccion
4. Click "Imprimir Ticket" -> Mismo comportamiento que recibo original

### 3.7 Historial de Transacciones de Sucursal
1. Scroll down en la pagina del scanner
2. Tabla con transacciones de la sucursal actual
3. **Paginacion**: Navegar entre paginas
4. **Datos mostrados**: Folio, fecha, empleado, tarjeta, monto
5. Solo muestra transacciones de la sucursal del usuario actual

---

## 4. Pruebas del Dashboard (Empleado)

### 4.1 Vista Principal
1. Login como empleado -> Redirige a `/dashboard`
2. Muestra historial de transacciones del empleado
3. Solo ve SUS propias transacciones

### 4.2 Sin Gift Card Asignada
1. Empleado sin tarjeta QR -> Dashboard muestra mensaje informativo

---

## 5. Pruebas del Admin Panel (`/admin`)

### 5.1 Gestion de Usuarios
1. **Listar**: `/admin/users` -> Tabla con todos los usuarios
2. **Crear**: Boton "Nuevo" -> Formulario con nombre, email, password, sucursal, rol
3. **Editar**: Click en usuario -> Modificar datos
4. **Activar/Desactivar**: Toggle en la lista -> Cambia `is_active`
   - Al desactivar: todas las gift cards del usuario se desactivan automaticamente
   - Al reactivar: todas las gift cards se reactivan
   - No puede desactivar su propia cuenta
5. **Importar usuarios**: Descargar plantilla Excel, llenar, subir
6. **Filtros**: Filtrar por activo/inactivo, buscar por nombre/email

### 5.2 Gestion de Gift Cards (QR)
1. **Listar**: `/admin/gift-cards` -> Tabla con todas las tarjetas
2. **Crear**: Seleccionar categoria, asignar usuario, balance inicial, scope, fecha expiracion
   - `legacy_id` se genera automaticamente con prefijo de categoria + 6 digitos
   - QR codes se generan automaticamente (UUID y legacy)
3. **Editar**: Modificar balance, usuario, status, scope
4. **Soft Delete**: Eliminar tarjeta (se puede restaurar)
5. **Force Delete**: Eliminar permanentemente (borra archivos QR)
6. **Carga masiva de saldos**: Descargar plantilla, llenar, subir
   - Soporta creditos (positivos) y debitos (negativos)
   - Validacion de saldo suficiente para debitos
   - Reporte de errores descargable

### 5.3 Gestion de Categorias
1. **Listar**: `/admin/gift-card-categories`
2. **Crear**: Nombre, prefijo (letras mayusculas, unico), naturaleza (metodo_pago/descuento)
3. **Editar**: Nombre editable siempre; prefijo y naturaleza solo si no hay QR asignados
4. **No se puede eliminar** si tiene gift cards asignadas

### 5.4 Gestion de Sucursales
1. **Listar**: `/admin/branches`
2. **Crear**: Nombre, marca asociada
3. **Editar**: Modificar nombre/marca
4. **NO se puede eliminar** (proteccion total)

### 5.5 Gestion de Cadenas y Marcas
1. **Cadenas** (`/admin/chains`): CRUD con marcas relacionadas
2. **Marcas** (`/admin/brands`): CRUD con cadena padre
3. Eliminacion bloqueada si tienen dependencias

### 5.6 Transacciones
1. **Listar**: `/admin/transactions` -> Solo lectura
2. Filtros por tipo (credit/debit/adjustment), fecha, gift card
3. No se pueden crear/editar/eliminar manualmente

### 5.7 Roles y Permisos (Shield)
1. **Listar roles**: `/admin/shield/roles`
2. Cada rol tiene permisos granulares por recurso
3. `super_admin` tiene acceso total automaticamente

---

## 6. Pruebas de Seguridad (desde Navegador)

### 6.1 Headers de Seguridad
1. Abrir DevTools (F12) -> Network -> Cualquier request
2. Verificar headers en Response:
   - `X-Content-Type-Options: nosniff`
   - `X-Frame-Options: SAMEORIGIN`
   - `Referrer-Policy: strict-origin-when-cross-origin`
   - `Permissions-Policy: camera=(self), microphone=(), geolocation=()`

### 6.2 CSRF Protection
1. Abrir DevTools -> Application -> Cookies
2. Verificar que existe cookie `XSRF-TOKEN`
3. Intentar POST sin token (cURL/Postman) -> 419 (Token Mismatch)

### 6.3 Path Traversal (Download Routes)
1. Intentar acceder a: `/download/import-errors/../../.env`
2. Debe retornar 404 (no el archivo .env)
3. Solo acepta caracteres: `[a-zA-Z0-9._-]`

### 6.4 Rate Limiting API
1. Hacer 30+ requests en 1 minuto a `/api/v1/public/gift-cards/search`
2. Despues de 30 -> Retorna 429 (Too Many Requests)

### 6.5 Acceso No Autorizado
1. Sin login, navegar a `/dashboard` -> Redirige a login
2. Sin login, navegar a `/scanner` -> Redirige a login
3. Sin login, navegar a `/admin` -> Redirige a admin login
4. Empleado sin BranchTerminal intenta `/scanner` -> Redirige a dashboard
5. Empleado intenta `/admin` -> Acceso denegado

### 6.6 Inertia Props (No Data Leakage)
1. Abrir DevTools -> Network -> Cualquier pagina con Inertia
2. Buscar en la respuesta JSON los props compartidos
3. Verificar que `auth.user` solo contiene: id, name, email, avatar, email_verified_at, two_factor_enabled, created_at, updated_at
4. NO debe contener: password, remember_token, two_factor_secret, two_factor_recovery_codes

---

## 7. Pruebas de PWA / Offline

### 7.1 Service Worker
1. Abrir DevTools -> Application -> Service Workers
2. Verificar que `sw-custom.js` esta registrado y activo
3. Verificar que el precache tiene ~40 entries

### 7.2 Push Notifications
1. En la app, aceptar permisos de notificacion
2. Desde admin, procesar una transaccion
3. Verificar que llega notificacion push (si VAPID keys configuradas)

---

## 8. Pruebas de Responsividad

### 8.1 Scanner
1. **Desktop** (>1024px): Layout 2 columnas (scanner + formulario)
2. **Tablet** (768-1024px): Layout adaptado
3. **Mobile** (<768px): Layout 1 columna, botones full-width
4. **Historial**: Desktop=tabla, Mobile=cards

### 8.2 Admin Panel
1. **Desktop**: Sidebar expandido
2. **Mobile**: Sidebar colapsado, hamburger menu

### 8.3 Recibo de Impresion
1. Verificar que el recibo se formatea para 80mm
2. Probar impresion en impresora termica si disponible

---

## 9. Flujo Completo E2E (Escenario Principal)

### Escenario: Cajero procesa descuento a empleado

1. **Admin** crea usuario empleado con email y password
2. **Admin** crea gift card con balance de $500, scope=cadena, asigna al empleado
3. **Admin** crea usuario cajero con rol BranchTerminal, asigna a sucursal
4. **Cajero** inicia sesion en el frontend
5. **Cajero** va a `/scanner`
6. **Cajero** escanea QR del empleado (o ingresa legacy_id manualmente)
7. Sistema muestra info de la tarjeta: empleado, balance $500
8. **Cajero** ingresa monto: $150, referencia: "Comida"
9. Sistema procesa debito, muestra recibo: saldo anterior $500, descuento -$150, saldo actual $350
10. **Cajero** imprime ticket
11. **Empleado** inicia sesion -> Dashboard muestra transaccion de -$150

### Escenario: Carga masiva de saldos

1. **Admin** va a `/admin/gift-cards`
2. Click "Cargar Saldos"
3. Descarga plantilla Excel
4. Llena plantilla con legacy_ids y montos
5. Sube el archivo
6. Sistema procesa y muestra resumen: X cargados, Y errores
7. Si hay errores, descarga reporte de errores

---

## 10. Checklist de Regresion Rapida

Ejecutar despues de cada deploy:

- [ ] Login/logout funciona
- [ ] Dashboard carga
- [ ] Scanner accesible para BranchTerminal
- [ ] Escaneo de QR funciona (camara o manual)
- [ ] Debito procesa correctamente
- [ ] Recibo se muestra y puede imprimirse
- [ ] Admin panel accesible
- [ ] CRUD de usuarios funciona
- [ ] CRUD de gift cards funciona
- [ ] Carga masiva de saldos funciona
- [ ] Headers de seguridad presentes
- [ ] 2FA funciona

---

## 11. Comandos de Testing Automatizado

```bash
# Correr todos los tests (271 tests)
composer test

# Tests especificos
vendor/bin/pest tests/Feature/Api/          # API tests
vendor/bin/pest tests/Feature/Auth/         # Auth tests
vendor/bin/pest tests/Feature/ScannerFlowTest.php  # Scanner E2E
vendor/bin/pest tests/Feature/TransactionTest.php  # Transacciones

# Con cobertura
vendor/bin/pest --coverage

# Modo watch (re-ejecuta al cambiar archivos)
vendor/bin/pest --watch
```
