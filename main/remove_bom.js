#!/usr/bin/env node
'use strict'

const fs = require('fs')

function main(args) {

    if (!args[0]) {
        console.error('Usage: remove_bom filename')
        process.exit(1)
    }

    fs.readFile(args[0], 'utf8', function (err, data) {
        if (err) {
            return console.log(err);
        }

        if (data[0] === '\uFEFF' || data[0] === '\uFFFE') {
            fs.writeFile(args[0], data.slice(1), 'utf8', function (err) {
                if (err) {
                    return console.log(err)
                }
            })
        }
    })
}

main(process.argv.slice(2))
