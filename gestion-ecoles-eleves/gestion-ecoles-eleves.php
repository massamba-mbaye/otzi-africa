<?php
/*
Plugin Name: Gestion Complète Écoles et Élèves
Plugin URI: https://im-mass.com/
Description: Plugin complet pour gérer les écoles, les élèves, les communes et les rôles (Directeur d'école, Maire de commune).
Version: 2.0
Author: Massamba MBAYE
Author URI: https://www.linkedin.com/in/massamba-mbaye/
License: GPL-2.0+
License URI: http://www.gnu.org/licenses/gpl-2.0.txt
*/

// Sécurité : Empêcher un accès direct
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Activation du plugin - Créer les rôles et tables nécessaires
 */
function gcee_activation() {
    // Créer les rôles
    gcee_create_roles();
    
    // Enregistrer les CPT et taxonomies
    gcee_register_cpts();
    gcee_register_taxonomies();
    
    // Vider les règles de réécriture
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'gcee_activation');

/**
 * Désactivation du plugin
 */
function gcee_deactivation() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'gcee_deactivation');

/**
 * Création des rôles personnalisés
 */
function gcee_create_roles() {
    // Rôle Directeur d'école
    add_role('directeur_ecole', 'Directeur d\'école', array(
        'read' => true,
        'upload_files' => true,
        'edit_posts' => true,
        'edit_published_posts' => true,
        'publish_posts' => true,
        'delete_posts' => true,
        'delete_published_posts' => true,
    ));

    // Rôle Maire de commune
    add_role('maire_commune', 'Maire de commune', array(
        'read' => true,
    ));
}

/**
 * Enregistrement des CPT Écoles et Élèves
 */
function gcee_register_cpts() {
    // CPT Écoles
    register_post_type('school', array(
        'labels' => array(
            'name' => 'Écoles',
            'singular_name' => 'École',
            'add_new' => 'Ajouter une école',
            'add_new_item' => 'Ajouter une nouvelle école',
            'edit_item' => 'Modifier l\'école',
            'new_item' => 'Nouvelle école',
            'view_item' => 'Voir l\'école',
            'search_items' => 'Rechercher des écoles',
            'not_found' => 'Aucune école trouvée',
            'not_found_in_trash' => 'Aucune école trouvée dans la corbeille',
        ),
        'public' => true,
        'menu_icon' => 'dashicons-building',
        'supports' => array('title'),
        'has_archive' => true,
        'rewrite' => array('slug' => 'ecoles'),
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ));

    // CPT Élèves
    register_post_type('student', array(
        'labels' => array(
            'name' => 'Élèves',
            'singular_name' => 'Élève',
            'add_new' => 'Ajouter un élève',
            'add_new_item' => 'Ajouter un nouvel élève',
            'edit_item' => 'Modifier l\'élève',
            'new_item' => 'Nouvel élève',
            'view_item' => 'Voir l\'élève',
            'search_items' => 'Rechercher des élèves',
            'not_found' => 'Aucun élève trouvé',
            'not_found_in_trash' => 'Aucun élève trouvé dans la corbeille',
        ),
        'public' => true,
        'menu_icon' => 'dashicons-id',
        'supports' => array('title'),
        'has_archive' => true,
        'rewrite' => array('slug' => 'eleves'),
        'capability_type' => 'post',
        'map_meta_cap' => true,
    ));
}
add_action('init', 'gcee_register_cpts');

/**
 * Enregistrement de la taxonomie Commune
 */
function gcee_register_taxonomies() {
    register_taxonomy('commune', 'school', array(
        'labels' => array(
            'name' => 'Communes',
            'singular_name' => 'Commune',
            'add_new_item' => 'Ajouter une nouvelle commune',
            'edit_item' => 'Modifier la commune',
            'view_item' => 'Voir la commune',
            'search_items' => 'Rechercher des communes',
            'not_found' => 'Aucune commune trouvée',
        ),
        'hierarchical' => true,
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'communes'),
    ));
}
add_action('init', 'gcee_register_taxonomies');

/**
 * Ajouter les meta boxes pour les écoles
 */
function gcee_add_school_meta_boxes() {
    add_meta_box(
        'school_information',
        'Informations de l\'école',
        'gcee_school_information_meta_box',
        'school',
        'normal',
        'high'
    );
}
add_action('add_meta_boxes', 'gcee_add_school_meta_boxes');

/**
 * Affichage de la meta box pour les informations de l'école
 */
function gcee_school_information_meta_box($post) {
    wp_nonce_field('gcee_school_meta_box_nonce', 'gcee_school_meta_box_nonce');
    
    // Récupérer les valeurs existantes
    $nom_localite = get_post_meta($post->ID, '_nom_localite', true);
    $type_ecole = get_post_meta($post->ID, '_type_ecole', true);
    $nom_directeur = get_post_meta($post->ID, '_nom_directeur', true);
    $tel_directeur = get_post_meta($post->ID, '_tel_directeur', true);
    $email_directeur = get_post_meta($post->ID, '_email_directeur', true);
    $nombre_eleves = get_post_meta($post->ID, '_nombre_eleves', true);
    $nombre_garcons = get_post_meta($post->ID, '_nombre_garcons', true);
    $nombre_filles = get_post_meta($post->ID, '_nombre_filles', true);
    $nombre_salles = get_post_meta($post->ID, '_nombre_salles', true);
    $nombre_enseignants = get_post_meta($post->ID, '_nombre_enseignants', true);
    $nombre_wc_garcons = get_post_meta($post->ID, '_nombre_wc_garcons', true);
    $nombre_wc_filles = get_post_meta($post->ID, '_nombre_wc_filles', true);
    $nombre_wc_mixtes = get_post_meta($post->ID, '_nombre_wc_mixtes', true);
    $nombre_points_eau = get_post_meta($post->ID, '_nombre_points_eau', true);
    $electricite = get_post_meta($post->ID, '_electricite', true);
    $cloture = get_post_meta($post->ID, '_cloture', true);
    
    ?>
    <style>
        .gcee-school-form {
            max-width: 100%;
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        .gcee-form-section {
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .gcee-form-section:last-child {
            border-bottom: none;
        }
        .gcee-form-section h3 {
            margin: 0 0 15px 0;
            color: #23282d;
            font-size: 16px;
            font-weight: 600;
        }
        .gcee-form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 15px;
        }
        .gcee-form-row.two-columns {
            grid-template-columns: 1fr 1fr;
        }
        .gcee-form-row.three-columns {
            grid-template-columns: 1fr 1fr 1fr;
        }
        .gcee-form-field {
            display: flex;
            flex-direction: column;
        }
        .gcee-form-field label {
            margin-bottom: 5px;
            font-weight: 600;
            color: #23282d;
            font-size: 14px;
        }
        .gcee-form-field label.required:after {
            content: ' *';
            color: #d63638;
        }
        .gcee-form-field input[type="text"],
        .gcee-form-field input[type="email"],
        .gcee-form-field input[type="tel"],
        .gcee-form-field input[type="number"],
        .gcee-form-field select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            line-height: 1.5;
            background-color: #fff;
            transition: border-color 0.2s;
        }
        .gcee-form-field input:focus,
        .gcee-form-field select:focus {
            outline: none;
            border-color: #007cba;
            box-shadow: 0 0 0 1px #007cba;
        }
        .gcee-form-field input[type="number"] {
            max-width: 400px;
        }
        .gcee-error-message {
            color: #d63638;
            font-weight: 600;
            margin-top: 15px;
            padding: 12px;
            background-color: #fcf0f1;
            border: 1px solid #d63638;
            border-radius: 4px;
            display: none;
        }
        .gcee-statistics-validation {
            background: #fff8e5;
            border: 1px solid #ffb900;
            border-radius: 4px;
            padding: 15px;
            margin-top: 10px;
        }
        @media (max-width: 768px) {
            .gcee-form-row,
            .gcee-form-row.two-columns,
            .gcee-form-row.three-columns {
                grid-template-columns: 1fr;
            }
            .gcee-form-field input[type="number"] {
                max-width: 100%;
            }
        }
    </style>
    
    <div class="gcee-school-form">
        <!-- Section Informations générales -->
        <div class="gcee-form-section">
            <h3>Informations générales de l'école</h3>
            <div class="gcee-form-row two-columns">
                <div class="gcee-form-field">
                    <label for="nom_localite">Nom de la localité</label>
                    <input type="text" id="nom_localite" name="nom_localite" value="<?php echo esc_attr($nom_localite); ?>" placeholder="Ex: Kaolack Centre">
                </div>
                <div class="gcee-form-field">
                    <label for="type_ecole" class="required">Type d'école</label>
                    <select id="type_ecole" name="type_ecole" required>
                        <option value="">-- Sélectionner le type --</option>
                        <option value="publique" <?php selected($type_ecole, 'publique'); ?>>École Publique</option>
                        <option value="privee" <?php selected($type_ecole, 'privee'); ?>>École Privée</option>
                        <option value="communautaire" <?php selected($type_ecole, 'communautaire'); ?>>École Communautaire</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Section Directeur -->
        <div class="gcee-form-section">
            <h3>Informations du Directeur</h3>
            <div class="gcee-form-row">
                <div class="gcee-form-field">
                    <label for="nom_directeur" class="required">Nom complet du Directeur</label>
                    <input type="text" id="nom_directeur" name="nom_directeur" value="<?php echo esc_attr($nom_directeur); ?>" required placeholder="Ex: Moussa Diop">
                </div>
            </div>
            <div class="gcee-form-row two-columns">
                <div class="gcee-form-field">
                    <label for="tel_directeur" class="required">Numéro de téléphone</label>
                    <input type="tel" id="tel_directeur" name="tel_directeur" value="<?php echo esc_attr($tel_directeur); ?>" required placeholder="Ex: +221 77 123 45 67">
                </div>
                <div class="gcee-form-field">
                    <label for="email_directeur" class="required">Adresse e-mail</label>
                    <input type="email" id="email_directeur" name="email_directeur" value="<?php echo esc_attr($email_directeur); ?>" required placeholder="Ex: directeur@ecole.sn">
                </div>
            </div>
        </div>

        <!-- Section Statistiques des élèves -->
        <div class="gcee-form-section">
            <h3>Statistiques des élèves</h3>
            <div class="gcee-statistics-validation">
                <p><strong>Important :</strong> La somme du nombre de garçons et de filles doit être égale au nombre total d'élèves.</p>
            </div>
            <div class="gcee-form-row three-columns">
                <div class="gcee-form-field">
                    <label for="nombre_eleves" class="required">Nombre total d'élèves</label>
                    <input type="number" id="nombre_eleves" name="nombre_eleves" value="<?php echo esc_attr($nombre_eleves); ?>" min="0" required>
                </div>
                <div class="gcee-form-field">
                    <label for="nombre_garcons" class="required">Nombre de garçons</label>
                    <input type="number" id="nombre_garcons" name="nombre_garcons" value="<?php echo esc_attr($nombre_garcons); ?>" min="0" required>
                </div>
                <div class="gcee-form-field">
                    <label for="nombre_filles" class="required">Nombre de filles</label>
                    <input type="number" id="nombre_filles" name="nombre_filles" value="<?php echo esc_attr($nombre_filles); ?>" min="0" required>
                </div>
            </div>
            <div id="students-count-error" class="gcee-error-message">
                La somme du nombre de filles et de garçons doit être égale au nombre total d'élèves.
            </div>
        </div>

        <!-- Section Infrastructure -->
        <div class="gcee-form-section">
            <h3>Infrastructure et Personnel</h3>
            <div class="gcee-form-row three-columns">
                <div class="gcee-form-field">
                    <label for="nombre_salles" class="required">Nombre de salles de classe</label>
                    <input type="number" id="nombre_salles" name="nombre_salles" value="<?php echo esc_attr($nombre_salles); ?>" min="0" required>
                </div>
                <div class="gcee-form-field">
                    <label for="nombre_enseignants" class="required">Nombre d'enseignants</label>
                    <input type="number" id="nombre_enseignants" name="nombre_enseignants" value="<?php echo esc_attr($nombre_enseignants); ?>" min="0" required>
                </div>
                <div class="gcee-form-field">
                    <label for="nombre_points_eau" class="required">Nombre de points d'eau</label>
                    <input type="number" id="nombre_points_eau" name="nombre_points_eau" value="<?php echo esc_attr($nombre_points_eau); ?>" min="0" required>
                </div>
            </div>
        </div>

        <!-- Section Installations sanitaires -->
        <div class="gcee-form-section">
            <h3>Installations sanitaires</h3>
            <div class="gcee-form-row three-columns">
                <div class="gcee-form-field">
                    <label for="nombre_wc_garcons" class="required">WC pour garçons</label>
                    <input type="number" id="nombre_wc_garcons" name="nombre_wc_garcons" value="<?php echo esc_attr($nombre_wc_garcons); ?>" min="0" required>
                </div>
                <div class="gcee-form-field">
                    <label for="nombre_wc_filles" class="required">WC pour filles</label>
                    <input type="number" id="nombre_wc_filles" name="nombre_wc_filles" value="<?php echo esc_attr($nombre_wc_filles); ?>" min="0" required>
                </div>
                <div class="gcee-form-field">
                    <label for="nombre_wc_mixtes" class="required">WC mixtes</label>
                    <input type="number" id="nombre_wc_mixtes" name="nombre_wc_mixtes" value="<?php echo esc_attr($nombre_wc_mixtes); ?>" min="0" required>
                </div>
            </div>
        </div>

        <!-- Section Équipements -->
        <div class="gcee-form-section">
            <h3>Équipements et Sécurité</h3>
            <div class="gcee-form-row two-columns">
                <div class="gcee-form-field">
                    <label for="electricite" class="required">L'école a de l'électricité</label>
                    <select id="electricite" name="electricite" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="oui" <?php selected($electricite, 'oui'); ?>>Oui</option>
                        <option value="non" <?php selected($electricite, 'non'); ?>>Non</option>
                    </select>
                </div>
                <div class="gcee-form-field">
                    <label for="cloture" class="required">L'école est clôturée</label>
                    <select id="cloture" name="cloture" required>
                        <option value="">-- Sélectionner --</option>
                        <option value="oui" <?php selected($cloture, 'oui'); ?>>Oui</option>
                        <option value="non" <?php selected($cloture, 'non'); ?>>Non</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            const totalStudentsField = document.getElementById('nombre_eleves');
            const boysField = document.getElementById('nombre_garcons');
            const girlsField = document.getElementById('nombre_filles');
            const errorMessage = document.getElementById('students-count-error');
            
            function validateStudentCounts() {
                const totalStudents = parseInt(totalStudentsField.value) || 0;
                const boysCount = parseInt(boysField.value) || 0;
                const girlsCount = parseInt(girlsField.value) || 0;
                
                if (boysCount + girlsCount !== totalStudents) {
                    errorMessage.textContent = `La somme des filles (${girlsCount}) et des garçons (${boysCount}) doit être égale au nombre total d'élèves (${totalStudents}).`;
                    errorMessage.style.display = 'block';
                    document.getElementById('publish').disabled = true;
                    return false;
                } else {
                    errorMessage.style.display = 'none';
                    document.getElementById('publish').disabled = false;
                    return true;
                }
            }
            
            totalStudentsField.addEventListener('input', validateStudentCounts);
            boysField.addEventListener('input', validateStudentCounts);
            girlsField.addEventListener('input', validateStudentCounts);
            
            // Validation initiale
            validateStudentCounts();
            
            // Validation à la soumission
            document.getElementById('post').addEventListener('submit', function(e) {
                if (!validateStudentCounts()) {
                    e.preventDefault();
                    alert('Veuillez corriger les statistiques des élèves avant de publier.');
                }
            });
        });
    </script>
    <?php
}

/**
 * Ajouter les meta boxes pour les élèves
 */
function gcee_add_student_meta_boxes() {
    add_meta_box(
        'student_information',
        'Informations de l\'élève',
        'gcee_student_information_meta_box',
        'student',
        'normal',
        'high'
    );
    
    add_meta_box(
        'student_school_association',
        'École Associée',
        'gcee_student_school_meta_box',
        'student',
        'side',
        'high'
    );
}
add_action('add_meta_boxes', 'gcee_add_student_meta_boxes');

/**
 * Affichage de la meta box pour les informations de l'élève
 */
function gcee_student_information_meta_box($post) {
    wp_nonce_field('gcee_student_meta_box_nonce', 'gcee_student_meta_box_nonce');
    
    // Récupérer les valeurs existantes
    $prenom_nom = get_post_meta($post->ID, '_prenom_nom', true);
    $genre = get_post_meta($post->ID, '_genre', true);
    $date_naissance = get_post_meta($post->ID, '_date_naissance', true);
    $distance_parcourue = get_post_meta($post->ID, '_distance_parcourue', true);
    $pointure = get_post_meta($post->ID, '_pointure', true);
    $classe_frequentee = get_post_meta($post->ID, '_classe_frequentee', true);
    
    ?>
    <style>
        .gcee-student-form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        .gcee-student-form-field {
            flex: 1;
        }
        .gcee-student-form-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .gcee-student-form-field label.required:after {
            content: ' *';
            color: red;
        }
        .gcee-student-form-field input,
        .gcee-student-form-field select {
            width: 100%;
            padding: 5px;
        }
    </style>
    
    <div class="gcee-student-form-row">
        <div class="gcee-student-form-field">
            <label for="prenom_nom" class="required">Prénom & nom</label>
            <input type="text" id="prenom_nom" name="prenom_nom" value="<?php echo esc_attr($prenom_nom); ?>" required>
        </div>
    </div>
    
    <div class="gcee-student-form-row">
        <div class="gcee-student-form-field">
            <label for="genre" class="required">Garçon / Fille</label>
            <select id="genre" name="genre" required>
                <option value="">-- Sélectionner --</option>
                <option value="garcon" <?php selected($genre, 'garcon'); ?>>Garçon</option>
                <option value="fille" <?php selected($genre, 'fille'); ?>>Fille</option>
            </select>
        </div>
        <div class="gcee-student-form-field">
            <label for="date_naissance" class="required">Date de naissance</label>
            <input type="date" id="date_naissance" name="date_naissance" value="<?php echo esc_attr($date_naissance); ?>" required>
        </div>
    </div>
    
    <div class="gcee-student-form-row">
        <div class="gcee-student-form-field">
            <label for="distance_parcourue" class="required">Distance parcourue</label>
            <input type="text" id="distance_parcourue" name="distance_parcourue" value="<?php echo esc_attr($distance_parcourue); ?>" placeholder="Distance parcourue (en Km) par jour par l'élève (du domicile à l'école)" required>
        </div>
        <div class="gcee-student-form-field">
            <label for="pointure" class="required">Pointure</label>
            <input type="number" id="pointure" name="pointure" value="<?php echo esc_attr($pointure); ?>" min="1" max="50" required>
        </div>
        <div class="gcee-student-form-field">
            <label for="classe_frequentee" class="required">Classe fréquentée au primaire</label>
            <select id="classe_frequentee" name="classe_frequentee" required>
                <option value="">-- Sélectionner --</option>
                <option value="1" <?php selected($classe_frequentee, '1'); ?>>1</option>
                <option value="2" <?php selected($classe_frequentee, '2'); ?>>2</option>
                <option value="3" <?php selected($classe_frequentee, '3'); ?>>3</option>
                <option value="4" <?php selected($classe_frequentee, '4'); ?>>4</option>
                <option value="5" <?php selected($classe_frequentee, '5'); ?>>5</option>
                <option value="6" <?php selected($classe_frequentee, '6'); ?>>6</option>
            </select>
        </div>
    </div>
    <?php
}

/**
 * Meta box pour l'association école-élève
 */
function gcee_student_school_meta_box($post) {
    $associated_school = get_post_meta($post->ID, '_associated_school', true);
    $current_user = wp_get_current_user();
    
    // Si l'utilisateur est un directeur, montrer seulement ses écoles
    if (in_array('directeur_ecole', $current_user->roles)) {
        $schools = get_posts(array(
            'post_type' => 'school',
            'numberposts' => -1,
            'author' => $current_user->ID
        ));
    } else {
        // Administrateur voit toutes les écoles
        $schools = get_posts(array(
            'post_type' => 'school',
            'numberposts' => -1
        ));
    }
    
    ?>
    <label for="associated_school">Sélectionner une école :</label>
    <select name="associated_school" id="associated_school" style="width: 100%;">
        <option value="">-- Sélectionner une école --</option>
        <?php foreach ($schools as $school): ?>
            <option value="<?php echo $school->ID; ?>" <?php selected($associated_school, $school->ID); ?>>
                <?php echo esc_html($school->post_title); ?>
            </option>
        <?php endforeach; ?>
    </select>
    <?php
}

/**
 * Sauvegarde des métadonnées école
 */
function gcee_save_school_meta($post_id) {
    // Vérifications de sécurité
    if (!isset($_POST['gcee_school_meta_box_nonce']) || !wp_verify_nonce($_POST['gcee_school_meta_box_nonce'], 'gcee_school_meta_box_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (get_post_type($post_id) !== 'school') {
        return;
    }
    
    // Validation côté serveur pour les nombres d'élèves
    $total = intval($_POST['nombre_eleves']);
    $boys = intval($_POST['nombre_garcons']);
    $girls = intval($_POST['nombre_filles']);
    
    if ($boys + $girls !== $total) {
        wp_die(
            sprintf(
                'Erreur : La somme des filles (%d) et des garçons (%d) doit être égale au nombre total d\'élèves (%d).',
                $girls,
                $boys,
                $total
            ),
            'Erreur de validation',
            array('response' => 500, 'back_link' => true)
        );
    }
    
    // Sauvegarder tous les champs
    $fields = array(
        '_nom_localite' => 'nom_localite',
        '_type_ecole' => 'type_ecole',
        '_nom_directeur' => 'nom_directeur',
        '_tel_directeur' => 'tel_directeur',
        '_email_directeur' => 'email_directeur',
        '_nombre_eleves' => 'nombre_eleves',
        '_nombre_garcons' => 'nombre_garcons',
        '_nombre_filles' => 'nombre_filles',
        '_nombre_salles' => 'nombre_salles',
        '_nombre_enseignants' => 'nombre_enseignants',
        '_nombre_wc_garcons' => 'nombre_wc_garcons',
        '_nombre_wc_filles' => 'nombre_wc_filles',
        '_nombre_wc_mixtes' => 'nombre_wc_mixtes',
        '_nombre_points_eau' => 'nombre_points_eau',
        '_electricite' => 'electricite',
        '_cloture' => 'cloture'
    );
    
    foreach ($fields as $meta_key => $post_key) {
        if (isset($_POST[$post_key])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
        }
    }
}
add_action('save_post', 'gcee_save_school_meta');

/**
 * Sauvegarde des métadonnées élève
 */
function gcee_save_student_meta($post_id) {
    // Vérifications de sécurité
    if (!isset($_POST['gcee_student_meta_box_nonce']) || !wp_verify_nonce($_POST['gcee_student_meta_box_nonce'], 'gcee_student_meta_box_nonce')) {
        return;
    }
    
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    if (get_post_type($post_id) !== 'student') {
        return;
    }
    
    // Sauvegarder tous les champs
    $fields = array(
        '_prenom_nom' => 'prenom_nom',
        '_genre' => 'genre',
        '_date_naissance' => 'date_naissance',
        '_distance_parcourue' => 'distance_parcourue',
        '_pointure' => 'pointure',
        '_classe_frequentee' => 'classe_frequentee',
        '_associated_school' => 'associated_school'
    );
    
    foreach ($fields as $meta_key => $post_key) {
        if (isset($_POST[$post_key])) {
            update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$post_key]));
        }
    }
}
add_action('save_post', 'gcee_save_student_meta');

// Inclure les autres fichiers du plugin
require_once plugin_dir_path(__FILE__) . 'includes/restrictions.php';
require_once plugin_dir_path(__FILE__) . 'includes/mayor-dashboard.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';