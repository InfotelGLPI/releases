<?php
/*
 -------------------------------------------------------------------------
 Releases plugin for GLPI
 Copyright (C) 2015 by the Releases Development Team.
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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginReleasesDeployment extends CommonDBTM {

    static $rightname = "plugin_releases";

    /**
     * @since version 0.84
     * */
    static function getTypeName($nb = 0) {
        return _n('Deployment', 'Deployments', $nb,'releases');
    }

    /**
     * Return the name of the tab for item including forms like the config page
     *
     * @param  CommonGLPI $item Instance of a CommonGLPI Item (The Config Item)
     * @param  integer    $withtemplate
     *
     * @return String                   Name to be displayed
     */
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {
        switch ($item->getType()) {
            case "Change":
                $nb = 0;
//            if ($_SESSION['glpishow_count_on_tabs']) {
//               $nb = countElementsInTable('glpi_plugin_eventsmanager_tickets',
//                                          "tickets_id = '".$item->getID()."'");
//            }
                return self::getTypeName($nb);
                break;
        }
        return '';
    }

    /**
     * @param CommonGLPI $item
     * @param int $tabnum
     * @param int $withtemplate
     * @return bool
     */
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {
        $deplo = new self();
        $ID = $_GET['id'];
        $deplo->showSummary($item, $ID);
    }

    function showSummary($item, $ID, $options = array()) {
        global $CFG_GLPI;

       if(!$this->find(["changes_id" => $ID])){
            $this->add(array('changes_id' => $ID));
            $this->showSummary($item, $ID);
        }else {

        $this->initForm($this->getID(), $options);
        $options["formtitle"] = "";
        $this->showFormHeader($options);
        echo "<table class='tab_cadre_fixe'>";
        echo "<tr class='headerRow'>";
        echo '<th colspan="2">' . __('Choice of deployment methode','releases') . '</th>';
        echo '</tr>';

        echo '<tr><td>';
        static::dropdownDeploymentType(array('value' => $this->fields['type']));
        echo '</td>';

        if (isset($this->fields['type'])
               && $this->fields['type'] == 2) {
            echo '<td>';
            echo
            "<a id='addbutton' class='vsubmit' href='javascript:viewAddPhase" . $item->fields['id'] . "();'>";
            echo __('Add a new phase','releases') . "</a>\n";
            echo '</td>';
            echo "<script type='text/javascript' >\n";
            echo "function viewAddPhase" . $item->fields['id'] . "() {\n";
            $params = array('type' => $this->getType(),
                'parenttype' => $item->getType(),
                $item->getForeignKeyField() => $item->fields['id'],
                'deployments_id' => $this->getID(),
                'id' => -1);
            Ajax::updateItemJsCode("viewfollowup" . $item->fields['id'] . "", $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/viewsubitemphase.php", $params);
            echo "};";
            echo "</script>\n";
        }
        echo '</tr>';

        echo "<tr class='tab_bg_1 center'><td colspan='4'>";
        echo "<input type='submit' name='updatetype' value=\"" . _sx('button', 'Save') . "\" class='submit'>";
        echo "<input type='hidden' name='id' value=" . $this->getID() . ">";
        echo "</td></tr>";
        echo "</table>";
        Html::closeForm();

        $phases = getAllDatasFromTable('glpi_plugin_releases_phases', '`deployments_id`="' . $this->getID() . '"');

        if (isset($this->fields['type'])
               && $this->fields['type'] == 2
                  && count($phases) > 0) {
            echo '<table class="tab_cadre_fixe" id="mainformtable">';
            echo '<tbody>';
            echo '<tr class="headerRow">';
            echo '<th>' . __('List of phases','releases') . "</th>";
            echo '</tr>';
            echo '</tbody>';
            echo '</table>';

            echo '<table class="tab_cadre_fixe" id="mainformtable">';

            echo '<thead>';
            echo '<tr role="row">';
            echo '<th class="sorting_disabled">' . __('Name') . "</th>";
            echo '<td width="1"></td>';
            echo '<th class="sorting_disabled">' . __('Description') . "</th>";
            echo "</tr>";
            echo '</thead/>';

            echo '<tbody role=alert>';

            foreach ($phases as $data) {
                echo "<tr class='tab_bg_2' style='cursor:pointer' onClick=\"viewEditPhase" . $data['id'] . "();\">";
                echo '<td class="center">';
                echo $data['name'];
                echo '</td>';
                echo '<td></td>';
                echo '<td class="center">';
                echo $data['comment'];
                echo '</td>';
                echo '</tr>';

                echo "\n<script type='text/javascript' >\n";
                echo "function viewEditPhase" . $data['id'] . "() {\n";
                $params = array('type' => $this->getType(),
                    'parenttype' => $item->getType(),
                    'deployments_id' => $this->getID(),
                    'id' => $data["id"]);
                Ajax::updateItemJsCode("viewfollowup" . $ID, $CFG_GLPI["root_doc"] . "/plugins/releases/ajax/viewsubitemphase.php", $params);
                echo "};";
                echo "</script>\n";
            }
        }
        echo '</tbody>';

        echo '</table>';
        echo "<div id='viewfollowup" . $ID . "'></div>\n";
        
        $plugin = new Plugin();
        if ($plugin->isActivated("pdf")){
            echo '<br><br>';
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='headerRow'>";
            echo '<th colspan="2">' . __('Save the configuration of associated elements of change','releases') . '</th>';
            echo '</tr>';
            echo '<td class="center">';
            echo "<a id='addbutton' class='vsubmit' href='".$CFG_GLPI["root_doc"]."/plugins/releases/ajax/saveConf.php?id=".$ID."'>";
            echo __('Save configuration','releases') . "</a>\n";
            echo '</td>';
            echo '</table>';
        } else {
            echo '<br><br>';
            echo "<table class='tab_cadre_fixe'>";
            echo "<tr class='headerRow'>";
            echo '<th colspan="2">' . __('Save the configuration of associated elements of change','releases') . '</th>';
            echo '</tr>';
            echo '<td class="center">';
            echo __('You need pdf plugin for this fonctionality','releases');
            echo '</td>';
            echo '</table>';
        }
        
        }
        

    }

    /**
     * get the change status list
     * To be overridden by class
     *
     * @param $withmetaforsearch boolean (default false)
     *
     * @return an array
     * */
    static function dropdownDeploymentType($options) {

        $tab = array(0 => __('----'),
            1 => __('Bigbang'),
            2 => __('Phases','releases'));

        return Dropdown::showFromArray('type', $tab, $options);
    }

    /** form for Task
     *
     * @param $ID        Integer : Id of the task
     * @param $options   array
     *     -  parent Object : the object
     * */
    function showForm($ID, $options = array()) {
        global $DB, $CFG_GLPI;

        $phase = new PluginReleasesPhase();

        if ($ID >= 0) {
            $phase->check($ID, READ);
            $phase->getFromDB($ID);
        } else {
            // Create item
            //$options[$fkfield] = $item->getField('id');
            $phase->check(-1, CREATE, $options);
        }

        $rand = mt_rand();
        $phase->showFormHeader(array('formtitle' => false));
        echo "<tr class='headerRow'><th colspan='4'>";
        if ($ID > 0) {
            echo __('Edit phase','releases');
        } else {
            echo __("New phase",'releases');
        }
        echo '</th></tr>';

        $rowspan = 5;

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Name') . "</td>";
        echo "<td>";
        Html::autocompletionTextField($phase, "name");
        echo "<td>";
        echo "</tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td rowspan='$rowspan' style='width:100px'>" . __('Description') . "</td>";
        if (isset($phase->fields["comment"])) {
            $text = $phase->fields["comment"];
        } else {
            $text = "";
        }
        echo "<td rowspan='$rowspan' style='width:50%' id='content'>" .
        "<textarea name='comment' style='width: 95%; height: 160px' id='phase'>" . $text .
        "</textarea>";
        echo Html::scriptBlock("$(document).ready(function() { $('#content$rand').autogrow(); });");
        echo "</td>";
        echo "<input type='hidden' name='deployments_id' value='" . $options['deployments_id'] . "'>";
        echo "</tr>\n";


        $phase->showFormButtons(array('formfooter' => false));


        return true;
    }

}
