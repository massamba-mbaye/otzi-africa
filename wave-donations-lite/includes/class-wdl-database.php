<?php
/**
 * Classe de gestion de la base de données
 * Gère la création et les opérations sur la table des donations
 */

// Sécurité - Empêcher l'accès direct
if (!defined('ABSPATH')) {
    exit;
}

class WDL_Database {
    
    /**
     * Version de la base de données
     */
    const DB_VERSION = '1.0.0';
    
    /**
     * Nom de la table des donations
     */
    public static function get_donations_table() {
        global $wpdb;
        return $wpdb->prefix . 'wdl_donations';
    }
    
    /**
     * Créer les tables nécessaires
     */
    public static function create_tables() {
        global $wpdb;
        
        $table_name = self::get_donations_table();
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            donation_id varchar(50) NOT NULL,
            donor_name varchar(255) NOT NULL,
            donor_email varchar(255) NOT NULL,
            donor_phone varchar(20) DEFAULT NULL,
            amount decimal(10,2) NOT NULL,
            currency varchar(3) DEFAULT 'XOF',
            status enum('pending','completed','failed','cancelled') DEFAULT 'pending',
            payment_method varchar(50) DEFAULT 'wave',
            transaction_id varchar(255) DEFAULT NULL,
            wave_payment_url text DEFAULT NULL,
            donor_message text DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY donation_id (donation_id),
            INDEX status_idx (status),
            INDEX email_idx (donor_email),
            INDEX created_idx (created_at)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
        
        // Sauvegarder la version de la base de données
        update_option('wdl_db_version', self::DB_VERSION);
    }
    
    /**
     * Insérer une nouvelle donation
     */
    public static function insert_donation($data) {
        global $wpdb;
        
        // Données par défaut
        $defaults = array(
            'donation_id' => self::generate_donation_id(),
            'currency' => 'XOF',
            'status' => 'pending',
            'payment_method' => 'wave',
            'created_at' => current_time('mysql')
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Valider les données obligatoires
        $required_fields = array('donor_name', 'donor_email', 'amount');
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                return new WP_Error('missing_field', "Le champ $field est obligatoire");
            }
        }
        
        // Nettoyer et valider les données
        $clean_data = array(
            'donation_id' => sanitize_text_field($data['donation_id']),
            'donor_name' => sanitize_text_field($data['donor_name']),
            'donor_email' => sanitize_email($data['donor_email']),
            'donor_phone' => !empty($data['donor_phone']) ? sanitize_text_field($data['donor_phone']) : null,
            'amount' => floatval($data['amount']),
            'currency' => sanitize_text_field($data['currency']),
            'status' => sanitize_text_field($data['status']),
            'payment_method' => sanitize_text_field($data['payment_method']),
            'transaction_id' => !empty($data['transaction_id']) ? sanitize_text_field($data['transaction_id']) : null,
            'wave_payment_url' => !empty($data['wave_payment_url']) ? esc_url_raw($data['wave_payment_url']) : null,
            'donor_message' => !empty($data['donor_message']) ? sanitize_textarea_field($data['donor_message']) : null
        );
        
        // Valider l'email
        if (!is_email($clean_data['donor_email'])) {
            return new WP_Error('invalid_email', 'Adresse email invalide');
        }
        
        // Valider le montant
        if ($clean_data['amount'] <= 0) {
            return new WP_Error('invalid_amount', 'Le montant doit être supérieur à 0');
        }
        
        $table_name = self::get_donations_table();
        $result = $wpdb->insert($table_name, $clean_data);
        
        if ($result === false) {
            return new WP_Error('db_error', 'Erreur lors de l\'insertion en base de données');
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Mettre à jour une donation
     */
    public static function update_donation($donation_id, $data) {
        global $wpdb;
        
        $table_name = self::get_donations_table();
        
        // Nettoyer les données
        $clean_data = array();
        
        if (isset($data['status'])) {
            $clean_data['status'] = sanitize_text_field($data['status']);
        }
        
        if (isset($data['transaction_id'])) {
            $clean_data['transaction_id'] = sanitize_text_field($data['transaction_id']);
        }
        
        if (isset($data['wave_payment_url'])) {
            $clean_data['wave_payment_url'] = esc_url_raw($data['wave_payment_url']);
        }
        
        if (empty($clean_data)) {
            return new WP_Error('no_data', 'Aucune donnée à mettre à jour');
        }
        
        $result = $wpdb->update(
            $table_name,
            $clean_data,
            array('donation_id' => $donation_id),
            null,
            array('%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Récupérer une donation par ID
     */
    public static function get_donation_by_id($donation_id) {
        global $wpdb;
        
        $table_name = self::get_donations_table();
        
        $donation = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $table_name WHERE donation_id = %s",
                $donation_id
            )
        );
        
        return $donation;
    }
    
    /**
     * Récupérer toutes les donations avec pagination
     */
    public static function get_donations($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'limit' => 20,
            'offset' => 0,
            'status' => '',
            'order_by' => 'created_at',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $table_name = self::get_donations_table();
        $where_clauses = array('1=1');
        $where_values = array();
        
        // Filtrer par statut
        if (!empty($args['status'])) {
            $where_clauses[] = 'status = %s';
            $where_values[] = $args['status'];
        }
        
        // Construire la requête
        $where_sql = implode(' AND ', $where_clauses);
        $order_by = sanitize_sql_orderby($args['order_by'] . ' ' . $args['order']);
        
        $sql = "SELECT * FROM $table_name WHERE $where_sql";
        
        if ($order_by) {
            $sql .= " ORDER BY $order_by";
        }
        
        $sql .= " LIMIT %d OFFSET %d";
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];
        
        if (!empty($where_values)) {
            $sql = $wpdb->prepare($sql, $where_values);
        }
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Compter le nombre total de donations
     */
    public static function count_donations($status = '') {
        global $wpdb;
        
        $table_name = self::get_donations_table();
        
        if (empty($status)) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        } else {
            $count = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name WHERE status = %s",
                    $status
                )
            );
        }
        
        return intval($count);
    }
    
    /**
     * Obtenir les statistiques des donations
     */
    public static function get_donation_stats() {
        global $wpdb;
        
        $table_name = self::get_donations_table();
        
        $stats = $wpdb->get_row("
            SELECT 
                COUNT(*) as total_donations,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END), 0) as total_raised,
                COALESCE(AVG(CASE WHEN status = 'completed' THEN amount ELSE NULL END), 0) as average_donation,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_donations,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_donations,
                COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed_donations
            FROM $table_name
        ");
        
        return $stats;
    }
    
    /**
     * Générer un ID unique pour la donation
     */
    private static function generate_donation_id() {
        return 'DON_' . strtoupper(uniqid()) . '_' . time();
    }
    
    /**
     * Supprimer les anciennes données (nettoyage)
     */
    public static function cleanup_old_data($days = 365) {
        global $wpdb;
        
        $table_name = self::get_donations_table();
        
        $result = $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name 
                 WHERE status IN ('failed', 'cancelled') 
                 AND created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
                $days
            )
        );
        
        return $result;
    }
}