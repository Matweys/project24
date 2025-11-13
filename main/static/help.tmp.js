var Help = (function () {
   'use strict';

   /*! gettext.js - Guillaume Potier - MIT Licensed */
   var i18n = function (options) {
    options = options || {};
    this && (this.__version = '2.0.0');

    // default values that could be overriden in i18n() construct
    var defaults = {
      domain: 'messages',
      locale: (typeof document !== 'undefined' ? document.documentElement.getAttribute('lang') : false) || 'en',
      plural_func: function (n) { return { nplurals: 2, plural: (n!=1) ? 1 : 0 }; },
      ctxt_delimiter: String.fromCharCode(4) // \u0004
    };

    // handy mixins taken from underscode.js
    var _ = {
      isObject: function (obj) {
        var type = typeof obj;
        return type === 'function' || type === 'object' && !!obj;
      },
      isArray: function (obj) {
        return toString.call(obj) === '[object Array]';
      }
    };

    var
      _plural_funcs = {},
      _locale = options.locale || defaults.locale,
      _domain = options.domain || defaults.domain,
      _dictionary = {},
      _plural_forms = {},
      _ctxt_delimiter = options.ctxt_delimiter || defaults.ctxt_delimiter;

      if (options.messages) {
        _dictionary[_domain] = {};
        _dictionary[_domain][_locale] = options.messages;
      }

      if (options.plural_forms) {
        _plural_forms[_locale] = options.plural_forms;
      }

      // sprintf equivalent, takes a string and some arguments to make a computed string
      // eg: strfmt("%1 dogs are in %2", 7, "the kitchen"); => "7 dogs are in the kitchen"
      // eg: strfmt("I like %1, bananas and %1", "apples"); => "I like apples, bananas and apples"
      // NB: removes msg context if there is one present
      var strfmt = function (fmt) {
         var args = arguments;

         return fmt
          // put space after double % to prevent placeholder replacement of such matches
          .replace(/%%/g, '%% ')
          // replace placeholders
          .replace(/%(\d+)/g, function (str, p1) {
            return args[p1];
          })
          // replace double % and space with single %
          .replace(/%% /g, '%')
      };

      var removeContext = function(str) {
         // if there is context, remove it
         if (str.indexOf(_ctxt_delimiter) !== -1) {
           var parts = str.split(_ctxt_delimiter);
           return parts[1];
         }

       return str;
      };

      var expand_locale = function(locale) {
          var locales = [locale],
              i = locale.lastIndexOf('-');
          while (i > 0) {
              locale = locale.slice(0, i);
              locales.push(locale);
              i = locale.lastIndexOf('-');
          }
          return locales;
      };

      var normalizeLocale = function (locale) {
         // Convert locale to BCP 47. If the locale is in POSIX format, locale variant and encoding is discarded.
         locale = locale.replace('_', '-');
         var i = locale.search(/[.@]/);
         if (i != -1) locale = locale.slice(0, i);
         return locale;
      };

      var getPluralFunc = function (plural_form) {
        // Plural form string regexp
        // taken from https://github.com/Orange-OpenSource/gettext.js/blob/master/lib.gettext.js
        // plural forms list available here http://localization-guide.readthedocs.org/en/latest/l10n/pluralforms.html
        var pf_re = new RegExp('^\\s*nplurals\\s*=\\s*[0-9]+\\s*;\\s*plural\\s*=\\s*(?:\\s|[-\\?\\|&=!<>+*/%:;n0-9_\(\)])+');

        if (!pf_re.test(plural_form))
          throw new Error(strfmt('The plural form "%1" is not valid', plural_form));

        // Careful here, this is a hidden eval() equivalent..
        // Risk should be reasonable though since we test the plural_form through regex before
        // taken from https://github.com/Orange-OpenSource/gettext.js/blob/master/lib.gettext.js
        // TODO: should test if https://github.com/soney/jsep present and use it if so
        return new Function("n", 'var plural, nplurals; '+ plural_form +' return { nplurals: nplurals, plural: (plural === true ? 1 : (plural ? plural : 0)) };');
      };

      // Proper translation function that handle plurals and directives
      // Contains juicy parts of https://github.com/Orange-OpenSource/gettext.js/blob/master/lib.gettext.js
      var t = function (messages, n, options /* ,extra */) {
        // Singular is very easy, just pass dictionnary message through strfmt
        if (!options.plural_form)
         return strfmt.apply(this, [removeContext(messages[0])].concat(Array.prototype.slice.call(arguments, 3)));

        var plural;

        // if a plural func is given, use that one
        if (options.plural_func) {
          plural = options.plural_func(n);

        // if plural form never interpreted before, do it now and store it
        } else if (!_plural_funcs[_locale]) {
          _plural_funcs[_locale] = getPluralFunc(_plural_forms[_locale]);
          plural = _plural_funcs[_locale](n);

        // we have the plural function, compute the plural result
        } else {
          plural = _plural_funcs[_locale](n);
        }

        // If there is a problem with plurals, fallback to singular one
        if ('undefined' === typeof plural.plural || plural.plural > plural.nplurals || messages.length <= plural.plural)
          plural.plural = 0;

        return strfmt.apply(this, [removeContext(messages[plural.plural])].concat(Array.prototype.slice.call(arguments, 3)));
      };

    return {
      strfmt: strfmt, // expose strfmt util
      expand_locale: expand_locale, // expose expand_locale util

      // Declare shortcuts
      __: function () { return this.gettext.apply(this, arguments); },
      _n: function () { return this.ngettext.apply(this, arguments); },
      _p: function () { return this.pgettext.apply(this, arguments); },

      setMessages: function (domain, locale, messages, plural_forms) {
        if (!domain || !locale || !messages)
          throw new Error('You must provide a domain, a locale and messages');

        if ('string' !== typeof domain || 'string' !== typeof locale || !_.isObject(messages))
          throw new Error('Invalid arguments');

        locale = normalizeLocale(locale);

        if (plural_forms)
          _plural_forms[locale] = plural_forms;

        if (!_dictionary[domain])
          _dictionary[domain] = {};

        _dictionary[domain][locale] = messages;

        return this;
      },
      loadJSON: function (jsonData, domain) {
        if (!_.isObject(jsonData))
          jsonData = JSON.parse(jsonData);

        if (!jsonData[''] || !jsonData['']['language'] || !jsonData['']['plural-forms'])
          throw new Error('Wrong JSON, it must have an empty key ("") with "language" and "plural-forms" information');

        var headers = jsonData[''];
        delete jsonData[''];

        return this.setMessages(domain || defaults.domain, headers['language'], jsonData, headers['plural-forms']);
      },
      setLocale: function (locale) {
        _locale = normalizeLocale(locale);
        return this;
      },
      getLocale: function () {
        return _locale;
      },
      // getter/setter for domain
      textdomain: function (domain) {
        if (!domain)
          return _domain;
        _domain = domain;
        return this;
      },
      gettext: function (msgid /* , extra */) {
        return this.dcnpgettext.apply(this, [undefined, undefined, msgid, undefined, undefined].concat(Array.prototype.slice.call(arguments, 1)));
      },
      ngettext: function (msgid, msgid_plural, n /* , extra */) {
        return this.dcnpgettext.apply(this, [undefined, undefined, msgid, msgid_plural, n].concat(Array.prototype.slice.call(arguments, 3)));
      },
      pgettext: function (msgctxt, msgid /* , extra */) {
        return this.dcnpgettext.apply(this, [undefined, msgctxt, msgid, undefined, undefined].concat(Array.prototype.slice.call(arguments, 2)));
      },
      dcnpgettext: function (domain, msgctxt, msgid, msgid_plural, n /* , extra */) {
        domain = domain || _domain;

        if ('string' !== typeof msgid)
          throw new Error(this.strfmt('Msgid "%1" is not a valid translatable string', msgid));

        var
          translation,
          options = { plural_form: false },
          key = msgctxt ? msgctxt + _ctxt_delimiter + msgid : msgid,
          exist,
          locale,
          locales = expand_locale(_locale);

        for (var i in locales) {
           locale = locales[i];
           exist = _dictionary[domain] && _dictionary[domain][locale] && _dictionary[domain][locale][key];

           // because it's not possible to define both a singular and a plural form of the same msgid,
           // we need to check that the stored form is the same as the expected one.
           // if not, we'll just ignore the translation and consider it as not translated.
           if (msgid_plural) {
             exist = exist && "string" !== typeof _dictionary[domain][locale][key];
           } else {
             exist = exist && "string" === typeof _dictionary[domain][locale][key];
           }
           if (exist) {
             break;
           }
        }

        if (!exist) {
          translation = msgid;
          options.plural_func = defaults.plural_func;
        } else {
          translation = _dictionary[domain][locale][key];
        }

        // Singular form
        if (!msgid_plural)
          return t.apply(this, [[translation], n, options].concat(Array.prototype.slice.call(arguments, 5)));

        // Plural one
        options.plural_form = true;
        return t.apply(this, [exist ? translation : [msgid, msgid_plural], n, options].concat(Array.prototype.slice.call(arguments, 5)));
      }
    };
   };

   const main_translations = {
   	"": {
   	language: "ru",
   	"plural-forms": "nplurals=4; plural=n==1 ? 3 : n%10==1 && n%100!=11 ? 0 : n%10>=2 && n%10<=4 && (n%100<10 || n%100>=20) ? 1 : 2;"
   },
   	"Delete selected files and folders?": "Удалить выбранные файлы и каталоги?",
   	"Select file": "Выбрать файл",
   	"Rename folder": "Переименовать каталог",
   	"Edit file attributes": "Редактировать атрибуты файле",
   	"File upload error": "Ошибка загрузки файла",
   	"upload_file_overwrite_dialog\u0004Abort": "Прервать",
   	"upload_file_overwrite_dialog\u0004None": "Нет для всех",
   	"upload_file_overwrite_dialog\u0004All": "Все",
   	"upload_file_overwrite_dialog\u0004No": "Нет",
   	"upload_file_overwrite_dialog\u0004Yes": "Да",
   	"upload_file_overwrite_dialog\u0004File already exists %s. Overwrite this file?": "Файл уже существует %s. Переписать?",
   	"help\u0004Text retrieval error": "Ошибка получения текста",
   	"help\u0004Help — Archivarius Digital Archive": "Справка — Архивариус. Электронный архив",
   	"help\u0004Help": "Справка",
   	"move_files\u0004Close": "Закрыть",
   	"move_files\u0004Folder tree retrieval error": "Ошибка списка папок",
   	"move_files\u0004Move files": "Переместить файлы",
   	"move_files\u0004Move selected files to:": "Перенести выбранные файлы в:",
   	"move_files\u0004Root folder": "Корневая папка",
   	"move_files\u0004Choose destination folder": "Выберите папку",
   	"move_files\u0004A file with the same name exists in the destination folder.": "Файл с таким именем уже существует в папке.",
   	"move_files\u0004File move error.": "Ошибка перемещения файлов.",
   	"move_files\u0004Please select a destination folder to move the files to.": "Пожалуйста, выберите папку, куда хотите перенести файлы."
   };

   var en = [
   	{
   		name: "keyboard_shortcuts",
   		label: "Keyboard Shortcuts"
   	},
   	{
   		name: "import_attributes",
   		label: "Import file attributes"
   	},
   	{
   		name: "move_files",
   		label: "Move files"
   	}
   ];
   var ru = [
   	{
   		name: "keyboard_shortcuts",
   		label: "Горячие клавиши"
   	},
   	{
   		name: "import_attributes",
   		label: "Импорт атрибутов файлов"
   	},
   	{
   		name: "move_files",
   		label: "Перемещение файлов"
   	}
   ];
   const menu = {
   	en: en,
   	ru: ru
   };

   class Help {
     constructor(options) {
       this.i18n = new i18n({
         locale: options.language
       });
       this.i18n.loadJSON(main_translations);
       this.modals = [];
       this.options = options;
       window.addEventListener("popstate", e => {
         let state = e.state || {};
         if (state.slug) {
           let modal = this.modals.length ? this.modals[this.modals.length - 1] : this.createModal();
           Help.setModalContent(modal, state.text);
           modal.show();
         } else {
           this.modals.forEach(modal => modal.hide());
           document.title = options.title;
         }
       });
       !document.getElementById("navbar_help").addEventListener("click", e => {
         e.preventDefault();
         let xhr = new XMLHttpRequest();
         xhr.onreadystatechange = () => {
           if (xhr.readyState === 4) {
             if (xhr.status !== 200) {
               alert(xhr.responseText || this.i18n.pgettext("help", "Text retrieval error"));
             } else {
               let modal = this.createModal(xhr.responseText);
               window.setTimeout(() => document.title = `${this.i18n.pgettext("help", "Help — Archivarius Digital Archive")} - ${menu[options.language][0].label}`, 300);
               window.history.pushState({
                 slug: menu[options.language][0].name,
                 language: options.language,
                 text: xhr.responseText
               }, "", `${(options.return_url || "").split("#")[0]}#help/${options.language}/${menu[options.language][0].name}`);
               modal.show();
             }
           }
         };
         xhr.open("GET", `${(options.base_url || "").replace(/\/+$/, "")}/${options.language}/${menu[options.language][0].name}.html`);
         xhr.send();
       });
       if (window.location.hash) {
         let m = /help\/(en|ru)\/(.*)/g.exec(window.location.hash);
         if (m && m[1] && m[2]) {
           let modal = this.modals.length ? this.modals[this.modals.length - 1] : this.createModal();
           Help.loadModalContent(modal, m[2],
           // slug
           m[1],
           // language
           this.options.base_url, this.options.return_url, this.i18n);
           modal.show();
         }
       }
     }
     createModal(content) {
       let menu_str = '<nav class="nav flex-column">' + menu[this.options.language].reduce((acc, x) => acc + (acc ? `<li class="nav-item"><a class="nav-link" href="#${x["name"]}">${x["label"]}</a></li>` : `<li class="nav-item"><a aria-current="page" class="nav-link active" href="#${x["name"]}">${x["label"]}</a></li>`), "") + "</nav>";
       let tpl = document.createElement("template");
       tpl.innerHTML = `<div class="modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-xl modal-fullscreen-xl-down">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">${this.i18n.pgettext("help", "Help")}</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="${this.i18n.pgettext("move_files", "Close")}"></button>
      </div>
      <div class="modal-body">
        <div class="container-fluid">
          <div class="row">
            <div class="col-3 menu">${menu_str}</div>
            <div class="col-9 content">${content}</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this.i18n.pgettext("move_files", "Close")}</button>
      </div>
    </div>
  </div>
</div>`;
       let el = tpl.content.firstElementChild;
       document.body.appendChild(tpl.content, document.body.firstChild);
       let modal = new bootstrap.Modal(el);
       this.modals.push(modal);
       modal._element.addEventListener("hidden.bs.modal", e => {
         window.setTimeout(() => {
           let modal = bootstrap.Modal.getInstance(e.target);
           if (modal) {
             let element = modal._element;
             this.modals.splice(this.modals.indexOf(modal), 1);
             modal.dispose();
             element.parentNode.removeChild(element);
             if (!this.modals.length && window.location.href.indexOf("#")) {
               setTimeout(() => document.title = this.options.title, 300);
               window.history.pushState(null, "", this.options.return_url);
             }
           }
         });
       });
       let menu_item_handle_click = e => {
         e.preventDefault();
         let slug = e.target.getAttribute("href");
         slug = slug[0] == "#" ? slug.replace("#", "") : slug;
         Help.loadModalContent(modal, slug, this.options.language, this.options.base_url, this.options.return_url, this.i18n);
       };
       !modal._element.querySelectorAll(".menu .nav-link").forEach(el => {
         el.addEventListener("click", menu_item_handle_click);
       });
       return modal;
     }
     static loadModalContent(modal, slug, language, base_url, browser_base_url, i18n) {
       if (slug) {
         let xhr = new XMLHttpRequest();
         xhr.onreadystatechange = () => {
           if (xhr.readyState === 4) {
             if (xhr.status !== 200) {
               alert(xhr.responseText || this.i18n.pgettext("help", "Text retrieval error"));
             } else {
               let menu_item_label = (menu[language].find(x => x.name === slug) || {}).label;
               let title = `${i18n.pgettext("help", "Help — Archivarius Digital Archive")} - ${menu_item_label}`;
               window.setTimeout(() => document.title = title, 300);
               window.history.pushState({
                 language: language,
                 slug: slug,
                 text: xhr.responseText
               }, "", `${(browser_base_url || "").split("#")[0]}#help/${language}/${slug}`);
               modal._element.querySelector(".content").innerHTML = xhr.responseText;
             }
           }
         };
         xhr.open("GET", `${(base_url || "").replace(/\/+$/, "")}/${language}/${slug}.html`);
         xhr.send();
       }
     }
     static setModalContent(modal, content) {
       if (modal) {
         modal._element.querySelector(".content").innerHTML = content;
       }
     }
   }

   return Help;

})();
