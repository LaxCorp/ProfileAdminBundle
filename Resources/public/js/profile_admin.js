(function ($) {
    var defaults = {
        apiUrl               : '/admin/app/client',
        dataMap              : {
            clientId       : 'client-id',
            profileId      : 'profile-id',
            tariffId       : 'tariff-id',
            replaceTariffId: 'replace-tariff-id',
            resultContainer: 'result-container'
        },
        confirmSelector      : '[data-action][data-toggle=confirmation]',
        modalSelector        : '[data-action][data-toggle=modal]',
        showPasswordSelectors: {
            container      : '[data-container=show_password]',
            button         : '[data-toggle=show_password]',
            dataIcon       : 'icon',
            dataIconPressed: 'icon-pressed'
        },
        modalContainer       : '[data-container=modal]',
        template             : {
            loader:
                '<div data-container="loader">' +
                '<span class="glyphicon glyphicon-refresh glyphicon-refresh-animate"></span>' +
                '</div>',
            flash :
                '<div class="alert alert-warning alert-dismissable">' +
                '<button type="button" class="close" data-dismiss="alert" aria-hidden="true" aria-label="Close">×</button>' +
                '{{ message }}' +
                '</div>'
        },
        sonataContentSelector: 'div.content-wrapper section.content'
    };

    // актуальные настройки, глобальные
    var opt;

    var methods = {

        init: function (params) {
            opt = $.extend({}, defaults, opt, params);
            methods.attachConfirmation(this);
            methods.attachModal(this);
            methods.attachShowPassword(this);
            // end init
        },

        route: function (action, param) {
            switch (action) {
                case 'add_tariff_choose':
                    return 'add/tariff/choose';

                case 'change_tariff_choose':
                    return 'tariff/' + param.tariffId + '/change/choose';

                case 'change_tariff_preview':
                    return 'tariff/' + param.tariffId + '/change/preview/' + param.replaceTariffId;

                case 'change_tariff':
                    return 'tariff/' + param.tariffId + '/change/' + param.replaceTariffId;

                case 'undo_change_tariff':
                    return 'tariff/' + param.tariffId + '/change/undo';

                case 'add_tariff':
                    return 'add/tariff/' + param.tariffId;

                case 'add_tariff_preview':
                    return 'add/tariff/preview/' + param.tariffId;

                case 'auto_renewal_on':
                    return 'tariff/' + param.tariffId + '/auto_renewal/on';

                case 'auto_renewal_off':
                    return 'tariff/' + param.tariffId + '/auto_renewal/off';

                case 'delete_tariff':
                    return 'tariff/' + param.tariffId + '/delete';

                case 'delete_profile':
                    return 'delete';

                case 'enable_profile':
                    return 'enable';

                case 'disable_profile':
                    return 'disable';

                default:
                    return action;
            }
        },

        attachShowPassword: function (root) {
            return $(root).on('click', opt.showPasswordSelectors.button, methods.showPassword);
        },

        showPassword: function () {
            var $button = $(this);
            var $container = $button.parents(opt.showPasswordSelectors.container + ':first');
            var $input = $container.find('input:first');
            var $buttonIcon = $button.find(':first-child');

            var icon = $button.data(opt.showPasswordSelectors.dataIcon);
            var pressedIcon = $button.data(opt.showPasswordSelectors.dataIconPressed);

            var isShowed = ($input.attr('type') === 'text');

            $input.attr('type', (isShowed) ? 'password' : 'text');

            if(isShowed){
                $buttonIcon.addClass(icon).removeClass(pressedIcon);
            }else{
                $buttonIcon.addClass(pressedIcon).removeClass(icon);
            }
        },

        addFlash: function (message) {
            var $sonataContent = $(opt.sonataContentSelector);
            var flashMessage = opt.template.flash.replace(/{{ message }}/, message);
            $sonataContent.prepend(flashMessage);
        },

        attachConfirmation: function (container) {
            $(container).find(opt.confirmSelector).confirmation({onConfirm: methods.confirm});
        },

        attachModal: function (root) {
            // delegate
            $(root).on('click', opt.modalSelector, methods.modal);
        },

        path: function (clientId, profileId, route) {
            return opt.apiUrl + '/' + clientId + '/profile/' + profileId + '/' + route;
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
                    '        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' +
                    '      </div>' +
                    '    </div><!-- /.modal-content -->' +
                    '  </div><!-- /.modal-dialog -->' +
                    '</div><!-- /.modal -->';
            }

            $(opt.modalContainer).remove();
            $('body').append(html);

            $(opt.modalContainer).modal();
            $(opt.modalContainer).find('[data-action=change_tariff]').on('click', methods.modalAction);
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
            var path = methods.path(param.clientId, param.profileId, route);
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
            var path = methods.path(param.clientId, param.profileId, route);

            var $tariffOptions = methods.getParent($this, ['container=tariff_options']);

            if ($tariffOptions) {
                param.autoRenewal = ($tariffOptions.find('[data-field=autoRenwal]').prop('checked')) ? 1 : 0;
                param.jobs = $tariffOptions.find('[data-field=jobs]').val();
            }

            var selector = '[data-container=tariffs]';

            if (action === 'change_tariff') {
                selector += ' [data-container=tariff][data-tariff-id=' + param.tariffId + ']';
            }

            var $container = $(selector);

            $(opt.modalContainer).modal('hide');
            $(opt.modalContainer).remove();

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

        modal: function () {
            var $this = $(this);

            var param = methods.collectParam($this);
            param.resultContainer = methods.getParam($this, opt.dataMap.resultContainer);
            var action = methods.getParam($this, 'action');
            var route = methods.route(action, param);
            var path = methods.path(param.clientId, param.profileId, route);
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
            var route = methods.route(action, param);
            var path = methods.path(param.clientId, param.profileId, route);

            var $container = (param.resultContainer)
                ? methods.getParent($this, 'container=' + param.resultContainer)
                : $('body');

            methods.loader($container, 'show');

            $.post(path, param, function (data) {
                if (!param.resultContainer) {
                    methods.addFlash(data);
                    document.location.href = opt.apiUrl + '/' + param.clientId + '/profile/list';
                    return;
                }
                if (action === 'delete_profile') {
                    methods.addFlash(data);
                    $container.remove();
                    return;
                }
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

    $.fn.profile_admin = function (method) {
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

$(function () {
    $('[data-container=profiles]').profile_admin();
});
