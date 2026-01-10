import { fileURLToPath, URL } from "node:url";

import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";

export default defineConfig(({ mode }) => ({
  plugins: [vue()],
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", import.meta.url)),
    },
  },
  server: {
    host: "0.0.0.0",
    port: 5173,
    watch: {
      usePolling: true,
    },
  },
  build: {
    // Optimisations de production
    minify: "esbuild",
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ["vue", "vue-router", "pinia"],
          charts: ["chart.js"],
        },
      },
    },
    // Augmenter la limite de taille pour les chunks
    chunkSizeWarningLimit: 1000,
    sourcemap: mode !== "production",
  },
  esbuild: {
    // Supprimer console.* et debugger en production
    drop: mode === "production" ? ["console", "debugger"] : [],
  },
}));
