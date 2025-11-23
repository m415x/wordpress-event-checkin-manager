=== Código8 – Event Check-in Manager ===
Contributors: codigo8
Donate link: https://codigo8.com
Tags: eventos, check-in, check-out, invitados, qr, csv, organizacion, gestión de eventos
Requires at least: 5.8
Tested up to: 6.8
Stable tag: 2.1.1
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sistema completo para la gestión de invitados con control de acceso mediante QR, check-out/re-ingreso y filtros avanzados.

== Descripción ==

**Código8 – Event Check-in Manager v2.1.1** es un sistema robusto para gestión de invitados con funcionalidades avanzadas de check-in y check-out. Ideal para eventos empresariales, institucionales o sociales que requieren control de acceso preciso.

**Características principales:**
- ✅ **Check-in/Check-out avanzado**: Registro de entrada, salida y re-ingreso de invitados
- 👤 **Registro de operador**: Control completo de quién realizó cada operación
- 🎯 **Búsqueda inteligente**: Búsqueda unificada por ticket, nombre y organización
- 📱 **Interfaz responsive**: Tablas optimizadas para móviles (columna Evento oculta en pantallas pequeñas)
- 🔐 **Control de acceso**: Menú visible solo para administradores
- 📊 **Listas interactivas**: Filas clickeables que abren la página de check-in del invitado en la misma pestaña
- 🔄 **Re-ingreso habilitado**: Permite registrar salidas y nuevos ingresos del mismo invitado
- 📋 **Importación flexible**: Selector de separadores CSV (coma, punto y coma, pipe, tabulador)
- 🎪 **Múltiples eventos**: Gestión centralizada con taxonomía de eventos
- ⚡ **AJAX instantáneo**: Operaciones sin recarga de página

== Instalación ==

1. Subí el archivo `codigo8-event-checkin-manager-v2.1.1.zip` desde el panel de administración de WordPress → Plugins → Añadir nuevo → Subir plugin.  
2. Activá el plugin.  
3. En el menú lateral aparecerá **“Invitados”**, donde podrás importar, filtrar y exportar la lista.  
4. Accedé al check-in mediante la URL del evento (por ejemplo `/nombre_del_evento/?ticket=123`).  
5. Insertá los shortcodes en tus páginas para habilitar el acceso al sistema de check-in.

== Uso ==

1. Cargá los invitados desde “Invitados > Importar/Exportar” usando un archivo CSV.  
   - Formato CSV: `ticket,nombre,organizacion,mesa,evento,observaciones,checkin`.  
   - Marcá “Actualizar existentes” si querés sobreescribir datos.  
2. Insertá el shortcode `[c8ecm_checkin event="nombre_del_evento"]` en la página de check-in (por QR o búsqueda manual).  
3. Insertá el shortcode `[c8ecm_list event="nombre_del_evento"]` en una página separada para listar, buscar y filtrar invitados.  
4. Desde el panel “Invitados” podrás exportar los resultados, filtrar por evento, organización o estado, y revisar observaciones.  

== Shortcodes ==

- `[c8ecm_checkin event="nombre_del_evento"]` → muestra el panel de check-in con botón de ingreso y campo de observaciones.  
- `[c8ecm_list event="nombre_del_evento"]` → muestra la tabla con búsqueda, filtros y control manual de ingreso.

== Changelog ==

= 2.1.1 =
- **FIX**: Corrección completa del sistema de búsqueda (ahora busca correctamente por ticket, nombre y organización)
- **FIX**: Variable no definida en función de guardado de metabox
- **MEJORA**: Filas clickeables ahora abren en la misma pestaña en lugar de nueva ventana
- **MEJORA**: Implementación de campo c8_ticket para búsquedas más eficientes
- **MEJORA**: Optimización del meta_query en búsquedas AJAX

= 2.1.0 =
- **NUEVO**: Sistema completo de check-out y re-ingreso
- **NUEVO**: Registro detallado del operador en todas las acciones
- **NUEVO**: Filas clickeables en listas que abren página de check-in
- **NUEVO**: Selector de separadores CSV en importación
- **MEJORA**: Filtros de búsqueda optimizados (coincidencia exacta en mesa)
- **MEJORA**: Ordenamiento por organización en listas
- **MEJORA**: Interfaz responsive (oculta columna Evento en móviles)
- **MEJORA**: Control de acceso restringido solo a administradores
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

== Preguntas frecuentes ==

= ¿Por qué no encuentra invitados al buscar por ticket? =
En la versión 2.1.1 se ha corregido completamente el sistema de búsqueda. Ahora busca simultáneamente en ticket, nombre y organización.

= ¿Las filas clickeables abren en nueva pestaña? =
No, desde la versión 2.1.1 las filas clickeables abren en la misma pestaña para mejor experiencia de usuario.

= ¿Cómo restringir el acceso al personal no autorizado? =
El menú "Invitados" solo es visible para usuarios con rol Administrador. Las páginas con shortcodes pueden protegerse con restricciones de Elementor o plugins de membresía.

= ¿Qué formato de CSV debo usar para importar? =
El encabezado debe ser exacto: `titulo,nombre,organizacion,mesa,evento,observaciones,checkin`
Podés elegir el separador según tu región durante la importación.

= ¿Cómo generar los códigos QR? =
Podés usar cualquier generador de QR que cree URLs con el formato: `https://tudominio.com/slug-evento/?ticket=NUMERO`

== Créditos ==
Desarrollado por **Código8** – Agencia de Desarrollo Web en Argentina.  
Visitanos en [https://codigo8.com](https://codigo8.com)

== Soporte ==
Para soporte técnico visitá nuestro sitio web o contactanos a través de [https://codigo8.com/contacto](https://codigo8.com/contacto)