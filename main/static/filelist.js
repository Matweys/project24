var Filelist = (function () {
  'use strict';

  /** Detect free variable `global` from Node.js. */
  var freeGlobal = typeof global == 'object' && global && global.Object === Object && global;

  const freeGlobal$1 = freeGlobal;

  /** Detect free variable `self`. */
  var freeSelf = typeof self == 'object' && self && self.Object === Object && self;

  /** Used as a reference to the global object. */
  var root = freeGlobal$1 || freeSelf || Function('return this')();

  const root$1 = root;

  /** Built-in value references. */
  var Symbol$1 = root$1.Symbol;

  const Symbol$2 = Symbol$1;

  /** Used for built-in method references. */
  var objectProto$1 = Object.prototype;

  /** Used to check objects for own properties. */
  var hasOwnProperty = objectProto$1.hasOwnProperty;

  /**
   * Used to resolve the
   * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
   * of values.
   */
  var nativeObjectToString$1 = objectProto$1.toString;

  /** Built-in value references. */
  var symToStringTag$1 = Symbol$2 ? Symbol$2.toStringTag : undefined;

  /**
   * A specialized version of `baseGetTag` which ignores `Symbol.toStringTag` values.
   *
   * @private
   * @param {*} value The value to query.
   * @returns {string} Returns the raw `toStringTag`.
   */
  function getRawTag(value) {
    var isOwn = hasOwnProperty.call(value, symToStringTag$1),
        tag = value[symToStringTag$1];

    try {
      value[symToStringTag$1] = undefined;
      var unmasked = true;
    } catch (e) {}

    var result = nativeObjectToString$1.call(value);
    if (unmasked) {
      if (isOwn) {
        value[symToStringTag$1] = tag;
      } else {
        delete value[symToStringTag$1];
      }
    }
    return result;
  }

  /** Used for built-in method references. */
  var objectProto = Object.prototype;

  /**
   * Used to resolve the
   * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
   * of values.
   */
  var nativeObjectToString = objectProto.toString;

  /**
   * Converts `value` to a string using `Object.prototype.toString`.
   *
   * @private
   * @param {*} value The value to convert.
   * @returns {string} Returns the converted string.
   */
  function objectToString(value) {
    return nativeObjectToString.call(value);
  }

  /** `Object#toString` result references. */
  var nullTag = '[object Null]',
      undefinedTag = '[object Undefined]';

  /** Built-in value references. */
  var symToStringTag = Symbol$2 ? Symbol$2.toStringTag : undefined;

  /**
   * The base implementation of `getTag` without fallbacks for buggy environments.
   *
   * @private
   * @param {*} value The value to query.
   * @returns {string} Returns the `toStringTag`.
   */
  function baseGetTag(value) {
    if (value == null) {
      return value === undefined ? undefinedTag : nullTag;
    }
    return (symToStringTag && symToStringTag in Object(value))
      ? getRawTag(value)
      : objectToString(value);
  }

  /**
   * Checks if `value` is object-like. A value is object-like if it's not `null`
   * and has a `typeof` result of "object".
   *
   * @static
   * @memberOf _
   * @since 4.0.0
   * @category Lang
   * @param {*} value The value to check.
   * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
   * @example
   *
   * _.isObjectLike({});
   * // => true
   *
   * _.isObjectLike([1, 2, 3]);
   * // => true
   *
   * _.isObjectLike(_.noop);
   * // => false
   *
   * _.isObjectLike(null);
   * // => false
   */
  function isObjectLike(value) {
    return value != null && typeof value == 'object';
  }

  /** `Object#toString` result references. */
  var symbolTag = '[object Symbol]';

  /**
   * Checks if `value` is classified as a `Symbol` primitive or object.
   *
   * @static
   * @memberOf _
   * @since 4.0.0
   * @category Lang
   * @param {*} value The value to check.
   * @returns {boolean} Returns `true` if `value` is a symbol, else `false`.
   * @example
   *
   * _.isSymbol(Symbol.iterator);
   * // => true
   *
   * _.isSymbol('abc');
   * // => false
   */
  function isSymbol(value) {
    return typeof value == 'symbol' ||
      (isObjectLike(value) && baseGetTag(value) == symbolTag);
  }

  /** Used to match a single whitespace character. */
  var reWhitespace = /\s/;

  /**
   * Used by `_.trim` and `_.trimEnd` to get the index of the last non-whitespace
   * character of `string`.
   *
   * @private
   * @param {string} string The string to inspect.
   * @returns {number} Returns the index of the last non-whitespace character.
   */
  function trimmedEndIndex(string) {
    var index = string.length;

    while (index-- && reWhitespace.test(string.charAt(index))) {}
    return index;
  }

  /** Used to match leading whitespace. */
  var reTrimStart = /^\s+/;

  /**
   * The base implementation of `_.trim`.
   *
   * @private
   * @param {string} string The string to trim.
   * @returns {string} Returns the trimmed string.
   */
  function baseTrim(string) {
    return string
      ? string.slice(0, trimmedEndIndex(string) + 1).replace(reTrimStart, '')
      : string;
  }

  /**
   * Checks if `value` is the
   * [language type](http://www.ecma-international.org/ecma-262/7.0/#sec-ecmascript-language-types)
   * of `Object`. (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
   *
   * @static
   * @memberOf _
   * @since 0.1.0
   * @category Lang
   * @param {*} value The value to check.
   * @returns {boolean} Returns `true` if `value` is an object, else `false`.
   * @example
   *
   * _.isObject({});
   * // => true
   *
   * _.isObject([1, 2, 3]);
   * // => true
   *
   * _.isObject(_.noop);
   * // => true
   *
   * _.isObject(null);
   * // => false
   */
  function isObject(value) {
    var type = typeof value;
    return value != null && (type == 'object' || type == 'function');
  }

  /** Used as references for various `Number` constants. */
  var NAN = 0 / 0;

  /** Used to detect bad signed hexadecimal string values. */
  var reIsBadHex = /^[-+]0x[0-9a-f]+$/i;

  /** Used to detect binary string values. */
  var reIsBinary = /^0b[01]+$/i;

  /** Used to detect octal string values. */
  var reIsOctal = /^0o[0-7]+$/i;

  /** Built-in method references without a dependency on `root`. */
  var freeParseInt = parseInt;

  /**
   * Converts `value` to a number.
   *
   * @static
   * @memberOf _
   * @since 4.0.0
   * @category Lang
   * @param {*} value The value to process.
   * @returns {number} Returns the number.
   * @example
   *
   * _.toNumber(3.2);
   * // => 3.2
   *
   * _.toNumber(Number.MIN_VALUE);
   * // => 5e-324
   *
   * _.toNumber(Infinity);
   * // => Infinity
   *
   * _.toNumber('3.2');
   * // => 3.2
   */
  function toNumber(value) {
    if (typeof value == 'number') {
      return value;
    }
    if (isSymbol(value)) {
      return NAN;
    }
    if (isObject(value)) {
      var other = typeof value.valueOf == 'function' ? value.valueOf() : value;
      value = isObject(other) ? (other + '') : other;
    }
    if (typeof value != 'string') {
      return value === 0 ? value : +value;
    }
    value = baseTrim(value);
    var isBinary = reIsBinary.test(value);
    return (isBinary || reIsOctal.test(value))
      ? freeParseInt(value.slice(2), isBinary ? 2 : 8)
      : (reIsBadHex.test(value) ? NAN : +value);
  }

  /**
   * Gets the timestamp of the number of milliseconds that have elapsed since
   * the Unix epoch (1 January 1970 00:00:00 UTC).
   *
   * @static
   * @memberOf _
   * @since 2.4.0
   * @category Date
   * @returns {number} Returns the timestamp.
   * @example
   *
   * _.defer(function(stamp) {
   *   console.log(_.now() - stamp);
   * }, _.now());
   * // => Logs the number of milliseconds it took for the deferred invocation.
   */
  var now = function() {
    return root$1.Date.now();
  };

  const now$1 = now;

  /** Error message constants. */
  var FUNC_ERROR_TEXT$1 = 'Expected a function';

  /* Built-in method references for those with the same name as other `lodash` methods. */
  var nativeMax = Math.max,
      nativeMin = Math.min;

  /**
   * Creates a debounced function that delays invoking `func` until after `wait`
   * milliseconds have elapsed since the last time the debounced function was
   * invoked. The debounced function comes with a `cancel` method to cancel
   * delayed `func` invocations and a `flush` method to immediately invoke them.
   * Provide `options` to indicate whether `func` should be invoked on the
   * leading and/or trailing edge of the `wait` timeout. The `func` is invoked
   * with the last arguments provided to the debounced function. Subsequent
   * calls to the debounced function return the result of the last `func`
   * invocation.
   *
   * **Note:** If `leading` and `trailing` options are `true`, `func` is
   * invoked on the trailing edge of the timeout only if the debounced function
   * is invoked more than once during the `wait` timeout.
   *
   * If `wait` is `0` and `leading` is `false`, `func` invocation is deferred
   * until to the next tick, similar to `setTimeout` with a timeout of `0`.
   *
   * See [David Corbacho's article](https://css-tricks.com/debouncing-throttling-explained-examples/)
   * for details over the differences between `_.debounce` and `_.throttle`.
   *
   * @static
   * @memberOf _
   * @since 0.1.0
   * @category Function
   * @param {Function} func The function to debounce.
   * @param {number} [wait=0] The number of milliseconds to delay.
   * @param {Object} [options={}] The options object.
   * @param {boolean} [options.leading=false]
   *  Specify invoking on the leading edge of the timeout.
   * @param {number} [options.maxWait]
   *  The maximum time `func` is allowed to be delayed before it's invoked.
   * @param {boolean} [options.trailing=true]
   *  Specify invoking on the trailing edge of the timeout.
   * @returns {Function} Returns the new debounced function.
   * @example
   *
   * // Avoid costly calculations while the window size is in flux.
   * jQuery(window).on('resize', _.debounce(calculateLayout, 150));
   *
   * // Invoke `sendMail` when clicked, debouncing subsequent calls.
   * jQuery(element).on('click', _.debounce(sendMail, 300, {
   *   'leading': true,
   *   'trailing': false
   * }));
   *
   * // Ensure `batchLog` is invoked once after 1 second of debounced calls.
   * var debounced = _.debounce(batchLog, 250, { 'maxWait': 1000 });
   * var source = new EventSource('/stream');
   * jQuery(source).on('message', debounced);
   *
   * // Cancel the trailing debounced invocation.
   * jQuery(window).on('popstate', debounced.cancel);
   */
  function debounce(func, wait, options) {
    var lastArgs,
        lastThis,
        maxWait,
        result,
        timerId,
        lastCallTime,
        lastInvokeTime = 0,
        leading = false,
        maxing = false,
        trailing = true;

    if (typeof func != 'function') {
      throw new TypeError(FUNC_ERROR_TEXT$1);
    }
    wait = toNumber(wait) || 0;
    if (isObject(options)) {
      leading = !!options.leading;
      maxing = 'maxWait' in options;
      maxWait = maxing ? nativeMax(toNumber(options.maxWait) || 0, wait) : maxWait;
      trailing = 'trailing' in options ? !!options.trailing : trailing;
    }

    function invokeFunc(time) {
      var args = lastArgs,
          thisArg = lastThis;

      lastArgs = lastThis = undefined;
      lastInvokeTime = time;
      result = func.apply(thisArg, args);
      return result;
    }

    function leadingEdge(time) {
      // Reset any `maxWait` timer.
      lastInvokeTime = time;
      // Start the timer for the trailing edge.
      timerId = setTimeout(timerExpired, wait);
      // Invoke the leading edge.
      return leading ? invokeFunc(time) : result;
    }

    function remainingWait(time) {
      var timeSinceLastCall = time - lastCallTime,
          timeSinceLastInvoke = time - lastInvokeTime,
          timeWaiting = wait - timeSinceLastCall;

      return maxing
        ? nativeMin(timeWaiting, maxWait - timeSinceLastInvoke)
        : timeWaiting;
    }

    function shouldInvoke(time) {
      var timeSinceLastCall = time - lastCallTime,
          timeSinceLastInvoke = time - lastInvokeTime;

      // Either this is the first call, activity has stopped and we're at the
      // trailing edge, the system time has gone backwards and we're treating
      // it as the trailing edge, or we've hit the `maxWait` limit.
      return (lastCallTime === undefined || (timeSinceLastCall >= wait) ||
        (timeSinceLastCall < 0) || (maxing && timeSinceLastInvoke >= maxWait));
    }

    function timerExpired() {
      var time = now$1();
      if (shouldInvoke(time)) {
        return trailingEdge(time);
      }
      // Restart the timer.
      timerId = setTimeout(timerExpired, remainingWait(time));
    }

    function trailingEdge(time) {
      timerId = undefined;

      // Only invoke if we have `lastArgs` which means `func` has been
      // debounced at least once.
      if (trailing && lastArgs) {
        return invokeFunc(time);
      }
      lastArgs = lastThis = undefined;
      return result;
    }

    function cancel() {
      if (timerId !== undefined) {
        clearTimeout(timerId);
      }
      lastInvokeTime = 0;
      lastArgs = lastCallTime = lastThis = timerId = undefined;
    }

    function flush() {
      return timerId === undefined ? result : trailingEdge(now$1());
    }

    function debounced() {
      var time = now$1(),
          isInvoking = shouldInvoke(time);

      lastArgs = arguments;
      lastThis = this;
      lastCallTime = time;

      if (isInvoking) {
        if (timerId === undefined) {
          return leadingEdge(lastCallTime);
        }
        if (maxing) {
          // Handle invocations in a tight loop.
          clearTimeout(timerId);
          timerId = setTimeout(timerExpired, wait);
          return invokeFunc(lastCallTime);
        }
      }
      if (timerId === undefined) {
        timerId = setTimeout(timerExpired, wait);
      }
      return result;
    }
    debounced.cancel = cancel;
    debounced.flush = flush;
    return debounced;
  }

  /** Error message constants. */
  var FUNC_ERROR_TEXT = 'Expected a function';

  /**
   * Creates a throttled function that only invokes `func` at most once per
   * every `wait` milliseconds. The throttled function comes with a `cancel`
   * method to cancel delayed `func` invocations and a `flush` method to
   * immediately invoke them. Provide `options` to indicate whether `func`
   * should be invoked on the leading and/or trailing edge of the `wait`
   * timeout. The `func` is invoked with the last arguments provided to the
   * throttled function. Subsequent calls to the throttled function return the
   * result of the last `func` invocation.
   *
   * **Note:** If `leading` and `trailing` options are `true`, `func` is
   * invoked on the trailing edge of the timeout only if the throttled function
   * is invoked more than once during the `wait` timeout.
   *
   * If `wait` is `0` and `leading` is `false`, `func` invocation is deferred
   * until to the next tick, similar to `setTimeout` with a timeout of `0`.
   *
   * See [David Corbacho's article](https://css-tricks.com/debouncing-throttling-explained-examples/)
   * for details over the differences between `_.throttle` and `_.debounce`.
   *
   * @static
   * @memberOf _
   * @since 0.1.0
   * @category Function
   * @param {Function} func The function to throttle.
   * @param {number} [wait=0] The number of milliseconds to throttle invocations to.
   * @param {Object} [options={}] The options object.
   * @param {boolean} [options.leading=true]
   *  Specify invoking on the leading edge of the timeout.
   * @param {boolean} [options.trailing=true]
   *  Specify invoking on the trailing edge of the timeout.
   * @returns {Function} Returns the new throttled function.
   * @example
   *
   * // Avoid excessively updating the position while scrolling.
   * jQuery(window).on('scroll', _.throttle(updatePosition, 100));
   *
   * // Invoke `renewToken` when the click event is fired, but not more than once every 5 minutes.
   * var throttled = _.throttle(renewToken, 300000, { 'trailing': false });
   * jQuery(element).on('click', throttled);
   *
   * // Cancel the trailing throttled invocation.
   * jQuery(window).on('popstate', throttled.cancel);
   */
  function throttle(func, wait, options) {
    var leading = true,
        trailing = true;

    if (typeof func != 'function') {
      throw new TypeError(FUNC_ERROR_TEXT);
    }
    if (isObject(options)) {
      leading = 'leading' in options ? !!options.leading : leading;
      trailing = 'trailing' in options ? !!options.trailing : trailing;
    }
    return debounce(func, wait, {
      'leading': leading,
      'maxWait': wait,
      'trailing': trailing
    });
  }

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

  var Pager = function (options) {
    this.options = options;
  };
  Pager.l10ns = {};
  function appendTo(d, k, v) {
    v = Array.isArray(v) ? v.join(",") : v;
    if (k in d) {
      if (!Array.isArray(d[k])) {
        d[k] = [d[k]];
      }
      d[k].push(v);
    } else {
      d[k] = v;
    }
  }
  function URLSearchParams$1(q) {
    var i,
      index,
      l,
      pairs,
      v,
      r = Object.create(null);
    if (q && typeof q === "string") {
      if (q.charAt(0) === "?") {
        q = q.slice(1);
      }
      for (pairs = q.split("&"), i = 0, l = pairs.length; i < l; i++) {
        v = pairs[i];
        index = v.indexOf("=");
        if (-1 < index) {
          appendTo(r, decodeURIComponent(v.slice(0, index).replace("+", " ")), decodeURIComponent(v.slice(index + 1)).replace("+", " "));
        } else if (v.length) {
          appendTo(r, decodeURIComponent(v.replace("+", " ")), "");
        }
      }
    }
    return r;
  }
  if (!String.format) {
    var string_format = function (format) {
      var args = Array.prototype.slice.call(arguments, 1);
      return format.replace(/{(\d+)}/g, function (match, number) {
        return typeof args[number] !== "undefined" ? args[number] : match;
      });
    };
  }
  Pager.prototype.pager = function (param, count, page_size, generator) {
    var current_page, i, l10n, max, min, num_pages, r;
    l10n = Pager.l10ns[this.options.language];
    if (!generator || page_size <= 0) {
      return "";
    }
    num_pages = Math.ceil(count / page_size);
    if (num_pages < 2) {
      return "";
    }
    r = '<div class="pager">';
    current_page = Math.min(Math.max(0, parseInt(URLSearchParams$1(window.location.search)[param]) || 0), num_pages - 1);
    min = current_page - 4;
    max = current_page + 4 + 1;
    if (min < 0) {
      max = max - min;
    }
    if (max >= num_pages) {
      min = min - max + num_pages;
    }
    if (min < 0) {
      min = 0;
    }
    if (max > num_pages) {
      max = num_pages;
    }
    if (current_page > 0) {
      r += '<a class="pager__item" href="' + generator(current_page - 1) + '">' + l10n.previous_page + "</a>";
    }
    for (i = min; i < max; i++) {
      if (i == current_page) {
        r += '<span class="pager__item active">' + (i + 1) + "</span>";
      } else {
        r += '<a class="pager__item" href="' + generator(i) + '">' + (i + 1) + "</a>";
      }
    }
    if (current_page + 1 < num_pages) {
      r += '<a class="pager__item" href="' + generator(current_page + 1) + '">' + l10n.next_page + "</a>";
    }
    r += "</div>";
    return r;
  };
  Pager.prototype.pagerCompact = function (param, count, page_size, generator) {
    var current_page, l10n, num_pages, offset;
    l10n = Pager.l10ns[this.options.language];
    if (!generator || count <= 0 || page_size <= 0) {
      return "";
    }
    num_pages = Math.ceil(count / page_size);
    current_page = Math.min(Math.max(0, parseInt(URLSearchParams$1(window.location.search)[param]) || 0), num_pages - 1);
    offset = Math.max(0, Math.min(current_page, num_pages - 1) * page_size);
    return num_pages > 1 ? '<div class="pager-compact"><div class="pager-compact__info">' + (String.format ? String.format(l10n.pager_compact, offset + 1, Math.min(offset + page_size, count), count) : string_format(l10n.pager_compact, offset + 1, Math.min(offset + page_size, count), count)) + "</div>" + (current_page > 0 ? '<a class="pager-compact__item" href="' + generator(current_page - 1) + '">❮</a>' : '<span class="pager-compact__item pager-compact__item--disabled">❮</span>') + (current_page + 1 < num_pages ? '<a class="pager-compact__item" href="' + generator(current_page + 1) + '">❯</a>' : '<span class="pager-compact__item pager-compact__item--disabled">❯</span>') + "</div>" : "";
  };

  const translations = {
    en: {
      item_count: function (n) {
        var plurals = ["%d item", "%d items"];
        return plurals[n > 1 ? 1 : 0].replace("%d", n);
      }
    },
    ru: {
      item_count: function (n) {
        var plurals = ["%s элемент", "%s элемента", "%s элементов", "1 элемент"];
        return plurals[n == 1 ? 3 : n % 10 == 1 && n % 100 != 11 ? 0 : n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20) ? 1 : 2].replace("%s", n);
      }
    }
  };

  /* Borrows heavily from Dropzone.js https://www.dropzone.dev */

  var Upload_CANCELED = "canceled",
    Upload_ERROR = "error",
    Upload_QUEUED = "queued",
    Upload_SUCCESS = "success",
    Upload_UPLOADING = "uploading";
  function Upload(options) {
    var defaults = {
      cancelButton: null,
      concurency: 2,
      dropzone: null,
      dropzoneHoverClass: "upload-hover",
      field_name: "file",
      hiddenInputContainer: "body",
      onCancel: null,
      onComplete: null,
      onError: (file, response) => alert(response),
      onProgress: null,
      onTotalProgress: null,
      params: null,
      timeout: 0,
      totalProgressClass: "upload-progress",
      totalProgressContainer: null,
      uploadButton: "",
      url: null
    };
    this.options = extend({}, defaults, options);
    this.init();
  }
  Upload.prototype.cancelUpload = function () {
    for (let i = 0; i < this.files.length; i++) {
      let file = this.files[i];
      if (file.status === Upload_QUEUED || file.status === Upload_UPLOADING) {
        file.status = Upload_CANCELED;
      }
      if (file.xhr != null) {
        file.xhr.abort();
      }
    }
    if (typeof this.options.onCancel === "function") {
      setTimeout(this.options.onCancel, 0);
    }
    setTimeout(processQueue.bind(this), 0);
  };
  Upload.prototype.enqueueFiles = function (files, cb) {
    for (let i = 0; i < files.length; i++) {
      let file = {
        file: files[i],
        size: files[i].size,
        status: Upload_QUEUED,
        upload: {
          uuid: (() => {
            return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(/[xy]/g, c => {
              let r = Math.random() * 16 | 0,
                v = c === "x" ? r : r & 0x3 | 0x8;
              return v.toString(16);
            });
          })(),
          bytesSent: 0,
          progress: 0,
          bytesSent: 0,
          filename: files[i].name
        }
      };
      if (typeof cb === "function") {
        cb(file);
      }
      this.files.push(file);
    }
    setTimeout(processQueue.bind(this), 0);
  };
  Upload.prototype.init = function () {
    this.l10n = Upload.prototype.l10ns[this.options.language];
    this.files = [];
    this.cancel_btn = typeof this.options.cancelButton === "string" ? document.querySelector(this.options.cancelButton) : this.options.cancelButton;
    this.dropzone = typeof this.options.cancelButton === "string" ? document.querySelector(this.options.dropzone) : this.options.dropzone;
    this.upload_btn = typeof this.options.cancelButton === "string" ? document.querySelector(this.options.uploadButton) : this.options.uploadButton;
    this.hidden_file_input = document.createElement("input");
    this.hidden_file_input.setAttribute("multiple", "multiple");
    this.hidden_file_input.setAttribute("style", "position:absolute;height:0;width:0;visibility:hidden");
    this.hidden_file_input.setAttribute("tabindex", "-1");
    this.hidden_file_input.setAttribute("type", "file");
    this.hidden_input_form = document.createElement("form");
    this.hidden_input_form.setAttribute("enctype", "multipart/form-data");
    this.hidden_input_form.appendChild(this.hidden_file_input);
    document.querySelector(this.options.hiddenInputContainer).appendChild(this.hidden_input_form);
    if (this.options.totalProgressContainer) {
      let el = this.options.totalProgressContainer === "string" ? document.querySelector(this.options.totalProgressContainer) : this.options.totalProgressContainer;
      if (el) {
        this.totalProgress = document.createElement("div");
        this.totalProgress.style.display = "none";
        if (this.options.totalProgressClass) {
          this.totalProgress.classList.add(this.options.totalProgressClass);
        }
        this.totalProgress.appendChild(document.createElement("div"));
        el.appendChild(this.totalProgress);
      }
    }
    this.hidden_file_input.addEventListener("change", e => {
      if (this.hidden_file_input.value !== "") {
        this.enqueueFiles(this.hidden_file_input.files);
        this.hidden_input_form.reset();
      }
    });
    if (this.upload_btn) {
      this.upload_btn.addEventListener("click", e => {
        e.preventDefault();
        this.hidden_file_input.click();
      });
    }
    if (this.cancel_btn) {
      this.cancel_btn.addEventListener("click", e => {
        e.preventDefault();
        this.cancelUpload();
      });
    }
    if (this.dropzone) {
      this.dropzone.addEventListener("dragend", e => {
        this.dropzone.classList.remove(this.options.dropzoneHoverClass);
      });
      this.dropzone.addEventListener("dragenter", e => {
        e.preventDefault();
        this.dropzone.classList.add(this.options.dropzoneHoverClass);
        return false;
      });
      this.dropzone.addEventListener("dragleave", e => {
        this.dropzone.classList.remove(this.options.dropzoneHoverClass);
      });
      this.dropzone.addEventListener("dragover", e => {
        e.preventDefault();
        this.dropzone.classList.add(this.options.dropzoneHoverClass);
        if (dragFileCheck(e)) {
          let r;
          try {
            r = e.dataTransfer.effectAllowed;
          } catch (err) {}
          e.dataTransfer.dropEffect = r === "move" || r === "linkMove" ? "move" : "copy";
        }
        return false;
      });
      this.dropzone.addEventListener("drop", e => {
        e.preventDefault();
        this.dropzone.classList.remove(this.options.dropzoneHoverClass);
        if (dragFileCheck(e)) {
          this.enqueueFiles(e.dataTransfer.files);
        }
      });
    }
  };
  Upload.prototype.l10ns = {};
  function dragFileCheck(e) {
    if (e.dataTransfer.types) {
      for (let i = 0; i < e.dataTransfer.types.length; i++) {
        if (e.dataTransfer.types[i] == "Files") {
          return true;
        }
      }
    }
    return false;
  }
  function extend() {
    for (let i = 1; i < arguments.length; i++) {
      for (let key in arguments[i]) {
        if (arguments[i].hasOwnProperty(key)) {
          arguments[0][key] = arguments[i][key];
        }
      }
    }
    return arguments[0];
  }
  function finishedUploading(file, xhr, e) {
    if (xhr.readyState !== 4) {
      return;
    }
    if (file.status !== Upload_CANCELED) {
      file.status = xhr.status >= 200 && xhr.status < 300 ? Upload_SUCCESS : Upload_ERROR;
    }
    let response;
    if (xhr.responseType !== "arraybuffer" && xhr.responseType !== "blob") {
      response = xhr.responseText;
      if (xhr.getResponseHeader("content-type") && ~xhr.getResponseHeader("content-type").indexOf("application/json")) {
        try {
          response = JSON.parse(response);
        } catch (error) {
          response = "Invalid JSON response from server.";
        }
      }
    }
    updateUploadProgress.call(this, file, xhr);
    if (xhr.status >= 200 && xhr.status < 300) {
      if (typeof this.options.onComplete === "function") {
        setTimeout(this.options.onComplete.bind(null, file, response), 0);
      }
    } else {
      if (typeof this.options.onError === "function") {
        setTimeout(this.options.onError.bind(null, file, response), 0);
      }
    }
    processQueue.call(this);
  }
  function handleUploadError(file, xhr, response) {
    if (file.status !== Upload_CANCELED) {
      file.status = Upload_ERROR;
    }
    if (typeof this.options.onError === "function") {
      setTimeout(this.options.onError.bind(null, file, response), 0);
    }
    processQueue.call(this);
  }
  function getActiveFiles() {
    return this.files.filter(function (file) {
      return file.status === Upload_QUEUED || file.status === Upload_UPLOADING;
    }).map(function (file) {
      return file;
    });
  }
  function getFilesWithStatus(status) {
    return this.files.filter(function (file) {
      return file.status === status;
    }).map(function (file) {
      return file;
    });
  }
  function processQueue() {
    var err = !!this.files.filter(file => {
      return file.status === Upload_CANCELED || file.status === Upload_ERROR && !file.xhr || file.status === Upload_ERROR && file.xhr.status !== 409;
    }).length;
    var processingLength = getFilesWithStatus.call(this, Upload_UPLOADING).length,
      queuedFiles = getFilesWithStatus.call(this, Upload_QUEUED);
    if (!getActiveFiles.call(this).length || err) {
      this.files = [];
      return;
    }
    var i = processingLength;
    while (i < this.options.concurency && queuedFiles.length) {
      uploadFile.call(this, queuedFiles.shift());
      i++;
    }
  }
  function updateUploadProgress(file, xhr, e) {
    if (e !== undefined) {
      file.upload.progress = e.total ? Math.round(e.loaded / e.total * 100) : 0;
      file.upload.totalBytes = e.total;
      file.upload.bytesSent = e.loaded;
      if (typeof this.options.onProgress === "function") {
        this.options.onProgress(file.upload.progress, file.size, file.upload.bytesSent);
      }
    } else {
      file.upload.progress = 100;
      file.upload.bytesSent = file.upload.totalBytes;
    }
    let bytesSent = 0,
      err = false,
      is_processing = false,
      totalBytes = 0;
    for (let i = 0; i < this.files.length; i++) {
      if (!err && (this.files[i].status === Upload_CANCELED || this.files[i].status === Upload_ERROR && !this.files[i].xhr || this.files[i].status === Upload_ERROR && this.files[i].xhr.status !== 409)) {
        err = true;
      }
      if (!is_processing && this.files[i].status === Upload_QUEUED || this.files[i].status === Upload_UPLOADING) {
        is_processing = true;
      }
      bytesSent += this.files[i].upload.bytesSent || 0;
      totalBytes += this.files[i].size || 0;
    }
    let totalProgress = totalBytes ? Math.round(bytesSent / totalBytes * 100) : 0;
    if (this.totalProgress) {
      let els = this.totalProgress.getElementsByTagName("div");
      if (els.length) {
        els[0].style.width = totalProgress + "%";
      }
    }

    // Hide an upload progress an error or a cancel

    if (is_processing && err) {
      is_processing = false;
    }
    if (this.cancel_btn) {
      this.cancel_btn.style.display = is_processing ? "" : "none";
    }
    if (this.totalProgress) {
      this.totalProgress.style.display = is_processing ? "" : "none";
    }
    if (!is_processing && this.totalProgress) {
      let els = this.totalProgress.getElementsByTagName("div");
      if (els.length) {
        els[0].style.width = 0;
      }
    }
    if (e !== undefined && typeof this.options.onTotalProgress === "function") {
      this.options.onTotalProgress(totalProgress, totalBytes, bytesSent);
    }
  }
  function uploadFile(file) {
    file.status = Upload_UPLOADING;
    let xhr = new XMLHttpRequest();
    file.xhr = xhr;
    xhr.open("post", this.options.url, true);
    xhr.timeout = this.options.timeout;
    xhr.onerror = () => handleUploadError.call(this, file, xhr);
    xhr.onload = e => finishedUploading.call(this, file, xhr, e);
    xhr.ontimeout = () => handleUploadError.call(this, file, xhr, `Request timedout after ${this.options.timeout / 1000} seconds`);

    // Some browsers do not have the .upload property
    let progressObj = xhr.upload != null ? xhr.upload : xhr;
    progressObj.onprogress = e => updateUploadProgress.call(this, file, xhr, e);
    xhr.setRequestHeader("Accept", "application/json");
    xhr.setRequestHeader("Cache-Control", "no-cache");
    xhr.setRequestHeader("X-File-Id", file.upload.uuid);
    if (file.overwrite !== undefined) {
      xhr.setRequestHeader("X-File-Overwrite", file.overwrite ? "?1" : "?0");
    }
    let fd = new FormData();
    if (this.options.params) {
      let additional_params = this.options.params;
      if (typeof additional_params === "function") {
        additional_params = additional_params.call(this, file, xhr);
      }
      for (let k in additional_params) {
        let v = additional_params[k];
        if (Array.isArray(v)) {
          for (i = 0; i < v.length; i++) {
            fd.append(k, v[i]);
          }
        } else {
          fd.append(k, v);
        }
      }
    }
    fd.append(this.options.field_name, file.file);
    if (xhr.readyState == 1) {
      xhr.send(fd);
    }
  }

  function Filelist(options) {
    var files_overwrite = [],
      modals = [];
    const i18nn = new i18n({
      locale: options.language
    });
    i18nn.loadJSON(main_translations);
    function action_button_delete_handle_click(e) {
      if (confirm(i18nn.gettext("Delete selected files and folders?"))) {
        var fd = new FormData(),
          els = document.querySelectorAll('.datatable input[name="id[]"],.file-grid input[name="id[]"]');
        for (let i = 0; i < els.length; i++) {
          if (els[i].checked) {
            fd.append("ids[]", els[i].value);
          }
        }
        var xhr = new XMLHttpRequest();
        xhr.onreadystatechange = () => {
          if (xhr.readyState === 4) {
            window.location.assign(options.return_url);
          }
        };
        xhr.open("POST", options.delete_url, true);
        xhr.send(fd);
      }
    }

    /*function action_button_move_files_handle_click(e) {
      modal = move_files_modal()
    }*/

    function action_checkbox_handle_change(e) {
      var els = document.querySelectorAll('.datatable input[name="id[]"],.file-grid input[name="id[]"]');
      var checked = 0;
      for (let i = 0; i < els.length; i++) {
        if (els[i].checked) {
          checked++;
        }
      }
      [].forEach.call(document.querySelectorAll(".action-btn"), function (el) {
        if (checked) {
          el.style.removeProperty("display");
        } else {
          el.style.display = "none";
        }
      });
    }
    function file_overwrite_modal(filename) {
      let modal = new tingle.modal({
        closeLabel: "Закрыть",
        closeMethods: [],
        cssClass: ["tmodal"],
        footer: true,
        onClose: () => {
          window.setTimeout(() => {
            files_overwrite.splice(files_overwrite.indexOf(modal._file));
            modal.destroy();
            modals.splice(modals.indexOf(modal), 1);
          });
        }
      });
      modal.addFooterBtn(i18nn.pgettext("upload_file_overwrite_dialog", "Abort"), "btn btn-secondary tingle-btn--pull-right", () => {
        modal.close();
        upload.cancelUpload();
        files_overwrite = [];
      });
      modal.addFooterBtn(i18nn.pgettext("upload_file_overwrite_dialog", "None"), "btn btn-secondary tingle-btn--pull-right", () => {
        modal.close();
        for (let i = 0; i < upload.files.length; i++) {
          upload.files[i];
          if (upload.files[i].status === "queued" || upload.files[i].status === "uploading") {
            upload.files[i].overwrite = false;
          }
        }
        files_overwrite = [];
      });
      modal.addFooterBtn(i18nn.pgettext("upload_file_overwrite_dialog", "All"), "btn btn-secondary tingle-btn--pull-right", () => {
        modal.close();
        for (let i = 0; i < upload.files.length; i++) {
          if (upload.files[i].status === "queued" || upload.files[i].status === "uploading") {
            upload.files[i].overwrite = true;
          }
        }
        files_overwrite.push(modal._file);
        upload.enqueueFiles(files_overwrite.map(function (file) {
          return file.file;
        }), file => {
          file.overwrite = true;
        });
        files_overwrite = [];
      });
      modal.addFooterBtn(i18nn.pgettext("upload_file_overwrite_dialog", "No"), "btn btn-secondary tingle-btn--pull-right", () => {
        modal.close();
        let file;
        do {
          file = files_overwrite.shift();
        } while (file && file.overwrite !== undefined);
        if (file) {
          let modal = file_overwrite_modal(file.file.name);
          modal._file = file;
        }
      });
      modal.addFooterBtn(i18nn.pgettext("upload_file_overwrite_dialog", "Yes"), "btn btn-secondary tingle-btn--pull-right", () => {
        upload.enqueueFiles([modal._file.file], file => {
          file.overwrite = true;
        });
        modal.close();
        let file;
        do {
          file = files_overwrite.shift();
        } while (file && file.overwrite !== undefined);
        if (file) {
          let modal = file_overwrite_modal(file.file.name);
          modal._file = file;
        }
      });
      modal.setContent(i18nn.pgettext("upload_file_overwrite_dialog", "File already exists %s. Overwrite this file?").replace("%s", filename));
      modal.open();
      modals.push(modal);
      return modal;
    }
    function file_size_format(size) {
      size = parseInt(size);
      if (size < 1000 * 1000) {
        return Math.round(size / 1000) + " K";
      } else if (size < 1000 * 1000 * 1000) {
        return Math.round(size / (1000 * 1000) * 10) / 10 + " M";
      } else if (size < 1000 * 1000 * 1000 * 1000) {
        return Math.round(size / (1000 * 1000 * 1000) * 10) / 10 + " G";
      } else {
        return size;
      }
    }
    function draw_datatable(data) {
      var content = "";
      if (data && data.data && Array.isArray(data.data)) {
        let attr_count = data.attributes && Array.isArray(data.attributes) ? data.attributes.length : 0;
        for (let i = 0; i < data.data.length; i++) {
          let item = data.data[i];
          item.action_url = "/storage/action/" + data.storage_id;
          //item.modified = moment(item.modified).format("L LT")
          item.size = item.size ? file_size_format(item.size) : "";
          item.attributes = "";
          for (let j = 0; j < attr_count; j++) {
            item.attributes += "<td>" + (item[data.attributes[j].name] || "") + "</td>";
          }
          item.select_file = i18nn.gettext("Select file");
          if (data.view_mode) {
            content += `<div class="file-grid-item">` + (item.folder ? `<a href="${item.folder_url}"><img class="file-grid-icon" src="${data.static_url}/assets/img/file_folder.svg"></a>` : `<a href="${data.base_url}${item.file}">` + (item.image ? `<img class="lazyload" data-src="${data.base_url}${item.image}">` : item.type === 2 && item.mime_type === "image/svg+xml" ? `<img class="lazyload" data-src="${data.base_url}${item.file}"></a>` : item.type === 1 ? `<img class="file-grid-icon" src="${data.static_url}/assets/img/file_pdf.svg">` : item.type === 2 ? `<img class="file-grid-icon" src="${data.static_url}/assets/img/image.webp">` : `<img class="file-grid-icon" src="${data.static_url}/assets/img/file2.svg">`) + `</a>`) + `<div class="file-grid-actions"><input class="file-grid-actions-checkbox form-check-input" name="id[]" title="${item.select_file}" type="checkbox" value="${item.id}">
<a class="btn btn-secondary image-btn" href="${item.edit_url}" title="` + (item.folder ? i18nn.gettext("Rename folder") : i18nn.gettext("Edit file attributes")) + `"><img src="${data.static_url}/assets/img/icon_pencil.svg"></a></div>
<div class="file-grid-item-text">` + (item.folder ? `<a href="${item.folder_url}">${file.name}</a>` : `<a href="${data.base_url}${item.file}">${item.name}</a>`) + `</div></div>`;
          } else {
            content += `<tr>` + (options.can_edit ? `<td><div class="datatable-actions">
<input class="datatable-actions-checkbox form-check-input" name="id[]" title="${item.select_file}" type="checkbox" value="${item.id}">
<a class="btn btn-secondary image-btn" href="${item.edit_url}" title="` + (item.folder ? i18nn.gettext("Rename folder") : i18nn.gettext("Edit file attributes")) + `"><img src="${data.static_url}/assets/img/icon_pencil.svg"></a>
</div></td>` : "") + (item.folder ? `<td><a href="${item.folder_url}"><img class="datatable-file-icon" src="${data.static_url}/assets/img/icon_folder.svg">${item.name}</a></td>` : `<td><a href="${data.base_url}${item.file}" target="_blank"><img class="datatable-file-icon" src="${data.static_url}/assets/img/icon_file.svg">${item.name}</a></td>`) + `${item.attributes}
</tr>`;
          }
        }
      }
      let container = document.querySelector(data.view_mode ? ".js-file-grid" : ".js-datatable-file-list");
      if (container) {
        container.innerHTML = content;
        !container.querySelectorAll('input[name="id[]"]').forEach(function (el) {
          el.addEventListener("change", action_checkbox_handle_change);
        });
      }
      function pager_url(p) {
        var url = (data.return_url || options.location.toString() || "").split("?");
        var params = new URLSearchParams(url[1] || "");
        if (p) {
          params.set("p", p);
        } else {
          params.delete("p");
        }
        params = params.toString();
        return url[0] + (params ? "?" + params : "");
      }
      document.getElementById("list-item-count").innerText = translations[options.language].item_count(data.count);
      document.getElementById("compact-pager-container").innerHTML = pager.pagerCompact("p", data.count, data.page_size, pager_url);
      document.getElementById("pager-container").innerHTML = pager.pager("p", data.count, data.page_size, pager_url);
      let filter_reset_btn = document.getElementById("datatable-filter-reset");
      if (filter_reset_btn) {
        if (data.show_filter) {
          filter_reset_btn.style.removeProperty("display");
        } else {
          filter_reset_btn.style.display = "none";
        }
      }
      if (!data.view_mode) {
        let container = document.getElementById("datatable-container"),
          filter_container = document.getElementById("datatable-filter");
        !document.querySelectorAll(".datatable-filter-alert,.datatable-filter-error").forEach(el => el.parentNode.removeChild(el));
        !filter_container.querySelectorAll("input,textarea").forEach(el => el.classList.remove("is-invalid"));
        let filter_errors = [];
        if (data.filter_form_errors) {
          for (let k in data.filter_form_errors) {
            if (data.filter_form_errors.hasOwnProperty(k)) {
              let r = data.filter_form_errors[k];
              if (r) {
                let input_with_error = document.getElementById(k);
                if (input_with_error) {
                  input_with_error.classList.add("is-invalid");
                  let el = document.createElement("div");
                  el.innerHTML = '<div class="invalid-feedback datatable-filter-error">' + (Array.isArray(r) ? r.join("<br>") : r) + "</div>";
                  input_with_error.parentNode.insertBefore(el.firstChild, input_with_error.nextSibling);
                } else {
                  filter_errors.push(Array.isArray(r) ? r.join("<br>") : r);
                }
              }
            }
          }
        }
        if (Array.isArray(filter_errors) && filter_errors.length) {
          let el = document.createElement("div");
          el.innerHTML = '<div class="alert alert-danger datatable-filter-alert" style="display:table;margin:0 auto 1em;">' + filter_errors.join("<br>") + "</div>";
          container.parentNode.insertBefore(el.firstChild, container);
        }
      }
    }

    /*function move_files_modal() {
      let modal = new tingle.modal({
        closeLabel: "Закрыть",
        closeMethods: [],
        cssClass: ["tmodal"],
        footer: true,
        onClose: () => {
          window.setTimeout(() => {
            modal.destroy()
            modals.splice(modals.indexOf(modal), 1)
          })
        },
      })
       modal.addFooterBtn(
        i18nn.pgettext("move_files_dialog", "Cancel"),
        "btn btn-secondary tingle-btn--pull-right",
        () => {
          modal.close()
        },
      )
       modal.addFooterBtn(
        i18nn.pgettext("move_files_dialog", "Move files"),
        "btn btn-secondary tingle-btn--pull-right",
        () => {
          modal.close()
        },
      )
       modal.setContent(
        i18nn.pgettext("move_files_dialog", "Select destination folder?"),
      )
       modal.open()
       modals.push(modal)
       return modal
    }*/

    function process_file_overwrite(file) {
      let isModalOpen = false,
        modal = null;
      for (let i = 0; i < modals.length; i++) {
        if (!isModalOpen && modals[i].isOpen()) {
          isModalOpen = true;
        }
        if (modal === null && !modals[i].isOpen()) {
          modal = modals[i];
        }
      }
      files_overwrite.push(file);
      if (!isModalOpen) {
        let f;
        do {
          f = files_overwrite.shift();
        } while (f && f.overwrite !== undefined);
        if (f) {
          if (!modal) {
            modal = file_overwrite_modal(f.file.name);
          }
          modal._file = f;
          modal.open();
        }
      }
    }
    !document.getElementById("select-all").addEventListener("change", e => {
      [].forEach.call(document.querySelectorAll('.datatable input[name="id[]"],.file-grid input[name="id[]"]'), el => el.checked = e.target.checked);
      action_checkbox_handle_change();
    });
    !document.querySelectorAll('.datatable input[name="id[]"],.file-grid input[name="id[]"]').forEach(el => el.addEventListener("change", action_checkbox_handle_change));
    !document.getElementById("action_delete").addEventListener("click", action_button_delete_handle_click);

    /*!document
      .getElementById("action_move")
      .addEventListener("click", action_button_move_files_handle_click)*/

    !document.querySelectorAll("#download-btn,#export-btn").forEach(el => el.addEventListener("click", e => {
      var selected = document.querySelectorAll('.datatable input[name="id[]"]:checked,.file-grid input[name="id[]"]:checked');
      if (selected.length) {
        e.preventDefault();
        let form = document.createElement("form");
        form.setAttribute("action", e.currentTarget.getAttribute("href"));
        form.setAttribute("enctype", "multipart/form-data");
        form.setAttribute("method", "post");
        form.setAttribute("style", "position:absolute;height:0;width:0;visibility:hidden");
        for (let i = 0; i < selected.length; i++) {
          let f = document.createElement("input");
          f.setAttribute("type", "hidden");
          f.setAttribute("name", "id[]");
          f.setAttribute("value", selected[i].getAttribute("value"));
          form.appendChild(f);
        }
        document.body.appendChild(form);
        form.submit();
      }
    }));
    !document.querySelectorAll(".datatable-filter-control").forEach(el => el.addEventListener("keyup", throttle(() => {
      let xhr = new window.XMLHttpRequest();
      let url = options.clear_filter_url.replace(/\?+$/g, "");
      url += url.indexOf("?") !== -1 ? "&js=1" : "?js=1";
      xhr.open("post", url, true);
      xhr.setRequestHeader("Accept", "application/json");
      xhr.setRequestHeader("Cache-Control", "no-cache");
      xhr.onload = function (e) {
        var response;
        if (xhr.readyState !== 4) {
          return;
        }
        if (xhr.responseType !== "arraybuffer" && xhr.responseType !== "blob") {
          response = xhr.responseText;
          if (xhr.getResponseHeader("content-type") && ~xhr.getResponseHeader("content-type").indexOf("application/json")) {
            try {
              response = JSON.parse(response);
            } catch (err) {}
          }
        }
        if (response) {
          draw_datatable(response);
          if (window.lazyLoadInstance) {
            window.lazyLoadInstance.update();
          }
        }
      };
      let fd = new FormData(),
        list = document.querySelectorAll(".datatable-filter-control");
      for (let i = 0; i < list.length; i++) {
        fd.append(list[i].name, list[i].value);
      }
      xhr.send(fd);
    }, 500)));
    const pager = new Pager({
      language: options.language
    });
    const upload = new Upload({
      cancelButton: document.getElementById("upload-cancel-btn"),
      concurency: options.upload_concurency,
      dropzone: document.body,
      language: options.language,
      onError: throttle((file, response) => {
        if (file.xhr && file.xhr.status === 409) {
          process_file_overwrite(file);
        } else {
          if (alert && alert instanceof Element) {
            alert.innerHTML = response || i18nn.gettext("File upload error");
          } else {
            let container = document.querySelector(".container-fluid");
            if (container) {
              alert = document.createElement("div");
              alert.className = "alert alert-danger alert-small";
              alert.innerHTML = response || i18nn.gettext("File upload error");
              if (container.firstElementChild) {
                container.insertBefore(alert, container.firstElementChild);
              } else {
                container.appendChild(alert);
              }
            }
          }
          let xhr = new XMLHttpRequest();
          xhr.onload = () => {
            if (xhr.readyState === 4) {
              let response;
              if (xhr.responseType !== "arraybuffer" && xhr.responseType !== "blob") {
                response = xhr.responseText;
                if (xhr.getResponseHeader("content-type") && ~xhr.getResponseHeader("content-type").indexOf("application/json")) {
                  try {
                    response = JSON.parse(response);
                  } catch (err) {}
                }
              }
              if (response) {
                draw_datatable(response);
                if (window.lazyLoadInstance) {
                  window.lazyLoadInstance.update();
                }
              }
            }
          };
          let url = options.return_url.replace(/\?+$/g, "");
          url += url.indexOf("?") !== -1 ? "&js=1" : "?js=1";
          xhr.open("get", url, true);
          xhr.setRequestHeader("Accept", "application/json");
          xhr.setRequestHeader("Content-Type", "application/json");
          xhr.send();
        }
      }, 2000),
      onCancel: throttle(() => {
        let xhr = new XMLHttpRequest();
        xhr.onload = () => {
          if (xhr.readyState === 4) {
            let response;
            if (xhr.responseType !== "arraybuffer" && xhr.responseType !== "blob") {
              response = xhr.responseText;
              if (xhr.getResponseHeader("content-type") && ~xhr.getResponseHeader("content-type").indexOf("application/json")) {
                try {
                  response = JSON.parse(response);
                } catch (err) {}
              }
            }
            if (response) {
              draw_datatable(response);
              if (window.lazyLoadInstance) {
                window.lazyLoadInstance.update();
              }
            }
          }
        };
        let url = options.return_url.replace(/\?+$/g, "");
        url += url.indexOf("?") !== -1 ? "&js=1" : "?js=1";
        xhr.open("get", url, true);
        xhr.setRequestHeader("Accept", "application/json");
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send();
      }, 2000),
      onProgress: () => {
        if (alert && alert instanceof Element) {
          alert.parentNode.removeChild(alert);
          alert = null;
        }
      },
      onComplete: throttle((file, response) => {
        let xhr = new XMLHttpRequest();
        xhr.onload = () => {
          if (xhr.readyState === 4) {
            let response;
            if (xhr.responseType !== "arraybuffer" && xhr.responseType !== "blob") {
              response = xhr.responseText;
              if (xhr.getResponseHeader("content-type") && ~xhr.getResponseHeader("content-type").indexOf("application/json")) {
                try {
                  response = JSON.parse(response);
                } catch (err) {}
              }
            }
            if (response) {
              draw_datatable(response);
              if (window.lazyLoadInstance) {
                window.lazyLoadInstance.update();
              }
            }
          }
        };
        let url = options.return_url.replace(/\?+$/g, "");
        url += url.indexOf("?") !== -1 ? "&js=1" : "?js=1";
        xhr.open("get", url, true);
        xhr.setRequestHeader("Accept", "application/json");
        xhr.setRequestHeader("Content-Type", "application/json");
        xhr.send();
      }, 2000),
      timeout: options.upload_timeout,
      totalProgressClass: "upload-progress",
      totalProgressContainer: document.getElementById("upload-progress"),
      uploadButton: document.getElementById("upload-btn"),
      url: options.upload_url
    });
  }

  return Filelist;

})();
