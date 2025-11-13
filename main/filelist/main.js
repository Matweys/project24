import { throttle } from "lodash-es"
import i18n from "gettext.js"
import main_translations from "../i18n/translations.json"
import Pager from "./pager.js"
import translations from "./translations.js"
import Upload from "./upload.js"

function Filelist(options) {
  var files_overwrite = [],
    modals = []

  const i18nn = new i18n({ locale: options.language })
  i18nn.loadJSON(main_translations)

  function action_button_delete_handle_click(e) {
    if (confirm(i18nn.gettext("Delete selected files and folders?"))) {
      var fd = new FormData(),
        els = document.querySelectorAll(
          '.datatable input[name="id[]"],.file-grid input[name="id[]"]',
        )

      for (let i = 0; i < els.length; i++) {
        if (els[i].checked) {
          fd.append("ids[]", els[i].value)
        }
      }

      var xhr = new XMLHttpRequest()

      xhr.onreadystatechange = () => {
        if (xhr.readyState === 4) {
          window.location.assign(options.return_url)
        }
      }

      xhr.open("POST", options.delete_url, true)
      xhr.send(fd)
    }
  }

  /*function action_button_move_files_handle_click(e) {
    modal = move_files_modal()
  }*/

  function action_checkbox_handle_change(e) {
    var els = document.querySelectorAll(
      '.datatable input[name="id[]"],.file-grid input[name="id[]"]',
    )
    var checked = 0

    for (let i = 0; i < els.length; i++) {
      if (els[i].checked) {
        checked++
      }
    }

    ;[].forEach.call(document.querySelectorAll(".action-btn"), function (el) {
      if (checked) {
        el.style.removeProperty("display")
      } else {
        el.style.display = "none"
      }
    })
  }

  function file_overwrite_modal(filename) {
    let modal = new tingle.modal({
      closeLabel: "Закрыть",
      closeMethods: [],
      cssClass: ["tmodal"],
      footer: true,
      onClose: () => {
        window.setTimeout(() => {
          files_overwrite.splice(files_overwrite.indexOf(modal._file))
          modal.destroy()
          modals.splice(modals.indexOf(modal), 1)
        })
      },
    })

    modal.addFooterBtn(
      i18nn.pgettext("upload_file_overwrite_dialog", "Abort"),
      "btn btn-secondary tingle-btn--pull-right",
      () => {
        modal.close()
        upload.cancelUpload()
        files_overwrite = []
      },
    )

    modal.addFooterBtn(
      i18nn.pgettext("upload_file_overwrite_dialog", "None"),
      "btn btn-secondary tingle-btn--pull-right",
      () => {
        modal.close()

        for (let i = 0; i < upload.files.length; i++) {
          let file = upload.files[i]
          if (
            upload.files[i].status === "queued" ||
            upload.files[i].status === "uploading"
          ) {
            upload.files[i].overwrite = false
          }
        }

        files_overwrite = []
      },
    )

    modal.addFooterBtn(
      i18nn.pgettext("upload_file_overwrite_dialog", "All"),
      "btn btn-secondary tingle-btn--pull-right",
      () => {
        modal.close()

        for (let i = 0; i < upload.files.length; i++) {
          if (
            upload.files[i].status === "queued" ||
            upload.files[i].status === "uploading"
          ) {
            upload.files[i].overwrite = true
          }
        }

        files_overwrite.push(modal._file)
        upload.enqueueFiles(
          files_overwrite.map(function (file) {
            return file.file
          }),
          (file) => {
            file.overwrite = true
          },
        )

        files_overwrite = []
      },
    )

    modal.addFooterBtn(
      i18nn.pgettext("upload_file_overwrite_dialog", "No"),
      "btn btn-secondary tingle-btn--pull-right",
      () => {
        modal.close()

        let file
        do {
          file = files_overwrite.shift()
        } while (file && file.overwrite !== undefined)

        if (file) {
          let modal = file_overwrite_modal(file.file.name)
          modal._file = file
        }
      },
    )

    modal.addFooterBtn(
      i18nn.pgettext("upload_file_overwrite_dialog", "Yes"),
      "btn btn-secondary tingle-btn--pull-right",
      () => {
        upload.enqueueFiles([modal._file.file], (file) => {
          file.overwrite = true
        })
        modal.close()

        let file
        do {
          file = files_overwrite.shift()
        } while (file && file.overwrite !== undefined)

        if (file) {
          let modal = file_overwrite_modal(file.file.name)
          modal._file = file
        }
      },
    )

    modal.setContent(
      i18nn
        .pgettext(
          "upload_file_overwrite_dialog",
          "File already exists %s. Overwrite this file?",
        )
        .replace("%s", filename),
    )
    modal.open()

    modals.push(modal)

    return modal
  }

  function file_size_format(size) {
    size = parseInt(size)

    if (size < 1000 * 1000) {
      return Math.round(size / 1000) + " K"
    } else if (size < 1000 * 1000 * 1000) {
      return Math.round((size / (1000 * 1000)) * 10) / 10 + " M"
    } else if (size < 1000 * 1000 * 1000 * 1000) {
      return Math.round((size / (1000 * 1000 * 1000)) * 10) / 10 + " G"
    } else {
      return size
    }
  }

  function draw_datatable(data) {
    var content = ""

    if (data && data.data && Array.isArray(data.data)) {
      let attr_count =
        data.attributes && Array.isArray(data.attributes)
          ? data.attributes.length
          : 0

      for (let i = 0; i < data.data.length; i++) {
        let item = data.data[i]

        item.action_url = "/storage/action/" + data.storage_id
        //item.modified = moment(item.modified).format("L LT")
        item.size = item.size ? file_size_format(item.size) : ""
        item.attributes = ""

        for (let j = 0; j < attr_count; j++) {
          item.attributes +=
            "<td>" + (item[data.attributes[j].name] || "") + "</td>"
        }

        item.select_file = i18nn.gettext("Select file")

        if (data.view_mode) {
          content +=
            `<div class="file-grid-item">` +
            (item.folder
              ? `<a href="${item.folder_url}"><img class="file-grid-icon" src="${data.static_url}/assets/img/file_folder.svg"></a>`
              : `<a href="${data.base_url}${item.file}">` +
                (item.image
                  ? `<img class="lazyload" data-src="${data.base_url}${item.image}">`
                  : item.type === 2 && item.mime_type === "image/svg+xml"
                  ? `<img class="lazyload" data-src="${data.base_url}${item.file}"></a>`
                  : item.type === 1
                  ? `<img class="file-grid-icon" src="${data.static_url}/assets/img/file_pdf.svg">`
                  : item.type === 2
                  ? `<img class="file-grid-icon" src="${data.static_url}/assets/img/image.webp">`
                  : `<img class="file-grid-icon" src="${data.static_url}/assets/img/file2.svg">`) +
                `</a>`) +
            `<div class="file-grid-actions"><input class="file-grid-actions-checkbox form-check-input" name="id[]" title="${item.select_file}" type="checkbox" value="${item.id}">
<a class="btn btn-secondary image-btn" href="${item.edit_url}" title="` +
            (item.folder
              ? i18nn.gettext("Rename folder")
              : i18nn.gettext("Edit file attributes")) +
            `"><img src="${data.static_url}/assets/img/icon_pencil.svg"></a></div>
<div class="file-grid-item-text">` +
            (item.folder
              ? `<a href="${item.folder_url}">${file.name}</a>`
              : `<a href="${data.base_url}${item.file}">${item.name}</a>`) +
            `</div></div>`
        } else {
          content +=
            `<tr>` +
            (options.can_edit
              ? `<td><div class="datatable-actions">
<input class="datatable-actions-checkbox form-check-input" name="id[]" title="${item.select_file}" type="checkbox" value="${item.id}">
<a class="btn btn-secondary image-btn" href="${item.edit_url}" title="` +
                (item.folder
                  ? i18nn.gettext("Rename folder")
                  : i18nn.gettext("Edit file attributes")) +
                `"><img src="${data.static_url}/assets/img/icon_pencil.svg"></a>
</div></td>`
              : "") +
            (item.folder
              ? `<td><a href="${item.folder_url}"><img class="datatable-file-icon" src="${data.static_url}/assets/img/icon_folder.svg">${item.name}</a></td>`
              : `<td><a href="${data.base_url}${item.file}" target="_blank"><img class="datatable-file-icon" src="${data.static_url}/assets/img/icon_file.svg">${item.name}</a></td>`) +
            `${item.attributes}
</tr>`
        }
      }
    }

    let container = document.querySelector(
      data.view_mode ? ".js-file-grid" : ".js-datatable-file-list",
    )

    if (container) {
      container.innerHTML = content
      !container.querySelectorAll('input[name="id[]"]').forEach(function (el) {
        el.addEventListener("change", action_checkbox_handle_change)
      })
    }

    function pager_url(p) {
      var url = (data.return_url || options.location.toString() || "").split(
        "?",
      )

      var params = new URLSearchParams(url[1] || "")

      if (p) {
        params.set("p", p)
      } else {
        params.delete("p")
      }

      params = params.toString()

      return url[0] + (params ? "?" + params : "")
    }

    document.getElementById("list-item-count").innerText = translations[
      options.language
    ].item_count(data.count)

    document.getElementById("compact-pager-container").innerHTML =
      pager.pagerCompact("p", data.count, data.page_size, pager_url)

    document.getElementById("pager-container").innerHTML = pager.pager(
      "p",
      data.count,
      data.page_size,
      pager_url,
    )

    let filter_reset_btn = document.getElementById("datatable-filter-reset")

    if (filter_reset_btn) {
      if (data.show_filter) {
        filter_reset_btn.style.removeProperty("display")
      } else {
        filter_reset_btn.style.display = "none"
      }
    }

    if (!data.view_mode) {
      let container = document.getElementById("datatable-container"),
        filter_container = document.getElementById("datatable-filter")

      !document
        .querySelectorAll(".datatable-filter-alert,.datatable-filter-error")
        .forEach((el) => el.parentNode.removeChild(el))

      !filter_container
        .querySelectorAll("input,textarea")
        .forEach((el) => el.classList.remove("is-invalid"))

      let filter_errors = []

      if (data.filter_form_errors) {
        for (let k in data.filter_form_errors) {
          if (data.filter_form_errors.hasOwnProperty(k)) {
            let r = data.filter_form_errors[k]

            if (r) {
              let input_with_error = document.getElementById(k)

              if (input_with_error) {
                input_with_error.classList.add("is-invalid")

                let el = document.createElement("div")
                el.innerHTML =
                  '<div class="invalid-feedback datatable-filter-error">' +
                  (Array.isArray(r) ? r.join("<br>") : r) +
                  "</div>"

                input_with_error.parentNode.insertBefore(
                  el.firstChild,
                  input_with_error.nextSibling,
                )
              } else {
                filter_errors.push(Array.isArray(r) ? r.join("<br>") : r)
              }
            }
          }
        }
      }

      if (Array.isArray(filter_errors) && filter_errors.length) {
        let el = document.createElement("div")
        el.innerHTML =
          '<div class="alert alert-danger datatable-filter-alert" style="display:table;margin:0 auto 1em;">' +
          filter_errors.join("<br>") +
          "</div>"
        container.parentNode.insertBefore(el.firstChild, container)
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
      modal = null

    for (let i = 0; i < modals.length; i++) {
      if (!isModalOpen && modals[i].isOpen()) {
        isModalOpen = true
      }
      if (modal === null && !modals[i].isOpen()) {
        modal = modals[i]
      }
    }

    files_overwrite.push(file)

    if (!isModalOpen) {
      let f
      do {
        f = files_overwrite.shift()
      } while (f && f.overwrite !== undefined)

      if (f) {
        if (!modal) {
          modal = file_overwrite_modal(f.file.name)
        }

        modal._file = f
        modal.open()
      }
    }
  }

  !document.getElementById("select-all").addEventListener("change", (e) => {
    ;[].forEach.call(
      document.querySelectorAll(
        '.datatable input[name="id[]"],.file-grid input[name="id[]"]',
      ),
      (el) => (el.checked = e.target.checked),
    )

    action_checkbox_handle_change()
  })

  !document
    .querySelectorAll(
      '.datatable input[name="id[]"],.file-grid input[name="id[]"]',
    )
    .forEach((el) =>
      el.addEventListener("change", action_checkbox_handle_change),
    )

  !document
    .getElementById("action_delete")
    .addEventListener("click", action_button_delete_handle_click)

  /*!document
    .getElementById("action_move")
    .addEventListener("click", action_button_move_files_handle_click)*/

  !document.querySelectorAll("#download-btn,#export-btn").forEach((el) =>
    el.addEventListener("click", (e) => {
      var selected = document.querySelectorAll(
        '.datatable input[name="id[]"]:checked,.file-grid input[name="id[]"]:checked',
      )

      if (selected.length) {
        e.preventDefault()

        let form = document.createElement("form")
        form.setAttribute("action", e.currentTarget.getAttribute("href"))
        form.setAttribute("enctype", "multipart/form-data")
        form.setAttribute("method", "post")
        form.setAttribute(
          "style",
          "position:absolute;height:0;width:0;visibility:hidden",
        )

        for (let i = 0; i < selected.length; i++) {
          let f = document.createElement("input")
          f.setAttribute("type", "hidden")
          f.setAttribute("name", "id[]")
          f.setAttribute("value", selected[i].getAttribute("value"))
          form.appendChild(f)
        }

        document.body.appendChild(form)
        form.submit()
      }
    }),
  )

  !document.querySelectorAll(".datatable-filter-control").forEach((el) =>
    el.addEventListener(
      "keyup",
      throttle(() => {
        let xhr = new window.XMLHttpRequest()

        let url = options.clear_filter_url.replace(/\?+$/g, "")
        url += url.indexOf("?") !== -1 ? "&js=1" : "?js=1"

        xhr.open("post", url, true)
        xhr.setRequestHeader("Accept", "application/json")
        xhr.setRequestHeader("Cache-Control", "no-cache")

        xhr.onload = function (e) {
          var response

          if (xhr.readyState !== 4) {
            return
          }

          if (
            xhr.responseType !== "arraybuffer" &&
            xhr.responseType !== "blob"
          ) {
            response = xhr.responseText

            if (
              xhr.getResponseHeader("content-type") &&
              ~xhr.getResponseHeader("content-type").indexOf("application/json")
            ) {
              try {
                response = JSON.parse(response)
              } catch (err) {}
            }
          }

          if (response) {
            draw_datatable(response)
            if (window.lazyLoadInstance) {
              window.lazyLoadInstance.update()
            }
          }
        }

        let fd = new FormData(),
          list = document.querySelectorAll(".datatable-filter-control")

        for (let i = 0; i < list.length; i++) {
          fd.append(list[i].name, list[i].value)
        }

        xhr.send(fd)
      }, 500),
    ),
  )

  const pager = new Pager({ language: options.language })

  const upload = new Upload({
    cancelButton: document.getElementById("upload-cancel-btn"),
    concurency: options.upload_concurency,
    dropzone: document.body,
    language: options.language,
    onError: throttle((file, response) => {
      if (file.xhr && file.xhr.status === 409) {
        process_file_overwrite(file)
      } else {
        if (alert && alert instanceof Element) {
          alert.innerHTML = response || i18nn.gettext("File upload error")
        } else {
          let container = document.querySelector(".container-fluid")
          if (container) {
            alert = document.createElement("div")
            alert.className = "alert alert-danger alert-small"
            alert.innerHTML = response || i18nn.gettext("File upload error")

            if (container.firstElementChild) {
              container.insertBefore(alert, container.firstElementChild)
            } else {
              container.appendChild(alert)
            }
          }
        }

        let xhr = new XMLHttpRequest()
        xhr.onload = () => {
          if (xhr.readyState === 4) {
            let response
            if (
              xhr.responseType !== "arraybuffer" &&
              xhr.responseType !== "blob"
            ) {
              response = xhr.responseText
              if (
                xhr.getResponseHeader("content-type") &&
                ~xhr
                  .getResponseHeader("content-type")
                  .indexOf("application/json")
              ) {
                try {
                  response = JSON.parse(response)
                } catch (err) {}
              }
            }
            if (response) {
              draw_datatable(response)
              if (window.lazyLoadInstance) {
                window.lazyLoadInstance.update()
              }
            }
          }
        }
        let url = options.return_url.replace(/\?+$/g, "")
        url += url.indexOf("?") !== -1 ? "&js=1" : "?js=1"
        xhr.open("get", url, true)
        xhr.setRequestHeader("Accept", "application/json")
        xhr.setRequestHeader("Content-Type", "application/json")
        xhr.send()
      }
    }, 2000),
    onCancel: throttle(() => {
      let xhr = new XMLHttpRequest()
      xhr.onload = () => {
        if (xhr.readyState === 4) {
          let response
          if (
            xhr.responseType !== "arraybuffer" &&
            xhr.responseType !== "blob"
          ) {
            response = xhr.responseText
            if (
              xhr.getResponseHeader("content-type") &&
              ~xhr.getResponseHeader("content-type").indexOf("application/json")
            ) {
              try {
                response = JSON.parse(response)
              } catch (err) {}
            }
          }
          if (response) {
            draw_datatable(response)
            if (window.lazyLoadInstance) {
              window.lazyLoadInstance.update()
            }
          }
        }
      }
      let url = options.return_url.replace(/\?+$/g, "")
      url += url.indexOf("?") !== -1 ? "&js=1" : "?js=1"
      xhr.open("get", url, true)
      xhr.setRequestHeader("Accept", "application/json")
      xhr.setRequestHeader("Content-Type", "application/json")
      xhr.send()
    }, 2000),
    onProgress: () => {
      if (alert && alert instanceof Element) {
        alert.parentNode.removeChild(alert)
        alert = null
      }
    },
    onComplete: throttle((file, response) => {
      let xhr = new XMLHttpRequest()
      xhr.onload = () => {
        if (xhr.readyState === 4) {
          let response
          if (
            xhr.responseType !== "arraybuffer" &&
            xhr.responseType !== "blob"
          ) {
            response = xhr.responseText
            if (
              xhr.getResponseHeader("content-type") &&
              ~xhr.getResponseHeader("content-type").indexOf("application/json")
            ) {
              try {
                response = JSON.parse(response)
              } catch (err) {}
            }
          }
          if (response) {
            draw_datatable(response)
            if (window.lazyLoadInstance) {
              window.lazyLoadInstance.update()
            }
          }
        }
      }
      let url = options.return_url.replace(/\?+$/g, "")
      url += url.indexOf("?") !== -1 ? "&js=1" : "?js=1"
      xhr.open("get", url, true)
      xhr.setRequestHeader("Accept", "application/json")
      xhr.setRequestHeader("Content-Type", "application/json")
      xhr.send()
    }, 2000),
    timeout: options.upload_timeout,
    totalProgressClass: "upload-progress",
    totalProgressContainer: document.getElementById("upload-progress"),
    uploadButton: document.getElementById("upload-btn"),
    url: options.upload_url,
  })
}

export default Filelist
