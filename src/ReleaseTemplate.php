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
use CommonGLPI;
use CommonITILActor;
use CommonITILObject;
use DbUtils;
use Document;
use Document_Item;
use Dropdown;
use Entity;
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use Group;
use Html;
use MassiveAction;
use Session;
use Supplier;
use Toolbox;
use User;

/**
 * Template for Release
 * @since 9.1
 **/
class ReleaseTemplate extends CommonDropdown
{
    // From CommonDBTM
    public $dohistory         = true;
    public $can_be_translated = true;
    public $userlinkclass     = ReleaseTemplate_User::class; //todo chnage after table create for template
    public $grouplinkclass    = Group_ReleaseTemplate::class;//todo chnage after table create for template
    public $supplierlinkclass = ReleaseTemplate_Supplier::class;//todo chnage after table create for template

    public static $rightname = 'plugin_releases_releases';

    // Propriétés attendues par les plugins qui itèrent sur les CommonITILObject
    // (ex. metademands) — Release n'utilise pas de champs ITIL template
    public array $mandatory  = [];
    public array $predefined = [];
    public array $hidden     = [];

    /// Use user entity to select entity of the object
    protected $userentity_oncreate = false;
    protected $users               = [];
    /// Groups by type
    protected $groups = [];
    /// Suppliers by type
    protected $suppliers = [];

    public static function getTypeName($nb = 0)
    {
        return _n('Release template', 'Release templates', $nb, 'releases');
    }

    /**
     * Retourne la liste des champs autorisés pour ce type de template.
     * Release n'utilise pas de restrictions de champs ITIL — retourne un tableau vide
     * pour la compatibilité avec les plugins qui appellent cette méthode sur tout CommonITILObject.
     */
    public static function getAllowedFields(bool $withtypeandcategory = false, bool $withitemtype = false): array
    {
        return [];
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (static::canView()) {
            switch ($item->getType()) {
                case __CLASS__:
                    $timeline    = $item->getTimelineItems();
                    $nb_elements = count($timeline);
                    //               $nb_elements = 0;

                    $ong = [
                        1 => __("Processing release", 'releases') . " <span class='badge'>$nb_elements</span>",
                    ];

                    return $ong;

            }
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case __CLASS__:
                switch ($tabnum) {
                    case 1:
                        $rand = mt_rand();
                        if (!$withtemplate) {
                            $item->showTimelineForm($rand);
                        }
                        $item->showTimeline($rand);

                        break;

                }
                break;

        }
        return true;
    }

    public function defineTabs($options = [])
    {

        $ong = [];
        $this->addStandardTab(self::getType(), $ong, $options);
        $this->addDefaultFormTab($ong);
        //      $this->defineDefaultObjectTabs($ong, $options);
        $this->addStandardTab(ReleaseTemplate_Item::class, $ong, $options);
        $this->addStandardTab('Document_Item', $ong, $options); // todo hide in template
        $this->addStandardTab('KnowbaseItem_Item', $ong, $options);

        $this->addStandardTab('Notepad', $ong, $options);
        $this->addStandardTab('Log', $ong, $options);
        return $ong;
    }

    public function getAdditionalFields()
    {

        return [
            ['name'  => 'content',
                'label' => __('Description', 'releases'),
                'type'  => 'textarea',
                'rows'  => 10],
            ['name'  => 'date_preproduction',
                'label' => __('Pre-production run date', 'releases'),
                'type'  => 'date',
            ],
            ['name'  => 'date_production',
                'label' => __('Production run date', 'releases'),
                'type'  => 'date',
            ],
            ['name'  => 'service_shutdown',
                'label' => __('Service shutdown', 'releases'),
                'type'  => 'bool',
            ],
            ['name'  => 'service_shutdown_details',
                'label' => __('Service shutdown details', 'releases'),
                'type'  => 'textarea',
                'rows'  => 10],
            ['name'  => 'hour_type',
                'label' => __('Non-working hours', 'releases'),
                'type'  => 'bool',
            ],
            ['name'  => 'tests',
                'label' => _n('Test', 'Tests', 2, 'releases'),
                'type'  => 'dropdownTests',
            ],
            ['name'  => 'rollbacks',
                'label' => _n('Rollback', 'Rollbacks', 2, 'releases'),
                'type'  => 'dropdownRollbacks',
            ],
            ['name'  => 'tasks',
                'label' => _n('Deploy task', 'Deploy tasks', 2, 'releases'),
                'type'  => 'dropdownTasks',
            ],
        ];
    }

    public function rawSearchOptions()
    {
        $tab = parent::rawSearchOptions();

        $tab[] = [
            'id'       => '4',
            'name'     => __('Content'),
            'field'    => 'content',
            'table'    => $this->getTable(),
            'datatype' => 'text',
            'htmltext' => true,
        ];

        $tab[] = [
            'id'       => '3',
            'name'     => __('Deploy Task type', 'releases'),
            'field'    => 'name',
            'table'    => getTableForItemType(TypeDeployTask::class),
            'datatype' => 'dropdown',
        ];

        return $tab;
    }

    public static function getItemsTable()
    {
        return 'glpi_plugin_releases_releases_items';
    }

    /**
     * @see CommonDropdown::displaySpecificTypeField()
     **/
    public function displaySpecificTypeField($ID, $field = [], array $options = [])
    {
        $dbu = new DbUtils();

        switch ($field['type']) {
            case 'dropdownRollbacks':
                $item      = new Rollbacktemplate();
                $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
                $rolltemp  = new Rollbacktemplate();
                $alltemps  = $rolltemp->find($condition);
                $rolls     = [];
                foreach ($alltemps as $roll) {
                    $rolls[$roll["id"]] = $roll["name"];
                }

                $val = $this->getField("rollbacks");
                $val = json_decode($val);
                if ($val == "") {
                    $val = [];
                }
                Dropdown::showFromArray("rollbacks", $rolls, ['id' => 'rollbacks', 'multiple' => true, 'values' => $val, "display" => true]);

                break;
            case 'dropdownTests':
                $item      = new Testtemplate();
                $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
                $testtemp  = new Testtemplate();
                $alltemps  = $testtemp->find($condition);
                $tests     = [];
                foreach ($alltemps as $test) {
                    $tests[$test["id"]] = $test["name"];
                }

                $val = $this->getField("tests");
                $val = json_decode($val);
                if ($val == "") {
                    $val = [];
                }
                Dropdown::showFromArray("tests", $tests, ['id' => 'tests', 'multiple' => true, 'values' => $val, "display" => true]);
                break;
            case 'dropdownTasks':
                $item      = new Deploytasktemplate();
                $condition = $dbu->getEntitiesRestrictCriteria($item->getTable());
                $tasktemp  = new Deploytasktemplate();
                $alltemps  = $tasktemp->find($condition);
                $tasks     = [];
                foreach ($alltemps as $task) {
                    $tasks[$task["id"]] = $task["name"];
                }

                $val = $this->getField("tasks");
                $val = json_decode($val);
                if ($val == "") {
                    $val = [];
                }
                Dropdown::showFromArray("tasks", $tasks, ['id' => 'tasks', 'multiple' => true, 'values' => $val, "display" => true]);
                break;
            case 'actiontime':
                $toadd = [];
                for ($i = 9; $i <= 100; $i++) {
                    $toadd[] = $i * HOUR_TIMESTAMP;
                }
                Dropdown::showTimeStamp(
                    "actiontime",
                    [
                        'min'             => 0,
                        'max'             => 8 * HOUR_TIMESTAMP,
                        'value'           => $this->fields["actiontime"],
                        'addfirstminutes' => true,
                        'inhours'         => true,
                        'toadd'           => $toadd,
                    ],
                );
                break;
        }
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

    public function prepareInputForAdd($input)
    {
        $input = parent::prepareInputForAdd($input);
        //      $input = parent::prepareInputForUpdate($input);

        // Same domain as a release: the value is copied into the release fields when a
        // release is created from this template (Release::showForm()).
        if (!Release::checkCommunicationTypeInput($input)) {
            return false;
        }

        if (!Release::checkLocationInput($input)) {
            return false;
        }
        if ((isset($input['target']) && empty($input['target'])) || !isset($input['target'])) {
            $input['target'] = [];
        }
        // Same sink check as Release::prepareInputForAdd(): this column holds the same
        // actor ids, picked from the same entity-restricted dropdown, and is copied into
        // the release created from the template. Filtering on Release alone left this
        // class as the one way to persist a target the session was never offered.
        $input['target'] = Release::filterAllowedTargets(
            $input['target'],
            $input['communication_type'] ?? '',
        );
        $input['target'] = json_encode($input['target']);
        if (!isset($input['_auto_import'])) {
            if (!isset($input["_users_id_requester"])) {
                if ($uid = Session::getLoginUserID()) {
                    $input["_users_id_requester"] = $uid;
                }
            }
        }
        if (($uid = Session::getLoginUserID())
            && !isset($input['_auto_import'])) {
            $input["users_id_recipient"] = $uid;
        } elseif (isset($input["_users_id_requester"]) && $input["_users_id_requester"]
                   && !isset($input["users_id_recipient"])) {
            if (!is_array($input['_users_id_requester'])) {
                $input["users_id_recipient"] = $input["_users_id_requester"];
            }
        }
        return $input;
    }

    public function prepareInputForUpdate($input)
    {
        $input = parent::prepareInputForUpdate($input);
        //      $input = parent::prepareInputForUpdate($input);

        // Release::prepareInputForUpdate() simply drops this key, a template cannot: the
        // massive "transfer" action moves one between entities on purpose
        // (processMassiveActionsForOneItemtype() validates the destination entity and the
        // row right, then calls update() with entities_id). Revalidate the value instead —
        // check($id, UPDATE) covers the entity the row sits in today, never the one a
        // forged POST asks to move it to. The transfer action has already passed this very
        // check, so it is unaffected; the form exposes no entity field after creation.
        if (isset($input['entities_id'])
            && !Session::haveAccessToEntity((int) $input['entities_id'])) {
            Session::addMessageAfterRedirect(
                __('The action you have requested is not allowed.'),
                false,
                ERROR,
            );
            return false;
        }

        if (!Release::checkCommunicationTypeInput($input)) {
            return false;
        }

        if (!Release::checkLocationInput($input)) {
            return false;
        }
        if ((isset($input['target']) && empty($input['target'])) || (!isset($input['target']) && isset($input["communication_type"]) && $input["communication_type"] != $this->fields["communication_type"])) {
            $input['target'] = [];
        }
        if (isset($input['target'])) {
            // Same sink check as on creation and as Release::prepareInputForUpdate(). The
            // communication type may be absent from the payload, so fall back on the one
            // already stored for this template. Filtering inside the former
            // "communication_type is posted" branch would have left the bypass open: a
            // target posted on its own never reached the encoding at all and was written
            // to the column as a raw array.
            $input['target'] = Release::filterAllowedTargets(
                $input['target'],
                $input["communication_type"] ?? ($this->fields["communication_type"] ?? ''),
            );
            $input['target'] = json_encode($input['target']);
        } elseif (isset($input["communication_type"])) {
            $input['target'] = json_encode([]);
        }

        $release_user     = new ReleaseTemplate_User();
        $release_supplier = new ReleaseTemplate_Supplier();
        $group_release    = new Group_ReleaseTemplate();

        $release_user->deleteByCriteria(["plugin_releases_releasetemplates_id" => $this->getID()]);
        $release_supplier->deleteByCriteria(["plugin_releases_releasetemplates_id" => $this->getID()]);
        $group_release->deleteByCriteria(["plugin_releases_releasetemplates_id" => $this->getID()]);
        $useractors = null;
        // Add user groups linked to ITIL objects
        if (!empty($this->userlinkclass)) {
            $useractors = new $this->userlinkclass();
        }
        $groupactors = null;
        if (!empty($this->grouplinkclass)) {
            $groupactors = new $this->grouplinkclass();
        }
        $supplieractors = null;
        if (!empty($this->supplierlinkclass)) {
            $supplieractors = new $this->supplierlinkclass();
        }

        // "do not compute" flag set by business rules for "takeintoaccount_delay_stat" field
        $do_not_compute_takeintoaccount = $this->isTakeIntoAccountComputationBlocked($this->input);

        if (!is_null($useractors)) {
            $user_input = [
                $useractors->getItilObjectForeignKey() => $this->fields['id'],
                '_do_not_compute_takeintoaccount'      => $do_not_compute_takeintoaccount,
                '_from_object'                         => true,
            ];

            if (isset($this->input["_users_id_requester"])) {

                if (is_array($this->input["_users_id_requester"])) {
                    $tab_requester = $this->input["_users_id_requester"];
                } else {
                    $tab_requester   = [];
                    $tab_requester[] = $this->input["_users_id_requester"];
                }

                $requesterToAdd = [];
                foreach ($tab_requester as $key_requester => $requester) {
                    if (in_array($requester, $requesterToAdd)) {
                        // This requester ID is already added;
                        continue;
                    }

                    $input2 = [
                        'users_id' => $requester,
                        'type'     => CommonITILActor::REQUESTER,
                    ] + $user_input;

                    if (isset($this->input["_users_id_requester_notif"])) {
                        foreach ($this->input["_users_id_requester_notif"] as $key => $val) {
                            if (isset($val[$key_requester])) {
                                $input2[$key] = $val[$key_requester];
                            }
                        }
                    }

                    //empty actor
                    if ($input2['users_id'] == 0
                        && (!isset($input2['alternative_email'])
                            || empty($input2['alternative_email']))) {
                        continue;
                    } elseif ($requester != 0) {
                        $requesterToAdd[] = $requester;
                    }

                    $useractors->add($input2);
                }
            }

            if (isset($this->input["_users_id_observer"])) {

                if (is_array($this->input["_users_id_observer"])) {
                    $tab_observer = $this->input["_users_id_observer"];
                } else {
                    $tab_observer   = [];
                    $tab_observer[] = $this->input["_users_id_observer"];
                }

                $observerToAdd = [];
                foreach ($tab_observer as $key_observer => $observer) {
                    if (in_array($observer, $observerToAdd)) {
                        // This observer ID is already added;
                        continue;
                    }

                    $input2 = [
                        'users_id' => $observer,
                        'type'     => CommonITILActor::OBSERVER,
                    ] + $user_input;

                    if (isset($this->input["_users_id_observer_notif"])) {
                        foreach ($this->input["_users_id_observer_notif"] as $key => $val) {
                            if (isset($val[$key_observer])) {
                                $input2[$key] = $val[$key_observer];
                            }
                        }
                    }

                    //empty actor
                    if ($input2['users_id'] == 0
                        && (!isset($input2['alternative_email'])
                            || empty($input2['alternative_email']))) {
                        continue;
                    } elseif ($observer != 0) {
                        $observerToAdd[] = $observer;
                    }

                    $useractors->add($input2);
                }
            }

            if (isset($this->input["_users_id_assign"])) {

                if (is_array($this->input["_users_id_assign"])) {
                    $tab_assign = $this->input["_users_id_assign"];
                } else {
                    $tab_assign   = [];
                    $tab_assign[] = $this->input["_users_id_assign"];
                }

                $assignToAdd = [];
                foreach ($tab_assign as $key_assign => $assign) {
                    if (in_array($assign, $assignToAdd)) {
                        // This assigned user ID is already added;
                        continue;
                    }

                    $input2 = [
                        'users_id' => $assign,
                        'type'     => CommonITILActor::ASSIGN,
                    ] + $user_input;

                    if (isset($this->input["_users_id_assign_notif"])) {
                        foreach ($this->input["_users_id_assign_notif"] as $key => $val) {
                            if (isset($val[$key_assign])) {
                                $input2[$key] = $val[$key_assign];
                            }
                        }
                    }

                    //empty actor
                    if ($input2['users_id'] == 0
                        && (!isset($input2['alternative_email'])
                            || empty($input2['alternative_email']))) {
                        continue;
                    } elseif ($assign != 0) {
                        $assignToAdd[] = $assign;
                    }

                    $useractors->add($input2);
                }
            }
        }

        if (!is_null($groupactors)) {
            $group_input = [
                $groupactors->getItilObjectForeignKey() => $this->fields['id'],
                '_do_not_compute_takeintoaccount'       => $do_not_compute_takeintoaccount,
                '_from_object'                          => true,
            ];

            if (isset($this->input["_groups_id_requester"])) {
                $groups_id_requester = $this->input["_groups_id_requester"];
                if (!is_array($this->input["_groups_id_requester"])) {
                    $groups_id_requester = [$this->input["_groups_id_requester"]];
                } else {
                    $groups_id_requester = $this->input["_groups_id_requester"];
                }
                foreach ($groups_id_requester as $groups_id) {
                    if ($groups_id > 0) {
                        $groupactors->add(
                            [
                                'groups_id' => $groups_id,
                                'type'      => CommonITILActor::REQUESTER,
                            ] + $group_input,
                        );
                    }
                }
            }

            if (isset($this->input["_groups_id_assign"])) {
                if (!is_array($this->input["_groups_id_assign"])) {
                    $groups_id_assign = [$this->input["_groups_id_assign"]];
                } else {
                    $groups_id_assign = $this->input["_groups_id_assign"];
                }
                foreach ($groups_id_assign as $groups_id) {
                    if ($groups_id > 0) {
                        $groupactors->add(
                            [
                                'groups_id' => $groups_id,
                                'type'      => CommonITILActor::ASSIGN,
                            ] + $group_input,
                        );
                    }
                }
            }

            if (isset($this->input["_groups_id_observer"])) {
                if (!is_array($this->input["_groups_id_observer"])) {
                    $groups_id_observer = [$this->input["_groups_id_observer"]];
                } else {
                    $groups_id_observer = $this->input["_groups_id_observer"];
                }
                foreach ($groups_id_observer as $groups_id) {
                    if ($groups_id > 0) {
                        $groupactors->add(
                            [
                                'groups_id' => $groups_id,
                                'type'      => CommonITILActor::OBSERVER,
                            ] + $group_input,
                        );
                    }
                }
            }
        }

        if (!is_null($supplieractors)) {
            $supplier_input = [
                $supplieractors->getItilObjectForeignKey() => $this->fields['id'],
                '_do_not_compute_takeintoaccount'          => $do_not_compute_takeintoaccount,
                '_from_object'                             => true,
            ];

            if (isset($this->input["_suppliers_id_assign"])
                && ($this->input["_suppliers_id_assign"] > 0)) {

                if (is_array($this->input["_suppliers_id_assign"])) {
                    $tab_assign = $this->input["_suppliers_id_assign"];
                } else {
                    $tab_assign   = [];
                    $tab_assign[] = $this->input["_suppliers_id_assign"];
                }

                $supplierToAdd = [];
                foreach ($tab_assign as $key_assign => $assign) {
                    if (in_array($assign, $supplierToAdd)) {
                        // This assigned supplier ID is already added;
                        continue;
                    }
                    $input3 = [
                        'suppliers_id' => $assign,
                        'type'         => CommonITILActor::ASSIGN,
                    ] + $supplier_input;

                    if (isset($this->input["_suppliers_id_assign_notif"])) {
                        foreach ($this->input["_suppliers_id_assign_notif"] as $key => $val) {
                            $input3[$key] = $val[$key_assign];
                        }
                    }

                    //empty supplier
                    if ($input3['suppliers_id'] == 0
                        && (!isset($input3['alternative_email'])
                            || empty($input3['alternative_email']))) {
                        continue;
                    } elseif ($assign != 0) {
                        $supplierToAdd[] = $assign;
                    }

                    $supplieractors->add($input3);
                }
            }
        }

        // Additional actors
        $this->addAdditionalActors($this->input);
        return $input;
    }

    public function showForm($ID, $options = [])
    {
        global $CFG_GLPI;

        if ($ID > 0) {
            $this->check($ID, READ);
        } else {
            // Create item
            $this->check(-1, CREATE, $options);
        }

        $this->initForm($ID, $options);
        $default_values = self::getDefaultValues();

        // Restore saved value or override with page parameter
        $saved                  = $this->restoreInput();
        $options['entities_id'] = Session::getActiveEntity();
        foreach ($default_values as $name => $value) {
            if (!isset($this->fields[$name])) {
                if (isset($saved[$name])) {
                    $this->fields[$name] = $saved[$name];
                    $options[$name]      = $saved[$name];
                } else {
                    $this->fields[$name] = $value;
                    $options[$name]      = $value;
                }
            }
        }

        // The actors widget (CommonITILActor dropdowns + hidden inputs consumed by
        // prepareInputForUpdate) is self-contained legacy markup: capture each of the
        // three blocs (requester/observer/assign) separately so the Twig template can
        // lay them out on three columns inside the single <form>. The requester bloc
        // also carries the entities_id hidden input.
        [$actor_options, $can_admin, $can_assign, $can_assigntome] = $this->prepareActorsData($ID, $options);

        $actors_requester_html = $this->getRequesterActorFormHtml($actor_options, $can_admin);
        $actors_observer_html  = $this->getObserverActorFormHtml($actor_options, $can_admin);
        $actors_assign_html    = $this->getAssignActorFormHtml($actor_options, $can_assign, $can_assigntome);

        $targets = json_decode($this->fields["target"] ?? '') ?: [];
        if (!is_array($targets)) {
            $targets = [];
        }

        TemplateRenderer::getInstance()->display('@releases/form_releasetemplate.html.twig', [
            'item'                  => $this,
            'params'                => $options,
            'actors_requester_html' => $actors_requester_html,
            'actors_observer_html'  => $actors_observer_html,
            'actors_assign_html'    => $actors_assign_html,
            'comm_types'            => ['Entity'   => 'Entity',
                'Group'    => 'Group',
                'Profile'  => 'Profile',
                'User'     => 'User',
                'Location' => 'Location'],
            'targets'               => array_values($targets),
            'targets_url'           => $CFG_GLPI['root_doc'] . "/plugins/releases/ajax/changeTarget.php",
        ]);

        return true;
    }

    public function displayMenu($ID, $options = [])
    {
        $dbu       = new DbUtils();
        $condition = $dbu->getEntitiesRestrictCriteria($this->getTable(), '', '', true);
        $template  = new ReleaseTemplate();
        $templates = $template->find($condition);

        $has_templates = count($templates) != 0;
        $dropdown_html = '';
        if ($has_templates) {
            // Capture the template dropdown (echoes internally) to embed it in Twig.
            // Name is template_id so a plain GET form submits it to release.form.php
            // (read by Release::showForm to prefill and set releasetemplates_id).
            ob_start();
            self::dropdown(["name" => "template_id"] + $condition);
            $dropdown_html = ob_get_clean();
        }

        // Plain GET form: the chosen template id always reaches release.form.php,
        // with no dependency on a client-side href rewrite.
        TemplateRenderer::getInstance()->display('@releases/form_releasetemplate_menu.html.twig', [
            'has_templates' => $has_templates,
            'dropdown_html' => $dropdown_html,
            'form_action'   => Release::getFormURL(),
        ]);
    }

    public function getTimelineItems()
    {

        $objType    = self::getType();
        $foreignKey = self::getForeignKeyField();
        //      $foreignKey =  "plugin_releases_releases_id";

        $timeline = [];

        $riskClass     = Risktemplate::class;
        $risk_obj      = new $riskClass();
        $rollbackClass = Rollbacktemplate::class;
        $rollback_obj  = new $rollbackClass();
        $taskClass     = Deploytasktemplate::class;
        $task_obj      = new $taskClass();
        $testClass     = Testtemplate::class;
        $test_obj      = new $testClass();

        //checks rights
        $restrict_risk = $restrict_rollback = $restrict_task = $restrict_test = [];
        //      $restrict_risk['itemtype'] = static::getType();
        //      $restrict_risk['items_id'] = $this->getID();
        $user = new User();

        //checks rights

        //add risks to timeline
        if ($risk_obj->canView()) {
            $risks = $risk_obj->find([$foreignKey => $this->getID()] + $restrict_risk, ['date_mod DESC', 'id DESC']);
            foreach ($risks as $risks_id => $risk) {
                $risk_obj->getFromDB($risks_id);
                // Rows created before the parent check existed may sit outside the
                // template's entity; find() has no entity criteria of its own.
                if (!$risk_obj->canViewItem()) {
                    continue;
                }
                $risk['can_edit']                                   = $risk_obj->canUpdateItem();
                $timeline[$risk['date_mod'] . "_risk_" . $risks_id] = ['type'     => $riskClass,
                    'item'     => $risk,
                    'itiltype' => 'Risk'];
            }
        }

        if ($rollback_obj->canView()) {
            $rollbacks = $rollback_obj->find([$foreignKey => $this->getID()] + $restrict_rollback, ['date_mod DESC', 'id DESC']);
            foreach ($rollbacks as $rollbacks_id => $rollback) {
                $rollback_obj->getFromDB($rollbacks_id);
                // Rows created before the parent check existed may sit outside the
                // template's entity; find() has no entity criteria of its own.
                if (!$rollback_obj->canViewItem()) {
                    continue;
                }
                $rollback['can_edit']                                       = $rollback_obj->canUpdateItem();
                $timeline[$rollback['date_mod'] . "_rollback_" . $rollbacks_id] = ['type'     => $rollbackClass,
                    'item'     => $rollback,
                    'itiltype' => 'Rollback'];
            }
        }

        if ($task_obj->canView()) {
            //         $tasks = $task_obj->find([$foreignKey => $this->getID()] + $restrict_task);
            $tasks = $task_obj->find([$foreignKey => $this->getID()] + $restrict_task, ['level ASC']);
            foreach ($tasks as $tasks_id => $task) {
                $task_obj->getFromDB($tasks_id);
                // Rows created before the parent check existed may sit outside the
                // template's entity; find() has no entity criteria of its own.
                if (!$task_obj->canViewItem()) {
                    continue;
                }
                $task['can_edit']                                                      = $task_obj->canUpdateItem();
                $rand                                                                  = mt_rand();
                $timeline["task" . $task_obj->getField('level') . "$tasks_id" . $rand] = ['type'     => $taskClass,
                    'item'     => $task,
                    'itiltype' => 'Deploytask'];
            }
        }

        if ($test_obj->canView()) {
            $tests = $test_obj->find([$foreignKey => $this->getID()] + $restrict_test, ['date_mod DESC', 'id DESC']);
            foreach ($tests as $tests_id => $test) {
                $test_obj->getFromDB($tests_id);
                // Rows created before the parent check existed may sit outside the
                // template's entity; find() has no entity criteria of its own.
                if (!$test_obj->canViewItem()) {
                    continue;
                }
                $test['can_edit']                                   = $test_obj->canUpdateItem();
                $timeline[$test['date_mod'] . "_test_" . $tests_id] = ['type'     => $testClass,
                    'item'     => $test,
                    'itiltype' => 'Test'];
            }
        }

        //reverse sort timeline items by key (date)
        //      ksort($timeline);

        return $timeline;
    }

    public function showTimelineForm($rand)
    {
        global $CFG_GLPI;

        $foreignKey = static::getForeignKeyField();

        //check sub-items rights
        $tmp      = [$foreignKey => $this->getID()];
        $risk     = new Risktemplate();
        $rollback = new Rollbacktemplate();
        $task     = new Deploytasktemplate();
        $test     = new Testtemplate();

        $closed = $this->getClosedStatusArray();
        $status = $this->fields["status"] ?? null;

        $canadd_risk     = $risk->can(-1, CREATE, $tmp) && !in_array($status, $closed);
        $canadd_rollback = $rollback->can(-1, CREATE, $tmp) && !in_array($status, $closed);
        $canadd_task     = $task->can(-1, CREATE, $tmp) && !in_array($status, $closed);
        $canadd_test     = $test->can(-1, CREATE, $tmp) && !in_array($status, $closed);

        if (!$canadd_risk && !$canadd_rollback && !$canadd_task && !$canadd_test && !$this->canReopen()) {
            return false;
        }

        // Each entry describes a subitem type: its filter link and (optionally) an add button.
        $types = [
            ['itemtype'  => Risktemplate::class,       'data_type' => 'risk',     'li_class' => 'Risk',       'icon' => 'ti-bug',           'canadd' => $canadd_risk],
            ['itemtype'  => Rollbacktemplate::class,   'data_type' => 'rollback', 'li_class' => 'Rollback',   'icon' => 'ti-arrow-back-up', 'canadd' => $canadd_rollback],
            ['itemtype'  => Deploytasktemplate::class, 'data_type' => 'task',     'li_class' => 'Deploytask', 'icon' => 'ti-checkbox',      'canadd' => $canadd_task],
            ['itemtype'  => Testtemplate::class,       'data_type' => 'Test',     'li_class' => 'Test',       'icon' => 'ti-check',         'canadd' => $canadd_test],
        ];
        foreach ($types as &$type) {
            $type['name']  = $type['itemtype']::getTypeName(2);
            $type['count'] = $type['itemtype']::countForItem($this);
        }
        unset($type);

        TemplateRenderer::getInstance()->display('@releases/timeline_form_releasetemplate.html.twig', [
            'types'       => $types,
            'parenttype'  => static::class,
            'foreign_key' => $foreignKey,
            'parent_id'   => $this->fields['id'],
            'ajax_box_id' => "viewitem" . $this->fields['id'] . $rand,
            'subitem_url' => $CFG_GLPI['root_doc'] . "/plugins/releases/ajax/viewsubitemtemplate.php",
        ]);

        return true;
    }

    public static function isAllowedStatus($old, $new)
    {
        if ($old != Release::CLOSED && $old != Release::REVIEW) {
            return true;
        }
        return false;
    }

    /**
     * is the current user could reopen the current change
     *
     * @return boolean
     * @since 9.4.0
     *
     */
    public function canReopen()
    {
        return Session::haveRight('plugin_releases_releases', CREATE)
               && in_array($this->fields["status"], $this->getClosedStatusArray());
    }

    /**
     * Get the ITIL object closed status list
     *
     * @return array
     **@since 0.83
     *
     */
    public static function getClosedStatusArray()
    {

        $tab = [Release::CLOSED, Release::REVIEW];
        return $tab;
    }

    /**
     * Get the ITIL object closed, solved or waiting status list
     *
     * @return array
     * @since 9.4.0
     *
     */
    public static function getReopenableStatusArray()
    {
        return [Release::CLOSED];
    }

    public static function countForItem($ID, $class, $state = 0)
    {
        $dbu   = new DbUtils();
        $table = CommonDBTM::getTable($class);
        if ($state) {
            return $dbu->countElementsInTable(
                $table,
                ["plugin_releases_releasetemplates_id" => $ID, "state" => 2],
            );
        }
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releasetemplates_id" => $ID],
        );
    }

    /**
     * @param CommonDBTM $item The item whose form should be shown
     * @param integer    $id ID of the item
     * @param mixed[]    $params Array of extra parameters
     *
     * @return void
     * @since 9.4.0
     *
     */
    public static function showSubForm(CommonDBTM $item, $id, $params)
    {

        if ($item instanceof Document_Item) {
            Document_Item::showAddFormForItem($params['parent'], '');

        } elseif (method_exists($item, "showForm")
                   && $item->can(-1, CREATE, $params)) {
            $item->showForm($id);
        }
    }

    /**
     * Displays the timeline of items for this ITILObject
     *
     * @param integer $rand random value used by div
     *
     * @return void
     * @since 9.4.0
     *
     */
    public function showTimeLine($rand)
    {
        global $CFG_GLPI;

        $user     = new User();
        $timeline = $this->getTimelineItems();
        $closed   = in_array($this->fields['status'] ?? null, $this->getClosedStatusArray());

        $entries = [];
        foreach ($timeline as $item) {
            $item_i = $item['item'];

            $date = "";
            if (isset($item_i['date'])) {
                $date = $item_i['date'];
            } elseif (isset($item_i['date_mod'])) {
                $date = $item_i['date_mod'];
            }

            $domid     = "viewitem{$item['type']}{$item_i['id']}";
            $randdomid = $domid . $rand;

            $entry = [
                'position'        => $item['itiltype'] == "Followup" ? 'right' : 'left',
                'class'           => $item['itiltype'] == "Followup"
                    ? "ITIL{$item['itiltype']}"
                    : "Releases{$item['itiltype']}",
                'date'            => Html::convDateTime($date),
                'show_user'       => ($item_i['users_id'] ?? null) !== false,
                'user_html'       => null,
                'domid'           => Toolbox::slugify($domid),
                'uid'             => $randdomid,
                'can_edit'        => !empty($item_i['can_edit']),
                'edit'            => null,
                'content'         => null,
                'long_text'       => false,
                'types'           => [],
                'associated_risk' => null,
                'actiontime'      => null,
                'begin'           => null,
                'end'             => null,
                'editor'          => null,
            ];

            if (!empty($item_i['users_id'])) {
                $user->getFromDB($item_i['users_id']);
                $userdata           = getUserName($item_i['users_id'], 2);
                $entry['user_html'] = $user->getLink() . "&nbsp;"
                    . Html::showToolTip($userdata["comment"], [
                        'link'    => $userdata['link'],
                        'display' => false,
                    ]);
            }

            if ($entry['can_edit'] && !$closed) {
                $entry['edit'] = [
                    'itemtype'   => $item['type'],
                    'items_id'   => $item_i['id'],
                    'parenttype' => static::class,
                    'fkey'       => static::getForeignKeyField(),
                    'parentid'   => $this->fields['id'],
                    'url'        => $CFG_GLPI['root_doc'] . "/plugins/releases/ajax/viewsubitemtemplate.php",
                ];
            }

            if (isset($item_i['content'])) {
                if (isset($item_i["name"])) {
                    // Same as Release.php: the sub-item name is stored raw, escape it rather
                    // than relying on the downstream purifier as the only rampart.
                    $content = RichText::getEnhancedHtml(
                        "<h2>" . htmlescape($item_i['name']) . "  </h2>" . $item_i['content'],
                    );
                } else {
                    $content = RichText::getEnhancedHtml($item_i['content']);
                }
                $entry['content']   = $content;
                $entry['long_text'] = (substr_count($content, "<br") > 30) || (strlen($content) > 2000);
            }

            $type_tables = [
                'plugin_releases_typedeploytasks_id' => 'glpi_plugin_releases_typedeploytasks',
                'plugin_releases_typerisks_id'       => 'glpi_plugin_releases_typerisks',
                'plugin_releases_typetests_id'       => 'glpi_plugin_releases_typetests',
            ];
            foreach ($type_tables as $field => $table) {
                if (!empty($item_i[$field])) {
                    $entry['types'][] = Dropdown::getDropdownName($table, $item_i[$field]);
                }
            }
            if (!empty($item_i['plugin_releases_risks_id'])) {
                $entry['associated_risk'] = Dropdown::getDropdownName(
                    "glpi_plugin_releases_risktemplates",
                    $item_i['plugin_releases_risks_id'],
                );
            }
            if (!empty($item_i['actiontime'])) {
                $entry['actiontime'] = Html::timestampToString($item_i['actiontime'], false);
            }
            if (isset($item_i['begin'])) {
                $entry['begin'] = Html::convDateTime($item_i["begin"]);
                $entry['end']   = Html::convDateTime($item_i["end"]);
            }

            if (isset($item_i['users_id_editor']) && $item_i['users_id_editor'] > 0) {
                $user->getFromDB($item_i['users_id_editor']);
                // Without the second argument getUserName() returns a plain string, so
                // the two accesses below were silently indexing a string.
                $userdata = getUserName($item_i['users_id_editor'], 2);
                $html     = '';
                if (isset($item_i['date_mod'])) {
                    $html = sprintf(
                        htmlescape(__('Last edited on %1$s by %2$s')),
                        htmlescape(Html::convDateTime($item_i['date_mod'])),
                        $user->getLink(),
                    );
                }
                $entry['editor'] = [
                    'id'   => (int) $item_i['users_id_editor'],
                    'html' => $html . Html::showToolTip($userdata["comment"], [
                        'link'    => $userdata['link'],
                        'display' => false,
                    ]),
                ];
            }

            $entries[] = $entry;
        }

        // Filter chip to highlight, set by the sub-item that was last saved
        // (Risktemplate, Rollbacktemplate, Deploytasktemplate, Testtemplate).
        $active_type = $_SESSION["releases"]["template"][Session::getLoginUserID()] ?? 'risk';
        unset($_SESSION["releases"]["template"][Session::getLoginUserID()]);

        TemplateRenderer::getInstance()->display('@releases/timeline_releasetemplate.html.twig', [
            'entries'     => $entries,
            'active_type' => $active_type,
        ]);
    }

    public function canAddFollowups()
    {
        return Session::haveRightsOr("plugin_releases_releases", [CREATE, UPDATE]);
    }

    public static function getDefaultValues($entity = 0)
    {
        global $CFG_GLPI;

        $users_id_requester = 0;
        $users_id_assign    = 0;
        $requesttype        = $CFG_GLPI['default_requesttypes_id'];

        $default_use_notif = Entity::getUsedConfig('is_notif_enable_default', $entity, '', 1);
        // Set default values...
        return ['_users_id_requester'        => $users_id_requester,
            '_users_id_requester_notif'  => ['use_notification'  => [$default_use_notif],
                'alternative_email' => ['']],
            '_groups_id_requester'       => 0,
            '_users_id_assign'           => $users_id_assign,
            '_users_id_assign_notif'     => ['use_notification'  => [$default_use_notif],
                'alternative_email' => ['']],
            '_groups_id_assign'          => 0,
            '_users_id_observer'         => 0,
            '_users_id_observer_notif'   => ['use_notification'  => [$default_use_notif],
                'alternative_email' => ['']],
            '_groups_id_observer'        => 0,
            '_link'                      => ['tickets_id_2' => '',
                'link'         => ''],
            '_suppliers_id_assign'       => 0,
            '_suppliers_id_assign_notif' => ['use_notification'  => [$default_use_notif],
                'alternative_email' => ['']],
            'name'                       => '',
            'content'                    => '',
            'date_preproduction'         => null,
            'date_production'            => null,
            'entities_id'                => $entity,
            'status'                     => Release::NEWRELEASE,
            'service_shutdown'           => false,
            'service_shutdown_details'   => '',
            'hour_type'                  => 0,
            'communication'              => false,
            'communication_type'         => false,
            'target'                     => "",
            'locations_id'               => 0,
        ];
    }

    /**
     * Prepare shared data (resolved actor values + rights) for the actor blocs.
     *
     * @param int   $ID
     * @param array $options
     *
     * @return array{0: array, 1: bool, 2: bool, 3: bool} [$options, $can_admin, $can_assign, $can_assigntome]
     */
    protected function prepareActorsData($ID, array $options)
    {
        $options['_default_use_notification'] = 0;

        if (isset($options['entities_id'])) {
            $options['_default_use_notification'] = Entity::getUsedConfig('is_notif_enable_default', $options['entities_id'], '', 1);
        }
        if ($ID) {
            $release_user     = new ReleaseTemplate_User();
            $release_supplier = new ReleaseTemplate_Supplier();
            $group_release    = new Group_ReleaseTemplate();
            $users            = $release_user->find(['plugin_releases_releasetemplates_id' => $ID]);
            $suppliers        = $release_supplier->find(['plugin_releases_releasetemplates_id' => $ID]);
            $groups           = $group_release->find(['plugin_releases_releasetemplates_id' => $ID]);
            foreach ($users as $user) {
                $options["_users_id_" . self::getActorFieldNameType($user["type"])] = $user["users_id"];
            }
            foreach ($suppliers as $supplier) {
                $options["_suppliers_id_" . self::getActorFieldNameType($supplier["type"])] = $supplier["suppliers_id"];
            }
            foreach ($groups as $group) {
                $options["_groups_id_" . self::getActorFieldNameType($group["type"])] = $group["groups_id"];
            }
        }

        // on creation can select actor
        $can_admin = true;

        $can_assign     = $this->canAssign();
        $can_assigntome = $this->canAssignToMe();

        if (isset($options['_noupdate']) && !$options['_noupdate']) {
            $can_admin      = false;
            $can_assign     = false;
            $can_assigntome = false;
        }

        return [$options, $can_admin, $can_assign, $can_assigntome];
    }

    /**
     * Render one actor bloc (requester/observer/assign column of the form).
     *
     * @param string $title Bloc header
     * @param array  $rows  Rows as built by the get*ActorRows() helpers
     *
     * @return string
     */
    private function renderActorBloc(string $title, array $rows): string
    {
        return TemplateRenderer::getInstance()->render('@releases/actor_bloc_releasetemplate.html.twig', [
            'title' => $title,
            'rows'  => $rows,
        ]);
    }

    /**
     * Read-only row for an actor value predefined by the template.
     *
     * @param string $user_group 'user', 'group' or 'supplier'
     * @param int    $type       CommonITILActor type
     * @param string $table      Table of the actor
     * @param string $name       Name of the hidden input
     * @param mixed  $value      Actor ID
     * @param string $after      Separator after the row ('hr' or 'br')
     *
     * @return array<string, mixed>
     */
    private static function getPredefinedActorRow(string $user_group, $type, string $table, string $name, $value, string $after): array
    {
        return [
            'icon'   => static::getActorIconData($user_group, $type),
            'label'  => Dropdown::getDropdownName($table, $value),
            'hidden' => ['name' => $name, 'value' => $value],
            'after'  => $after,
        ];
    }

    /**
     * Requester actor bloc (user + group). Also carries the entities_id hidden input.
     */
    protected function getRequesterActorFormHtml(array $options, $can_admin): string
    {
        $rows = [];

        // Requester
        $reqdisplay = false;
        if ($can_admin) {
            $rows[]     = $this->getActorAddFormOnCreateRow(CommonITILActor::REQUESTER, $options);
            $reqdisplay = true;
        } else {
            $delegating = User::getDelegateGroupsForUser($options['entities_id']);
            if (count($delegating)) {
                $options['_right'] = "delegate";
                $rows[]            = $this->getActorAddFormOnCreateRow(CommonITILActor::REQUESTER, $options);
                $reqdisplay        = true;
            } elseif (isset($options["_users_id_requester"]) && $options["_users_id_requester"]) {
                // predefined value
                $rows[]     = self::getPredefinedActorRow('user', CommonITILActor::REQUESTER, "glpi_users", '_users_id_requester', $options["_users_id_requester"], 'br');
                $reqdisplay = true;
            }
        }

        if ($this->userentity_oncreate
            && isset($this->countentitiesforuser)
            && ($this->countentitiesforuser > 1)) {
            $rows[] = [
                'widget' => '<br>' . Entity::dropdown([
                    'value'     => $this->fields["entities_id"],
                    'entity'    => $this->userentities,
                    'on_change' => 'this.form.submit()',
                    'display'   => false,
                ]),
            ];
        } else {
            $rows[] = ['hidden' => ['name' => 'entities_id', 'value' => $this->fields["entities_id"]]];
        }
        if ($reqdisplay) {
            $rows[] = ['after' => 'hr'];
        }

        // Requester Group
        if ($can_admin) {
            $rows[] = [
                'icon'   => static::getActorIconData('group', CommonITILActor::REQUESTER),
                'widget' => Group::dropdown([
                    'name'      => '_groups_id_requester',
                    'value'     => $options["_groups_id_requester"],
                    'entity'    => $this->fields["entities_id"],
                    'condition' => ['is_requester' => 1],
                    'display'   => false,
                ]),
            ];
        } elseif (isset($options["_groups_id_requester"]) && $options["_groups_id_requester"]) {
            // predefined value
            $rows[] = self::getPredefinedActorRow('group', CommonITILActor::REQUESTER, "glpi_groups", '_groups_id_requester', $options["_groups_id_requester"], 'br');
        }

        return $this->renderActorBloc(__('Requester'), $rows);
    }

    /**
     * Observer actor bloc (user + group).
     */
    protected function getObserverActorFormHtml(array $options, $can_admin): string
    {
        $rows = [];

        // Observer
        if ($can_admin) {
            $row          = $this->getActorAddFormOnCreateRow(CommonITILActor::OBSERVER, $options);
            $row['after'] = 'hr';
            $rows[]       = $row;
        } elseif (isset($options["_users_id_observer"][0]) && $options["_users_id_observer"][0]) {
            // predefined value
            $rows[] = self::getPredefinedActorRow('user', CommonITILActor::OBSERVER, "glpi_users", '_users_id_observer', $options["_users_id_observer"][0], 'hr');
        }

        // Observer Group
        if ($can_admin) {
            $rows[] = [
                'icon'   => static::getActorIconData('group', CommonITILActor::OBSERVER),
                'widget' => Group::dropdown([
                    'name'      => '_groups_id_observer',
                    'value'     => $options["_groups_id_observer"],
                    'entity'    => $this->fields["entities_id"],
                    'condition' => ['is_requester' => 1],
                    'display'   => false,
                ]),
            ];
        } elseif (isset($options["_groups_id_observer"]) && $options["_groups_id_observer"]) {
            // predefined value
            $rows[] = self::getPredefinedActorRow('group', CommonITILActor::OBSERVER, "glpi_groups", '_groups_id_observer', $options["_groups_id_observer"], 'br');
        }

        return $this->renderActorBloc(_n('Observer', 'Observers', 1), $rows);
    }

    /**
     * Assigned actor bloc (user + group + supplier).
     */
    protected function getAssignActorFormHtml(array $options, $can_assign, $can_assigntome): string
    {
        $rows    = [];
        $allowed = $this->isAllowedStatus(CommonITILObject::INCOMING, CommonITILObject::ASSIGNED);

        // Assign User
        if ($can_assign && $allowed) {
            $row          = $this->getActorAddFormOnCreateRow(CommonITILActor::ASSIGN, $options);
            $row['after'] = 'hr';
            $rows[]       = $row;
        } elseif ($can_assigntome && $allowed) {
            $rows[] = [
                'icon'   => static::getActorIconData('user', CommonITILActor::ASSIGN),
                'widget' => User::dropdown([
                    'name'        => '_users_id_assign',
                    'value'       => $options["_users_id_assign"],
                    'entity'      => $this->fields["entities_id"],
                    'ldap_import' => true,
                    'display'     => false,
                ]),
                'after'  => 'hr',
            ];
        } elseif (isset($options["_users_id_assign"]) && $options["_users_id_assign"] && $allowed) {
            // predefined value
            $rows[] = self::getPredefinedActorRow('user', CommonITILActor::ASSIGN, "glpi_users", '_users_id_assign', $options["_users_id_assign"], 'hr');
        }

        // Assign Groups
        if ($can_assign && $allowed) {
            $rows[] = [
                'icon'   => static::getActorIconData('group', CommonITILActor::ASSIGN),
                'widget' => Group::dropdown([
                    'name'      => '_groups_id_assign',
                    'value'     => $options["_groups_id_assign"],
                    'entity'    => $this->fields["entities_id"],
                    'condition' => ['is_assign' => 1],
                    'display'   => false,
                ]),
                'after'  => 'hr',
            ];
        } elseif (isset($options["_groups_id_assign"]) && $options["_groups_id_assign"] && $allowed) {
            // predefined value
            $rows[] = self::getPredefinedActorRow('group', CommonITILActor::ASSIGN, "glpi_groups", '_groups_id_assign', $options["_groups_id_assign"], 'hr');
        }

        // Assign Suppliers
        if ($can_assign && $allowed) {
            $rows[] = [
                'icon'   => static::getActorIconData('supplier', CommonITILActor::ASSIGN),
                'widget' => Supplier::dropdown([
                    'name'    => '_suppliers_id_assign',
                    'value'   => $options["_suppliers_id_assign"],
                    'display' => false,
                ]),
            ];
        } elseif (isset($options["_suppliers_id_assign"]) && $options["_suppliers_id_assign"] && $allowed) {
            // predefined value
            $rows[] = self::getPredefinedActorRow('supplier', CommonITILActor::ASSIGN, "glpi_suppliers", '_suppliers_id_assign', $options["_suppliers_id_assign"], 'hr');
        }

        return $this->renderActorBloc(__('Assigned to'), $rows);
    }

    /**
     * Can manage actors
     *
     * @return boolean
     */
    public function canAdminActors()
    {
        if (isset($this->fields['is_deleted']) && $this->fields['is_deleted'] == 1) {
            return false;
        }
        return Session::haveRight(static::$rightname, UPDATE);
    }

    /**
     * Can assign object
     *
     * @return boolean
     */
    public function canAssign()
    {
        if (isset($this->fields['is_deleted']) && ($this->fields['is_deleted'] == 1)
            || isset($this->fields['status']) && in_array($this->fields['status'], $this->getClosedStatusArray())
        ) {
            return false;
        }
        return Session::haveRight(static::$rightname, UPDATE);
    }

    /**
     * Can be assigned to me
     *
     * @return boolean
     */
    public function canAssignToMe()
    {
        if (isset($this->fields['is_deleted']) && $this->fields['is_deleted'] == 1
            || isset($this->fields['status']) && in_array($this->fields['status'], $this->getClosedStatusArray())
        ) {
            return false;
        }
        return Session::haveRight(static::$rightname, UPDATE);
    }

    /**
     * get field part name corresponding to actor type
     *
     * @param $type      integer : user type
     *
     * @return string|boolean Field part or false if not applicable
     **@since 0.84.6
     *
     */
    public static function getActorFieldNameType($type)
    {

        switch ($type) {
            case CommonITILActor::REQUESTER:
                return 'requester';

            case CommonITILActor::OBSERVER:
                return 'observer';

            case CommonITILActor::ASSIGN:
                return 'assign';

            default:
                return false;
        }
    }

    /**
     * Get icon data (Tabler class + title) for an actor
     *
     * @param string $user_group 'user', 'group' or 'supplier'
     * @param mixed  $type       CommonITILActor type
     *
     * @return array{class: string, title: string}|null
     **/
    public static function getActorIconData($user_group, $type)
    {
        switch ($user_group) {
            case 'user':
                $icontitle = __('User') . ' - ' . $type; // should never be used
                switch ($type) {
                    case CommonITILActor::REQUESTER:
                        $icontitle = __('Requester user');
                        break;

                    case CommonITILActor::OBSERVER:
                        $icontitle = __('Watcher user');
                        break;

                    case CommonITILActor::ASSIGN:
                        $icontitle = __('Technician');
                        break;
                }
                return ['class' => 'ti-user', 'title' => $icontitle];

            case 'group':
                $icontitle = __('Group');
                switch ($type) {
                    case CommonITILActor::REQUESTER:
                        $icontitle = __('Requester group');
                        break;

                    case CommonITILActor::OBSERVER:
                        $icontitle = __('Watcher group');
                        break;

                    case CommonITILActor::ASSIGN:
                        $icontitle = __('Group in charge of the release', 'releases');
                        break;
                }
                return ['class' => 'ti-users', 'title' => $icontitle];

            case 'supplier':
                return ['class' => 'ti-truck', 'title' => __('Supplier')];
        }
        return null;
    }

    /**
     * Is a user linked to the object ?
     *
     * @param integer $type type to search (see constants)
     * @param integer $users_id user ID
     *
     * @return boolean
     **/
    public function isUser($type, $users_id)
    {

        if (isset($this->users[$type])) {
            foreach ($this->users[$type] as $data) {
                if ($data['users_id'] == $users_id) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Resolve and check the template a sub-item is being attached to.
     *
     * The *template sub-items are CommonDropdown: the core's check(-1, CREATE, $_POST)
     * only validates the posted entities_id, never the foreign key pointing at the
     * template. Without this, a crafted key drops the sub-item into another entity's
     * template — and from there into every release cloned from it. This is the mirror
     * of what Risk/Test/Rollback/Deploytask already do with their parent release.
     *
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>|false
     */
    public static function checkParentTemplateInput(array $input)
    {
        $templates_id = (int) ($input['plugin_releases_releasetemplates_id'] ?? 0);

        // A sub-item with no parent carries no entity to borrow. The listings all filter
        // on the foreign key, so such a row is inert; leave that case as it was.
        if ($templates_id === 0) {
            $input['plugin_releases_releasetemplates_id'] = 0;
            return $input;
        }

        $template = new self();
        if (!$template->getFromDB($templates_id)
            || !Session::haveAccessToEntity($template->fields['entities_id'], $template->isRecursive())) {
            Session::addMessageAfterRedirect(
                __('The action you have requested is not allowed.'),
                false,
                ERROR,
            );
            return false;
        }

        $input['entities_id'] = $template->fields['entities_id'];

        return $input;
    }

    /**
     * Drop the keys that settle the ownership of a template sub-item.
     *
     * The parent template is fixed at creation time, from a template that was checked back
     * then; re-sending it in an update is the cross-entity move this closes. The entity is
     * never taken from the input but follows the stored parent template, so that the
     * transfer of a template (parent updated first) still moves its sub-items.
     *
     * @param array<string, mixed> $input
     * @param CommonDBTM           $subitem sub-item being updated
     *
     * @return array<string, mixed>
     */
    public static function stripParentTemplateInput(array $input, CommonDBTM $subitem)
    {
        unset($input['plugin_releases_releasetemplates_id']);

        if (array_key_exists('entities_id', $input)) {
            $template = new self();
            if ($template->getFromDB((int) ($subitem->fields['plugin_releases_releasetemplates_id'] ?? 0))) {
                $input['entities_id'] = $template->fields['entities_id'];
            } else {
                unset($input['entities_id']);
            }
        }

        return $input;
    }

    public function post_addItem()
    {
        parent::post_addItem();
        $useractors = null;
        // Add user groups linked to ITIL objects
        if (!empty($this->userlinkclass)) {
            $useractors = new $this->userlinkclass();
        }
        $groupactors = null;
        if (!empty($this->grouplinkclass)) {
            $groupactors = new $this->grouplinkclass();
        }
        $supplieractors = null;
        if (!empty($this->supplierlinkclass)) {
            $supplieractors = new $this->supplierlinkclass();
        }

        // "do not compute" flag set by business rules for "takeintoaccount_delay_stat" field
        $do_not_compute_takeintoaccount = $this->isTakeIntoAccountComputationBlocked($this->input);

        if (!is_null($useractors)) {
            $user_input = [
                $useractors->getItilObjectForeignKey() => $this->fields['id'],
                '_do_not_compute_takeintoaccount'      => $do_not_compute_takeintoaccount,
                '_from_object'                         => true,
            ];

            if (isset($this->input["_users_id_requester"])) {

                if (is_array($this->input["_users_id_requester"])) {
                    $tab_requester = $this->input["_users_id_requester"];
                } else {
                    $tab_requester   = [];
                    $tab_requester[] = $this->input["_users_id_requester"];
                }

                $requesterToAdd = [];
                foreach ($tab_requester as $key_requester => $requester) {
                    if (in_array($requester, $requesterToAdd)) {
                        // This requester ID is already added;
                        continue;
                    }

                    $input2 = [
                        'users_id' => $requester,
                        'type'     => CommonITILActor::REQUESTER,
                    ] + $user_input;

                    if (isset($this->input["_users_id_requester_notif"])) {
                        foreach ($this->input["_users_id_requester_notif"] as $key => $val) {
                            if (isset($val[$key_requester])) {
                                $input2[$key] = $val[$key_requester];
                            }
                        }
                    }

                    //empty actor
                    if ($input2['users_id'] == 0
                        && (!isset($input2['alternative_email'])
                            || empty($input2['alternative_email']))) {
                        continue;
                    } elseif ($requester != 0) {
                        $requesterToAdd[] = $requester;
                    }

                    $useractors->add($input2);
                }
            }

            if (isset($this->input["_users_id_observer"])) {

                if (is_array($this->input["_users_id_observer"])) {
                    $tab_observer = $this->input["_users_id_observer"];
                } else {
                    $tab_observer   = [];
                    $tab_observer[] = $this->input["_users_id_observer"];
                }

                $observerToAdd = [];
                foreach ($tab_observer as $key_observer => $observer) {
                    if (in_array($observer, $observerToAdd)) {
                        // This observer ID is already added;
                        continue;
                    }

                    $input2 = [
                        'users_id' => $observer,
                        'type'     => CommonITILActor::OBSERVER,
                    ] + $user_input;

                    if (isset($this->input["_users_id_observer_notif"])) {
                        foreach ($this->input["_users_id_observer_notif"] as $key => $val) {
                            if (isset($val[$key_observer])) {
                                $input2[$key] = $val[$key_observer];
                            }
                        }
                    }

                    //empty actor
                    if ($input2['users_id'] == 0
                        && (!isset($input2['alternative_email'])
                            || empty($input2['alternative_email']))) {
                        continue;
                    } elseif ($observer != 0) {
                        $observerToAdd[] = $observer;
                    }

                    $useractors->add($input2);
                }
            }

            if (isset($this->input["_users_id_assign"])) {

                if (is_array($this->input["_users_id_assign"])) {
                    $tab_assign = $this->input["_users_id_assign"];
                } else {
                    $tab_assign   = [];
                    $tab_assign[] = $this->input["_users_id_assign"];
                }

                $assignToAdd = [];
                foreach ($tab_assign as $key_assign => $assign) {
                    if (in_array($assign, $assignToAdd)) {
                        // This assigned user ID is already added;
                        continue;
                    }

                    $input2 = [
                        'users_id' => $assign,
                        'type'     => CommonITILActor::ASSIGN,
                    ] + $user_input;

                    if (isset($this->input["_users_id_assign_notif"])) {
                        foreach ($this->input["_users_id_assign_notif"] as $key => $val) {
                            if (isset($val[$key_assign])) {
                                $input2[$key] = $val[$key_assign];
                            }
                        }
                    }

                    //empty actor
                    if ($input2['users_id'] == 0
                        && (!isset($input2['alternative_email'])
                            || empty($input2['alternative_email']))) {
                        continue;
                    } elseif ($assign != 0) {
                        $assignToAdd[] = $assign;
                    }

                    $useractors->add($input2);
                }
            }
        }

        if (!is_null($groupactors)) {
            $group_input = [
                $groupactors->getItilObjectForeignKey() => $this->fields['id'],
                '_do_not_compute_takeintoaccount'       => $do_not_compute_takeintoaccount,
                '_from_object'                          => true,
            ];

            if (isset($this->input["_groups_id_requester"])) {
                $groups_id_requester = $this->input["_groups_id_requester"];
                if (!is_array($this->input["_groups_id_requester"])) {
                    $groups_id_requester = [$this->input["_groups_id_requester"]];
                } else {
                    $groups_id_requester = $this->input["_groups_id_requester"];
                }
                foreach ($groups_id_requester as $groups_id) {
                    if ($groups_id > 0) {
                        $groupactors->add(
                            [
                                'groups_id' => $groups_id,
                                'type'      => CommonITILActor::REQUESTER,
                            ] + $group_input,
                        );
                    }
                }
            }

            if (isset($this->input["_groups_id_assign"])) {
                if (!is_array($this->input["_groups_id_assign"])) {
                    $groups_id_assign = [$this->input["_groups_id_assign"]];
                } else {
                    $groups_id_assign = $this->input["_groups_id_assign"];
                }
                foreach ($groups_id_assign as $groups_id) {
                    if ($groups_id > 0) {
                        $groupactors->add(
                            [
                                'groups_id' => $groups_id,
                                'type'      => CommonITILActor::ASSIGN,
                            ] + $group_input,
                        );
                    }
                }
            }

            if (isset($this->input["_groups_id_observer"])) {
                if (!is_array($this->input["_groups_id_observer"])) {
                    $groups_id_observer = [$this->input["_groups_id_observer"]];
                } else {
                    $groups_id_observer = $this->input["_groups_id_observer"];
                }
                foreach ($groups_id_observer as $groups_id) {
                    if ($groups_id > 0) {
                        $groupactors->add(
                            [
                                'groups_id' => $groups_id,
                                'type'      => CommonITILActor::OBSERVER,
                            ] + $group_input,
                        );
                    }
                }
            }
        }

        if (!is_null($supplieractors)) {
            $supplier_input = [
                $supplieractors->getItilObjectForeignKey() => $this->fields['id'],
                '_do_not_compute_takeintoaccount'          => $do_not_compute_takeintoaccount,
                '_from_object'                             => true,
            ];

            if (isset($this->input["_suppliers_id_assign"])
                && ($this->input["_suppliers_id_assign"] > 0)) {

                if (is_array($this->input["_suppliers_id_assign"])) {
                    $tab_assign = $this->input["_suppliers_id_assign"];
                } else {
                    $tab_assign   = [];
                    $tab_assign[] = $this->input["_suppliers_id_assign"];
                }

                $supplierToAdd = [];
                foreach ($tab_assign as $key_assign => $assign) {
                    if (in_array($assign, $supplierToAdd)) {
                        // This assigned supplier ID is already added;
                        continue;
                    }
                    $input3 = [
                        'suppliers_id' => $assign,
                        'type'         => CommonITILActor::ASSIGN,
                    ] + $supplier_input;

                    if (isset($this->input["_suppliers_id_assign_notif"])) {
                        foreach ($this->input["_suppliers_id_assign_notif"] as $key => $val) {
                            $input3[$key] = $val[$key_assign];
                        }
                    }

                    //empty supplier
                    if ($input3['suppliers_id'] == 0
                        && (!isset($input3['alternative_email'])
                            || empty($input3['alternative_email']))) {
                        continue;
                    } elseif ($assign != 0) {
                        $supplierToAdd[] = $assign;
                    }

                    $supplieractors->add($input3);
                }
            }
        }

        // Additional actors
        $this->addAdditionalActors($this->input);
    }

    /**
     * Check if input contains a flag set to prevent 'takeintoaccount' delay computation.
     *
     * @param array $input
     *
     * @return boolean
     */
    public function isTakeIntoAccountComputationBlocked($input)
    {
        return array_key_exists('_do_not_compute_takeintoaccount', $input)
               && $input['_do_not_compute_takeintoaccount'];
    }

    /**
     * @since 0.84
     * @since 0.85 must have param $input
     **/
    private function addAdditionalActors($input)
    {

        $useractors = null;
        // Add user groups linked to ITIL objects
        if (!empty($this->userlinkclass)) {
            $useractors = new $this->userlinkclass();
        }
        $groupactors = null;
        if (!empty($this->grouplinkclass)) {
            $groupactors = new $this->grouplinkclass();
        }
        $supplieractors = null;
        if (!empty($this->supplierlinkclass)) {
            $supplieractors = new $this->supplierlinkclass();
        }

        // "do not compute" flag set by business rules for "takeintoaccount_delay_stat" field
        $do_not_compute_takeintoaccount = $this->isTakeIntoAccountComputationBlocked($input);

        // Additional groups actors
        if (!is_null($groupactors)) {
            $group_input = [
                $groupactors->getItilObjectForeignKey() => $this->fields['id'],
                '_do_not_compute_takeintoaccount'       => $do_not_compute_takeintoaccount,
                '_from_object'                          => true,
            ];

            // Requesters
            if (isset($input['_additional_groups_requesters'])
                && is_array($input['_additional_groups_requesters'])
                && count($input['_additional_groups_requesters'])) {
                foreach ($input['_additional_groups_requesters'] as $tmp) {
                    if ($tmp > 0) {
                        $groupactors->add(
                            [
                                'type'      => CommonITILActor::REQUESTER,
                                'groups_id' => $tmp,
                            ] + $group_input,
                        );
                    }
                }
            }

            // Observers
            if (isset($input['_additional_groups_observers'])
                && is_array($input['_additional_groups_observers'])
                && count($input['_additional_groups_observers'])) {
                foreach ($input['_additional_groups_observers'] as $tmp) {
                    if ($tmp > 0) {
                        $groupactors->add(
                            [
                                'type'      => CommonITILActor::OBSERVER,
                                'groups_id' => $tmp,
                            ] + $group_input,
                        );
                    }
                }
            }

            // Assigns
            if (isset($input['_additional_groups_assigns'])
                && is_array($input['_additional_groups_assigns'])
                && count($input['_additional_groups_assigns'])) {
                foreach ($input['_additional_groups_assigns'] as $tmp) {
                    if ($tmp > 0) {
                        $groupactors->add(
                            [
                                'type'      => CommonITILActor::ASSIGN,
                                'groups_id' => $tmp,
                            ] + $group_input,
                        );
                    }
                }
            }
        }

        // Additional suppliers actors
        if (!is_null($supplieractors)) {
            $supplier_input = [
                $supplieractors->getItilObjectForeignKey() => $this->fields['id'],
                '_do_not_compute_takeintoaccount'          => $do_not_compute_takeintoaccount,
                '_from_object'                             => true,
            ];

            // Assigns
            if (isset($input['_additional_suppliers_assigns'])
                && is_array($input['_additional_suppliers_assigns'])
                && count($input['_additional_suppliers_assigns'])) {

                $input2 = [
                    'type' => CommonITILActor::ASSIGN,
                ] + $supplier_input;

                foreach ($input["_additional_suppliers_assigns"] as $tmp) {
                    if (isset($tmp['suppliers_id'])) {
                        foreach ($tmp as $key => $val) {
                            $input2[$key] = $val;
                        }
                        $supplieractors->add($input2);
                    }
                }
            }
        }

        // Additional actors : using default notification parameters
        if (!is_null($useractors)) {
            $user_input = [
                $useractors->getItilObjectForeignKey() => $this->fields['id'],
                '_do_not_compute_takeintoaccount'      => $do_not_compute_takeintoaccount,
                '_from_object'                         => true,
            ];

            // Observers : for mailcollector
            if (isset($input["_additional_observers"])
                && is_array($input["_additional_observers"])
                && count($input["_additional_observers"])) {

                $input2 = [
                    'type' => CommonITILActor::OBSERVER,
                ] + $user_input;

                foreach ($input["_additional_observers"] as $tmp) {
                    if (isset($tmp['users_id'])) {
                        foreach ($tmp as $key => $val) {
                            $input2[$key] = $val;
                        }
                        $useractors->add($input2);
                    }
                }
            }

            if (isset($input["_additional_assigns"])
                && is_array($input["_additional_assigns"])
                && count($input["_additional_assigns"])) {

                $input2 = [
                    'type' => CommonITILActor::ASSIGN,
                ] + $user_input;

                foreach ($input["_additional_assigns"] as $tmp) {
                    if (isset($tmp['users_id'])) {
                        foreach ($tmp as $key => $val) {
                            $input2[$key] = $val;
                        }
                        $useractors->add($input2);
                    }
                }
            }
            if (isset($input["_additional_requesters"])
                && is_array($input["_additional_requesters"])
                && count($input["_additional_requesters"])) {

                $input2 = [
                    'type' => CommonITILActor::REQUESTER,
                ] + $user_input;

                foreach ($input["_additional_requesters"] as $tmp) {
                    if (isset($tmp['users_id'])) {
                        foreach ($tmp as $key => $val) {
                            $input2[$key] = $val;
                        }
                        $useractors->add($input2);
                    }
                }
            }
        }
    }

    /**
     * Update date mod of the ITIL object
     *
     * @param $ID                    integer  ID of the ITIL object
     * @param $no_stat_computation   boolean  do not cumpute take into account stat (false by default)
     * @param $users_id_lastupdater  integer  to force last_update id (default 0 = not used)
     **/
    public function updateDateMod($ID, $no_stat_computation = false, $users_id_lastupdater = 0) {}

    public function post_getFromDB()
    {
        $this->loadActors();
    }

    /**
     * @since 0.84
     **/
    public function loadActors()
    {

        if (!empty($this->grouplinkclass)) {
            $class        = new $this->grouplinkclass();
            $this->groups = $class->getActors($this->fields['id']);
        }

        if (!empty($this->userlinkclass)) {
            $class       = new $this->userlinkclass();
            $this->users = $class->getActors($this->fields['id']);
        }

        if (!empty($this->supplierlinkclass)) {
            $class           = new $this->supplierlinkclass();
            $this->suppliers = $class->getActors($this->fields['id']);
        }
    }

    /**
     * User selector row for an actor bloc on creation
     *
     * @param $type      integer  actor type
     * @param $options   array    options for default values ($options of showForm)
     *
     * @return array{icon: array|null, widget: string}
     **/
    public function getActorAddFormOnCreateRow($type, array $options)
    {
        $typename = static::getActorFieldNameType($type);

        if (!isset($options["_right"])) {
            $right = $this->getDefaultActorRightSearch($type);
        } else {
            $right = $options["_right"];
        }

        $actor_name = '_users_id_' . $typename;
        if ($type == CommonITILActor::OBSERVER) {
            $actor_name = '_users_id_' . $typename . '[]';
        }
        $params = ['name'    => $actor_name,
            'value'   => $options["_users_id_" . $typename],
            'right'   => $right,
            'rand'    => mt_rand(),
            'display' => false,
            'entity'  => (isset($options['entities_id'])
               ? $options['entities_id'] : $options['entity_restrict'])];

        //only for active ldap and corresponding right
        $ldap_methods = getAllDataFromTable('glpi_authldaps', ['is_active' => 1]);
        if (count($ldap_methods)
            && Session::haveRight('user', User::IMPORTEXTAUTHUSERS)) {
            $params['ldap_import'] = true;
        }

        if ($this->userentity_oncreate
            && ($type == CommonITILActor::REQUESTER)) {
            $params['on_change'] = 'this.form.submit()';
            unset($params['entity']);
        }

        $params['_user_index'] = 0;
        if (isset($options['_user_index'])) {
            $params['_user_index'] = $options['_user_index'];
        }

        // List all users in the active entities
        return [
            'icon'   => static::getActorIconData('user', $type),
            'widget' => User::dropdown($params),
        ];
    }

    /**
     * Get Default actor when creating the object
     *
     * @param integer $type type to search (see constants)
     *
     * @return boolean
     **/
    public function getDefaultActorRightSearch($type)
    {

        if ($type == CommonITILActor::ASSIGN) {
            return "own_ticket";
        }
        return "all";
    }

    /**
     * @see CommonITILObject::getDefaultActor()
     **/
    public function getDefaultActor($type)
    {

        if ($type == CommonITILActor::ASSIGN) {
            if (Session::haveRight(self::$rightname, UPDATE)
                && $_SESSION['glpiset_default_tech']) {
                return Session::getLoginUserID();
            }
        }
        if ($type == CommonITILActor::REQUESTER) {
            if (Session::haveRight(self::$rightname, CREATE)
                && $_SESSION['glpiset_default_requester']) {
                return Session::getLoginUserID();
            }
        }
        return 0;
    }

    /**
     * count users linked to object by type or global
     *
     * @param integer $type type to search (see constants) / 0 for all (default 0)
     *
     * @return integer
     **/
    public function countUsers($type = 0)
    {

        if ($type > 0) {
            if (isset($this->users[$type])) {
                return count($this->users[$type]);
            }

        } else {
            if (count($this->users)) {
                $count = 0;
                foreach ($this->users as $u) {
                    $count += count($u);
                }
                return $count;
            }
        }
        return 0;
    }

    /**
     * count groups linked to object by type or global
     *
     * @param integer $type type to search (see constants) / 0 for all (default 0)
     *
     * @return integer
     **/
    public function countGroups($type = 0)
    {

        if ($type > 0) {
            if (isset($this->groups[$type])) {
                return count($this->groups[$type]);
            }

        } else {
            if (count($this->groups)) {
                $count = 0;
                foreach ($this->groups as $u) {
                    $count += count($u);
                }
                return $count;
            }
        }
        return 0;
    }

    /**
     * count suppliers linked to object by type or global
     *
     * @param integer $type type to search (see constants) / 0 for all (default 0)
     *
     * @return integer
     **@since 0.84
     *
     */
    public function countSuppliers($type = 0)
    {

        if ($type > 0) {
            if (isset($this->suppliers[$type])) {
                return count($this->suppliers[$type]);
            }

        } else {
            if (count($this->suppliers)) {
                $count = 0;
                foreach ($this->suppliers as $u) {
                    $count += count($u);
                }
                return $count;
            }
        }
        return 0;
    }

    /**
     * @param null $checkitem
     *
     * @return array
     * @since version 0.85
     *
     * @see CommonDBTM::getSpecificMassiveActions()
     *
     */
    public function getSpecificMassiveActions($checkitem = null)
    {
        $isadmin = static::canUpdate();
        $actions = parent::getSpecificMassiveActions($checkitem);

        if (Session::getCurrentInterface() == 'central') {
            if ($isadmin) {
                $actions['GlpiPlugin\Releases\ReleaseTemplate' . MassiveAction::CLASS_ACTION_SEPARATOR . 'transfer'] = __('Transfer');
            }
        }
        return $actions;
    }

    /**
     * @param MassiveAction $ma
     *
     * @return bool|false
     * @since version 0.85
     *
     * @see CommonDBTM::showMassiveActionsSubForm()
     *
     */
    public static function showMassiveActionsSubForm(MassiveAction $ma)
    {

        switch ($ma->getAction()) {
            case "transfer":
                Dropdown::show('Entity');
                echo Html::submit(_x('button', 'Post'), ['name' => 'massiveaction', 'class' => 'btn btn-primary']);
                return true;
                break;
        }
        return parent::showMassiveActionsSubForm($ma);
    }

    /**
     * @param MassiveAction $ma
     * @param CommonDBTM    $item
     * @param array         $ids
     *
     * @return nothing|void
     * @since version 0.85
     *
     * @see CommonDBTM::processMassiveActionsForOneItemtype()
     *
     */
    public static function processMassiveActionsForOneItemtype(
        MassiveAction $ma,
        CommonDBTM $item,
        array $ids
    ) {

        switch ($ma->getAction()) {

            case "transfer":
                $input = $ma->getInput();
                if ($item->getType() == ReleaseTemplate::getType()) {
                    // The posted target entity must be revalidated: a right check protects the row, not the posted value
                    $entities_id = (int) ($input['entities_id'] ?? -1);
                    if ($entities_id < 0 || !Session::haveAccessToEntity($entities_id)) {
                        $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                        foreach ($ids as $key) {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                        }
                        return;
                    }

                    foreach ($ids as $key) {
                        // The core forwards the posted ids as is: the right has to be rechecked on each row
                        if (!$item->can($key, UPDATE)) {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_NORIGHT);
                            $ma->addMessage($item->getErrorMessage(ERROR_RIGHT));
                            continue;
                        }

                        $values                = [];
                        $values["id"]          = $key;
                        $values["entities_id"] = $entities_id;

                        if ($item->update($values)) {
                            Deploytasktemplate::transfer($key, $entities_id);
                            Testtemplate::transfer($key, $entities_id);
                            Risktemplate::transfer($key, $entities_id);
                            Rollbacktemplate::transfer($key, $entities_id);
                            self::transferDocument($key, $entities_id);
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_OK);
                        } else {
                            $ma->itemDone($item->getType(), $key, MassiveAction::ACTION_KO);
                        }
                    }
                }
                return;
        }
        parent::processMassiveActionsForOneItemtype($ma, $item, $ids);
    }

    /**
     * Follow a transferred template with the documents attached to it.
     *
     * Only the Document_Item link used to be moved, never the Document: the link then claimed
     * an entity the document does not belong to, and the attachment showed on a template whose
     * readers cannot read the file. Moving the document with it is only correct when this
     * template is its sole holder and it is not recursive — a shared or recursive document
     * answers to more than one caller and must stay where it is, link included, so the two
     * entities_id never diverge.
     *
     * @param int $ID     template id
     * @param int $entity target entity
     *
     * @return bool
     */
    public static function transferDocument($ID, $entity)
    {
        if ($ID <= 0) {
            return false;
        }

        $document_item = new Document_Item();
        $document      = new Document();
        $links         = $document_item->find(["items_id" => $ID, "itemtype" => self::getType()]);

        foreach ($links as $id => $values) {
            $others = $document_item->find([
                'documents_id' => $values['documents_id'],
                'NOT'          => ['id' => $id],
            ]);
            if (count($others) > 0
                || !$document->getFromDB($values['documents_id'])
                || $document->fields['is_recursive']
                // The right on the template does not extend to the Document itself: moving it
                // requires UPDATE on the document in its current entity, otherwise it is left
                // where it is, like a shared document.
                || !$document->can($document->getID(), UPDATE)) {
                continue;
            }

            $document_item->update(['id' => $id, 'entities_id' => $entity]);
            $document->update(['id' => $document->getID(), 'entities_id' => $entity]);
        }

        return true;
    }

    /**
     * Retrieve an item from the database with additional datas
     *
     * @since 0.83
     *
     * @param $ID                    integer  ID of the item to get
     * @param $withtypeandcategory   boolean  with type and category (true by default)
     *
     * @return true if succeed else false
     **/
    public function getFromDBWithData($ID, $withtypeandcategory = true)
    {
        if ($this->getFromDB($ID)) {
            $itiltype = str_replace('Template', '', static::getType());
            $itil_object  = new $itiltype();
            $itemstable = $itil_object->getItemsTable();
            $tth_class = $itiltype . 'TemplateHiddenField';
            $tth          = new $tth_class();
            $this->hidden = $tth->getHiddenFields($ID, $withtypeandcategory);

            // Force items_id if itemtype is defined
            if (
                isset($this->hidden['itemtype'])
                && !isset($this->hidden['items_id'])
            ) {
                $this->hidden['items_id'] = $itil_object->getSearchOptionIDByField(
                    'field',
                    'items_id',
                    $itemstable,
                );
            }
            // Always get all mandatory fields
            $ttm_class = $itiltype . 'TemplateMandatoryField';
            $ttm             = new $ttm_class();
            $this->mandatory = $ttm->getMandatoryFields($ID);

            // Force items_id if itemtype is defined
            if (
                isset($this->mandatory['itemtype'])
                && !isset($this->mandatory['items_id'])
            ) {
                $this->mandatory['items_id'] = $itil_object->getSearchOptionIDByField(
                    'field',
                    'items_id',
                    $itemstable,
                );
            }

            $ttp_class = $itiltype . 'TemplatePredefinedField';
            $ttp              = new $ttp_class();
            $this->predefined = $ttp->getPredefinedFields($ID, $withtypeandcategory);
            // Compute time_to_resolve
            if (isset($this->predefined['time_to_resolve'])) {
                $this->predefined['time_to_resolve']
                   = Html::computeGenericDateTimeSearch($this->predefined['time_to_resolve'], false);
            }
            if (isset($this->predefined['time_to_own'])) {
                $this->predefined['time_to_own']
                   = Html::computeGenericDateTimeSearch($this->predefined['time_to_own'], false);
            }

            // Compute internal_time_to_resolve
            if (isset($this->predefined['internal_time_to_resolve'])) {
                $this->predefined['internal_time_to_resolve']
                   = Html::computeGenericDateTimeSearch($this->predefined['internal_time_to_resolve'], false);
            }
            if (isset($this->predefined['internal_time_to_own'])) {
                $this->predefined['internal_time_to_own']
                   = Html::computeGenericDateTimeSearch($this->predefined['internal_time_to_own'], false);
            }

            // Compute date
            if (isset($this->predefined['date'])) {
                $this->predefined['date']
                   = Html::computeGenericDateTimeSearch($this->predefined['date'], false);
            }
            return true;
        }
        return false;
    }
}
