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
use Glpi\Application\View\TemplateRenderer;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Template for test
 * @since 9.1
 **/
class Testtemplate extends CommonDropdown
{
    // From CommonDBTM
    public $dohistory = true;
    public $can_be_translated = true;

    public static $rightname = 'plugin_releases_tests';

    public static function getTypeName($nb = 0)
    {
        return _n('Test template', 'Test templates', $nb, 'releases');
    }

    public function getAdditionalFields()
    {
        return [
            [
                'name' => 'plugin_releases_typetests_id',
                'label' => _n('Test type', 'Test types', 1, 'releases'),
                'type' => 'dropdownTests',
            ],
            [
                'name' => 'plugin_releases_risks_id',
                'label' => _n('Risk', 'Risks', 1, 'releases'),
                'type' => 'dropdownRisks',
            ],
            [
                'name' => 'content',
                'label' => __('Description'),
                'type' => 'textarea',
                'rows' => 10,
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
            'name' => __('Deploy Task type'),
            'field' => 'name',
            'table' => getTableForItemType(TypeDeployTask::class),
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
            case 'dropdownTests':
                TypeTest::dropdown(["name" => "plugin_releases_typetests_id"]);
                break;
            case 'dropdownRisks':
                Risktemplate::dropdown(["name" => "plugin_releases_risks_id"]);
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

    /**
     * @param       $ID
     * @param array $options
     *
     * @return bool|void
     */
    public function showForm($ID, $options = [])
    {
        $this->initForm($ID, $options);

        $typetests_id = $_GET["typetestid"] ?? $this->fields["plugin_releases_typetests_id"];

        TemplateRenderer::getInstance()->display('@releases/form_testtemplate.html.twig', [
            'item'         => $this,
            'typetests_id' => $typetests_id,
            'params'       => $options,
        ]);
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
        return "test";
    }

    public function prepareInputForAdd($input)
    {

        if (empty($input["plugin_releases_releasetemplates_id"])) {
            $input["plugin_releases_releasetemplates_id"] = 0;
        }
        return $input;
    }

    public function post_addItem()
    {
        $_SESSION['releases']["template"][Session::getLoginUserID()] = 'test';
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
}
