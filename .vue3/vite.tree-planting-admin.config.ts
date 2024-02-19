import {defineConfig} from 'vite'
import vue from '@vitejs/plugin-vue'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [vue()],
  build: {
    emptyOutDir: false,
    outDir: '../assets',
    lib: {
      entry: 'resources/admin-tree-planting/main.ts',
      name: 'YousaiditToolkit',
      formats: ['iife'],
    },
    rollupOptions: {
      output: {
        entryFileNames: 'js/tree-planting-admin.js',
        assetFileNames: 'css/tree-planting-admin.css',
      },
    },
  },
})
