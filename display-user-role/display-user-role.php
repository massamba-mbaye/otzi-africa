<?php
/**
 * Plugin Name: Display user role
 * Plugin URI: https://im-mass.com/
 * Description: Affiche le rôle de l'utilisateur connecté via un shortcode. Utilisez le shortcode [user_role] pour afficher le rôle.
 * Version: 1.1
 * Author: Massamba MBAYE
 * Author URI: https://www.linkedin.com/in/massamba-mbaye/
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Sécurité : empêcher l'accès direct au fichier.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fonction pour récupérer et afficher le rôle de l'utilisateur connecté.
 *
 * @return string
 */
function display_user_role_shortcode() {
    // Vérifier si un utilisateur est connecté.
    if ( is_user_logged_in() ) {
        $user = wp_get_current_user();

        // Récupérer les rôles de l'utilisateur.
        $roles = $user->roles;

        // Afficher les rôles sous forme de liste (si plusieurs).
        return 'Votre rôle : ' . implode(', ', $roles);
    }

    // Si aucun utilisateur n'est connecté.
    return 'Vous devez être connecté pour voir votre rôle.';
}

// Ajouter le shortcode [user_role] pour afficher le rôle.
add_shortcode( 'user_role', 'display_user_role_shortcode' );