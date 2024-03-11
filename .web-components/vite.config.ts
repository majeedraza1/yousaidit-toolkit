import {defineConfig} from 'vite'

// https://vitejs.dev/config/
export default defineConfig({
  build: {
    emptyOutDir: false,
    outDir: '../assets',
    lib: {
      entry: 'resources/main.ts',
      name: 'YousaiditToolkit',
      formats: ['iife'],
    },
    rollupOptions: {
      output: {
        entryFileNames: 'js/web-components.js',
        assetFileNames: 'css/web-components.css',
      },
    },
  },
})
