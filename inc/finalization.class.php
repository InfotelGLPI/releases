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

   public $dohistory = true;
   static $rightname = 'plugin_releases_releases';
   protected $usenotepad = true;
   static $types = [];
   public $userlinkclass = 'PluginReleasesRelease_User';
   public $grouplinkclass = 'PluginReleasesGroup_Release';
   public $supplierlinkclass = 'PluginReleasesRelease_Supplier';

   // STATUS
   const TODO = 1; // todo
   const DONE = 2; // done
   const PROCESSING = 3; // processing
   const WAITING = 4; // waiting
   const LATE = 5; // late
   const DEF = 6; // default

   const NEWRELEASE = 7;
   const RELEASEDEFINITION = 8; // default
   const DATEDEFINITION = 9; // date definition
   const CHANGEDEFINITION = 10; // changes defenition
   const RISKDEFINITION = 11; // risks definition
   const ROLLBACKDEFINITION = 12; // rollbacks definition
   const TASKDEFINITION = 13; // tasks definition
   const TESTDEFINITION = 14; // tests definition
   const FINALIZE = 15; // finalized
   const REVIEW = 16; // reviewed
   const CLOSED = 17; // closed


//   static $typeslinkable = ["Computer"  => "Computer",
//                            "Appliance" => "Appliance"];


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

   function showForm($ID,$options = []) {
      global $CFG_GLPI;
      $release = new PluginReleasesRelease();
      $release->getFromDB($ID);

      echo "<table class='tab_cadre_fixe' id='mainformtable'>";
      echo "<tr class='tab_bg_1'>";
      echo "<td>";
//      echo _n('Risk', 'Risks', 2, 'releases');
//      echo "</td>";
//      echo "<td>";
//      echo PluginReleasesRelease::getStateItem($release->getField("risk_state"));
//      echo "</td>";
//      echo "<td>";
//
//
//      echo "<td>";
//      echo _n('Rollback', 'Rollbacks', 2, 'releases');
//      echo "</td>";
//      echo "<td>";
//      echo PluginReleasesRelease::getStateItem($release->getField("rollback_state"));
//      echo "</td>";
//
//      echo "<td>";
//      echo _n('Deploy task', 'Deploy tasks', 2, 'releases');
//      echo "</td>";
//      echo "<td class='left'>";
      $dtF = PluginReleasesRelease::countForItem($ID, PluginReleasesDeploytask::class, 1);
      $dtT = PluginReleasesRelease::countForItem($ID, PluginReleasesDeploytask::class);
      $dtFail = PluginReleasesDeploytask::countFailForItem($release);
      $taskfailed = "";
      if($dtFail != 0){
         $taskfailed = "bulleFailed";
      }
      if ($dtT != 0) {
         $pourcentageTask = $dtF / $dtT * 100;
      } else {
         $pourcentageTask = 100;
      }
//
      $tF = PluginReleasesRelease::countForItem($ID, PluginReleasesTest::class, 1);
      $tT = PluginReleasesRelease::countForItem($ID, PluginReleasesTest::class);
      $tFail = PluginReleasesTest::countFailForItem($release);
      $testfailed = "";
      if($tFail != 0){
         $testfailed = "bulleFailed";
      }
      if ($tT != 0) {
         $pourcentageTest = $tF / $tT * 100;
      } else {
         $pourcentageTest = 100;
      }
//      echo "<div class=\"progress-circle\" data-value=\"" . round($pourcentage) . "\">
//                <div class=\"progress-masque\">
//                    <div class=\"progress-barre\"></div>
//                    <div class=\"progress-sup50\"></div>
//                </div>
//               </div>";
//
//      //      echo $dtF;
//      //      echo "/";
//      //      echo $dtT;
//      echo "</td>";
//
//      echo "<td>";
//      echo _n('Test', 'Tests', 2, 'releases');
//      echo "</td>";
//      echo "<td>";
//      echo PluginReleasesRelease::getStateItem($release->getField("test_state"));
      $riF = PluginReleasesRelease::countForItem($ID, PluginReleasesRisk::class, 1);
      $riT = PluginReleasesRelease::countForItem($ID, PluginReleasesRisk::class);
      $roF = PluginReleasesRelease::countForItem($ID, PluginReleasesRollback::class, 1);
      $roT = PluginReleasesRelease::countForItem($ID, PluginReleasesRollback::class);
      echo "<section id=\"timeline\">
  <article>
    <div class=\"inner\" >
      <span class=\"bulle riskBulle\">
        ".PluginReleasesRelease::getStateItem($release->getField('risk_state'))."
      </span>
      <h2 class='risk'>"._n('Risk', 'Risk', 2,'releases')."</h2>
      <p>".sprintf(__('%s / %s risks'),$riF,$riT )."</p>
    </div>
  </article>
  <article>
    <div class=\"inner\">
      <span class=\"bulle rollbackBulle\">
        ".PluginReleasesRelease::getStateItem($release->getField("rollback_state"))."
      </span>
      <h2 class='rollback'>"._n('Rollback','Rollbacks',2, 'release')."</h2>
      <p>".sprintf(__('%s / %s rollbacks'),$roF,$roT )."</p>
    </div>
  </article>
  <article>
    <div class=\"inner\">
      <span class=\"bulle taskBulle $taskfailed\">
      <br>
      <span class='percent '>
        ".$pourcentageTask." %
      </span>
      </span>
      <h2 class='task'>" . _n('Deploy task', 'Deploy tasks', 2, 'releases') . "</h2>
      <p>".sprintf(__('%s / %s deploy tasks'),$dtF,$dtT )."</br>
      ".sprintf(__('%s  deploy tasks failed'),$dtFail )."</p>
    </div>
  </article>
  <article>
    <div class=\"inner\">
    
      <span class=\"bulle testBulle $testfailed\">
        <br>
        <span class='percent'>
            ".$pourcentageTest." %
        </span>
      </span>
      <h2 class='test'>"._n('Test','Tests', 2,'release')."</h2>
      <p>".sprintf(__('%s / %s tests'),$tF,$tT )."</br>
      ".sprintf(__('%s  tests failed'),$tFail )."</p>
    </div>
  </article>
</section>";
      echo "</td>";

      echo "</tr>";


      echo "</table>";
      $allfinish = $release->getField("risk_state")
         && ($dtT == $dtF)
         && $release->getField("test_state")
         && $release->getField("rollback_state");
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

         echo Html::scriptBlock("var mTitle =  \"<i class='" . $srcImg . " fa-1x' style='color:" . $color . "'></i>&nbsp;" . "finalize" . " \";");
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
            'ok': function() {
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
            'cancel': function() {
                  $( this ).dialog( 'close' );
             }
         },
         
       })");
      }
   }
}