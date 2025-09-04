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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access directly to this file");
}


/**
 * Class PluginReleasesRelease
 */
class PluginReleasesFinalization extends CommonDBTM {

   public $dohistory = true;
   static $rightname = 'plugin_releases_releases';
   const TODO = 1; // todo
   const DONE = 2; // done
   const FAIL = 3; // Failed


    static function getIcon()
    {
        return "ti ti-check";
    }
   /**
    * @param int $nb
    *
    * @return translated
    */
   static function getTypeName($nb = 0) {

      return __('Finalization', 'releases');
   }

   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case PluginReleasesRelease::getType() :
            $self = new self();
            $self->showForm($item->getID());
            break;
      }
      return true;
   }

   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (static::canView()) {
         switch ($item->getType()) {
            case PluginReleasesRelease::getType() :
               return self::createTabEntry(self::getTypeName(2));
         }
      }
      return '';
   }

   /**
    * @param $state
    *
    * @return string
    */
   public static function getStateItem($state) {
      switch ($state) {
         case self::TODO :
            return "<span><i class=\"fas fa-3x fa-hourglass-half\"></i></span>";
            break;
         case self::DONE :
            return "<span><i class=\"fas fa-3x fa-check\"></i></span>";
            break;
         case self::FAIL :
            return "<span><i class=\"fas fa-3x fa-times\"></i></span>";
            break;
      }
   }

   function showForm($ID, $options = []) {


       global $CFG_GLPI;
       $release = new PluginReleasesRelease();
      $release->getFromDB($ID);

      echo "<table class='tab_cadre_fixe' id='mainformtable'>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
      if (PluginReleasesRisk::countForItem($release) == PluginReleasesRisk::countDoneForItem($release)) {
         $risk_state = PluginReleasesRisk::DONE;
      } else {
         $risk_state = PluginReleasesRisk::TODO;
      }

      if (PluginReleasesRollback::countForItem($release) == PluginReleasesRollback::countDoneForItem($release)) {
         $rollback_state = PluginReleasesRollback::DONE;
      } else {
         $rollback_state = PluginReleasesRollback::TODO;
      }

      $deployTaskDone  = PluginReleasesRelease::countForItem($ID, PluginReleasesDeploytask::class, PluginReleasesDeploytask::DONE);
      $deployTaskTotal = PluginReleasesRelease::countForItem($ID, PluginReleasesDeploytask::class);
      $deployTaskFail  = PluginReleasesDeploytask::countFailForItem($release);
      $taskfailed      = "";
      $task_state      = PluginReleasesDeploytask::TODO;
      if ($deployTaskFail != 0) {
         $taskfailed = "bulleFailed";
         $task_state = PluginReleasesDeploytask::FAIL;
      }
      if ($deployTaskTotal != 0) {
         $pourcentageTask = $deployTaskDone / $deployTaskTotal * 100;
      }
      if ($deployTaskDone == $deployTaskTotal) {
         $pourcentageTask = 100;
         $task_state      = PluginReleasesDeploytask::DONE;
      }

      $test_state = PluginReleasesTest::TODO;
      $testDone   = PluginReleasesRelease::countForItem($ID, PluginReleasesTest::class, PluginReleasesTest::DONE);
      $testTotal  = PluginReleasesRelease::countForItem($ID, PluginReleasesTest::class);
      $testFail   = PluginReleasesTest::countFailForItem($release);
      $testfailed = "";
      if ($testFail != 0) {
         $testfailed = "bulleFailed";
         $test_state = PluginReleasesTest::FAIL;
      }
      if ($testTotal != 0) {
         $pourcentageTest = $testDone / $testTotal * 100;
      }
      if ($testDone == $testTotal) {
         $pourcentageTest = 100;
         $test_state      = PluginReleasesTest::DONE;
      }

      $riskDone      = PluginReleasesRelease::countForItem($ID, PluginReleasesRisk::class, PluginReleasesRisk::DONE);
      $riskTotal     = PluginReleasesRelease::countForItem($ID, PluginReleasesRisk::class);
      $rollbackDone  = PluginReleasesRelease::countForItem($ID, PluginReleasesRollback::class, PluginReleasesRollback::DONE);
      $rollbackTotal = PluginReleasesRelease::countForItem($ID, PluginReleasesRollback::class);

      echo "<section id=\"timeline\">
        <article>
          <div class=\"inner\" >
                  <span class=\"bulle bulleMarge\">
                    <span style=\"margin-left: 5px;\"><i class=\"fas fa-3x fa-play\"></i></span>
                  </span>
                  <h2 class='dateColor'>" . __("Creation date") . "<i class='fas fa-calendar' style=\"float: right;\"></i></h2>
                  <p>" . Html::convDateTime($release->fields["date_creation"]) . "</p>
             </div>
        </article>
        <article>
          <div class=\"inner\" >
            <span class=\"bulle riskBulle bulleMarge\">
              " . self::getStateItem($risk_state) . "
            </span>
            <h2 class='Finalization-Risk'>" . _n('Risk', 'Risk', 2, 'releases') . "<i class='fas fa-bug' style=\"float: right;\"></i></h2>
            <p>" . sprintf(__('%s / %s risks', 'releases'), $riskDone, $riskTotal) . "</p>
          </div>
        </article>
        <article>
          <div class=\"inner\">
            <span class=\"bulle rollbackBulle bulleMarge\">
              " . self::getStateItem($rollback_state) . "
            </span>
            <h2 class='Finalization-Rollback'>" . _n('Rollback', 'Rollbacks', 2, 'releases') . "<i class='fas fa-undo-alt' style=\"float: right;\"></i></h2>
            <p>" . sprintf(__('%s / %s rollbacks', 'releases'), $rollbackDone, $rollbackTotal) . "</p>
          </div>
        </article>
        <article>
          <div class=\"inner\">
            <span class=\"bulle taskBulle $taskfailed bulleMarge\">
            " . self::getStateItem($task_state) . "
            </span>
            <h2 class='Finalization-Deploytask'>" . _n('Deploy task', 'Deploy tasks', 2, 'releases') . "<i class='fas fa-check-square' style=\"float: right;\"></i></h2>
            <p>" . sprintf(__('%s / %s deploy tasks', 'releases'), $deployTaskDone, $deployTaskTotal) . "</br>
            " . sprintf(__('%s deploy tasks failed', 'releases'), $deployTaskFail) . "<p><span class='percent' style=\"float: right;\">
                  " . Html::formatNumber($pourcentageTask) . " %
              </span></p></p>
          </div>
        </article>
        <article>
          <div class=\"inner\">
          <span class=\"bulle testBulle $testfailed bulleMarge\">
            " . self::getStateItem($test_state) . "
            </span>
            <h2 class='Finalization-Test'>" . _n('Test', 'Tests', 2, 'releases') . "<i class='fas fa-check' style=\"float: right;\"></i></h2>
            <p>" . sprintf(__('%s / %s tests', 'releases'), $testDone, $testTotal) . "</br>
            " . sprintf(__('%s  tests failed', 'releases'), $testFail) . "<p><span class='percent' style=\"float: right;\">
                  " . Html::formatNumber($pourcentageTest) . " %
              </span></p></p>
          </div>
        </article>
        ";

      $dateEnd = (!empty($release->fields["date_end"])) ? Html::convDateTime($release->fields["date_end"]) : __("Not yet completed", 'releases');

      echo "<article>
         <div class=\"inner\" >
            <span class=\"bulle bulleMarge\">
              <span><i class=\"fas fa-3x fa-stop\"></i></span>
            </span>
            <h2 class='dateColor'>" . __("End date") . "<i class='fas fa-calendar' style=\"float: right;\"></i></h2>
            <p>" . $dateEnd . "<br><br>";

      $link = '';
      $msg  = '';
      if ((empty($release->fields["date_end"])
           || $release->fields["status"] < PluginReleasesRelease::REVIEW)
          && $this->canUpdate()) {
         if ($deployTaskFail == 0 && $testFail == 0) {

            $link = '<a href="#" id="finalize" class="submit btn btn-primary" data-bs-toggle="modal" data-bs-target="#alert-message"> ' . __("Finalize", 'releases') . '</a>';

            echo Ajax::createIframeModalWindow('alert-message',
                $CFG_GLPI['root_doc'] . "/plugins/releases/front/finalization.php?release_id=" . $release->fields['id'] . "&confirm=1",
                                               ['title'   => __("Finalize", 'releases'),
                                                'display' => false]);
         } else {
            $link = '<a href="#" id="finalize" class="submit btn btn-danger" data-bs-toggle="modal" data-bs-target="#alert-message"> ' . __("Mark as failed", 'releases') . '</a>';

            echo Ajax::createIframeModalWindow('alert-message',
                $CFG_GLPI['root_doc'] . "/plugins/releases/front/finalization.php?release_id=" . $release->fields['id'] . "&failed=1",
                                               ['title'   => __("Mark as failed", 'releases'),
                                                'display' => false]);
         }
      }

      echo $link . "</p>
       </div>
     </article>";
      echo $msg;

      echo "</section>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";
   }

   static function showFinalizeForm($params) {

       global $CFG_GLPI;
       $release = new PluginReleasesRelease();
      $ID      = $params["release_id"];
      $release->getFromDB($ID);
      $deployTaskDone  = PluginReleasesRelease::countForItem($ID, PluginReleasesDeploytask::class, PluginReleasesDeploytask::DONE);
      $deployTaskTotal = PluginReleasesRelease::countForItem($ID, PluginReleasesDeploytask::class);
      $testDone        = PluginReleasesRelease::countForItem($ID, PluginReleasesTest::class, PluginReleasesTest::DONE);
      $testTotal       = PluginReleasesRelease::countForItem($ID, PluginReleasesTest::class);
      $testFail        = PluginReleasesTest::countFailForItem($release);
      $deployTaskFail  = PluginReleasesDeploytask::countFailForItem($release);

      $allfinish = (PluginReleasesRisk::countForItem($release) == PluginReleasesRisk::countDoneForItem($release))
                   && ($deployTaskTotal == $deployTaskDone)
                   && ($testTotal == $testDone)
                   && (PluginReleasesRollback::countForItem($release) == PluginReleasesRollback::countDoneForItem($release));

      if (!$allfinish) {
         echo '<div class="alert alert-important alert-warning d-flex">';
         echo __("Care all steps are not finish !", "releases") . '</div>';
      }
      $target = $CFG_GLPI['root_doc'] . "/plugins/releases/front/finalization.php";
      echo "<form name='release_form' id='release_form' method='post'
                action='" . $target . "'>";

      echo __("Production run date", "releases");
      Html::showDateTimeField("date_production", ["id"         => "date_production",
                                                  "maybeempty" => false, "size" => 40]);


      if (isset($params["failed"])) {

         echo Html::submit(__("Mark as failed", 'releases'), ['name' => 'failed', 'class' => 'btn btn-danger']);
         echo Html::hidden('id', ['value' => $ID]);
         echo Html::hidden('failedtasks', ['value' => $deployTaskFail]);
         echo Html::hidden('failedtests', ['value' => $testFail]);

      } else {

         echo Html::submit(__("Finalize", 'releases'), ['name' => 'finalize', 'class' => 'btn btn-success']);
         echo Html::hidden('id', ['value' => $ID]);

      }
      Html::closeForm();
   }
}
