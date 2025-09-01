<?php
/*
Plugin Name: Générateur de Données Fictives - Kaolack
Plugin URI: https://im-mass.com/
Description: Plugin pour générer des données fictives (communes, écoles, élèves, utilisateurs) pour la région de Kaolack au Sénégal.
Version: 1.0
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
 * Ajouter la page d'administration du générateur
 */
function gdfk_add_admin_page() {
    add_management_page(
        'Générateur de Données Fictives',
        'Générateur Kaolack',
        'manage_options',
        'generateur-kaolack',
        'gdfk_render_admin_page'
    );
}
add_action('admin_menu', 'gdfk_add_admin_page');

/**
 * Données de base pour la région de Kaolack
 */
function gdfk_get_kaolack_data() {
    return array(
        'communes' => array(
            'Kaolack' => array(
                'departement' => 'Kaolack',
                'population' => 233708,
                'localites' => array('Kaolack Centre', 'Médina Baye', 'Touba Kaolack', 'Sam', 'Thioffack')
            ),
            'Guinguinéo' => array(
                'departement' => 'Guinguinéo',
                'population' => 35856,
                'localites' => array('Guinguinéo', 'Keur Baka', 'Paoskoto', 'Ngayène', 'Kahi')
            ),
            'Nioro du Rip' => array(
                'departement' => 'Nioro du Rip',
                'population' => 45586,
                'localites' => array('Nioro du Rip', 'Médina Sabakh', 'Paoskoto', 'Keur Madiabel', 'Gainthe Kaye')
            ),
            'Ndoffane' => array(
                'departement' => 'Kaolack',
                'population' => 22345,
                'localites' => array('Ndoffane', 'Thiomby', 'Keur Samba Guèye', 'Latmingué', 'Prokhane')
            ),
            'Kabadio' => array(
                'departement' => 'Guinguinéo', 
                'population' => 18756,
                'localites' => array('Kabadio', 'Ndiédieng', 'Keur Ngalgou', 'Darou Miname', 'Nguidjilogne')
            ),
            'Wack Ngouna' => array(
                'departement' => 'Nioro du Rip',
                'population' => 25634,
                'localites' => array('Wack Ngouna', 'Kaffrine', 'Ndame', 'Gniby', 'Ida Mouride')
            ),
            'Koungheul' => array(
                'departement' => 'Koungheul',
                'population' => 28945,
                'localites' => array('Koungheul', 'Lour Escale', 'Payar', 'Missirah Wadène', 'Kouthiaba Wolof')
            ),
            'Maka Coulibantang' => array(
                'departement' => 'Koungheul',
                'population' => 16789,
                'localites' => array('Maka Coulibantang', 'Saly Escale', 'Keur Mamadou', 'Pakour', 'Thiamène')
            )
        ),
        'prenoms_masculins' => array(
            'Moussa', 'Ibrahima', 'Mamadou', 'Ousmane', 'Abdou', 'Cheikh', 'Pape', 'Modou', 'Babacar', 'Samba',
            'Alioune', 'Omar', 'Mor', 'Serigne', 'Papa', 'Baye', 'Daouda', 'Fallou', 'Souleymane', 'Idrissa',
            'Doudou', 'Assane', 'Mbacké', 'Thierno', 'Amadou', 'Birane', 'Lamine', 'Bamba', 'Tafsir', 'Mansour'
        ),
        'prenoms_feminins' => array(
            'Fatou', 'Aïssatou', 'Aminata', 'Mariama', 'Khady', 'Awa', 'Dieynaba', 'Ndèye', 'Penda', 'Astou',
            'Bineta', 'Coumba', 'Nafi', 'Rokhaya', 'Yacine', 'Mame', 'Adama', 'Aida', 'Binta', 'Ndeye',
            'Ramatoulaye', 'Safiatou', 'Marème', 'Oumou', 'Maimouna', 'Aby', 'Anna', 'Khadidiatou', 'Seynabou', 'Fatoumata'
        ),
        'noms_famille' => array(
            'Diop', 'Diouf', 'Ndiaye', 'Fall', 'Faye', 'Seck', 'Mbaye', 'Gueye', 'Diallo', 'Ba',
            'Sy', 'Sarr', 'Cissé', 'Thiam', 'Kane', 'Sow', 'Wade', 'Lo', 'Ndour', 'Dieye',
            'Sene', 'Mbodj', 'Toure', 'Konté', 'Camara', 'Badiane', 'Pouye', 'Ndong', 'Mané', 'Dia',
            'Thiaw', 'Samb', 'Sall', 'Ngom', 'Joof', 'Dieme', 'Kébé', 'Sakho', 'Ly', 'Niang'
        ),
        'types_ecoles' => array('publique', 'privee', 'communautaire'),
        'distances' => array('0.5', '1', '1.5', '2', '2.5', '3', '3.5', '4', '4.5', '5', '6', '7', '8', '10', '12'),
        'pointures' => array(25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45)
    );
}

/**
 * Rendu de la page d'administration
 */
function gdfk_render_admin_page() {
    if (isset($_POST['generate_data'])) {
        $result = gdfk_generate_fake_data();
        echo '<div class="notice notice-success"><p>' . $result . '</p></div>';
    }
    
    if (isset($_POST['clean_data'])) {
        $result = gdfk_clean_fake_data();
        echo '<div class="notice notice-info"><p>' . $result . '</p></div>';
    }
    
    ?>
    <div class="wrap">
        <h1>Générateur de Données Fictives - Région de Kaolack</h1>
        
        <style>
            .gdfk-card {
                background: #fff;
                border: 1px solid #ccd0d4;
                border-radius: 4px;
                padding: 20px;
                margin: 20px 0;
                max-width: 800px;
            }
            .gdfk-warning {
                background: #fff3cd;
                border: 1px solid #ffeaa7;
                border-radius: 4px;
                padding: 15px;
                margin: 15px 0;
                color: #856404;
            }
            .gdfk-stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin: 20px 0;
            }
            .gdfk-stat {
                text-align: center;
                padding: 15px;
                background: #f8f9fa;
                border-radius: 4px;
            }
            .gdfk-number {
                font-size: 24px;
                font-weight: bold;
                color: #0073aa;
            }
        </style>
        
        <div class="gdfk-card">
            <h2>Statistiques actuelles</h2>
            <div class="gdfk-stats">
                <div class="gdfk-stat">
                    <div class="gdfk-number"><?php echo gdfk_count_communes(); ?></div>
                    <div>Communes</div>
                </div>
                <div class="gdfk-stat">
                    <div class="gdfk-number"><?php echo gdfk_count_schools(); ?></div>
                    <div>Écoles</div>
                </div>
                <div class="gdfk-stat">
                    <div class="gdfk-number"><?php echo gdfk_count_students(); ?></div>
                    <div>Élèves</div>
                </div>
                <div class="gdfk-stat">
                    <div class="gdfk-number"><?php echo gdfk_count_directors(); ?></div>
                    <div>Directeurs</div>
                </div>
                <div class="gdfk-stat">
                    <div class="gdfk-number"><?php echo gdfk_count_mayors(); ?></div>
                    <div>Maires</div>
                </div>
            </div>
        </div>
        
        <div class="gdfk-card">
            <h2>Générer des données fictives</h2>
            <p>Ce générateur va créer automatiquement :</p>
            <ul>
                <li><strong>8 communes</strong> de la région de Kaolack avec leurs localités</li>
                <li><strong>24-40 écoles</strong> réparties dans les communes (3-5 par commune)</li>
                <li><strong>600-1200 élèves</strong> répartis dans les écoles (15-40 par école)</li>
                <li><strong>24-40 directeurs d'école</strong> (un par école)</li>
                <li><strong>8 maires</strong> (un par commune)</li>
            </ul>
            
            <div class="gdfk-warning">
                <strong>Attention :</strong> Cette opération va créer de nombreuses données fictives dans votre base de données. 
                Assurez-vous d'avoir une sauvegarde avant de procéder.
            </div>
            
            <form method="post" action="">
                <input type="hidden" name="generate_data" value="1">
                <p>
                    <input type="submit" class="button button-primary" value="Générer les données fictives" 
                           onclick="return confirm('Êtes-vous sûr de vouloir générer les données fictives ? Cette opération peut prendre quelques minutes.');">
                </p>
            </form>
        </div>
        
        <div class="gdfk-card">
            <h2>Nettoyer les données</h2>
            <p>Supprimer toutes les données générées (communes, écoles, élèves, utilisateurs) de la région de Kaolack.</p>
            
            <div class="gdfk-warning">
                <strong>Attention :</strong> Cette opération est irréversible. Toutes les données fictives seront définitivement supprimées.
            </div>
            
            <form method="post" action="">
                <input type="hidden" name="clean_data" value="1">
                <p>
                    <input type="submit" class="button button-secondary" value="Nettoyer toutes les données" 
                           onclick="return confirm('Êtes-vous sûr de vouloir supprimer toutes les données fictives ? Cette action est irréversible.');">
                </p>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Fonctions de comptage pour les statistiques
 */
function gdfk_count_communes() {
    $terms = get_terms(array(
        'taxonomy' => 'commune',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key' => '_generated_kaolack',
                'value' => 'yes',
                'compare' => '='
            )
        )
    ));
    return count($terms);
}

function gdfk_count_schools() {
    $schools = get_posts(array(
        'post_type' => 'school',
        'numberposts' => -1,
        'meta_query' => array(
            array(
                'key' => '_generated_kaolack',
                'value' => 'yes',
                'compare' => '='
            )
        )
    ));
    return count($schools);
}

function gdfk_count_students() {
    $students = get_posts(array(
        'post_type' => 'student',
        'numberposts' => -1,
        'meta_query' => array(
            array(
                'key' => '_generated_kaolack',
                'value' => 'yes',
                'compare' => '='
            )
        )
    ));
    return count($students);
}

function gdfk_count_directors() {
    $users = get_users(array(
        'role' => 'directeur_ecole',
        'meta_query' => array(
            array(
                'key' => '_generated_kaolack',
                'value' => 'yes',
                'compare' => '='
            )
        )
    ));
    return count($users);
}

function gdfk_count_mayors() {
    $users = get_users(array(
        'role' => 'maire_commune',
        'meta_query' => array(
            array(
                'key' => '_generated_kaolack',
                'value' => 'yes',
                'compare' => '='
            )
        )
    ));
    return count($users);
}

/**
 * Générateur principal de données fictives
 */
function gdfk_generate_fake_data() {
    set_time_limit(300); // 5 minutes max
    $data = gdfk_get_kaolack_data();
    $results = array();
    
    // 1. Créer les communes
    $commune_ids = array();
    foreach ($data['communes'] as $commune_name => $commune_info) {
        $term_exists = term_exists($commune_name, 'commune');
        if (!$term_exists) {
            $term = wp_insert_term($commune_name, 'commune', array(
                'description' => 'Commune de ' . $commune_name . ', département de ' . $commune_info['departement'] . ', région de Kaolack'
            ));
            
            if (!is_wp_error($term)) {
                $commune_ids[$commune_name] = $term['term_id'];
                add_term_meta($term['term_id'], '_generated_kaolack', 'yes');
                add_term_meta($term['term_id'], '_departement', $commune_info['departement']);
                add_term_meta($term['term_id'], '_population', $commune_info['population']);
            }
        } else {
            $commune_ids[$commune_name] = $term_exists['term_id'];
        }
    }
    $results[] = count($commune_ids) . ' communes créées';
    
    // 2. Créer les maires
    $mayor_ids = array();
    foreach ($commune_ids as $commune_name => $commune_id) {
        $mayor_data = gdfk_generate_person_data($data, 'maire');
        $mayor_username = sanitize_user('maire_' . strtolower(str_replace(' ', '', $commune_name)));
        $mayor_email = $mayor_username . '@kaolack-fictif.sn';
        
        $mayor_id = wp_create_user($mayor_username, wp_generate_password(), $mayor_email);
        
        if (!is_wp_error($mayor_id)) {
            $user = new WP_User($mayor_id);
            $user->set_role('maire_commune');
            
            wp_update_user(array(
                'ID' => $mayor_id,
                'first_name' => $mayor_data['prenom'],
                'last_name' => $mayor_data['nom'],
                'display_name' => $mayor_data['prenom'] . ' ' . $mayor_data['nom']
            ));
            
            update_user_meta($mayor_id, '_associated_commune', $commune_id);
            update_user_meta($mayor_id, '_generated_kaolack', 'yes');
            update_user_meta($mayor_id, '_commune_name', $commune_name);
            
            $mayor_ids[$commune_name] = $mayor_id;
        }
    }
    $results[] = count($mayor_ids) . ' maires créés';
    
    // 3. Créer les écoles
    $school_ids = array();
    foreach ($data['communes'] as $commune_name => $commune_info) {
        $nb_ecoles = rand(3, 5); // 3 à 5 écoles par commune
        
        for ($i = 1; $i <= $nb_ecoles; $i++) {
            $localite = $commune_info['localites'][array_rand($commune_info['localites'])];
            $type_ecole = $data['types_ecoles'][array_rand($data['types_ecoles'])];
            
            $school_name = 'École ' . $localite . ' ' . $i;
            
            $school_data = array(
                'post_title' => $school_name,
                'post_type' => 'school',
                'post_status' => 'publish'
            );
            
            $school_id = wp_insert_post($school_data);
            
            if ($school_id) {
                // Assigner à la commune
                wp_set_post_terms($school_id, array($commune_ids[$commune_name]), 'commune');
                
                // Générer les données du directeur
                $director_data = gdfk_generate_person_data($data, 'directeur');
                
                // Générer les statistiques d'élèves
                $total_eleves = rand(15, 40);
                $pourcentage_filles = rand(45, 55) / 100;
                $nb_filles = floor($total_eleves * $pourcentage_filles);
                $nb_garcons = $total_eleves - $nb_filles;
                
                // Sauvegarder les métadonnées de l'école
                $school_meta = array(
                    '_nom_localite' => $localite,
                    '_type_ecole' => $type_ecole,
                    '_nom_directeur' => $director_data['prenom'] . ' ' . $director_data['nom'],
                    '_tel_directeur' => gdfk_generate_phone(),
                    '_email_directeur' => strtolower($director_data['prenom'] . '.' . $director_data['nom']) . '@ecole-' . strtolower(str_replace(' ', '', $localite)) . '.sn',
                    '_nombre_eleves' => $total_eleves,
                    '_nombre_garcons' => $nb_garcons,
                    '_nombre_filles' => $nb_filles,
                    '_nombre_salles' => rand(3, 8),
                    '_nombre_enseignants' => rand(2, 6),
                    '_nombre_wc_garcons' => rand(1, 3),
                    '_nombre_wc_filles' => rand(1, 3),
                    '_nombre_wc_mixtes' => rand(0, 2),
                    '_nombre_points_eau' => rand(1, 4),
                    '_electricite' => rand(0, 1) ? 'oui' : 'non',
                    '_cloture' => rand(0, 1) ? 'oui' : 'non',
                    '_validation_status' => rand(0, 100) < 80 ? 'validated' : 'pending',
                    '_generated_kaolack' => 'yes'
                );
                
                foreach ($school_meta as $key => $value) {
                    update_post_meta($school_id, $key, $value);
                }
                
                // Créer le directeur
                $director_username = sanitize_user('dir_' . strtolower(str_replace(' ', '', $localite)) . '_' . $i);
                $director_email = strtolower($director_data['prenom'] . '.' . $director_data['nom']) . '@ecole-' . strtolower(str_replace(' ', '', $localite)) . '.sn';
                
                $director_id = wp_create_user($director_username, wp_generate_password(), $director_email);
                
                if (!is_wp_error($director_id)) {
                    $user = new WP_User($director_id);
                    $user->set_role('directeur_ecole');
                    
                    wp_update_user(array(
                        'ID' => $director_id,
                        'first_name' => $director_data['prenom'],
                        'last_name' => $director_data['nom'],
                        'display_name' => $director_data['prenom'] . ' ' . $director_data['nom']
                    ));
                    
                    update_user_meta($director_id, '_generated_kaolack', 'yes');
                    update_user_meta($director_id, '_school_id', $school_id);
                    
                    // Assigner l'école au directeur
                    wp_update_post(array(
                        'ID' => $school_id,
                        'post_author' => $director_id
                    ));
                    
                    update_post_meta($school_id, '_school_director', $director_id);
                }
                
                $school_ids[] = $school_id;
            }
        }
    }
    $results[] = count($school_ids) . ' écoles créées';
    
    // 4. Créer les élèves
    $total_students = 0;
    foreach ($school_ids as $school_id) {
        $nb_eleves_total = get_post_meta($school_id, '_nombre_eleves', true);
        $nb_garcons = get_post_meta($school_id, '_nombre_garcons', true);
        $nb_filles = get_post_meta($school_id, '_nombre_filles', true);
        
        // Créer les garçons
        for ($i = 0; $i < $nb_garcons; $i++) {
            $student_data = gdfk_generate_student_data($data, 'garcon');
            $student_id = gdfk_create_student($student_data, $school_id);
            if ($student_id) $total_students++;
        }
        
        // Créer les filles
        for ($i = 0; $i < $nb_filles; $i++) {
            $student_data = gdfk_generate_student_data($data, 'fille');
            $student_id = gdfk_create_student($student_data, $school_id);
            if ($student_id) $total_students++;
        }
    }
    $results[] = $total_students . ' élèves créés';
    
    return 'Génération terminée avec succès ! ' . implode(', ', $results);
}

/**
 * Générer les données d'une personne
 */
function gdfk_generate_person_data($data, $type = 'directeur') {
    $genre = rand(0, 1) ? 'masculin' : 'feminin';
    
    if ($genre === 'masculin') {
        $prenom = $data['prenoms_masculins'][array_rand($data['prenoms_masculins'])];
    } else {
        $prenom = $data['prenoms_feminins'][array_rand($data['prenoms_feminins'])];
    }
    
    $nom = $data['noms_famille'][array_rand($data['noms_famille'])];
    
    return array(
        'prenom' => $prenom,
        'nom' => $nom,
        'genre' => $genre
    );
}

/**
 * Générer les données d'un élève
 */
function gdfk_generate_student_data($data, $genre) {
    if ($genre === 'garcon') {
        $prenom = $data['prenoms_masculins'][array_rand($data['prenoms_masculins'])];
    } else {
        $prenom = $data['prenoms_feminins'][array_rand($data['prenoms_feminins'])];
    }
    
    $nom = $data['noms_famille'][array_rand($data['noms_famille'])];
    
    // Date de naissance (entre 5 et 15 ans)
    $age = rand(5, 15);
    $birth_date = date('Y-m-d', strtotime('-' . $age . ' years -' . rand(0, 365) . ' days'));
    
    return array(
        'prenom_nom' => $prenom . ' ' . $nom,
        'genre' => $genre,
        'date_naissance' => $birth_date,
        'distance_parcourue' => $data['distances'][array_rand($data['distances'])],
        'pointure' => $data['pointures'][array_rand($data['pointures'])],
        'classe_frequentee' => rand(1, 6)
    );
}

/**
 * Créer un élève
 */
function gdfk_create_student($student_data, $school_id) {
    $student_post = array(
        'post_title' => $student_data['prenom_nom'],
        'post_type' => 'student',
        'post_status' => 'publish'
    );
    
    $student_id = wp_insert_post($student_post);
    
    if ($student_id) {
        $student_meta = array(
            '_prenom_nom' => $student_data['prenom_nom'],
            '_genre' => $student_data['genre'],
            '_date_naissance' => $student_data['date_naissance'],
            '_distance_parcourue' => $student_data['distance_parcourue'],
            '_pointure' => $student_data['pointure'],
            '_classe_frequentee' => $student_data['classe_frequentee'],
            '_associated_school' => $school_id,
            '_generated_kaolack' => 'yes'
        );
        
        foreach ($student_meta as $key => $value) {
            update_post_meta($student_id, $key, $value);
        }
        
        return $student_id;
    }
    
    return false;
}

/**
 * Générer un numéro de téléphone sénégalais
 */
function gdfk_generate_phone() {
    $prefixes = array('77', '78', '76', '70', '75');
    $prefix = $prefixes[array_rand($prefixes)];
    $number = $prefix . rand(1000000, 9999999);
    return '+221 ' . $number;
}

/**
 * Nettoyer toutes les données générées
 */
function gdfk_clean_fake_data() {
    $results = array();
    
    // Supprimer les élèves générés
    $students = get_posts(array(
        'post_type' => 'student',
        'numberposts' => -1,
        'meta_query' => array(
            array(
                'key' => '_generated_kaolack',
                'value' => 'yes',
                'compare' => '='
            )
        )
    ));
    
    foreach ($students as $student) {
        wp_delete_post($student->ID, true);
    }
    $results[] = count($students) . ' élèves supprimés';
    
    // Supprimer les écoles générées
    $schools = get_posts(array(
        'post_type' => 'school',
        'numberposts' => -1,
        'meta_query' => array(
            array(
                'key' => '_generated_kaolack',
                'value' => 'yes',
                'compare' => '='
            )
        )
    ));
    
    foreach ($schools as $school) {
        wp_delete_post($school->ID, true);
    }
    $results[] = count($schools) . ' écoles supprimées';
    
    // Supprimer les utilisateurs générés
    $users = get_users(array(
        'meta_query' => array(
            array(
                'key' => '_generated_kaolack',
                'value' => 'yes',
                'compare' => '='
            )
        )
    ));
    
    foreach ($users as $user) {
        wp_delete_user($user->ID);
    }
    $results[] = count($users) . ' utilisateurs supprimés';
    
    // Supprimer les communes générées
    $communes = get_terms(array(
        'taxonomy' => 'commune',
        'hide_empty' => false,
        'meta_query' => array(
            array(
                'key' => '_generated_kaolack',
                'value' => 'yes',
                'compare' => '='
            )
        )
    ));
    
    foreach ($communes as $commune) {
        wp_delete_term($commune->term_id, 'commune');
    }
    $results[] = count($communes) . ' communes supprimées';
    
    return 'Nettoyage terminé ! ' . implode(', ', $results);
}