(function ($) {
    var defaults = {
        apiUrl         : '/admin/app/client',
        dataMap        : {
            clientId       : 'client-id',
            accountId      : 'account-id',
            profileId      : 'profile-id',
            tariffId       : 'tariff-id',
            replaceTariffId: 'replace-tariff-id',
            resultContainer: 'result-container',
            for1c          : 'for1c'
        },
        confirmSelector: '[data-action][data-toggle=confirmation]',
        modalSelector  : '[data-action][data-toggle=modal]',
        modalContainer : '[data-container=modal]',
        template       : {
            loader:
                '<div data-container="loader">' +
                '<span class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span>' +
                '</div>'
        }
    };

    // актуальные настройки, глобальные
    var opt;

    var methods = {

        init: function (params) {
            opt = $.extend({}, defaults, opt, params);
            methods.attachConfirmation(this);
            methods.attachModal(this);
            methods.attachAddButton(this);
            // end init
        },

        route: function (action, param) {
            switch (action) {
                case 'add_tariff_choose':
                    return  'profile/create/template/tariff_choose';

                case 'add_tariff':
                    return 'profile/create/template/tariff/add/' + param.tariffId;

                case 'add_tariff_preview':
                    return 'profile/create/template/tariff/preview/' + param.tariffId;

                default:
                    return action;
            }
        },

        attachAddButton(tariffContainers) {
            $.each(tariffContainers, function (i, e) {

                var $tariffsCont = $(e);
                var addText = $tariffsCont.data('add-text');
                var $header = $tariffsCont.parents('.box.box-primary:first').find('.box-header:first');

                var $addButton = $header.append(
                    '<button type="button" data-action="change_tariff" class="pull-right">' +
                    '   <span class="glyphicon glyphicon-plus-sign"></span>' +
                    '   <span>' + addText + '</span>' +
                    '</button>'
                ).find('[data-action=change_tariff]');

                $addButton.on('click', function () {
                    var param = {
                        clientId : $tariffsCont.data(opt.dataMap.clientId),
                        accountId : $tariffsCont.data(opt.dataMap.accountId),
                        profileId: $tariffsCont.data(opt.dataMap.profileId),
                        for1c    : $tariffsCont.data(opt.dataMap.for1c)
                    };
                    var action = 'add_tariff_choose';

                    var route = methods.route(action, param);
                    var path = methods.path(param.clientId, param.accountId, route);

                    var $container = $('body');

                    methods.loader($container, 'show');

                    $.post(path, param, function (data) {
                        methods.createModal(null, null, methods.responseControl(data));
                    })
                        .fail(methods.errorControl)
                        .always(function () {
                            methods.loader($container, 'hide');
                        });
                });
            });
        },

        attachConfirmation: function (container) {
            $(container).find(opt.confirmSelector).confirmation({onConfirm: methods.confirm});
        },

        attachModal: function (root) {
            // delegate
            $(root).on('click', opt.modalSelector, methods.modal);
        },

        path: function (clientId, accountId, route) {
            return opt.apiUrl + '/' + clientId + '/account/' + accountId + '/' + route;
        },

        getParent: function (e, dataName) {
            return $(e).parents('[data-' + dataName + ']:first');
        },

        getParentParam: function (e, dataName) {
            var p = methods.getParent($(e), dataName);
            return (p) ? p.data(dataName) : null;
        },

        getParam: function (e, dataName) {
            return $(e).data(dataName);
        },

        collectParam: function (e) {
            var result = {};

            $.each(opt.dataMap, function (k, v) {
                result[k] = methods.getParentParam(e, v);
            });

            return result;
        },

        createModal: function (title, content, html) {
            if (html === undefined) {
                html =
                    '<div data-container="modal" class="modal">' +
                    '  <div class="modal-dialog modal-lg">' +
                    '    <div class="modal-content">' +
                    '      <div class="modal-header">' +
                    '        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' +
                    '        <h4 class="modal-title">' + title + '</h4>' +
                    '      </div>' +
                    '      <div class="modal-body">' + content + '</div>' +
                    '      <div class="modal-footer">' +
                    '        <button type="button" class="btn btn-default" data-dismiss="modal">Закрыть</button>' +
                    '      </div>' +
                    '    </div><!-- /.modal-content -->' +
                    '  </div><!-- /.modal-dialog -->' +
                    '</div><!-- /.modal -->';
            }

            $(opt.modalContainer).remove();
            $('body').append(html);

            $(opt.modalContainer).modal();
            $(opt.modalContainer).find('[data-action=add_tariff]').on('click', methods.modalAction);
            $(opt.modalContainer).find('[data-action=change_tariff_preview]').on('input', methods.previewAction);
            $(opt.modalContainer).find('[data-action=add_tariff_preview]').on('input', methods.previewAction);
        },

        previewAction: function () {
            var $this = $(this);

            var param = methods.collectParam($this);
            param.resultContainer = methods.getParam($this, opt.dataMap.resultContainer);
            var action = methods.getParam($this, 'action');
            var route = methods.route(action, param);
            var path = methods.path(param.clientId, param.accountId, route);
            var $tabContainer = methods.getParent($this, 'tariff-id');
            var $tariffOptions = methods.getParent($this, ['container=tariff_options']);

            if ($tariffOptions) {
                param.autoRenewal = ($tariffOptions.find('[data-field=autoRenwal]').prop('checked')) ? 1 : 0;
                var $jobs = $tariffOptions.find('[data-field=jobs]');
                var errclass = 'has-error';
                var $actionButton = $tabContainer.find('button[data-action]');

                if ($jobs.val() < 1) {
                    $jobs.parent().addClass(errclass);
                    $actionButton.prop('disabled', true);
                    return false;
                } else {
                    $jobs.parent().removeClass(errclass);
                    $actionButton.prop('disabled', false);
                }
                param.jobs = $jobs.val();
            }


            var resultCont = methods.getParam($this, 'result-container');
            var selector = $tabContainer.find('[data-container=' + resultCont + ']');

            var $container = $(selector);

            var $profile = $('[data-container=template_tariffs][data-client-id=' + param.clientId + ']');
            param.for1c = $profile.data(opt.dataMap.for1c);

            methods.loader($container, 'show');

            $.post(path, param, function (data) {
                $container.html(methods.responseControl(data));
                methods.attachConfirmation($container);
            })
                .fail(methods.errorControl)
                .always(function () {
                    methods.loader($container, 'hide');
                });
        },

        modalAction: function () {
            var $this = $(this);

            var param = methods.collectParam($this);
            param.resultContainer = methods.getParam($this, opt.dataMap.resultContainer);
            var action = methods.getParam($this, 'action');
            var route = methods.route(action, param);
            var path = methods.path(param.clientId, param.accountId, route);

            var $tariffOptions = methods.getParent($this, ['container=tariff_options']);

            if ($tariffOptions) {
                param.autoRenewal = ($tariffOptions.find('[data-field=autoRenwal]').prop('checked')) ? 1 : 0;
                param.jobs = $tariffOptions.find('[data-field=jobs]').val();
            }

            var selector = '[data-container=template_tariffs][data-client-id=' + param.clientId + ']';

            var $container = $(selector);

            param.for1c = $container.data(opt.dataMap.for1c);

            $(opt.modalContainer).modal('hide');
            $(opt.modalContainer).remove();

            methods.loader($container, 'show');

            $.post(path, param, function (data) {
                $container.prepend(methods.responseControl(data));
                methods.attachConfirmation($container);
            })
                .fail(methods.errorControl)
                .always(function () {
                    methods.loader($container, 'hide');
                });
        },

        modal: function () {
            var $this = $(this);

            var param = methods.collectParam($this);
            param.resultContainer = methods.getParam($this, opt.dataMap.resultContainer);

            var path = methods.path(methods.getParam($this, 'action'));

            var $container = $('body');

            methods.loader($container, 'show');

            $.post(path, param, function (data) {
                methods.createModal(null, null, methods.responseControl(data));
            })
                .fail(methods.errorControl)
                .always(function () {
                    methods.loader($container, 'hide');
                });
        },

        confirm: function () {
            var $this = $(this);

            var param = methods.collectParam($this);
            param.resultContainer = methods.getParam($this, opt.dataMap.resultContainer);

            var action = methods.getParam($this, 'action');
            var $container = methods.getParent($this, 'container=' + param.resultContainer);

            if (action === 'delete_tariff') {
                $container.remove();
                return;
            }

            var route = methods.route(action, param);
            var path = methods.path(param.clientId, param.accountId, route);

            methods.loader($container, 'show');

            $.post(path, param, function (data) {
                $container.html(methods.responseControl(data));
                methods.attachConfirmation($container);
            })
                .fail(methods.errorControl)
                .always(function () {
                    methods.loader($container, 'hide');
                });
        },

        loader: function (container, s) {
            if (s === 'show') {
                container.append(opt.template.loader);
            }
            if (s === 'hide') {
                container.find('[data-container=loader]').remove();
            }
        },

        responseControl: function (data) {
            var unautorizedExp = /\/admin\/login_check/;
            if (unautorizedExp.test(data)) {
                document.location.reload(true);
                return 'Authorisation Error!';
            }

            return data;
        },

        errorControl: function (response) {
            if (response.status === 0) {
                response.responseText = 'No connection to the site. Please refresh this page (Ctrl+r)';
            }
            methods.createModal('Error: ' + response.status + ' - ' + response.statusText, response.responseText);
        }

        //, app methods here
    };

    $.fn.profile_admin_template_tariff = function (method) {
        // Логика вызова метода
        if (methods[method]) {
            return methods[method].apply(this, Array.prototype.slice.call(arguments, 1));
        } else if (typeof method === 'object' || !method) {
            return methods.init.apply(this, arguments);
        } else {
            $.error('Method ' + method + ' not exist');
        }
    };

})(jQuery);
