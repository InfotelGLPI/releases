<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2018 by the Releases Development Team.

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

   public    $dohistory         = true;
   static    $rightname         = 'plugin_releases_releases';



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
               return self::getTypeName(2);
         }
      }
      return '';
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
      $deployTaskDone = PluginReleasesRelease::countForItem($ID, PluginReleasesDeploytask::class, 1);
      $deployTaskTotal = PluginReleasesRelease::countForItem($ID, PluginReleasesDeploytask::class);
      $deployTaskFail = PluginReleasesDeploytask::countFailForItem($release);
      $taskfailed = "";
      $task_state = PluginReleasesDeploytask::TODO;
      if ($deployTaskFail != 0) {
         $taskfailed = "bulleFailed";
         $task_state = PluginReleasesDeploytask::FAIL;
      }
      if ($deployTaskTotal != 0) {
         $pourcentageTask = $deployTaskDone / $deployTaskTotal * 100;
      } else {
         $pourcentageTask = 100;
         $task_state = PluginReleasesDeploytask::DONE;
      }
      $test_state = PluginReleasesTest::TODO;
      $testDone = PluginReleasesRelease::countForItem($ID, PluginReleasesTest::class, 1);
      $testTotal = PluginReleasesRelease::countForItem($ID, PluginReleasesTest::class);
      $testFail = PluginReleasesTest::countFailForItem($release);
      $testfailed = "";
      if ($testFail != 0) {
         $testfailed = "bulleFailed";
         $test_state = PluginReleasesTest::FAIL;
      }
      if ($testTotal != 0) {
         $pourcentageTest = $testDone / $testTotal * 100;
      } else {
         $pourcentageTest = 100;
         $test_state = PluginReleasesTest::DONE;
      }

      $riskDone = PluginReleasesRelease::countForItem($ID, PluginReleasesRisk::class, 1);
      $riskTotal = PluginReleasesRelease::countForItem($ID, PluginReleasesRisk::class);
      $rollbackDone = PluginReleasesRelease::countForItem($ID, PluginReleasesRollback::class, 1);
      $rollbackTotal = PluginReleasesRelease::countForItem($ID, PluginReleasesRollback::class);
      echo "<section id=\"timeline\">
  <article>
    <div class=\"inner\" >
      <span class=\"bulle riskBulle\">
        " . PluginReleasesRelease::getStateItem($risk_state) . "
      </span>
      <h2 class='risk'>" . _n('Risk', 'Risk', 2, 'releases') . "<i class='fas fa-bug' style=\"float: right;\"></i></h2>
      <p>" . sprintf(__('%s / %s risks'), $riskDone, $riskTotal) . "</p>
    </div>
  </article>
  <article>
    <div class=\"inner\">
      <span class=\"bulle rollbackBulle\">
        " . PluginReleasesRelease::getStateItem($rollback_state) . "
      </span>
      <h2 class='rollback'>" . _n('Rollback', 'Rollbacks', 2, 'releases') . "<i class='fas fa-undo-alt' style=\"float: right;\"></i></h2>
      <p>" . sprintf(__('%s / %s rollbacks'), $rollbackDone, $rollbackTotal) . "</p>
    </div>
  </article>
  <article>
    <div class=\"inner\">
      <span class=\"bulle taskBulle $taskfailed\">
      " . PluginReleasesRelease::getStateItem($task_state) . "
      </span>
      <h2 class='task'>" . _n('Deploy task', 'Deploy tasks', 2, 'releases') . "<i class='fas fa-check-square' style=\"float: right;\"></i></h2>
      <p>" . sprintf(__('%s / %s deploy tasks'), $deployTaskDone, $deployTaskTotal) . "</br>
      " . sprintf(__('%s  deploy tasks failed'), $deployTaskFail) . "<span class='percent' style=\"float: right;\">
            " . $pourcentageTask . " %
        </span></p>
    </div>
  </article>
  <article>
    <div class=\"inner\">
    <span class=\"bulle taskBulle $testfailed\">
      " . PluginReleasesRelease::getStateItem($test_state) . "
      </span>
      <h2 class='test'>" . _n('Test', 'Tests', 2, 'releases') . "<i class='fas fa-check' style=\"float: right;\"></i></h2>
      <p>" . sprintf(__('%s / %s tests', 'releases'), $testDone, $testTotal) . "</br>
      " . sprintf(__('%s  tests failed'), $testFail) . "<span class='percent' style=\"float: right;\">
            " . $pourcentageTest . " %
        </span></p>
    </div>
  </article>
</section>";
      echo "</td>";

      echo "</tr>";


      echo "</table>";
      if ($deployTaskFail == 0 && $testFail == 0) {
         $allfinish = (PluginReleasesRisk::countForItem($release) == PluginReleasesRollback::countDoneForItem($release))
            && ($deployTaskTotal == $deployTaskDone)
            && ($testTotal == $testDone)
            && (PluginReleasesRollback::countForItem($release) == PluginReleasesRollback::countDoneForItem($release));
         $text = "";
         if (!$allfinish) {

            $text .= '<span class="center"><i class=\'fas fa-exclamation-triangle fa-1x\' style=\'color: orange\'></i> ' . __("Care all steps are not finish !") . '</span>';
            $text .= "<br>";
            $text .= "<br>";
         }
         if ($release->getField('status') < PluginReleasesRelease::FINALIZE) {
            echo '<a id="finalize" class="vsubmit"> ' . __("Finalize", 'releases') . '</a>';

            echo Html::scriptBlock(
               "$('#finalize').click(function(){
               $( '#alert-message' ).dialog( 'open' );
      
               });");
            echo "<div id='alert-message' class='tab_cadre_navigation_center' style='display:none;'>" . $text . __("production run date", "releases") . Html::showDateField("date_production", ["id" => "date_production", "maybeempty" => false, "display" => false]) . "</div>";
            $srcImg = "fas fa-info-circle";
            $color = "forestgreen";
            $alertTitle = _n("Information", "Informations", 1);

            echo Html::scriptBlock("var mTitle =  \"<i class='" . $srcImg . " fa-1x' style='color:" . $color . "'></i>&nbsp;" . __("finalize",'releases') . " \";");
            echo Html::scriptBlock("$( '#alert-message' ).dialog({
              autoOpen: false,
              height: " . 200 . ",
              width: " . 300 . ",
              modal: true,
              open: function (){
               $(this)
                  .parent()
                  .children('.ui-dialog-titlebar')
                  .html(mTitle);
            },
              buttons: {
               '".__("Ok")."': function() {
                  if($(\"[name = 'date_production']\").val() == '' || $(\"[name = 'date_production']\").val() === undefined){
              
                    $(\"[name = 'date_production']\").siblings(':first').css('border-color','red')
                  }else{  
                     var date = $(\"[name = 'date_production']\").val();
                     console.log(date);
                     $.ajax({
                        url:  '" . $CFG_GLPI['root_doc'] . "/plugins/releases/ajax/finalize.php',
                        data: {'id' : " . $release->getID() . ",'date' : date},
                        success: function() {
                           document.location.reload();
                        }
                     });
                     
                  }
               
               },
               '".__("Cancel")."': function() {
                     $( this ).dialog( 'close' );
                }
            },
            
          })");
         }
      }else{
         $text = "";
         if ($release->getField('status') <= PluginReleasesRelease::FAIL) {
            echo '<a id="finalize" class="vsubmit"> ' . __("Mark as failed", 'releases') . '</a>';

            echo Html::scriptBlock(
               "$('#finalize').click(function(){
               $( '#alert-message' ).dialog( 'open' );
      
               });");
            echo "<div id='alert-message' class='tab_cadre_navigation_center' style='display:none;'>" . $text . __("production run date", "releases") . Html::showDateField("date_production", ["id" => "date_production", "maybeempty" => false, "display" => false]) . "</div>";
            $srcImg = "fas fa-times";
            $color = "firebrick";
            $alertTitle = _n("Information", "Informations", 1);

            echo Html::scriptBlock("var mTitle =  \"<i class='" . $srcImg . " fa-1x' style='color:" . $color . "'></i>&nbsp;" . __("Mark as failed",'releases') . " \";");
            echo Html::scriptBlock("$( '#alert-message' ).dialog({
              autoOpen: false,
              height: " . 200 . ",
              width: " . 300 . ",
              modal: true,
              open: function (){
               $(this)
                  .parent()
                  .children('.ui-dialog-titlebar')
                  .html(mTitle);
            },
              buttons: {
               '".__("Confirm",'releases')."': function() {
                  if($(\"[name = 'date_production']\").val() == '' || $(\"[name = 'date_production']\").val() === undefined){
              
                    $(\"[name = 'date_production']\").siblings(':first').css('border-color','red')
                  }else{  
                     var date = $(\"[name = 'date_production']\").val();
                     $.ajax({
                        url:  '" . $CFG_GLPI['root_doc'] . "/plugins/releases/ajax/finalize.php',
                        data: {'id' : " . $release->getID() . ",'failedtasks' : $deployTaskFail , 'failedtests' : $testFail},
                        success: function() {
                           document.location.reload();
                        }
                     });
                     
                  }
               
               },
               '".__("Cancel")."': function() {
                     $( this ).dialog( 'close' );
                }
            },
            
          })");
         }
      }


   }
}