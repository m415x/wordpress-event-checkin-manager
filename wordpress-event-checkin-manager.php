<?php
/**
 * Plugin Name: Código8 – Event Check-in Manager
 * Description: Sistema completo de gestión de invitados con check-in/check-out por QR, múltiples eventos, importación/exportación CSV y control de acceso por roles.
 * Plugin URI: https://codigo8.com/download/event-checkin-manager/
 * Version: 2.2.0
 * Author: Código8
 * Author URI: https://codigo8.com
 * Requires PHP: 7.4
 * Requires at least: 5.8
 * Text Domain: codigo8-event-checkin-manager
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @package C8ECM
 * @category Core
 *
 * Código8 – Event Check-in Manager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * Código8 – Event Check-in Manager is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 */

if (!defined('ABSPATH')) exit;

define('C8ECM_PATH', plugin_dir_path(__FILE__));
define('C8ECM_URL', plugin_dir_url(__FILE__));

/**
 * AUTOLOAD de clases
 */
spl_autoload_register(function($class) {
    $prefix = 'C8ECM_';
    $base_dir = C8ECM_PATH . 'includes/';

    if (strpos($class, $prefix) !== 0) return;

    $file = $base_dir . 'class-' . strtolower(str_replace($prefix, '', $class)) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

/**
 * Helpers globales
 */
require_once C8ECM_PATH . 'includes/helpers.php';

/**
 * Inicializar módulos
 */
add_action('plugins_loaded', function() {
    new C8ECM_CPT_Manager();
    new C8ECM_Taxonomy_Manager();
    new C8ECM_Metabox_Manager();
    new C8ECM_Admin_Columns();
    new C8ECM_Ajax_Handler();
    new C8ECM_Shortcode_Manager();
    new C8ECM_Import_Export();
    new C8ECM_QR_Generator();
});