// Dani Group - free address autocomplete for location fields (no Google, no API key).
//
// Any <input data-address-autocomplete="true"> wrapped in a .location-input-wrap
// gets live suggestions from two sources, merged:
//   1. A small built-in list of common South African cities/suburbs, shown
//      instantly with zero network latency.
//   2. The free OpenStreetMap Nominatim search API (nominatim.openstreetmap.org),
//      debounced, restricted to South Africa, no key required.
//
// Nominatim's usage policy (https://operations.osmfoundation.org/policies/nominatim/)
// asks for light, reasonable use with attribution - this script debounces
// requests, limits results, and shows an "OpenStreetMap contributors" credit
// under every result list, as required by the ODbL data licence. For a
// high-traffic production site, consider a self-hosted Nominatim instance or
// a commercial geocoder instead of the public endpoint.
(function () {
    "use strict";

    var DEBOUNCE_MS = 400;
    var MIN_CHARS = 3;
    var MAX_REMOTE_RESULTS = 6;
    var MAX_LOCAL_RESULTS = 4;

    // Common South African metros/suburbs for instant, offline-friendly suggestions.
    var COMMON_AREAS = [
        "Sandton, Johannesburg, Gauteng", "Rosebank, Johannesburg, Gauteng",
        "Randburg, Johannesburg, Gauteng", "Fourways, Johannesburg, Gauteng",
        "Midrand, Gauteng", "Roodepoort, Johannesburg, Gauteng",
        "Soweto, Johannesburg, Gauteng", "Kempton Park, Gauteng",
        "Boksburg, Gauteng", "Germiston, Gauteng", "Alberton, Gauteng",
        "Benoni, Gauteng", "Centurion, Pretoria, Gauteng",
        "Hatfield, Pretoria, Gauteng", "Menlyn, Pretoria, Gauteng",
        "Brooklyn, Pretoria, Gauteng", "Pretoria CBD, Gauteng",
        "Cape Town City Centre, Western Cape", "Sea Point, Cape Town, Western Cape",
        "Claremont, Cape Town, Western Cape", "Bellville, Cape Town, Western Cape",
        "Century City, Cape Town, Western Cape", "Stellenbosch, Western Cape",
        "Durban Central, KwaZulu-Natal", "Umhlanga, Durban, KwaZulu-Natal",
        "Pinetown, KwaZulu-Natal", "Westville, Durban, KwaZulu-Natal",
        "Gqeberha (Port Elizabeth), Eastern Cape", "East London, Eastern Cape",
        "Bloemfontein, Free State", "Polokwane, Limpopo",
        "Mbombela (Nelspruit), Mpumalanga", "Kimberley, Northern Cape",
        "Rustenburg, North West", "Potchefstroom, North West",
        "Vanderbijlpark, Gauteng", "Vereeniging, Gauteng",
        "eMalahleni (Witbank), Mpumalanga", "George, Western Cape"
    ];

    function debounce(fn, delay) {
        var timer = null;
        return function () {
            var args = arguments;
            var context = this;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(context, args);
            }, delay);
        };
    }

    function localMatches(query) {
        var q = query.toLowerCase();
        return COMMON_AREAS
            .filter(function (a) { return a.toLowerCase().indexOf(q) !== -1; })
            .slice(0, MAX_LOCAL_RESULTS)
            .map(function (label) { return { label: label, source: "local" }; });
    }

    function fetchRemoteMatches(query, callback) {
        var url = "https://nominatim.openstreetmap.org/search"
            + "?format=json&addressdetails=0&countrycodes=za"
            + "&accept-language=en-ZA&limit=" + MAX_REMOTE_RESULTS
            + "&q=" + encodeURIComponent(query);

        fetch(url, { headers: { "Accept": "application/json" } })
            .then(function (res) { return res.ok ? res.json() : []; })
            .then(function (data) {
                var results = (data || []).map(function (item) {
                    return { label: item.display_name, source: "osm" };
                });
                callback(results);
            })
            .catch(function () { callback([]); });
    }

    function dedupe(items) {
        var seen = {};
        var out = [];
        items.forEach(function (item) {
            var key = item.label.toLowerCase();
            if (!seen[key]) {
                seen[key] = true;
                out.push(item);
            }
        });
        return out;
    }

    function setupField(input) {
        if (input.dataset.addressAutocompleteReady === "true") {
            return;
        }
        input.dataset.addressAutocompleteReady = "true";
        input.setAttribute("autocomplete", "off");

        var wrap = input.closest(".location-input-wrap") || input.parentElement;
        wrap.style.position = wrap.style.position || "relative";

        var list = document.createElement("ul");
        list.className = "addr-suggestions";
        list.setAttribute("role", "listbox");
        list.style.display = "none";
        wrap.appendChild(list);

        var activeIndex = -1;
        var currentItems = [];

        function closeList() {
            list.style.display = "none";
            list.innerHTML = "";
            currentItems = [];
            activeIndex = -1;
        }

        function selectItem(item) {
            input.value = item.label;
            closeList();
            input.dispatchEvent(new Event("input", { bubbles: true }));
        }

        function renderList(items) {
            currentItems = items;
            activeIndex = -1;
            list.innerHTML = "";

            if (!items.length) {
                closeList();
                return;
            }

            items.forEach(function (item, idx) {
                var li = document.createElement("li");
                li.className = "addr-suggestion-item";
                li.setAttribute("role", "option");
                li.textContent = item.label;
                li.addEventListener("mousedown", function (e) {
                    e.preventDefault();
                    selectItem(item);
                });
                li.addEventListener("mouseenter", function () {
                    setActive(idx);
                });
                list.appendChild(li);
            });

            var credit = document.createElement("li");
            credit.className = "addr-suggestion-credit";
            credit.textContent = "Search by OpenStreetMap contributors";
            list.appendChild(credit);

            list.style.display = "block";
        }

        function setActive(idx) {
            var options = list.querySelectorAll(".addr-suggestion-item");
            options.forEach(function (el) { el.classList.remove("active"); });
            if (idx >= 0 && idx < options.length) {
                options[idx].classList.add("active");
                activeIndex = idx;
            } else {
                activeIndex = -1;
            }
        }

        var runRemoteSearch = debounce(function (query) {
            fetchRemoteMatches(query, function (remoteItems) {
                if (input.value.trim() !== query) {
                    return; // stale response, input changed since request fired
                }
                var merged = dedupe(localMatches(query).concat(remoteItems));
                renderList(merged.slice(0, MAX_LOCAL_RESULTS + MAX_REMOTE_RESULTS));
            });
        }, DEBOUNCE_MS);

        input.addEventListener("input", function () {
            var query = input.value.trim();

            if (query.length < MIN_CHARS) {
                closeList();
                return;
            }

            // Show instant local matches immediately, then refine with live results.
            renderList(localMatches(query));
            runRemoteSearch(query);
        });

        input.addEventListener("keydown", function (e) {
            var options = list.querySelectorAll(".addr-suggestion-item");
            if (!options.length || list.style.display === "none") {
                return;
            }

            if (e.key === "ArrowDown") {
                e.preventDefault();
                setActive((activeIndex + 1) % options.length);
            } else if (e.key === "ArrowUp") {
                e.preventDefault();
                setActive((activeIndex - 1 + options.length) % options.length);
            } else if (e.key === "Enter") {
                if (activeIndex >= 0 && currentItems[activeIndex]) {
                    e.preventDefault();
                    selectItem(currentItems[activeIndex]);
                }
            } else if (e.key === "Escape") {
                closeList();
            }
        });

        input.addEventListener("blur", function () {
            // Delay so a mousedown-selection on the list can register first.
            setTimeout(closeList, 120);
        });

        document.addEventListener("click", function (e) {
            if (!wrap.contains(e.target)) {
                closeList();
            }
        });
    }

    function init() {
        document.querySelectorAll('[data-address-autocomplete="true"]').forEach(setupField);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();
