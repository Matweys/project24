(function (root, factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        define(['jquery'], factory);
    } else if (typeof exports === 'object') {
        module.exports = factory(require('jquery'));
    } else {
        root.ListAction = factory(root.jQuery);
    }
}(typeof self !== 'undefined' ? self : this, function ($) {
    'use strict';

    function ListAction(options) {

        var _options,
            i,
            k,
            list;

        this.change = function(e) {
            if ($(_options.selectCheckbox + ':checked').length) {
                $(_options.actionButton).show();
            } else {
                $(_options.actionButton).hide();
            }
        };

        this.click = function(e) {
            var action,
                form,
                ref,
                selected;

            selected = $(_options.selectCheckbox + ':checked').length;

            if (!selected) {
                alert(_options.message);
                return false;
            }

            ref = e.currentTarget || e.srcElement;
            action = ref.id;

            if (ref && action && _options.confirmation) {
                ref = _options.confirmation[action];

                if (ref) {
                    if (!confirm(ref)) {
                        return false;
                    }
                }
            }

            form = $(_options.actionForm);
            $(_options.actionField, form).val(action);

            $(_options.selectCheckbox, form).remove();
            $(_options.selectCheckbox + ':checked').each(function() {
                form.append($(this).clone());
            });

            form.submit();

            return false;
        };

        _options = {
            actionButton: '.js-action-btn',
            actionField: '#js-action-input',
            actionForm: '.js-action-form',
            message: 'Выберите записи',
            selectCheckbox: '.js-action-checkbox',
        };

        for (k in options) {
            if (options.hasOwnProperty(k)) {
                _options[k] = options[k];
            }
        }

        list = document.querySelectorAll(_options.actionButton);

        for (i = 0; i < list.length; i++) {
            list[i].onclick = this.click;
        }
    }

    return ListAction;
}));
