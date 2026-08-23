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
 * Timeline for release
 */
var filter_timeline_release = function () {
    $(document).on("click", '.filter_timeline_release li a', function (event) {
        event.preventDefault();
        var _this = $(this);
        //hide all elements in timeline
        $('.filter_timeline_release li a').removeClass('h_active');
        // $('.filterEle').removeClass('h_active');
        $('.h_item').removeClass('h_hidden');
        $('.h_item').addClass('h_hidden');
        $('.ajax_box').empty();
        //activate clicked element
        _this.toggleClass('h_active');

        //find active classname
        var active_classnames = [];
        $('.filter_timeline_release .h_active').each(function () {
            active_classnames.push(".h_content." + $(this).data('type'));
            // $("a[data-type='"+$(this).data('type')+"'].filterEle").addClass('h_active');
        });
        $(active_classnames.join(', ')).each(function () {
            $(this).parent().removeClass('h_hidden');
        });
    });
};
