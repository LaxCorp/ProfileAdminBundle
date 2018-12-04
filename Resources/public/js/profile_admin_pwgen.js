(function ($) {
    // переключатель табов профилей
    var defaults = {
        password_lenght: 15
    };

    // актуальные настройки, глобальные
    var opt;

    var methods = {

        init: function (params) {
            opt = $.extend({}, defaults, opt, params);
            $(this).on('click', methods.generate);
            // end init
        },

        generate: function () {
            var resultId = $(this).data('result-id');
            var $field =  $('#'+resultId);
            var password = $.passGen({
                'length'   : opt.password_lenght,
                'numeric'  : true,
                'lowercase': true,
                'uppercase': true,
                'special'  : false
            });

            $field.val(password);
        }

        //, app methods here
    };

    $.fn.profile_admin_pwgen = function (method) {
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

