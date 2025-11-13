var Pager = function (options) {
  this.options = options
}

Pager.l10ns = {}

function appendTo(d, k, v) {
  v = Array.isArray(v) ? v.join(",") : v
  if (k in d) {
    if (!Array.isArray(d[k])) {
      d[k] = [d[k]]
    }
    d[k].push(v)
  } else {
    d[k] = v
  }
}

function URLSearchParams(q) {
  var i,
    index,
    k,
    l,
    pairs,
    v,
    r = Object.create(null)
  if (q && typeof q === "string") {
    if (q.charAt(0) === "?") {
      q = q.slice(1)
    }
    for (pairs = q.split("&"), i = 0, l = pairs.length; i < l; i++) {
      v = pairs[i]
      index = v.indexOf("=")
      if (-1 < index) {
        appendTo(
          r,
          decodeURIComponent(v.slice(0, index).replace("+", " ")),
          decodeURIComponent(v.slice(index + 1)).replace("+", " "),
        )
      } else if (v.length) {
        appendTo(r, decodeURIComponent(v.replace("+", " ")), "")
      }
    }
  }
  return r
}

if (!String.format) {
  var string_format = function (format) {
    var args = Array.prototype.slice.call(arguments, 1)
    return format.replace(/{(\d+)}/g, function (match, number) {
      return typeof args[number] !== "undefined" ? args[number] : match
    })
  }
}

Pager.prototype.pager = function (param, count, page_size, generator) {
  var current_page, i, l10n, max, min, num_pages, r

  l10n = Pager.l10ns[this.options.language]

  if (!generator || page_size <= 0) {
    return ""
  }

  num_pages = Math.ceil(count / page_size)

  if (num_pages < 2) {
    return ""
  }

  r = '<div class="pager">'

  current_page = Math.min(
    Math.max(0, parseInt(URLSearchParams(window.location.search)[param]) || 0),
    num_pages - 1,
  )
  min = current_page - 4
  max = current_page + 4 + 1

  if (min < 0) {
    max = max - min
  }

  if (max >= num_pages) {
    min = min - max + num_pages
  }

  if (min < 0) {
    min = 0
  }

  if (max > num_pages) {
    max = num_pages
  }

  if (current_page > 0) {
    r +=
      '<a class="pager__item" href="' +
      generator(current_page - 1) +
      '">' +
      l10n.previous_page +
      "</a>"
  }

  for (i = min; i < max; i++) {
    if (i == current_page) {
      r += '<span class="pager__item active">' + (i + 1) + "</span>"
    } else {
      r +=
        '<a class="pager__item" href="' + generator(i) + '">' + (i + 1) + "</a>"
    }
  }

  if (current_page + 1 < num_pages) {
    r +=
      '<a class="pager__item" href="' +
      generator(current_page + 1) +
      '">' +
      l10n.next_page +
      "</a>"
  }

  r += "</div>"

  return r
}

Pager.prototype.pagerCompact = function (param, count, page_size, generator) {
  var current_page, l10n, num_pages, offset

  l10n = Pager.l10ns[this.options.language]

  if (!generator || count <= 0 || page_size <= 0) {
    return ""
  }

  num_pages = Math.ceil(count / page_size)
  current_page = Math.min(
    Math.max(0, parseInt(URLSearchParams(window.location.search)[param]) || 0),
    num_pages - 1,
  )
  offset = Math.max(0, Math.min(current_page, num_pages - 1) * page_size)

  return num_pages > 1
    ? '<div class="pager-compact"><div class="pager-compact__info">' +
        (String.format
          ? String.format(
              l10n.pager_compact,
              offset + 1,
              Math.min(offset + page_size, count),
              count,
            )
          : string_format(
              l10n.pager_compact,
              offset + 1,
              Math.min(offset + page_size, count),
              count,
            )) +
        "</div>" +
        (current_page > 0
          ? '<a class="pager-compact__item" href="' +
            generator(current_page - 1) +
            '">❮</a>'
          : '<span class="pager-compact__item pager-compact__item--disabled">❮</span>') +
        (current_page + 1 < num_pages
          ? '<a class="pager-compact__item" href="' +
            generator(current_page + 1) +
            '">❯</a>'
          : '<span class="pager-compact__item pager-compact__item--disabled">❯</span>') +
        "</div>"
    : ""
}

export default Pager
