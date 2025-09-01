# Wave Donations Lite - Plugin WordPress

Plugin simple et efficace pour gérer les donations avec intégration Wave SN (Sénégal).

## ✨ Fonctionnalités

### 🎯 **Version MVP (Actuelle)**
- ✅ Formulaire de donation responsive
- ✅ Intégration directe avec Wave SN
- ✅ Gestion des statuts de paiement
- ✅ Interface d'administration WordPress
- ✅ Pages de retour personnalisées
- ✅ Export CSV des donations
- ✅ Shortcode simple `[wave_donation_form]`

### 🚀 **Fonctionnalités prévues**
- 📊 Dashboard avec graphiques
- 📧 Emails automatiques
- 👥 Gestion avancée des donateurs
- 🔄 Dons récurrents
- 🎨 Thèmes personnalisables
- 📈 Rapports détaillés

## 📦 Installation

1. **Téléchargez** le dossier `wave-donations-lite`
2. **Placez-le** dans `/wp-content/plugins/`
3. **Activez** le plugin dans l'administration WordPress
4. **Configurez** vos paramètres Wave SN

## ⚙️ Configuration

### 1. Paramètres Wave SN
Allez dans **Donations > Paramètres** et configurez :

- **ID Marchand Wave** : Votre identifiant Wave (ex: `M_sn_qqv-YCHOudUu`)
- **Devise** : XOF (FCFA) par défaut
- **Montants prédéfinis** : `1000,5000,10000,25000,50000`
- **Montant minimum** : `500 FCFA`

### 2. URLs de retour Wave
Configurez dans votre compte Wave :
- **URL de succès** : `https://votresite.com/donation-success/`
- **URL d'échec** : `https://votresite.com/donation-failed/`

### 3. Messages personnalisés
Personnalisez les messages de succès et d'échec dans les paramètres.

## 🎨 Utilisation

### Shortcode principal
```
[wave_donation_form]
```

### Options du shortcode
```
[wave_donation_form title="Soutenez notre cause"]
[wave_donation_form show_amounts="false"]
[wave_donation_form show_message="false"]
```

### Paramètres disponibles
- `title` : Titre du formulaire
- `show_amounts` : Afficher les montants prédéfinis (true/false)
- `show_message` : Afficher le champ message (true/false)

## 📊 Administration

### Tableau de bord
- **Statistiques** en temps réel
- **Donations récentes**
- **Actions rapides**

### Liste des donations
- **Filtrage** par statut
- **Pagination**
- **Export CSV**
- **Détails complets**

### Statuts de donations
- 🟡 **En attente** (`pending`) : Donation créée, en attente de paiement
- 🟢 **Confirmée** (`completed`) : Paiement réussi
- 🔴 **Échouée** (`failed`) : Paiement échoué
- ⚪ **Annulée** (`cancelled`) : Transaction annulée

## 🗄️ Base de données

Le plugin crée automatiquement la table `wp_wdl_donations` avec :

```sql
- id (bigint, auto-increment)
- donation_id (varchar, unique)
- donor_name (varchar)
- donor_email (varchar)
- donor_phone (varchar, optional)
- amount (decimal)
- currency (varchar, default: XOF)
- status (enum: pending/completed/failed/cancelled)
- payment_method (varchar, default: wave)
- transaction_id (varchar, optional)
- wave_payment_url (text, optional)
- donor_message (text, optional)
- created_at (datetime)
- updated_at (datetime)
```

## 🔧 Hooks et Filtres

### Actions disponibles
```php
// Après confirmation d'une donation
do_action('wdl_donation_completed', $donation_id, $donation);

// Après échec d'une donation
do_action('wdl_donation_failed', $donation_id, $donation);
```

### Exemples d'utilisation
```php
// Envoyer un email après donation confirmée
add_action('wdl_donation_completed', 'my_send_thank_you_email', 10, 2);
function my_send_thank_you_email($donation_id, $donation) {
    wp_mail(
        $donation->donor_email,
        'Merci pour votre don !',
        "Bonjour {$donation->donor_name}, merci pour votre don de {$donation->amount} FCFA !"
    );
}
```

## 📱 Responsive Design

Le plugin est entièrement responsive :
- **Desktop** : Formulaire sur 2 colonnes
- **Tablet** : Formulaire adapté
- **Mobile** : Formulaire simple colonne
- **Touch-friendly** : Boutons optimisés pour mobile

## 🎯 Performance

### Optimisations incluses
- ✅ **CSS/JS minifiés** en production
- ✅ **Requêtes optimisées**
- ✅ **Cache des paramètres**
- ✅ **Chargement conditionnel**
- ✅ **Validation côté client et serveur**

### Métriques typiques
- **Temps de chargement** : < 2 secondes
- **Score GTMetrix** : A/A
- **Score PageSpeed** : > 90/100

## 🔐 Sécurité

### Mesures implémentées
- ✅ **Nonces WordPress**
- ✅ **Sanitization** de tous les inputs
- ✅ **Validation** côté serveur
- ✅ **Prévention XSS**
- ✅ **Prévention CSRF**
- ✅ **Échappement des sorties**

### Bonnes pratiques
- Données nettoyées avant insertion BDD
- Vérification des permissions utilisateur
- Protection contre l'accès direct aux fichiers

## 🌍 Internationalisation

Le plugin est prêt pour la traduction :
- **Domain** : `wave-donations-lite`
- **Fichiers** : `/languages/`
- **Langues supportées** : Français (par défaut)

### Ajouter une traduction
1. Utilisez Poedit ou un éditeur de traduction
2. Créez `wave-donations-lite-en_US.po`
3. Placez dans `/languages/`

## 🧪 Tests

### Tests manuels recommandés
1. **Formulaire** : Validation, soumission, erreurs
2. **Paiement** : Redirection Wave, retours
3. **Admin** : Dashboard, liste, paramètres
4. **Mobile** : Responsive, touch events
5. **Performance** : Temps de chargement

### Environnements testés
- ✅ WordPress 5.0+
- ✅ PHP 7.4+
- ✅ MySQL 5.7+
- ✅ Navigateurs modernes

## 🐛 Debug

### Activer le debug WordPress
```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

### Logs du plugin
Les erreurs sont loggées dans `/wp-content/debug.log`

### Problèmes courants

**Formulaire ne s'affiche pas :**
- Vérifier que le shortcode est correct : `[wave_donation_form]`
- Vérifier les conflits CSS/JS avec d'autres plugins
- Désactiver temporairement les autres plugins
- Vérifier que le plugin est bien activé

**Erreur "ID marchand Wave non configuré" :**
- Aller dans Donations > Paramètres
- Saisir votre ID marchand Wave (format : M_sn_xxxxx)
- Sauvegarder les paramètres

**Paiements non confirmés :**
- Vérifier les URLs de retour dans Wave
- Contrôler les logs d'erreur WordPress
- Tester avec un petit montant

**Pages de retour non trouvées :**
- Vérifier que les pages sont créées (donation-success, donation-failed)
- Vider le cache des permaliens : Réglages > Permaliens > Enregistrer

## 📞 Support

### Assistance technique
- **Email** : support@votresite.com
- **Documentation** : Ce README
- **Issues GitHub** : [Lien vers issues]

### Informations système requises
Lors d'une demande d'aide, fournissez :
- Version WordPress
- Version PHP
- Liste des plugins actifs
- Message d'erreur exact
- Étapes pour reproduire le problème

## 🚀 Roadmap

### Version 1.1 (Prochaine)
- [ ] Dashboard avec graphiques
- [ ] Emails automatiques de confirmation
- [ ] Webhooks Wave pour confirmation temps réel
- [ ] Gestion des donateurs récurrents

### Version 1.2 (Future)
- [ ] Dons récurrents mensuels
- [ ] Certificats fiscaux automatiques
- [ ] Intégration MailChimp/Sendinblue
- [ ] API REST pour développeurs

### Version 2.0 (Long terme)
- [ ] Interface donateur (historique)
- [ ] Campagnes de don avec objectifs
- [ ] Intégrations multiples (PayPal, Stripe...)
- [ ] Marketplace d'extensions

## 🏗️ Structure technique

### Architecture MVC simplifiée
```
wave-donations-lite/
├── wave-donations-lite.php    # Contrôleur principal
├── includes/                  # Modèles/Classes
│   ├── class-wdl-database.php
│   ├── class-wdl-form.php
│   └── class-wdl-admin.php
├── templates/                 # Vues
│   ├── donation-form.php
│   ├── success.php
│   └── failed.php
└── assets/                    # Ressources
    ├── style.css
    └── script.js
```

### Flux de données
1. **Utilisateur** remplit le formulaire
2. **JavaScript** valide côté client
3. **PHP** valide et sauvegarde en BDD
4. **Redirection** vers Wave avec paramètres
5. **Retour Wave** met à jour le statut
6. **Affichage** de la page de confirmation

### Classes principales

**WaveDonationsLite** (Singleton)
- Point d'entrée du plugin
- Gestion des hooks WordPress
- Chargement des dépendances

**WDL_Database**
- Création et gestion des tables
- CRUD des donations
- Statistiques et rapports

**WDL_Form**
- Affichage des formulaires
- Traitement AJAX
- Gestion des shortcodes

**WDL_Admin**
- Interface d'administration
- Pages de configuration
- Export des données

## 📋 Checklist de déploiement

### Avant mise en production
- [ ] Tester sur environnement de staging
- [ ] Configurer les paramètres Wave
- [ ] Vérifier les URLs de retour
- [ ] Tester les paiements avec petits montants
- [ ] Sauvegarder la base de données
- [ ] Activer les logs d'erreur

### Après déploiement
- [ ] Vérifier que le formulaire s'affiche
- [ ] Tester un don complet
- [ ] Contrôler les données en BDD
- [ ] Vérifier l'interface admin
- [ ] Surveiller les erreurs 24h

### Maintenance régulière
- [ ] Backup hebdomadaire des donations
- [ ] Nettoyage des logs anciens
- [ ] Mise à jour des dépendances
- [ ] Contrôle des performances
- [ ] Surveillance des erreurs

## 💡 Conseils d'optimisation

### Performance
```php
// Désactiver les révisions pour les pages de don
add_filter('wp_revisions_to_keep', function($num, $post) {
    if ($post->post_type === 'page' && in_array($post->post_name, ['donation-success', 'donation-failed'])) {
        return 0;
    }
    return $num;
}, 10, 2);
```

### Sécurité
```php
// Limiter les tentatives de donation
add_action('wp_ajax_wdl_process_donation', function() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $attempts = get_transient('wdl_attempts_' . $ip) ?: 0;
    
    if ($attempts > 10) {
        wp_die('Trop de tentatives. Réessayez dans 1 heure.');
    }
    
    set_transient('wdl_attempts_' . $ip, $attempts + 1, HOUR_IN_SECONDS);
});
```

### UX
```javascript
// Auto-complétion des montants fréquents
jQuery('#wdl_amount').on('focus', function() {
    // Afficher les montants récents du donateur
    var recent = localStorage.getItem('wdl_recent_amounts');
    if (recent) {
        // Logique d'auto-suggestion
    }
});
```

## 📜 Licence

GPL v2 ou ultérieure - https://www.gnu.org/licenses/gpl-2.0.html

## 👨‍💻 Développement

### Contribuer
1. Fork le projet
2. Créer une branche feature
3. Commiter vos changements
4. Pusher vers la branche
5. Ouvrir une Pull Request

### Standards de code
- **PSR-4** pour l'autoloading
- **WordPress Coding Standards**
- **Commentaires** en français
- **Noms de variables** explicites
- **Fonctions** courtes et spécialisées

---

**Wave Donations Lite v1.0** - Plugin WordPress pour donations avec Wave SN
Développé avec ❤️ pour les organisations sénégalaises par Massamba MBAYE