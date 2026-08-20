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

/**
 * "Create a release" link builder (externalized from
 * Release::showCreateRelease and ReleaseTemplate::displayMenu).
 *
 * When the release template dropdown changes, rebuild the target anchor's
 * href = base URL + selected template id. Configuration travels with the DOM
 * through a wrapping [data-releases-linkgroup] element, so the same handler
 * serves both the "from change" and the "menu" contexts. The handler is
 * delegated at document level because the dropdown is select2-enhanced: the
 * native <select> "change" event still bubbles.
 */
(function () {
    "use strict";

    document.addEventListener("change", function (e) {
        var select = e.target;
        if (!select || select.nodeName !== "SELECT" || select.name !== "releasetemplates_id") {
            return;
        }
        var group = select.closest("[data-releases-linkgroup]");
        if (!group) {
            return;
        }
        var base = group.getAttribute("data-url") || "";
        var anchor = document.querySelector(group.getAttribute("data-target"));
        if (anchor) {
            anchor.setAttribute("href", base + encodeURIComponent(select.value));
        }
    });
}());
