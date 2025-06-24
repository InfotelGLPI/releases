<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018-2022 by the Releases Development Team.

 https://github.com/InfotelGLPI/releases
 -------------------------------------------------------------------------

 LICENSE

 This file is part of Releases.

 Releases is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 Releases is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with Releases. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */


use Glpi\Exception\Http\AccessDeniedHttpException;

Session::checkLoginUser();
Html::header(PluginReleasesRelease::getTypeName(2), '', "helpdesk", PluginReleasesRelease::getType());

$release = new PluginReleasesRelease();

if ($release->canView()) {
   //   Html::compileScss(["file"=>"../css/style.scss"]);
   //     echo Html::Scss("../css/style.scss");
   Search::show(PluginReleasesRelease::getType());

} else {
    throw new AccessDeniedHttpException();
}

Html::footer();
