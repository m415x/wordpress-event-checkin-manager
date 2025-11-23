<?php
/**
 * Plugin Name: Código8 – Event Check-in Manager
 * Plugin URI: https://codigo8.com/download/event-checkin-manager/
 * Description: Gestión de invitados, importador/exportador CSV, taxonomía Evento, check-in por QR con AJAX, registro del usuario que hizo el check-in y observaciones. v2.0.0
 * Version: 2.0.0
 * Author: Código8
 * Author URI: https://codigo8.com
 * License: GPLv2 or later
 * Text Domain: codigo8-event-checkin-manager
 */

if (!defined('ABSPATH')) exit;

/* -----------------------
   1. CPT 'invitado' (solo admins)
   ----------------------- */
add_action('init', function(){
    $labels = [
        'name' => 'Invitados',
        'singular_name' => 'Invitado',
        'add_new' => 'Agregar Invitado',
        'add_new_item' => 'Agregar Invitado',
        'edit_item' => 'Editar Invitado',
    ];

    register_post_type('invitado', [
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 20,
        'menu_icon' => 'dashicons-tickets-alt',
        'supports' => ['title'],
        'capability_type' => 'post',
        'capabilities' => [
            'publish_posts' => 'manage_options',
            'edit_posts' => 'manage_options',
            'edit_others_posts' => 'manage_options',
            'delete_posts' => 'manage_options',
            'delete_others_posts' => 'manage_options',
            'read_private_posts' => 'manage_options',
            'edit_post' => 'manage_options',
            'delete_post' => 'manage_options',
            'read_post' => 'manage_options',
            'create_posts' => 'manage_options',
        ],
        'map_meta_cap' => true,
    ]);

    register_taxonomy('evento', 'invitado', [
        'labels' => ['name'=>'Eventos','singular_name'=>'Evento'],
        'public' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'hierarchical' => false,
    ]);
});

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
    $evento_terms = get_the_terms($post->ID, 'evento');
    $evento = ($evento_terms && !is_wp_error($evento_terms)) ? $evento_terms[0]->name : '';
    ?>
    <p>
      <label><strong>Nombre completo</strong><br>
      <input class="c8-field-nombre" type="text" name="c8_nombre" value="<?php echo esc_attr($nombre); ?>" style="width:100%"></label>
    </p>
    <p>
      <label><strong>Organización</strong><br>
      <input class="c8-field-organizacion" type="text" name="c8_organizacion" value="<?php echo esc_attr($org); ?>" style="width:100%"></label>
    </p>
    <p>
      <label><strong>Mesa asignada</strong><br>
      <input class="c8-field-mesa" type="text" name="c8_mesa" value="<?php echo esc_attr($mesa); ?>" style="width:100%"></label>
    </p>
    <p>
      <label><strong>Evento</strong> <em>(nombre / slug)</em><br>
      <input class="c8-field-evento" type="text" name="c8_evento" value="<?php echo esc_attr($evento); ?>" style="width:100%"></label>
      <br><small>Si el término no existe se creará al guardar.</small>
    </p>
    <p>
      <label><strong>Observaciones (privadas)</strong><br>
      <textarea class="c8-field-observaciones" name="c8_observaciones" rows="3" style="width:100%"><?php echo esc_textarea($observ); ?></textarea></label>
    </p>
    <p>
      <strong>Estado check-in:</strong>
      <?php if($check): ?>
          <span style="color:green">Ingresado</span> — <?php echo esc_html($check_at); ?> <?php if($check_by) echo '('.esc_html($check_by).')'; ?>
      <?php else: ?>
          <span style="color:orange">Pendiente</span>
      <?php endif; ?>
    </p>
    <?php
}

add_action('save_post_invitado', function($post_id){
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!isset($_POST['c8_nonce']) || !wp_verify_nonce($_POST['c8_nonce'], 'c8_save_invitado')) return;
    if (!current_user_can('manage_options')) return;

    $nombre = isset($_POST['c8_nombre']) ? sanitize_text_field($_POST['c8_nombre']) : '';
    $org = isset($_POST['c8_organizacion']) ? sanitize_text_field($_POST['c8_organizacion']) : '';
    $mesa = isset($_POST['c8_mesa']) ? sanitize_text_field($_POST['c8_mesa']) : '';
    $observ = isset($_POST['c8_observaciones']) ? sanitize_textarea_field($_POST['c8_observaciones']) : '';
    $evento = isset($_POST['c8_evento']) ? sanitize_text_field($_POST['c8_evento']) : '';

    update_post_meta($post_id, 'c8_nombre', $nombre);
    update_post_meta($post_id, 'c8_organizacion', $org);
    update_post_meta($post_id, 'c8_mesa', $mesa);
    update_post_meta($post_id, 'c8_observaciones', $observ);

    // asignar taxonomía evento (crear si no existe)
    if ($evento) {
        $term = term_exists($evento, 'evento');
        if (!$term) {
            $term = wp_insert_term($evento, 'evento');
        }
        if (!is_wp_error($term)) {
            $term_id = is_array($term) ? $term['term_id'] : $term;
            wp_set_object_terms($post_id, intval($term_id), 'evento', false);
        }
    } else {
        wp_set_object_terms($post_id, [], 'evento', false);
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

    // Evento taxonomy
    $taxonomy = 'evento';
    $selected = isset($_GET[$taxonomy]) ? $_GET[$taxonomy] : '';
    wp_dropdown_categories([
        'show_option_all' => 'Todos los eventos',
        'taxonomy' => $taxonomy,
        'name' => $taxonomy,
        'selected' => $selected,
        'orderby' => 'name',
        'hide_empty' => false,
    ]);

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
            'field' => 'id',
            'terms' => intval($_GET['evento']),
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
    $ticket = isset($_GET['ticket']) ? sanitize_text_field($_GET['ticket']) : '';
    if (!$ticket) return '<p>No se especificó ticket.</p>';

    // determine event slug from current page slug if exists
    global $post;
    $event_slug = $post ? $post->post_name : '';

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

    ob_start();
    ?>
    <style>
    .c8c_wrapper{font-family:system-ui,Arial,sans-serif;padding:16px;max-width:420px;margin:0 auto;text-align:center;}
    .c8c_field {margin:8px 0;font-size:1.05rem;}
    .c8c_btn{background:#2d89ef;color:#fff;border:0;border-radius:10px;padding:12px 14px;font-size:1.05rem;width:100%;cursor:pointer;}
    .c8c_btn.disabled{opacity:.6;cursor:not-allowed;}
    .c8c_obs textarea{width:100%;min-height:80px;border-radius:6px;border:1px solid #ddd;padding:8px;}
    </style>

    <div class="c8c_wrapper">
      <h2 class="c8-field-nombre"><?php echo esc_html($nombre ?: $inv->post_title); ?></h2>
      <p class="c8-field-organizacion c8c_field"><strong>Organización:</strong> <?php echo esc_html($org); ?></p>
      <p class="c8-field-mesa c8c_field"><strong>Mesa:</strong> <?php echo esc_html($mesa); ?></p>
      <p class="c8-field-evento c8c_field"><strong>Evento:</strong> <?php echo esc_html($event_slug); ?></p>

      <?php if ($check): ?>
        <p style="color:green;font-weight:700;">✅ Ingresó: <?php echo esc_html($check_at); ?> <?php if($check_by) echo '('.esc_html($check_by).')'; ?></p>
        <?php if($observ): ?><p class="c8-field-observaciones"><strong>Observaciones:</strong><br><?php echo nl2br(esc_html($observ)); ?></p><?php endif; ?>
      <?php else: ?>
        <div class="c8c_obs c8-field-observaciones">
          <label for="c8_obs"><?php _e('Observaciones (opcional)'); ?></label>
          <textarea id="c8_obs" placeholder="Ej: alergia, silla extra..."></textarea>
        </div>
        <button class="c8c_btn" id="c8c_btn" data-postid="<?php echo esc_attr($inv->ID); ?>">
          ✅ Marcar ingreso
        </button>
        <p id="c8c_msg" style="margin-top:10px;display:none;"></p>

        <script>
        (function(){
            const btn = document.getElementById('c8c_btn');
            const msg = document.getElementById('c8c_msg');
            btn.addEventListener('click', function(){
                if(!confirm('Confirmar check-in para <?php echo esc_js($nombre ?: $inv->post_title); ?>?')) return;
                btn.disabled = true;
                btn.classList.add('disabled');
                btn.textContent = 'Procesando...';
                const postId = btn.dataset.postid;
                const observ = document.getElementById('c8_obs').value;
                const data = new URLSearchParams();
                data.append('action','c8ecm_checkin_ajax');
                data.append('post_id', postId);
                data.append('observ', observ);
                data.append('nonce', '<?php echo wp_create_nonce('c8ecm_checkin_nonce'); ?>');

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method:'POST',
                    credentials:'same-origin',
                    headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
                    body: data.toString()
                }).then(r=>r.json()).then(res=>{
                    if(res.success){
                        msg.style.display='block';
                        msg.style.color='green';
                        msg.innerText = res.data.message;
                        btn.style.display='none';
                        document.getElementById('c8_obs').style.display='none';
                    } else {
                        msg.style.display='block';
                        msg.style.color='red';
                        msg.innerText = 'Error: ' + (res.data ? res.data : 'Error');
                        btn.disabled = false;
                        btn.classList.remove('disabled');
                        btn.textContent = '✅ Marcar ingreso';
                    }
                }).catch(e=>{
                    msg.style.display='block';
                    msg.style.color='red';
                    msg.innerText = 'Error de red';
                    btn.disabled = false;
                    btn.classList.remove('disabled');
                    btn.textContent = '✅ Marcar ingreso';
                });
            });
        })();
        </script>
      <?php endif; ?>
    </div>
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

    $already = get_post_meta($post_id,'c8_checkin',true);
    if ($already) wp_send_json_error('Invitado ya ingresado');

    $observ = isset($_POST['observ']) ? sanitize_textarea_field($_POST['observ']) : '';

    $user = wp_get_current_user();
    $by = $user && $user->exists() ? ($user->display_name ?: $user->user_login) : 'anon@'.$_SERVER['REMOTE_ADDR'];

    update_post_meta($post_id,'c8_checkin',1);
    update_post_meta($post_id,'c8_checkin_at', current_time('Y-m-d H:i:s'));
    update_post_meta($post_id,'c8_checkin_by', $by);
    if ($observ) update_post_meta($post_id,'c8_observaciones_checkin', $observ);

    wp_send_json_success(['message' => 'Check-in registrado: ' . current_time('H:i')]);
}

/* -----------------------
   6. Shortcode [c8ecm_list] — listado / búsqueda / filtros + checkin rápido
   - Public (useful for staff devices); Elementor display conditions can hide page if needed
   ----------------------- */
add_shortcode('c8ecm_list', function($atts){
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
    </style>

    <div class="c8-list-wrap">
      <div class="c8-list-controls">
        <input class="c8-filter-input" id="c8_q" placeholder="Buscar por ticket, nombre, organización...">
        <input class="c8-filter-input" id="c8_evento_filter" placeholder="Evento (slug)">
        <input class="c8-filter-input" id="c8_organizacion_filter" placeholder="Organización">
        <input class="c8-filter-input" id="c8_mesa_filter" placeholder="Mesa">
        <button class="c8-list-btn" id="c8_refresh">Buscar</button>
      </div>
      <div id="c8_list_results">Cargando...</div>
    </div>

    <script>
    (function(){
      const ajaxUrl = '<?php echo admin_url('admin-ajax.php'); ?>';
      const results = document.getElementById('c8_list_results');

      function loadList(){
        const q = document.getElementById('c8_q').value;
        const evento = document.getElementById('c8_evento_filter').value;
        const org = document.getElementById('c8_organizacion_filter').value;
        const mesa = document.getElementById('c8_mesa_filter').value;
        results.innerHTML = 'Buscando...';
        const data = new URLSearchParams();
        data.append('action','c8ecm_list_ajax');
        data.append('q', q);
        data.append('evento', evento);
        data.append('organizacion', org);
        data.append('mesa', mesa);
        fetch(ajaxUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:data.toString()})
          .then(r=>r.text()).then(html=>{ results.innerHTML = html; attachHandlers(); })
          .catch(()=> results.innerHTML = 'Error de red');
      }

      document.getElementById('c8_refresh').addEventListener('click', loadList);
      document.getElementById('c8_q').addEventListener('keyup', function(e){ if(e.key === 'Enter') loadList(); });

      function attachHandlers(){
        document.querySelectorAll('.c8-do-checkin').forEach(btn=>{
          btn.addEventListener('click', function(){
            const id = this.dataset.id;
            if(!confirm('Confirmar check-in?')) return;
            this.disabled = true;
            this.textContent = 'Procesando...';
            const data = new URLSearchParams();
            data.append('action','c8ecm_checkin_ajax');
            data.append('post_id', id);
            data.append('observ', '');
            data.append('nonce', '<?php echo wp_create_nonce('c8ecm_checkin_nonce'); ?>');
            fetch(ajaxUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:data.toString()})
              .then(r=>r.json()).then(res=>{
                if(res.success){
                  btn.classList.add('green');
                  btn.textContent = 'Ingresó';
                } else {
                  alert('Error: ' + (res.data || ''));
                  btn.disabled = false;
                  btn.textContent = 'Marcar ingreso';
                }
              }).catch(()=>{ alert('Error de red'); btn.disabled=false; btn.textContent='Marcar ingreso'; });
          });
        });
      }

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
    $organizacion = isset($_POST['organizacion']) ? sanitize_text_field($_POST['organizacion']) : '';
    $mesa = isset($_POST['mesa']) ? sanitize_text_field($_POST['mesa']) : '';

    $meta_query = [];
    if ($organizacion) $meta_query[] = ['key'=>'c8_organizacion','value'=>$organizacion,'compare'=>'LIKE'];
    if ($mesa) $meta_query[] = ['key'=>'c8_mesa','value'=>$mesa,'compare'=>'LIKE'];

    $args = ['post_type'=>'invitado','posts_per_page'=>200,'s'=>$q,'meta_query'=>$meta_query];
    if ($evento) {
        $args['tax_query'] = [[ 'taxonomy'=>'evento','field'=>'slug','terms'=>$evento ]];
    }

    $posts = get_posts($args);
    if (!$posts) { echo '<p>No se encontraron invitados.</p>'; wp_die(); }

    echo '<table class="c8-list-table"><thead><tr><th>Ticket</th><th class="c8-field-nombre">Nombre</th><th class="c8-field-organizacion">Organización</th><th class="c8-field-mesa">Mesa</th><th>Evento</th><th>Check-in</th></tr></thead><tbody>';
    foreach($posts as $p){
        $nombre = get_post_meta($p->ID,'c8_nombre',true);
        $org = get_post_meta($p->ID,'c8_organizacion',true);
        $mesa = get_post_meta($p->ID,'c8_mesa',true);
        $terms = get_the_terms($p->ID,'evento');
        $evento_name = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
        $check = get_post_meta($p->ID,'c8_checkin',true);
        $check_at = get_post_meta($p->ID,'c8_checkin_at',true);
        $check_by = get_post_meta($p->ID,'c8_checkin_by',true);
        echo '<tr>';
        echo '<td>'.esc_html($p->post_title).'</td>';
        echo '<td class="c8-field-nombre">'.esc_html($nombre).'</td>';
        echo '<td class="c8-field-organizacion">'.esc_html($org).'</td>';
        echo '<td class="c8-field-mesa">'.esc_html($mesa).'</td>';
        echo '<td class="c8-field-evento">'.esc_html($evento_name).'</td>';
        echo '<td>';
        if ($check) {
            echo '<strong style="color:green">Ingresó</strong><br>'.esc_html($check_at).' '.esc_html($check_by);
        } else {
            echo '<button class="c8-do-checkin c8-list-btn" data-id="'.esc_attr($p->ID).'">Marcar ingreso</button>';
        }
        echo '</td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    wp_die();
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
    submit_button('Importar Invitados', 'primary', 'c8ecm_do_import');
    echo '</form>';

    // handle import
    if (isset($_POST['c8ecm_do_import'])) {
        if (!isset($_POST['c8ecm_import_nonce']) || !wp_verify_nonce($_POST['c8ecm_import_nonce'],'c8ecm_import_action')) {
            echo '<div class="notice notice-error"><p>Nonce inválido</p></div>';
        } else if (!empty($_FILES['c8ecm_csv']['tmp_name'])) {
            $update_existing = isset($_POST['update_existing']);
            $file = $_FILES['c8ecm_csv']['tmp_name'];
            $handle = fopen($file, 'r');
            $header = fgetcsv($handle, 0, ',');
            if (!$header) { echo '<div class="notice notice-error"><p>CSV inválido</p></div>'; return; }
            $count = 0; $skipped=0; $updated=0;
            while (($row = fgetcsv($handle,0,',')) !== false) {
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

                // find existing by title & evento
                $existing = get_page_by_title($titulo, OBJECT, 'invitado');
                $do_create = true;
                if ($existing) {
                    // check event match
                    $terms = get_the_terms($existing->ID,'evento');
                    $exists_same_event = false;
                    if ($evento && $terms && !is_wp_error($terms)){
                        foreach($terms as $t) if(sanitize_title($t->name) === sanitize_title($evento)) { $exists_same_event = true; break; }
                    }
                    if ($existing && $exists_same_event) {
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
                        wp_set_object_terms($post_id, intval($term_id), 'evento', false);
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
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename='.$filename);
            $out = fopen('php://output','w');
            fputcsv($out, ['titulo','nombre','organizacion','mesa','evento','observaciones','checkin']);
            $q = new WP_Query(['post_type'=>'invitado','posts_per_page'=>-1,'post_status'=>'publish']);
            while ($q->have_posts()) {
                $q->the_post();
                $id = get_the_ID();
                $title = get_the_title();
                $nombre = get_post_meta($id,'c8_nombre',true);
                $org = get_post_meta($id,'c8_organizacion',true);
                $mesa = get_post_meta($id,'c8_mesa',true);
                $terms = get_the_terms($id,'evento');
                $evento = ($terms && !is_wp_error($terms)) ? $terms[0]->name : '';
                $observ = get_post_meta($id,'c8_observaciones',true);
                $check = get_post_meta($id,'c8_checkin',true) ? '1' : '0';
                fputcsv($out, [$title, $nombre, $org, $mesa, $evento, $observ, $check]);
            }
            wp_reset_postdata();
            fclose($out);
            exit;
        }
    }

    echo '</div>';
}

/* -----------------------
   8. Small helper: ensure menu 'Invitados' only visible to admins
   (capabilities were already set on CPT registration)
   ----------------------- */

/* -----------------------
   End plugin
   ----------------------- */
