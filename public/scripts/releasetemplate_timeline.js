/**
 * -------------------------------------------------------------------------
 * releases plugin for GLPI
 * Copyright (C) 2020-2026 by the releases Development Team.
 *
 * https://github.com/InfotelGLPI/releases
 * -------------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of releases.
 *
 * releases is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * releases is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with releases. If not, see <http://www.gnu.org/licenses/>.
 * --------------------------------------------------------------------------
 */

/* global $ */

/**
 * ReleaseTemplate timeline add/edit behaviours (externalized from
 * showTimelineForm / showTimeLine). Uses jQuery .load() so the embedded
 * richtext/select2 init scripts of the loaded subitem form execute; the
 * global jQuery ajaxSetup adds the GLPI CSRF token to the POST.
 */
(function () {
    "use strict";

    // Build the subitem AJAX params from the trigger's data-* attributes.
    function subitemParams(el, id) {
        var params = {
            action: "viewsubitem",
            type: el.getAttribute("data-itemtype"),
            parenttype: el.getAttribute("data-parenttype"),
            id: id
        };
        params[el.getAttribute("data-fkey")] = el.getAttribute("data-parentid");
        return params;
    }

    document.addEventListener("click", function (e) {
        // Add a new subitem into the ajax box.
        var add = e.target.closest("[data-releases-add]");
        if (add) {
            e.preventDefault();
            if (typeof $ === "undefined") {
                return;
            }
            var target = document.querySelector(add.getAttribute("data-target"));
            if (target) {
                $(target).load(add.getAttribute("data-url"), subitemParams(add, -1));
            }
            return;
        }

        // Edit an existing subitem inline.
        var edit = e.target.closest("[data-releases-edit]");
        if (edit) {
            e.preventDefault();
            if (typeof $ === "undefined") {
                return;
            }
            // data-uid embeds the namespaced itemtype, so it contains
            // backslashes; escape them or the attribute selector silently
            // matches nothing (\R / \D are read as CSS escapes).
            var uid = edit.getAttribute("data-uid");
            var wrap = $('[data-uid="' + $.escapeSelector(uid) + '"]');
            wrap.addClass("edited");
            wrap.find(".displayed_content").hide();
            wrap.find(".cancel_edit_item_content").show()
                .off("click.releases")
                .on("click.releases", function () {
                    $(this).hide();
                    wrap.removeClass("edited");
                    wrap.find(".edit_item_content").empty().hide();
                    wrap.find(".displayed_content").show();
                });
            wrap.find(".edit_item_content").show()
                .load(edit.getAttribute("data-url"), subitemParams(edit, edit.getAttribute("data-items-id")));
        }
    });
}());
