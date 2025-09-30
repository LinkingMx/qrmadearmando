# Importación Masiva de Usuarios

Sistema de importación masiva de usuarios desde Excel con soporte para fotografías mediante ZIP o URLs directas.

## Características

### Modos de Importación
1. **Excel + ZIP**: Sube un archivo Excel con datos y un ZIP con fotografías
2. **Excel con URLs**: El Excel contiene URLs directas a las fotografías
3. **Sin fotos**: Solo importa los datos sin fotografías

### Datos Soportados
- **Nombre** (requerido)
- **Email** (requerido, único)
- **Contraseña** (opcional - se genera automáticamente si se omite)
- **Sucursal** (opcional - nombre o ID)
- **Foto** (opcional - nombre de archivo o URL)

## Uso

### 1. Descargar Plantilla
En el módulo de Usuarios, hacer clic en **"Descargar Plantilla"** para obtener el archivo Excel de ejemplo con el formato correcto.

### 2. Preparar Datos

#### Estructura del Excel
```
| nombre              | email                  | contrasena  | sucursal         | foto                |
|---------------------|------------------------|-------------|------------------|---------------------|
| Juan Pérez García   | juan.perez@empresa.com | Password123 | Sucursal Centro  | juan.perez.jpg      |
| María López         | maria.lopez@empresa.com|             | Sucursal Norte   | maria.jpg           |
| Carlos Rodríguez    | carlos@empresa.com     |             |                  | https://url.com/c.jpg|
```

#### Columnas
- **nombre**: Nombre completo del usuario
- **email**: Correo electrónico (único en el sistema)
- **contrasena**: Contraseña deseada (dejar vacío para generar automáticamente)
- **sucursal**: Nombre de la sucursal (debe existir en el sistema)
- **foto**: Nombre del archivo en el ZIP o URL directa

### 3. Preparar Fotografías (Modo ZIP)

Si usas el modo ZIP, crea un archivo ZIP con las fotos. Los nombres de archivo deben coincidir con la columna "foto" del Excel.

**Formatos aceptados**: JPG, JPEG, PNG, GIF
**Tamaño máximo**: 5MB por foto

**Estrategias de coincidencia automática**:
El sistema intenta encontrar fotos usando:
1. Nombre exacto del archivo especificado
2. Nombre de usuario del email (antes del @)
3. Nombre completo convertido a slug

Ejemplo:
- Email: `juan.perez@empresa.com`
- El sistema busca: `juan.perez.jpg`, `juan.perez.png`, etc.

### 4. Ejecutar Importación

1. En el módulo Usuarios, clic en **"Importar Usuarios"**
2. Seleccionar archivo Excel
3. Elegir modo de fotos:
   - **Subir ZIP con fotos**: Seleccionar archivo ZIP
   - **URLs en el Excel**: Las URLs deben estar en la columna "foto"
   - **Sin fotos**: No se importarán fotografías
4. Opcionalmente activar **"Actualizar usuarios existentes"** para modificar usuarios que ya existen (por email)
5. Clic en **"Importar"**

## Resultados de la Importación

### Importación Exitosa
El sistema muestra:
- Cantidad de usuarios creados
- Cantidad de usuarios actualizados (si aplica)

### Contraseñas Generadas
Si se generaron contraseñas automáticas:
- Aparece notificación con botón para **"Descargar Contraseñas"**
- El archivo contiene: Nombre, Email, Contraseña Generada
- **IMPORTANTE**: Guardar este archivo, las contraseñas no se pueden recuperar después

### Errores
Si hay errores en la importación:
- El sistema continúa importando las filas válidas
- Muestra cantidad de errores encontrados
- Botón para **"Descargar Reporte de Errores"**
- El reporte contiene: Fila, Error, Datos intentados

### Tipos de Errores Comunes
- Email duplicado (si no está activada la actualización)
- Nombre o email vacío
- Email con formato inválido
- Sucursal no existe en el sistema
- Foto no encontrada en el ZIP

## Validaciones

### Automáticas
- Email único (no puede haber duplicados activos)
- Email con formato válido
- Tamaño de foto máximo 5MB
- Formatos de foto: jpg, jpeg, png, gif
- Sucursal debe existir en el sistema

### Comportamiento Especial
- **Usuarios eliminados**: Si un usuario fue eliminado (soft delete) y se intenta importar de nuevo, se restaura y actualiza
- **Contraseñas**: Si se deja vacía, se genera automáticamente (12 caracteres alfanuméricos)
- **Sucursal**: Puede especificarse por nombre o por ID
- **Fotos**: Si no se encuentra, el usuario se crea sin foto (no genera error)

## Archivos Técnicos

### Clases Principales
- `App\Imports\UsersImport` - Lógica de importación y validación
- `App\Services\UserImportService` - Manejo de fotografías (extracción ZIP, descarga URLs)
- `App\Exports\UsersTemplateExport` - Generación de plantilla Excel

### Rutas
- `/download/users-template` - Descargar plantilla
- `/download/import-errors/{file}` - Descargar reporte de errores
- `/download/import-passwords/{file}` - Descargar contraseñas generadas

### Tests
- `tests/Feature/UserImportTest.php` - Suite de 5 tests:
  - Importación básica sin fotos
  - Validación de campos requeridos
  - Prevención de duplicados
  - Actualización de usuarios existentes
  - Asignación de sucursales

## Ejemplo de Flujo Completo

### Importar 50 Usuarios Nuevos con Fotos

1. **Preparar Excel** (`usuarios.xlsx`):
   ```
   nombre, email, contrasena, sucursal, foto
   Juan Pérez, juan@empresa.com, , Sucursal Centro, juan.jpg
   María López, maria@empresa.com, Pass123, Sucursal Norte, maria.png
   ... (48 usuarios más)
   ```

2. **Preparar ZIP** (`fotos.zip`):
   ```
   juan.jpg
   maria.png
   ... (48 fotos más)
   ```

3. **Importar**:
   - Seleccionar `usuarios.xlsx`
   - Modo: "Subir ZIP con fotos"
   - Seleccionar `fotos.zip`
   - Clic en "Importar"

4. **Resultado**:
   - ✅ 50 usuarios creados
   - 📥 Descargar contraseñas generadas (para Juan y los que no tenían contraseña)
   - Las fotos se almacenan en `storage/app/public/avatars/`

## Notas Importantes

- Las fotos se almacenan permanentemente en `storage/app/public/avatars/`
- Los archivos temporales (ZIP, reportes) se limpian automáticamente
- La importación procesa en lotes de 100 filas para optimizar memoria
- Las contraseñas generadas son aleatorias de 12 caracteres
- Los usuarios pueden cambiar su contraseña después del primer login
- La importación requiere autenticación (solo usuarios logueados)
