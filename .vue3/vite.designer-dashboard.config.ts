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
      entry: 'resources/frontend-designer-dashboard/main.ts',
      name: 'YousaiditToolkit',
      formats: ['iife'],
    },
    rollupOptions: {
      input: 'resources/frontend-designer-dashboard/main.ts',
      output: {
        entryFileNames: 'js/designer-dashboard-vue3.js',
        assetFileNames: 'css/designer-dashboard-vue3.css',
      },
    },
  },
})
