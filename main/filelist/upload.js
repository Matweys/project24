/* Borrows heavily from Dropzone.js https://www.dropzone.dev */

var Upload_CANCELED = "canceled",
  Upload_ERROR = "error",
  Upload_QUEUED = "queued",
  Upload_SUCCESS = "success",
  Upload_UPLOADING = "uploading"

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
    url: null,
  }

  this.options = extend({}, defaults, options)

  this.init()
}

Upload.prototype.cancelUpload = function () {
  for (let i = 0; i < this.files.length; i++) {
    let file = this.files[i]

    if (file.status === Upload_QUEUED || file.status === Upload_UPLOADING) {
      file.status = Upload_CANCELED
    }

    if (file.xhr != null) {
      file.xhr.abort()
    }
  }

  if (typeof this.options.onCancel === "function") {
    setTimeout(this.options.onCancel, 0)
  }

  setTimeout(processQueue.bind(this), 0)
}

Upload.prototype.enqueueFiles = function (files, cb) {
  for (let i = 0; i < files.length; i++) {
    let file = {
      file: files[i],
      size: files[i].size,
      status: Upload_QUEUED,
      upload: {
        uuid: (() => {
          return "xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx".replace(
            /[xy]/g,
            (c) => {
              let r = (Math.random() * 16) | 0,
                v = c === "x" ? r : (r & 0x3) | 0x8
              return v.toString(16)
            },
          )
        })(),
        bytesSent: 0,
        progress: 0,
        bytesSent: 0,
        filename: files[i].name,
      },
    }

    if (typeof cb === "function") {
      cb(file)
    }

    this.files.push(file)
  }

  setTimeout(processQueue.bind(this), 0)
}

Upload.prototype.init = function () {
  this.l10n = Upload.prototype.l10ns[this.options.language]
  this.files = []

  this.cancel_btn =
    typeof this.options.cancelButton === "string"
      ? document.querySelector(this.options.cancelButton)
      : this.options.cancelButton
  this.dropzone =
    typeof this.options.cancelButton === "string"
      ? document.querySelector(this.options.dropzone)
      : this.options.dropzone
  this.upload_btn =
    typeof this.options.cancelButton === "string"
      ? document.querySelector(this.options.uploadButton)
      : this.options.uploadButton

  this.hidden_file_input = document.createElement("input")
  this.hidden_file_input.setAttribute("multiple", "multiple")
  this.hidden_file_input.setAttribute(
    "style",
    "position:absolute;height:0;width:0;visibility:hidden",
  )
  this.hidden_file_input.setAttribute("tabindex", "-1")
  this.hidden_file_input.setAttribute("type", "file")

  this.hidden_input_form = document.createElement("form")
  this.hidden_input_form.setAttribute("enctype", "multipart/form-data")
  this.hidden_input_form.appendChild(this.hidden_file_input)

  document
    .querySelector(this.options.hiddenInputContainer)
    .appendChild(this.hidden_input_form)

  if (this.options.totalProgressContainer) {
    let el =
      this.options.totalProgressContainer === "string"
        ? document.querySelector(this.options.totalProgressContainer)
        : this.options.totalProgressContainer

    if (el) {
      this.totalProgress = document.createElement("div")
      this.totalProgress.style.display = "none"

      if (this.options.totalProgressClass) {
        this.totalProgress.classList.add(this.options.totalProgressClass)
      }

      this.totalProgress.appendChild(document.createElement("div"))
      el.appendChild(this.totalProgress)
    }
  }

  this.hidden_file_input.addEventListener("change", (e) => {
    if (this.hidden_file_input.value !== "") {
      this.enqueueFiles(this.hidden_file_input.files)
      this.hidden_input_form.reset()
    }
  })

  if (this.upload_btn) {
    this.upload_btn.addEventListener("click", (e) => {
      e.preventDefault()
      this.hidden_file_input.click()
    })
  }

  if (this.cancel_btn) {
    this.cancel_btn.addEventListener("click", (e) => {
      e.preventDefault()
      this.cancelUpload()
    })
  }

  if (this.dropzone) {
    this.dropzone.addEventListener("dragend", (e) => {
      this.dropzone.classList.remove(this.options.dropzoneHoverClass)
    })

    this.dropzone.addEventListener("dragenter", (e) => {
      e.preventDefault()
      this.dropzone.classList.add(this.options.dropzoneHoverClass)
      return false
    })

    this.dropzone.addEventListener("dragleave", (e) => {
      this.dropzone.classList.remove(this.options.dropzoneHoverClass)
    })

    this.dropzone.addEventListener("dragover", (e) => {
      e.preventDefault()
      this.dropzone.classList.add(this.options.dropzoneHoverClass)

      if (dragFileCheck(e)) {
        let r
        try {
          r = e.dataTransfer.effectAllowed
        } catch (err) {}
        e.dataTransfer.dropEffect =
          r === "move" || r === "linkMove" ? "move" : "copy"
      }

      return false
    })

    this.dropzone.addEventListener("drop", (e) => {
      e.preventDefault()
      this.dropzone.classList.remove(this.options.dropzoneHoverClass)

      if (dragFileCheck(e)) {
        this.enqueueFiles(e.dataTransfer.files)
      }
    })
  }
}

Upload.prototype.l10ns = {}

function dragFileCheck(e) {
  if (e.dataTransfer.types) {
    for (let i = 0; i < e.dataTransfer.types.length; i++) {
      if (e.dataTransfer.types[i] == "Files") {
        return true
      }
    }
  }

  return false
}

function extend() {
  for (let i = 1; i < arguments.length; i++) {
    for (let key in arguments[i]) {
      if (arguments[i].hasOwnProperty(key)) {
        arguments[0][key] = arguments[i][key]
      }
    }
  }

  return arguments[0]
}

function finishedUploading(file, xhr, e) {
  if (xhr.readyState !== 4) {
    return
  }

  if (file.status !== Upload_CANCELED) {
    file.status =
      xhr.status >= 200 && xhr.status < 300 ? Upload_SUCCESS : Upload_ERROR
  }

  let response

  if (xhr.responseType !== "arraybuffer" && xhr.responseType !== "blob") {
    response = xhr.responseText
    if (
      xhr.getResponseHeader("content-type") &&
      ~xhr.getResponseHeader("content-type").indexOf("application/json")
    ) {
      try {
        response = JSON.parse(response)
      } catch (error) {
        e = error
        response = "Invalid JSON response from server."
      }
    }
  }

  updateUploadProgress.call(this, file, xhr)

  if (xhr.status >= 200 && xhr.status < 300) {
    if (typeof this.options.onComplete === "function") {
      setTimeout(this.options.onComplete.bind(null, file, response), 0)
    }
  } else {
    if (typeof this.options.onError === "function") {
      setTimeout(this.options.onError.bind(null, file, response), 0)
    }
  }

  processQueue.call(this)
}

function handleUploadError(file, xhr, response) {
  if (file.status !== Upload_CANCELED) {
    file.status = Upload_ERROR
  }

  if (typeof this.options.onError === "function") {
    setTimeout(this.options.onError.bind(null, file, response), 0)
  }

  processQueue.call(this)
}

function getActiveFiles() {
  return this.files
    .filter(function (file) {
      return file.status === Upload_QUEUED || file.status === Upload_UPLOADING
    })
    .map(function (file) {
      return file
    })
}

function getFilesWithStatus(status) {
  return this.files
    .filter(function (file) {
      return file.status === status
    })
    .map(function (file) {
      return file
    })
}

function processQueue() {
  var err = !!this.files.filter((file) => {
    return (
      file.status === Upload_CANCELED ||
      (file.status === Upload_ERROR && !file.xhr) ||
      (file.status === Upload_ERROR && file.xhr.status !== 409)
    )
  }).length

  var processingLength = getFilesWithStatus.call(this, Upload_UPLOADING).length,
    queuedFiles = getFilesWithStatus.call(this, Upload_QUEUED)

  if (!getActiveFiles.call(this).length || err) {
    this.files = []
    return
  }

  var i = processingLength

  while (i < this.options.concurency && queuedFiles.length) {
    uploadFile.call(this, queuedFiles.shift())
    i++
  }
}

function updateUploadProgress(file, xhr, e) {
  if (e !== undefined) {
    file.upload.progress = e.total ? Math.round((e.loaded / e.total) * 100) : 0
    file.upload.totalBytes = e.total
    file.upload.bytesSent = e.loaded

    if (typeof this.options.onProgress === "function") {
      this.options.onProgress(
        file.upload.progress,
        file.size,
        file.upload.bytesSent,
      )
    }
  } else {
    file.upload.progress = 100
    file.upload.bytesSent = file.upload.totalBytes
  }

  let bytesSent = 0,
    err = false,
    is_processing = false,
    totalBytes = 0

  for (let i = 0; i < this.files.length; i++) {
    if (
      !err &&
      (this.files[i].status === Upload_CANCELED ||
        (this.files[i].status === Upload_ERROR && !this.files[i].xhr) ||
        (this.files[i].status === Upload_ERROR &&
          this.files[i].xhr.status !== 409))
    ) {
      err = true
    }

    if (
      (!is_processing && this.files[i].status === Upload_QUEUED) ||
      this.files[i].status === Upload_UPLOADING
    ) {
      is_processing = true
    }

    bytesSent += this.files[i].upload.bytesSent || 0
    totalBytes += this.files[i].size || 0
  }

  let totalProgress = totalBytes
    ? Math.round((bytesSent / totalBytes) * 100)
    : 0

  if (this.totalProgress) {
    let els = this.totalProgress.getElementsByTagName("div")

    if (els.length) {
      els[0].style.width = totalProgress + "%"
    }
  }

  // Hide an upload progress an error or a cancel

  if (is_processing && err) {
    is_processing = false
  }

  if (this.cancel_btn) {
    this.cancel_btn.style.display = is_processing ? "" : "none"
  }

  if (this.totalProgress) {
    this.totalProgress.style.display = is_processing ? "" : "none"
  }

  if (!is_processing && this.totalProgress) {
    let els = this.totalProgress.getElementsByTagName("div")

    if (els.length) {
      els[0].style.width = 0
    }
  }

  if (e !== undefined && typeof this.options.onTotalProgress === "function") {
    this.options.onTotalProgress(totalProgress, totalBytes, bytesSent)
  }
}

function uploadFile(file) {
  file.status = Upload_UPLOADING

  let xhr = new XMLHttpRequest()
  file.xhr = xhr

  xhr.open("post", this.options.url, true)

  xhr.timeout = this.options.timeout

  xhr.onerror = () => handleUploadError.call(this, file, xhr)
  xhr.onload = (e) => finishedUploading.call(this, file, xhr, e)
  xhr.ontimeout = () =>
    handleUploadError.call(
      this,
      file,
      xhr,
      `Request timedout after ${this.options.timeout / 1000} seconds`,
    )

  // Some browsers do not have the .upload property
  let progressObj = xhr.upload != null ? xhr.upload : xhr

  progressObj.onprogress = (e) => updateUploadProgress.call(this, file, xhr, e)

  xhr.setRequestHeader("Accept", "application/json")
  xhr.setRequestHeader("Cache-Control", "no-cache")
  xhr.setRequestHeader("X-File-Id", file.upload.uuid)

  if (file.overwrite !== undefined) {
    xhr.setRequestHeader("X-File-Overwrite", file.overwrite ? "?1" : "?0")
  }

  let fd = new FormData()

  if (this.options.params) {
    let additional_params = this.options.params

    if (typeof additional_params === "function") {
      additional_params = additional_params.call(this, file, xhr)
    }

    for (let k in additional_params) {
      let v = additional_params[k]
      if (Array.isArray(v)) {
        for (i = 0; i < v.length; i++) {
          fd.append(k, v[i])
        }
      } else {
        fd.append(k, v)
      }
    }
  }

  fd.append(this.options.field_name, file.file)

  if (xhr.readyState == 1) {
    xhr.send(fd)
  }
}

export default Upload
