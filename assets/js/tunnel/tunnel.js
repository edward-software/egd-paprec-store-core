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
            minDate: +1,
            maxDate: "+1M"
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

    if ($('.product-container').is('div')) {


        var navbarOffset = $('.navbar')[0].getBoundingClientRect().top;
        var productOffset = $('.product-container')[0].getBoundingClientRect().top;
        var otherNeedsOffset = $('.other-needs-container')[0].getBoundingClientRect().top;
        var otherNeedsHeight = $('.other-needs-container').height();
        var duration = 350;

        $(window).scroll(function () {
                const scrollTop = $(this).scrollTop();
                if (scrollTop <= navbarOffset) {
                    $('#define-need-button').fadeOut(duration);
                    $('#other-needs-button').fadeOut(duration);
                } else if (scrollTop > navbarOffset && scrollTop < productOffset) {
                    $('#define-need-button').fadeIn(duration);
                    $('#other-needs-button').fadeOut(duration);

                } else if (scrollTop >= productOffset && scrollTop <= ($(document).height() - $(window).height() - (otherNeedsHeight / 2))) {
                    $('#define-need-button').fadeOut(duration);
                    $('#other-needs-button').fadeIn(duration);
                } else if (scrollTop > ($(document).height() - $(window).height() - (otherNeedsHeight / 2))) {
                    $('#other-needs-button').fadeOut(duration);
                }
            }
        );

        $('#define-need-button').on('click', function (e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: productOffset
            }, 750);
        });

        $('#other-needs-button').on('click', function (e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: otherNeedsOffset
            }, 750);
        });
    }

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


    $('[id^=regularFrequencyButton__]').on('click', function () {
        const productId = (this.id).replace('regularFrequencyButton__', '');
        $('#productFrequencyIntervalSelect__' + productId).prop("disabled", false);
        $('#productFrequencyTimesInput__' + productId).prop("disabled", false);
        $('#productFrequencyTimesInput__' + productId).val(1);
    });

    $('[id^=ponctualFrequencyButton__], [id^=unknowFrequencyButton__]').on('click', function () {
        const productId = (this.name).replace('productFrequencyRadios__', '');
        $('#productFrequencyTimesInput__' + productId).val(0);
        $('#productFrequencyTimesInput__' + productId).prop("disabled", true);
        $('#productFrequencyIntervalSelect__' + productId).prop("disabled", true);

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

    $('#catalog_next_step_button').on('click', function () {
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
                $("#devis-recap").append(htmlToDisplay);
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
                    $("#devis-recap").append(htmlToDisplay);
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
        }
    });

    // Désactivation des champs de adresse de facturation quand on sélecitonne facturation identique
    $('input[name*=isSameAddress]').change(function () {
        if (this.value == 1) {
            $('.billing-address-field').prop("disabled", true);
            $('.billing-address-field').val('');
        } else if (this.value == 0) {
            $('.billing-address-field').prop("disabled", false);
        }
    });

    // Désactivation des champs de signatory quand on sélecitonne signataire
    $('input[name*=isSameSignatory]').change(function () {
        if (this.value == 1) {
            $('.signatory-field').prop("disabled", true);
            $('.signatory-field').val('');
        } else if (this.value == 0) {
            $('.signatory-field').prop("disabled", false);
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
            $('#paprec_catalogbundle_quote_request_public_city').val(ui.item.label.substring(ui.item.label.indexOf('-') + 2));
        }
    });

    $('#paprec_catalogbundle_quote_request_public_billingPostalCode').autocomplete({
        source: '' + $('#paprec_catalogbundle_quote_request_public_billingPostalCode').data('url'),
        minLength: 1,
        select: function (event, ui) {
            $('#paprec_catalogbundle_quote_request_public_billingCity').val(ui.item.label.substring(8));
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
    const frequencyTimes = $("#productFrequencyTimesInput__" + productId).val();
    const frequencyInterval = $("#productFrequencyIntervalSelect__" + productId).val();

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
