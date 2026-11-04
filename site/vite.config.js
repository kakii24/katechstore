import { defineConfig } from 'vite';

export default defineConfig({
  server: {
    port: 5173,
    host: '0.0.0.0',
    proxy: {
      '/api/v1': {
        target: 'https://bisque-shrew-404215.hostingersite.com',
        changeOrigin: true,
        secure: true,
        cookieDomainRewrite: 'localhost'
      }
    }
  }
});
