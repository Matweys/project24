(function(root, factory) {
    'use strict';
    if (typeof define === 'function' && define.amd) {
        define(factory);
    } else if (typeof exports === 'object') {
        module.exports = factory(root);
    } else {
        root.Menu = factory(root);
    }
}(typeof self !== 'undefined' ? self : this, function (window) {
    'use strict';

    function Menu(options) {
        var _options,
            dropdowns,
            hovers,
            menu,
            toggler;

        function init() {
            var i,
                k,
                l,
                list,
                r;

            for (k in options) {
                if (options.hasOwnProperty(k)) {
                    _options[k] = options[k];
                }
            }

            if (window.addEventListener) {
                window.addEventListener('touchstart', function touchDetect() {
                    addClass(document.documentElement, 'touch');
                    removeClass(document.documentElement, 'hover');
                    window.touching = true;
                    window.removeEventListener('touchstart', touchDetect, false);
                }, false);
            }

            toggler = document.querySelector(_options.toggler);

            if (toggler) {
                toggler.onclick = menuToggle;
                toggler.onkeydown = toggleKeydown;
            }

            list = document.querySelectorAll(_options.dropdownLink);

            for (i = 0, l = list.length; i < l; i++) {
                r = list[i];
                if (r) {
                    r.onblur = dropdownClose;
                    r.onclick = dropdownToggle;
                    r.onkeydown = dropdownKeydown;
                }
            }

            dropdowns = [];

            menu = document.querySelector(_options.menu);
            if (menu) {
                dropdowns.push([menu, false]);
            }

            list = document.querySelectorAll(_options.dropdowns);

            for (i = 0, l = list.length; i < l; i++) {
                dropdowns.push([list[i], false]);
            }

            hovers = [];

            list = document.querySelectorAll(_options.hoverDropdowns);

            for (i = 0, l = list.length; i < l; i++) {
                hovers.push(list[i]);
            }
        }

        function addClass(el, name) {
            if (!el || !name) {
                return false;
            }
            if (!hasClass(el, name)) {
                el.className += (el.className ? ' ' : '') + name;
            }
            return el;
        }

        function addEvent(el, type, fn) {
            if (el.addEventListener) {
                el.addEventListener(type, fn, false);
            } else {
                el.attachEvent('on' + type, fn);
            }
            return function() {
                removeEvent(el, type, fn);
            };
        }

        function dropdownClose(e) {
            var i, l, target;

            target = e.currentTarget || e.srcElement;

            if (!window.touching) {
                for (i = 0, l = hovers.length; i < l; i++) {
                    if (hovers[i] === target.parentNode) {
                        return;
                    }
                }
            }

            setTimeout(function() {
                removeClass(target.parentNode, 'open');
            }, 10);
        }

        function dropdownKeydown(e) {
            var i, l, target;

            target = e.currentTarget || e.srcElement;

            if (!window.touching) {
                for (i = 0, l = hovers.length; i < l; i++) {
                    if (hovers[i] === target.parentNode) {
                        return;
                    }
                }
            }

            if (!/(38|40|27|32)/.test(e.which) || /input|textarea/i.test(e.target.tagName)) {
                return;
            }

            stopPropagation(e);

            if (hasClass(target.parentNode, 'open') && e.which == 27) {
                dropdownToggle(e);
            }
        }

        function dropdownToggle(e) {
            var i,
                l,
                open,
                target;

            target = e.currentTarget || e.srcElement;

            if (!window.touching) {
                for (i = 0, l = hovers.length; i < l; i++) {
                    if (hovers[i] === target.parentNode) {
                        return;
                    }
                }
            }

            open = hasClass(target.parentNode, 'open');

            if (open) {
                removeClass(target.parentNode, 'open');
            } else {
                addClass(target.parentNode, 'open');
            }

            for (i = 0, l = dropdowns.length; i < l; i++) {
                if (dropdowns[i][0] === target.parentNode) {
                    dropdowns[i][1] = !open;
                }
            }

            e.preventDefault();
            return false;
        }

        function hasClass(el, name) {
            if (!el || !name) {
                return false;
            }
            return (' ' + el.className + ' ').replace(/[\t\r\n\f]/g, ' ').indexOf(' ' + name + ' ') >= 0;
        }

        function menuToggle(e) {
            var i,
                l,
                open,
                r;

            open = hasClass(menu, 'open');

            if (open) {
                removeClass(menu, 'open');
                removeClass(toggler, 'active');
                removeClass(document.body, 'menu-opened');
            } else {
                addClass(menu, 'open');
                addClass(toggler, 'active');
                addClass(document.body, 'menu-opened');
                window.scrollTo(0, 0);
            }

            for (i = 0, l = dropdowns.length; i < l; i++) {
                if (dropdowns[i][0] === menu) {
                    dropdowns[i][1] = !open;
                }
            }

            e.preventDefault();
            return false;
        }

        function removeClass(el, name) {
            if (!el || !name) {
                return;
            }
            el.className = el.className.replace(new RegExp('(?:^|\\s)' + name + '(?!\\S)'), '');

            return el;
        }

        function stopPropagation(e) {
            e.stopPropagation();

            if (e.preventDefault) {
                e.preventDefault();
            }
            else {
                e.returnValue = false;
            }
        }

        function toggleKeydown(e) {

            if (!/(38|40|27|32)/.test(e.which) || /input|textarea/i.test(e.target.tagName)) {
                return;
            }

            stopPropagation(e);

            if (hasClass(menu, 'open') && e.which == 27) {
                toggle(e);
            }
        }

        _options = {
            dropdownLink: '.js-menu-dropdown-link',
            dropdowns: '.js-menu-dropdown,.js-mobile-menu-dropdown',
            hoverDropdowns: '.js-menu-dropdown',
            menu: '.js-mobile-menu',
            toggler: '.js-menu-toggler',
        };

        init();
    }

    return Menu;
}));
