/**
 * Wave Donations Lite - JavaScript Frontend
 */

(function($) {
    'use strict';

    // Objet principal du plugin
    var WDL = {
        
        // Initialisation
        init: function() {
            this.bindEvents();
            this.setupAmountButtons();
            this.setupFormValidation();
        },
        
        // Liaison des événements
        bindEvents: function() {
            $(document).on('click', '.wdl-amount-btn', this.handleAmountSelection);
            $(document).on('submit', '#wdl-donation-form', this.handleFormSubmit);
            $(document).on('input', '#wdl_amount', this.validateAmount);
            $(document).on('input', '.wdl-input', this.clearFieldError);
        },
        
        // Configuration des boutons de montant
        setupAmountButtons: function() {
            $('.wdl-amount-btn').each(function() {
                $(this).on('click', function(e) {
                    e.preventDefault();
                    WDL.selectAmount($(this));
                });
            });
        },
        
        // Sélection d'un montant prédéfini
        selectAmount: function($button) {
            // Retirer la classe active des autres boutons
            $('.wdl-amount-btn').removeClass('active');
            
            // Ajouter la classe active au bouton cliqué
            $button.addClass('active');
            
            // Mettre à jour le champ montant
            var amount = $button.data('amount');
            $('#wdl_amount').val(amount);
            
            // Valider le montant
            this.validateAmount.call($('#wdl_amount')[0]);
        },
        
        // Gestion de la sélection de montant
        handleAmountSelection: function(e) {
            e.preventDefault();
            WDL.selectAmount($(this));
        },
        
        // Configuration de la validation de formulaire
        setupFormValidation: function() {
            // Validation en temps réel
            $('#wdl_donor_name').on('input', this.validateName);
            $('#wdl_donor_email').on('input', this.validateEmail);
            $('#wdl_donor_phone').on('input', this.validatePhone);
        },
        
        // Validation du nom
        validateName: function() {
            var $field = $(this);
            var value = $field.val().trim();
            var isValid = value.length >= 2;
            
            WDL.updateFieldValidation($field, isValid, 'Le nom doit contenir au moins 2 caractères');
            return isValid;
        },
        
        // Validation de l'email
        validateEmail: function() {
            var $field = $(this);
            var value = $field.val().trim();
            var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            var isValid = emailRegex.test(value);
            
            WDL.updateFieldValidation($field, isValid, 'Veuillez saisir une adresse email valide');
            return isValid;
        },
        
        // Validation du téléphone
        validatePhone: function() {
            var $field = $(this);
            var value = $field.val().trim();
            
            // Le téléphone est optionnel, mais s'il est renseigné, il doit être valide
            if (value === '') {
                WDL.updateFieldValidation($field, true);
                return true;
            }
            
            var phoneRegex = /^[\+]?[0-9\s\-\(\)]{8,}$/;
            var isValid = phoneRegex.test(value);
            
            WDL.updateFieldValidation($field, isValid, 'Veuillez saisir un numéro de téléphone valide');
            return isValid;
        },
        
        // Validation du montant
        validateAmount: function() {
            var $field = $(this);
            var value = parseFloat($field.val());
            var minAmount = parseFloat($field.attr('min')) || 500;
            var isValid = !isNaN(value) && value >= minAmount;
            
            // Désélectionner les boutons si le montant est modifié manuellement
            if ($field.is('#wdl_amount')) {
                var selectedAmount = $('.wdl-amount-btn.active').data('amount');
                if (selectedAmount && selectedAmount != value) {
                    $('.wdl-amount-btn').removeClass('active');
                }
            }
            
            var errorMessage = 'Le montant minimum est de ' + minAmount.toLocaleString('fr-FR') + ' FCFA';
            WDL.updateFieldValidation($field, isValid, errorMessage);
            return isValid;
        },
        
        // Mettre à jour la validation d'un champ
        updateFieldValidation: function($field, isValid, errorMessage) {
            var $errorElement = $field.siblings('.wdl-error-text');
            
            if (isValid) {
                $field.removeClass('error').addClass('success');
                $errorElement.remove();
            } else {
                $field.removeClass('success').addClass('error');
                
                if ($errorElement.length === 0 && errorMessage) {
                    $field.after('<span class="wdl-error-text">' + errorMessage + '</span>');
                }
            }
        },
        
        // Effacer l'erreur d'un champ
        clearFieldError: function() {
            var $field = $(this);
            if ($field.hasClass('error')) {
                $field.removeClass('error');
                $field.siblings('.wdl-error-text').remove();
            }
        },
        
        // Gestion de la soumission du formulaire
        handleFormSubmit: function(e) {
            e.preventDefault();
            
            var $form = $(this);
            var $submitBtn = $form.find('.wdl-submit-btn');
            var $loading = $form.find('.wdl-loading');
            var $messages = $('#wdl-messages');
            
            // Valider tous les champs
            var isValid = WDL.validateForm($form);
            
            if (!isValid) {
                WDL.showMessage('Veuillez corriger les erreurs avant de continuer', 'error');
                return;
            }
            
            // Désactiver le bouton et afficher le loading
            $submitBtn.prop('disabled', true).html('<span class="wdl-spinner"></span>Traitement...');
            $loading.show();
            $messages.empty();
            
            // Préparer les données
            var formData = {
                action: 'wdl_process_donation',
                wdl_nonce: $form.find('[name="wdl_nonce"]').val(),
                donor_name: $form.find('[name="donor_name"]').val(),
                donor_email: $form.find('[name="donor_email"]').val(),
                donor_phone: $form.find('[name="donor_phone"]').val(),
                amount: $form.find('[name="amount"]').val(),
                donor_message: $form.find('[name="donor_message"]').val()
            };
            
            // Envoyer la requête AJAX
            $.ajax({
                url: wdl_ajax.ajax_url,
                type: 'POST',
                data: formData,
                dataType: 'json',
                timeout: 30000,
                success: function(response) {
                    if (response.success) {
                        // Rediriger vers Wave
                        WDL.showMessage('Redirection vers la page de paiement...', 'success');
                        
                        setTimeout(function() {
                            window.location.href = response.data.redirect_url;
                        }, 1000);
                        
                    } else {
                        WDL.showMessage(response.data.message || 'Une erreur est survenue', 'error');
                        WDL.resetForm($form, $submitBtn, $loading);
                    }
                },
                error: function(xhr, status, error) {
                    var errorMessage = 'Erreur de connexion. Veuillez réessayer.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'La requête a expiré. Veuillez réessayer.';
                    }
                    
                    WDL.showMessage(errorMessage, 'error');
                    WDL.resetForm($form, $submitBtn, $loading);
                }
            });
        },
        
        // Valider l'ensemble du formulaire
        validateForm: function($form) {
            var isValid = true;
            
            // Valider chaque champ
            $form.find('#wdl_donor_name').each(function() {
                if (!WDL.validateName.call(this)) isValid = false;
            });
            
            $form.find('#wdl_donor_email').each(function() {
                if (!WDL.validateEmail.call(this)) isValid = false;
            });
            
            $form.find('#wdl_donor_phone').each(function() {
                if (!WDL.validatePhone.call(this)) isValid = false;
            });
            
            $form.find('#wdl_amount').each(function() {
                if (!WDL.validateAmount.call(this)) isValid = false;
            });
            
            return isValid;
        },
        
        // Réinitialiser le formulaire après erreur
        resetForm: function($form, $submitBtn, $loading) {
            $submitBtn.prop('disabled', false).html('Faire un don');
            $loading.hide();
        },
        
        // Afficher un message
        showMessage: function(message, type) {
            var $messages = $('#wdl-messages');
            var messageClass = 'wdl-message ' + (type || 'info');
            
            var $messageElement = $('<div class="' + messageClass + '">' + message + '</div>');
            $messages.html($messageElement);
            
            // Faire défiler vers le message
            $('html, body').animate({
                scrollTop: $messages.offset().top - 100
            }, 500);
            
            // Auto-masquer les messages de succès
            if (type === 'success') {
                setTimeout(function() {
                    $messageElement.fadeOut();
                }, 5000);
            }
        },
        
        // Formater les nombres
        formatNumber: function(number) {
            return number.toLocaleString('fr-FR');
        },
        
        // Utilitaires pour les cookies (si nécessaire)
        setCookie: function(name, value, days) {
            var expires = '';
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = '; expires=' + date.toUTCString();
            }
            document.cookie = name + '=' + (value || '') + expires + '; path=/';
        },
        
        getCookie: function(name) {
            var nameEQ = name + '=';
            var ca = document.cookie.split(';');
            for (var i = 0; i < ca.length; i++) {
                var c = ca[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) return c.substring(nameEQ.length, c.length);
            }
            return null;
        }
    };
    
    // Initialiser quand le DOM est prêt
    $(document).ready(function() {
        WDL.init();
    });
    
    // Exposer l'objet WDL globalement si nécessaire
    window.WDL = WDL;

})(jQuery);