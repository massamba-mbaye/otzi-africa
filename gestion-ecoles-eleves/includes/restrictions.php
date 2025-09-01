<?php
/**
 * Fichier : includes/restrictions.php
 * Gestion des restrictions d'accès pour les directeurs et maires
 */

// Sécurité : Empêcher un accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Rediriger les directeurs et maires après connexion
 */
function gcee_redirect_after_login($redirect_to, $request, $user) {
    if (is_wp_error($user)) {
        return $redirect_to;
    }
    
    if (in_array('directeur_ecole', $user->roles)) {
        return admin_url('edit.php?post_type=school');
    }
    
    if (in_array('maire_commune', $user->roles)) {
        return admin_url('admin.php?page=mayor-dashboard');
    }
    
    return $redirect_to;
}
add_filter('login_redirect', 'gcee_redirect_after_login', 10, 3);

/**
 * Rediriger les directeurs et maires loin du tableau de bord principal
 */
function gcee_redirect_dashboard() {
    $user = wp_get_current_user();
    global $pagenow;
    
    if (in_array('directeur_ecole', $user->roles)) {
        if ($pagenow === 'index.php') {
            wp_redirect(admin_url('edit.php?post_type=school'));
            exit;
        }
    }
    
    if (in_array('maire_commune', $user->roles)) {
        if ($pagenow === 'index.php') {
            wp_redirect(admin_url('admin.php?page=mayor-dashboard'));
            exit;
        }
    }
}
add_action('admin_init', 'gcee_redirect_dashboard');

/**
 * Restreindre l'accès aux menus d'administration pour les directeurs
 */
function gcee_restrict_director_admin_menu() {
    $user = wp_get_current_user();
    
    if (in_array('directeur_ecole', $user->roles)) {
        global $menu, $submenu;
        
        // Menus autorisés pour les directeurs
        $allowed_menus = array(
            'edit.php?post_type=school',
            'edit.php?post_type=student',
            'profile.php'
        );
        
        // Supprimer tous les menus non autorisés
        foreach ($menu as $key => $item) {
            if (!in_array($item[2], $allowed_menus) && $item[2] !== 'separator1' && $item[2] !== 'separator2') {
                unset($menu[$key]);
            }
        }
        
        // Supprimer certains sous-menus même pour les menus autorisés
        if (isset($submenu['edit.php?post_type=school'])) {
            foreach ($submenu['edit.php?post_type=school'] as $key => $item) {
                if ($item[2] === 'edit-tags.php?taxonomy=commune&post_type=school') {
                    unset($submenu['edit.php?post_type=school'][$key]);
                }
            }
        }
    }
}
add_action('admin_menu', 'gcee_restrict_director_admin_menu', 999);

/**
 * Permettre aux maires d'accéder au menu des écoles
 */
function gcee_add_schools_menu_for_mayors() {
    $user = wp_get_current_user();
    if (in_array('maire_commune', $user->roles)) {
        add_menu_page(
            'Écoles de ma commune',
            'Écoles',
            'read',
            'edit.php?post_type=school',
            '',
            'dashicons-building',
            25
        );
    }
}
add_action('admin_menu', 'gcee_add_schools_menu_for_mayors');
/**
 * Restreindre l'accès aux menus d'administration pour les maires (sauf écoles)
 */
function gcee_restrict_mayor_admin_menu() {
    $user = wp_get_current_user();
    
    if (in_array('maire_commune', $user->roles)) {
        global $menu;
        
        // Menus autorisés pour les maires
        $allowed_menus = array(
            'admin.php?page=mayor-dashboard',
            'edit.php?post_type=school',
            'profile.php'
        );
        
        // Supprimer tous les menus non autorisés
        foreach ($menu as $key => $item) {
            if (!in_array($item[2], $allowed_menus) && 
                $item[2] !== 'separator1' && 
                $item[2] !== 'separator2' && 
                $item[2] !== 'separator-last') {
                unset($menu[$key]);
            }
        }
    }
}
add_action('admin_menu', 'gcee_restrict_mayor_admin_menu', 999);

/**
 * Restreindre la vue des écoles pour les directeurs (seulement leurs écoles)
 */
function gcee_restrict_director_schools($query) {
    if (is_admin() && $query->is_main_query()) {
        $user = wp_get_current_user();
        
        if (in_array('directeur_ecole', $user->roles)) {
            if ($query->get('post_type') === 'school') {
                // Si c'est un nouvel utilisateur directeur, montrer toutes les écoles sans auteur
                $user_schools = get_posts(array(
                    'post_type' => 'school',
                    'author' => $user->ID,
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ));
                
                // Si le directeur n'a pas encore d'écoles, on lui permet de voir les écoles sans auteur
                if (empty($user_schools)) {
                    $query->set('meta_query', array(
                        'relation' => 'OR',
                        array(
                            'key' => '_school_director',
                            'value' => $user->ID,
                            'compare' => '='
                        ),
                        array(
                            'key' => '_school_director',
                            'compare' => 'NOT EXISTS'
                        )
                    ));
                } else {
                    // Sinon, montrer seulement ses écoles
                    $query->set('author', $user->ID);
                }
            }
            
            if ($query->get('post_type') === 'student') {
                // Pour les élèves, montrer seulement ceux associés aux écoles du directeur
                $user_schools = get_posts(array(
                    'post_type' => 'school',
                    'author' => $user->ID,
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ));
                
                if (!empty($user_schools)) {
                    $query->set('meta_query', array(
                        array(
                            'key' => '_associated_school',
                            'value' => $user_schools,
                            'compare' => 'IN'
                        )
                    ));
                } else {
                    // Si pas d'écoles, pas d'élèves
                    $query->set('post__in', array(0));
                }
            }
        }
    }
}
add_action('pre_get_posts', 'gcee_restrict_director_schools');

/**
 * Vérifier les permissions pour la vue des écoles (maires)
 */
function gcee_check_school_view_permission($caps, $cap, $user_id, $args) {
    if ($cap === 'read_post' && isset($args[0])) {
        $post = get_post($args[0]);
        
        if ($post && $post->post_type === 'school') {
            $user = get_userdata($user_id);
            
            if (in_array('maire_commune', $user->roles)) {
                // Le maire peut voir les écoles de sa commune
                $mayor_commune = get_user_meta($user_id, '_associated_commune', true);
                $school_communes = wp_get_post_terms($post->ID, 'commune', array('fields' => 'ids'));
                
                if (in_array($mayor_commune, $school_communes)) {
                    $caps = array('read');
                } else {
                    $caps = array('do_not_allow');
                }
            }
        }
    }
    
    return $caps;
}
add_filter('map_meta_cap', 'gcee_check_school_view_permission', 10, 4);
function gcee_check_school_edit_permission($caps, $cap, $user_id, $args) {
    if ($cap === 'edit_post' && isset($args[0])) {
        $post = get_post($args[0]);
        
        if ($post && $post->post_type === 'school') {
            $user = get_userdata($user_id);
            
            if (in_array('directeur_ecole', $user->roles)) {
                // Le directeur peut éditer ses propres écoles ou celles assignées à lui
                if ($post->post_author == $user_id || get_post_meta($post->ID, '_school_director', true) == $user_id) {
                    $caps = array('edit_posts');
                } else {
                    $caps = array('do_not_allow');
                }
            }
        }
    }
    
    return $caps;
}
add_filter('map_meta_cap', 'gcee_check_school_edit_permission', 10, 4);

/**
 * Vérifier les permissions pour l'édition des élèves
 */
function gcee_check_student_edit_permission($caps, $cap, $user_id, $args) {
    if ($cap === 'edit_post' && isset($args[0])) {
        $post = get_post($args[0]);
        
        if ($post && $post->post_type === 'student') {
            $user = get_userdata($user_id);
            
            if (in_array('directeur_ecole', $user->roles)) {
                // Vérifier si l'élève est associé à une école du directeur
                $associated_school = get_post_meta($post->ID, '_associated_school', true);
                
                if ($associated_school) {
                    $school = get_post($associated_school);
                    if ($school && ($school->post_author == $user_id || get_post_meta($school->ID, '_school_director', true) == $user_id)) {
                        $caps = array('edit_posts');
                    } else {
                        $caps = array('do_not_allow');
                    }
                } else {
                    // Si pas d'école associée, autoriser l'édition pour permettre l'association
                    $caps = array('edit_posts');
                }
            }
        }
    }
    
    return $caps;
}
add_filter('map_meta_cap', 'gcee_check_student_edit_permission', 10, 4);

/**
 * Assigner automatiquement le directeur à une école qu'il crée
 */
function gcee_assign_director_to_school($post_id, $post, $update) {
    if ($post->post_type === 'school' && !$update) {
        $user = wp_get_current_user();
        
        if (in_array('directeur_ecole', $user->roles)) {
            update_post_meta($post_id, '_school_director', $user->ID);
        }
    }
}
add_action('wp_insert_post', 'gcee_assign_director_to_school', 10, 3);

/**
 * Empêcher les directeurs de voir la taxonomie Commune
 */
function gcee_remove_commune_meta_box() {
    $user = wp_get_current_user();
    
    if (in_array('directeur_ecole', $user->roles)) {
        remove_meta_box('communediv', 'school', 'side');
    }
}
add_action('admin_menu', 'gcee_remove_commune_meta_box');

/**
 * Validation des écoles par les maires
 */
function gcee_add_school_validation_meta_box() {
    add_meta_box(
        'school_validation',
        'Validation par le Maire',
        'gcee_school_validation_meta_box',
        'school',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'gcee_add_school_validation_meta_box');

function gcee_school_validation_meta_box($post) {
    $validation_status = get_post_meta($post->ID, '_validation_status', true);
    $validation_date = get_post_meta($post->ID, '_validation_date', true);
    $validated_by = get_post_meta($post->ID, '_validated_by', true);
    
    $user = wp_get_current_user();
    $is_mayor = in_array('maire_commune', $user->roles);
    $is_admin = in_array('administrator', $user->roles);
    
    // Vérifier si le maire peut valider cette école (école dans sa commune)
    $can_validate = false;
    if ($is_mayor) {
        $mayor_commune = get_user_meta($user->ID, '_associated_commune', true);
        $school_communes = wp_get_post_terms($post->ID, 'commune', array('fields' => 'ids'));
        $can_validate = in_array($mayor_commune, $school_communes);
    }
    
    ?>
    <div>
        <p><strong>Statut actuel :</strong> 
            <?php
            switch ($validation_status) {
                case 'validated':
                    echo '<span style="color: green;">✓ Validée</span>';
                    break;
                case 'rejected':
                    echo '<span style="color: red;">✗ Rejetée</span>';
                    break;
                default:
                    echo '<span style="color: orange;">En attente</span>';
            }
            ?>
        </p>
        
        <?php if ($validation_date): ?>
            <p><strong>Date de validation :</strong> <?php echo date('d/m/Y H:i', strtotime($validation_date)); ?></p>
        <?php endif; ?>
        
        <?php if ($validated_by): ?>
            <?php $validator = get_userdata($validated_by); ?>
            <p><strong>Validée par :</strong> <?php echo $validator->display_name; ?></p>
        <?php endif; ?>
        
        <?php if (($is_mayor && $can_validate) || $is_admin): ?>
            <hr>
            <p><strong>Actions :</strong></p>
            <button type="button" class="button button-primary" onclick="validateSchoolMeta(<?php echo $post->ID; ?>, 'validated')">
                Valider l'école
            </button>
            <button type="button" class="button" onclick="validateSchoolMeta(<?php echo $post->ID; ?>, 'rejected')">
                Rejeter l'école
            </button>
            
            <script>
            function validateSchoolMeta(postId, status) {
                if (confirm('Êtes-vous sûr de vouloir ' + (status === 'validated' ? 'valider' : 'rejeter') + ' cette école ?')) {
                    jQuery.post(ajaxurl, {
                        action: 'validate_school',
                        post_id: postId,
                        status: status,
                        nonce: '<?php echo wp_create_nonce('validate_school_' . get_current_user_id()); ?>'
                    }, function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Erreur : ' + response.data);
                        }
                    });
                }
            }
            </script>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Ajouter une colonne pour le statut de validation dans la liste des écoles
 */
function gcee_add_validation_column($columns) {
    $columns['validation_status'] = 'Statut de validation';
    
    // Pour les maires, ajouter une colonne d'actions
    $user = wp_get_current_user();
    if (in_array('maire_commune', $user->roles)) {
        $columns['mayor_actions'] = 'Actions';
    }
    
    return $columns;
}
add_filter('manage_school_posts_columns', 'gcee_add_validation_column');

function gcee_display_validation_column($column, $post_id) {
    if ($column === 'validation_status') {
        $validation_status = get_post_meta($post_id, '_validation_status', true);
        
        switch ($validation_status) {
            case 'validated':
                echo '<span style="color: green;">✓ Validée</span>';
                break;
            case 'rejected':
                echo '<span style="color: red;">✗ Rejetée</span>';
                break;
            default:
                echo '<span style="color: orange;">En attente</span>';
        }
    }
    
    if ($column === 'mayor_actions') {
        $user = wp_get_current_user();
        if (in_array('maire_commune', $user->roles)) {
            $mayor_commune = get_user_meta($user->ID, '_associated_commune', true);
            $school_communes = wp_get_post_terms($post_id, 'commune', array('fields' => 'ids'));
            
            // Vérifier si l'école est dans la commune du maire
            if ($mayor_commune && is_array($school_communes) && in_array($mayor_commune, $school_communes)) {
                echo '<button type="button" class="button button-small gcee-open-validation-popup" data-school-id="' . $post_id . '">Voir/Valider</button>';
            }
        }
    }
}
add_action('manage_school_posts_custom_column', 'gcee_display_validation_column', 10, 2);

/**
 * Ajouter le popup de validation et le JavaScript pour les maires
 */
function gcee_add_mayor_validation_popup() {
    $user = wp_get_current_user();
    $screen = get_current_screen();
    
    if (in_array('maire_commune', $user->roles) && $screen && $screen->id === 'edit-school') {
        ?>
        <!-- Popup de validation -->
        <div id="gcee-validation-popup" style="display: none;">
            <div class="gcee-popup-overlay">
                <div class="gcee-popup-content">
                    <div class="gcee-popup-header">
                        <h2>Validation de l'école</h2>
                        <button type="button" class="gcee-popup-close">&times;</button>
                    </div>
                    <div class="gcee-popup-body">
                        <div id="gcee-school-details"></div>
                        <div class="gcee-validation-actions">
                            <p><strong>Actions de validation :</strong></p>
                            <button type="button" id="gcee-validate-school" class="button button-primary">Valider l'école</button>
                            <button type="button" id="gcee-reject-school" class="button">Rejeter l'école</button>
                        </div>
                        <div id="gcee-validation-result" style="display: none;"></div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            .gcee-popup-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background-color: rgba(0, 0, 0, 0.5);
                z-index: 100000;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .gcee-popup-content {
                background: white;
                border-radius: 6px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
                max-width: 600px;
                width: 90%;
                max-height: 80vh;
                overflow-y: auto;
            }
            .gcee-popup-header {
                padding: 20px 20px 10px;
                border-bottom: 1px solid #eee;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            .gcee-popup-header h2 {
                margin: 0;
                color: #23282d;
            }
            .gcee-popup-close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            .gcee-popup-close:hover {
                color: #d63638;
            }
            .gcee-popup-body {
                padding: 20px;
            }
            .gcee-school-info {
                background: #f9f9f9;
                padding: 15px;
                border-radius: 4px;
                margin-bottom: 20px;
            }
            .gcee-school-info h3 {
                margin: 0 0 10px 0;
                color: #23282d;
            }
            .gcee-school-detail {
                margin-bottom: 8px;
            }
            .gcee-school-detail strong {
                color: #1d2327;
            }
            .gcee-validation-actions {
                background: #fff8e5;
                padding: 15px;
                border-radius: 4px;
                border-left: 4px solid #ffb900;
                margin-top: 20px;
            }
            .gcee-validation-actions p {
                margin: 0 0 10px 0;
                font-weight: 600;
            }
            .gcee-validation-actions button {
                margin-right: 10px;
            }
            #gcee-validation-result {
                margin-top: 15px;
                padding: 10px;
                border-radius: 4px;
            }
            .gcee-result-success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .gcee-result-error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
        </style>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            let currentSchoolId = null;

            // Ouvrir le popup
            $(document).on('click', '.gcee-open-validation-popup', function() {
                currentSchoolId = $(this).data('school-id');
                loadSchoolDetails(currentSchoolId);
                $('#gcee-validation-popup').show();
            });

            // Fermer le popup
            $(document).on('click', '.gcee-popup-close, .gcee-popup-overlay', function(e) {
                if (e.target === this) {
                    $('#gcee-validation-popup').hide();
                    currentSchoolId = null;
                }
            });

            // Valider l'école
            $('#gcee-validate-school').on('click', function() {
                validateSchool(currentSchoolId, 'validated');
            });

            // Rejeter l'école
            $('#gcee-reject-school').on('click', function() {
                validateSchool(currentSchoolId, 'rejected');
            });

            // Charger les détails de l'école
            function loadSchoolDetails(schoolId) {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'get_school_details',
                        school_id: schoolId,
                        nonce: '<?php echo wp_create_nonce('get_school_details'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('#gcee-school-details').html(response.data.html);
                        } else {
                            $('#gcee-school-details').html('<p>Erreur lors du chargement des détails.</p>');
                        }
                    },
                    error: function() {
                        $('#gcee-school-details').html('<p>Erreur de connexion.</p>');
                    }
                });
            }

            // Valider/Rejeter l'école
            function validateSchool(schoolId, status) {
                if (!confirm('Êtes-vous sûr de vouloir ' + (status === 'validated' ? 'valider' : 'rejeter') + ' cette école ?')) {
                    return;
                }

                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'validate_school',
                        post_id: schoolId,
                        status: status,
                        nonce: '<?php echo wp_create_nonce('validate_school_' . get_current_user_id()); ?>'
                    },
                    success: function(response) {
                        const resultDiv = $('#gcee-validation-result');
                        if (response.success) {
                            resultDiv.removeClass('gcee-result-error').addClass('gcee-result-success');
                            resultDiv.html('École ' + (status === 'validated' ? 'validée' : 'rejetée') + ' avec succès !');
                            
                            // Recharger la page après 2 secondes
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            resultDiv.removeClass('gcee-result-success').addClass('gcee-result-error');
                            resultDiv.html('Erreur : ' + response.data);
                        }
                        resultDiv.show();
                    },
                    error: function() {
                        const resultDiv = $('#gcee-validation-result');
                        resultDiv.removeClass('gcee-result-success').addClass('gcee-result-error');
                        resultDiv.html('Erreur de connexion.');
                        resultDiv.show();
                    }
                });
            }
        });
        </script>
        <?php
    }
}
add_action('admin_footer', 'gcee_add_mayor_validation_popup');

/**
 * AJAX handler pour récupérer les détails d'une école
 */
function gcee_get_school_details_ajax() {
    if (!wp_verify_nonce($_POST['nonce'], 'get_school_details')) {
        wp_send_json_error('Nonce invalide');
    }
    
    $school_id = intval($_POST['school_id']);
    $user = wp_get_current_user();
    
    // Vérifier les permissions
    if (!in_array('maire_commune', $user->roles)) {
        wp_send_json_error('Permissions insuffisantes');
    }
    
    // Vérifier que l'école est dans la commune du maire
    $mayor_commune = get_user_meta($user->ID, '_associated_commune', true);
    $school_communes = wp_get_post_terms($school_id, 'commune', array('fields' => 'ids'));
    
    if (!$mayor_commune || !is_array($school_communes) || !in_array($mayor_commune, $school_communes)) {
        wp_send_json_error('Cette école n\'est pas dans votre commune');
    }
    
    $school = get_post($school_id);
    if (!$school) {
        wp_send_json_error('École non trouvée');
    }
    
    // Récupérer les détails de l'école
    $school_meta = array(
        'nom_localite' => get_post_meta($school_id, '_nom_localite', true),
        'type_ecole' => get_post_meta($school_id, '_type_ecole', true),
        'nom_directeur' => get_post_meta($school_id, '_nom_directeur', true),
        'tel_directeur' => get_post_meta($school_id, '_tel_directeur', true),
        'email_directeur' => get_post_meta($school_id, '_email_directeur', true),
        'nombre_eleves' => get_post_meta($school_id, '_nombre_eleves', true),
        'nombre_garcons' => get_post_meta($school_id, '_nombre_garcons', true),
        'nombre_filles' => get_post_meta($school_id, '_nombre_filles', true),
        'nombre_salles' => get_post_meta($school_id, '_nombre_salles', true),
        'nombre_enseignants' => get_post_meta($school_id, '_nombre_enseignants', true),
        'electricite' => get_post_meta($school_id, '_electricite', true),
        'cloture' => get_post_meta($school_id, '_cloture', true),
        'validation_status' => get_post_meta($school_id, '_validation_status', true)
    );
    
    // Statut actuel
    $status_text = '';
    $status_color = '';
    switch ($school_meta['validation_status']) {
        case 'validated':
            $status_text = 'Validée';
            $status_color = 'green';
            break;
        case 'rejected':
            $status_text = 'Rejetée';
            $status_color = 'red';
            break;
        default:
            $status_text = 'En attente';
            $status_color = 'orange';
    }
    
    // Générer le HTML
    $html = '<div class="gcee-school-info">';
    $html .= '<h3>' . esc_html($school->post_title) . '</h3>';
    $html .= '<div class="gcee-school-detail"><strong>Statut :</strong> <span style="color: ' . $status_color . ';">' . $status_text . '</span></div>';
    $html .= '<div class="gcee-school-detail"><strong>Localité :</strong> ' . esc_html($school_meta['nom_localite']) . '</div>';
    $html .= '<div class="gcee-school-detail"><strong>Type :</strong> ' . esc_html(ucfirst($school_meta['type_ecole'])) . '</div>';
    $html .= '<div class="gcee-school-detail"><strong>Directeur :</strong> ' . esc_html($school_meta['nom_directeur']) . '</div>';
    $html .= '<div class="gcee-school-detail"><strong>Téléphone :</strong> ' . esc_html($school_meta['tel_directeur']) . '</div>';
    $html .= '<div class="gcee-school-detail"><strong>Email :</strong> ' . esc_html($school_meta['email_directeur']) . '</div>';
    $html .= '<div class="gcee-school-detail"><strong>Élèves :</strong> ' . esc_html($school_meta['nombre_eleves']) . ' (Garçons: ' . esc_html($school_meta['nombre_garcons']) . ', Filles: ' . esc_html($school_meta['nombre_filles']) . ')</div>';
    $html .= '<div class="gcee-school-detail"><strong>Salles de classe :</strong> ' . esc_html($school_meta['nombre_salles']) . '</div>';
    $html .= '<div class="gcee-school-detail"><strong>Enseignants :</strong> ' . esc_html($school_meta['nombre_enseignants']) . '</div>';
    $html .= '<div class="gcee-school-detail"><strong>Électricité :</strong> ' . esc_html($school_meta['electricite'] === 'oui' ? 'Oui' : 'Non') . '</div>';
    $html .= '<div class="gcee-school-detail"><strong>Clôturée :</strong> ' . esc_html($school_meta['cloture'] === 'oui' ? 'Oui' : 'Non') . '</div>';
    $html .= '</div>';
    
    wp_send_json_success(array('html' => $html));
}
add_action('wp_ajax_get_school_details', 'gcee_get_school_details_ajax');

/**
 * AJAX handler pour la validation des écoles (mis à jour)
 */
function gcee_validate_school_ajax() {
    $nonce_action = 'validate_school_' . get_current_user_id();
    if (!wp_verify_nonce($_POST['nonce'], $nonce_action)) {
        wp_send_json_error('Nonce invalide');
    }
    
    $post_id = intval($_POST['post_id']);
    $status = sanitize_text_field($_POST['status']);
    $user = wp_get_current_user();
    
    // Vérifier les permissions
    if (!in_array('administrator', $user->roles) && !in_array('maire_commune', $user->roles)) {
        wp_send_json_error('Permissions insuffisantes');
    }
    
    // Si c'est un maire, vérifier qu'il peut valider cette école
    if (in_array('maire_commune', $user->roles)) {
        $mayor_commune = get_user_meta($user->ID, '_associated_commune', true);
        $school_communes = wp_get_post_terms($post_id, 'commune', array('fields' => 'ids'));
        
        if (!in_array($mayor_commune, $school_communes)) {
            wp_send_json_error('Vous ne pouvez valider que les écoles de votre commune');
        }
    }
    
    // Mettre à jour le statut de validation
    update_post_meta($post_id, '_validation_status', $status);
    update_post_meta($post_id, '_validation_date', current_time('mysql'));
    update_post_meta($post_id, '_validated_by', $user->ID);
    
    wp_send_json_success('École ' . ($status === 'validated' ? 'validée' : 'rejetée') . ' avec succès');
}
add_action('wp_ajax_validate_school', 'gcee_validate_school_ajax');