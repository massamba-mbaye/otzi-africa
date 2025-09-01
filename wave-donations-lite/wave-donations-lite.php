<?php
/**
 * Plugin Name: Wave Donations Lite
 * Plugin URI: https://www.im-mass.com/
 * Description: Plugin simple de gestion des donations avec intégration Wave SN
 * Version: 1.0.0
 * Author: Massamba MBAYE
 * Author URI: https://www.im-mass.com/
 * Text Domain: wave-donations-lite
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.3
 * Requires PHP: 7.4
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

// Sécurité - Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

// Définir les constantes du plugin
define('WDL_VERSION', '1.0.0');
define('WDL_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WDL_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('WDL_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Classe principale Wave Donations Lite
 */
class WaveDonationsLite {
    
    /**
     * Instance unique du plugin (Singleton)
     */
    private static $instance = null;
    
    /**
     * Obtenir l'instance unique du plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructeur privé pour le pattern Singleton
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialisation du plugin
     */
    private function init() {
        // Charger les fichiers nécessaires
        $this->load_dependencies();
        
        // Hooks d'activation et désactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Hooks WordPress
        add_action('init', array($this, 'init_plugin'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        
        // Initialiser les classes
        add_action('plugins_loaded', array($this, 'init_classes'));
    }
    
    /**
     * Charger les dépendances
     */
    private function load_dependencies() {
        require_once WDL_PLUGIN_PATH . 'includes/class-wdl-database.php';
        require_once WDL_PLUGIN_PATH . 'includes/class-wdl-form.php';
        require_once WDL_PLUGIN_PATH . 'includes/class-wdl-admin.php';
    }
    
    /**
     * Activation du plugin
     */
    public function activate() {
        // Créer les tables de base de données
        WDL_Database::create_tables();
        
        // Créer les pages nécessaires
        $this->create_pages();
        
        // Ajouter les options par défaut
        $this->set_default_options();
        
        // Vider le cache des permaliens
        flush_rewrite_rules();
    }
    
    /**
     * Désactivation du plugin
     */
    public function deactivate() {
        // Vider le cache des permaliens
        flush_rewrite_rules();
    }
    
    /**
     * Initialisation du plugin après chargement
     */
    public function init_plugin() {
        // Charger la traduction
        load_plugin_textdomain('wave-donations-lite', false, dirname(WDL_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Initialiser les classes du plugin
     */
    public function init_classes() {
        // Initialiser le formulaire frontend
        new WDL_Form();
        
        // Initialiser l'admin seulement dans l'admin
        if (is_admin()) {
            new WDL_Admin();
        }
    }
    
    /**
     * Charger les scripts et styles frontend
     */
    public function enqueue_frontend_scripts() {
        wp_enqueue_style(
            'wdl-frontend-style',
            WDL_PLUGIN_URL . 'assets/style.css',
            array(),
            WDL_VERSION
        );
        
        wp_enqueue_script(
            'wdl-frontend-script',
            WDL_PLUGIN_URL . 'assets/script.js',
            array('jquery'),
            WDL_VERSION,
            true
        );
        
        // Localiser le script pour AJAX
        wp_localize_script('wdl-frontend-script', 'wdl_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wdl_nonce'),
            'wave_return_url' => home_url('/donation-success/'),
            'wave_cancel_url' => home_url('/donation-failed/')
        ));
    }
    
    /**
     * Charger les scripts et styles admin
     */
    public function enqueue_admin_scripts($hook) {
        // Charger seulement sur nos pages admin
        if (strpos($hook, 'wave-donations') === false) {
            return;
        }
        
        wp_enqueue_style(
            'wdl-admin-style',
            WDL_PLUGIN_URL . 'assets/admin-style.css',
            array(),
            WDL_VERSION
        );
    }
    
    /**
     * Créer les pages nécessaires
     */
    private function create_pages() {
        $pages = array(
            'donation-success' => array(
                'title' => 'Donation Confirmée',
                'content' => '[wdl_success_page]'
            ),
            'donation-failed' => array(
                'title' => 'Donation Échouée',
                'content' => '[wdl_failed_page]'
            )
        );
        
        foreach ($pages as $slug => $page_data) {
            // Vérifier si la page existe déjà
            $page = get_page_by_path($slug);
            
            if (!$page) {
                $page_id = wp_insert_post(array(
                    'post_title' => $page_data['title'],
                    'post_content' => $page_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => $slug
                ));
                
                // Sauvegarder l'ID de la page dans les options
                update_option('wdl_' . str_replace('-', '_', $slug) . '_page_id', $page_id);
            }
        }
    }
    
    /**
     * Définir les options par défaut
     */
    private function set_default_options() {
        $default_options = array(
            'wdl_wave_merchant_id' => '',
            'wdl_wave_api_key' => '',
            'wdl_currency' => 'XOF',
            'wdl_default_amounts' => '1000,5000,10000,25000,50000',
            'wdl_min_amount' => 500,
            'wdl_success_message' => 'Merci pour votre généreux don !',
            'wdl_failed_message' => 'Désolé, votre paiement n\'a pas pu être traité.'
        );
        
        foreach ($default_options as $option_name => $default_value) {
            if (get_option($option_name) === false) {
                add_option($option_name, $default_value);
            }
        }
    }
}

/**
 * Fonction helper pour obtenir l'instance du plugin
 */
function WDL() {
    return WaveDonationsLite::get_instance();
}

// Initialiser le plugin
WDL();

/**
 * Fonction d'installation pour les mises à jour
 */
function wdl_install() {
    WDL_Database::create_tables();
}

/**
 * Hook pour les mises à jour de version
 */
add_action('upgrader_process_complete', 'wdl_upgrade_function', 10, 2);
function wdl_upgrade_function($upgrader_object, $options) {
    if ($options['action'] == 'update' && $options['type'] == 'plugin') {
        if (isset($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                if ($plugin == WDL_PLUGIN_BASENAME) {
                    wdl_install();
                }
            }
        }
    }
}