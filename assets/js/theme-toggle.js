// Dani Group - light/dark theme toggle.
// The initial theme is applied synchronously in _Layout's <head> (before
// paint) to avoid a flash of the wrong theme. This file just wires up the
// toggle button and persists the choice.
(function () {
    "use strict";

    function applyTheme(theme) {
        if (theme === "light") {
            document.documentElement.setAttribute("data-theme", "light");
        } else {
            document.documentElement.removeAttribute("data-theme");
        }
    }

    function currentTheme() {
        return document.documentElement.getAttribute("data-theme") === "light" ? "light" : "dark";
    }

    function init() {
        var btn = document.getElementById("themeToggleBtn");
        if (!btn) {
            return;
        }

        btn.addEventListener("click", function () {
            var next = currentTheme() === "light" ? "dark" : "light";
            applyTheme(next);
            try {
                localStorage.setItem("daniTheme", next);
            } catch (e) {
                // localStorage unavailable (private browsing, etc.) - theme just won't persist.
            }
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
