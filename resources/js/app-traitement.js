import "./bootstrap";
import { createApp } from "vue";
import { ZiggyVue } from "ziggy-js";
import HomeProcess from "./components/process/HomeProcess.vue";

const el = document.getElementById("video-processing");
if (el) {
    const app = createApp({});
    app.use(ZiggyVue);
    // Initialize Sanctum CSRF cookie early for API calls on this standalone page
    if (window.axios) {
        window.axios.get("/sanctum/csrf-cookie").catch(() => {});
    }
    app.component("home-process", HomeProcess);
    app.mount("#video-processing");
}
