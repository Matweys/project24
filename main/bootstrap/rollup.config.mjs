import path from "node:path"
import process from "node:process"
import replace from "@rollup/plugin-replace"
import { babel } from "@rollup/plugin-babel"
import { fileURLToPath } from "node:url"
import { nodeResolve } from "@rollup/plugin-node-resolve"

const __dirname = path.dirname(fileURLToPath(import.meta.url))

export default {
  external: ["@popperjs/core"],
  generatedCode: "es2015",
  input: path.resolve(__dirname, "index.esm.js"),
  output: [
    {
      file: path.resolve(__dirname, "../static/bootstrap.tmp.js"),
      format: "iife",
      generatedCode: "es2015",
      globals: {
        "@popperjs/core": "Popper",
      },
      name: "bootstrap",
    },
  ],
  plugins: [
    babel({
      babelHelpers: "bundled",
      exclude: "node_modules/**",
    }),
    replace({
      "process.env.NODE_ENV": '"production"',
      preventAssignment: true,
    }),
    nodeResolve(),
  ],
}
