import json from "@rollup/plugin-json"
import path from "node:path"
import peerDepsExternal from "rollup-plugin-peer-deps-external"
import process from "node:process"
import replace from "@rollup/plugin-replace"
import { babel } from "@rollup/plugin-babel"
import { fileURLToPath } from "node:url"
import { nodeResolve } from "@rollup/plugin-node-resolve"

const __dirname = path.dirname(fileURLToPath(import.meta.url))

export default {
  input: path.resolve(__dirname, "main.js"),
  output: [
    {
      //dir: path.resolve(__dirname, "static/"),
      //manualChunks: id => path.parse(id).name,
      //preserveModules: true,
      file: path.resolve(__dirname, "../static/filelist.js"),
      format: "iife",
      generatedCode: "es2015",
      globals: [],
      name: "Filelist",
    },
  ],
  external: [],
  plugins: [
    peerDepsExternal(),
    babel({
      exclude: "node_modules/**",
      babelHelpers: "bundled",
    }),
    replace({
      "process.env.NODE_ENV": '"production"',
      preventAssignment: true,
    }),
    json(),
    nodeResolve(),
  ],
}
