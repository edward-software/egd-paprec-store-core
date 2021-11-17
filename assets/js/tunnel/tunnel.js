$(function () {

    /****************************************
     * CATALOG
     ***************************************/

    // $('.quantityProductSelect').change(function () {
    //
    //     $('button').prop("disabled", true); // On désactive tous les select
    //     const productId = $(this).data('product');
    //     const url = $(this).data('url');
    //     const qtty = $(this).val();
    //
    //     $.ajax({
    //         url: url,
    //         type: "POST",
    //         data: {
    //             "productId": productId,
    //             "quantity": qtty
    //         },
    //         success: function (response) {
    //             // On récupère l'HTML du du produit ajouté et on l'insère dans le récap du devis (=panier)
    //             var htmlToDisplay = response.trim();
    //             $("#devis-recap-item-" + productId).remove();
    //             $("#devis-recap").append(htmlToDisplay);
    //             $('#quantityProductSelect_' + productId).val($('#devis-recap-item-' + productId).data('qtty'));
    //             disableButtonsFromQuantity($('#quantityProductSelect_' + productId).val(), productId);
    //         },
    //         complete: function () {
    //             $('button').prop("disabled", false);
    //         }
    //     });
    // });

    if ($('.catalog-ponctual').is('div')) {
        /**
         * Gestion des datepickers
         */

            // On ne peut choisir une date de prestation qu'à partir d'aujourd'hui

        var now = new Date(); // On définit arbitrairement la date maximum pour le rappel à dans 3 mois

        var maxDate = moment(now);
        $('#frequencyPonctualDatepicker').datepicker({
            option: $.datepicker.regional["fr"],
            minDate: +3,
            maxDate: "+3M"
        });

        $('#frequencyPonctualDatepicker').change(function () {
            const ponctualDate = $(this).val().split('/').reverse().join('-');
            const url = $(this).data('url');

            $.ajax({
                url: url,
                type: "POST",
                data: {
                    "ponctual_date": ponctualDate
                },
            });

        })
    }


    /*****************************
     *  Gestion du bouton flottant en bas de page
     *****************************/

    // if ($('.product-container').is('div')) {
    //
    //
    //     var navbarOffset = $('.navbar')[0].getBoundingClientRect().top;
    //     var productOffset = $('.product-container')[0].getBoundingClientRect().top;
    //     var otherNeedsOffset = $('.other-needs-container')[0].getBoundingClientRect().top;
    //     var otherNeedsHeight = $('.other-needs-container').height();
    //     var duration = 350;
    //
    //     $(window).scroll(function () {
    //             const scrollTop = $(this).scrollTop();
    //             if (scrollTop <= navbarOffset) {
    //                 $('#define-need-button').fadeOut(duration);
    //                 $('#other-needs-button').fadeOut(duration);
    //             } else if (scrollTop > navbarOffset && scrollTop < productOffset) {
    //                 $('#define-need-button').fadeIn(duration);
    //                 $('#other-needs-button').fadeOut(duration);
    //
    //             } else if (scrollTop >= productOffset && scrollTop <= ($(document).height() - $(window).height() - (otherNeedsHeight / 2))) {
    //                 $('#define-need-button').fadeOut(duration);
    //                 $('#other-needs-button').fadeIn(duration);
    //             } else if (scrollTop > ($(document).height() - $(window).height() - (otherNeedsHeight / 2))) {
    //                 $('#other-needs-button').fadeOut(duration);
    //             }
    //         }
    //     );
    //
    //     $('#define-need-button').on('click', function (e) {
    //         e.preventDefault();
    //         $('html, body').animate({
    //             scrollTop: productOffset
    //         }, 750);
    //     });
    //
    //     $('#other-needs-button').on('click', function (e) {
    //         e.preventDefault();
    //         $('html, body').animate({
    //             scrollTop: otherNeedsOffset
    //         }, 750);
    //     });
    // }

    /*****************************
     *  Gestion otherNeed
     *****************************/

    $('.other-needs-image').click(function () {
        var url = $(this).data('url');
        const that = $(this);
        if (that.hasClass('active')) {
            that.removeClass('active');
        } else {
            that.addClass('active');
        }

        $.ajax({
            type: "POST",
            url: url,
            success: function (response) {

            }
        })
    });


    /**
     * Lorsque l'on clique sur la fréquence régulière, l'input et le select pour configurer la fréquence s'affiche
     */
    $('[id^=regularFrequencyButton__]').on('click', function () {
        const productId = (this.name).replace('productFrequencyRadios__', '');
        $('#productFrequencyIntervalSelect__' + productId).prop('hidden', false);
        $('#productFrequencyTimesInput__' + productId).prop('hidden', false);
        $('#textFrequencyTimes__' + productId).prop('hidden', false);
        $('#productFrequencyTimesInput__' + productId).val(1);
    });

    /**
     * Lorsque l'on clique sur les autres fréquences, l'input et le select pour configurer la fréquence ne doivent plus s'affiche
     */
    $('[id^=unknownFrequencyButton__]').on('click', function () {
        const productId = (this.name).replace('productFrequencyRadios__', '');
        $('#productFrequencyTimesInput__' + productId).prop('hidden', true);
        $('#textFrequencyTimes__' + productId).prop('hidden', true);
        $('#productFrequencyIntervalSelect__' + productId).prop('hidden', true);
        $('#productFrequencyTimesInput__' + productId).val(0);
    });

    $('#addFrequencyButton').on('click', function () {
        $('#catalog_frequency_times_select').val(function (i, oldval) {
            return ++oldval;
        });
    });

    $('#removeFrequencyButton').on('click', function () {
        if ($('#catalog_frequency_times_select').val() > 1) {
            $('#catalog_frequency_times_select').val(function (i, oldval) {
                return --oldval;
            });
        }
    });

    $('.catalog_next_step_button').on('click', function () {
        const url = $(this).data('url');
        $(location).attr('href', url);
    });

    /**
     * Ajout un seul produit au clic sur le +
     */
    $('.addOneToCartButton').click(function () {
        var url = $(this).data('url');

        var productId = (this.id).replace('addOneToCartButton', '');
        $.ajax({
            type: "POST",
            url: url,
            success: function (response) {
                // On récupère l'HTML du du produit ajouté et on l'insère dans le récap du devis (=panier)
                var htmlToDisplay = response.trim();
                $("#devis-recap-item-" + productId).remove();
                $(".devis-recap").append(htmlToDisplay);
                // On met à jour la valeur du <select> de qtty du produit
                $('#quantityProductSelect_' + productId).val($('#devis-recap-item-' + productId).data('qtty'));
                disableButtonsFromQuantity($('#quantityProductSelect_' + productId).val(), productId);

            }
        })
    });

    /**
     * Enlève un seul produit au clic sur le -
     */
    $('.removeOneToCartButton').click(function () {
        var url = $(this).data('url');
        var productId = (this.id).replace('removeOneToCartButton', '');
        $.ajax({
            type: "POST",
            url: url,
            success: function (response) {
                $("#devis-recap-item-" + productId).remove();
                if (JSON.stringify(response) !== '{}') {
                    // On récupère l'HTML du du produit ajouté et on l'insère dans le récap du devis (=panier)
                    var htmlToDisplay = response.trim();
                    $(".devis-recap").append(htmlToDisplay);
                }
                // On met à jour la valeur du <select> de qtty du produit
                $('#quantityProductSelect_' + productId).val($('#devis-recap-item-' + productId).data('qtty'));
                disableButtonsFromQuantity($('#quantityProductSelect_' + productId).val(), productId);
            }
        })
    });

    /**
     * Edition de la fréquence d'un produit
     */
    $('input[type=radio][name^="productFrequencyRadios__"]').change(function () {
        var productId = (this.name).replace('productFrequencyRadios__', '');
        const url = $(this).data('url');
        editProductFrequency(url, productId);
    });

    $('input[id^="productFrequencyTimesInput__"]').change(function () {
        var productId = (this.id).replace('productFrequencyTimesInput__', '');
        const url = $(this).data('url');
        editProductFrequency(url, productId);
    });

    $('select[id^="productFrequencyIntervalSelect__"]').change(function () {
        var productId = (this.id).replace('productFrequencyIntervalSelect__', '');
        const url = $(this).data('url');
        editProductFrequency(url, productId);
    });

    /**
     * Masque la description et l'image de la gamme lorsque les produits sont affichés
     */
    $('[id^=rangeProductCollapse__]').on('show.bs.collapse', function () {
        const productId = (this.id).replace('rangeProductCollapse__', '');
        $('#rangeContentContainer__' + productId).hide();
    });

    /**
     * Affichage de la description et de l'image de la gamme lorsque les produits sont cachés
     */
    $('[id^=rangeProductCollapse__]').on('hide.bs.collapse', function () {
        const productId = (this.id).replace('rangeProductCollapse__', '');
        $('#rangeContentContainer__' + productId).show();
    });

    /****************************************
     * CONTACT FORM
     ***************************************/

    /**
     * Affichage d'un message d'info au focus sur le numéro de téléphone
     */
    $('#paprec_catalogbundle_quote_request_public_phone').focus(function () {
        $('#phone-number-info').show();
    });

    $('#paprec_catalogbundle_quote_request_public_phone').blur(function () {
        $('#phone-number-info').hide();
    });

    // Désactivation des champs d'adresses quand on sélecitonne multisite
    $('input[name*=isMultisite]').change(function () {
        if (this.value == 1) {
            $('.address-field').prop("disabled", true);
            $('.address-field').val('');
            $('#multisite-info').show();
        } else if (this.value == 0) {
            $('.address-field').prop("disabled", false);
            $('#multisite-info').hide();
            $('.address-content').hide();
        }
    });

    // Désactivation des champs de adresse de facturation quand on sélecitonne facturation identique
    $('input[name*=isSameAddress]').change(function () {
        if (this.value == 1) {
            $('.billing-address-label').hide();
            $('.billing-address-field').hide().prop('required',false);
            $('.billing-address-field').val('');
        } else if (this.value == 0) {
            $('.billing-address-label').show();
            $('.billing-address-field').show().prop('required',true);
        }
    });

    // Désactivation des champs de signatory quand on sélecitonne signataire
    $('input[name*=isSameSignatory]').change(function () {
        if (this.value == 1) {
            $('.signatory-label').hide();
            $('.signatory-field').hide().prop('required', false);
            $('.signatory-field').val('');
        } else if (this.value == 0) {
            $('.signatory-field').show().prop('required', true);
            $('.signatory-label').show();
        }
    });

    $('#contact_staff_select').change(function () {
        $('.contact_staff_input').val(this.value);
    });

    $('#contact_access_select').change(function () {
        $('.contact_access_input').val(this.value);
        if ($('.contact_access_input').val() === 'stairs') {
            $('#floorNumber').show();
            $('#paprec_catalogbundle_quote_request_public_floorNumber').focus().select();
        } else {
            $('#floorNumber').hide();
            $('#paprec_catalogbundle_quote_request_public_floorNumber').val(0);
        }
    });

    $('#contact_destruction_type_select').change(function () {
        $('.contact_destruction_type_input').val(this.value);
    });

    /**
     * Ajout du token du captcha dans le formulaire
     */
    var isContactDetailFormSubimitted = false;
    $('#contactDetailForm').submit(function (event) {
        if (!isContactDetailFormSubimitted) {
            $('.overlay').addClass('active');
            isContactDetailFormSubimitted = true;
            event.preventDefault();
            const siteKey = $('#contactDetailFormSubmitButton').data('key');
            grecaptcha.ready(function () {
                grecaptcha.execute(siteKey, {action: 'homepage'}).then(function (token) {
                    $('#contactDetailForm').prepend('<input type="hidden" name="g-recaptcha-response" value="' + token + '">')
                    $('#contactDetailForm').submit();
                });
            });
        }
    });

    $('#paprec_catalogbundle_quote_request_public_postalCode').autocomplete({
        source: '' + $('#paprec_catalogbundle_quote_request_public_postalCode').data('url'),
        minLength: 1,
        select: function (event, ui) {
            // $('#paprec_catalogbundle_quote_request_public_city').val(ui.item.label.substring(ui.item.label.indexOf('-') + 2));
            /**
             * Dès qu'un code postal a été sélectionné pour l'adresse de facturation, on affiche les autres champs
             * (adresse et précision de l'adresse)
             */
            $('.address-content').show();
        }
    }).keyup(function(){
        /**
         * s'il n'y a plus de valeur dans le code postal, on masque les autres champs
         */
        if ($('#paprec_catalogbundle_quote_request_public_postalCode').val() == '') {
            $('.address-content').hide();
        }
    });

});


/*******************************************
 * Functions
 ******************************************/

/**
 * Désactive les buttons d'un produit sur la page catalog en fonction de la quantité et du productId
 * si la quantité est égale à 0, alors on ne peut pas "Add One" ou "Add to quote"
 * @param quantity
 * @param productId
 */
function disableButtonsFromQuantity(quantity, productId) {
    if (quantity < 1) {
        $('#addProductToQuoteButton_' + productId).prop('disabled', true);
        $('#removeOneToCartButton' + productId).prop('disabled', true);
        $('#removeOneToCartButton' + productId).addClass('round-btn--disable');
    } else {
        $('#addProductToQuoteButton_' + productId).prop('disabled', false);
        $('#removeOneToCartButton' + productId).prop('disabled', false);
        $('#removeOneToCartButton' + productId).removeClass('round-btn--disable');
    }
}


function editProductFrequency(url, productId) {
    const frequency = $('input[type=radio][name^="productFrequencyRadios__' + productId + '"]:checked').val();
    let frequencyTimes = null;
    let frequencyInterval = null;
    if (frequency != 'unknown') {
        frequencyTimes = $("#productFrequencyTimesInput__" + productId).val();
        frequencyInterval = $("#productFrequencyIntervalSelect__" + productId).val();
    }

    $.ajax({
        url: url,
        type: "POST",
        data: {
            "frequency": frequency,
            "frequency_times": frequencyTimes,
            "frequency_interval": frequencyInterval
        },
        success: function (response) {
            // // On récupère l'HTML du du produit ajouté et on l'insère dans le récap du devis (=panier)
            // var htmlToDisplay = response.trim();
            // $("#devis-recap-item-" + productId).remove();
            // $("#devis-recap").append(htmlToDisplay);
            // $('#quantityProductSelect_' + productId).val($('#devis-recap-item-' + productId).data('qtty'));
            // disableButtonsFromQuantity($('#quantityProductSelect_' + productId).val(), productId);
        }
    });
}
