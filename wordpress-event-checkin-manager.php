<?php
/**
 * Plugin Name: Código8 – Event Check-in Manager
 * Plugin URI: https://codigo8.com/download/event-checkin-manager/
 * Description: Sistema completo de gestión de invitados con check-in/check-out por QR, múltiples eventos, importación/exportación CSV y control de acceso por roles. v2.1.1
 * Version: 2.1.1
 * Author: Código8
 * Author URI: https://codigo8.com
 * License: GPLv2 or later
 * Text Domain: codigo8-event-checkin-manager
 */

if (!defined('ABSPATH')) exit;

/* -----------------------
   1. CPT 'invitado' (solo admins)
   ----------------------- */
add_action('init', 'c8ecm_register_invitados_cpt');
function c8ecm_register_invitados_cpt() {
    $labels = array(
        'name'               => 'Invitados',
        'singular_name'      => 'Invitado',
        'menu_name'          => 'Invitados',
        'name_admin_bar'     => 'Invitado',
        'add_new'            => 'Añadir nuevo',
        'add_new_item'       => 'Añadir nuevo invitado',
        'new_item'           => 'Nuevo invitado',
        'edit_item'          => 'Editar invitado',
        'view_item'          => 'Ver invitado',
        'all_items'          => 'Todos los invitados',
        'search_items'       => 'Buscar invitados',
        'not_found'          => 'No se encontraron invitados',
        'not_found_in_trash' => 'No hay invitados en la papelera',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => false,
        'show_ui'            => true,
        'show_in_menu'       => current_user_can('administrator') ? true : false,
        'menu_position'      => 6,
        'menu_icon'          => 'dashicons-tickets',
        'supports'           => array('title', 'custom-fields'),
        'capability_type'    => 'invitado',
        'map_meta_cap'       => false,
        'capabilities' => array(
            'edit_post'              => 'administrator',
            'read_post'              => 'administrator',
            'delete_post'            => 'administrator',
            'edit_posts'             => 'administrator',
            'edit_others_posts'      => 'administrator',
            'delete_posts'           => 'administrator',
            'publish_posts'          => 'administrator',
            'read_private_posts'     => 'administrator',
        ),
    );

    register_post_type('invitado', $args);
}

/* -----------------------
   Registrar taxonomía "evento"
   ----------------------- */
add_action('init', 'c8ecm_register_evento_taxonomy');
function c8ecm_register_evento_taxonomy() {
    $labels = array(
        'name'              => 'Eventos',
        'singular_name'     => 'Evento',
        'search_items'      => 'Buscar Eventos',
        'all_items'         => 'Todos los Eventos',
        'parent_item'       => 'Evento Padre',
        'parent_item_colon' => 'Evento Padre:',
        'edit_item'         => 'Editar Evento',
        'update_item'       => 'Actualizar Evento',
        'add_new_item'      => 'Añadir Nuevo Evento',
        'new_item_name'     => 'Nombre del Nuevo Evento',
        'menu_name'         => 'Eventos',
    );

    $args = array(
        'hierarchical'      => false,
        'labels'            => $labels,
        'show_ui'           => true,
        'show_admin_column' => true,
        'query_var'         => true,
        'rewrite'           => array('slug' => 'evento'),
        'show_in_rest'      => true,
    );

    register_taxonomy('evento', array('invitado'), $args);
}

/* -----------------------
   2. Metabox: datos del invitado
   ----------------------- */
add_action('add_meta_boxes', function(){
    add_meta_box('c8_invitado_data', 'Datos del Invitado', 'c8_render_metabox', 'invitado', 'normal', 'high');
});

function c8_render_metabox($post){
    wp_nonce_field('c8_save_invitado', 'c8_nonce');
    $nombre = get_post_meta($post->ID, 'c8_nombre', true);
    $org = get_post_meta($post->ID, 'c8_organizacion', true);
    $mesa = get_post_meta($post->ID, 'c8_mesa', true);
    $observ = get_post_meta($post->ID, 'c8_observaciones', true);
    $check = get_post_meta($post->ID, 'c8_checkin', true);
    $check_by = get_post_meta($post->ID, 'c8_checkin_by', true);
    $check_at = get_post_meta($post->ID, 'c8_checkin_at', true);
    
    // Obtener eventos existentes para el dropdown
    $eventos = get_terms(array(
        'taxonomy' => 'evento',
        'hide_empty' => false,
        'orderby' => 'name',
        'order' => 'ASC'
    ));
    
    // Obtener el evento actual del invitado
    $evento_actual = '';
    $evento_terms = c8ecm_get_evento_terms($post->ID);
    if ($evento_terms) {
        $evento_actual = $evento_terms[0]->term_id;
    }
    ?>
    <style>
    .c8-metabox-field { margin-bottom: 15px; }
    .c8-metabox-field label { display: block; margin-bottom: 5px; font-weight: bold; }
    .c8-metabox-field input[type="text"],
    .c8-metabox-field textarea,
    .c8-metabox-field select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
    .c8-checkin-status { padding: 10px; background: #f9f9f9; border-radius: 4px; }
    </style>

    <div class="c8-metabox-field">
        <label for="c8_nombre"><strong>Nombre completo</strong></label>
        <input type="text" id="c8_nombre" name="c8_nombre" value="<?php echo esc_attr($nombre); ?>">
    </div>

    <div class="c8-metabox-field">
        <label for="c8_organizacion"><strong>Organización</strong></label>
        <input type="text" id="c8_organizacion" name="c8_organizacion" value="<?php echo esc_attr($org); ?>">
    </div>

    <div class="c8-metabox-field">
        <label for="c8_mesa"><strong>Mesa asignada</strong></label>
        <input type="text" id="c8_mesa" name="c8_mesa" value="<?php echo esc_attr($mesa); ?>">
    </div>

    <div class="c8-metabox-field">
        <label for="c8_evento"><strong>Evento</strong></label>
        <select id="c8_evento" name="c8_evento" style="width: 100%">
            <option value="">-- Seleccionar evento --</option>
            <?php foreach($eventos as $evento): ?>
                <option value="<?php echo esc_attr($evento->term_id); ?>" <?php selected($evento_actual, $evento->term_id); ?>>
                    <?php echo esc_html($evento->name); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <p><small>Selecciona un evento existente. Si no existe, créalo primero en la pestaña "Eventos".</small></p>
    </div>

    <div class="c8-metabox-field">
        <label for="c8_observaciones"><strong>Observaciones (privadas)</strong></label>
        <textarea id="c8_observaciones" name="c8_observaciones" rows="3"><?php echo esc_textarea($observ); ?></textarea>
    </div>

    <div class="c8-checkin-status">
        <strong>Estado check-in:</strong>
        <?php if($check): ?>
            <span style="color:green">✅ Ingresado</span> — <?php echo esc_html($check_at); ?> 
            <?php if($check_by) echo '('.esc_html($check_by).')'; ?>
            
            <?php 
            $checkout = get_post_meta($post->ID, 'c8_checkout', true);
            $checkout_at = get_post_meta($post->ID, 'c8_checkout_at', true);
            $checkout_by = get_post_meta($post->ID, 'c8_checkout_by', true);
            ?>
            <?php if($checkout): ?>
                <br><strong>Estado check-out:</strong>
                <span style="color:blue">🚪 Salió</span> — <?php echo esc_html($checkout_at); ?>
                <?php if($checkout_by) echo '('.esc_html($checkout_by).')'; ?>
            <?php endif; ?>
            
        <?php else: ?>
            <span style="color:orange">⏳ Pendiente</span>
        <?php endif; ?>
    </div>
    <?php
}

add_action('save_post_invitado', function($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['c8_nonce']) || !wp_verify_nonce($_POST['c8_nonce'], 'c8_save_invitado')) return;
    if (!current_user_can('manage_options')) return;

    $nombre  = isset($_POST['c8_nombre']) ? sanitize_text_field($_POST['c8_nombre']) : '';
    $org     = isset($_POST['c8_organizacion']) ? sanitize_text_field($_POST['c8_organizacion']) : '';
    $mesa    = isset($_POST['c8_mesa']) ? sanitize_text_field($_POST['c8_mesa']) : '';
    $observ  = isset($_POST['c8_observaciones']) ? sanitize_textarea_field($_POST['c8_observaciones']) : '';
    $evento  = isset($_POST['c8_evento']) ? intval($_POST['c8_evento']) : '';

    // Obtener el título del post
    $titulo = get_the_title($post_id);

    update_post_meta($post_id, 'c8_ticket', $titulo);
    update_post_meta($post_id, 'c8_nombre', $nombre);
    update_post_meta($post_id, 'c8_organizacion', $org);
    update_post_meta($post_id, 'c8_mesa', $mesa);
    update_post_meta($post_id, 'c8_observaciones', $observ);

    // Asignar evento solo si se seleccionó uno válido
    if (!empty($evento)) {
        wp_set_object_terms($post_id, intval($evento), 'evento', false);
    } else {
        // Si no hay evento seleccionado, eliminar cualquier término asignado
        wp_set_object_terms($post_id, null, 'evento');
    }
});

/* -----------------------
   3. Admin columns & filters & search by name
   ----------------------- */
add_filter('manage_invitado_posts_columns', function($cols){
    $cols_new = [];
    $cols_new['cb'] = $cols['cb'];
    $cols_new['title'] = 'Ticket';
    $cols_new['c8_nombre'] = 'Nombre';
    $cols_new['c8_organizacion'] = 'Organización';
    $cols_new['c8_mesa'] = 'Mesa';
    $cols_new['evento'] = 'Evento';
    $cols_new['c8_checkin'] = 'Check-in';
    $cols_new['date'] = $cols['date'];
    return $cols_new;
});
add_action('manage_invitado_posts_custom_column', function($column, $post_id){
    if ($column === 'c8_nombre') echo esc_html(get_post_meta($post_id,'c8_nombre',true));
    if ($column === 'c8_organizacion') echo esc_html(get_post_meta($post_id,'c8_organizacion',true));
    if ($column === 'c8_mesa') echo esc_html(get_post_meta($post_id,'c8_mesa',true));
    if ($column === 'evento') {
        $terms = get_the_terms($post_id, 'evento');
        if ($terms && !is_wp_error($terms)) echo esc_html($terms[0]->name);
    }
    if ($column === 'c8_checkin') {
        $c = get_post_meta($post_id,'c8_checkin',true);
        if ($c) {
            echo '<span style="color:green">Ingresó</span> — ' . esc_html(get_post_meta($post_id,'c8_checkin_at',true));
            $by = get_post_meta($post_id,'c8_checkin_by',true);
            if ($by) echo ' ('.esc_html($by).')';
        } else {
            echo '<span style="color:orange">Pendiente</span>';
        }
    }
}, 10, 2);

// filtros (evento dropdown y cajas)
add_action('restrict_manage_posts', function(){
    global $typenow;
    if ($typenow !== 'invitado') return;

    // Evento taxonomy - CORREGIDO
    $taxonomy = 'evento';
    $selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
    
    $dropdown_args = array(
        'show_option_all' => 'Todos los eventos',
        'taxonomy'        => $taxonomy,
        'name'            => $taxonomy,
        'selected'        => $selected,
        'value_field'     => 'slug', // Usar slug en lugar de ID
        'orderby'         => 'name',
        'hide_empty'      => false,
        'hierarchical'    => true,
    );
    
    wp_dropdown_categories($dropdown_args);

    // organización
    $org_val = isset($_GET['c8_organizacion']) ? esc_attr($_GET['c8_organizacion']) : '';
    echo '<input type="text" name="c8_organizacion" placeholder="Filtrar por organización" value="'.$org_val.'" style="margin-left:8px;">';

    // mesa
    $mesa_val = isset($_GET['c8_mesa']) ? esc_attr($_GET['c8_mesa']) : '';
    echo '<input type="text" name="c8_mesa" placeholder="Filtrar por mesa" value="'.$mesa_val.'" style="margin-left:8px;">';

    // estado
    $st = isset($_GET['c8_checkin_status']) ? esc_attr($_GET['c8_checkin_status']) : '';
    echo '<select name="c8_checkin_status" style="margin-left:8px;">
            <option value="">Todos los estados</option>
            <option value="1" '.selected($st,'1',false).'>Ingresado</option>
            <option value="0" '.selected($st,'0',false).'>Pendiente</option>
          </select>';
});

add_action('pre_get_posts', function($query){
    global $pagenow, $typenow;
    if (!is_admin() || $pagenow !== 'edit.php' || $typenow !== 'invitado') return;

    // taxonomy evento
    if (!empty($_GET['evento'])) {
        $query->set('tax_query', [[
            'taxonomy' => 'evento',
            'field' => 'slug', // Cambiado de 'id' a 'slug'
            'terms' => sanitize_text_field($_GET['evento']),
        ]]);
    }

    // organización
    if (!empty($_GET['c8_organizacion'])) {
        $meta = $query->get('meta_query') ?: [];
        $meta[] = ['key'=>'c8_organizacion','value'=>sanitize_text_field($_GET['c8_organizacion']),'compare'=>'LIKE'];
        $query->set('meta_query',$meta);
    }

    // mesa
    if (!empty($_GET['c8_mesa'])) {
        $meta = $query->get('meta_query') ?: [];
        $meta[] = ['key'=>'c8_mesa','value'=>sanitize_text_field($_GET['c8_mesa']),'compare'=>'LIKE'];
        $query->set('meta_query',$meta);
    }

    // estado
    if (isset($_GET['c8_checkin_status']) && $_GET['c8_checkin_status'] !== '') {
        $meta = $query->get('meta_query') ?: [];
        $meta[] = ['key'=>'c8_checkin','value'=>intval($_GET['c8_checkin_status']),'compare'=>'='];
        $query->set('meta_query',$meta);
    }
});

// búsqueda por título y por meta c8_nombre
add_filter('posts_search', function($search, $q){
    global $wpdb;
    if (is_admin() && $q->is_main_query() && $q->get('post_type') === 'invitado' && $search) {
        $s = $q->get('s');
        $s_esc = esc_sql($wpdb->esc_like($s));
        $search = " AND ( ({$wpdb->posts}.post_title LIKE '%{$s_esc}%') OR EXISTS (
            SELECT 1 FROM {$wpdb->postmeta} pm WHERE pm.post_id = {$wpdb->posts}.ID
            AND pm.meta_key = 'c8_nombre' AND pm.meta_value LIKE '%{$s_esc}%'
        ) ) ";
    }
    return $search;
}, 10, 2);

/* -----------------------
   4. Shortcode [c8ecm_checkin]
   - Public access (Elementor handles display restrictions).
   - Page slug = event slug (e.g. evento-2025)
   - URL: /evento-2025/?ticket=123
   ----------------------- */
add_shortcode('c8ecm_checkin', function($atts){
    $atts = shortcode_atts(array(
        'event' => ''
    ), $atts);
    
    $ticket = isset($_GET['ticket']) ? sanitize_text_field($_GET['ticket']) : '';
    if (!$ticket) return '<p>No se especificó ticket.</p>';

    // Determinar evento desde parámetro shortcode o slug de página
    $event_slug = !empty($atts['event']) ? $atts['event'] : '';
    if (!$event_slug) {
        global $post;
        $event_slug = $post ? $post->post_name : '';
    }

    $args = ['post_type'=>'invitado','title'=>$ticket,'numberposts'=>1];
    if ($event_slug) {
        $args['tax_query'] = [[ 'taxonomy'=>'evento','field'=>'slug','terms'=>$event_slug ]];
    }

    $found = get_posts($args);
    if (!$found) return '<p>Invitado no encontrado para el ticket: '.esc_html($ticket).' (evento: '.esc_html($event_slug).')</p>';

    $inv = $found[0];
    $nombre = get_post_meta($inv->ID,'c8_nombre',true) ?: '';
    $org = get_post_meta($inv->ID,'c8_organizacion',true) ?: '';
    $mesa = get_post_meta($inv->ID,'c8_mesa',true) ?: '';
    $check = get_post_meta($inv->ID,'c8_checkin',true);
    $check_at = get_post_meta($inv->ID,'c8_checkin_at',true);
    $check_by = get_post_meta($inv->ID,'c8_checkin_by',true);
    $observ = get_post_meta($inv->ID,'c8_observaciones_checkin',true);
    $checkout = get_post_meta($inv->ID,'c8_checkout',true);
    $checkout_at = get_post_meta($inv->ID,'c8_checkout_at',true);
    $checkout_by = get_post_meta($inv->ID,'c8_checkout_by',true);

    ob_start();
    ?>
    <style>
    .c8-list-wrap{max-width:1000px;margin:0 auto;font-family:system-ui,Arial,sans-serif;}
    .c8-list-controls{display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;}
    .c8-list-table{width:100%;border-collapse:collapse;}
    .c8-list-table th, .c8-list-table td{padding:8px;border-bottom:1px solid #eee;text-align:left;}
    .c8-list-btn{padding:6px 10px;border-radius:6px;border:0;background:#2d89ef;color:#fff;cursor:pointer;}
    .c8-list-btn.green{background:#4caf50;}
    .c8-filter-input{padding:6px;border:1px solid #ddd;border-radius:6px;}
    
    /* Estilos para filas clickeables */
    .c8-clickable-row:hover { background-color: #f5f5f5; }
    .c8-clickable-row:active { background-color: #e9e9e9; }
    .c8-clickable-row td:first-child { position: relative; }
    .c8-clickable-row td:first-child::after {
        content: "🔗";
        position: absolute;
        right: 5px;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.5;
        font-size: 12px;
    }
    </style>

    <div class="c8c_wrapper">
      <h2 class="c8-field-nombre"><?php echo esc_html($nombre ?: $inv->post_title); ?></h2>
      <p class="c8-field-organizacion c8c_field"><strong>Organización:</strong> <?php echo esc_html($org); ?></p>
      <p class="c8-field-mesa c8c_field"><strong>Mesa:</strong> <?php echo esc_html($mesa); ?></p>
      <p class="c8-field-evento c8c_field"><strong>Evento:</strong> <?php echo esc_html($event_slug); ?></p>

      <?php if ($check): ?>
        <?php if($checkout): ?>
          <p style="color:blue;font-weight:700;">🚪 Salió: <?php echo esc_html($checkout_at); ?></p>
          <?php if($checkout_by): ?>
            <p style="color:#666;font-size:0.9em;">Registrado por: <?php echo esc_html($checkout_by); ?></p>
          <?php endif; ?>
          <button class="c8c_btn checkin_again" id="c8c_btn_checkin_again" data-postid="<?php echo esc_attr($inv->ID); ?>">
            🔄 Volver a ingresar
          </button>
        <?php else: ?>
          <p style="color:green;font-weight:700;">✅ Ingresó: <?php echo esc_html($check_at); ?></p>
          <?php if($check_by): ?>
            <p style="color:#666;font-size:0.9em;">Registrado por: <?php echo esc_html($check_by); ?></p>
          <?php endif; ?>
          <button class="c8c_btn checkout" id="c8c_btn_checkout" data-postid="<?php echo esc_attr($inv->ID); ?>">
            🚪 Registrar salida
          </button>
        <?php endif; ?>
        
        <?php if($observ): ?>
          <p class="c8-field-observaciones"><strong>Observaciones:</strong><br><?php echo nl2br(esc_html($observ)); ?></p>
        <?php endif; ?>
        
      <?php else: ?>
        <div class="c8c_obs c8-field-observaciones">
          <label for="c8_obs"><?php _e('Observaciones'); ?></label>
          <textarea id="c8_obs" placeholder="Ej: alergia, silla extra..."></textarea>
        </div>
        <button class="c8c_btn" id="c8c_btn" data-postid="<?php echo esc_attr($inv->ID); ?>">
          ✅ Marcar ingreso
        </button>
      <?php endif; ?>
      
      <p id="c8c_msg" style="margin-top:10px;display:none;"></p>
    </div>

    <script>
    (function(){
        const msg = document.getElementById('c8c_msg');
        
        function handleAction(btn, action, confirmText) {
            if(!confirm(confirmText)) return;
            btn.disabled = true;
            btn.classList.add('disabled');
            btn.textContent = 'Procesando...';
            const postId = btn.dataset.postid;
            const observ = document.getElementById('c8_obs') ? document.getElementById('c8_obs').value : '';
            const data = new URLSearchParams();
            data.append('action','c8ecm_checkin_ajax');
            data.append('post_id', postId);
            data.append('observ', observ);
            data.append('check_action', action);
            data.append('nonce', '<?php echo wp_create_nonce('c8ecm_checkin_nonce'); ?>');

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method:'POST',
                credentials:'same-origin',
                headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
                body: data.toString()
            }).then(r=>r.json()).then(res=>{
                if(res.success){
                    location.reload();
                } else {
                    msg.style.display='block';
                    msg.style.color='red';
                    msg.innerText = 'Error: ' + (res.data ? res.data : 'Error');
                    btn.disabled = false;
                    btn.classList.remove('disabled');
                    btn.textContent = getButtonText(action);
                }
            }).catch(e=>{
                msg.style.display='block';
                msg.style.color='red';
                msg.innerText = 'Error de red';
                btn.disabled = false;
                btn.classList.remove('disabled');
                btn.textContent = getButtonText(action);
            });
        }

        function getButtonText(action) {
            switch(action) {
                case 'checkin': return '✅ Marcar ingreso';
                case 'checkout': return '🚪 Registrar salida';
                case 'checkin_again': return '🔄 Volver a ingresar';
                default: return 'Acción';
            }
        }

        // Asignar eventos a todos los botones posibles
        const btnCheckin = document.getElementById('c8c_btn');
        const btnCheckout = document.getElementById('c8c_btn_checkout');
        const btnCheckinAgain = document.getElementById('c8c_btn_checkin_again');

        if(btnCheckin) {
            btnCheckin.addEventListener('click', function(){
                handleAction(this, 'checkin', 'Confirmar check-in para <?php echo esc_js($nombre ?: $inv->post_title); ?>?');
            });
        }
        
        if(btnCheckout) {
            btnCheckout.addEventListener('click', function(){
                handleAction(this, 'checkout', 'Confirmar check-out para <?php echo esc_js($nombre ?: $inv->post_title); ?>?');
            });
        }
        
        if(btnCheckinAgain) {
            btnCheckinAgain.addEventListener('click', function(){
                handleAction(this, 'checkin_again', 'Confirmar re-ingreso para <?php echo esc_js($nombre ?: $inv->post_title); ?>?');
            });
        }
    })();
    </script>
    <?php
    return ob_get_clean();
});

/* -----------------------
   5. AJAX handler for checkin (logged + nopriv)
   - verifies nonce from page (we use wp_verify_nonce for nopriv compatibility)
   ----------------------- */
add_action('wp_ajax_c8ecm_checkin_ajax', 'c8ecm_checkin_ajax_handler');
add_action('wp_ajax_nopriv_c8ecm_checkin_ajax', 'c8ecm_checkin_ajax_handler');

function c8ecm_checkin_ajax_handler() {
    $nonce = isset($_POST['nonce']) ? $_POST['nonce'] : '';
    if (!wp_verify_nonce($nonce, 'c8ecm_checkin_nonce')) {
        wp_send_json_error('Nonce inválido');
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id || get_post_type($post_id) !== 'invitado') wp_send_json_error('Ticket inválido');

    $observ = isset($_POST['observ']) ? sanitize_textarea_field($_POST['observ']) : '';
    $check_action = isset($_POST['check_action']) ? $_POST['check_action'] : 'checkin';

    $user = wp_get_current_user();
    $by = $user && $user->exists() ? ($user->display_name ?: $user->user_login) : 'anon@'.$_SERVER['REMOTE_ADDR'];

    $current_time = current_time('Y-m-d H:i:s');
    
    switch($check_action) {
        case 'checkin':
            $already = get_post_meta($post_id,'c8_checkin',true);
            if ($already) wp_send_json_error('Invitado ya ingresado');
            
            update_post_meta($post_id,'c8_checkin',1);
            update_post_meta($post_id,'c8_checkin_at', $current_time);
            update_post_meta($post_id,'c8_checkin_by', $by);
            // Limpiar checkout si existe
            delete_post_meta($post_id,'c8_checkout');
            delete_post_meta($post_id,'c8_checkout_at');
            delete_post_meta($post_id,'c8_checkout_by');
            if ($observ) update_post_meta($post_id,'c8_observaciones_checkin', $observ);
            break;
            
        case 'checkout':
            $checked_in = get_post_meta($post_id,'c8_checkin',true);
            if (!$checked_in) wp_send_json_error('Invitado no ha ingresado');
            
            $already_checked_out = get_post_meta($post_id,'c8_checkout',true);
            if ($already_checked_out) wp_send_json_error('Invitado ya salió');
            
            update_post_meta($post_id,'c8_checkout',1);
            update_post_meta($post_id,'c8_checkout_at', $current_time);
            update_post_meta($post_id,'c8_checkout_by', $by);
            break;
            
        case 'checkin_again':
            $checked_out = get_post_meta($post_id,'c8_checkout',true);
            if (!$checked_out) wp_send_json_error('Invitado no ha salido');
            
            update_post_meta($post_id,'c8_checkin_at', $current_time);
            update_post_meta($post_id,'c8_checkin_by', $by);
            // Limpiar checkout para permitir re-ingreso
            delete_post_meta($post_id,'c8_checkout');
            delete_post_meta($post_id,'c8_checkout_at');
            delete_post_meta($post_id,'c8_checkout_by');
            break;
    }

    wp_send_json_success(['message' => 'Operación completada: ' . $current_time]);
}

/* -----------------------
   6. Shortcode [c8ecm_list] — listado / búsqueda / filtros + checkin rápido
   - Public (useful for staff devices); Elementor display conditions can hide page if needed
   ----------------------- */
add_shortcode('c8ecm_list', function($atts){
    $atts = shortcode_atts(array(
        'event' => ''
    ), $atts);
    
    ob_start();
    ?>
    <style>
    .c8-list-wrap{max-width:1000px;margin:0 auto;font-family:system-ui,Arial,sans-serif;}
    .c8-list-controls{display:flex;gap:8px;margin-bottom:10px;flex-wrap:wrap;}
    .c8-list-table{width:100%;border-collapse:collapse;}
    .c8-list-table th, .c8-list-table td{padding:8px;border-bottom:1px solid #eee;text-align:left;}
    .c8-list-btn{padding:6px 10px;border-radius:6px;border:0;background:#2d89ef;color:#fff;cursor:pointer;}
    .c8-list-btn.green{background:#4caf50;}
    .c8-filter-input{padding:6px;border:1px solid #ddd;border-radius:6px;}
    
    /* Estilos para filas clickeables */
    .c8-clickable-row:hover { background-color: #f5f5f5; }
    .c8-clickable-row:active { background-color: #e9e9e9; }
    .c8-clickable-row td:first-child { position: relative; }
    .c8-clickable-row td:first-child::after {
        content: "🔗";
        position: absolute;
        right: 5px;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.5;
        font-size: 12px;
    }
    
    /* Ocultar solo columna Evento en móviles */
    @media (max-width: 768px) {
        .c8-list-table th:nth-child(5),  /* Columna Evento en th */
        .c8-list-table td:nth-child(5) {  /* Columna Evento en td */
            display: none;
        }
        
        /* Ajustar controles para móviles */
        .c8-list-controls {
            flex-direction: column;
        }
        
        .c8-filter-input {
            width: 100%;
            margin-bottom: 5px;
        }
        
        .c8-list-btn {
            width: 100%;
        }
    }
    </style>

    <div class="c8-list-wrap">
      <div class="c8-list-controls">
		 <input class="c8-filter-input" id="c8_q" placeholder="Buscar por ticket, nombre, organización">
		 <input class="c8-filter-input" id="c8_mesa_filter" placeholder="Buscar por Mesa">
		 <button class="c8-list-btn" id="c8_refresh">Buscar</button>
	  </div>
      <div id="c8_list_results">Cargando...</div>
    </div>

    <script>
(function(){
  const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
  const results = document.getElementById('c8_list_results');
  const eventSlug = '<?php echo esc_js($atts['event']); ?>';

  function loadList(){
    const q = document.getElementById('c8_q').value;
    const mesa = document.getElementById('c8_mesa_filter').value;
    results.innerHTML = 'Buscando...';
    const data = new URLSearchParams();
    data.append('action','c8ecm_list_ajax');
    data.append('q', q);
    data.append('mesa', mesa);
    data.append('evento', eventSlug); // ← Pasar el evento desde el shortcode
    fetch(ajaxUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:data.toString()})
      .then(r=>r.text()).then(html=>{ 
        results.innerHTML = html; 
        attachHandlers(); 
        attachRowHandlers();
      })
      .catch(()=> results.innerHTML = 'Error de red');
  }

  function attachHandlers(){
    document.querySelectorAll('.c8-do-checkin').forEach(btn=>{
      btn.addEventListener('click', function(e){
        e.stopPropagation(); // Prevenir que el click se propague a la fila
        const id = this.dataset.id;
        if(!confirm('Confirmar check-in?')) return;
        this.disabled = true;
        this.textContent = 'Procesando...';
        const data = new URLSearchParams();
        data.append('action','c8ecm_checkin_ajax');
        data.append('post_id', id);
        data.append('observ', '');
        data.append('check_action', 'checkin');
        data.append('nonce', '<?php echo wp_create_nonce('c8ecm_checkin_nonce'); ?>');
        fetch(ajaxUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:data.toString()})
          .then(r=>r.json()).then(res=>{
            if(res.success){
              btn.classList.add('green');
              btn.textContent = 'Ingresó';
            } else {
              alert('Error: ' + (res.data || ''));
              btn.disabled = false;
              btn.textContent = 'Ingresar';
            }
          }).catch(()=>{ 
            alert('Error de red'); 
            btn.disabled=false; 
            btn.textContent='Ingresar'; 
          });
      });
    });
  }
  
  // Manejar clicks en filas
  function attachRowHandlers() {
    document.querySelectorAll('.c8-clickable-row').forEach(row => {
      row.addEventListener('click', function(e) {
        // No abrir el enlace si se hizo click en un botón
        if (e.target.tagName === 'BUTTON' || e.target.closest('button')) {
          return;
        }
        const href = this.dataset.href;
        if (href && href !== '#') {
          window.location.href = href; // Redirige directamente en la misma pestaña
        }
      });
    });
  }

  // Event listeners
  document.getElementById('c8_refresh').addEventListener('click', loadList);
  document.getElementById('c8_q').addEventListener('keyup', function(e){ 
    if(e.key === 'Enter') loadList(); 
  });

  // initial load
  loadList();
})();
</script>
    <?php
    return ob_get_clean();
});

/* -----------------------
   AJAX for list: returns HTML table (used by c8ecm_list)
   ----------------------- */
add_action('wp_ajax_c8ecm_list_ajax', 'c8ecm_list_ajax_handler');
add_action('wp_ajax_nopriv_c8ecm_list_ajax', 'c8ecm_list_ajax_handler');

function c8ecm_list_ajax_handler(){
    $q = isset($_POST['q']) ? sanitize_text_field($_POST['q']) : '';
    $evento = isset($_POST['evento']) ? sanitize_text_field($_POST['evento']) : '';
    $mesa = isset($_POST['mesa']) ? sanitize_text_field($_POST['mesa']) : '';

    $args = [
        'post_type'=>'invitado',
        'posts_per_page'=>200,
        'meta_key' => 'c8_organizacion',
        'orderby' => 'meta_value',
        'order' => 'ASC',
    ];
    
    // Búsqueda múltiple: ticket, nombre Y organización
    if ($q) {
        $args['meta_query'] = [
            'relation' => 'OR',
            ['key'=>'c8_ticket','value'=>$q,'compare'=>'LIKE'],     // Buscar en ticket
            ['key'=>'c8_nombre','value'=>$q,'compare'=>'LIKE'],      // Buscar en nombre
            ['key'=>'c8_organizacion','value'=>$q,'compare'=>'LIKE'] // Buscar en organización
        ];
    }
    
    // Búsqueda por mesa (coincidencia exacta)
    if ($mesa) {
        if (isset($args['meta_query'])) {
            // Si ya existe meta_query, añadir la búsqueda por mesa
            $args['meta_query'] = [
                'relation' => 'AND',
                $args['meta_query'],
                ['key'=>'c8_mesa','value'=>$mesa,'compare'=>'=']
            ];
        } else {
            $args['meta_query'] = [
                ['key'=>'c8_mesa','value'=>$mesa,'compare'=>'=']
            ];
        }
    }
    
    // Filtro por evento (si se especifica)
    if ($evento) {
        $args['tax_query'] = [[ 'taxonomy'=>'evento','field'=>'slug','terms'=>$evento ]];
    }

    $posts = get_posts($args);
    
    if (!$posts) { 
        echo '<p>No se encontraron invitados.</p>'; 
        wp_die(); 
    }

    echo '<table class="c8-list-table"><thead><tr><th>Ticket</th><th class="c8-field-nombre">Nombre</th><th class="c8-field-organizacion">Organización</th><th class="c8-field-mesa">Mesa</th><th>Evento</th><th>Check-in</th></tr></thead><tbody>';
    foreach($posts as $p){
        $nombre = get_post_meta($p->ID,'c8_nombre',true);
        $org = get_post_meta($p->ID,'c8_organizacion',true);
        $mesa_val = get_post_meta($p->ID,'c8_mesa',true);
        $terms = c8ecm_get_evento_terms($p->ID);
        $evento_name = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
        $check = get_post_meta($p->ID,'c8_checkin',true);
        $check_at = get_post_meta($p->ID,'c8_checkin_at',true);
        $check_by = get_post_meta($p->ID,'c8_checkin_by',true);
        $checkout = get_post_meta($p->ID,'c8_checkout',true);
        $checkout_at = get_post_meta($p->ID,'c8_checkout_at',true);
        
        // Obtener el slug del evento para construir la URL
        $evento_slug = $terms ? $terms[0]->slug : '';
        $checkin_url = $evento_slug ? home_url("/{$evento_slug}/?ticket=" . $p->post_title) : '#';
        
        echo '<tr class="c8-clickable-row" data-href="' . esc_url($checkin_url) . '" style="cursor: pointer;">';
        echo '<td><strong>' . esc_html($p->post_title) . '</strong></td>';
        echo '<td class="c8-field-nombre">' . esc_html($nombre) . '</td>';
        echo '<td class="c8-field-organizacion">' . esc_html($org) . '</td>';
        echo '<td class="c8-field-mesa">' . esc_html($mesa_val) . '</td>';
        echo '<td class="c8-field-evento">' . esc_html($evento_name) . '</td>';
        echo '<td>';
        if ($check) {
            if ($checkout) {
                echo '<strong style="color:blue">🚪 Salió</strong><br><small>' . esc_html($checkout_at) . '</small>';
            } else {
                echo '<strong style="color:green">✅ Ingresó</strong><br><small>' . esc_html($check_at) . '</small>';
                if ($check_by) {
                    echo '<br><small style="color:#666">por: ' . esc_html($check_by) . '</small>';
                }
            }
        } else {
            echo '<button class="c8-do-checkin c8-list-btn" data-id="' . esc_attr($p->ID) . '">Ingresar</button>';
        }
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    wp_die();
}

// Función helper para buscar en el título
function c8ecm_search_title_custom($where, $query) {
    global $wpdb;
    
    if ($search_term = $query->get('s')) {
        $where .= " OR {$wpdb->posts}.post_title LIKE '%" . esc_sql($wpdb->esc_like($search_term)) . "%'";
    }
    
    return $where;
}

/* -----------------------
   7. Import / Export submenu (inside Invitados)
   - Import: uploader, option to update existing
   - Export: CSV matching import format
   ----------------------- */
add_action('admin_menu', function(){
    add_submenu_page('edit.php?post_type=invitado','Importar / Exportar','Importar / Exportar','manage_options','c8ecm_import_export','c8ecm_import_export_page');
});

function c8ecm_import_export_page(){
    if (!current_user_can('manage_options')) wp_die('No autorizado');
    echo '<div class="wrap"><h1>Importar / Exportar Invitados</h1>';

     // import form
    echo '<h2>Importar CSV</h2>';
    echo '<p>Formato CSV esperado (encabezado EXACTO): <code>titulo,nombre,organizacion,mesa,evento,observaciones,checkin</code></p>';
    echo '<form method="post" enctype="multipart/form-data">';
    wp_nonce_field('c8ecm_import_action','c8ecm_import_nonce');
    echo '<input type="file" name="c8ecm_csv" accept=".csv" required>';
    echo ' <label><input type="checkbox" name="update_existing"> Actualizar existentes (por título + evento)</label><br><br>';
    
    // Selector de separador
    $separador = isset($_POST['separador']) ? $_POST['separador'] : ',';
    echo '<label><strong>Separador CSV:</strong> ';
    echo '<select name="separador">';
    echo '<option value="," '.selected($separador, ',', false).'>Coma (,)</option>';
    echo '<option value=";" '.selected($separador, ';', false).'>Punto y coma (;)</option>';
    echo '<option value="|" '.selected($separador, '|', false).'>Pipe (|)</option>';
    echo '<option value="tab" '.selected($separador, 'tab', false).'>Tabulador</option>';
    echo '</select>';
    echo '</label><br><br>';
    
    submit_button('Importar Invitados', 'primary', 'c8ecm_do_import');
    echo '</form>';

    // handle import
    if (isset($_POST['c8ecm_do_import'])) {
        if (!isset($_POST['c8ecm_import_nonce']) || !wp_verify_nonce($_POST['c8ecm_import_nonce'],'c8ecm_import_action')) {
            echo '<div class="notice notice-error"><p>Nonce inválido</p></div>';
        } else if (!empty($_FILES['c8ecm_csv']['tmp_name'])) {
            $update_existing = isset($_POST['update_existing']);
            $separador = isset($_POST['separador']) ? $_POST['separador'] : ',';
            
            // Convertir "tab" a carácter de tabulación real
            if ($separador === 'tab') {
                $separador = "\t";
            }
            
            $file = $_FILES['c8ecm_csv']['tmp_name'];
            $handle = fopen($file, 'r');
            
            // Detectar automáticamente el BOM UTF-8 y saltarlo si existe
            $bom = fread($handle, 3);
            if ($bom !== "\xEF\xBB\xBF") {
                // Si no es BOM, rebobinar al inicio
                rewind($handle);
            }
            
            $header = fgetcsv($handle, 0, $separador);
            if (!$header) { echo '<div class="notice notice-error"><p>CSV inválido</p></div>'; return; }
            $count = 0; $skipped=0; $updated=0;
            while (($row = fgetcsv($handle, 0, $separador)) !== false) {
                if (count($row) !== count($header)) { $skipped++; continue; }
                $data = array_combine($header, $row);
                $titulo = sanitize_text_field($data['titulo'] ?? '');
                if (!$titulo) { $skipped++; continue; }
                $nombre = sanitize_text_field($data['nombre'] ?? '');
                $org = sanitize_text_field($data['organizacion'] ?? '');
                $mesa = sanitize_text_field($data['mesa'] ?? '');
                $evento = sanitize_text_field($data['evento'] ?? '');
                $observ = sanitize_textarea_field($data['observaciones'] ?? '');
                $checkin_val = (isset($data['checkin']) && ($data['checkin'] === '1' || strtolower($data['checkin'])==='true')) ? 1 : 0;

                // Buscar invitado existente por título Y evento
                $do_create = true;
                $post_id = 0;
                
                // Primero buscar el término del evento
                $evento_term = term_exists($evento, 'evento');
                if ($evento_term) {
                    $evento_term_id = is_array($evento_term) ? $evento_term['term_id'] : $evento_term;
                    
                    // Buscar posts que tengan el mismo título Y el mismo evento
                    $existing_posts = get_posts(array(
                        'post_type' => 'invitado',
                        'title' => $titulo,
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'evento',
                                'field' => 'term_id',
                                'terms' => $evento_term_id
                            )
                        ),
                        'numberposts' => 1,
                        'post_status' => 'any'
                    ));
                    
                    if (!empty($existing_posts)) {
                        $existing = $existing_posts[0];
                        if ($update_existing) {
                            $post_id = $existing->ID;
                            $do_create = false;
                            $updated++;
                        } else {
                            $skipped++;
                            $do_create = false;
                        }
                    }
                }

                if ($do_create) {
                    $post_id = wp_insert_post(['post_type'=>'invitado','post_title'=>$titulo,'post_status'=>'publish']);
                    if (!$post_id) { $skipped++; continue; }
                }

                // update metas
                update_post_meta($post_id,'c8_ticket',$titulo);
                update_post_meta($post_id,'c8_nombre',$nombre);
                update_post_meta($post_id,'c8_organizacion',$org);
                update_post_meta($post_id,'c8_mesa',$mesa);
                update_post_meta($post_id,'c8_observaciones',$observ);
                update_post_meta($post_id,'c8_checkin',$checkin_val);
                if ($checkin_val) update_post_meta($post_id,'c8_checkin_at', current_time('Y-m-d H:i:s'));

                // set event term
                if ($evento) {
                    $term = term_exists($evento,'evento');
                    if (!$term) $term = wp_insert_term($evento,'evento');
                    if (!is_wp_error($term)) {
                        $term_id = is_array($term) ? $term['term_id'] : $term;
                        if (!empty($term_id)) {
    						wp_set_object_terms($post_id, intval($term_id), 'evento', false);
						}
                    }
                }

                $count++;
            }
            fclose($handle);
            echo '<div class="notice notice-success"><p>Import finalizado. Nuevos: '.$count.' — Actualizados: '.$updated.' — Saltados: '.$skipped.'</p></div>';
        } else {
            echo '<div class="notice notice-error"><p>No se recibió archivo.</p></div>';
        }
    }

    // Export form
    echo '<hr><h2>Exportar CSV</h2>';
    echo '<form method="post">';
    wp_nonce_field('c8ecm_export_action','c8ecm_export_nonce');
    submit_button('Descargar CSV de Invitados', 'secondary', 'c8ecm_do_export');
    echo '</form>';

    if (isset($_POST['c8ecm_do_export'])) {
		if (!isset($_POST['c8ecm_export_nonce']) || !wp_verify_nonce($_POST['c8ecm_export_nonce'],'c8ecm_export_action')) {
			echo '<div class="notice notice-error"><p>Nonce inválido</p></div>';
		} else {
			$filename = 'invitados_export_'.date('Ymd').'.csv';

			// Limpiar cualquier salida previa y enviar headers
			if (ob_get_level()) ob_end_clean();

			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename='.$filename);
			header('Pragma: no-cache');
			header('Expires: 0');

			$out = fopen('php://output','w');

			// Agregar BOM para UTF-8 en Excel
			fwrite($out, "\xEF\xBB\xBF");

			fputcsv($out, ['titulo','nombre','organizacion','mesa','evento','observaciones','checkin']);

			$q = new WP_Query([
				'post_type'=>'invitado',
				'posts_per_page'=>-1,
				'post_status'=>'publish',
				'orderby' => 'title',
				'order' => 'ASC'
			]);

			while ($q->have_posts()) {
				$q->the_post();
				$id = get_the_ID();
				$title = get_the_title();
				$nombre = get_post_meta($id,'c8_nombre',true);
				$org = get_post_meta($id,'c8_organizacion',true);
				$mesa = get_post_meta($id,'c8_mesa',true);
				$terms = c8ecm_get_evento_terms($id);
				$evento = $terms ? $terms[0]->name : '';
				$observ = get_post_meta($id,'c8_observaciones',true);
				$check = get_post_meta($id,'c8_checkin',true) ? '1' : '0';
				fputcsv($out, [$title, $nombre, $org, $mesa, $evento, $observ, $check]);
			}

			wp_reset_postdata();
			fclose($out);
			exit; // Importante: terminar la ejecución aquí
		}
	}

    echo '</div>';
}

/* -----------------------
   Helper function para obtener eventos de forma segura
   ----------------------- */
function c8ecm_get_evento_terms($post_id) {
    $terms = get_the_terms($post_id, 'evento');
    if ($terms && !is_wp_error($terms) && !empty($terms)) {
        return $terms;
    }
    return false;
}

/* -----------------------
   8. Small helper: ensure menu 'Invitados' only visible to admins
   (capabilities were already set on CPT registration)
   ----------------------- */

/* -----------------------
   End plugin
   ----------------------- */