<?php
/**
 * Classe de gestion du formulaire de donation
 * Gère l'affichage, la validation et le traitement des donations
 */

// Sécurité - Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class WDL_Form {
    
    /**
     * Constructeur
     */
    public function __construct() {
        $this->init();
    }
    
    /**
     * Initialisation
     */
    private function init() {
        // Enregistrer les shortcodes
        add_shortcode('wave_donation_form', array($this, 'display_donation_form'));
        add_shortcode('wdl_success_page', array($this, 'display_success_page'));
        add_shortcode('wdl_failed_page', array($this, 'display_failed_page'));
        
        // Gérer les soumissions AJAX
        add_action('wp_ajax_wdl_process_donation', array($this, 'process_donation'));
        add_action('wp_ajax_nopriv_wdl_process_donation', array($this, 'process_donation'));
        
        // Gérer les retours de Wave
        add_action('init', array($this, 'handle_wave_return'));
    }
    
    /**
     * Afficher le formulaire de donation
     */
    public function display_donation_form($atts = array()) {
        // Attributs par défaut
        $atts = shortcode_atts(array(
            'title' => 'Faire un don',
            'show_amounts' => 'true',
            'show_message' => 'true'
        ), $atts);
        
        // Commencer la capture de sortie
        ob_start();
        
        // Charger le template
        $template_path = WDL_PLUGIN_PATH . 'templates/donation-form.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo $this->get_default_form_html($atts);
        }
        
        return ob_get_clean();
    }
    
    /**
     * Générer le HTML par défaut du formulaire
     */
    private function get_default_form_html($atts) {
        $default_amounts = explode(',', get_option('wdl_default_amounts', '1000,5000,10000,25000,50000'));
        $currency = get_option('wdl_currency', 'XOF');
        $min_amount = get_option('wdl_min_amount', 500);
        
        $html = '<div class="wdl-donation-form-container">';
        
        if (!empty($atts['title'])) {
            $html .= '<h3 class="wdl-form-title">' . esc_html($atts['title']) . '</h3>';
        }
        
        $html .= '<form id="wdl-donation-form" class="wdl-donation-form">';
        $html .= wp_nonce_field('wdl_donation_nonce', 'wdl_nonce', true, false);
        
        // Montants prédéfinis
        if ($atts['show_amounts'] === 'true') {
            $html .= '<div class="wdl-amount-section">';
            $html .= '<label class="wdl-label">Choisissez un montant :</label>';
            $html .= '<div class="wdl-amount-buttons">';
            
            foreach ($default_amounts as $amount) {
                $amount = trim($amount);
                if (!empty($amount)) {
                    $html .= '<button type="button" class="wdl-amount-btn" data-amount="' . esc_attr($amount) . '">';
                    $html .= number_format($amount, 0, ',', ' ') . ' ' . $currency;
                    $html .= '</button>';
                }
            }
            
            $html .= '</div>';
            $html .= '</div>';
        }
        
        // Montant personnalisé
        $html .= '<div class="wdl-custom-amount-section">';
        $html .= '<label for="wdl_amount" class="wdl-label">Montant personnalisé (' . $currency . ') :</label>';
        $html .= '<input type="number" id="wdl_amount" name="amount" min="' . $min_amount . '" step="1" required class="wdl-input">';
        $html .= '<small class="wdl-help-text">Montant minimum : ' . number_format($min_amount, 0, ',', ' ') . ' ' . $currency . '</small>';
        $html .= '</div>';
        
        // Informations du donateur
        $html .= '<div class="wdl-donor-section">';
        $html .= '<h4 class="wdl-section-title">Vos informations</h4>';
        
        $html .= '<div class="wdl-field-group">';
        $html .= '<label for="wdl_donor_name" class="wdl-label">Nom complet *</label>';
        $html .= '<input type="text" id="wdl_donor_name" name="donor_name" required class="wdl-input">';
        $html .= '</div>';
        
        $html .= '<div class="wdl-field-group">';
        $html .= '<label for="wdl_donor_email" class="wdl-label">Email *</label>';
        $html .= '<input type="email" id="wdl_donor_email" name="donor_email" required class="wdl-input">';
        $html .= '</div>';
        
        $html .= '<div class="wdl-field-group">';
        $html .= '<label for="wdl_donor_phone" class="wdl-label">Téléphone</label>';
        $html .= '<input type="tel" id="wdl_donor_phone" name="donor_phone" class="wdl-input">';
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Message du donateur
        if ($atts['show_message'] === 'true') {
            $html .= '<div class="wdl-message-section">';
            $html .= '<label for="wdl_donor_message" class="wdl-label">Message (optionnel)</label>';
            $html .= '<textarea id="wdl_donor_message" name="donor_message" rows="3" class="wdl-textarea"></textarea>';
            $html .= '</div>';
        }
        
        // Bouton de soumission
        $html .= '<div class="wdl-submit-section">';
        $html .= '<button type="submit" class="wdl-submit-btn">Faire un don</button>';
        $html .= '<div class="wdl-loading" style="display: none;">Traitement en cours...</div>';
        $html .= '</div>';
        
        $html .= '</form>';
        
        // Messages d'erreur/succès
        $html .= '<div id="wdl-messages" class="wdl-messages"></div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Traiter la soumission du formulaire
     */
    public function process_donation() {
        // Vérifier le nonce
        if (!wp_verify_nonce($_POST['wdl_nonce'], 'wdl_donation_nonce')) {
            wp_die('Erreur de sécurité');
        }
        
        // Récupérer et nettoyer les données
        $donation_data = array(
            'donor_name' => sanitize_text_field($_POST['donor_name']),
            'donor_email' => sanitize_email($_POST['donor_email']),
            'donor_phone' => !empty($_POST['donor_phone']) ? sanitize_text_field($_POST['donor_phone']) : '',
            'amount' => floatval($_POST['amount']),
            'donor_message' => !empty($_POST['donor_message']) ? sanitize_textarea_field($_POST['donor_message']) : ''
        );
        
        // Validation
        $errors = $this->validate_donation_data($donation_data);
        
        if (!empty($errors)) {
            wp_send_json_error(array(
                'message' => 'Erreurs de validation',
                'errors' => $errors
            ));
        }
        
        // Sauvegarder la donation en base
        $donation_id = WDL_Database::insert_donation($donation_data);
        
        if (is_wp_error($donation_id)) {
            wp_send_json_error(array(
                'message' => 'Erreur lors de la sauvegarde : ' . $donation_id->get_error_message()
            ));
        }
        
        // Récupérer la donation créée
        global $wpdb;
        $table_name = WDL_Database::get_donations_table();
        $donation = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_name WHERE id = %d",
            $donation_id
        ));
        
        if (!$donation) {
            wp_send_json_error(array(
                'message' => 'Erreur : donation non trouvée après création'
            ));
        }
        
        // Générer l'URL de paiement Wave
        $wave_url = $this->generate_wave_payment_url($donation);
        
        if (is_wp_error($wave_url)) {
            wp_send_json_error(array(
                'message' => 'Erreur Wave : ' . $wave_url->get_error_message()
            ));
        }
        
        // Mettre à jour la donation avec l'URL Wave
        WDL_Database::update_donation($donation->donation_id, array(
            'wave_payment_url' => $wave_url
        ));
        
        // Retourner l'URL de redirection
        wp_send_json_success(array(
            'message' => 'Donation créée avec succès',
            'redirect_url' => $wave_url,
            'donation_id' => $donation->donation_id
        ));
    }
    
    /**
     * Valider les données de donation
     */
    private function validate_donation_data($data) {
        $errors = array();
        
        // Vérifier les champs obligatoires
        if (empty($data['donor_name'])) {
            $errors['donor_name'] = 'Le nom est obligatoire';
        }
        
        if (empty($data['donor_email'])) {
            $errors['donor_email'] = 'L\'email est obligatoire';
        } elseif (!is_email($data['donor_email'])) {
            $errors['donor_email'] = 'L\'email n\'est pas valide';
        }
        
        if (empty($data['amount']) || $data['amount'] <= 0) {
            $errors['amount'] = 'Le montant doit être supérieur à 0';
        }
        
        // Vérifier le montant minimum
        $min_amount = get_option('wdl_min_amount', 500);
        if ($data['amount'] < $min_amount) {
            $errors['amount'] = 'Le montant minimum est de ' . number_format($min_amount, 0, ',', ' ') . ' FCFA';
        }
        
        return $errors;
    }
    
    /**
     * Générer l'URL de paiement Wave
     */
    private function generate_wave_payment_url($donation) {
        $wave_merchant_id = get_option('wdl_wave_merchant_id', '');
        
        if (empty($wave_merchant_id)) {
            return new WP_Error('no_merchant_id', 'ID marchand Wave non configuré');
        }
        
        // Nettoyer l'ID marchand (enlever les espaces, etc.)
        $wave_merchant_id = trim($wave_merchant_id);
        
        // URL de base Wave SN avec votre ID marchand spécifique
        $base_url = 'https://pay.wave.com/m/' . $wave_merchant_id;
        
        // Paramètres pour Wave
        $params = array(
            'amount' => $donation->amount,
            'currency' => $donation->currency,
            'reference' => $donation->donation_id,
            'customer_name' => $donation->donor_name,
            'customer_email' => $donation->donor_email
        );
        
        // Ajouter les URLs de retour si configurées
        $success_url = home_url('/donation-success/?donation_id=' . $donation->donation_id);
        $cancel_url = home_url('/donation-failed/?donation_id=' . $donation->donation_id);
        
        if (!empty($success_url)) {
            $params['success_url'] = $success_url;
        }
        
        if (!empty($cancel_url)) {
            $params['cancel_url'] = $cancel_url;
        }
        
        // Construire l'URL finale
        $wave_url = $base_url . '?' . http_build_query($params);
        
        // Log pour debug (optionnel - à supprimer en production)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('=== DEBUG WAVE URL ===');
            error_log('ID Marchand configuré: ' . $wave_merchant_id);
            error_log('URL générée: ' . $wave_url);
            error_log('Donation ID: ' . $donation->donation_id);
            error_log('Montant: ' . $donation->amount);
            error_log('======================');
        }
        
        return $wave_url;
    }
    
    /**
     * Gérer les retours de Wave
     */
    public function handle_wave_return() {
        // Vérifier si on est sur une page de retour
        if (!is_page()) {
            return;
        }
        
        global $post;
        
        // Page de succès
        if ($post->post_name === 'donation-success' && isset($_GET['donation_id'])) {
            $this->handle_success_return($_GET['donation_id']);
        }
        
        // Page d'échec
        if ($post->post_name === 'donation-failed' && isset($_GET['donation_id'])) {
            $this->handle_failed_return($_GET['donation_id']);
        }
    }
    
    /**
     * Gérer le retour de succès
     */
    private function handle_success_return($donation_id) {
        $donation = WDL_Database::get_donation_by_id($donation_id);
        
        if ($donation && $donation->status === 'pending') {
            // Mettre à jour le statut
            WDL_Database::update_donation($donation_id, array(
                'status' => 'completed',
                'transaction_id' => isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : null
            ));
            
            // Hook pour les actions après confirmation
            do_action('wdl_donation_completed', $donation_id, $donation);
        }
    }
    
    /**
     * Gérer le retour d'échec
     */
    private function handle_failed_return($donation_id) {
        $donation = WDL_Database::get_donation_by_id($donation_id);
        
        if ($donation && $donation->status === 'pending') {
            // Mettre à jour le statut
            WDL_Database::update_donation($donation_id, array(
                'status' => 'failed'
            ));
            
            // Hook pour les actions après échec
            do_action('wdl_donation_failed', $donation_id, $donation);
        }
    }
    
    /**
     * Afficher la page de succès
     */
    public function display_success_page($atts = array()) {
        $donation_id = isset($_GET['donation_id']) ? sanitize_text_field($_GET['donation_id']) : '';
        $donation = null;
        
        if ($donation_id) {
            $donation = WDL_Database::get_donation_by_id($donation_id);
        }
        
        ob_start();
        
        $template_path = WDL_PLUGIN_PATH . 'templates/success.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo $this->get_default_success_html($donation);
        }
        
        return ob_get_clean();
    }
    
    /**
     * HTML par défaut de la page de succès
     */
    private function get_default_success_html($donation) {
        $success_message = get_option('wdl_success_message', 'Merci pour votre généreux don !');
        
        $html = '<div class="wdl-success-container">';
        $html .= '<div class="wdl-success-icon">✅</div>';
        $html .= '<h2 class="wdl-success-title">Don confirmé !</h2>';
        $html .= '<p class="wdl-success-message">' . esc_html($success_message) . '</p>';
        
        if ($donation) {
            $html .= '<div class="wdl-donation-details">';
            $html .= '<h3>Détails de votre don :</h3>';
            $html .= '<p><strong>Montant :</strong> ' . number_format($donation->amount, 0, ',', ' ') . ' ' . $donation->currency . '</p>';
            $html .= '<p><strong>Référence :</strong> ' . esc_html($donation->donation_id) . '</p>';
            $html .= '<p><strong>Date :</strong> ' . date_i18n('d/m/Y à H:i', strtotime($donation->created_at)) . '</p>';
            $html .= '</div>';
        }
        
        $html .= '<div class="wdl-success-actions">';
        $html .= '<a href="' . home_url() . '" class="wdl-btn wdl-btn-primary">Retour à l\'accueil</a>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
    
    /**
     * Afficher la page d'échec
     */
    public function display_failed_page($atts = array()) {
        $donation_id = isset($_GET['donation_id']) ? sanitize_text_field($_GET['donation_id']) : '';
        $donation = null;
        
        if ($donation_id) {
            $donation = WDL_Database::get_donation_by_id($donation_id);
        }
        
        ob_start();
        
        $template_path = WDL_PLUGIN_PATH . 'templates/failed.php';
        if (file_exists($template_path)) {
            include $template_path;
        } else {
            echo $this->get_default_failed_html($donation);
        }
        
        return ob_get_clean();
    }
    
    /**
     * HTML par défaut de la page d'échec
     */
    private function get_default_failed_html($donation) {
        $failed_message = get_option('wdl_failed_message', 'Désolé, votre paiement n\'a pas pu être traité.');
        
        $html = '<div class="wdl-failed-container">';
        $html .= '<div class="wdl-failed-icon">❌</div>';
        $html .= '<h2 class="wdl-failed-title">Paiement échoué</h2>';
        $html .= '<p class="wdl-failed-message">' . esc_html($failed_message) . '</p>';
        
        if ($donation) {
            $html .= '<div class="wdl-donation-details">';
            $html .= '<p><strong>Référence :</strong> ' . esc_html($donation->donation_id) . '</p>';
            $html .= '<p><strong>Montant :</strong> ' . number_format($donation->amount, 0, ',', ' ') . ' ' . $donation->currency . '</p>';
            $html .= '</div>';
        }
        
        $html .= '<div class="wdl-failed-actions">';
        $html .= '<a href="javascript:history.back()" class="wdl-btn wdl-btn-secondary">Réessayer</a>';
        $html .= '<a href="' . home_url() . '" class="wdl-btn wdl-btn-primary">Retour à l\'accueil</a>';
        $html .= '</div>';
        
        $html .= '</div>';
        
        return $html;
    }
}