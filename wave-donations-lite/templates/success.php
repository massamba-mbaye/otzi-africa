<?php
/**
 * Template de la page de succès
 * Variables disponibles : $donation
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

$success_message = get_option('wdl_success_message', 'Merci pour votre généreux don !');
?>

<div class="wdl-success-container">
    <div class="wdl-success-icon">🎉</div>
    <h2 class="wdl-success-title">Don confirmé avec succès !</h2>
    <p class="wdl-success-message"><?php echo esc_html($success_message); ?></p>
    
    <?php if ($donation) : ?>
        <div class="wdl-donation-details">
            <h3>📋 Récapitulatif de votre don :</h3>
            <p><strong>💰 Montant :</strong> <?php echo number_format($donation->amount, 0, ',', ' ') . ' ' . $donation->currency; ?></p>
            <p><strong>🔖 Référence :</strong> <?php echo esc_html($donation->donation_id); ?></p>
            <p><strong>👤 Donateur :</strong> <?php echo esc_html($donation->donor_name); ?></p>
            <p><strong>📅 Date :</strong> <?php echo date_i18n('d/m/Y à H:i', strtotime($donation->created_at)); ?></p>
            
            <?php if (!empty($donation->transaction_id)) : ?>
                <p><strong>🏦 ID Transaction :</strong> <?php echo esc_html($donation->transaction_id); ?></p>
            <?php endif; ?>
            
            <?php if (!empty($donation->donor_message)) : ?>
                <p><strong>💬 Votre message :</strong></p>
                <p style="font-style: italic; padding: 10px; background: #f0f0f0; border-radius: 5px;">
                    "<?php echo esc_html($donation->donor_message); ?>"
                </p>
            <?php endif; ?>
        </div>
        
        <div class="wdl-next-steps">
            <h3>📧 Et maintenant ?</h3>
            <p>Un email de confirmation a été envoyé à <strong><?php echo esc_html($donation->donor_email); ?></strong></p>
            <p>Vous recevrez également un reçu fiscal si applicable selon la réglementation en vigueur.</p>
        </div>
    <?php endif; ?>
    
    <div class="wdl-success-actions">
        <a href="<?php echo home_url(); ?>" class="wdl-btn wdl-btn-primary">
            🏠 Retour à l'accueil
        </a>
        
        <?php if ($donation) : ?>
            <a href="javascript:window.print()" class="wdl-btn wdl-btn-secondary">
                🖨️ Imprimer le reçu
            </a>
        <?php endif; ?>
    </div>
    
    <div class="wdl-social-share">
        <p>💙 Partagez votre geste généreux :</p>
        <div class="wdl-share-buttons">
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?php echo urlencode(home_url()); ?>" 
               target="_blank" 
               class="wdl-share-btn wdl-facebook">
                Facebook
            </a>
            <a href="https://twitter.com/intent/tweet?text=<?php echo urlencode('Je viens de faire un don ! Rejoignez-moi pour soutenir cette belle cause.'); ?>&url=<?php echo urlencode(home_url()); ?>" 
               target="_blank" 
               class="wdl-share-btn wdl-twitter">
                Twitter
            </a>
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?php echo urlencode(home_url()); ?>" 
               target="_blank" 
               class="wdl-share-btn wdl-linkedin">
                LinkedIn
            </a>
        </div>
    </div>
</div>

<style>
/* Styles spécifiques à la page de succès */
.wdl-next-steps {
    background: #e8f5e8;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    border-left: 4px solid #27ae60;
}

.wdl-next-steps h3 {
    margin-top: 0;
    color: #27ae60;
}

.wdl-social-share {
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.wdl-share-buttons {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
}

.wdl-share-btn {
    padding: 8px 16px;
    border-radius: 5px;
    text-decoration: none;
    color: white;
    font-size: 14px;
    font-weight: 500;
    transition: opacity 0.3s;
}

.wdl-share-btn:hover {
    opacity: 0.8;
    color: white;
    text-decoration: none;
}

.wdl-facebook { background: #3b5998; }
.wdl-twitter { background: #1da1f2; }
.wdl-linkedin { background: #0077b5; }

@media print {
    .wdl-success-actions,
    .wdl-social-share {
        display: none;
    }
    
    .wdl-success-container {
        box-shadow: none;
        border: 1px solid #ccc;
    }
}

@media (max-width: 600px) {
    .wdl-share-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .wdl-share-btn {
        width: 200px;
        text-align: center;
    }
}
</style>