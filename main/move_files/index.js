import React from "react"
import DropdownTreeSelect from "react-dropdown-tree-select"
import i18n from "gettext.js"
import ReactDOM from "react-dom/client"
import main_translations from "../i18n/translations.json"

class MoveFiles {
  constructor(options) {
    this.i18n = new i18n({ locale: options.language })
    this.modals = []
    this.i18n.loadJSON(main_translations)

    !document.getElementById("action_move").addEventListener("click", (e) => {
      let xhr = new XMLHttpRequest()

      xhr.onreadystatechange = () => {
        if (xhr.readyState === 4) {
          let folder_tree

          if (xhr.status === 200) {
            try {
              folder_tree = JSON.parse(xhr.responseText)
            } catch (ex) {}
          }

          if (!folder_tree) {
            alert(
              this.i18n.pgettext("move_files", "Folder tree retrieval error"),
            )
          } else {
            let modal = MoveFiles.createModal(
              `<div class="modal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-fullscreen-sm-down modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">${this.i18n.pgettext(
          "move_files",
          "Move files",
        )}</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="${this.i18n.pgettext(
          "move_files",
          "Close",
        )}"></button>
      </div>
      <div class="modal-body"><div>${this.i18n.pgettext(
        "move_files",
        "Move selected files to:",
      )}</div><div class="move_files_select"></div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${this.i18n.pgettext(
          "move_files",
          "Close",
        )}</button>
        <button type="button" class="btn btn-primary">${this.i18n.pgettext(
          "move_files",
          "Move files",
        )}</button>
      </div>
    </div>
  </div>
</div>`,
            )

            this.modals.push(modal)

            const assignDefaultValue = (obj) => {
              Object.keys(obj).every((k) => {
                const node = obj[k]
                if (typeof node === "object") {
                  if (node.id === options.folder_id) {
                    node.isDefaultValue = true
                    node.expanded = true
                    return false
                  }
                  assignDefaultValue(node)
                }
                return true
              })
            }

            assignDefaultValue(folder_tree)

            folder_tree = [
              {
                children: folder_tree,
                expanded: true,
                id: 0,
                label: this.i18n.pgettext("move_files", "Root folder"),
              },
            ]

            const FolderSelect = () => {
              this.folderSelectRef = React.useRef(null)

              return (
                <DropdownTreeSelect
                  className="folder-select"
                  data={folder_tree}
                  keepTreeOnSearch
                  mode="radioSelect"
                  ref={this.folderSelectRef}
                  texts={{
                    placeholder: this.i18n.pgettext(
                      "move_files",
                      "Choose destination folder",
                    ),
                  }}
                />
              )
            }

            this.root = ReactDOM.createRoot(
              modal._element.querySelector(".move_files_select"),
            )

            this.root.render(<FolderSelect />)

            modal.show()

            modal._element.addEventListener("hidden.bs.modal", (e) => {
              window.setTimeout(() => {
                let modal = bootstrap.Modal.getInstance(e.target),
                  element = modal._element

                this.root.unmount()
                this.modals.splice(this.modals.indexOf(modal), 1)

                modal.dispose()
                element.parentNode.removeChild(element)
              })
            })

            let btn = modal._element.querySelector("button.btn-primary")

            btn.addEventListener("click", (e) => {
              let selectedFolder

              if (this.folderSelectRef.current) {
                // https://stackoverflow.com/questions/57800726/getting-a-reference-to-a-react-component-using-useref-hook
                selectedFolder = this.folderSelectRef.current.state.currentFocus
              }

              if (selectedFolder) {
                let fd = new FormData(),
                  els = document.querySelectorAll(
                    '.datatable input[name="id[]"],.file-grid input[name="id[]"]',
                  )

                for (let i = 0; i < els.length; i++) {
                  if (els[i].checked) {
                    fd.append("ids[]", els[i].value)
                  }
                }

                fd.append("folder_id", selectedFolder)

                let xhr = new XMLHttpRequest()

                xhr.onreadystatechange = () => {
                  if (xhr.readyState === 4) {
                    if (xhr.status === 200 || xhr.status === 204) {
                      modal.hide()
                      window.location.assign(options.return_url)
                    } else if (xhr.status === 409) {
                      alert(
                        this.i18n.pgettext(
                          "move_files",
                          "A file with the same name exists in the destination folder.",
                        ),
                      )
                    } else {
                      alert(
                        this.i18n.pgettext("move_files", "File move error."),
                      )
                    }
                  }
                }

                xhr.open("POST", options.move_url, true)
                xhr.send(fd)
              } else {
                alert(
                  this.i18n.pgettext(
                    "move_files",
                    "Please select a destination folder to move the files to.",
                  ),
                )
              }
            })
          }
        }
      }

      xhr.open("GET", options.folder_select_url)
      xhr.send()
    })
  }

  static createModal(html) {
    let tpl = document.createElement("template")
    tpl.innerHTML = html
    let element = tpl.content.firstElementChild
    document.body.appendChild(tpl.content, document.body.firstChild)
    return new bootstrap.Modal(element, { backdrop: "static" })
  }

  static disposeModal(modal) {
    modal.dispose()
    modal._element.parentNode.removeChild(modal._element)
  }
}

export default MoveFiles
