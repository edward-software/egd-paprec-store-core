'use strict';

import '../../templates/public/scss/global.scss';

global.$ = global.jQuery = require('jquery');
import './plugins/egd-datatable';
import './plugins/egd-datatable-locales';
import 'datatables.net';
import 'datatables.net-buttons';
import 'datatables.net-buttons-bs4';
import 'datatables.net-select';
import 'datatables.net-select-bs4';
import 'datatables.net-bs4';
import 'datatables.net-dt';
import 'datatables.net-rowreorder';
import 'datatables.net-rowreorder-bs4';

const moment =  require('moment');
global.moment = moment;

import 'bootstrap';
import 'datatables.net';
import 'datatables.net-bs4';
import 'datatables.net-buttons';
import 'datatables.net-buttons-bs4';
import 'datatables.net-select';
import 'datatables.net-select-bs4';
import 'datatables.net-rowreorder';
import 'jquery-sortable';
import 'jquery-ui';
import 'jquery-ui/ui/widgets/datepicker';
import 'jquery-ui/ui/i18n/datepicker-fr';
import 'jquery-ui/ui/widgets/sortable';
import 'jquery-ui/ui/widgets/autocomplete';
import 'jquery-ui/ui/disable-selection';
import 'eonasdan-bootstrap-datetimepicker/build/js/bootstrap-datetimepicker.min';
import 'select2/dist/js/select2.min'
import 'bootstrap-select/dist/js/bootstrap-select.min'
import 'bootstrap-select/dist/js/i18n/defaults-fr_FR.min'
import 'bootstrap-select/dist/js/i18n/defaults-en_US.min'

import Swal from 'sweetalert2';
global.Swal = Swal;

import bootbox from 'bootbox';
global.bootbox = bootbox;

/**
 * Création d'un prompt avec une dialog
 */
function loadPrompt(url, prompt) {

    if (prompt.type == 'select') {
        bootbox.prompt({
            title: prompt.title,
            inputType: 'select',
            inputOptions: prompt.choices,
            callback: function (result) {
                if (result !== null) {
                    url = url.replace('__' + prompt.name + '__', result);
                    $(location).attr('href', url);
                }
            }
        });
    } else if (prompt.type == 'checkbox') {
        bootbox.prompt({
            title: prompt.title,
            inputType: 'checkbox',
            inputOptions: prompt.choices,
            callback: function (result) {
                if (result !== null) {
                    url = url.replace('__' + prompt.name + '__', result);
                    $(location).attr('href', url);
                }
            }
        });
    } else if (prompt.type == 'text') {

        var preload = null;
        if (prompt.preload) {
            if (typeof prompt.preload === "function") {
                preload = eval(prompt.preload);
            } else {
                preload = prompt.preload;
            }
            preload = preload.toString().trim();
        }

        bootbox.prompt(
            {
                title: prompt.title,
                value: prompt.preload,
                callback: function (result) {
                    if (result !== null) {
                        url = url.replace('__' + prompt.name + '__', result);
                        if (prompt.ajax) {
                            $.ajax({
                                'url': url,
                                'method': 'get'
                            }).done(function (data) {
                                if (data.error) {
                                    bootbox.alert(data.error);
                                } else {
                                    if (prompt.callback) {
                                        eval(prompt.callback(result));
                                    }
                                }

                            });
                        } else {
                            $(location).attr('href', url);
                        }
                    }
                }
            }
        );

    } else if (prompt.type == 'custom') {

        var originalModalClassName = prompt.modalClassName;

        $.ajax({
            'url': url,
            'method': 'get'
        }).done(function (data) {
            /**
             * Gestion de la taille, celle proposé par bootbox et les customizable en plus
             * Par défaut c'est medium
             */
            var standardSize = null;
            if (prompt.size == 'small' || prompt.size == 'large' || prompt.size == 'medium') {
                standardSize = prompt.size;
            } else if (prompt.size == 'xlarge') {
                /**
                 * Taille rajoutée, on utilise le className pour ajouter une classe spécifique
                 */
                prompt.modalClassName = prompt.modalClassName + ' modal-custom-width-xlarge';
            }

            // For Button identification
            var submitButtonClassName = 'submit-button-' + Math.random().toString(36).substring(7);
            var cancelButtonClassName = 'cancel-button-' + Math.random().toString(36).substring(7);

            var dialog = bootbox.dialog({
                title: prompt.title,
                size: standardSize,
                className: prompt.modalClassName,
                message: data,
                buttons: {
                    cancel: {
                        label: prompt.cancelButton.label,
                        className: cancelButtonClassName + ' ' + prompt.cancelButton.className,
                        callback: prompt.cancelButton.callback
                    },
                    submit: {
                        label: prompt.submitButton.label,
                        className: submitButtonClassName + ' ' + prompt.submitButton.className,
                        callback: function () {
                            var tmp = $('.' + submitButtonClassName).html();
                            $('.' + submitButtonClassName).addClass('button-loading').addClass('disabled').html('<i class="fa fa-refresh fa-spin"></i>' + tmp).prop('disabled', true);
                            $('.' + cancelButtonClassName).addClass('disabled').prop('disabled', true);
                            /**
                             * Dans le cas ou il y a un CKEditor dans le form il faut faire une manip spéciale
                             */
                            if ("undefined" !== typeof CKEDITOR && CKEDITOR.instances) {
                                for (var instanceName in CKEDITOR.instances) {
                                    CKEDITOR.instances[instanceName].updateElement();
                                }
                            }

                            var data = null;
                            var processData = true;
                            var contentType = 'application/x-www-form-urlencoded';

                            // if (prompt.other == 'picture') {
                            data = new FormData($(prompt.formName)[0]);
                            processData = false;
                            contentType = false;
                            // } else {
                            //     data = $(prompt.formName).serialize();
                            // }
                            // console.log(data);

                            $.ajax({
                                'url': url,
                                'method': 'post',
                                'data': data,
                                'processData': processData,
                                'contentType': contentType
                            }).done(function (data) {
                                if (data.id || data.resultCode == 1) {
                                    /**
                                     * Le retour est en Json
                                     */
                                    dialog.modal('hide');
                                    if (prompt.submitButton.callback) {
                                        prompt.submitButton.callback(data);
                                    }
                                } else {

                                    /**
                                     * Si c'est pas du json c'est qu'il y a une erreur dans mon formulaire et que symofny me le renvoi
                                     */
                                    $('.' + prompt.modalClassName).find('.bootbox-body').html(data.data);
                                    var tmp = $('.' + submitButtonClassName).html().replace('<i class="fa fa-refresh fa-spin"></i>', '');
                                    $('.' + submitButtonClassName).removeClass('button-loading').removeClass('disabled').html(tmp).prop('disabled', false);
                                    $('.' + cancelButtonClassName).removeClass('disabled').prop('disabled', false);
                                }

                            });
                            return false;
                        }
                    }
                }
            }).on('shown.bs.modal', function () {
                /**
                 * Necessaire pour que select2 fonctionne dans le bootbox
                 */
                dialog.removeAttr("tabindex");

                // $('.' + originalModalClassName + ' .modal-content').draggable();
            });


        });

    }
}

global.loadPrompt = loadPrompt;

function optionTextToObject(optionText) {
    /**
     * Transformation d'un tableau json envoyé dans un data-options par exemple en objet JS
     */
    var options = {};
    var options = eval('[' + optionText + ']');
    if ($.isPlainObject(options[0])) {
        options = $.extend({}, options[0]);
    }
    return options;
}

global.optionTextToObject = optionTextToObject;

$(function () {
    /**
     * Intercepte les boutons de vue qui ont besoin d'un prompt
     * if faut simplement que le bouton ait la classe : view-prompt-button
     * et que data-url = l'url de l'action
     * et que data-prompt = {
     *      name : nom de la variable
     *      title: question du prompt
     *      type: type de prompt (cf. bootbox)
     *      choices: choix possible, pour les types select et checkbox,
     *      ClassName: précise le nom de la classe appliquer à la modal (sans le '.') (pour le custom)
     *      formName: "#projectToolBoardIdaItemForm",
     *      cancelButton:  (pour le custom)
     *          label: libellé du bouton d'annulation
     *          className: class du bouton d'annulation, exemple : btn-danger
     *          callback: fonction de callback après annulation
     *      submitButton:  (pour le custom)
     *          label: libellé du bouton de submit
     *          className: class du bouton d'annulation, exemple : btn-success
     *          callback: fonction de callback après soumission
     */
    $(document).on('click', '.view-prompt-button', function (e) {

        e.preventDefault();
        e.stopPropagation();
        var url = $(this).data('url');
        var prompt = optionTextToObject($(this).data('prompt'));

        if (prompt.stopPropagation) {
            e.stopPropagation();
        }

        /**
         * Appel de la fonction qui charge le prompt
         */
        loadPrompt(url, prompt);

    });

});

