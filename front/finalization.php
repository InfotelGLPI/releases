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

 releases is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with releases. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */


use GlpiPlugin\Releases\Finalization;
use GlpiPlugin\Releases\Release;
use GlpiPlugin\Releases\Review;

Session::checkLoginUser();

Html::popHeader(__("Release finalization", 'releases'), $_SERVER['PHP_SELF']);

if (isset($_REQUEST["id"]) && isset($_REQUEST["date_production"])) {
   $release         = new Release();
   $val             = [];
   $val['id']       = $_REQUEST["id"];
   $val['status']   = Release::REVIEW;
   $val['date_end'] = $_SESSION["glpi_currenttime"];
   $release->update($val);
   $release->getFromDB($_REQUEST["id"]);
   $review = new Review();

   if ($review->getFromDBByCrit(["plugin_releases_releases_id" => $_REQUEST["id"]])) {
      $val                           = [];
      $val['id']                     = $review->getID();
      $val['real_date_release']      = $_REQUEST["date_production"];
      $val['name']                   = Review::getTypeName() . " - " . $release->getField("name");
      $val['date_lock']              = 1;
      $val['conforming_realization'] = 1;
      $val['incident']               = 0;
      $val['incident_description']   = "";

      $review->update($val);
   } else {
      $val                                = [];
      $val['plugin_releases_releases_id'] = $_REQUEST["id"];
      $val['real_date_release']           = $_REQUEST["date_production"];
      $val['name']                        = Review::getTypeName() . " - " . $release->getField("name");
      $val['date_lock']                   = 1;
      $val['conforming_realization']      = 1;
      $val['incident']                    = 0;
      $val['incident_description']        = "";

      $review->add($val);
   }

   echo '<div class="alert alert-important alert-success d-flex">';
   echo __("The release has been finalized", "releases") . '</div>';

} else if (isset($_REQUEST["id"])
           && isset($_REQUEST["failedtasks"])
           && isset($_REQUEST["failedtests"])) {
   $review          = new Review();
   $release         = new Release();
   $val             = [];
   $val['id']       = $_REQUEST["id"];
   $val['status']   = Release::FAIL;
   $val['date_end'] = $_SESSION["glpi_currenttime"];
   $release->update($val);
   $release->getFromDB($_REQUEST["id"]);
   if ($review->getFromDBByCrit(["plugin_releases_releases_id" => $_REQUEST["id"]])) {
      $val                           = [];
      $val['id']                     = $review->getID();
      $val['name']                   = Review::getTypeName() . " - " . $release->getField("name");
      $val['conforming_realization'] = 0;
      $val['incident']               = 1;
      $val['incident_description']   = "";
      if ($_REQUEST["failedtasks"] > 0) {
         $val['incident_description'] .= sprintf(__("%s deploy tasks failed", "releases"), $_REQUEST["failedtasks"]) . "<br />";
      }
      if ($_REQUEST["failedtests"] > 0) {
         $val['incident_description'] .= sprintf(__("%s tests failed", "releases"), $_REQUEST["failedtests"]) . "<br />";
      }
      $review->update($val);

   } else {
      $val                                = [];
      $val['plugin_releases_releases_id'] = $_REQUEST["id"];
      $val['name']                        = Review::getTypeName() . " - " . $release->getField("name");
      $val['conforming_realization']      = 0;
      $val['incident']                    = 1;
      $val['incident_description']        = "";
      if ($_REQUEST["failedtasks"] > 0) {
         $val['incident_description'] .= sprintf(__("%s deploy tasks failed", "releases"), $_REQUEST["failedtasks"] . "<br />");
      }
      if ($_REQUEST["failedtests"] > 0) {
         $val['incident_description'] .= sprintf(__("%s tests failed", "releases"), $_REQUEST["failedtests"] . "<br />");
      }
      $review->add($val);
   }
} else if (isset($_REQUEST["release_id"])) {

   Finalization::showFinalizeForm($_REQUEST);

}

Html::popFooter();
