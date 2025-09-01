<?php
/**
 * Fichier : includes/shortcodes.php
 * Shortcodes pour afficher les écoles et élèves côté front-end
 */

// Sécurité : Empêcher un accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode pour afficher les élèves d'une école spécifique
 */
function gcee_students_by_school_shortcode($atts) {
    $atts = shortcode_atts(array(
        'school_id' => '',
        'show_school_name' => 'yes'
    ), $atts);
    
    if (!$atts['school_id']) {
        return '<p>Veuillez spécifier un ID d\'école.</p>';
    }
    
    $school = get_post($atts['school_id']);
    if (!$school || $school->post_type !== 'school') {
        return '<p>École non trouvée.</p>';
    }
    
    $args = array(
        'post_type' => 'student',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => '_associated_school',
                'value' => $atts['school_id'],
            ),
        ),
    );
    
    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        return '<p>Aucun élève trouvé pour cette école.</p>';
    }
    
    $output = '<style>
                .gcee-students-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                    font-size: 16px;
                    text-align: left;
                }
                .gcee-students-table th,
                .gcee-students-table td {
                    padding: 12px;
                    border: 1px solid #ddd;
                }
                .gcee-students-table th {
                    background-color: #f4f4f4;
                    font-weight: bold;
                }
                .gcee-students-table tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                @media (max-width: 768px) {
                    .gcee-students-table {
                        font-size: 14px;
                    }
                    .gcee-students-table th,
                    .gcee-students-table td {
                        padding: 8px;
                    }
                }
              </style>';
    
    if ($atts['show_school_name'] === 'yes') {
        $output .= '<h3>Élèves de l\'école ' . esc_html($school->post_title) . '</h3>';
    }
    
    $output .= '<table class="gcee-students-table">
                <thead>
                    <tr>
                        <th>Prénom et nom</th>
                        <th>Genre</th>
                        <th>Date de naissance</th>
                        <th>Distance parcourue</th>
                        <th>Pointure</th>
                        <th>Classe</th>
                    </tr>
                </thead>
                <tbody>';
    
    while ($query->have_posts()) {
        $query->the_post();
        $student_id = get_the_ID();
        
        $prenom_nom = get_post_meta($student_id, '_prenom_nom', true) ?: 'Non spécifié';
        $genre = get_post_meta($student_id, '_genre', true) ?: 'Non spécifié';
        $date_naissance = get_post_meta($student_id, '_date_naissance', true);
        $distance = get_post_meta($student_id, '_distance_parcourue', true) ?: 'Non spécifié';
        $pointure = get_post_meta($student_id, '_pointure', true) ?: 'Non spécifié';
        $classe = get_post_meta($student_id, '_classe_frequentee', true) ?: 'Non spécifié';
        
        // Formater la date de naissance
        $date_formatted = $date_naissance ? date('d/m/Y', strtotime($date_naissance)) : 'Non spécifié';
        
        $output .= '<tr>
                        <td>' . esc_html($prenom_nom) . '</td>
                        <td>' . esc_html(ucfirst($genre)) . '</td>
                        <td>' . esc_html($date_formatted) . '</td>
                        <td>' . esc_html($distance) . '</td>
                        <td>' . esc_html($pointure) . '</td>
                        <td>' . esc_html($classe) . '</td>
                    </tr>';
    }
    
    wp_reset_postdata();
    $output .= '</tbody></table>';
    $output .= '<p><strong>Total : ' . $query->found_posts . ' élève(s)</strong></p>';
    
    return $output;
}
add_shortcode('eleves_par_ecole', 'gcee_students_by_school_shortcode');

/**
 * Shortcode pour afficher les élèves de l'école actuelle
 */
function gcee_students_current_school_shortcode($atts) {
    if (is_singular('school')) {
        $school_id = get_the_ID();
        $atts = shortcode_atts(array('show_school_name' => 'no'), $atts);
        $atts['school_id'] = $school_id;
        return gcee_students_by_school_shortcode($atts);
    }
    return '<p>Ce shortcode doit être utilisé sur une page d\'école.</p>';
}
add_shortcode('eleves_de_l_ecole', 'gcee_students_current_school_shortcode');

/**
 * Shortcode pour afficher les écoles d'une commune spécifique
 */
function gcee_schools_by_commune_shortcode($atts) {
    $atts = shortcode_atts(array(
        'commune' => '',
        'commune_id' => '',
        'show_students' => 'no',
        'show_validation' => 'no'
    ), $atts);
    
    if (!$atts['commune'] && !$atts['commune_id']) {
        return '<p>Veuillez spécifier une commune ou un ID de commune.</p>';
    }
    
    // Déterminer la commune à afficher
    $commune_term = null;
    if ($atts['commune_id']) {
        $commune_term = get_term($atts['commune_id'], 'commune');
    } elseif ($atts['commune']) {
        $commune_term = get_term_by('name', $atts['commune'], 'commune');
        if (!$commune_term) {
            $commune_term = get_term_by('slug', $atts['commune'], 'commune');
        }
    }
    
    if (!$commune_term || is_wp_error($commune_term)) {
        return '<p>Commune non trouvée.</p>';
    }
    
    $args = array(
        'post_type' => 'school',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'commune',
                'field' => 'term_id',
                'terms' => $commune_term->term_id,
            ),
        ),
    );
    
    $query = new WP_Query($args);
    
    if (!$query->have_posts()) {
        return '<p>Aucune école trouvée dans cette commune.</p>';
    }
    
    $output = '<style>
                .gcee-schools-table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 20px 0;
                    font-size: 16px;
                    text-align: left;
                }
                .gcee-schools-table th,
                .gcee-schools-table td {
                    padding: 12px;
                    border: 1px solid #ddd;
                }
                .gcee-schools-table th {
                    background-color: #f4f4f4;
                    font-weight: bold;
                }
                .gcee-schools-table tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                .validation-status {
                    padding: 4px 8px;
                    border-radius: 3px;
                    font-size: 12px;
                    font-weight: bold;
                }
                .validation-validated {
                    background-color: #d4edda;
                    color: #155724;
                }
                .validation-pending {
                    background-color: #fff3cd;
                    color: #856404;
                }
                .validation-rejected {
                    background-color: #f8d7da;
                    color: #721c24;
                }
                @media (max-width: 768px) {
                    .gcee-schools-table {
                        font-size: 14px;
                    }
                    .gcee-schools-table th,
                    .gcee-schools-table td {
                        padding: 8px;
                    }
                }
              </style>';
    
    $output .= '<h3>Écoles de la commune de ' . esc_html($commune_term->name) . '</h3>';
    
    // Construire l'en-tête du tableau
    $headers = '<th>École</th><th>Localité</th><th>Type</th><th>Directeur</th>';
    if ($atts['show_students'] === 'yes') {
        $headers .= '<th>Élèves</th><th>Garçons</th><th>Filles</th>';
    }
    if ($atts['show_validation'] === 'yes') {
        $headers .= '<th>Statut</th>';
    }
    $headers .= '<th>Lien</th>';
    
    $output .= '<table class="gcee-schools-table">
                <thead>
                    <tr>' . $headers . '</tr>
                </thead>
                <tbody>';
    
    while ($query->have_posts()) {
        $query->the_post();
        $school_id = get_the_ID();
        
        $localite = get_post_meta($school_id, '_nom_localite', true) ?: 'Non spécifié';
        $type = get_post_meta($school_id, '_type_ecole', true) ?: 'Non spécifié';
        $directeur = get_post_meta($school_id, '_nom_directeur', true) ?: 'Non spécifié';
        
        $output .= '<tr>
                        <td><strong>' . get_the_title() . '</strong></td>
                        <td>' . esc_html($localite) . '</td>
                        <td>' . esc_html(ucfirst($type)) . '</td>
                        <td>' . esc_html($directeur) . '</td>';
        
        if ($atts['show_students'] === 'yes') {
            $eleves = get_post_meta($school_id, '_nombre_eleves', true) ?: '0';
            $garcons = get_post_meta($school_id, '_nombre_garcons', true) ?: '0';
            $filles = get_post_meta($school_id, '_nombre_filles', true) ?: '0';
            
            $output .= '<td>' . esc_html($eleves) . '</td>
                        <td>' . esc_html($garcons) . '</td>
                        <td>' . esc_html($filles) . '</td>';
        }
        
        if ($atts['show_validation'] === 'yes') {
            $validation_status = get_post_meta($school_id, '_validation_status', true);
            $status_class = '';
            $status_text = '';
            
            switch ($validation_status) {
                case 'validated':
                    $status_class = 'validation-validated';
                    $status_text = 'Validée';
                    break;
                case 'rejected':
                    $status_class = 'validation-rejected';
                    $status_text = 'Rejetée';
                    break;
                default:
                    $status_class = 'validation-pending';
                    $status_text = 'En attente';
            }
            
            $output .= '<td><span class="validation-status ' . $status_class . '">' . $status_text . '</span></td>';
        }
        
        $output .= '<td><a href="' . get_permalink() . '">Voir détails</a></td>
                    </tr>';
    }
    
    wp_reset_postdata();
    $output .= '</tbody></table>';
    $output .= '<p><strong>Total : ' . $query->found_posts . ' école(s)</strong></p>';
    
    return $output;
}
add_shortcode('ecoles_par_commune', 'gcee_schools_by_commune_shortcode');

/**
 * Shortcode pour afficher les écoles de la commune actuelle
 */
function gcee_schools_current_commune_shortcode($atts) {
    if (is_tax('commune')) {
        $commune_term = get_queried_object();
        $atts = shortcode_atts(array(
            'show_students' => 'no',
            'show_validation' => 'no'
        ), $atts);
        $atts['commune_id'] = $commune_term->term_id;
        return gcee_schools_by_commune_shortcode($atts);
    }
    return '<p>Ce shortcode doit être utilisé sur une page de commune.</p>';
}
add_shortcode('ecoles_de_la_commune', 'gcee_schools_current_commune_shortcode');

/**
 * Shortcode pour afficher le rôle de l'utilisateur connecté
 */
function gcee_user_role_shortcode() {
    if (is_user_logged_in()) {
        $user = wp_get_current_user();
        $roles = $user->roles;
        
        // Traduire les rôles en français
        $role_translations = array(
            'administrator' => 'Administrateur',
            'directeur_ecole' => 'Directeur d\'école',
            'maire_commune' => 'Maire de commune',
            'editor' => 'Éditeur',
            'author' => 'Auteur',
            'contributor' => 'Contributeur',
            'subscriber' => 'Abonné'
        );
        
        $translated_roles = array();
        foreach ($roles as $role) {
            $translated_roles[] = isset($role_translations[$role]) ? $role_translations[$role] : ucfirst($role);
        }
        
        return 'Votre rôle : ' . implode(', ', $translated_roles);
    }
    
    return 'Vous devez être connecté pour voir votre rôle.';
}
add_shortcode('user_role', 'gcee_user_role_shortcode');

/**
 * Shortcode pour afficher les statistiques d'une école
 */
function gcee_school_stats_shortcode($atts) {
    $atts = shortcode_atts(array(
        'school_id' => ''
    ), $atts);
    
    // Si pas d'ID spécifié, utiliser l'école actuelle
    if (!$atts['school_id']) {
        if (is_singular('school')) {
            $atts['school_id'] = get_the_ID();
        } else {
            return '<p>Veuillez spécifier un ID d\'école ou utiliser ce shortcode sur une page d\'école.</p>';
        }
    }
    
    $school = get_post($atts['school_id']);
    if (!$school || $school->post_type !== 'school') {
        return '<p>École non trouvée.</p>';
    }
    
    // Récupérer les statistiques
    $total_eleves = get_post_meta($atts['school_id'], '_nombre_eleves', true) ?: '0';
    $garcons = get_post_meta($atts['school_id'], '_nombre_garcons', true) ?: '0';
    $filles = get_post_meta($atts['school_id'], '_nombre_filles', true) ?: '0';
    $salles = get_post_meta($atts['school_id'], '_nombre_salles', true) ?: '0';
    $enseignants = get_post_meta($atts['school_id'], '_nombre_enseignants', true) ?: '0';
    $electricite = get_post_meta($atts['school_id'], '_electricite', true) ?: 'Non spécifié';
    $cloture = get_post_meta($atts['school_id'], '_cloture', true) ?: 'Non spécifié';
    
    $output = '<div class="gcee-school-stats">
                <style>
                    .gcee-school-stats {
                        background: #f9f9f9;
                        border: 1px solid #ddd;
                        border-radius: 5px;
                        padding: 20px;
                        margin: 20px 0;
                    }
                    .gcee-stats-grid {
                        display: grid;
                        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                        gap: 15px;
                        margin-top: 15px;
                    }
                    .gcee-stat-item {
                        text-align: center;
                        padding: 15px;
                        background: white;
                        border-radius: 3px;
                        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    }
                    .gcee-stat-number {
                        font-size: 2em;
                        font-weight: bold;
                        color: #0073aa;
                        display: block;
                    }
                    .gcee-stat-label {
                        color: #666;
                        margin-top: 5px;
                        font-size: 0.9em;
                    }
                </style>
                <h3>Statistiques de l\'école</h3>
                <div class="gcee-stats-grid">
                    <div class="gcee-stat-item">
                        <span class="gcee-stat-number">' . esc_html($total_eleves) . '</span>
                        <div class="gcee-stat-label">Élèves au total</div>
                    </div>
                    <div class="gcee-stat-item">
                        <span class="gcee-stat-number">' . esc_html($garcons) . '</span>
                        <div class="gcee-stat-label">Garçons</div>
                    </div>
                    <div class="gcee-stat-item">
                        <span class="gcee-stat-number">' . esc_html($filles) . '</span>
                        <div class="gcee-stat-label">Filles</div>
                    </div>
                    <div class="gcee-stat-item">
                        <span class="gcee-stat-number">' . esc_html($salles) . '</span>
                        <div class="gcee-stat-label">Salles de classe</div>
                    </div>
                    <div class="gcee-stat-item">
                        <span class="gcee-stat-number">' . esc_html($enseignants) . '</span>
                        <div class="gcee-stat-label">Enseignants</div>
                    </div>
                    <div class="gcee-stat-item">
                        <span class="gcee-stat-number">' . (($electricite === 'oui') ? '✓' : '✗') . '</span>
                        <div class="gcee-stat-label">Électricité</div>
                    </div>
                    <div class="gcee-stat-item">
                        <span class="gcee-stat-number">' . (($cloture === 'oui') ? '✓' : '✗') . '</span>
                        <div class="gcee-stat-label">Clôturée</div>
                    </div>
                </div>
            </div>';
    
    return $output;
}
add_shortcode('stats_ecole', 'gcee_school_stats_shortcode');

/**
 * Shortcode pour afficher un formulaire de recherche d'écoles
 */
function gcee_school_search_shortcode($atts) {
    $atts = shortcode_atts(array(
        'show_commune_filter' => 'yes',
        'show_type_filter' => 'yes',
        'results_per_page' => '5'
    ), $atts);
    
    // Traitement de la recherche
    $search_query = isset($_GET['school_search']) ? sanitize_text_field($_GET['school_search']) : '';
    $commune_filter = isset($_GET['commune_filter']) ? sanitize_text_field($_GET['commune_filter']) : '';
    $type_filter = isset($_GET['type_filter']) ? sanitize_text_field($_GET['type_filter']) : '';
    
    $output = '<div class="gcee-school-search">
                <style>
                    .gcee-school-search {
                        margin: 20px 0;
                    }
                    .gcee-search-form {
                        background: #f9f9f9;
                        padding: 20px;
                        border-radius: 5px;
                        margin-bottom: 20px;
                    }
                    .gcee-search-row {
                        display: flex;
                        gap: 15px;
                        align-items: end;
                        flex-wrap: wrap;
                    }
                    .gcee-search-field {
                        flex: 1;
                        min-width: 200px;
                    }
                    .gcee-search-field label {
                        display: block;
                        margin-bottom: 5px;
                        font-weight: bold;
                    }
                    .gcee-search-field input,
                    .gcee-search-field select {
                        width: 100%;
                        padding: 8px;
                        border: 1px solid #ddd;
                        border-radius: 3px;
                    }
                    .gcee-search-button {
                        padding: 8px 20px;
                        background: #0073aa;
                        color: white;
                        border: none;
                        border-radius: 3px;
                        cursor: pointer;
                    }
                    .gcee-search-results {
                        margin-top: 20px;
                    }
                </style>
                
                <form class="gcee-search-form" method="get">
                    <div class="gcee-search-row">
                        <div class="gcee-search-field">
                            <label for="school_search">Rechercher une école</label>
                            <input type="text" id="school_search" name="school_search" value="' . esc_attr($search_query) . '" placeholder="Nom de l\'école...">
                        </div>';
    
    if ($atts['show_commune_filter'] === 'yes') {
        $communes = get_terms(array(
            'taxonomy' => 'commune',
            'hide_empty' => false,
        ));
        
        $output .= '<div class="gcee-search-field">
                        <label for="commune_filter">Commune</label>
                        <select id="commune_filter" name="commune_filter">
                            <option value="">Toutes les communes</option>';
        
        foreach ($communes as $commune) {
            $output .= '<option value="' . $commune->term_id . '"' . selected($commune_filter, $commune->term_id, false) . '>' . esc_html($commune->name) . '</option>';
        }
        
        $output .= '</select>
                    </div>';
    }
    
    if ($atts['show_type_filter'] === 'yes') {
        $output .= '<div class="gcee-search-field">
                        <label for="type_filter">Type d\'école</label>
                        <select id="type_filter" name="type_filter">
                            <option value="">Tous les types</option>
                            <option value="publique"' . selected($type_filter, 'publique', false) . '>Publique</option>
                            <option value="privee"' . selected($type_filter, 'privee', false) . '>Privée</option>
                            <option value="communautaire"' . selected($type_filter, 'communautaire', false) . '>Communautaire</option>
                        </select>
                    </div>';
    }
    
    $output .= '        <div>
                            <button type="submit" class="gcee-search-button">Rechercher</button>
                        </div>
                    </div>
                </form>';
    
    // Si une recherche a été effectuée, afficher les résultats
    if ($search_query || $commune_filter || $type_filter) {
        $query_args = array(
            'post_type' => 'school',
            'posts_per_page' => intval($atts['results_per_page']),
            'paged' => get_query_var('paged') ? get_query_var('paged') : 1,
        );
        
        // Recherche par nom
        if ($search_query) {
            $query_args['s'] = $search_query;
        }
        
        // Filtre par commune
        if ($commune_filter) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'commune',
                    'field' => 'term_id',
                    'terms' => $commune_filter,
                ),
            );
        }
        
        // Filtre par type
        if ($type_filter) {
            $query_args['meta_query'] = array(
                array(
                    'key' => '_type_ecole',
                    'value' => $type_filter,
                    'compare' => '='
                )
            );
        }
        
        $search_results = new WP_Query($query_args);
        
        $output .= '<div class="gcee-search-results">';
        
        if ($search_results->have_posts()) {
            $output .= '<h3>Résultats de la recherche (' . $search_results->found_posts . ' école(s) trouvée(s))</h3>';
            $output .= '<div class="gcee-results-list">';
            
            while ($search_results->have_posts()) {
                $search_results->the_post();
                $school_id = get_the_ID();
                
                $communes = wp_get_post_terms($school_id, 'commune');
                $commune_names = array();
                foreach ($communes as $commune) {
                    $commune_names[] = $commune->name;
                }
                
                $type = get_post_meta($school_id, '_type_ecole', true);
                $directeur = get_post_meta($school_id, '_nom_directeur', true);
                $eleves = get_post_meta($school_id, '_nombre_eleves', true);
                
                $output .= '<div style="border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 5px;">
                                <h4><a href="' . get_permalink() . '">' . get_the_title() . '</a></h4>
                                <p><strong>Commune(s) :</strong> ' . esc_html(implode(', ', $commune_names)) . '</p>
                                <p><strong>Type :</strong> ' . esc_html(ucfirst($type)) . '</p>
                                <p><strong>Directeur :</strong> ' . esc_html($directeur) . '</p>
                                <p><strong>Nombre d\'élèves :</strong> ' . esc_html($eleves) . '</p>
                                <a href="' . get_permalink() . '" class="button">Voir détails</a>
                            </div>';
            }
            
            $output .= '</div>';
            
            // Pagination simple
            if ($search_results->max_num_pages > 1) {
                $output .= '<div class="gcee-pagination" style="text-align: center; margin-top: 20px;">';
                $output .= paginate_links(array(
                    'base' => add_query_arg('paged', '%#%'),
                    'format' => '',
                    'current' => $query_args['paged'],
                    'total' => $search_results->max_num_pages,
                    'prev_text' => '« Précédent',
                    'next_text' => 'Suivant »',
                ));
                $output .= '</div>';
            }
            
        } else {
            $output .= '<p>Aucune école trouvée pour votre recherche.</p>';
        }
        
        wp_reset_postdata();
        $output .= '</div>';
    }
    
    $output .= '</div>';
    
    return $output;
}
add_shortcode('recherche_ecoles', 'gcee_school_search_shortcode');