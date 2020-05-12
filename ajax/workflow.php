<?php
/*
 * @version $Id: HEADER 15930 2011-10-30 15:47:55Z tsmr $
 -------------------------------------------------------------------------
 databases plugin for GLPI
 Copyright (C) 2009-2016 by the databases Development Team.

 https://github.com/InfotelGLPI/databases
 -------------------------------------------------------------------------

 LICENSE

 This file is part of databases.

 databases is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 databases is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with databases. If not, see <http://www.gnu.org/licenses/>.
 --------------------------------------------------------------------------
 */

if (strpos($_SERVER['PHP_SELF'], "workflow.php")) {
   $AJAX_INCLUDE = 1;
   include('../../../inc/includes.php');
   header("Content-Type: text/html; charset=UTF-8");
   Html::header_nocache();
}

Session::checkCentralAccess();


if (isset($_POST["id"])) {
   global $DB;
   $ID = $_POST["id"];
   $query = "SELECT `state`,`itemtype`
                      FROM `glpi_plugin_releases_globalstatues`"." WHERE plugin_releases_releases_id=".$ID;

   $result = $DB->query($query);
   $vals    = [];
   if ($DB->numrows($result) >= 1) {
      while ($line = $DB->fetch_assoc($result)) {
         if($line["itemtype"] == PluginReleasesRelease::getType()) {
            $vals[PluginReleasesRelease::getType()] = PluginReleasesRelease::getStatusKey($line["state"]);
            $releasedef = PluginReleasesRelease::getStatusKey($line["state"]);
         }else if($line["itemtype"] == 'PluginReleaseDate'){
            $vals['PluginReleaseDate'] = PluginReleasesRelease::getStatusKey($line["state"]);
            $datec = PluginReleasesRelease::getStatusKey($line["state"]);
         }else if($line["itemtype"] == PluginReleasesRisk::getType()){
            $vals[PluginReleasesRisk::getType()] = PluginReleasesRelease::getStatusKey($line["state"]);
            $risk = PluginReleasesRelease::getStatusKey($line["state"]);
         }else if($line["itemtype"] == PluginReleasesTest::getType()){
            $vals[PluginReleasesTest::getType()] = PluginReleasesRelease::getStatusKey($line["state"]);
            $tests = PluginReleasesRelease::getStatusKey($line["state"]);
         }else if($line["itemtype"] == PluginReleasesDeployTask::getType()) {
            $vals[PluginReleasesDeployTask::getType()] = PluginReleasesRelease::getStatusKey($line["state"]);
            $tasks = PluginReleasesRelease::getStatusKey($line["state"]);
         }else if($line["itemtype"] == PluginReleasesRollback::getType()) {
            $vals[PluginReleasesRollback::getType()] = PluginReleasesRelease::getStatusKey($line["state"]);
            $rollback = PluginReleasesRelease::getStatusKey($line["state"]);
         }
      }
   }

   $steps = [PluginReleasesRelease::getType(),'PluginReleaseDate', PluginReleasesRisk::getType(),PluginReleasesTest::getType(),PluginReleasesDeployTask::getType(),PluginReleasesRollback::getType()];
   $var = '<div class=" title header center">WorkFlow</div><ul id="progress">
                   <li><div id="PluginReleasesRelease" class="node workflowstatus '.$releasedef.'"></div><p>'.__("Release definition","releases").'</p></li>
                   <li><div class="divider workflowstatus '.$datec.'"></div></li>
                   <li><div id="PluginReleaseDate" class="node workflowstatus '.$datec.'"></div><p>'.__("Date choose","releases").'</p></li>
                   <li><div class="divider workflowstatus '.$risk.'"></div></li>
                   <li><div id="PluginReleasesRisk" class="node workflowstatus '.$risk.'"></div><p>'.__("Risks definition","releases").'</p></li>
                   <li><div class="divider workflowstatus '.$tests.'"></div></li>
                   <li><div id="PluginReleasesTest" class="node workflowstatus '.$tests.'"></div><p>'.__("Tests definition","releases").'</p></li>
                   <li><div class="divider workflowstatus '.$tasks.'"></div></li>
                   <li><div id="PluginReleasesDeployTask" class="node  workflowstatus '.$tasks.'"></div><p>'.__("Tasks definition","releases").'</p></li>
                   <li><div class="divider workflowstatus '.$rollback.'"></div></li>
                   <li><div id="PluginReleasesRollback" class="node workflowstatus '.$rollback.'"></div><p>'.__("Rollbacks definition","releases").'</p></li>
                   <!-- Modal -->     
    </ul>';

   foreach ($steps as $step) {


      $var .= '<div id="modal'.$step.'"></div><script> 
            $("#modal'.$step.'").html(\' \
             <span id="done'.$step.'" class="status"><i class="releasestatus fas fa-circle done" title="' . __("Done") . '"></i>' . __("Done") . '</span> <br>\
              <span id="todo'.$step.'" class="status"><i class="releasestatus fas fa-circle todo" title="' . __("To do") . '"></i>' . __("To do") . '</span><br>\
              <span id="inprogress'.$step.'" class="status"><i class="releasestatus fas fa-circle inprogress" title="' . __("In progress", "releases") . '"></i>' . __("In progress", "releases") . '</span><br>\
              <span id="waiting'.$step.'" class="status"><i class="releasestatus fas fa-circle waiting" title="' . __("Waiting") . '"></i>' . __("Waiting") . '</span><br>\
              <span id="late'.$step.'" class="status"><i class="releasestatus fas fa-circle late" title="' . __("Late", "releases") . '"></i>' . __("Late", "releases") . '</span><br>\
              <span id="def'.$step.'" class="status"><i class="releasestatus fas fa-circle def" title="' . __("Default") . '"></i>' . __("Default") . '</span><br>\
             \').dialog({
             title:"choix du statut",
            dialogClass: \'glpi_modal\',
           autoOpen: false,
           modal: true,
          resizable: true,
           draggable: true,
//           position: "relative",
            position: {my:"right",of:$("#PluginReleasesRelease")},
           height:"auto",
            width: "auto"
         });
         $("#done'.$step.'").click(function () {
              var state = 1;
              var plugin_releases_releases_id = '.$ID.';
              var itemtype = "'.$step.'";
              var data = {state,plugin_releases_releases_id,itemtype};
              if (confirm("'.__("Confirm change status").'")) {
                  $.ajax({
                      data: data,
                      type: \'POST\',
                      url: \'../ajax/changeState.php\',
                      success: function (data) {
                           var id = '.$ID.';
                           var d = {id};
                          $.ajax({
                              data: d,
                              type: \'POST\',
                              url: \'../ajax/workflow.php\',
                              success: function (data) {
                             
                                 $("#modal'.$step.'").dialog("close");
                                   $("#workflow").html(data);
                              }
                           });    
                      }
                  });
              }
          });
         $("#todo'.$step.'").click(function () {
              var state = 0;
              var plugin_releases_releases_id = '.$ID.';
              var itemtype = "'.$step.'";
              var data = {state,plugin_releases_releases_id,itemtype};
              if (confirm("'.__("Confirm change status").'")) {
                  $.ajax({
                      data: data,
                      type: \'POST\',
                      url: \'../ajax/changeState.php\',
                      success: function (data) {
                         var id = '.$ID.';
                           var d = {id};
                          $.ajax({
                              data: d,
                              type: \'POST\',
                              url: \'../ajax/workflow.php\',
                              success: function (data) {
                             
                                 $("#modal'.$step.'").dialog("close");
                                   $("#workflow").html(data);
                              }
                           });    
                      }
                  });
              }
          });
         $("#inprogress'.$step.'").click(function () {
              var state = 2;
              var plugin_releases_releases_id = '.$ID.';
              var itemtype = "'.$step.'";
              var data = {state,plugin_releases_releases_id,itemtype};
              if (confirm("'.__("Confirm change status").'")) {
                  $.ajax({
                      data: data,
                      type: \'POST\',
                      url: \'../ajax/changeState.php\',
                      success: function (data) {
                          var id = '.$ID.';
                           var d = {id};
                          $.ajax({
                              data: d,
                              type: \'POST\',
                              url: \'../ajax/workflow.php\',
                              success: function (data) {
                             
                                 $("#modal'.$step.'").dialog("close");
                                   $("#workflow").html(data);
                              }
                           });    
                      }
                  });
              }
          });
         $("#waiting'.$step.'").click(function () {
              var state = 3;
              var plugin_releases_releases_id = '.$ID.';
              var itemtype = "'.$step.'";
              var data = {state,plugin_releases_releases_id,itemtype};
              if (confirm("'.__("Confirm change status").'")) {
                  $.ajax({
                      data: data,
                      type: \'POST\',
                      url: \'../ajax/changeState.php\',
                      success: function (data) {
                          var id = '.$ID.';
                           var d = {id};
                          $.ajax({
                              data: d,
                              type: \'POST\',
                              url: \'../ajax/workflow.php\',
                              success: function (data) {
                             
                                 $("#modal'.$step.'").dialog("close");
                                   $("#workflow").html(data);
                              }
                           });    
                      }
                  });
              }
          });
         $("#late'.$step.'").click(function () {
              var state = 4;
              var plugin_releases_releases_id = '.$ID.';
              var itemtype = "'.$step.'";
              var data = {state,plugin_releases_releases_id,itemtype};
              if (confirm("'.__("Confirm change status").'")) {
                  $.ajax({
                      data: data,
                      type: \'POST\',
                      url: \'../ajax/changeState.php\',
                      success: function (data) {
                          var id = '.$ID.';
                           var d = {id};
                          $.ajax({
                              data: d,
                              type: \'POST\',
                              url: \'../ajax/workflow.php\',
                              success: function (data) {
                             
                                 $("#modal'.$step.'").dialog("close");
                                   $("#workflow").html(data);
                              }
                           });    
                      }
                  });
              }
          });
         $("#default'.$step.'").click(function () {
              var state = 5;
              var plugin_releases_releases_id = '.$ID.';
              var itemtype = "'.$step.'";
              var data = {state,plugin_releases_releases_id,itemtype};
              if (confirm("'.__("Confirm change status").'")) {
                  $.ajax({
                      data: data,
                      type: \'POST\',
                      url: \'../ajax/changeState.php\',
                      success: function (data) {
                          var id = '.$ID.';
                           var d = {id};
                          $.ajax({
                              data: d,
                              type: \'POST\',
                              url: \'../ajax/workflow.php\',
                              success: function (data) {
                             
                                 $("#modal'.$step.'").dialog("close");
                                   $("#workflow").html(data);
                              }
                           });    
                      }
                  });
              }
          });
         </script> ';
   }


   $var .= "<script>
               $( \"#PluginReleasesRelease\" ).click(function() {
               $(\"#modal".PluginReleasesRelease::getType()."\").dialog(\"open\");
                 
               });
               $( \"#PluginReleaseDate\" ).click(function() {
                  if( $( \"#PluginReleasesRelease\").hasClass('done')){
                  $(\"#modalPluginReleaseDate\").dialog(\"open\");
                 
                   }
                 
               });
               $( \"#PluginReleasesRisk\" ).click(function() {
                  if( $( \"#PluginReleaseDate\").hasClass('done')){
                        $(\"#modalPluginReleaseRisk\").dialog(\"open\");
                   }        
               });
               $( \"#PluginReleasesTest\" ).click(function() {
                 if( $( \"#PluginReleasesRisk\").hasClass('done')){
                        $(\"#modalPluginReleaseTest\").dialog(\"open\");
                   }
});
               $( \"#PluginReleasesDeployTask\" ).click(function() {
                 if( $( \"#PluginReleasesTest\").hasClass('done')){
                        $(\"#modalPluginReleaseDeployTask\").dialog(\"open\");
                   }
               });
               $( \"#PluginReleasesRollback\" ).click(function() {
                 if( $( \"#PluginReleasesDeployTask\").hasClass('done')){
                        $(\"#modalPluginReleaseRollback\").dialog(\"open\");
                   }
               });


</script>";

   echo $var;
}
