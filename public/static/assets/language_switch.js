if (document.querySelector('.js-language-switch')) {
    (function() {
        'use strict';
        var v = document.querySelector('.js-language-switch');
        if (v) {
            v.addEventListener('change', function (e) {
                document.cookie = 'lang=' + encodeURIComponent(this.value) + ';expires=' + (new Date(Date.now() + 2678400e3)).toUTCString() + ';path=/';
                location.reload();
            }, false);
        }
    })();
}
