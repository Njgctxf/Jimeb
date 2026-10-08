import { defineConfig } from "vite";
import laravel from "laravel-vite-plugin";

export default defineConfig({
    plugins: [
        laravel({
            input: [
                "resources/css/app.css",
                "resources/js/app.js",
                "resources/js/pos-offline.js", // Blade POS offline queue
            ],
            refresh: true,
        }),
    ],
    build: {
        chunkSizeWarningLimit: 1000,
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (id.includes("node_modules")) {
                        if (id.includes("apexcharts")) {
                            return "apexcharts";
                        }
                        if (id.includes("flatpickr") || id.includes("pikaday")) {
                            return "datepicker";
                        }
                        if (id.includes("sweetalert2")) {
                            return "sweetalert2";
                        }
                        if (id.includes("@fortawesome")) {
                            return "fontawesome";
                        }
                        if (id.includes("flowbite") || id.includes("preline")) {
                            return "ui";
                        }
                        // Other node_modules in a shared vendor chunk
                        return "vendor";
                    }
                },
            },
        },
    },
});
