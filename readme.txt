=== Código8 – Event Check-in Manager ===
Contributors: codigo8
Donate link: https://codigo8.com
Tags: eventos, check-in, check-out, invitados, qr, csv, organizacion, gestión de eventos, attendance, guest management
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 2.2.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sistema completo para la gestión de invitados con control de acceso mediante QR, check-out/re-ingreso, filtros avanzados y generación de códigos QR integrada.

== Descripción ==

**Código8 – Event Check-in Manager v2.2.0** es un sistema profesional y robusto para la gestión completa de invitados en eventos. Diseñado específicamente para eventos empresariales, institucionales y sociales que requieren control de acceso preciso y en tiempo real.

**🚀 Características Principales:**

**✅ Check-in/Check-out Avanzado**
- Sistema completo de registro de entrada, salida y re-ingreso
- Control total del flujo de invitados
- Historial completo de movimientos con timestamps
- Registro detallado del operador en cada acción

**🔍 Búsqueda Inteligente Multidimensional**
- Búsqueda unificada por ticket, nombre y organización
- Filtrado exacto por mesa asignada
- Filtros por evento y estado de check-in
- Resultados instantáneos con AJAX

**📱 Interfaz 100% Responsive**
- Tablas optimizadas para móviles y desktop
- Columna Evento oculta automáticamente en móviles
- Navegación intuitiva y fluida
- Diseño moderno y profesional

**🎯 Gestión Multi-evento**
- Administración centralizada de múltiples eventos
- Taxonomía flexible de eventos
- Separación total de invitados por evento
- Shortcodes específicos por evento

**🔐 Seguridad y Control de Acceso**
- Menú visible solo para administradores
- Protección contra accesos no autorizados
- Capacidades granulares de usuario
- Nonce verification en todas las operaciones

**📊 Herramientas Profesionales**
- Importación/exportación CSV con múltiples separadores
- Selector de separadores (coma, punto y coma, pipe, tabulador)
- Campos personalizados para observaciones privadas
- Filas clickeables para acceso rápido al check-in

**🎪 Generación de Códigos QR Integrada**
- Generación nativa de QR sin dependencias externas
- Tablas de QR personalizables (rango, columnas, tamaño)
- QR individuales por ticket
- URLs automáticas con formato estándar

**⚡ Performance Optimizado**
- Código modular y eficiente
- Arquitectura basada en principios SOLID
- Consultas AJAX optimizadas
- Caché nativo de WordPress

== Instalación ==

1. **Subir el plugin**: Descargá el archivo `codigo8-event-checkin-manager.zip` y subilo desde el panel de administración de WordPress → Plugins → Añadir nuevo → Subir plugin.

2. **Activar el plugin**: Hacé click en "Activar".

3. **Configurar eventos**: El menú **"Invitados"** aparecerá solo para usuarios Administrador. Creá tus eventos desde "Invitados > Eventos".

4. **Cargar invitados**: Usá "Invitados > Importar/Exportar" para cargar tu lista de invitados via CSV.

5. **Insertar shortcodes**: Agregá los shortcodes en tus páginas para habilitar el sistema de check-in.

== Uso ==

**📋 Flujo de Trabajo Recomendado:**

1. **Configurar eventos**: Creá los eventos en "Invitados > Eventos"
2. **Cargar invitados**: Usá "Invitados > Importar/Exportar" con archivo CSV
   - Formato: `titulo,nombre,organizacion,mesa,evento,observaciones,checkin`
   - Elegí el separador según tu región (punto y coma para Excel en español)
3. **Generar códigos QR**: Usá los shortcodes de QR para imprimir las entradas
4. **Páginas de check-in**: 
   - Creá una página por evento con slug igual al evento (ej: `cena-2025`)
   - Insertá `[c8ecm_checkin]` para el check-in individual por QR
5. **Panel de control**: 
   - Creá una página con `[c8ecm_list]` para búsqueda y control manual
   - Acceso restringido al personal autorizado

**🎯 Funcionalidades de Check-in/Check-out:**

- **Check-in**: Registra el primer ingreso del invitado
- **Check-out**: Marca la salida temporal (habilitado para eventos con re-ingreso)
- **Re-ingreso**: Permite volver a ingresar después de un check-out

== Shortcodes ==

**🎪 Check-in Individual**
`[c8ecm_checkin event="nombre-evento"]`
- Panel individual de check-in/check-out por ticket
- Para acceso con códigos QR
- Detecta automáticamente el evento desde el slug de la página

**📊 Lista de Invitados**
`[c8ecm_list event="nombre-evento"]`
- Tabla completa con búsqueda, filtros y control manual
- Búsqueda en tiempo real por ticket, nombre y organización
- Filas clickeables que abren la página de check-in
- Ideal para tablets del personal de control

**🔢 Generador de Tablas QR**
`[c8ecm_qr_table event="cena-2025" start="1" end="100" columns="4" size="150"]`
- Genera tablas completas de códigos QR
- **event**: Slug del evento (opcional)
- **start**: Número inicial de tickets (default: 1)
- **end**: Número final de tickets (default: 100)
- **columns**: Columnas por fila (default: 4)
- **size**: Tamaño del QR en píxeles (default: 150)

**🎫 QR Individual**
`[c8ecm_qr_single event="cena-2025" ticket="50" size="200"]`
- Genera un código QR individual
- **event**: Slug del evento (opcional)
- **ticket**: Número de ticket
- **size**: Tamaño del QR en píxeles (default: 200)

== Campos Personalizados ==

Cada invitado incluye los siguientes campos:

- **Ticket** (título, único por evento)
- **Nombre completo** (meta field)
- **Organización** (meta field)
- **Mesa asignada** (meta field)
- **Evento** (taxonomía)
- **Observaciones** (privadas, meta field)
- **Estado check-in/check-out** (meta fields)
- **Timestamp de operaciones** (meta fields)
- **Registro de operador** (meta fields)

== Changelog ==

= 2.2.0 =
- **REFACTOR**: Reestructuración completa del código en arquitectura modular
- **NUEVO**: Sistema nativo de generación de códigos QR
- **NUEVO**: Shortcodes [c8ecm_qr_table] y [c8ecm_qr_single]
- **MEJORA**: Implementación de principios SOLID y DRY
- **MEJORA**: Separación de responsabilidades en clases especializadas
- **MEJORA**: Sistema de helpers functions reutilizables
- **MEJORA**: Manejo consistente de errores y validaciones
- **MEJORA**: Código más mantenible y extensible
- **OPTIMIZACIÓN**: Mejor performance y eficiencia de consultas

= 2.1.1 =
- **FIX**: Corrección completa del sistema de búsqueda (ticket, nombre, organización)
- **FIX**: Variable no definida en función de guardado de metabox
- **UX**: Filas clickeables ahora abren en la misma pestaña
- **PERFORMANCE**: Implementación de campo c8_ticket para búsquedas eficientes
- **CODE**: Optimización del meta_query en búsquedas AJAX

= 2.1.0 =
- **NEW**: Sistema completo de check-out y re-ingreso
- **NEW**: Registro detallado del operador en todas las acciones
- **NEW**: Filas clickeables en listas que abren página de check-in
- **NEW**: Selector de separadores CSV en importación
- **IMPROVED**: Filtros de búsqueda optimizados (coincidencia exacta en mesa)
- **IMPROVED**: Ordenamiento por organización en listas
- **IMPROVED**: Interfaz responsive (oculta columna Evento en móviles)
- **IMPROVED**: Control de acceso restringido solo a administradores
- **FIX**: Corrección en actualización de invitados existentes durante importación

= 2.0.0 =
- Shortcode `[c8ecm_list]` para búsqueda y check-in manual en frontend
- Check-in instantáneo con AJAX sin recargar página
- Campos con clases CSS personalizadas para fácil estilización
- Importador CSV con opción de actualización de invitados existentes
- Exportador CSV limpio sin HTML residual
- Compatibilidad con múltiples eventos mediante taxonomía
- Optimización visual para pantallas móviles

= 1.0.0 =
- Versión inicial con funcionalidades básicas de check-in
- Soporte para eventos múltiples
- Importador y exportador CSV integrados

== Preguntas Frecuentes ==

= ¿Puedo usar el plugin para eventos con re-ingreso? =
Sí, la versión 2.1.0 incluye sistema completo de check-out y re-ingreso. Los invitados pueden salir y volver a ingresar manteniendo el historial completo de movimientos.

= ¿Cómo restringir el acceso al personal no autorizado? =
El menú "Invitados" solo es visible para usuarios con rol Administrador. Las páginas con shortcodes pueden protegerse con restricciones de Elementor o plugins de membresía.

= ¿Qué formato de CSV debo usar para importar? =
El encabezado debe ser exacto: `titulo,nombre,organizacion,mesa,evento,observaciones,checkin`
Podés elegir el separador (coma, punto y coma, pipe o tabulador) durante la importación.

= ¿Cómo generar los códigos QR? =
Podés usar los shortcodes [c8ecm_qr_table] para generar tablas completas o [c8ecm_qr_single] para QR individuales. Las URLs siguen el formato: `https://tudominio.com/slug-evento/?ticket=NUMERO`

= ¿El plugin requiere algún servicio externo para los QR? =
No, la versión 2.2.0 incluye generación nativa de QR usando Google Charts API, sin dependencias externas ni plugins adicionales.

= ¿Puedo personalizar el diseño de las tablas? =
Sí, todos los elementos tienen clases CSS específicas que podés sobrescribir en tu tema. Consultá la sección de Campos Personalizados para ver las clases disponibles.

= ¿El plugin es compatible con caché? =
Sí, está optimizado para trabajar con sistemas de caché. Las operaciones de check-in usan AJAX para evitar conflictos con el caché de páginas.

== Casos de Uso ==

**🏢 Eventos Corporativos**
- Conferencias y convenciones
- Lanzamientos de producto
- Reuniones ejecutivas
- Eventos de networking

**🎉 Eventos Sociales**
- Bodas y recepciones
- Fiestas de cumpleaños
- Eventos familiares
- Celebraciones privadas

**🏛️ Eventos Institucionales**
- Ceremonias de graduación
- Actos oficiales
- Inauguraciones
- Eventos gubernamentales

**🏟️ Eventos Deportivos**
- Competencias y torneos
- Maratones y carreras
- Eventos deportivos masivos
- Ceremonias de premiación

== Créditos ==
Desarrollado por **Código8** – Agencia de Desarrollo Web en Argentina.  
Visitanos en [https://codigo8.com](https://codigo8.com)

== Soporte ==
Para soporte técnico visitá nuestro sitio web o contactanos a través de [https://codigo8.com/contacto](https://codigo8.com/contacto)

¿Encontraste un bug o tenés una sugerencia? ¡Abrí un issue en nuestro repositorio!