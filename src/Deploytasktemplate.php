<?php

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

namespace GlpiPlugin\Releases;

use CommonDBTM;
use CommonDropdown;
use DbUtils;
use Dropdown;
use Glpi\Application\View\TemplateRenderer;
use Group;
use Session;
use Toolbox;
use User;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Template for task
 * @since 9.1
 **/
class Deploytasktemplate extends CommonDropdown
{
    // From CommonDBTM
    public $dohistory = true;
    public $can_be_translated = true;

    public static $rightname = 'plugin_releases_tasks';

    public static function getTypeName($nb = 0)
    {
        return _n('Deploy Task template', 'Deploy Task templates', $nb, 'releases');
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name' => 'content',
                'label' => __('Content'),
                'type' => 'textarea',
                'rows' => 10,
            ],

            [
                'name' => 'plugin_releases_typedeploytasks_id',
                'label' => __('Deploy Task type', 'releases'),
                'type' => 'dropdownValue',
                'list' => true,
            ],
            [
                'name' => 'state',
                'label' => __('Status'),
                'type' => 'state',
            ],
            [
                'name' => 'is_private',
                'label' => __('Private'),
                'type' => 'bool',
            ],
            [
                'name' => 'actiontime',
                'label' => __('Duration'),
                'type' => 'actiontime',
            ],
            [
                'name' => 'users_id_tech',
                'label' => __('By'),
                'type' => 'users_id_tech',
            ],
            [
                'name' => 'groups_id_tech',
                'label' => __('Group'),
                'type' => 'groups_id_tech',
            ],
        ];
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id' => '4',
            'name' => __('Content'),
            'field' => 'content',
            'table' => $this->getTable(),
            'datatype' => 'text',
            'htmltext' => true,
        ];

        $tab[] = [
            'id' => '3',
            'name' => TypeDeployTask::getTypeName(),
            'field' => 'name',
            'table' => getTableForItemType(TypeDeployTask::class),
            'datatype' => 'dropdown',
        ];

        $tab[] = [
            'id' => '5',
            'name' => ReleaseTemplate::getTypeName(),
            'field' => 'name',
            'table' => getTableForItemType(ReleaseTemplate::class),
            'datatype' => 'dropdown',
        ];

        return $tab;
    }

    /**
     * @see CommonDropdown::displaySpecificTypeField()
     **/
    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        switch ($field['type']) {
            case 'state':
                Deploytask::dropdownStateTask("state", $this->fields["state"]);
                break;
            case 'users_id_tech':
                User::dropdown([
                    'name' => "users_id_tech",
                    'right' => "own_ticket",
                    'value' => $this->fields["users_id_tech"],
                    'entity' => $this->fields["entities_id"],
                ]);
                break;
            case 'groups_id_tech':
                Group::dropdown([
                    'name' => "groups_id_tech",
                    'condition' => ['is_task' => 1],
                    'value' => $this->fields["groups_id_tech"],
                    'entity' => $this->fields["entities_id"],
                ]);
                break;
            case 'actiontime':
                $toadd = [];
                for ($i = 9; $i <= 100; $i++) {
                    $toadd[] = $i * HOUR_TIMESTAMP;
                }
                Dropdown::showTimeStamp(
                    "actiontime",
                    [
                        'min' => 0,
                        'max' => 8 * HOUR_TIMESTAMP,
                        'value' => $this->fields["actiontime"],
                        'addfirstminutes' => true,
                        'inhours' => true,
                        'toadd' => $toadd,
                    ],
                );
                break;
        }
    }

    public static function canCreate(): bool
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
    public static function canView(): bool
    {
        return Session::haveRight(static::$rightname, READ);
    }

    public function showForm($ID, $options = [])
    {
        $this->initForm($ID, $options);

        // Prevent null field due to getFromDB
        if (is_null($this->fields['begin'])) {
            $this->fields['begin'] = "";
        }

        $foreignKey = ReleaseTemplate::getForeignKeyField();
        $id_release = $options['plugin_releases_releasetemplates_id']
            ?? $this->fields["plugin_releases_releasetemplates_id"];

        // "Previous task" must not point to the item itself nor to any of its
        // descendants (that would create a cycle in the task tree). The exact
        // exclusion set depends on whether the item already exists.
        if ($ID != -1 && $ID != 0) {
            $forbidden_id = self::getAllDescendant($this->getID());
        } else {
            $forbidden_id = [$this->getID()];
        }

        $toadd = [];
        for ($i = 9; $i <= 100; $i++) {
            $toadd[] = $i * HOUR_TIMESTAMP;
        }

        TemplateRenderer::getInstance()->display('@releases/form_deploytasktemplate.html.twig', [
            'item'                => $this,
            'params'              => $options,
            'releasetemplates_id' => $id_release,
            'previous_condition'  => [
                "plugin_releases_releasetemplates_id" => $id_release,
                "NOT"                                 => ["id" => $forbidden_id],
            ],
            'risk_condition'      => [
                "plugin_releases_releasetemplates_id" => $this->fields[$foreignKey],
            ],
            'has_state'           => isset($this->fields["state"]),
            'state_values'        => [
                Deploytask::TODO => __('To do'),
                Deploytask::DONE => __('Done'),
                Deploytask::FAIL => __('Failed', 'releases'),
            ],
            'actiontime_max'      => 8 * HOUR_TIMESTAMP,
            'actiontime_toadd'    => $toadd,
            'users_id_tech'       => ($ID > -1)
                ? $this->fields["users_id_tech"]
                : Session::getLoginUserID(),
        ]);

        return true;
    }

    /**
     * @param \CommonDBTM $item
     *
     * @return int
     */
    public static function countForItem(CommonDBTM $item)
    {
        $dbu = new DbUtils();
        $table = CommonDBTM::getTable(self::class);
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releasetemplates_id" => $item->getID()],
        );
    }

    /**
     *
     * @return css class
     */
    public static function getCssClass()
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
    public function prepareInputForAdd($input)
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
    public function prepareInputForUpdate($input)
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

    public function post_addItem()
    {
        $_SESSION['releases']["template"][Session::getLoginUserID()] = 'task';
    }

    public function post_updateItem($history = 1)
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
                'plugin_releases_deploytasktemplates_id',
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
    public static function transfer($ID, $entity)
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

    public static function leveling_task($id, $previous_task)
    {
        $task = new Deploytask();
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

    public static function getAllDescendant($id)
    {
        $childrens = [];
        $task = new Deploytasktemplate();
        $tasks = $task->find(["plugin_releases_deploytasktemplates_id" => $id]);
        $childrens[] = $id;
        foreach ($tasks as $t) {
            $childs = self::getAllDescendant($t['id']);
            $childrens = array_merge($childrens, $childs);
        }
        return $childrens;
    }
}
