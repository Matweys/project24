import i18n from "gettext.js"
import main_translations from "../i18n/translations.json"
import menu from "./menu.json"

class Help {
  constructor(options) {
    this.i18n = new i18n({ locale: options.language })
    this.i18n.loadJSON(main_translations)
    this.modals = []
    this.options = options

    window.addEventListener("popstate", (e) => {
      let state = e.state || {}

      if (state.slug) {
        let modal = this.modals.length
          ? this.modals[this.modals.length - 1]
          : this.createModal()

        Help.setModalContent(modal, state.text)

        modal.show()
      } else {
        this.modals.forEach((modal) => modal.hide())
        document.title = options.title
      }
    })

    !document.getElementById("navbar_help").addEventListener("click", (e) => {
      e.preventDefault()

      let xhr = new XMLHttpRequest()

      xhr.onreadystatechange = () => {
        if (xhr.readyState === 4) {
          if (xhr.status !== 200) {
            alert(
              xhr.responseText ||
                this.i18n.pgettext("help", "Text retrieval error"),
            )
          } else {
            let modal = this.createModal(xhr.responseText)

            window.setTimeout(
              () =>
                (document.title = `${this.i18n.pgettext(
                  "help",
                  "Help — Archivarius Digital Archive",
                )} - ${menu[options.language][0].label}`),
              300,
            )

            window.history.pushState(
              {
                slug: menu[options.language][0].name,
                language: options.language,
                text: xhr.responseText,
              },
              "",
              `${(options.return_url || "").split("#")[0]}#help/${
                options.language
              }/${menu[options.language][0].name}`,
            )

            modal.show()
          }
        }
      }

      xhr.open(
        "GET",
        `${(options.base_url || "").replace(/\/+$/, "")}/${options.language}/${
          menu[options.language][0].name
        }.html`,
      )
      xhr.send()
    })

    if (window.location.hash) {
      let m = /help\/(en|ru)\/(.*)/g.exec(window.location.hash)
      if (m && m[1] && m[2]) {
        let modal = this.modals.length
          ? this.modals[this.modals.length - 1]
          : this.createModal()

        Help.loadModalContent(
          modal,
          m[2], // slug
          m[1], // language
          this.options.base_url,
          this.options.return_url,
          this.i18n,
        )

        modal.show()
      }
    }
  }

  createModal(content) {
    let menu_str =
      '<nav class="nav flex-column">' +
      menu[this.options.language].reduce(
        (acc, x) =>
          acc +
          (acc
            ? `<li class="nav-item"><a class="nav-link" href="#${x["name"]}">${x["label"]}</a></li>`
            : `<li class="nav-item"><a aria-current="page" class="nav-link active" href="#${x["name"]}">${x["label"]}</a></li>`),
        "",
      ) +
      "</nav>"

    let tpl = document.createElement("template")
    tpl.innerHTML = `<div class="modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-xl modal-fullscreen-xl-down">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">${this.i18n.pgettext(
          "help",
          "Help",
        )}</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="${this.i18n.pgettext(
          "move_files",
          "Close",
        )}"></button>
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
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this.i18n.pgettext(
          "move_files",
          "Close",
        )}</button>
      </div>
    </div>
  </div>
</div>`

    let el = tpl.content.firstElementChild
    document.body.appendChild(tpl.content, document.body.firstChild)
    let modal = new bootstrap.Modal(el)
    this.modals.push(modal)

    modal._element.addEventListener("hidden.bs.modal", (e) => {
      window.setTimeout(() => {
        let modal = bootstrap.Modal.getInstance(e.target)

        if (modal) {
          let element = modal._element
          this.modals.splice(this.modals.indexOf(modal), 1)
          modal.dispose()
          element.parentNode.removeChild(element)

          if (!this.modals.length && window.location.href.indexOf("#")) {
            setTimeout(() => (document.title = this.options.title), 300)
            window.history.pushState(null, "", this.options.return_url)
          }
        }
      })
    })

    let menu_item_handle_click = (e) => {
      e.preventDefault()
      let slug = e.target.getAttribute("href")
      slug = slug[0] == "#" ? slug.replace("#", "") : slug
      Help.loadModalContent(
        modal,
        slug,
        this.options.language,
        this.options.base_url,
        this.options.return_url,
        this.i18n,
      )
    }

    !modal._element.querySelectorAll(".menu .nav-link").forEach((el) => {
      el.addEventListener("click", menu_item_handle_click)
    })

    return modal
  }

  static loadModalContent(
    modal,
    slug,
    language,
    base_url,
    browser_base_url,
    i18n,
  ) {
    if (slug) {
      let xhr = new XMLHttpRequest()

      xhr.onreadystatechange = () => {
        if (xhr.readyState === 4) {
          if (xhr.status !== 200) {
            alert(
              xhr.responseText ||
                this.i18n.pgettext("help", "Text retrieval error"),
            )
          } else {
            let menu_item_label = (
              menu[language].find((x) => x.name === slug) || {}
            ).label

            let title = `${i18n.pgettext(
              "help",
              "Help — Archivarius Digital Archive",
            )} - ${menu_item_label}`

            window.setTimeout(() => (document.title = title), 300)

            window.history.pushState(
              {
                language: language,
                slug: slug,
                text: xhr.responseText,
              },
              "",
              `${
                (browser_base_url || "").split("#")[0]
              }#help/${language}/${slug}`,
            )

            modal._element.querySelector(".content").innerHTML =
              xhr.responseText
          }
        }
      }

      xhr.open(
        "GET",
        `${(base_url || "").replace(/\/+$/, "")}/${language}/${slug}.html`,
      )

      xhr.send()
    }
  }

  static setModalContent(modal, content) {
    if (modal) {
      modal._element.querySelector(".content").innerHTML = content
    }
  }
}

export default Help
