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

/**
 * Behaviours that the markup of the plugin used to carry in inline event handlers, which the
 * Content Security Policy of GLPI blocks. The markup now only holds data attributes; the
 * listeners are delegated on the document, so they also cover the content loaded later
 * (timelines, tabs), and are bound once, with the module.
 *
 *  - data-releases-confirm="<message>": the click is cancelled unless the user confirms the
 *    message (was onclick="return confirm('<message>')");
 *  - data-releases-action="<function>" + data-releases-args='<JSON array>': the click calls
 *    the global function, defined by the template, with these arguments; with
 *    data-releases-element-at="<index>", the element itself is inserted at that index, as the
 *    `this` the inline handler passed (was onclick="<function>(<id>, this, ...)"); only the
 *    functions of ACTIONS can be called;
 *  - data-releases-hover="<id>": the content <id> of a planning item is shown while the item is
 *    hovered (was onmouseover="cleandisplay('<id>')" onmouseout="cleanhide('<id>')");
 *  - data-releases-reload-tab-on-change="<query, %value% for the value>" on a dropdown: its
 *    change reloads the tab (was on_change="reloadTab('...' + this.value)").
 *
 * The core data-glpi-* attributes are not used: they only exist from the GLPI releases
 * shipping the CSP, and a confirmation would be silently skipped on the older ones.
 */

/**
 * Global functions that data-releases-action may call, defined by the templates of the
 * timelines (form_deploytask, form_risk, form_rollback, form_test, timeline).
 */
const ACTIONS = new Set([
    'change_risk_state',
    'change_rollback_state',
    'change_task_state',
    'done_fail_deploytask',
    'done_fail_test',
]);

// Capture phase: the confirmation is asked before any other listener of the element runs,
// as the inline handler, set first, was.
document.addEventListener('click', (event) => {
    const element = event.target.closest('[data-releases-confirm]');
    if (element !== null && !window.confirm(element.dataset.releasesConfirm)) {
        event.preventDefault();
    }
}, true);

document.addEventListener('click', (event) => {
    const action = event.target.closest('[data-releases-action]');
    if (action === null) {
        return;
    }
    const name = action.dataset.releasesAction;
    if (!ACTIONS.has(name) || typeof window[name] !== 'function') {
        return;
    }
    const args = JSON.parse(action.dataset.releasesArgs ?? '[]');
    if (action.dataset.releasesElementAt !== undefined) {
        args.splice(Number(action.dataset.releasesElementAt), 0, action);
    }
    window[name](...args);
});

document.addEventListener('mouseover', (event) => {
    const item = event.target.closest('[data-releases-hover]');
    if (item !== null) {
        window.cleandisplay(item.dataset.releasesHover);
    }
});
document.addEventListener('mouseout', (event) => {
    const item = event.target.closest('[data-releases-hover]');
    if (item !== null) {
        window.cleanhide(item.dataset.releasesHover);
    }
});

// select2 announces a change through jQuery only, which a native listener does not receive
window.$(document).on('change', '[data-releases-reload-tab-on-change]', function () {
    window.reloadTab(this.dataset.releasesReloadTabOnChange.replace('%value%', this.value));
});
