/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import "./bootstrap";
import { createApp } from "vue";
import Swal from "sweetalert2";

/**
 * Next, we will create a fresh Vue application instance. You may then begin
 * registering components with the application instance so they are ready
 * to use in your application's views. An example is included for you.
 */

window.Swal = Swal;

const el = document.getElementById("app");
if (el) {
    const app = createApp({});
    import("./components/ExampleComponent.vue").then(
        ({ default: ExampleComponent }) => {
            app.component("example-component", ExampleComponent);
            app.mount("#app");
        }
    );
}

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// Object.entries(import.meta.glob('./**/*.vue', { eager: true })).forEach(([path, definition]) => {
//     app.component(path.split('/').pop().replace(/\.\w+$/, ''), definition.default);
// });

/**
 * Finally, we will attach the application instance to a HTML element with
 * an "id" attribute of "app". This element is included with the "auth"
 * scaffolding. Otherwise, you will need to add an element yourself.
 */

// Vue mounts only when #app exists

// UI helpers
function setActiveNav() {
    try {
        const path = window.location.pathname.replace(/\/$/, "") || "/";
        document.querySelectorAll(".app-nav-item").forEach((el) => {
            const isHome = el.dataset.route === "home";
            const href = el.getAttribute("href") || "";
            // Consider active if exact route match or if data-route matches 'settings' for settings page
            const active =
                (isHome && (path === "/" || path === "/home")) ||
                (href &&
                    href !== "#" &&
                    (href === path || href.endsWith(path)));

            el.classList.toggle("nav-active", active);
            if (!active) {
                el.classList.remove("nav-active");
            }
        });
    } catch (e) {}
}

function initTheme() {
    const key = "darkMode";
    const prefersDark =
        window.matchMedia &&
        window.matchMedia("(prefers-color-scheme: dark)").matches;
    const saved = localStorage.getItem(key);
    const isDark = saved === "true" || (saved === null && prefersDark);
    document.documentElement.classList.toggle("dark", isDark);
}

function toggleDarkMode() {
    const isDark = document.documentElement.classList.toggle("dark");
    localStorage.setItem("darkMode", isDark);
}

function toggleSidebar() {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebar-overlay");
    if (!sidebar || !overlay) {
        return;
    }
    const isOpen = sidebar.style.transform === "translateX(0px)";
    if (isOpen) {
        sidebar.style.transform = "translateX(-100%)";
        overlay.style.display = "none";
    } else {
        sidebar.style.transform = "translateX(0px)";
        overlay.style.display = "block";
    }
}

// Expose for Blade inline onclick handlers
window.__toggleSidebar = toggleSidebar;
window.__toggleDarkMode = toggleDarkMode;

window.addEventListener("DOMContentLoaded", () => {
    initTheme();
    setActiveNav();
});

window.addEventListener("resize", () => {
    const sidebar = document.getElementById("sidebar");
    const overlay = document.getElementById("sidebar-overlay");
    if (!sidebar || !overlay) {
        return;
    }
    if (window.innerWidth >= 1024) {
        sidebar.style.transform = "translateX(0px)";
        overlay.style.display = "none";
    } else {
        sidebar.style.transform = "translateX(-100%)";
    }
});
