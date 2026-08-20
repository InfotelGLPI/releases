/*
 -------------------------------------------------------------------------
 releases plugin for GLPI
 Copyright (C) 2020-2026 by the releases Development Team.

 https://github.com/InfotelGLPI/releases
 -------------------------------------------------------------------------

 LICENSE

 This file is part of releases.

 releases is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 3 of the License, or
 (at your option) any later version.
 --------------------------------------------------------------------------
 */

/* global getAjaxCsrfToken */

/**
 * ReleaseTemplate main form behaviours (externalized from showForm).
 * All handlers are delegated at document level because GLPI dropdowns are
 * select2-enhanced: the native <select> "change" event still bubbles.
 */
(function () {
    "use strict";

    function csrfHeaders() {
        return {
            "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8",
            "X-Glpi-Csrf-Token": (typeof getAjaxCsrfToken === "function") ? getAjaxCsrfToken() : ""
        };
    }

    // Build the POST body expected by ajax/changeTarget.php.
    function targetsBody(type, currentType, values) {
        var params = new URLSearchParams();
        params.set("type", type || "");
        params.set("current_type", currentType || "");
        (values || []).forEach(function (v) {
            params.append("values[]", v);
        });
        return params.toString();
    }

    // Reload the target list for the given communication type.
    function reloadTargets(container, type) {
        if (!container) {
            return;
        }
        var url = container.getAttribute("data-url");
        var currentType = container.getAttribute("data-current-type") || "";
        var values = [];
        try {
            values = JSON.parse(container.getAttribute("data-values") || "[]") || [];
        } catch (e) {
            values = [];
        }
        if (!Array.isArray(values)) {
            values = [];
        }
        fetch(url, {
            method: "POST",
            headers: csrfHeaders(),
            body: targetsBody(type, currentType, values)
        }).then(function (r) {
            return r.text();
        }).then(function (html) {
            container.innerHTML = html;
        }).catch(function () { /* keep the container empty on failure */ });
    }

    document.addEventListener("change", function (e) {
        var select = e.target;
        if (!select || select.nodeName !== "SELECT") {
            return;
        }

        // Toggle the "service shutdown details" row without a server round-trip.
        if (select.name === "service_shutdown") {
            var row = document.getElementById("shutdowndetails-row");
            if (row) {
                row.style.display = (String(select.value) === "0") ? "none" : "";
            }
            return;
        }

        // Rebuild the target selector when the communication type changes.
        if (select.name === "communication_type") {
            reloadTargets(document.querySelector("[data-releases-targets]"), select.value);
        }
    });

    // Preload existing targets (edit mode). The script is emitted in the footer,
    // so the container already exists; still guard for a not-yet-parsed DOM.
    function autoloadTargets() {
        var container = document.querySelector("[data-releases-targets][data-autoload]");
        if (container) {
            reloadTargets(container, container.getAttribute("data-current-type") || "");
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", autoloadTargets);
    } else {
        autoloadTargets();
    }
}());
