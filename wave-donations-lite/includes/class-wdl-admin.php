<?php
/**
 * Classe d'administration Wave Donations Lite
 * Gère l'interface d'administration dans le back-office WordPress
 */

// Sécurité - Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class WDL_Admin {
    
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
        // Ajouter les menus d'administration
        add_action('admin_menu', array($this, 'add_admin_menus'));
        
        // Enregistrer les paramètres
        add_action('admin_init', array($this, 'register_settings'));
        
        // Ajouter des liens d'action sur la page des plugins
        add_filter('plugin_action_links_' . WDL_PLUGIN_BASENAME, array($this, 'add_plugin_action_links'));
        
        // Gérer les actions AJAX admin
        add_action('wp_ajax_wdl_export_donations', array($this, 'export_donations'));
        add_action('wp_ajax_wdl_delete_donation', array($this, 'delete_donation'));
        add_action('wp_ajax_wdl_get_donation_details', array($this, 'get_donation_details'));
        add_action('wp_ajax_wdl_update_donation_status', array($this, 'update_donation_status'));
    }
    
    /**
     * Ajouter les menus d'administration
     */
    public function add_admin_menus() {
        // Menu principal
        add_menu_page(
            'Wave Donations',
            'Donations',
            'manage_options',
            'wave-donations',
            array($this, 'dashboard_page'),
            'dashicons-heart',
            30
        );
        
        // Sous-menu Dashboard
        add_submenu_page(
            'wave-donations',
            'Tableau de bord',
            'Tableau de bord',
            'manage_options',
            'wave-donations',
            array($this, 'dashboard_page')
        );
        
        // Sous-menu Liste des donations
        add_submenu_page(
            'wave-donations',
            'Toutes les donations',
            'Toutes les donations',
            'manage_options',
            'wave-donations-list',
            array($this, 'donations_list_page')
        );
        
        // Sous-menu Paramètres
        add_submenu_page(
            'wave-donations',
            'Paramètres Wave',
            'Paramètres',
            'manage_options',
            'wave-donations-settings',
            array($this, 'settings_page')
        );
    }
    
    /**
     * Enregistrer les paramètres
     */
    public function register_settings() {
        // Groupe de paramètres Wave
        register_setting('wdl_wave_settings', 'wdl_wave_merchant_id');
        register_setting('wdl_wave_settings', 'wdl_wave_api_key');
        register_setting('wdl_wave_settings', 'wdl_currency');
        register_setting('wdl_wave_settings', 'wdl_default_amounts');
        register_setting('wdl_wave_settings', 'wdl_min_amount');
        register_setting('wdl_wave_settings', 'wdl_success_message');
        register_setting('wdl_wave_settings', 'wdl_failed_message');
    }
    
    /**
     * Ajouter des liens d'action sur la page des plugins
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=wave-donations-settings') . '">Paramètres</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
    
    /**
     * Page du tableau de bord
     */
    public function dashboard_page() {
        // Récupérer les statistiques
        $stats = WDL_Database::get_donation_stats();
        $recent_donations = WDL_Database::get_donations(array('limit' => 5));
        
        ?>
        <div class="wrap">
            <h1>Tableau de bord - Wave Donations</h1>
            
            <div class="wdl-dashboard-stats">
                <div class="wdl-stat-box">
                    <div class="wdl-stat-icon">💰</div>
                    <div class="wdl-stat-content">
                        <h3><?php echo number_format($stats->total_raised, 0, ',', ' '); ?> FCFA</h3>
                        <p>Total collecté</p>
                    </div>
                </div>
                
                <div class="wdl-stat-box">
                    <div class="wdl-stat-icon">📊</div>
                    <div class="wdl-stat-content">
                        <h3><?php echo $stats->completed_donations; ?></h3>
                        <p>Donations confirmées</p>
                    </div>
                </div>
                
                <div class="wdl-stat-box">
                    <div class="wdl-stat-icon">⏳</div>
                    <div class="wdl-stat-content">
                        <h3><?php echo $stats->pending_donations; ?></h3>
                        <p>En attente</p>
                    </div>
                </div>
                
                <div class="wdl-stat-box">
                    <div class="wdl-stat-icon">📈</div>
                    <div class="wdl-stat-content">
                        <h3><?php echo number_format($stats->average_donation, 0, ',', ' '); ?> FCFA</h3>
                        <p>Don moyen</p>
                    </div>
                </div>
            </div>
            
            <div class="wdl-dashboard-content">
                <div class="wdl-recent-donations">
                    <h2>Donations récentes</h2>
                    <?php if (!empty($recent_donations)) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>Donateur</th>
                                    <th>Montant</th>
                                    <th>Statut</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_donations as $donation) : ?>
                                    <tr>
                                        <td><?php echo esc_html($donation->donor_name); ?></td>
                                        <td><?php echo number_format($donation->amount, 0, ',', ' ') . ' ' . $donation->currency; ?></td>
                                        <td>
                                            <span class="wdl-status wdl-status-<?php echo $donation->status; ?>">
                                                <?php echo $this->get_status_label($donation->status); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date_i18n('d/m/Y H:i', strtotime($donation->created_at)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p>
                            <a href="<?php echo admin_url('admin.php?page=wave-donations-list'); ?>" class="button">
                                Voir toutes les donations
                            </a>
                        </p>
                    <?php else : ?>
                        <p>Aucune donation pour le moment.</p>
                        <p>
                            <strong>Pour commencer :</strong><br>
                            1. <a href="<?php echo admin_url('admin.php?page=wave-donations-settings'); ?>">Configurez vos paramètres Wave</a><br>
                            2. Ajoutez le shortcode <code>[wave_donation_form]</code> sur une page
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="wdl-quick-actions">
                    <h2>Actions rapides</h2>
                    <p><a href="<?php echo admin_url('admin.php?page=wave-donations-settings'); ?>" class="button button-primary">⚙️ Paramètres</a></p>
                    <p><a href="<?php echo admin_url('admin.php?page=wave-donations-list'); ?>" class="button">📋 Toutes les donations</a></p>
                    <p><a href="#" onclick="navigator.clipboard.writeText('[wave_donation_form]')" class="button">📋 Copier le shortcode</a></p>
                </div>
            </div>
        </div>
        
        <style>
        .wdl-dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        .wdl-stat-box {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            display: flex;
            align-items: center;
        }
        .wdl-stat-icon {
            font-size: 2em;
            margin-right: 15px;
        }
        .wdl-stat-content h3 {
            margin: 0;
            font-size: 1.5em;
            color: #2271b1;
        }
        .wdl-stat-content p {
            margin: 5px 0 0 0;
            color: #646970;
        }
        .wdl-dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
            margin-top: 20px;
        }
        .wdl-recent-donations, .wdl-quick-actions {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
        }
        .wdl-status {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .wdl-status-completed { background: #d1e7dd; color: #0f5132; }
        .wdl-status-pending { background: #fff3cd; color: #664d03; }
        .wdl-status-failed { background: #f8d7da; color: #721c24; }
        .wdl-status-cancelled { background: #e2e3e5; color: #383d41; }
        </style>
        <?php
    }
    
    /**
     * Page de liste des donations
     */
    public function donations_list_page() {
        // Gérer la pagination
        $items_per_page = 20;
        $current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
        $offset = ($current_page - 1) * $items_per_page;
        
        // Filtrer par statut
        $status_filter = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
        
        // Récupérer les donations
        $args = array(
            'limit' => $items_per_page,
            'offset' => $offset
        );
        
        if (!empty($status_filter)) {
            $args['status'] = $status_filter;
        }
        
        $donations = WDL_Database::get_donations($args);
        $total_items = WDL_Database::count_donations($status_filter);
        $total_pages = ceil($total_items / $items_per_page);
        
        ?>
        <div class="wrap">
            <h1>Toutes les donations</h1>
            
            <!-- Filtres -->
            <div class="tablenav top">
                <div class="alignleft actions">
                    <form method="get">
                        <input type="hidden" name="page" value="wave-donations-list">
                        <select name="status">
                            <option value="">Tous les statuts</option>
                            <option value="completed" <?php selected($status_filter, 'completed'); ?>>Confirmées</option>
                            <option value="pending" <?php selected($status_filter, 'pending'); ?>>En attente</option>
                            <option value="failed" <?php selected($status_filter, 'failed'); ?>>Échouées</option>
                            <option value="cancelled" <?php selected($status_filter, 'cancelled'); ?>>Annulées</option>
                        </select>
                        <input type="submit" class="button" value="Filtrer">
                    </form>
                </div>
                
                <div class="alignright actions">
                    <a href="#" id="wdl-export-btn" class="button">Exporter CSV</a>
                </div>
            </div>
            
            <!-- Tableau des donations -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Donateur</th>
                        <th>Email</th>
                        <th>Montant</th>
                        <th>Statut</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($donations)) : ?>
                        <?php foreach ($donations as $donation) : ?>
                            <tr>
                                <td><?php echo esc_html($donation->donation_id); ?></td>
                                <td><?php echo esc_html($donation->donor_name); ?></td>
                                <td><?php echo esc_html($donation->donor_email); ?></td>
                                <td><?php echo number_format($donation->amount, 0, ',', ' ') . ' ' . $donation->currency; ?></td>
                                <td>
                                    <span class="wdl-status wdl-status-<?php echo $donation->status; ?>">
                                        <?php echo $this->get_status_label($donation->status); ?>
                                    </span>
                                </td>
                                <td><?php echo date_i18n('d/m/Y H:i', strtotime($donation->created_at)); ?></td>
                                <td>
                                    <button class="button button-small wdl-view-details" 
                                            data-donation-id="<?php echo $donation->donation_id; ?>">
                                        Détails
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="7">Aucune donation trouvée.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1) : ?>
                <div class="tablenav bottom">
                    <div class="tablenav-pages">
                        <?php
                        $page_links = paginate_links(array(
                            'base' => add_query_arg('paged', '%#%'),
                            'format' => '',
                            'prev_text' => '&laquo;',
                            'next_text' => '&raquo;',
                            'total' => $total_pages,
                            'current' => $current_page
                        ));
                        
                        if ($page_links) {
                            echo '<span class="pagination-links">' . $page_links . '</span>';
                        }
                        ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <script>
        jQuery(document).ready(function($) {
            // Export CSV
            $('#wdl-export-btn').click(function(e) {
                e.preventDefault();
                window.location.href = ajaxurl + '?action=wdl_export_donations&_wpnonce=' + '<?php echo wp_create_nonce('wdl_export'); ?>';
            });
            
            // Voir les détails d'une donation
            $('.wdl-view-details').click(function(e) {
                e.preventDefault();
                var donationId = $(this).data('donation-id');
                
                // Créer la modal
                var modalHtml = `
                    <div id="wdl-details-modal" style="display: none;">
                        <div class="wdl-modal-overlay">
                            <div class="wdl-modal-content">
                                <div class="wdl-modal-header">
                                    <h2>Détails de la donation</h2>
                                    <button class="wdl-modal-close">&times;</button>
                                </div>
                                <div class="wdl-modal-body">
                                    <div class="wdl-loading-details">Chargement des détails...</div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                // Ajouter la modal au DOM si elle n'existe pas
                if ($('#wdl-details-modal').length === 0) {
                    $('body').append(modalHtml);
                }
                
                // Afficher la modal
                $('#wdl-details-modal').show();
                
                // Charger les détails via AJAX
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'wdl_get_donation_details',
                        donation_id: donationId,
                        _wpnonce: '<?php echo wp_create_nonce('wdl_details'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.wdl-modal-body').html(response.data.html);
                        } else {
                            $('.wdl-modal-body').html('<p>Erreur lors du chargement des détails.</p>');
                        }
                    },
                    error: function() {
                        $('.wdl-modal-body').html('<p>Erreur de connexion.</p>');
                    }
                });
            });
            
            // Fermer la modal
            $(document).on('click', '.wdl-modal-close, .wdl-modal-overlay', function(e) {
                if (e.target === this) {
                    $('#wdl-details-modal').hide();
                }
            });
            
            // Fermer avec Escape
            $(document).keyup(function(e) {
                if (e.keyCode === 27) {
                    $('#wdl-details-modal').hide();
                }
            });
        });
        </script>
        
        <style>
        .wdl-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 100000;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .wdl-modal-content {
            background: white;
            border-radius: 8px;
            max-width: 600px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        
        .wdl-modal-header {
            padding: 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            border-radius: 8px 8px 0 0;
        }
        
        .wdl-modal-header h2 {
            margin: 0;
            color: #2c3e50;
        }
        
        .wdl-modal-close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #999;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .wdl-modal-close:hover {
            color: #333;
        }
        
        .wdl-modal-body {
            padding: 20px;
        }
        
        .wdl-loading-details {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .wdl-detail-row {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .wdl-detail-label {
            font-weight: bold;
            min-width: 140px;
            color: #2c3e50;
        }
        
        .wdl-detail-value {
            flex: 1;
            color: #555;
        }
        
        .wdl-status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .wdl-status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .wdl-status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .wdl-status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .wdl-status-cancelled {
            background: #e2e3e5;
            color: #383d41;
        }
        </style>
        <?php
    }
    
    /**
     * Page des paramètres
     */
    public function settings_page() {
        if (isset($_POST['submit'])) {
            update_option('wdl_wave_merchant_id', sanitize_text_field($_POST['wdl_wave_merchant_id']));
            update_option('wdl_wave_api_key', sanitize_text_field($_POST['wdl_wave_api_key']));
            update_option('wdl_currency', sanitize_text_field($_POST['wdl_currency']));
            update_option('wdl_default_amounts', sanitize_text_field($_POST['wdl_default_amounts']));
            update_option('wdl_min_amount', intval($_POST['wdl_min_amount']));
            update_option('wdl_success_message', sanitize_textarea_field($_POST['wdl_success_message']));
            update_option('wdl_failed_message', sanitize_textarea_field($_POST['wdl_failed_message']));
            
            echo '<div class="notice notice-success"><p>Paramètres sauvegardés !</p></div>';
        }
        
        ?>
        <div class="wrap">
            <h1>Paramètres Wave Donations</h1>
            
            <form method="post" action="">
                <?php wp_nonce_field('wdl_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">ID Marchand Wave</th>
                        <td>
                            <input type="text" name="wdl_wave_merchant_id" 
                                   value="<?php echo esc_attr(get_option('wdl_wave_merchant_id')); ?>" 
                                   class="regular-text" required>
                            <p class="description">Votre identifiant marchand Wave SN (ex: M_sn_qqv-YCHOudUu)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Clé API Wave</th>
                        <td>
                            <input type="password" name="wdl_wave_api_key" 
                                   value="<?php echo esc_attr(get_option('wdl_wave_api_key')); ?>" 
                                   class="regular-text">
                            <p class="description">Clé API Wave (optionnel pour webhook)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Devise</th>
                        <td>
                            <select name="wdl_currency">
                                <option value="XOF" <?php selected(get_option('wdl_currency'), 'XOF'); ?>>FCFA (XOF)</option>
                                <option value="EUR" <?php selected(get_option('wdl_currency'), 'EUR'); ?>>Euro (EUR)</option>
                                <option value="USD" <?php selected(get_option('wdl_currency'), 'USD'); ?>>Dollar (USD)</option>
                            </select>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Montants prédéfinis</th>
                        <td>
                            <input type="text" name="wdl_default_amounts" 
                                   value="<?php echo esc_attr(get_option('wdl_default_amounts')); ?>" 
                                   class="regular-text">
                            <p class="description">Montants séparés par des virgules (ex: 1000,5000,10000)</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Montant minimum</th>
                        <td>
                            <input type="number" name="wdl_min_amount" 
                                   value="<?php echo esc_attr(get_option('wdl_min_amount')); ?>" 
                                   class="small-text" min="1">
                            <p class="description">Montant minimum accepté pour une donation</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Message de succès</th>
                        <td>
                            <textarea name="wdl_success_message" rows="3" cols="50"><?php echo esc_textarea(get_option('wdl_success_message')); ?></textarea>
                            <p class="description">Message affiché après une donation réussie</p>
                        </td>
                    </tr>
                    
                    <tr>
                        <th scope="row">Message d'échec</th>
                        <td>
                            <textarea name="wdl_failed_message" rows="3" cols="50"><?php echo esc_textarea(get_option('wdl_failed_message')); ?></textarea>
                            <p class="description">Message affiché après un échec de paiement</p>
                        </td>
                    </tr>
                </table>
                
                <?php submit_button('Sauvegarder les paramètres'); ?>
            </form>
            
            <hr>
            
            <h2>Utilisation</h2>
            <p>Pour afficher le formulaire de donation, utilisez le shortcode suivant :</p>
            <p><code>[wave_donation_form]</code></p>
            
            <p>Vous pouvez aussi personnaliser l'affichage :</p>
            <ul>
                <li><code>[wave_donation_form title="Soutenez notre cause"]</code></li>
                <li><code>[wave_donation_form show_amounts="false"]</code> - masquer les montants prédéfinis</li>
                <li><code>[wave_donation_form show_message="false"]</code> - masquer le champ message</li>
            </ul>
            
            <h2>URLs de retour Wave</h2>
            <p>Configurez ces URLs dans votre compte Wave :</p>
            <ul>
                <li><strong>URL de succès :</strong> <code><?php echo home_url('/donation-success/'); ?></code></li>
                <li><strong>URL d'échec :</strong> <code><?php echo home_url('/donation-failed/'); ?></code></li>
            </ul>
        </div>
        <?php
    }
    
    /**
     * Obtenir le libellé d'un statut
     */
    private function get_status_label($status) {
        $labels = array(
            'pending' => 'En attente',
            'completed' => 'Confirmée',
            'failed' => 'Échouée',
            'cancelled' => 'Annulée'
        );
        
        return isset($labels[$status]) ? $labels[$status] : $status;
    }
    
    /**
     * Exporter les donations en CSV
     */
    public function export_donations() {
        // Vérifier les permissions et le nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_GET['_wpnonce'], 'wdl_export')) {
            wp_die('Accès non autorisé');
        }
        
        // Récupérer toutes les donations
        $donations = WDL_Database::get_donations(array('limit' => 999999));
        
        // Préparer le fichier CSV
        $filename = 'donations_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // BOM pour UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // En-têtes CSV
        fputcsv($output, array(
            'ID Donation',
            'Nom',
            'Email',
            'Téléphone',
            'Montant',
            'Devise',
            'Statut',
            'ID Transaction',
            'Message',
            'Date création',
            'Date mise à jour'
        ), ';');
        
        // Données
        foreach ($donations as $donation) {
            fputcsv($output, array(
                $donation->donation_id,
                $donation->donor_name,
                $donation->donor_email,
                $donation->donor_phone,
                $donation->amount,
                $donation->currency,
                $this->get_status_label($donation->status),
                $donation->transaction_id,
                $donation->donor_message,
                $donation->created_at,
                $donation->updated_at
            ), ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Récupérer les détails d'une donation (AJAX)
     */
    public function get_donation_details() {
        // Vérifier les permissions et le nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'], 'wdl_details')) {
            wp_send_json_error('Accès non autorisé');
        }
        
        $donation_id = sanitize_text_field($_POST['donation_id']);
        $donation = WDL_Database::get_donation_by_id($donation_id);
        
        if (!$donation) {
            wp_send_json_error('Donation non trouvée');
        }
        
        // Générer le HTML des détails
        $html = '<div class="wdl-donation-details">';
        
        // Informations principales
        $html .= '<div class="wdl-detail-row">';
        $html .= '<div class="wdl-detail-label">ID Donation :</div>';
        $html .= '<div class="wdl-detail-value"><code>' . esc_html($donation->donation_id) . '</code></div>';
        $html .= '</div>';
        
        $html .= '<div class="wdl-detail-row">';
        $html .= '<div class="wdl-detail-label">Donateur :</div>';
        $html .= '<div class="wdl-detail-value">' . esc_html($donation->donor_name) . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="wdl-detail-row">';
        $html .= '<div class="wdl-detail-label">Email :</div>';
        $html .= '<div class="wdl-detail-value"><a href="mailto:' . esc_attr($donation->donor_email) . '">' . esc_html($donation->donor_email) . '</a></div>';
        $html .= '</div>';
        
        if (!empty($donation->donor_phone)) {
            $html .= '<div class="wdl-detail-row">';
            $html .= '<div class="wdl-detail-label">Téléphone :</div>';
            $html .= '<div class="wdl-detail-value"><a href="tel:' . esc_attr($donation->donor_phone) . '">' . esc_html($donation->donor_phone) . '</a></div>';
            $html .= '</div>';
        }
        
        $html .= '<div class="wdl-detail-row">';
        $html .= '<div class="wdl-detail-label">Montant :</div>';
        $html .= '<div class="wdl-detail-value"><strong>' . number_format($donation->amount, 0, ',', ' ') . ' ' . $donation->currency . '</strong></div>';
        $html .= '</div>';
        
        $html .= '<div class="wdl-detail-row">';
        $html .= '<div class="wdl-detail-label">Statut :</div>';
        $html .= '<div class="wdl-detail-value"><span class="wdl-status-badge wdl-status-' . $donation->status . '">' . $this->get_status_label($donation->status) . '</span></div>';
        $html .= '</div>';
        
        $html .= '<div class="wdl-detail-row">';
        $html .= '<div class="wdl-detail-label">Mode de paiement :</div>';
        $html .= '<div class="wdl-detail-value">' . ucfirst($donation->payment_method) . '</div>';
        $html .= '</div>';
        
        if (!empty($donation->transaction_id)) {
            $html .= '<div class="wdl-detail-row">';
            $html .= '<div class="wdl-detail-label">ID Transaction :</div>';
            $html .= '<div class="wdl-detail-value"><code>' . esc_html($donation->transaction_id) . '</code></div>';
            $html .= '</div>';
        }
        
        $html .= '<div class="wdl-detail-row">';
        $html .= '<div class="wdl-detail-label">Date de création :</div>';
        $html .= '<div class="wdl-detail-value">' . date_i18n('d/m/Y à H:i:s', strtotime($donation->created_at)) . '</div>';
        $html .= '</div>';
        
        $html .= '<div class="wdl-detail-row">';
        $html .= '<div class="wdl-detail-label">Dernière MAJ :</div>';
        $html .= '<div class="wdl-detail-value">' . date_i18n('d/m/Y à H:i:s', strtotime($donation->updated_at)) . '</div>';
        $html .= '</div>';
        
        if (!empty($donation->donor_message)) {
            $html .= '<div class="wdl-detail-row">';
            $html .= '<div class="wdl-detail-label">Message :</div>';
            $html .= '<div class="wdl-detail-value" style="font-style: italic; padding: 10px; background: #f8f9fa; border-radius: 4px;">';
            $html .= '"' . esc_html($donation->donor_message) . '"';
            $html .= '</div>';
            $html .= '</div>';
        }
        
        if (!empty($donation->wave_payment_url)) {
            $html .= '<div class="wdl-detail-row">';
            $html .= '<div class="wdl-detail-label">Lien Wave :</div>';
            $html .= '<div class="wdl-detail-value"><a href="' . esc_url($donation->wave_payment_url) . '" target="_blank">Voir le lien de paiement</a></div>';
            $html .= '</div>';
        }
        
        // Actions disponibles
        $html .= '<div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #eee;">';
        $html .= '<h4>Actions disponibles :</h4>';
        
        if ($donation->status === 'pending') {
            $html .= '<button class="button button-primary" onclick="wdlUpdateStatus(\'' . $donation->donation_id . '\', \'completed\')">Marquer comme confirmé</button> ';
            $html .= '<button class="button" onclick="wdlUpdateStatus(\'' . $donation->donation_id . '\', \'failed\')">Marquer comme échoué</button> ';
        }
        
        $html .= '<button class="button" onclick="wdlSendEmail(\'' . $donation->donor_email . '\', \'' . $donation->donor_name . '\')">Envoyer un email</button> ';
        $html .= '<button class="button button-link-delete" onclick="wdlDeleteDonation(\'' . $donation->donation_id . '\')">Supprimer</button>';
        
        $html .= '</div>';
        
        $html .= '</div>';
        
        // Ajouter le JavaScript pour les actions
        $html .= '<script>
        function wdlUpdateStatus(donationId, newStatus) {
            if (confirm("Êtes-vous sûr de vouloir changer le statut ?")) {
                jQuery.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "wdl_update_donation_status",
                        donation_id: donationId,
                        new_status: newStatus,
                        _wpnonce: "' . wp_create_nonce('wdl_update_status') . '"
                    },
                    success: function(response) {
                        if (response.success) {
                            alert("Statut mis à jour !");
                            location.reload();
                        } else {
                            alert("Erreur : " + response.data);
                        }
                    }
                });
            }
        }
        
        function wdlSendEmail(email, name) {
            window.location.href = "mailto:" + email + "?subject=Concernant votre don&body=Bonjour " + name + ",";
        }
        
        function wdlDeleteDonation(donationId) {
            if (confirm("Êtes-vous sûr de vouloir supprimer cette donation ? Cette action est irréversible.")) {
                jQuery.ajax({
                    url: ajaxurl,
                    type: "POST",
                    data: {
                        action: "wdl_delete_donation",
                        donation_id: donationId,
                        _wpnonce: "' . wp_create_nonce('wdl_delete') . '"
                    },
                    success: function(response) {
                        if (response.success) {
                            alert("Donation supprimée !");
                            location.reload();
                        } else {
                            alert("Erreur : " + response.data);
                        }
                    }
                });
            }
        }
        </script>';
        
        wp_send_json_success(array('html' => $html));
    }
    
    /**
     * Mettre à jour le statut d'une donation (AJAX)
     */
    public function update_donation_status() {
        // Vérifier les permissions et le nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'], 'wdl_update_status')) {
            wp_send_json_error('Accès non autorisé');
        }
        
        $donation_id = sanitize_text_field($_POST['donation_id']);
        $new_status = sanitize_text_field($_POST['new_status']);
        
        // Valider le statut
        $valid_statuses = array('pending', 'completed', 'failed', 'cancelled');
        if (!in_array($new_status, $valid_statuses)) {
            wp_send_json_error('Statut invalide');
        }
        
        $result = WDL_Database::update_donation($donation_id, array(
            'status' => $new_status
        ));
        
        if ($result) {
            wp_send_json_success('Statut mis à jour avec succès');
        } else {
            wp_send_json_error('Erreur lors de la mise à jour');
        }
    }
    
    /**
     * Supprimer une donation (AJAX)
     */
    public function delete_donation() {
        // Vérifier les permissions et le nonce
        if (!current_user_can('manage_options') || !wp_verify_nonce($_POST['_wpnonce'], 'wdl_delete')) {
            wp_send_json_error('Accès non autorisé');
        }
        
        $donation_id = sanitize_text_field($_POST['donation_id']);
        
        global $wpdb;
        $table_name = WDL_Database::get_donations_table();
        
        $result = $wpdb->delete(
            $table_name,
            array('donation_id' => $donation_id),
            array('%s')
        );
        
        if ($result !== false) {
            wp_send_json_success('Donation supprimée avec succès');
        } else {
            wp_send_json_error('Erreur lors de la suppression');
        }
    }
}