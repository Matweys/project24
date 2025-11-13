const translations = {
  en: {
    item_count: function (n) {
      var plurals = ["%d item", "%d items"]
      return plurals[n > 1 ? 1 : 0].replace("%d", n)
    },
  },
  ru: {
    item_count: function (n) {
      var plurals = ["%s элемент", "%s элемента", "%s элементов", "1 элемент"]
      return plurals[
        n == 1
          ? 3
          : n % 10 == 1 && n % 100 != 11
          ? 0
          : n % 10 >= 2 && n % 10 <= 4 && (n % 100 < 10 || n % 100 >= 20)
          ? 1
          : 2
      ].replace("%s", n)
    },
  },
}

export default translations
