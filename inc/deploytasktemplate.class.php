<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2018 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Template for task
 * @since 9.1
 **/
class PluginReleasesDeploytasktemplate extends CommonDropdown
{

    // From CommonDBTM
    public $dohistory = true;
    public $can_be_translated = true;

    static $rightname = 'plugin_releases_tasks';


    static function getTypeName($nb = 0)
    {
        return _n('Deploy Task template', 'Deploy Task templates', $nb, 'releases');
    }


    function getAdditionalFields()
    {
        return [
            [
                'name' => 'content',
                'label' => __('Content'),
                'type' => 'textarea',
                'rows' => 10
            ],

            [
                'name' => 'plugin_releases_typedeploytasks_id',
                'label' => __('Deploy Task type', 'releases'),
                'type' => 'dropdownValue',
                'list' => true
            ],
            [
                'name' => 'state',
                'label' => __('Status'),
                'type' => 'state'
            ],
            [
                'name' => 'is_private',
                'label' => __('Private'),
                'type' => 'bool'
            ],
            [
                'name' => 'actiontime',
                'label' => __('Duration'),
                'type' => 'actiontime'
            ],
            [
                'name' => 'users_id_tech',
                'label' => __('By'),
                'type' => 'users_id_tech'
            ],
            [
                'name' => 'groups_id_tech',
                'label' => __('Group'),
                'type' => 'groups_id_tech'
            ],
        ];
    }


    function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id' => '4',
            'name' => __('Content'),
            'field' => 'content',
            'table' => $this->getTable(),
            'datatype' => 'text',
            'htmltext' => true
        ];

        $tab[] = [
            'id' => '3',
            'name' => PluginReleasesTypeDeployTask::getTypeName(),
            'field' => 'name',
            'table' => getTableForItemType('PluginReleasesTypeDeployTask'),
            'datatype' => 'dropdown'
        ];

        $tab[] = [
            'id' => '5',
            'name' => PluginReleasesReleasetemplate::getTypeName(),
            'field' => 'name',
            'table' => getTableForItemType('PluginReleasesReleasetemplate'),
            'datatype' => 'dropdown'
        ];

        return $tab;
    }


    /**
     * @see CommonDropdown::displaySpecificTypeField()
     **/
    function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        switch ($field['type']) {
            case 'state' :
                PluginReleasesDeploytask::dropdownStateTask("state", $this->fields["state"]);
                break;
            case 'users_id_tech' :
                User::dropdown([
                    'name' => "users_id_tech",
                    'right' => "own_ticket",
                    'value' => $this->fields["users_id_tech"],
                    'entity' => $this->fields["entities_id"],
                ]);
                break;
            case 'groups_id_tech' :
                Group::dropdown([
                    'name' => "groups_id_tech",
                    'condition' => ['is_task' => 1],
                    'value' => $this->fields["groups_id_tech"],
                    'entity' => $this->fields["entities_id"],
                ]);
                break;
            case 'actiontime' :
                $toadd = [];
                for ($i = 9; $i <= 100; $i++) {
                    $toadd[] = $i * HOUR_TIMESTAMP;
                }
                Dropdown::showTimeStamp(
                    "actiontime", [
                        'min' => 0,
                        'max' => 8 * HOUR_TIMESTAMP,
                        'value' => $this->fields["actiontime"],
                        'addfirstminutes' => true,
                        'inhours' => true,
                        'toadd' => $toadd
                    ]
                );
                break;
        }
    }

    static function canCreate()
    {
        return Session::haveRightsOr(static::$rightname, [UPDATE, CREATE]);
    }

    /**
     * Have I the global right to "view" the Object
     *
     * Default is true and check entity if the objet is entity assign
     *
     * May be overloaded if needed
     *
     * @return booleen
     **/
    static function canView()
    {
        return Session::haveRight(static::$rightname, READ);
    }


    function showForm($ID, $options = [])
    {
        global $CFG_GLPI;

        $rand_template = mt_rand();
        $rand_text = mt_rand();
        $rand_type = mt_rand();
        $rand_time = mt_rand();
        $rand_user = mt_rand();
        $rand_is_private = mt_rand();
        $rand_group = mt_rand();
        $rand_name = mt_rand();
        $rand_state = mt_rand();


        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            // Create item
            //         $options[$fkfield] = $item->getField('id');
            $this->check(-1, CREATE, $options);
        }

        //prevent null fields due to getFromDB
        if (is_null($this->fields['begin'])) {
            $this->fields['begin'] = "";
        }

        $rand = mt_rand();
        $this->showFormHeader($options);

        //      $canplan = (!$item->isStatusExists(CommonITILObject::PLANNED)
        //         || $item->isAllowedStatus($item->fields['status'], CommonITILObject::PLANNED));
        $canplan = true;
        $rowspan = 7;
        if ($this->maybePrivate()) {
            $rowspan++;
        }
        if (isset($this->fields["state"])) {
            $rowspan++;
        }
        echo "<tr class='tab_bg_1' hidden>";
        echo "<td colspan='4'>";
        $foreignKey = PluginReleasesReleasetemplate::getForeignKeyField();
        echo Html::hidden($foreignKey, ["value" => $this->fields[$foreignKey]]);
        echo "</td>";
        echo "</tr>";
        echo "<tr class='tab_bg_1'>";
        echo "<td class='fa-label'>
         <span>" . __('Name') . "</span>&nbsp;";
        echo "</td>";
        echo "<td class='fa-label'>";
        echo Html::input("name", ["id" => "name" . $rand_name, "rand" => $rand_name, "value" => $this->getField('name')]
        );

        echo "</td>";
        //      echo "<td colspan='2'></td>";
        echo "<td >" . __("Previous task", "releases") . "</td>";
        echo "<td>";
        $id_release = isset($options['plugin_releases_releasetemplates_id']) ? $options['plugin_releases_releasetemplates_id'] : $this->fields["plugin_releases_releasetemplates_id"];

        if ($ID != -1 && $ID != 0) {
            $forbidden_id = self::getAllDescendant($this->getID());
            Dropdown::show(PluginReleasesDeploytasktemplate::getType(), [
                "condition" => [
                    "plugin_releases_releasetemplates_id" => $id_release,
                    "NOT" => ["id" => $forbidden_id]
                ],
                "value" => $this->fields["plugin_releases_deploytasktemplates_id"],
                "comments" => false
            ]);
        } else {
            Dropdown::show(PluginReleasesDeploytasktemplate::getType(), [
                "condition" => [
                    "plugin_releases_releasetemplates_id" => $id_release,
                    "NOT" => ["id" => $this->getID()]
                ],
                "value" => $this->fields["plugin_releases_deploytasktemplates_id"],
                "comments" => false
            ]);
        }
        echo "</td>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td colspan='3' id='content$rand_text'>";

        $rand_text = mt_rand();
        $content_id = "content$rand_text";
        $cols = 100;
        $rows = 10;

        Html::textarea([
            'name' => 'content',
            'value' => $this->fields["content"],
            'rand' => $rand_text,
            'editor_id' => $content_id,
            'enable_fileupload' => false,
            'enable_richtext' => true,
            'cols' => $cols,
            'rows' => $rows
        ]);

        //      echo "<input type='hidden' name='$fkfield' value='".$this->fields[$fkfield]."'>";
        echo "</td>";

        echo "<td style='vertical-align: middle'>";


        if ($ID > 0) {
            echo "<div class='fa-label'>
         <i class='far fa-calendar fa-fw'
            title='" . __('Date') . "'></i>";
            Html::showDateTimeField("date", [
                'value' => $this->fields["date"],
                'timestep' => 1,
                'maybeempty' => false
            ]);
            echo "</div>";
        }

        echo "<div class='fa-label'>
         <i class='fas fa-tag fa-fw'
            title='" . __('Category') . "'></i>";
        PluginReleasesTypeDeployTask::dropdown([
            'value' => $this->fields["plugin_releases_typedeploytasks_id"],
            'rand' => $rand_type,
            //         'entity'    => $item->fields["entities_id"],
            //         'condition' => ['is_active' => 1]
        ]);
        echo "</div>";
        echo "<div class='fa-label'>
         <span>" . __('Risk', 'releases') . "</span>&nbsp;";
        Dropdown::show(PluginReleasesRisktemplate::getType(), [
            'name' => "plugin_releases_risks_id",
            'value' => $this->fields["plugin_releases_risks_id"],
            "condition" => ["plugin_releases_releasetemplates_id" => $this->fields[$foreignKey]]
        ]);
        echo "</div>";

        if (isset($this->fields["state"])) {
            echo "<div class='fa-label'>
            <i class='fas fa-tasks fa-fw'
               title='" . __('Status') . "'></i>";
            PluginReleasesDeploytask::dropdownStateTask("state", $this->fields["state"], true, ['rand' => $rand_state]);
            echo "</div>";
        }

        if ($this->maybePrivate()) {
            echo "<div class='fa-label'>
            <i class='fas fa-lock fa-fw' title='" . __('Private') . "'></i>
            <span class='switch pager_controls'>
               <label for='is_privateswitch$rand_is_private' title='" . __('Private') . "'>";
            echo Html::hidden('is_private', ['value' => 0]);
            echo "<input type='checkbox' id='is_privateswitch$rand_is_private' name='is_private' value='1'" .
                ($this->fields["is_private"]
                    ? "checked='checked'"
                    : "") . "
                  >
                  <span class='lever'></span>
               </label>
            </span>
         </div>";
        }

        echo "<div class='fa-label'>
         <i class='fas fa-stopwatch fa-fw'
            title='" . __('Duration') . "'></i>";

        $toadd = [];
        for ($i = 9; $i <= 100; $i++) {
            $toadd[] = $i * HOUR_TIMESTAMP;
        }

        Dropdown::showTimeStamp("actiontime", [
            'min' => 0,
            'max' => 8 * HOUR_TIMESTAMP,
            'value' => $this->fields["actiontime"],
            'rand' => $rand_time,
            'addfirstminutes' => true,
            'inhours' => true,
            'toadd' => $toadd,
            'width' => ''
        ]);

        echo "</div>";

        echo "<div class='fa-label'>";
        echo "<i class='fas fa-user fa-fw' title='" . _n('User', 'Users', 1) . "'></i>";
        $params = [
            'name' => "users_id_tech",
            'value' => (($ID > -1)
                ? $this->fields["users_id_tech"]
                : Session::getLoginUserID()),
            'right' => "own_ticket",
            'rand' => $rand_user,
            //         'entity' => $item->fields["entities_id"],
            'width' => ''
        ];

        $params['toupdate'] = [
            'value_fieldname'
            => 'users_id',
            'to_update' => "user_available$rand_user",
            'url' => $CFG_GLPI["root_doc"] . "/ajax/planningcheck.php"
        ];
        User::dropdown($params);

//      echo " <a href='#' title=\"" . __s('Availability') . "\" onClick=\"" . Html::jsGetElementbyID('planningcheck' . $rand) . ".dialog('open'); return false;\">";
        $rand = mt_rand();
        echo "<a href='#' title=\"" . __s(
                'Availability'
            ) . "\" data-bs-toggle='modal' data-bs-target='#planningcheck$rand'>";
        echo "<i class='far fa-calendar-alt'></i>";
        echo "<span class='sr-only'>" . __('Availability') . "</span>";
        echo "</a>";
        Ajax::createIframeModalWindow(
            'planningcheck' . $rand,
            $CFG_GLPI["root_doc"] .
            "/front/planning.php?checkavailability=checkavailability" .
            "&itemtype=User&users_id=" . $this->fields["users_id_tech"],
            ['title' => __('Availability')]
        );
//
//      echo "<i class='far fa-calendar-alt'></i>";
//      echo "<span class='sr-only'>" . __('Availability') . "</span>";
//      echo "</a>";
        //      Ajax::createIframeModalWindow('planningcheck'.$rand,
        //         $CFG_GLPI["root_doc"].
        //         "/front/planning.php?checkavailability=checkavailability".
        //         "&itemtype=".$item->getType()."&$fkfield=".$item->getID(),
        //         ['title'  => __('Availability')]);
        echo "</div>";

        echo "<div class='fa-label'>";
        echo "<i class='fas fa-users fa-fw' title='" . _n('Group', 'Groups', 1) . "'></i>";
        $params = [
            'name' => "groups_id_tech",
            'value' => (($ID > -1)
                ? $this->fields["groups_id_tech"]
                : Dropdown::EMPTY_VALUE),
            'condition' => ['is_task' => 1],
            'rand' => $rand_group,
            //         'entity'    => $item->fields["entities_id"]      ];
        ];

        $params['toupdate'] = [
            'value_fieldname' => 'users_id',
            'to_update' => "group_available$rand_group",
            'url' => $CFG_GLPI["root_doc"] . "/ajax/planningcheck.php"
        ];
        Group::dropdown($params);
        echo "</div>";


        echo "</td></tr>";


        $this->showFormButtons($options);

        return true;
    }

    /**
     * @param \CommonDBTM $item
     *
     * @return int
     */
    static function countForItem(CommonDBTM $item)
    {
        $dbu = new DbUtils();
        $table = CommonDBTM::getTable(self::class);
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releasetemplates_id" => $item->getID()]
        );
    }

    /**
     *
     * @return css class
     */
    static function getCssClass()
    {
        return "task";
    }

    /**
     * Prepare input datas for adding the item
     *
     * @param array $input datas used to add the item
     *
     * @return array the modified $input array
     **/
    function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);

        if (empty($input["plugin_releases_releasetemplates_id"])) {
            $input["plugin_releases_releasetemplates_id"] = 0;
        }

        if ($input["plugin_releases_deploytasktemplates_id"] != 0) {
            $task = new self();
            $task->getFromDB($input["plugin_releases_deploytasktemplates_id"]);
            $input["level"] = $task->getField("level") + 1;
        }


        return $input;
    }

    /**
     * Prepare input datas for updating the item
     *
     * @param array $input data used to update the item
     *
     * @return array the modified $input array
     **/
    function prepareInputForUpdate($input)
    {
        Toolbox::manageBeginAndEndPlanDates($input['plan']);

        if (isset($input["plugin_releases_deploytasktemplates_id"]) && $input["plugin_releases_deploytasktemplates_id"] != 0) {
            $task = new self();
            $task->getFromDB($input["plugin_releases_deploytasktemplates_id"]);
            $input["level"] = $task->getField("level") + 1;
        }

        // update last editor if content change
        if (isset($input['update'])
            && ($uid = Session::getLoginUserID())) { // Change from task form
            $input["users_id_editor"] = $uid;
        }
        return $input;
    }

    function post_addItem()
    {
        $_SESSION['releases']["template"][Session::getLoginUserID()] = 'task';
    }

    function post_updateItem($history = 1)
    {
        parent::post_updateItem($history); // TODO: Change the autogenerated stub
        $task = new self();
        if (!isset($this->input['no_leveling'])) {
            if ($task->getFromDB($this->getField("plugin_releases_deploytasktemplates_id"))) {
                self::leveling_task($this->getID(), $task);
            } else {
                self::leveling_task($this->getID(), null);
            }
        }
    }

    public function post_deleteFromDB()
    {
        parent::post_deleteFromDB(); // TODO: Change the autogenerated stub
        $task = new self();
        $tasks = $task->find(["plugin_releases_deploytasktemplates_id" => $this->getID()]);
        foreach ($tasks as $t) {
            $input = [];
            $input['id'] = $t["id"];
            $input['plugin_releases_deploytasktemplates_id'] = $this->getField(
                'plugin_releases_deploytasktemplates_id'
            );
            $input['_disablenotif'] = true;
            $task->update($input);
        }
    }

    /**
     * @param $ID
     * @param $entity
     *
     * @return ID|int|the
     */
    static function transfer($ID, $entity)
    {
        global $DB;

        if ($ID > 0) {
            $self = new self();
            $items = $self->find(["plugin_releases_releasetemplates_id" => $ID]);
            foreach ($items as $id => $vals) {
                $input = [];
                $input["id"] = $id;
                $input["entities_id"] = $entity;
                $self->update($input);
            }
            return true;
        }
        return 0;
    }

    static function leveling_task($id, $previous_task)
    {
        $task = new PluginReleasesDeploytask();
        $input = [];
        $input['id'] = $id;
        $input['_disablenotif'] = true;
        $input['no_leveling'] = true;
        if ($previous_task != null) {
            $input["level"] = $previous_task->getField('level') + 1;
        } else {
            $input["level"] = 0;
        }


        $task->update($input);
        $tasks = $task->find(["plugin_releases_deploytasktemplates_id" => $id]);
        $task->getFromDB($id);
        foreach ($tasks as $t) {
            self::leveling_task($t['id'], $task);
        }
    }

    static function getAllDescendant($id)
    {
        $childrens = [];
        $task = new PluginReleasesDeploytasktemplate();
        $tasks = $task->find(["plugin_releases_deploytasktemplates_id" => $id]);
        $childrens[] = $id;
        foreach ($tasks as $t) {
            $childs = self::getAllDescendant($t['id']);
            $childrens = array_merge($childrens, $childs);
        }
        return $childrens;
    }
}
