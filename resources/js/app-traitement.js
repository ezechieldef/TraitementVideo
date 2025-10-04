import "./bootstrap";
import { createApp } from "vue";
import HomeProcess from "./components/process/HomeProcess.vue";

const el = document.getElementById("video-processing");
if (el) {
    const app = createApp({});
    app.component("home-process", HomeProcess);
    app.mount("#video-processing");
}
