import {defineConfig} from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue({
    template: {
      compilerOptions: {
        isCustomElement: (tag) => ['dynamic-card-canvas'].includes(tag),
      }
    }
  })],
  build: {
    emptyOutDir: false,
    outDir: '../assets',
    lib: {
      entry: 'resources/frontend.ts',
      name: 'YousaiditToolkit',
      formats: ['iife'],
    },
    rollupOptions: {
      external: ['jquery'],
      output: {
        globals: {
          jquery: 'jQuery'
        },
        entryFileNames: 'js/frontend.js',
        assetFileNames: 'css/frontend.css',
      },
    },
  },
})