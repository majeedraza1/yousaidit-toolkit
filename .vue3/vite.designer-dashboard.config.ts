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
      entry: 'resources/designer-dashboard.ts',
      name: 'YousaiditToolkit',
      formats: ['iife'],
    },
    rollupOptions: {
      input: 'resources/designer-dashboard.ts',
      output: {
        entryFileNames: 'js/designer-dashboard.js',
        assetFileNames: 'css/designer-dashboard.css',
      },
    },
  },
})