# Configuración del Sistema de Chat de Soporte

## Descripción
Se ha implementado un sistema completo de chat de soporte que permite a los usuarios comunicarse con administradores. El sistema incluye:

- Página de soporte para usuarios (`support.php`)
- Chat en tiempo real (`chat.php`)
- Panel de administración para el chat (`admin-chat.php`)
- APIs para manejar mensajes y conversaciones
- Integración con el panel de administración existente

## Archivos Creados

### Páginas Principales
- `support.php` - Página principal de soporte para usuarios
- `chat.php` - Interfaz de chat para usuarios
- `admin-chat.php` - Panel de chat para administradores

### APIs
- `api/create-conversation.php` - Crear nuevas conversaciones
- `api/send-message.php` - Enviar mensajes (usuarios)
- `api/get-messages.php` - Obtener mensajes (usuarios)
- `api/get-conversation-info.php` - Obtener info de conversación (admin)
- `api/get-conversation-messages.php` - Obtener mensajes (admin)
- `api/admin-send-message.php` - Enviar mensajes (admin)
- `api/close-conversation.php` - Cerrar conversaciones
- `api/get-new-messages.php` - Obtener nuevos mensajes (admin)

### Base de Datos
- `database/chat_tables.sql` - Script para crear las tablas necesarias

## Configuración

### 1. Crear las Tablas de Base de Datos
Ejecuta el archivo `database/chat_tables.sql` en tu base de datos MySQL:

```sql
-- Ejecutar en phpMyAdmin o tu cliente MySQL preferido
source database/chat_tables.sql;
```

### 2. Verificar Permisos de Administrador
Asegúrate de que el usuario administrador tenga el rol 'admin' en la tabla `users`. Si no existe el campo `role`, puedes agregarlo:

```sql
ALTER TABLE users ADD COLUMN role ENUM('user', 'admin') DEFAULT 'user';
UPDATE users SET role = 'admin' WHERE id = 1; -- Asumiendo que el ID 1 es el admin
```

### 3. Verificar Configuración de Sesiones
Asegúrate de que las sesiones estén configuradas correctamente en `config/config.php`.

## Funcionalidades

### Para Usuarios
1. **Acceso al Soporte**: Los usuarios pueden acceder desde el footer → "Soporte"
2. **Crear Conversación**: Pueden iniciar una nueva conversación con asunto y mensaje
3. **Chat en Tiempo Real**: Interfaz de chat con actualización automática cada 3 segundos
4. **Historial**: Ver todas sus conversaciones anteriores

### Para Administradores
1. **Panel de Chat**: Acceso desde el panel de administración → pestaña "Soporte"
2. **Gestión de Conversaciones**: Ver todas las conversaciones con estado y mensajes sin leer
3. **Chat en Tiempo Real**: Responder a usuarios con actualización automática cada 5 segundos
4. **Cerrar Conversaciones**: Marcar conversaciones como resueltas

## Características Técnicas

### Seguridad
- Verificación de autenticación en todas las APIs
- Verificación de permisos de administrador
- Validación de datos de entrada
- Protección contra acceso no autorizado a conversaciones

### Rendimiento
- Polling optimizado (3s para usuarios, 5s para admins)
- Consultas SQL optimizadas con índices
- Transacciones para operaciones críticas

### UX/UI
- Interfaz moderna y responsive
- Indicadores de mensajes sin leer
- Estados de conversación (abierta/cerrada)
- Auto-scroll en el chat
- Soporte para Enter para enviar mensajes

## Estructura de Base de Datos

### Tabla: support_conversations
- `id` - ID único de la conversación
- `user_id` - ID del usuario que inició la conversación
- `subject` - Asunto de la conversación
- `status` - Estado (open/closed)
- `created_at` - Fecha de creación
- `updated_at` - Fecha de última actualización

### Tabla: support_messages
- `id` - ID único del mensaje
- `conversation_id` - ID de la conversación
- `sender_id` - ID del remitente
- `sender_type` - Tipo de remitente (user/admin)
- `message` - Contenido del mensaje
- `is_read` - Estado de lectura
- `created_at` - Fecha de creación

### Tabla: chat_notifications
- `id` - ID único de la notificación
- `user_id` - ID del usuario
- `conversation_id` - ID de la conversación
- `message_id` - ID del mensaje
- `is_read` - Estado de lectura
- `created_at` - Fecha de creación

## Notas Importantes

1. **Compatibilidad**: El sistema es compatible con la estructura existente del proyecto
2. **Modificaciones Mínimas**: Solo se modificó el footer para cambiar "Contacto" por "Soporte"
3. **Escalabilidad**: El sistema está diseñado para manejar múltiples conversaciones simultáneas
4. **Mantenimiento**: Las conversaciones cerradas se mantienen para historial

## Solución de Problemas

### Error de Conexión a Base de Datos
- Verificar configuración en `config/database.php`
- Asegurar que las tablas existen

### Error de Permisos
- Verificar que el usuario tenga rol 'admin'
- Revisar configuración de sesiones

### Chat No Actualiza
- Verificar que JavaScript esté habilitado
- Revisar consola del navegador para errores
- Verificar que las APIs respondan correctamente

## Próximas Mejoras Sugeridas

1. **Notificaciones Push**: Implementar notificaciones en tiempo real
2. **Archivos Adjuntos**: Permitir envío de imágenes y documentos
3. **Categorización**: Agregar categorías a las conversaciones
4. **Respuestas Automáticas**: Sistema de respuestas automáticas
5. **Métricas Avanzadas**: Estadísticas detalladas de soporte 