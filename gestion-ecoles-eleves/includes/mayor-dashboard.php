<?php
/**
 * Fichier : includes/mayor-dashboard.php
 * Tableau de bord pour les maires
 */

// Sécurité : Empêcher un accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ajouter la page du tableau de bord pour les maires
 */
function gcee_add_mayor_dashboard() {
    add_menu_page(
        'Tableau de bord Maire',
        'Tableau de bord',
        'maire_commune',
        'mayor-dashboard',
        'gcee_render_mayor_dashboard',
        'dashicons-building',
        3
    );
    
    add_submenu_page(
        'mayor-dashboard',
        'Écoles de ma commune',
        'Écoles',
        'maire_commune',
        'mayor-schools',
        'gcee_render_mayor_schools'
    );
    
    add_submenu_page(
        'mayor-dashboard',
        'Élèves de ma commune',
        'Élèves',
        'maire_commune',
        'mayor-students',
        'gcee_render_mayor_students'
    );
}
add_action('admin_menu', 'gcee_add_mayor_dashboard');

/**
 * Ajouter le champ commune associée aux utilisateurs maire
 */
function gcee_add_mayor_commune_field($user) {
    if (in_array('maire_commune', $user->roles)) {
        $associated_commune = get_user_meta($user->ID, '_associated_commune', true);
        ?>
        <h3>Commune associée</h3>
        <table class="form-table">
            <tr>
                <th><label for="associated_commune">Commune</label></th>
                <td>
                    <select name="associated_commune" id="associated_commune">
                        <option value="">-- Sélectionner une commune --</option>
                        <?php
                        $communes = get_terms(array(
                            'taxonomy' => 'commune',
                            'hide_empty' => false,
                        ));
                        foreach ($communes as $commune) {
                            echo '<option value="' . $commune->term_id . '"' . selected($associated_commune, $commune->term_id, false) . '>' . $commune->name . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }
}
add_action('show_user_profile', 'gcee_add_mayor_commune_field');
add_action('edit_user_profile', 'gcee_add_mayor_commune_field');

/**
 * Sauvegarder le champ commune associée
 */
function gcee_save_mayor_commune_field($user_id) {
    if (current_user_can('edit_user', $user_id) && isset($_POST['associated_commune'])) {
        update_user_meta($user_id, '_associated_commune', sanitize_text_field($_POST['associated_commune']));
    }
}
add_action('personal_options_update', 'gcee_save_mayor_commune_field');
add_action('edit_user_profile_update', 'gcee_save_mayor_commune_field');

/**
 * Rendu du tableau de bord principal du maire
 */
function gcee_render_mayor_dashboard() {
    $user = wp_get_current_user();
    $associated_commune_id = get_user_meta($user->ID, '_associated_commune', true);
    
    if (!$associated_commune_id) {
        echo '<div class="wrap"><h1>Tableau de bord</h1>';
        echo '<div class="notice notice-error"><p>Aucune commune n\'est associée à votre compte. Veuillez contacter l\'administrateur.</p></div>';
        echo '</div>';
        return;
    }
    
    $commune = get_term($associated_commune_id, 'commune');
    
    // Récupérer les statistiques
    $schools_query = new WP_Query(array(
        'post_type' => 'school',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'commune',
                'field' => 'term_id',
                'terms' => $associated_commune_id,
            ),
        ),
    ));
    
    $schools = $schools_query->posts;
    $total_students = 0;
    $total_boys = 0;
    $total_girls = 0;
    $validated_schools = 0;
    $pending_schools = 0;
    
    foreach ($schools as $school) {
        $validation_status = get_post_meta($school->ID, '_validation_status', true);
        if ($validation_status === 'validated') {
            $validated_schools++;
        } else {
            $pending_schools++;
        }
        
        $students = intval(get_post_meta($school->ID, '_nombre_eleves', true));
        $boys = intval(get_post_meta($school->ID, '_nombre_garcons', true));
        $girls = intval(get_post_meta($school->ID, '_nombre_filles', true));
        
        $total_students += $students;
        $total_boys += $boys;
        $total_girls += $girls;
    }
    
    ?>
    <div class="wrap">
        <h1>Tableau de bord - Commune de <?php echo esc_html($commune->name); ?></h1>
        
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
        
        <div class="mayor-dashboard-stats">
            <style>
                .mayor-dashboard-stats {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                    gap: 20px;
                    margin: 20px 0;
                }
                .stat-card {
                    background: #fff;
                    border: 1px solid #ccd0d4;
                    border-radius: 4px;
                    padding: 20px;
                    text-align: center;
                    box-shadow: 0 1px 1px rgba(0,0,0,.04);
                }
                .stat-number {
                    font-size: 2em;
                    font-weight: bold;
                    color: #0073aa;
                    display: block;
                }
                .stat-label {
                    color: #666;
                    margin-top: 5px;
                }
                .mayor-actions {
                    margin: 30px 0;
                }
                .mayor-actions .button {
                    margin-right: 10px;
                    margin-bottom: 10px;
                }
                /* Styles du popup */
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
            
            <div class="stat-card">
                <span class="stat-number"><?php echo count($schools); ?></span>
                <div class="stat-label">Écoles au total</div>
            </div>
            
            <div class="stat-card">
                <span class="stat-number"><?php echo $validated_schools; ?></span>
                <div class="stat-label">Écoles validées</div>
            </div>
            
            <div class="stat-card">
                <span class="stat-number"><?php echo $pending_schools; ?></span>
                <div class="stat-label">Écoles en attente</div>
            </div>
            
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_students; ?></span>
                <div class="stat-label">Élèves au total</div>
            </div>
            
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_boys; ?></span>
                <div class="stat-label">Garçons</div>
            </div>
            
            <div class="stat-card">
                <span class="stat-number"><?php echo $total_girls; ?></span>
                <div class="stat-label">Filles</div>
            </div>
        </div>
        
        <div class="mayor-actions">
            <h2>Actions rapides</h2>
            <a href="<?php echo admin_url('admin.php?page=mayor-schools'); ?>" class="button button-primary">
                Voir toutes les écoles
            </a>
            <a href="<?php echo admin_url('admin.php?page=mayor-students'); ?>" class="button button-primary">
                Voir tous les élèves
            </a>
        </div>
        
        <?php if ($pending_schools > 0): ?>
        <div class="notice notice-warning">
            <p><strong>Attention :</strong> <?php echo $pending_schools; ?> école(s) sont en attente de validation.</p>
        </div>
        <?php endif; ?>
        
        <h2>Écoles récentes</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>École</th>
                    <th>Directeur</th>
                    <th>Élèves</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($schools)): ?>
                <tr>
                    <td colspan="5">Aucune école trouvée dans votre commune.</td>
                </tr>
                <?php else: ?>
                    <?php foreach (array_slice($schools, 0, 5) as $school): ?>
                    <tr>
                        <td><strong><?php echo esc_html($school->post_title); ?></strong></td>
                        <td><?php echo esc_html(get_post_meta($school->ID, '_nom_directeur', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($school->ID, '_nombre_eleves', true)); ?></td>
                        <td>
                            <?php
                            $validation_status = get_post_meta($school->ID, '_validation_status', true);
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
                        </td>
                        <td>
                            <button type="button" class="button button-small gcee-open-validation-popup" data-school-id="<?php echo $school->ID; ?>">
                                Voir/Valider
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        
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
    </div>
    <?php
}

/**
 * Rendu de la page des écoles pour les maires
 */
function gcee_render_mayor_schools() {
    $user = wp_get_current_user();
    $associated_commune_id = get_user_meta($user->ID, '_associated_commune', true);
    
    if (!$associated_commune_id) {
        echo '<div class="wrap"><h1>Écoles de ma commune</h1>';
        echo '<div class="notice notice-error"><p>Aucune commune n\'est associée à votre compte.</p></div>';
        echo '</div>';
        return;
    }
    
    $commune = get_term($associated_commune_id, 'commune');
    
    $schools_query = new WP_Query(array(
        'post_type' => 'school',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'commune',
                'field' => 'term_id',
                'terms' => $associated_commune_id,
            ),
        ),
    ));
    
    ?>
    <div class="wrap">
        <h1>Écoles de la commune de <?php echo esc_html($commune->name); ?></h1>
        
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
            /* Styles du popup */
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
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>École</th>
                    <th>Localité</th>
                    <th>Type</th>
                    <th>Directeur</th>
                    <th>Élèves</th>
                    <th>Garçons</th>
                    <th>Filles</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$schools_query->have_posts()): ?>
                <tr>
                    <td colspan="9">Aucune école trouvée dans votre commune.</td>
                </tr>
                <?php else: ?>
                    <?php while ($schools_query->have_posts()): $schools_query->the_post(); ?>
                    <tr>
                        <td><strong><?php the_title(); ?></strong></td>
                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_nom_localite', true)); ?></td>
                        <td><?php echo esc_html(ucfirst(get_post_meta(get_the_ID(), '_type_ecole', true))); ?></td>
                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_nom_directeur', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_nombre_eleves', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_nombre_garcons', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_nombre_filles', true)); ?></td>
                        <td>
                            <?php
                            $validation_status = get_post_meta(get_the_ID(), '_validation_status', true);
                            switch ($validation_status) {
                                case 'validated':
                                    echo '<span style="color: green; font-weight: bold;">✓ Validée</span>';
                                    break;
                                case 'rejected':
                                    echo '<span style="color: red; font-weight: bold;">✗ Rejetée</span>';
                                    break;
                                default:
                                    echo '<span style="color: orange; font-weight: bold;">⏳ En attente</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small gcee-open-validation-popup" data-school-id="<?php echo get_the_ID(); ?>">
                                Voir/Valider
                            </button>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php wp_reset_postdata(); ?>
        
        <?php if ($schools_query->have_posts()): ?>
        <p style="margin-top: 20px;"><strong>Total : <?php echo $schools_query->found_posts; ?> école(s) dans votre commune</strong></p>
        <?php endif; ?>
        
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

            // Empêcher la fermeture du popup en cliquant sur le contenu
            $(document).on('click', '.gcee-popup-content', function(e) {
                e.stopPropagation();
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
                $('#gcee-school-details').html('<p>Chargement des détails...</p>');
                
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
                            $('#gcee-school-details').html('<p style="color: red;">Erreur : ' + response.data + '</p>');
                        }
                    },
                    error: function() {
                        $('#gcee-school-details').html('<p style="color: red;">Erreur de connexion.</p>');
                    }
                });
            }

            // Valider/Rejeter l'école
            function validateSchool(schoolId, status) {
                const action = status === 'validated' ? 'valider' : 'rejeter';
                if (!confirm('Êtes-vous sûr de vouloir ' + action + ' cette école ?')) {
                    return;
                }

                // Désactiver les boutons pendant la requête
                $('#gcee-validate-school, #gcee-reject-school').prop('disabled', true);

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
                            resultDiv.html('✓ École ' + (status === 'validated' ? 'validée' : 'rejetée') + ' avec succès !');
                            
                            // Recharger la page après 2 secondes
                            setTimeout(function() {
                                location.reload();
                            }, 2000);
                        } else {
                            resultDiv.removeClass('gcee-result-success').addClass('gcee-result-error');
                            resultDiv.html('✗ Erreur : ' + response.data);
                            // Réactiver les boutons en cas d'erreur
                            $('#gcee-validate-school, #gcee-reject-school').prop('disabled', false);
                        }
                        resultDiv.show();
                    },
                    error: function() {
                        const resultDiv = $('#gcee-validation-result');
                        resultDiv.removeClass('gcee-result-success').addClass('gcee-result-error');
                        resultDiv.html('✗ Erreur de connexion.');
                        resultDiv.show();
                        // Réactiver les boutons en cas d'erreur
                        $('#gcee-validate-school, #gcee-reject-school').prop('disabled', false);
                    }
                });
            }
        });
        </script>
    </div>
    <?php
}

/**
 * Rendu de la page des élèves pour les maires
 */
function gcee_render_mayor_students() {
    $user = wp_get_current_user();
    $associated_commune_id = get_user_meta($user->ID, '_associated_commune', true);
    
    if (!$associated_commune_id) {
        echo '<div class="wrap"><h1>Élèves de ma commune</h1>';
        echo '<div class="notice notice-error"><p>Aucune commune n\'est associée à votre compte.</p></div>';
        echo '</div>';
        return;
    }
    
    $commune = get_term($associated_commune_id, 'commune');
    
    // Récupérer les écoles de la commune
    $schools_query = new WP_Query(array(
        'post_type' => 'school',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'tax_query' => array(
            array(
                'taxonomy' => 'commune',
                'field' => 'term_id',
                'terms' => $associated_commune_id,
            ),
        ),
    ));
    
    $school_ids = $schools_query->posts;
    
    if (empty($school_ids)) {
        echo '<div class="wrap"><h1>Élèves de la commune de ' . esc_html($commune->name) . '</h1>';
        echo '<p>Aucune école trouvée dans votre commune, donc aucun élève à afficher.</p>';
        echo '</div>';
        return;
    }
    
    // Récupérer les élèves des écoles de la commune
    $students_query = new WP_Query(array(
        'post_type' => 'student',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_associated_school',
                'value' => $school_ids,
                'compare' => 'IN'
            )
        )
    ));
    
    ?>
    <div class="wrap">
        <h1>Élèves de la commune de <?php echo esc_html($commune->name); ?></h1>
        
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Prénom et nom</th>
                    <th>Genre</th>
                    <th>Date de naissance</th>
                    <th>École</th>
                    <th>Classe</th>
                    <th>Distance parcourue</th>
                    <th>Pointure</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$students_query->have_posts()): ?>
                <tr>
                    <td colspan="7">Aucun élève trouvé dans les écoles de votre commune.</td>
                </tr>
                <?php else: ?>
                    <?php while ($students_query->have_posts()): $students_query->the_post(); ?>
                    <?php
                    $associated_school_id = get_post_meta(get_the_ID(), '_associated_school', true);
                    $school_name = $associated_school_id ? get_the_title($associated_school_id) : 'Non assigné';
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html(get_post_meta(get_the_ID(), '_prenom_nom', true)); ?></strong></td>
                        <td><?php echo esc_html(ucfirst(get_post_meta(get_the_ID(), '_genre', true))); ?></td>
                        <td><?php 
                        $date_naissance = get_post_meta(get_the_ID(), '_date_naissance', true);
                        echo $date_naissance ? date('d/m/Y', strtotime($date_naissance)) : 'Non renseigné';
                        ?></td>
                        <td><?php echo esc_html($school_name); ?></td>
                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_classe_frequentee', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_distance_parcourue', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta(get_the_ID(), '_pointure', true)); ?></td>
                    </tr>
                    <?php endwhile; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php wp_reset_postdata(); ?>
        
        <?php if ($students_query->have_posts()): ?>
        <p><strong>Total : <?php echo $students_query->found_posts; ?> élève(s)</strong></p>
        <?php endif; ?>
    </div>
    <?php
}