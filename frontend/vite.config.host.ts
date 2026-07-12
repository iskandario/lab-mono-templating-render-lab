import { defineConfig, mergeConfig } from 'vite'

import devConfig from './vite.config.dev'

export default mergeConfig(devConfig, defineConfig({
  base: './',
}))
