#!/usr/bin/env node
'use strict'

const fs  = require('fs')
const path = require('path')
const { hashElement } = require('folder-hash')
const { spawn } = require('child_process')

const options = {
    files: {
        include: process.platform === "win32" ? ['**.js', '**.json'] : ['*.css', '*.js'],
    },
    folders: {
        exclude: ['.*', '**.*', 'node_modules'],
    },
}

hashElement('static', options).then(hash => {
    if (typeof hash.hash === 'string' && hash.hash) {
        spawn('php', ['-r', '$f = "../assets.php"; $c = include $f; $c["assets_ver"]["' + path.basename(__dirname) + '"] = "' + hash.hash.substring(0, hash.hash.length - 1) + '"; ksort($c); file_put_contents($f, "<?php\nreturn ".var_export($c, true).";\n");']);
    }
})
