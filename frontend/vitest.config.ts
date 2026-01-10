import { fileURLToPath } from "node:url";
import { defineConfig, configDefaults } from "vitest/config";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
  plugins: [vue()],
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", import.meta.url)),
    },
  },
  test: {
    environment: "jsdom",
    exclude: [...configDefaults.exclude, "e2e/**"],
    root: fileURLToPath(new URL("./", import.meta.url)),
    coverage: {
      provider: "v8",
      reporter: ["text", "json", "html"],
      exclude: [
        "node_modules/",
        "src/**/*.d.ts",
        "src/main.ts",
        "src/**/*.spec.ts",
        "src/**/*.test.ts",
      ],
    },
    globals: true,
  },
});
