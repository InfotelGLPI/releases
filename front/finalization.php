<?php

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

// Mutating branches only accept POST: the finalize/fail forms are method=post and
// carry the _glpi_csrf_token validated by the core CheckCsrfListener. Reading $_POST
// (not $_REQUEST) prevents these state changes from being triggered by a forged GET.
// Dispatch on the submit button name (finalize/failed): both forms always emit the
// date_production field, so keying on field presence would route a "failed" submit
// into the finalize branch and never mark the release as failed.
if (isset($_POST["finalize"]) && isset($_POST["id"]) && isset($_POST["date_production"])) {
   $release = new Release();
   $release->check((int)$_POST["id"], UPDATE);
   $val             = [];
   $val['id']       = (int)$_POST["id"];
   $val['status']   = Release::REVIEW;
   $val['date_end'] = $_SESSION["glpi_currenttime"];
   $release->update($val);
   $release->getFromDB((int)$_POST["id"]);
   $review = new Review();

   if ($review->getFromDBByCrit(["plugin_releases_releases_id" => $_POST["id"]])) {
      $val                           = [];
      $val['id']                     = $review->getID();
      $val['real_date_release']      = $_POST["date_production"];
      $val['name']                   = Review::getTypeName() . " - " . $release->getField("name");
      $val['date_lock']              = 1;
      $val['conforming_realization'] = 1;
      $val['incident']               = 0;
      $val['incident_description']   = "";

      $review->update($val);
   } else {
      $val                                = [];
      $val['plugin_releases_releases_id'] = (int)$_POST["id"];
      $val['real_date_release']           = $_POST["date_production"];
      $val['name']                        = Review::getTypeName() . " - " . $release->getField("name");
      $val['date_lock']                   = 1;
      $val['conforming_realization']      = 1;
      $val['incident']                    = 0;
      $val['incident_description']        = "";

      $review->add($val);
   }

   echo '<div class="alert alert-important alert-success d-flex">';
   echo __("The release has been finalized", "releases") . '</div>';

} else if (isset($_POST["failed"])
           && isset($_POST["id"])
           && isset($_POST["failedtasks"])
           && isset($_POST["failedtests"])) {
   $review          = new Review();
   $release         = new Release();
   $release->check((int)$_POST["id"], UPDATE);
   $val             = [];
   $val['id']       = (int)$_POST["id"];
   $val['status']   = Release::FAIL;
   $val['date_end'] = $_SESSION["glpi_currenttime"];
   $release->update($val);
   $release->getFromDB((int)$_POST["id"]);
   if ($review->getFromDBByCrit(["plugin_releases_releases_id" => $_POST["id"]])) {
      $val                           = [];
      $val['id']                     = $review->getID();
      $val['name']                   = Review::getTypeName() . " - " . $release->getField("name");
      $val['conforming_realization'] = 0;
      $val['incident']               = 1;
      $val['incident_description']   = "";
      if ((int)$_POST["failedtasks"] > 0) {
         $val['incident_description'] .= sprintf(__("%s deploy tasks failed", "releases"), (int)$_POST["failedtasks"]) . "<br />";
      }
      if ((int)$_POST["failedtests"] > 0) {
         $val['incident_description'] .= sprintf(__("%s tests failed", "releases"), (int)$_POST["failedtests"]) . "<br />";
      }
      $review->update($val);

   } else {
      $val                                = [];
      $val['plugin_releases_releases_id'] = (int)$_POST["id"];
      $val['name']                        = Review::getTypeName() . " - " . $release->getField("name");
      $val['conforming_realization']      = 0;
      $val['incident']                    = 1;
      $val['incident_description']        = "";
      if ((int)$_POST["failedtasks"] > 0) {
         $val['incident_description'] .= sprintf(__("%s deploy tasks failed", "releases"), (int)$_POST["failedtasks"]) . "<br />";
      }
      if ((int)$_POST["failedtests"] > 0) {
         $val['incident_description'] .= sprintf(__("%s tests failed", "releases"), (int)$_POST["failedtests"]) . "<br />";
      }
      $review->add($val);
   }
} else if (isset($_GET["release_id"])) {

   // Read-only display of the release progress (opened in an iframe modal via GET);
   // enforce right + entity + item access before disclosing anything.
   $release = new Release();
   $release->check((int)$_GET["release_id"], UPDATE);
   Finalization::showFinalizeForm($_GET);

}

Html::popFooter();
