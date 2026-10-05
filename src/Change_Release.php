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

use Change;
use CommonDBRelation;
use CommonGLPI;
use DbUtils;
use Glpi\Application\View\TemplateRenderer;
use Glpi\RichText\RichText;
use Session;
use Toolbox;

/**
 * Change_Release Class
 *
 * Relation between Changes and Releases
 **/
class Change_Release extends CommonDBRelation
{
    // From CommonDBRelation
    public static ?string $itemtype_1 = 'Change';
    public static ?string $items_id_1 = 'changes_id';

    public static ?string $itemtype_2 = Release::class;
    public static ?string $items_id_2 = 'plugin_releases_releases_id';

    public static function getTypeName($nb = 0)
    {
        return _n('Link Release/Change', 'Links Release/Change', $nb, 'releases');
    }

    public static function getIcon()
    {
        return "ti ti-clipboard-check";
    }

    /**
     * @since 0.85
     *
     * @see CommonGLPI::getTabNameForItem()
     **/
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if (static::canView()) {
            $nb = 0;
            switch ($item->getType()) {
                case Release::class:
                    if ($_SESSION['glpishow_count_on_tabs']) {
                        $nb = countElementsInTable(
                            'glpi_plugin_releases_changes_releases',
                            ['plugin_releases_releases_id' => $item->getID()],
                        );
                    }
                    return self::createTabEntry(Change::getTypeName(Session::getPluralNumber()), $nb);
            }
        }
        return '';
    }

    /**
     * @since 0.85
     **/
    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {

        switch ($item->getType()) {
            case Release::class:
                self::showForRelease($item);
                break;
        }
        return true;
    }

    /**
     * Show changes for a release
     *
     * @param $release Release object
     **/
    public static function showForRelease(Release $release)
    {
        global $DB;

        $ID = $release->getField('id');
        if (!$release->can($ID, READ)) {
            return false;
        }

        $canedit = $release->canEdit($ID);
        $rand    = mt_rand();
        // Strip namespace backslashes: the container id becomes a DOM id and a JS
        // selector, both of which break with backslashes (cf. showReleaseFromChange).
        $mass_id = 'mass' . str_replace('\\', '', self::class) . $rand;

        // The link table carries no entity of its own and Change::showShort() performs no
        // right check, so the global Change_Release::canView() was the only gate on this
        // list: a change linked here and later transferred to another entity stayed
        // readable from the release. Restrict the join the way the sibling tabs already do
        // (Release_Item::showForRelease, ReleaseTemplate_Item::showForRelease).
        $dbu   = new DbUtils();
        $where = [
            'glpi_plugin_releases_changes_releases.plugin_releases_releases_id' => $ID,
        ];
        $entity_criteria = $dbu->getEntitiesRestrictCriteria('glpi_changes', '', '', true);
        if (count($entity_criteria) > 0) {
            $where[] = $entity_criteria;
        }

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_releases_changes_releases.id AS linkid',
                'glpi_changes.*',
            ],
            'DISTINCT'  => true,
            'FROM'      => 'glpi_plugin_releases_changes_releases',
            'LEFT JOIN' => [
                'glpi_changes' => [
                    'ON' => [
                        'glpi_plugin_releases_changes_releases' => 'changes_id',
                        'glpi_changes'                          => 'id',
                    ],
                ],
            ],
            'WHERE'     => $where,
            'ORDERBY'   => [
                'glpi_changes.name',
            ],
        ]);

        $changes = [];
        $used    = [];
        $change  = new Change();

        foreach ($iterator as $data) {
            // Replay the object-level right on every row: the entity criterion above does
            // not cover a change hidden by the profile itself, and the LEFT JOIN yields a
            // null id when the change no longer exists.
            if (!$change->can($data['id'], READ)) {
                continue;
            }
            $changes[$data['id']] = $data;
            $used[$data['id']]    = $data['id'];
        }
        $numrows = count($changes);
        if ($canedit) {
            // Capture the change dropdown (echoes internally) and render the add
            // mini-form through Twig instead of echoing raw HTML.
            ob_start();
            Change::dropdown([
                'used'      => $used,
                'entity'    => $release->getEntityID(),
                'condition' => ['status' => Change::getNotSolvedStatusArray()],
            ]);
            $change_dropdown = ob_get_clean();

            TemplateRenderer::getInstance()->display('@releases/form_change_release_add.html.twig', [
                'action_url'    => Toolbox::getItemTypeFormURL(self::class),
                'title'         => __('Add a change'),
                'hidden_name'   => 'plugin_releases_releases_id',
                'hidden_value'  => $ID,
                'dropdown_html' => $change_dropdown,
            ]);
        }

        [$columns, $formatters] = array_values(Change::getCommonDatatableColumns());

        Session::initNavigateListItems(
            Change::class,
            //TRANS : %1$s is the itemtype name,
            //        %2$s is the name of the item (used for headings of a list)
            sprintf(
                __('%1$s = %2$s'),
                Change::getTypeName(1),
                $release->fields["name"],
            ),
        );
        $rows = [];
        foreach ($changes as $data) {
            Session::addToNavigateListItems(Change::class, $data["id"]);
            $rows[] = [
                'itemtype' => Change::class,
                'item_id'  => $data['id'],
                'id'       => $data['linkid'],
            ];
        }
        // Entries are built from the Change, then retargeted to the link so the
        // massive actions (unlink) apply to Change_Release rows, as before.
        $entries = array_map(static function ($entry) {
            $entry['itemtype'] = self::class;
            return $entry;
        }, Change::getDatatableEntries($rows));

        TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
            'is_tab'              => true,
            'nofilter'            => true,
            'nosort'              => true,
            'super_header'        => Change::getTypeName($numrows),
            'columns'             => $columns,
            'formatters'          => $formatters,
            'entries'             => $entries,
            'total_number'        => count($entries),
            'filtered_number'     => count($entries),
            'showmassiveactions'  => $canedit,
            'massiveactionparams' => [
                'num_displayed' => count($entries),
                'container'     => $mass_id,
            ],
        ]);
    }

    public function post_addItem()
    {
        $release = new Release();
        if ($release->getFromDB($this->getField("plugin_releases_releases_id"))) {
            if ($release->getField("status") < Release::CHANGEDEFINITION) {
                $update["id"]     = $release->getID();
                $update["status"] = Release::CHANGEDEFINITION;
                $release->update($update);
            }
        }
    }

    /**
     * Actions done after the PURGE of the item in the database
     *
     * @return void
     **/
    public function post_purgeItem()
    {
        //TODO
    }

    public static function showReleaseFromChange($item)
    {
        global $CFG_GLPI, $DB;

        Release::showCreateRelease($item);

        $ID      = $item->getID();
        $canedit = Release::canUpdate();
        $rand    = mt_rand();

        // Mirror of showForRelease(): the reverse tab listed every release linked to the
        // change without any entity or object check either.
        $dbu   = new DbUtils();
        $where = [
            'glpi_plugin_releases_changes_releases.changes_id' => $ID,
        ];
        $entity_criteria = $dbu->getEntitiesRestrictCriteria('glpi_plugin_releases_releases', '', '', true);
        if (count($entity_criteria) > 0) {
            $where[] = $entity_criteria;
        }

        $iterator = $DB->request([
            'SELECT'    => [
                'glpi_plugin_releases_changes_releases.id AS linkid',
                'glpi_plugin_releases_releases.*',
            ],
            'DISTINCT'  => true,
            'FROM'      => 'glpi_plugin_releases_changes_releases',
            'LEFT JOIN' => [
                'glpi_plugin_releases_releases' => [
                    'ON' => [
                        'glpi_plugin_releases_changes_releases' => 'plugin_releases_releases_id',
                        'glpi_plugin_releases_releases'         => 'id',
                    ],
                ],
            ],
            'WHERE'     => $where,
            'ORDERBY'   => [
                'glpi_plugin_releases_releases.name',
            ],
        ]);

        $changes = [];
        $release = new Release();
        foreach ($iterator as $data) {
            if (!$release->can($data['id'], READ)) {
                continue;
            }
            $changes[$data['id']] = $data;
        }
        $numrows = count($changes);

        if ($canedit) {
            // Capture the release dropdown (echoes internally) and render the add
            // mini-form through Twig instead of echoing raw HTML.
            ob_start();
            Release::dropdown([
                'used'      => [],
                'entity'    => $item->getEntityID(),
                'condition' => [
                    'NOT' => [
                        'status' => Release::getClosedStatusArray(),
                    ],
                ],
            ]);
            $release_dropdown = ob_get_clean();

            TemplateRenderer::getInstance()->display('@releases/form_change_release_add.html.twig', [
                'action_url'    => Toolbox::getItemTypeFormURL(self::class),
                'title'         => __('Add a release', 'releases'),
                'hidden_name'   => 'changes_id',
                'hidden_value'  => $ID,
                'dropdown_html' => $release_dropdown,
            ]);
        }

        if ($numrows) {
            $entries = [];
            foreach ($changes as $idc => $d) {
                Session::addToNavigateListItems(self::getType(), $d["linkid"]);

                $review    = new Review();
                $real_date = '';
                if ($review->getFromDBByCrit(["plugin_releases_releases_id" => $d['id']])) {
                    $real_date = $review->fields["real_date_release"];
                }

                // Escape user-supplied release name (stored raw since GLPI 10+) to prevent stored XSS.
                $name = htmlspecialchars($d["name"]);
                if ($_SESSION["glpiis_ids_visible"] || empty($d["name"])) {
                    $name .= " (" . $idc . ")";
                }
                $link = $CFG_GLPI['root_doc'] . "/plugins/releases/front/release.form.php?id=" . $idc;

                $entries[] = [
                    'itemtype'           => self::class,
                    'id'                 => $d["linkid"],
                    'name'               => "<a href='" . htmlspecialchars($link) . "'>" . $name . "</a>",
                    'status'             => "<span class='status'>" . Release::getStatusIcon($d["status"], false) . Release::getStatus($d["status"]) . "</span>",
                    // Fully decoded plain text, escaped once by the default formatter below
                    'content'            => RichText::getTextFromHtml($d["content"], false),
                    'date_preproduction' => $d["date_preproduction"],
                    'date_production'    => $d["date_production"],
                    'real_date_release'  => $real_date,
                    'service_shutdown'   => (bool) $d["service_shutdown"],
                ];
            }

            $columns = [
                'name'               => __('Name'),
                'status'             => __('Status'),
                'content'            => __('Release area', 'releases'),
                'date_preproduction' => __('Pre-production planned date', 'releases'),
                'date_production'    => __('Production planned date', 'releases'),
                'real_date_release'  => __('Real production run date', 'releases'),
                'service_shutdown'   => __('Service shutdown', 'releases'),
            ];

            $formatters = [
                'name'               => 'raw_html',
                'status'             => 'raw_html',
                // No raw_html on content: getTextFromHtml() does NOT escape its output. It
                // strips the tags then decodes the entities, so a "&lt;img onerror&gt;" typed
                // in the editor came back as a live tag (stored XSS).
                'date_preproduction' => 'datetime',
                'date_production'    => 'datetime',
                'real_date_release'  => 'datetime',
                'service_shutdown'   => 'yesno',
            ];

            TemplateRenderer::getInstance()->display('components/datatable.html.twig', [
                'super_header'        => Release::getTypeName($numrows),
                'columns'             => $columns,
                'formatters'          => $formatters,
                'entries'             => $entries,
                'total_number'        => $numrows,
                'filtered_number'     => $numrows,
                'nofilter'            => true,
                'nosort'              => true,
                'showmassiveactions'  => $canedit,
                'massiveactionparams' => [
                    'num_displayed' => $numrows,
                    // Strip namespace backslashes: the container id becomes a DOM id
                    // and a JS selector, both of which break with backslashes.
                    'container'     => 'mass' . str_replace('\\', '', self::class) . $rand,
                ],
            ]);
        }
    }
}
