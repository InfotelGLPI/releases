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
use CommonGLPI;
use DbUtils;
use Document;
use Document_Item;
use Glpi\Application\View\TemplateRenderer;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

/**
 * Class Review
 */
class Review extends CommonDBTM
{
    public static $rightname = 'plugin_releases_releases';

    public static function getIcon()
    {
        return "ti ti-eye ";
    }
    /**
     * @param int $nb
     *
     * @return string
     */
    public static function getTypeName($nb = 0)
    {

        return _n('Review', 'Reviews', $nb, 'releases');
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {

        if ($item->getType() == Release::getType()) {
            return self::createTabEntry(self::getTypeName(1));
        }

        return '';
    }

    public static function countForItem(CommonDBTM $item)
    {
        $dbu   = new DbUtils();
        $table = CommonDBTM::getTable(Review::class);
        return $dbu->countElementsInTable(
            $table,
            ["plugin_releases_releases_id" => $item->getID()],
        );
    }


    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        global $CFG_GLPI;
        if ($item->getType() == Release::getType()) {
            $self = new self();
            if (self::canCreate()) {
                $review = new Review();
                if ($review->getFromDBByCrit(["plugin_releases_releases_id" => $item->getField('id')])) {
                    $ID = $review->getID();
                } else {
                    $ID = 0;
                }
                $self->showForm($ID, ['plugin_releases_releases_id' => $item->getField('id'),
                    'target'                      => $CFG_GLPI['root_doc'] . "/plugins/releases/front/review.form.php"]);
            }
        }
    }


    public function post_addItem()
    {
        // Add document if needed, without notification
        $this->input = $this->addFiles($this->input, ['force_update' => true]);

        $release = new Release();
        $release->getFromDB($this->input['plugin_releases_releases_id']);
        if ($release->getField('status') < Release::REVIEW) {
            $val           = [];
            $val['id']     = $release->getID();
            $val['status'] = Release::REVIEW;
            $release->update($val);
        }


    }

    public function post_updateItem($history = 1)
    {
        // Add document if needed, without notification
        $this->input = $this->addFiles($this->input, ['force_update' => true]);

    }

    /**
     * Actions done after the PURGE of the item in the database
     *
     * @return void
     **/
    public function post_purgeItem()
    {
        $release = new Release();
        $release->getFromDB($this->getField("plugin_releases_releases_id"));
        $val           = [];
        $val['id']     = $this->getField("plugin_releases_releases_id");
        $val['status'] = Release::FINALIZE;
        $release->update($val);
    }

    public function showForm($ID, $options = [])
    {
        global $CFG_GLPI;

        $this->initForm($ID, $options);

        $plugin_releases_releases_id = $options["plugin_releases_releases_id"]
           ?? $this->getField("plugin_releases_releases_id");

        // A locked real production run date must stay read-only (kept, not editable).
        $date_locked = ($this->getField("date_lock") == 1);

        // Build the attached-documents list for display: icon, links, image preview
        // and the per-document detach guard (UPDATE right on the document).
        $documents  = [];
        $foreignKey = "plugin_releases_reviews_id";
        $pics_url   = $CFG_GLPI['root_doc'] . "/pics/timeline";
        if ($this->getID() > 0) {
            $document_item = new Document_Item();
            $links         = $document_item->find(["itemtype" => self::getType(),
                "items_id" => $this->getID()]);
            $doc           = new Document();
            foreach ($links as $link_row) {
                if (!$doc->getFromDB($link_row["documents_id"])) {
                    continue;
                }
                $fields = $doc->fields;
                $ext    = strtolower(pathinfo($fields['filename'] ?? '', PATHINFO_EXTENSION));
                $icon   = file_exists(GLPI_ROOT . "/pics/icones/$ext-dist.png")
                   ? $CFG_GLPI['root_doc'] . "/pics/icones/$ext-dist.png"
                   : "$pics_url/file.png";
                $send_url = $CFG_GLPI['root_doc'] . "/front/document.send.php?docid=" . $fields['id']
                   . "&$foreignKey=" . $this->getID();
                $documents[] = [
                    'id'          => $fields['id'],
                    'filename'    => $fields['filename'],
                    'icon'        => $icon,
                    'url'         => $send_url,
                    'is_image'    => Document::isImage(GLPI_DOC_DIR . '/' . $fields['filepath']),
                    'preview_url' => $send_url . "&context=timeline",
                    'link'        => $fields['link'],
                    'link_name'   => $fields['name'],
                    'mime'        => $fields['mime'],
                    'show_url'    => Document::getFormURLWithID($fields['id']),
                    'can_update'  => $doc->can($fields['id'], UPDATE),
                ];
            }
        }

        $release = new Release();
        $release->getFromDB($plugin_releases_releases_id);
        $can_conclude = ($release->getField("status") == Release::REVIEW);

        TemplateRenderer::getInstance()->display('@releases/form_review.html.twig', [
            'item'                        => $this,
            'params'                      => $options,
            'plugin_releases_releases_id' => $plugin_releases_releases_id,
            'date_locked'                 => $date_locked,
            'documents'                   => $documents,
            'review_id'                   => $this->getID(),
            'form_url'                    => $this->getFormURL(),
            'can_conclude'                => $can_conclude,
        ]);

        return true;
    }

    public function prepareInputForAdd($input)
    {

        $release = new Release();
        $release->getFromDB($input["plugin_releases_releases_id"]);
        $input["entities_id"] = $release->getField("entities_id");

        if (empty($input["real_date_release"])) {
            $input["real_date_release"] = null;
        }
        return $input;
    }
}
