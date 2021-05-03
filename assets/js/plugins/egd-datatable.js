/**
 *
 *
 * Générateur de datatable
 *
 * Exemple d'utilisation
 *
 *           $('#userListInWorkshop').EgdDatatable({
              rowPrefix: 'uIn_',
              url: "{{ path('egd_workshop_workshop_loadUserList', {'id': workshop.id, 'option': 'in'}) }}",
              columns: [
                  {"data": "id", "width": "5%"},
                  {"data": "firstName"},
                  {"data": "lastName"},
                  {"data": "companyName"},
                  {"data": "id", "width": "10%", "bSortable": false, "className": "cell-action-button"}
              ],
              buttons: [
                  {
                      label: "{{ 'General.RemoveManyUser'|trans }}",
                      type: 'ajaxMany',
                      target: '#userListOutWorkshop',
                      url: "{{ path("egd_workshop_workshop_setUsers", {'id': workshop.id, 'option': 'out', userIds: '__rowIds__'}) }}",
                      confirmMessage: "{{ 'General.Confirm-remove'|trans }}",
                      className: 'btn-xs'
                  }
              ],
              rowDblclick: {
                  type: 'ajax',
                  target: '#userListOutWorkshop',
                  url: "{{ path("egd_workshop_workshop_setUser", {'id': workshop.id, 'option': 'out', userId: '__rowId__'}) }}",
                  confirmMessage: "{{ 'Workshop.Workshop.Confirm-remove'|trans }}"
              },
              egdDatatableRowButtons: [
                  {
                      label: "{{ 'Workshop.Workshop.View'|trans }}",
                      className: "btn btn-primary btn-xs",
                      icon: "fa fa-eye",
                      url: "{{ path('egd_user_user_view', {id: '__rowId__'}) }}",
                      confirmMessage: "{{ 'General.Confirm-open'|trans }}"
                  }
              ],
              contextMenu: [
                  {
                      key: "view",
                      label: "{{ 'Workshop.Workshop.View'|trans }}",
                      icon: "fa-search",
                      type: "open",
                      url: "{{ path('egd_user_user_view', {id: '__rowId__'}) }}"
                  },
                  {
                      key: "edit",
                      label: "{{ 'Workshop.Workshop.Edit'|trans }}",
                      icon: "edit",
                      type: "open",
                      url: "{{ path('egd_user_user_view', {id: '__rowId__'}) }}"
                  },
                  {
                      key: "separator"
                  },
                  {
                      key: "remove",
                      label: "{{ 'Workshop.Workshop.Remove'|trans }}",
                      icon: "delete",
                      type: "ajax",
                      target: '#userListOutWorkshop',
                      url: "{{ path("egd_workshop_workshop_setUser", {'id': workshop.id, 'option': 'out', userId: '__rowId__'}) }}",
                      confirmMessage: "{{ 'General.Confirm-remove'|trans }}"

                  }
              ]

          });
 *
 */
/**
 * Cache mémoire global des boutons de ligne
 */
var egdDatatableRowButtons = new Array();

(function ($) {
        'use strict';

        $.fn.EgdDatatable = function (options) {

            var myJqueryObject = this;
            var myList;

            var settings = $.extend({
                order: [[0, "asc"]],
                lengthMenu: [[50, 100, 200, 500], [50, 100, 200, 500]],
                rowPrefix: 'row_',
                processing: true,
                locale: 'fr'
            }, options);

            var dom = 'flrtip';
            /**
             * Génération du tableau de boutons
             */
            egdDatatableRowButtons[settings.rowPrefix] = new Array();
            var buttons = new Array();
            if (settings.buttons) {
                $.each(settings.buttons, function () {
                    var item = this;
                    var tmp = new Object();

                    tmp.text = item.label;
                    tmp.className = item.className;

                    if (item.type == 'ajaxMany') {
                        /**
                         * Bouton permettant d'executer une requete ajax sur plusieurs lignes et de reloader une target
                         */
                        tmp.action = function (e, dt) {

                            if (dt.rows({selected: true}).count() > 0) {
                                var rowIds = [];
                                $.each(dt.rows({selected: true}).data(), function () {
                                    rowIds.push(this.id);
                                });
                                var url = item.url.replace('__rowIds__', rowIds.join(','));
                                executeAction(myList, item, url);
                            }


                        };
                    } else if (item.type == 'ajax') {
                        tmp.action = function (e, dt) {

                            if (dt.rows({selected: true}).count() > 0) {
                                var rowIds = [];
                                $.each(dt.rows({selected: true}).data(), function () {
                                    rowIds.push(this.id);
                                });
                                var url = item.url.replace('__rowId__', rowIds[0]);
                                executeAction(myList, item, url);
                            }


                        };
                    }
                    /**
                     * TODO : les autres types de bouton
                     */

                    buttons.push(tmp);
                });

            }

            if (buttons && buttons.length > 0) {
                dom = 'B' + dom;
            }

            /**
             * Génération du context menu items
             */
            var contextMenuItems = {};
            var separatorNb = 0;
            if (settings.contextMenu) {
                $.each(settings.contextMenu, function () {

                    if (this.key == 'separator') {
                        contextMenuItems['sep' + separatorNb] = "---------";
                        separatorNb++;
                    } else {
                        contextMenuItems[this.key] = {
                            name: this.label,
                            icon: this.icon
                        };
                    }

                });
            }

            myList = myJqueryObject.DataTable({
                language: datatableLabels[settings.locale],
                dom: dom,
                buttons: buttons,
                order: settings.order,
                select: true,
                processing: settings.processing,
                serverSide: true,
                lengthMenu: settings.lengthMenu,
                ajax: {
                    url: settings.url,
                    type: 'POST',
                    data: {
                        rowPrefix: settings.rowPrefix
                    }
                },
                columns: settings.columns,
                rowCallback: function (nRow, aData, displayIndex) {

                    if (settings.rowButtons) {
                        var htmlButtons = '';
                        $.each(settings.rowButtons, function (i) {
                            var item = this;
                            /**
                             * Enregistrement en cache de la configuration des boutons
                             */
                            egdDatatableRowButtons[settings.rowPrefix][i] = item;

                            var isValid = true;
                            var disabled = false;

                            if (item.condition) {
                                isValid = item.condition(aData);
                            }

                            if (isValid) {
                                if (item.type == 'ajax') {

                                    if (item.confirmMessage) {
                                        htmlButtons += '<a href="javascript:void(0);" data-list="#' + myJqueryObject.attr('id') + '" data-text="' + item.confirmMessage + '" data-url="' + item.url.replace('__rowId__', aData['id']) + '" data-target="' + item.target + '" title="' + item.label + '" class="egd-datatable-confirm-ajax-button ' + item.className + '"><i class="' + item.icon + '"></i></a>';
                                    } else if (item.prompt) {
                                        htmlButtons += '<a href=javascript:void(0);" data-list="#' + myJqueryObject.attr('id') + '" data-url="' + item.url.replace('__rowId__', aData['id']) + '" title="' + item.label + '" data-index="' + i + '" data-prefix="' + settings.rowPrefix + '" class="egd-datatable-prompt-button ' + item.className + '"><i class="' + item.icon + '"></i></a>';
                                    } else if (item.modal) {
                                        htmlButtons += '<a href=javascript:void(0);" data-list="#' + myJqueryObject.attr('id') + '" data-url="' + item.url.replace('__rowId__', aData['id']) + '" title="' + item.label + '" data-index="' + i + '" data-prefix="' + settings.rowPrefix + '" class="egd-datatable-modal-button ' + item.className + '"><i class="' + item.icon + '"></i></a>';
                                    } else {
                                        htmlButtons += '<a href=javascript:void(0);" data-list="#' + myJqueryObject.attr('id') + '" data-url="' + item.url.replace('__rowId__', aData['id']) + '" data-target="' + item.target + '" title="' + item.label + '" class="egd-datatable-ajax-button ' + item.className + '"><i class="' + item.icon + '"></i></a>';
                                    }

                                } else {
                                    // open par defaut

                                    if (item.confirmMessage) {
                                        htmlButtons += '<a href="javascript:void(0);" data-text="' + item.confirmMessage + '" data-url="' + item.url.replace('__rowId__', aData['id']) + '" title="' + item.label + '" class="egd-datatable-confirm-button ' + item.className + '"><i class="' + item.icon + '"></i></a>';
                                    } else {
                                        htmlButtons += '<a href="' + item.url.replace('__rowId__', aData['id']) + '" title="' + item.label + '" class="' + item.className + '"><i class="' + item.icon + '"></i></a>';
                                    }

                                }
                            } else if (item.classNameDisabled) {
                                htmlButtons += '<a href="javascript:void(0);" title="' + item.label + '" class="' + item.classNameDisabled + '"><i class="' + item.icon + '"></i></a>';
                            }

                        });
                        $(nRow).find('.cell-action-button').html(htmlButtons);
                    }

                    /**
                     * Permet d'effectuer des modifications dynamique sur le contenu d'une cellule
                     */
                    if (settings.formatColumns) {
                        /**
                         * Formatage spécifique des colonnes
                         */
                        $.each(settings.formatColumns, function (i) {
                            $(nRow).find('.' + this.targetClassName).html(this.format(aData));
                        });
                    }

                    /**
                     * Permet d'effectuer des modifications dynamique sur la ligne complete.
                     */
                    if (settings.formatRow) {
                        settings.formatRow(nRow, aData);
                    }
                },
                drawCallback: function (settings) {
                    if (myList.data().length > 0 && settings.contextMenu) {

                        $.contextMenu({
                            selector: '#' + myJqueryObject.attr('id') + " tbody tr",
                            events: {
                                show: function () {
                                    myList.row('#' + this.prop('id')).select();
                                }
                            },
                            callback: function (key, options) {
                                var myRow = this;
                                var rowIds = new Array();
                                myJqueryObject.find('tbody tr.selected').each(function () {
                                    rowIds.push($(this).attr('id').replace(settings.rowPrefix, ''));
                                });

                                $.each(settings.contextMenu, function () {
                                    var item = this;

                                    if (item.key == key) {

                                        myJqueryObject.find('tbody tr.selected').removeClass('selected');
                                        myList.row('#' + myRow.prop('id')).select();
                                        var rowId = myRow.prop('id').replace(settings.rowPrefix, '');
                                        var url = item.url.replace('__rowId__', rowId);
                                        executeAction(myList, item, url);

                                    }

                                });

                            },
                            items: contextMenuItems
                        });
                    }
                },
                "fnRowCallback": function (nRow, aData, iDisplayIndex, iDisplayIndexFull) {
                },
                "fnDrawCallback": function (oSettings) {


                }
            });

            /**
             * Gestion du double clic sur une ligne
             */
            if (settings.rowDblclick) {


                this.find('tbody').on('dblclick', 'tr', function (event) {
                    var rowId = $(this).attr('id').replace(settings.rowPrefix, '');
                    var item = settings.rowDblclick;
                    var url = item.url.replace('__rowId__', rowId);
                    executeAction(myList, item, url);
                })
                ;
            }
        }
        ;


        function executeAction(myList, item, url) {

            if (item.type == 'ajax' || item.type == 'ajaxMany') {
                /**
                 * Bouton permettant d'éxecuter une requete ajax et de recharger une datatable target
                 */
                if (item.confirmMessage) {
                    /**
                     * Demande de confirmation avant l'execution
                     */
                    Swal.fire({
                        title: item.label,
                        html: item.confirmMessage,
                        showCancelButton: true,
                        cancelButtonText: item.buttons.cancel.label,
                        confirmButtonText: item.buttons.confirm.label,
                        confirmButtonColor: "#dc3545",
                        reverseButtons: true
                    }).then(function (result) {
                        if (result.value === true) {
                            $.getJSON(url)
                                .done(function (data) {
                                    if (data.resultCode == 1) {
                                        if ($.isArray(item.target)) {
                                            $.each(item.target, function () {
                                                $(this).DataTable().ajax.reload();
                                            })
                                        } else {
                                            $(item.target).DataTable().ajax.reload();
                                        }
                                        myList.ajax.reload();

                                        if (item.callback) {
                                            item.callback(data);
                                        }
                                    } else {
                                        Swal.fire(data.resultMessage);
                                    }
                                })
                                .fail(function () {
                                    Swal.fire('System Error ! 1');
                                });
                        }
                    });






                    // bootbox.confirm({
                    //     message: item.confirmMessage,
                    //     buttons: item.buttons,
                    //     callback: function (result) {
                    //         if (result === true) {
                    //             $.getJSON(url)
                    //                 .done(function (data) {
                    //                     if (data.resultCode == 1) {
                    //                         if ($.isArray(item.target)) {
                    //                             $.each(item.target, function () {
                    //                                 $(this).DataTable().ajax.reload();
                    //                             })
                    //                         } else {
                    //                             $(item.target).DataTable().ajax.reload();
                    //                         }
                    //                         myList.ajax.reload();
                    //
                    //                         if (item.callback) {
                    //                             item.callback(data);
                    //                         }
                    //                     } else {
                    //                         bootbox.alert(data.resultMessage);
                    //                     }
                    //                 })
                    //                 .fail(function () {
                    //                     bootbox.alert('System Error ! 1');
                    //                 });
                    //         }
                    //     }
                    // });
                } else if (item.prompt) {
                    /**
                     * Remplissage d'un formulaire avant execution
                     */
                    var prompt = item.prompt;
                    if (prompt.type == 'select') {
                        bootbox.prompt({
                            title: prompt.title,
                            inputType: 'select',
                            inputOptions: prompt.choices,
                            callback: function (result) {
                                if (result !== null) {
                                    $.getJSON(url.replace('__' + prompt.name + '__', result))
                                        .done(function (data) {
                                            if (data.resultCode == 1) {
                                                if ($.isArray(item.target)) {
                                                    $.each(item.target, function () {
                                                        $(this).DataTable().ajax.reload();
                                                    })
                                                } else {
                                                    $(item.target).DataTable().ajax.reload();
                                                }
                                                myList.ajax.reload();
                                            } else {
                                                bootbox.alert(data.resultMessage);
                                            }
                                        })
                                        .fail(function () {
                                            bootbox.alert('System Error ! 2');
                                        });
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
                                    $.getJSON(url.replace('__' + prompt.name + '__', result))
                                        .done(function (data) {
                                            if (data.resultCode == 1) {
                                                if ($.isArray(item.target)) {
                                                    $.each(item.target, function () {
                                                        $(this).DataTable().ajax.reload();
                                                    })
                                                } else {
                                                    $(item.target).DataTable().ajax.reload();
                                                }
                                                myList.ajax.reload();
                                            } else {
                                                bootbox.alert(data.resultMessage);
                                            }
                                        })
                                        .fail(function () {
                                            bootbox.alert('System Error ! 3');
                                        });
                                }
                            }
                        });
                    } else if (prompt.type == 'text') {
                        bootbox.prompt(prompt.title, function (result) {
                                $.getJSON(url.replace('__' + prompt.name + '__', result))
                                    .done(function (data) {
                                        if (data.resultCode == 1) {
                                            if ($.isArray(item.target)) {
                                                $.each(item.target, function () {
                                                    $(this).DataTable().ajax.reload();
                                                })
                                            } else {
                                                $(item.target).DataTable().ajax.reload();
                                            }
                                            myList.ajax.reload();
                                        } else {
                                            bootbox.alert(data.resultMessage);
                                        }
                                    })
                                    .fail(function () {
                                        bootbox.alert('System Error ! 4');
                                    });
                            }
                        );

                    } else if (prompt.type == 'custom') {

                        // For Button identification
                        var submitButtonClassName = 'submit-button-' + Math.random().toString(36).substring(7);
                        var cancelButtonClassName = 'cancel-button-' + Math.random().toString(36).substring(7);

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

                        $.ajax({
                            'url': url,
                            'method': 'get'
                        }).done(function (data) {
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

                                            $.ajax({
                                                'url': url,
                                                'method': 'post',
                                                'data': $(prompt.formName).serialize()
                                            }).done(function (data) {
                                                if (data.id || data.resultCode == 1) {
                                                    /**
                                                     * Le retour est en Json
                                                     */
                                                    dialog.modal('hide');
                                                    if (prompt.submitButton.callback) {
                                                        prompt.submitButton.callback(data);
                                                    }
                                                    if (prompt.target) {
                                                        if ($.isArray(prompt.target)) {
                                                            $.each(prompt.target, function () {
                                                                $(this).DataTable().ajax.reload();
                                                            })
                                                        } else {
                                                            $(prompt.target).DataTable().ajax.reload();
                                                        }
                                                    }
                                                    if (!myList.ajax) {
                                                        myList.DataTable().ajax.reload();
                                                    } else {
                                                        myList.ajax.reload();
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
                            });
                        });

                    }

                } else if (item.formBlock) {
                    /**
                     * Permet de loader un formulaire dans un block existant, cf form-block
                     */
                    var target = item.formBlock;
                    /**
                     * Récupération de l'animation éventuellement paramétrés sur le block
                     */
                    var animation = $(target).data('animation-in');

                    startLoading();
                    $.ajax({
                        'url': url,
                        'method': 'get'
                    }).done(function (data) {
                        stopLoading();
                        $(target).html(data);
                        if (animation) {
                            eval('$("' + target + '").' + animation);
                        } else {
                            $(target).show();
                        }

                    });
                } else if (item.modal) {
                    var modal = item.modal;

                    /**
                     * Gestion de la taille, celle proposé par bootbox et les customizable en plus
                     * Par défaut c'est medium
                     */
                    var standardSize = null;
                    if (modal.size == 'small' || modal.size == 'large' || modal.size == 'medium') {
                        standardSize = modal.size;
                    } else if (modal.size == 'xlarge') {
                        /**
                         * Taille rajoutée, on utilise le className pour ajouter une classe spécifique
                         */
                        modal.modalClassName = modal.modalClassName + ' modal-custom-width-xlarge';
                    }

                    /**
                     * Génération des boutons de la modal
                     */
                    var buttons = {};
                    if (modal.buttons) {
                        $.each(modal.buttons, function (i) {
                            var button = this;
                            buttons[button.name] = button;

                        });
                    }

                    $.ajax({
                        'url': url,
                        'method': 'get'
                    }).done(function (data) {
                        var dialog = bootbox.dialog({
                            title: modal.title,
                            className: modal.modalClassName,
                            size: standardSize,
                            message: data,
                            buttons: buttons
                        });
                        if (modal.onClose) {
                            /**
                             * Si précisé, une fonction à executer à la fermeture de la modal
                             */
                            dialog.on('hidden.bs.modal', modal.onClose);
                        }
                    });
                } else {
                    $.getJSON(url)
                        .done(function (data) {
                            if (data.resultCode == 1) {
                                $(item.target).DataTable().ajax.reload();
                                myList.ajax.reload();

                                if (item.callback) {
                                    item.callback(data);
                                }
                            } else {
                                bootbox.alert(data.resultMessage);
                            }
                        })
                        .fail(function () {
                            bootbox.alert('System Error ! 5');
                        });
                }
            } else if (item.type == 'open') {
                /**
                 * Bouton permettant de d'ouvrir une nouvelle page, avec confirmation possible
                 */
                if (item.confirmMessage) {
                    bootbox.confirm(item.confirmMessage, function (result) {
                        if (result === true) {
                            $(location).attr('href', url);
                        }
                    });
                } else {
                    $(location).attr('href', url);
                }
            }
        }


        $(document).on('click', '.egd-datatable-confirm-button', function (e) {
            e.preventDefault();
            var url = $(this).data('url');
            var text = $(this).data('text');

            bootbox.confirm(text, function (result) {
                if (result === true) {
                    $(location).attr('href', url);
                }
            });

        });

        $(document).on('click', '.egd-datatable-confirm-ajax-button', function (e) {
            e.preventDefault();
            var text = $(this).data('text');
            var url = $(this).data('url');
            var target = $(this).data('target');
            var list = $(this).data('list');


            Swal.fire({
                // title: text,
                html: text,
                showCancelButton: true,
                cancelButtonText: 'Non',
                confirmButtonText: 'Oui',
                confirmButtonColor: "#dc3545",
                reverseButtons: true
            }).then(function (result) {
                if (result.value === true) {
                    $.getJSON(url)
                        .done(function (data) {
                            if (data.resultCode == 1) {
                                if (target && target != 'undefined') {
                                    $(target).DataTable().ajax.reload();
                                }
                                $(list).DataTable().ajax.reload();
                            } else {
                                Swal.fire(data.resultMessage);
                            }
                        })
                        .fail(function () {
                            Swal.fire('System Error ! 1');
                        });
                }
            });



            // bootbox.confirm(text, function (result) {
            //
            //     if (result === true) {
            //         $.getJSON(url)
            //             .done(function (data) {
            //                 if (data.resultCode == 1) {
            //                     if (target && target != 'undefined') {
            //                         $(target).DataTable().ajax.reload();
            //                     }
            //                     $(list).DataTable().ajax.reload();
            //                 } else {
            //                     bootbox.alert(data.resultMessage);
            //                 }
            //             })
            //             .fail(function () {
            //                 bootbox.alert('System Error ! 6');
            //             });
            //     }
            // });

        });

        $(document).on('click', '.egd-datatable-ajax-button', function (e) {
            e.preventDefault();
            var url = $(this).data('url');
            var target = $(this).data('target');
            var list = $(this).data('list');

            $.getJSON(url).done(function (data) {
                if (data.resultCode == 1) {
                    if (target && target != 'undefined') {
                        $(target).DataTable().ajax.reload();
                    }
                    $(list).DataTable().ajax.reload();
                } else {
                    bootbox.alert(data.resultMessage);
                }
            })
                .fail(function () {
                    bootbox.alert('System Error ! 7');
                });

        });

        $(document).on('click', '.egd-datatable-prompt-button', function (e) {
            e.preventDefault();
            var url = $(this).data('url');
            var item = egdDatatableRowButtons[$(this).data('prefix')][$(this).data('index')];
            var list = $($(this).data('list'));
            executeAction(list, item, url);
        });

        $(document).on('click', '.egd-datatable-modal-button', function (e) {
            e.preventDefault();
            var url = $(this).data('url');
            var item = egdDatatableRowButtons[$(this).data('prefix')][$(this).data('index')];
            var list = $($(this).data('list'));
            executeAction(list, item, url);
        });

    }
    (jQuery)
)
;
