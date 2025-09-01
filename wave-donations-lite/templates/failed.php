<?php
/**
 * Template de la page d'échec
 * Variables disponibles : $donation
 */

// Sécurité
if (!defined('ABSPATH')) {
    exit;
}

$failed_message = get_option('wdl_failed_message', 'Désolé, votre paiement n\'a pas pu être traité.');
?>

<div class="wdl-failed-container">
    <div class="wdl-failed-icon">😔</div>
    <h2 class="wdl-failed-title">Paiement non abouti</h2>
    <p class="wdl-failed-message"><?php echo esc_html($failed_message); ?></p>
    
    <?php if ($donation) : ?>
        <div class="wdl-donation-details">
            <h3>📋 Détails de la tentative :</h3>
            <p><strong>🔖 Référence :</strong> <?php echo esc_html($donation->donation_id); ?></p>
            <p><strong>💰 Montant :</strong> <?php echo number_format($donation->amount, 0, ',', ' ') . ' ' . $donation->currency; ?></p>
            <p><strong>👤 Donateur :</strong> <?php echo esc_html($donation->donor_name); ?></p>
            <p><strong>📅 Date :</strong> <?php echo date_i18n('d/m/Y à H:i', strtotime($donation->created_at)); ?></p>
        </div>
    <?php endif; ?>
    
    <div class="wdl-help-section">
        <h3>🤔 Que s'est-il passé ?</h3>
        <p>Votre paiement n'a pas pu être finalisé. Cela peut être dû à :</p>
        <ul class="wdl-help-list">
            <li>💳 Solde insuffisant sur votre compte</li>
            <li>🔒 Problème de connexion réseau</li>
            <li>⏰ Session expirée</li>
            <li>🚫 Transaction annulée</li>
            <li>🏦 Problème technique temporaire</li>
        </ul>
    </div>
    
    <div class="wdl-solutions">
        <h3>💡 Que faire maintenant ?</h3>
        <div class="wdl-solution-cards">
            <div class="wdl-solution-card">
                <div class="wdl-solution-icon">🔄</div>
                <h4>Réessayer</h4>
                <p>Tentez à nouveau votre don avec le même montant</p>
                <a href="javascript:history.back()" class="wdl-btn wdl-btn-primary">
                    Réessayer le paiement
                </a>
            </div>
            
            <div class="wdl-solution-card">
                <div class="wdl-solution-icon">💰</div>
                <h4>Changer le montant</h4>
                <p>Modifiez le montant de votre don</p>
                <a href="<?php echo esc_url(wp_get_referer() ?: home_url('/faire-un-don/')); ?>" class="wdl-btn wdl-btn-secondary">
                    Nouveau montant
                </a>
            </div>
            
            <div class="wdl-solution-card">
                <div class="wdl-solution-icon">📞</div>
                <h4>Nous contacter</h4>
                <p>Besoin d'aide ? Contactez notre équipe</p>
                <a href="mailto:<?php echo get_option('admin_email'); ?>?subject=Problème de don - <?php echo $donation ? $donation->donation_id : 'Référence inconnue'; ?>" class="wdl-btn wdl-btn-secondary">
                    Envoyer un email
                </a>
            </div>
        </div>
    </div>
    
    <div class="wdl-alternative-methods">
        <h3>🏦 Autres moyens de contribuer</h3>
        <p>Vous pouvez aussi nous soutenir par :</p>
        <div class="wdl-alternatives">
            <div class="wdl-alt-method">
                <strong>💳 Virement bancaire</strong>
                <p>Contactez-nous pour obtenir nos coordonnées bancaires</p>
            </div>
            <div class="wdl-alt-method">
                <strong>📱 Mobile Money</strong>
                <p>Orange Money, Free Money, ou Wave disponibles</p>
            </div>
            <div class="wdl-alt-method">
                <strong>✋ Don en nature</strong>
                <p>Nous acceptons également les dons en nature</p>
            </div>
        </div>
    </div>
    
    <div class="wdl-failed-actions">
        <a href="<?php echo home_url(); ?>" class="wdl-btn wdl-btn-primary">
            🏠 Retour à l'accueil
        </a>
        
        <a href="mailto:<?php echo get_option('admin_email'); ?>" class="wdl-btn wdl-btn-secondary">
            📧 Contactez-nous
        </a>
    </div>
    
    <div class="wdl-encouragement">
        <p><em>💙 Merci pour votre intention généreuse ! Votre soutien compte énormément pour nous.</em></p>
    </div>
</div>

<style>
/* Styles spécifiques à la page d'échec */
.wdl-help-section {
    background: #fff3cd;
    padding: 20px;
    border-radius: 8px;
    margin: 20px 0;
    border-left: 4px solid #ffc107;
}

.wdl-help-section h3 {
    margin-top: 0;
    color: #856404;
}

.wdl-help-list {
    text-align: left;
    margin: 15px 0;
    padding-left: 20px;
}

.wdl-help-list li {
    margin: 8px 0;
    color: #856404;
}

.wdl-solutions {
    margin: 30px 0;
}

.wdl-solution-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.wdl-solution-card {
    background: #f8f9fa;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    border: 1px solid #dee2e6;
    transition: transform 0.3s, box-shadow 0.3s;
}

.wdl-solution-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.wdl-solution-icon {
    font-size: 2.5em;
    margin-bottom: 15px;
}

.wdl-solution-card h4 {
    color: #2c3e50;
    margin: 10px 0;
    font-size: 18px;
}

.wdl-solution-card p {
    color: #6c757d;
    margin-bottom: 15px;
    font-size: 14px;
}

.wdl-alternative-methods {
    background: #e7f3ff;
    padding: 25px;
    border-radius: 8px;
    margin: 30px 0;
    border-left: 4px solid #007bff;
}

.wdl-alternative-methods h3 {
    margin-top: 0;
    color: #004085;
}

.wdl-alternatives {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 15px 0;
}

.wdl-alt-method {
    background: white;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #b8daff;
}

.wdl-alt-method strong {
    color: #004085;
    display: block;
    margin-bottom: 8px;
}

.wdl-alt-method p {
    color: #6c757d;
    font-size: 13px;
    margin: 0;
}

.wdl-encouragement {
    margin-top: 30px;
    padding: 20px;
    background: #d1ecf1;
    border-radius: 8px;
    border-left: 4px solid #17a2b8;
}

.wdl-encouragement p {
    margin: 0;
    color: #0c5460;
    font-size: 16px;
}

@media (max-width: 768px) {
    .wdl-solution-cards {
        grid-template-columns: 1fr;
    }
    
    .wdl-alternatives {
        grid-template-columns: 1fr;
    }
    
    .wdl-failed-actions .wdl-btn {
        display: block;
        margin: 10px 0;
        text-align: center;
    }
}
</style>