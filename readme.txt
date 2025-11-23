=== Código8 – Event Check-in Manager ===
Contributors: codigo8
Donate link: https://codigo8.com
Tags: eventos, check-in, invitados, qr, csv, organizacion
Requires at least: 5.8
Tested up to: 6.6
Stable tag: 2.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Sistema completo para la gestión de invitados con control de acceso mediante QR, ideal para eventos empresariales, institucionales o sociales.

== Descripción ==

**Código8 – Event Check-in Manager** permite gestionar listas de invitados con control de ingreso por QR, búsqueda desde el frontend y administración centralizada en WordPress.  
Permite manejar múltiples eventos (por ejemplo, *evento-2025*, *evento-2026*) y realizar check-in con actualización instantánea mediante AJAX.

Ideal para eventos gratuitos o con invitaciones personalizadas.

**Características principales:**
- Múltiples eventos gestionados por taxonomía (“Evento”).
- Shortcodes para check-in y búsqueda en frontend:  
  `[c8ecm_checkin]` y `[c8ecm_list]`.
- Check-in instantáneo con AJAX (sin recargar la página).
- Importador y exportador CSV con opción para actualizar invitados existentes.
- Exportación limpia (sin etiquetas HTML).
- Campos personalizados: nombre, organización, mesa, observaciones, evento.
- Registro del usuario que realizó el check-in.
- Permisos de gestión reservados a Administradores.
- Diseño optimizado para dispositivos móviles.
- Clases CSS personalizadas para facilitar la personalización visual.

== Instalación ==

1. Subí el archivo `codigo8-event-checkin-manager.zip` desde el panel de administración de WordPress → Plugins → Añadir nuevo → Subir plugin.  
2. Activá el plugin.  
3. En el menú lateral aparecerá **“Invitados”**, donde podrás importar, filtrar y exportar la lista.  
4. Accedé al check-in mediante la URL del evento (por ejemplo `/evento-2025/?ticket=123`).  
5. Insertá los shortcodes en tus páginas para habilitar el acceso al sistema de check-in.

== Uso ==

1. Cargá los invitados desde “Invitados > Importar/Exportar” usando un archivo CSV.  
   - Formato CSV: `ticket,nombre,organizacion,mesa,evento,observaciones`.  
   - Marcá “Actualizar existentes” si querés sobreescribir datos.  
2. Insertá el shortcode `[c8ecm_checkin]` en la página de check-in (por QR o búsqueda manual).  
3. Insertá el shortcode `[c8ecm_list]` en una página separada para listar, buscar y filtrar invitados.  
4. Desde el panel “Invitados” podrás exportar los resultados, filtrar por evento, organización o estado, y revisar observaciones.  

== Shortcodes ==

- `[c8ecm_checkin]` → muestra el panel de check-in con botón de ingreso y campo de observaciones.  
- `[c8ecm_list]` → muestra la tabla con búsqueda, filtros y control manual de ingreso.

== Changelog ==

= 2.0 =
- Añadido shortcode `[c8ecm_list]` para búsqueda y check-in manual en frontend.  
- Mejora AJAX en `[c8ecm_checkin]` (check-in instantáneo sin recargar).  
- Campos con clases CSS personalizadas para fácil estilización.  
- Importador CSV con opción de actualización de invitados existentes.  
- Exportador CSV limpio, sin HTML residual.  
- Permisos administrativos restringidos a roles de Administrador.  
- Compatibilidad con múltiples eventos mediante taxonomía “Evento”.  
- Optimización visual para pantallas móviles.

= 1.0.0 =
- Versión inicial.  
- Soporte para eventos múltiples y control de check-in básico.  
- Importador y exportador CSV integrados.  
- Interfaz adaptada a móviles.  

== Créditos ==
Desarrollado por **Código8** – Agencia de Desarrollo Web en Argentina.  
Visitanos en [https://codigo8.com](https://codigo8.com)
