import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
  plugins: [react()],
  root: 'assets/react',
  base: '/build/',
  build: {
    outDir: '../../public/build',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        dashboard: 'assets/react/main.jsx',
      },
    },
  },
  server: {
    origin: 'http://localhost:5173',
  },
});
