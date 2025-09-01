<?php
/**
 * Template du formulaire de donation
 * Variables disponibles : $atts
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

// Récupérer les paramètres
$default_amounts = explode(',', get_option('wdl_default_amounts', '1000,5000,10000,25000,50000'));
$currency = get_option('wdl_currency', 'XOF');
$min_amount = get_option('wdl_min_amount', 500);
?>

<div class="wdl-donation-form-container">
    <?php if (!empty($atts['title'])) : ?>
        <h3 class="wdl-form-title"><?php echo esc_html($atts['title']); ?></h3>
    <?php endif; ?>
    
    <form id="wdl-donation-form" class="wdl-donation-form">
        <?php wp_nonce_field('wdl_donation_nonce', 'wdl_nonce'); ?>
        
        <?php if ($atts['show_amounts'] === 'true') : ?>
            <div class="wdl-amount-section">
                <label class="wdl-label">Choisissez un montant :</label>
                <div class="wdl-amount-buttons">
                    <?php foreach ($default_amounts as $amount) : 
                        $amount = trim($amount);
                        if (!empty($amount)) : ?>
                            <button type="button" class="wdl-amount-btn" data-amount="<?php echo esc_attr($amount); ?>">
                                <?php echo number_format($amount, 0, ',', ' ') . ' ' . $currency; ?>
                            </button>
                        <?php endif;
                    endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="wdl-custom-amount-section">
            <label for="wdl_amount" class="wdl-label">Montant personnalisé (<?php echo $currency; ?>) :</label>
            <input type="number" 
                   id="wdl_amount" 
                   name="amount" 
                   min="<?php echo $min_amount; ?>" 
                   step="1" 
                   required 
                   class="wdl-input"
                   placeholder="Saisissez votre montant">
            <small class="wdl-help-text">
                Montant minimum : <?php echo number_format($min_amount, 0, ',', ' ') . ' ' . $currency; ?>
            </small>
        </div>
        
        <div class="wdl-donor-section">
            <h4 class="wdl-section-title">Vos informations</h4>
            
            <div class="wdl-field-group">
                <label for="wdl_donor_name" class="wdl-label">Nom complet *</label>
                <input type="text" 
                       id="wdl_donor_name" 
                       name="donor_name" 
                       required 
                       class="wdl-input"
                       placeholder="Votre nom et prénom">
            </div>
            
            <div class="wdl-field-group">
                <label for="wdl_donor_email" class="wdl-label">Adresse email *</label>
                <input type="email" 
                       id="wdl_donor_email" 
                       name="donor_email" 
                       required 
                       class="wdl-input"
                       placeholder="votre@email.com">
            </div>
            
            <div class="wdl-field-group">
                <label for="wdl_donor_phone" class="wdl-label">Téléphone</label>
                <input type="tel" 
                       id="wdl_donor_phone" 
                       name="donor_phone" 
                       class="wdl-input"
                       placeholder="+221 77 123 45 67">
                <small class="wdl-help-text">Optionnel - Format international recommandé</small>
            </div>
        </div>
        
        <?php if ($atts['show_message'] === 'true') : ?>
            <div class="wdl-message-section">
                <label for="wdl_donor_message" class="wdl-label">Message (optionnel)</label>
                <textarea id="wdl_donor_message" 
                          name="donor_message" 
                          rows="3" 
                          class="wdl-textarea"
                          placeholder="Laissez un message personnel..."></textarea>
            </div>
        <?php endif; ?>
        
        <div class="wdl-submit-section">
            <button type="submit" class="wdl-submit-btn">
                💝 Faire un don
            </button>
            <div class="wdl-loading" style="display: none;">
                Préparation du paiement...
            </div>
        </div>
    </form>
    
    <div id="wdl-messages" class="wdl-messages"></div>
</div>