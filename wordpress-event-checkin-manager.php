<?php
/**
 * Plugin Name: Código8 – Event Check-in Manager
 * Plugin URI: https://codigo8.com/download/event-checkin-manager/
 * Description: Gestión de invitados, importador/exportador CSV, taxonomía Evento, check-in por QR con AJAX, registro del usuario que hizo el check-in y observaciones.
 * Version: 1.0.0
 * Author: Código8
 * Author URI: https://codigo8.com
 * License: GPLv2 or later
 * Text Domain: codigo8-event-checkin-manager
 *
 * Notes:
 * - Crear una página por evento con slug igual al término 'evento' (ej. evento-2025).
 * - Colocar [c8ecm_checkin] en esa página.
 */

if (!defined('ABSPATH')) exit;

/**
 * -------------------------------------------------------------------------
 * 1) Registro CPT 'invitado' y taxonomía 'evento'
 * -------------------------------------------------------------------------
 */
add_action('init', 'c8ecm_register_cpt_and_taxonomy');
function c8ecm_register_cpt_and_taxonomy() {
    register_post_type('invitado', [
        'labels' => [
            'name' => __('Invitados', 'codigo8-event-checkin-manager'),
            'singular_name' => __('Invitado', 'codigo8-event-checkin-manager'),
            'add_new_item' => __('Agregar Invitado', 'codigo8-event-checkin-manager'),
            'edit_item' => __('Editar Invitado', 'codigo8-event-checkin-manager'),
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-tickets-alt',
        'supports' => ['title'],
        'capability_type' => 'post',
    ]);

    register_taxonomy('evento', 'invitado', [
        'labels' => [
            'name' => __('Eventos', 'codigo8-event-checkin-manager'),
            'singular_name' => __('Evento', 'codigo8-event-checkin-manager'),
        ],
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'hierarchical' => false,
    ]);
}

/**
 * -------------------------------------------------------------------------
 * 2) Metaboxes para datos del invitado
 * -------------------------------------------------------------------------
 */
add_action('add_meta_boxes', 'c8ecm_add_metaboxes');
function c8ecm_add_metaboxes() {
    add_meta_box('c8ecm_datos', __('Datos del Invitado', 'codigo8-event-checkin-manager'), 'c8ecm_render_metabox', 'invitado', 'normal', 'high');
}

function c8ecm_render_metabox($post) {
    wp_nonce_field('c8ecm_save_meta', 'c8ecm_meta_nonce');

    $nombre = get_post_meta($post->ID, 'c8_nombre', true);
    $org = get_post_meta($post->ID, 'c8_organizacion', true);
    $mesa = get_post_meta($post->ID, 'c8_mesa', true);
    $observ = get_post_meta($post->ID, 'c8_observaciones', true);
    $checkin = get_post_meta($post->ID, 'c8_checkin', true);
    $checkin_by = get_post_meta($post->ID, 'c8_checkin_by', true);
    $checkin_at = get_post_meta($post->ID, 'c8_checkin_at', true);

    echo '<p><label><strong>' . __('Nombre completo', 'codigo8-event-checkin-manager') . '</strong><br>';
    echo '<input type="text" name="c8_nombre" value="' . esc_attr($nombre) . '" style="width:100%"></label></p>';

    echo '<p><label><strong>' . __('Organización', 'codigo8-event-checkin-manager') . '</strong><br>';
    echo '<input type="text" name="c8_organizacion" value="' . esc_attr($org) . '" style="width:100%"></label></p>';

    echo '<p><label><strong>' . __('Mesa asignada', 'codigo8-event-checkin-manager') . '</strong><br>';
    echo '<input type="text" name="c8_mesa" value="' . esc_attr($mesa) . '" style="width:100%"></label></p>';

    echo '<p><label><strong>' . __('Observaciones (privadas)', 'codigo8-event-checkin-manager') . '</strong><br>';
    echo '<textarea name="c8_observaciones" style="width:100%" rows="3">' . esc_textarea($observ) . '</textarea></label></p>';

    echo '<p><strong>' . __('Estado check-in:', 'codigo8-event-checkin-manager') . '</strong> ';
    echo ($checkin ? '<span style="color:green;">' . __('Ingresado', 'codigo8-event-checkin-manager') . '</span>' : '<span style="color:orange;">' . __('Pendiente', 'codigo8-event-checkin-manager') . '</span>') .
         ($checkin_at ? ' — ' . esc_html($checkin_at) : '');
    if ($checkin_by) echo ' (' . esc_html($checkin_by) . ')';
    echo '</p>';
}

/**
 * Guardar metadatos
 */
add_action('save_post_invitado', 'c8ecm_save_invitado_meta');
function c8ecm_save_invitado_meta($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['c8ecm_meta_nonce']) || !wp_verify_nonce($_POST['c8ecm_meta_nonce'], 'c8ecm_save_meta')) return;

    $fields = [
        'c8_nombre' => 'c8_nombre',
        'c8_organizacion' => 'c8_organizacion',
        'c8_mesa' => 'c8_mesa',
        'c8_observaciones' => 'c8_observaciones',
    ];

    foreach ($fields as $key => $field) {
        if (isset($_POST[$field])) {
            update_post_meta($post_id, $key, sanitize_text_field($_POST[$field]));
        }
    }
}

/**
 * -------------------------------------------------------------------------
 * 3) Admin columns: mostrar más info en el listado Invitados
 * -------------------------------------------------------------------------
 */
add_filter('manage_invitado_posts_columns', 'c8ecm_invitado_columns');
function c8ecm_invitado_columns($cols) {
    $new = [];
    $new['cb'] = $cols['cb'];
    $new['title'] = __('Ticket', 'codigo8-event-checkin-manager');
    $new['c8_nombre'] = __('Nombre', 'codigo8-event-checkin-manager');
    $new['c8_organizacion'] = __('Organización', 'codigo8-event-checkin-manager');
    $new['c8_mesa'] = __('Mesa', 'codigo8-event-checkin-manager');
    $new['evento'] = __('Evento', 'codigo8-event-checkin-manager');
    $new['c8_checkin'] = __('Check-in', 'codigo8-event-checkin-manager');
    $new['date'] = $cols['date'];
    return $new;
}

add_action('manage_invitado_posts_custom_column', 'c8ecm_invitado_columns_render', 10, 2);
function c8ecm_invitado_columns_render($column, $post_id) {
    switch ($column) {
        case 'c8_nombre':
            echo esc_html(get_post_meta($post_id, 'c8_nombre', true) ?: '');
            break;
        case 'c8_organizacion':
            echo esc_html(get_post_meta($post_id, 'c8_organizacion', true) ?: '');
            break;
        case 'c8_mesa':
            echo esc_html(get_post_meta($post_id, 'c8_mesa', true) ?: '');
            break;
        case 'evento':
            $terms = get_the_terms($post_id, 'evento');
            if ($terms && !is_wp_error($terms)) {
                $t = array_shift($terms);
                echo esc_html($t->name);
            }
            break;
        case 'c8_checkin':
            $checked = get_post_meta($post_id, 'c8_checkin', true);
            if ($checked) {
                $time = get_post_meta($post_id, 'c8_checkin_at', true);
                $by = get_post_meta($post_id, 'c8_checkin_by', true);
                echo '<span style="color:green;">' . __('Ingresó', 'codigo8-event-checkin-manager') . '</span>';
                if ($time) echo ' — ' . esc_html($time);
                if ($by) echo ' (' . esc_html($by) . ')';
            } else {
                echo '<span style="color:orange;">' . __('Pendiente', 'codigo8-event-checkin-manager') . '</span>';
            }
            break;
    }
}

/**
 * Añadir filtros (evento, organización, mesa, estado)
 */
add_action('restrict_manage_posts', 'c8ecm_restrict_manage_posts_filters');
function c8ecm_restrict_manage_posts_filters() {
    global $typenow;
    if ($typenow !== 'invitado') return;

    // Evento (taxonomy)
    $taxonomy = 'evento';
    $selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
    wp_dropdown_categories([
        'show_option_all' => __('Todos los eventos', 'codigo8-event-checkin-manager'),
        'taxonomy' => $taxonomy,
        'name' => $taxonomy,
        'selected' => $selected,
        'orderby' => 'name',
        'hide_empty' => false,
    ]);

    // Organización (meta)
    $org_val = isset($_GET['c8_organizacion']) ? sanitize_text_field($_GET['c8_organizacion']) : '';
    echo '<input type="text" name="c8_organizacion" placeholder="'.esc_attr__('Filtrar por organización','codigo8-event-checkin-manager').'" value="'.esc_attr($org_val).'" style="margin-left:8px;"/>';

    // Mesa (meta)
    $mesa_val = isset($_GET['c8_mesa']) ? sanitize_text_field($_GET['c8_mesa']) : '';
    echo '<input type="text" name="c8_mesa" placeholder="'.esc_attr__('Filtrar por mesa','codigo8-event-checkin-manager').'" value="'.esc_attr($mesa_val).'" style="margin-left:8px;"/>';

    // Estado (meta)
    $estado = isset($_GET['c8_checkin_status']) ? sanitize_text_field($_GET['c8_checkin_status']) : '';
    echo '<select name="c8_checkin_status" style="margin-left:8px;">
            <option value="">' . esc_html__('Todos los estados','codigo8-event-checkin-manager') . '</option>
            <option value="1" '.selected($estado,'1',false).'>' . esc_html__('Ingresado','codigo8-event-checkin-manager') . '</option>
            <option value="0" '.selected($estado,'0',false).'>' . esc_html__('Pendiente','codigo8-event-checkin-manager') . '</option>
          </select>';
}

/**
 * Aplicar filtros en query admin
 */
add_action('pre_get_posts', 'c8ecm_apply_admin_filters');
function c8ecm_apply_admin_filters($query) {
    global $pagenow, $typenow;
    if (!is_admin() || $pagenow !== 'edit.php' || $typenow !== 'invitado') return;

    // taxonomy evento
    if (!empty($_GET['evento'])) {
        $query->set('tax_query', [[
            'taxonomy' => 'evento',
            'field' => 'id',
            'terms' => intval($_GET['evento']),
        ]]);
    }

    // organización
    if (!empty($_GET['c8_organizacion'])) {
        $meta_query = $query->get('meta_query') ?: [];
        $meta_query[] = [
            'key' => 'c8_organizacion',
            'value' => sanitize_text_field($_GET['c8_organizacion']),
            'compare' => 'LIKE',
        ];
        $query->set('meta_query', $meta_query);
    }

    // mesa
    if (!empty($_GET['c8_mesa'])) {
        $meta_query = $query->get('meta_query') ?: [];
        $meta_query[] = [
            'key' => 'c8_mesa',
            'value' => sanitize_text_field($_GET['c8_mesa']),
            'compare' => 'LIKE',
        ];
        $query->set('meta_query', $meta_query);
    }

    // estado checkin
    if (isset($_GET['c8_checkin_status']) && $_GET['c8_checkin_status'] !== '') {
        $meta_query = $query->get('meta_query') ?: [];
        $meta_query[] = [
            'key' => 'c8_checkin',
            'value' => intval($_GET['c8_checkin_status']),
            'compare' => '=',
        ];
        $query->set('meta_query', $meta_query);
    }
}

/**
 * Habilitar búsqueda por nombre (meta c8_nombre) además de título
 */
add_filter('posts_search', 'c8ecm_search_by_nombre_meta', 10, 2);
function c8ecm_search_by_nombre_meta($search, $wp_query) {
    global $wpdb;
    if (is_admin() && $wp_query->is_main_query() && $wp_query->get('post_type') === 'invitado' && $search) {
        $s = $wp_query->get('s');
        $s_esc = esc_sql($wpdb->esc_like($s));
        $search = " AND ( ({$wpdb->posts}.post_title LIKE '%{$s_esc}%') OR EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} pm
            WHERE pm.post_id = {$wpdb->posts}.ID
              AND pm.meta_key = 'c8_nombre'
              AND pm.meta_value LIKE '%{$s_esc}%'
        ) ) ";
    }
    return $search;
}

/**
 * -------------------------------------------------------------------------
 * 4) Shortcode de check-in: [c8ecm_checkin]
 *    - colocar en la página cuyo slug es el evento (ej. evento-2025)
 *    - URL: /evento-2025/?ticket=123
 * -------------------------------------------------------------------------
 */
add_shortcode('c8ecm_checkin', 'c8ecm_checkin_shortcode');
function c8ecm_checkin_shortcode($atts) {
    if (!is_user_logged_in()) {
        // redirige a login y vuelve
        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        wp_safe_redirect(wp_login_url($current_url));
        exit;
    }

    if (!current_user_can('edit_posts')) {
        return '<p>' . __('Acceso denegado. No tenés permisos para realizar check-in.', 'codigo8-event-checkin-manager') . '</p>';
    }

    global $post;
    $event_slug = $post ? $post->post_name : '';
    $ticket = isset($_GET['ticket']) ? sanitize_text_field($_GET['ticket']) : '';

    if (!$ticket) {
        return '<p>' . __('No se especificó ticket.', 'codigo8-event-checkin-manager') . '</p>';
    }

    // Buscar invitado por título (ticket) y por taxonomía evento = slug de la página
    $args = [
        'post_type' => 'invitado',
        'title' => $ticket,
        'numberposts' => 1,
        'tax_query' => []
    ];
    if ($event_slug) {
        $args['tax_query'][] = [
            'taxonomy' => 'evento',
            'field' => 'slug',
            'terms' => $event_slug,
        ];
    }

    $found = get_posts($args);

    if (!$found) {
        return '<p>' . sprintf(__('Invitado no encontrado para el ticket: %s (evento: %s)', 'codigo8-event-checkin-manager'), esc_html($ticket), esc_html($event_slug)) . '</p>';
    }

    $inv = $found[0];
    $nombre = get_post_meta($inv->ID, 'c8_nombre', true) ?: '';
    $org = get_post_meta($inv->ID, 'c8_organizacion', true) ?: '';
    $mesa = get_post_meta($inv->ID, 'c8_mesa', true) ?: '';
    $check = get_post_meta($inv->ID, 'c8_checkin', true);
    $check_time = get_post_meta($inv->ID, 'c8_checkin_at', true);
    $check_by = get_post_meta($inv->ID, 'c8_checkin_by', true);
    $observ = get_post_meta($inv->ID, 'c8_observaciones_checkin', true);

    ob_start();
    ?>
    <style>
    .c8c_checkin_wrapper{font-family:system-ui,Segoe UI,Roboto,Arial,sans-serif;padding:18px;max-width:420px;margin:0 auto;text-align:center;}
    .c8c_checkin_wrapper h2{font-size:1.6rem;margin:0 0 6px;color:#222;}
    .c8c_checkin_wrapper p{margin:6px 0;font-size:1.05rem;color:#333;}
    .c8c_btn{background:#2d89ef;color:#fff;border:0;border-radius:10px;padding:14px 18px;font-size:1.1rem;width:100%;max-width:360px;cursor:pointer;box-shadow:0 6px 14px rgba(0,0,0,.12);}
    .c8c_btn:disabled{opacity:.6;cursor:not-allowed;}
    .c8c_success{color:#2e7d32;font-weight:700;margin-top:10px;}
    .c8c_obs{width:100%;max-width:360px;margin:10px auto 0;display:block;}
    .c8c_obs textarea{width:100%;padding:8px;border-radius:6px;border:1px solid #ddd;min-height:80px;}
    </style>

    <div class="c8c_checkin_wrapper" id="c8c_checkin_wrapper">
        <h2><?php echo esc_html($nombre ?: $inv->post_title); ?></h2>
        <p><strong><?php _e('Organización:', 'codigo8-event-checkin-manager'); ?></strong> <?php echo esc_html($org); ?></p>
        <p><strong><?php _e('Mesa:', 'codigo8-event-checkin-manager'); ?></strong> <?php echo esc_html($mesa); ?></p>
        <p><strong><?php _e('Evento:', 'codigo8-event-checkin-manager'); ?></strong> <?php echo esc_html($event_slug); ?></p>

        <?php if ($check): ?>
            <p class="c8c_success">✅ <?php _e('Ya ingresó a las', 'codigo8-event-checkin-manager'); ?> <?php echo esc_html($check_time); ?> (<?php echo esc_html($check_by); ?>)</p>
            <?php if ($observ): ?>
                <p><strong><?php _e('Observaciones:', 'codigo8-event-checkin-manager'); ?></strong><br><?php echo nl2br(esc_html($observ)); ?></p>
            <?php endif; ?>
        <?php else: ?>
            <div class="c8c_obs">
                <label for="c8c_obs_text"><?php _e('Observaciones (opcional)', 'codigo8-event-checkin-manager'); ?></label>
                <textarea id="c8c_obs_text" placeholder="<?php _e('Ej: invitado con requerimiento X...', 'codigo8-event-checkin-manager'); ?>"></textarea>
            </div>

            <button class="c8c_btn" id="c8c_do_checkin" data-postid="<?php echo esc_attr($inv->ID); ?>">
                ✅ <?php _e('Marcar ingreso', 'codigo8-event-checkin-manager'); ?>
            </button>

            <p id="c8c_msg" style="display:none;margin-top:10px;"></p>
            <script>
            (function(){
                const btn = document.getElementById('c8c_do_checkin');
                const msg = document.getElementById('c8c_msg');
                btn.addEventListener('click', function(){
                    if(!confirm('<?php echo esc_js(__('Confirmar check-in para este invitado?', 'codigo8-event-checkin-manager')); ?>')) return;
                    btn.disabled = true;
                    btn.textContent = '<?php echo esc_js(__('Procesando...', 'codigo8-event-checkin-manager')); ?>';
                    const postId = btn.dataset.postid;
                    const obs = document.getElementById('c8c_obs_text').value;
                    const data = new URLSearchParams();
                    data.append('action', 'c8ecm_ajax_checkin');
                    data.append('post_id', postId);
                    data.append('observ', obs);
                    data.append('nonce', '<?php echo wp_create_nonce('c8ecm_checkin_nonce'); ?>');

                    fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
                        body: data.toString()
                    }).then(r => r.json()).then(res => {
                        if(res.success) {
                            msg.style.display = 'block';
                            msg.className = 'c8c_success';
                            msg.innerText = '✅ ' + res.data.message;
                            // cambiar UI sin reload
                            btn.style.display = 'none';
                            document.getElementById('c8c_obs_text').style.display = 'none';
                        } else {
                            msg.style.display = 'block';
                            msg.className = '';
                            msg.innerText = 'Error: ' + res.data;
                            btn.disabled = false;
                            btn.textContent = '✅ <?php echo esc_js(__('Marcar ingreso', 'codigo8-event-checkin-manager')); ?>';
                        }
                    }).catch(e=>{
                        msg.style.display = 'block';
                        msg.innerText = 'Error de red';
                        btn.disabled = false;
                        btn.textContent = '✅ <?php echo esc_js(__('Marcar ingreso', 'codigo8-event-checkin-manager')); ?>';
                    });
                });
            })();
            </script>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * -------------------------------------------------------------------------
 * 5) AJAX handler para check-in (registra usuario, hora y observaciones)
 * -------------------------------------------------------------------------
 */
add_action('wp_ajax_c8ecm_ajax_checkin', 'c8ecm_ajax_checkin_handler');
function c8ecm_ajax_checkin_handler() {
    if (!is_user_logged_in()) wp_send_json_error(__('No autenticado', 'codigo8-event-checkin-manager'));
    if (!current_user_can('edit_posts')) wp_send_json_error(__('No tenés permisos para hacer check-in', 'codigo8-event-checkin-manager'));
    check_ajax_referer('c8ecm_checkin_nonce', 'nonce', true);

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $observ = isset($_POST['observ']) ? sanitize_textarea_field(wp_unslash($_POST['observ'])) : '';

    if (!$post_id || get_post_type($post_id) !== 'invitado') wp_send_json_error(__('Ticket inválido', 'codigo8-event-checkin-manager'));

    $already = get_post_meta($post_id, 'c8_checkin', true);
    if ($already) wp_send_json_error(__('El invitado ya fue marcado como ingresado', 'codigo8-event-checkin-manager'));

    $user = wp_get_current_user();
    update_post_meta($post_id, 'c8_checkin', 1);
    update_post_meta($post_id, 'c8_checkin_at', current_time('Y-m-d H:i:s'));
    update_post_meta($post_id, 'c8_checkin_by', $user->display_name ?: $user->user_login);
    if ($observ) update_post_meta($post_id, 'c8_observaciones_checkin', $observ);

    wp_send_json_success(['message' => __('Check-in registrado', 'codigo8-event-checkin-manager') . ' - ' . current_time('H:i')]);
}

/**
 * -------------------------------------------------------------------------
 * 6) Importador / Exportador CSV (submenú bajo Invitados)
 *    Import: formato CSV esperado: titulo,nombre,organizacion,mesa,evento
 * -------------------------------------------------------------------------
 */
add_action('admin_menu', 'c8ecm_admin_submenus');
function c8ecm_admin_submenus() {
    add_submenu_page('edit.php?post_type=invitado', __('Importar CSV', 'codigo8-event-checkin-manager'), __('Importar CSV', 'codigo8-event-checkin-manager'), 'manage_options', 'c8ecm_import_csv', 'c8ecm_render_import_page');
    add_submenu_page('edit.php?post_type=invitado', __('Exportar Invitados', 'codigo8-event-checkin-manager'), __('Exportar Invitados', 'codigo8-event-checkin-manager'), 'manage_options', 'c8ecm_export_csv', 'c8ecm_render_export_page');
}

/**
 * Render import page (uploader)
 */
function c8ecm_render_import_page() {
    if (!current_user_can('manage_options')) wp_die(__('No autorizado', 'codigo8-event-checkin-manager'));
    echo '<div class="wrap"><h1>' . __('Importar Invitados desde CSV', 'codigo8-event-checkin-manager') . '</h1>';

    if (!empty($_POST['c8ecm_import_nonce']) && check_admin_referer('c8ecm_import_action', 'c8ecm_import_nonce')) {
        if (!empty($_FILES['c8ecm_csv']['tmp_name'])) {
            $file = $_FILES['c8ecm_csv']['tmp_name'];
            $rows = array_map('str_getcsv', file($file));
            $header = array_map('trim', array_shift($rows));
            $count = 0;
            $skipped = 0;

            foreach ($rows as $row) {
                if (count($row) !== count($header)) continue;
                $data = array_combine($header, $row);

                $titulo = sanitize_text_field($data['titulo'] ?? '');
                $nombre = sanitize_text_field($data['nombre'] ?? '');
                $org = sanitize_text_field($data['organizacion'] ?? '');
                $mesa = sanitize_text_field($data['mesa'] ?? '');
                $evento = sanitize_text_field($data['evento'] ?? '');

                if (!$titulo) { $skipped++; continue; }

                // evitar duplicados por título + evento
                $existing = get_page_by_title($titulo, OBJECT, 'invitado');
                $create = true;
                if ($existing) {
                    // si existe, comprobar evento
                    $terms = get_the_terms($existing->ID, 'evento');
                    if ($terms) {
                        $found = false;
                        foreach ($terms as $t) {
                            if ($t->slug === sanitize_title($evento)) { $found = true; break; }
                        }
                        if ($found) $create = false;
                    }
                }

                if (!$create) { $skipped++; continue; }

                $post_id = wp_insert_post([
                    'post_type' => 'invitado',
                    'post_title' => $titulo,
                    'post_status' => 'publish'
                ]);

                if ($post_id && !is_wp_error($post_id)) {
                    update_post_meta($post_id, 'c8_nombre', $nombre);
                    update_post_meta($post_id, 'c8_organizacion', $org);
                    update_post_meta($post_id, 'c8_mesa', $mesa);
                    // asignar taxonomia evento (crear si no existe)
                    if ($evento) {
                        $term = term_exists($evento, 'evento');
                        if (!$term) $term = wp_insert_term($evento, 'evento');
                        if (!is_wp_error($term)) {
                            $term_id = is_array($term) ? $term['term_id'] : $term;
                            wp_set_object_terms($post_id, intval($term_id), 'evento', false);
                        }
                    }
                    update_post_meta($post_id, 'c8_checkin', 0);
                    $count++;
                } else {
                    $skipped++;
                }
            }

            echo '<div class="notice notice-success"><p>' . sprintf(__('Importados: %d — Saltados: %d', 'codigo8-event-checkin-manager'), $count, $skipped) . '</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>' . __('No se recibió archivo.', 'codigo8-event-checkin-manager') . '</p></div>';
        }
    }

    // Form
    echo '<h2>' . __('Instrucciones', 'codigo8-event-checkin-manager') . '</h2>';
    echo '<p>' . __('Formato CSV esperado: encabezado EXACTO: titulo,nombre,organizacion,mesa,evento', 'codigo8-event-checkin-manager') . '</p>';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('c8ecm_import_action', 'c8ecm_import_nonce');
    echo '<input type="file" name="c8ecm_csv" accept=".csv" required>';
    submit_button(__('Importar CSV', 'codigo8-event-checkin-manager'));
    echo '</form></div>';
}

/**
 * Render export page
 */
function c8ecm_render_export_page() {
    if (!current_user_can('manage_options')) wp_die(__('No autorizado', 'codigo8-event-checkin-manager'));
    echo '<div class="wrap"><h1>' . __('Exportar Invitados', 'codigo8-event-checkin-manager') . '</h1>';
    if (!empty($_POST['c8ecm_export_nonce']) && check_admin_referer('c8ecm_export_action', 'c8ecm_export_nonce')) {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=invitados_export_' . date('Y-m-d') . '.csv');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['titulo','nombre','organizacion','mesa','evento','checkin','checkin_at','checkin_by','observaciones_checkin']);
        $q = new WP_Query(['post_type' => 'invitado', 'posts_per_page' => -1, 'post_status' => 'publish']);
        while ($q->have_posts()) {
            $q->the_post();
            $id = get_the_ID();
            $titulo = get_the_title();
            $nombre = get_post_meta($id, 'c8_nombre', true);
            $org = get_post_meta($id, 'c8_organizacion', true);
            $mesa = get_post_meta($id, 'c8_mesa', true);
            $terms = get_the_terms($id, 'evento');
            $evento = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
            $check = get_post_meta($id, 'c8_checkin', true) ? '1' : '0';
            $check_at = get_post_meta($id, 'c8_checkin_at', true);
            $check_by = get_post_meta($id, 'c8_checkin_by', true);
            $obs = get_post_meta($id, 'c8_observaciones_checkin', true);
            fputcsv($out, [$titulo,$nombre,$org,$mesa,$evento,$check,$check_at,$check_by,$obs]);
        }
        wp_reset_postdata();
        fclose($out);
        exit;
    }

    echo '<form method="post">';
    wp_nonce_field('c8ecm_export_action', 'c8ecm_export_nonce');
    submit_button(__('Descargar CSV de Invitados', 'codigo8-event-checkin-manager'));
    echo '</form></div>';
}

/**
 * -------------------------------------------------------------------------
 * 7) Admin: añadir quick search placeholder o ayuda - ya implementado arriba
 * -------------------------------------------------------------------------
 */

/**
 * -------------------------------------------------------------------------
 * 8) Limpieza / helpers (si necesitás más funciones, agregalas aquí)
 * -------------------------------------------------------------------------
 */

/* End plugin file */
