import commonjs from "@rollup/plugin-commonjs"
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
  external: ["react", "react-dom"],
  input: path.resolve(__dirname, "index.js"),
  output: [
    {
      file: path.resolve(__dirname, "../static/move_files.js"),
      format: "iife",
      generatedCode: "es2015",
      globals: {
        react: "React",
        "react-dom": "ReactDOM",
      },
      name: "MoveFiles",
    },
  ],
  plugins: [
    peerDepsExternal(),
    babel({
      babelHelpers: "bundled",
      exclude: "node_modules/**",
      presets: ["@babel/preset-react"],
    }),
    commonjs(),
    json(),
    replace({
      "process.env.NODE_ENV": '"production"',
      preventAssignment: true,
    }),
    nodeResolve({
      browser: true,
      dedupe: ["react", "react-dom"],
    }),
  ],
}
