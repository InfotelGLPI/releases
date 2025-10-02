<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2020 Teclib' and contributors.
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

namespace GlpiPlugin\Releases;

use CommonDBRelation;
use CommonDBTM;
use CommonGLPI;
use DbUtils;
use Dropdown;
use Html;
use Session;
use Toolbox;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Release_Item Class
 *
 *  Relation between Release and Items
 **/
class Release_Item extends CommonDBRelation {


   // From CommonDBRelation
   static public $itemtype_1 = Release::class;
   static public $items_id_1 = 'plugin_releases_releases_id';

   static public $itemtype_2         = 'itemtype';
   static public $items_id_2         = 'items_id';
   static public $checkItem_2_Rights = self::HAVE_VIEW_RIGHT_ON_ITEM;



    public static function getIcon()
    {
        return "ti ti-package";
    }

   /**
    * @since 0.84
    **/
   function getForbiddenStandardMassiveAction() {

      $forbidden   = parent::getForbiddenStandardMassiveAction();
      $forbidden[] = 'update';
      return $forbidden;
   }

   /**
    * Clean table when item is purged
    *
    * @param CommonDBTM|Object $item Object to use
    *
    * @return void
    */
   public static function cleanForItem(CommonDBTM $item) {

      $temp = new self();
      $temp->deleteByCriteria(
         ['itemtype' => $item->getType(),
          'items_id' => $item->getField('id')]
      );
   }

   /**
    * @see CommonDBTM::prepareInputForAdd()
    **/
   function prepareInputForAdd($input) {

      // Avoid duplicate entry
      if (countElementsInTable($this->getTable(), ['plugin_releases_releases_id' => $input['plugin_releases_releases_id'],
                                                   'itemtype'                    => $input['itemtype'],
                                                   'items_id'                    => $input['items_id']]) > 0) {
         return false;
      }
      return parent::prepareInputForAdd($input);
   }


   /**
    * Print the HTML array for Items linked to a problem
    *
    * @param $release Release object
    *
    * @return void
    **/
   static function showForRelease(Release $release) {
      $instID = $release->fields['id'];

      if (!$release->can($instID, READ)) {
         return false;
      }
      $canedit = $release->canEdit($instID);
      $rand    = mt_rand();

      $types_iterator = self::getDistinctTypes($instID);
      $number         = count($types_iterator);

      if ($canedit) {
         echo "<div class='firstbloc'>";
         echo "<form name='releaseitem_form$rand' id='releaseitem_form$rand' method='post'
                action='" . Toolbox::getItemTypeFormURL(__CLASS__) . "'>";

         echo "<table class='tab_cadre_fixe'>";
         echo "<tr class='tab_bg_2'><th colspan='2'>" . __('Add an item') . "</th></tr>";

         echo "<tr class='tab_bg_1'><td>";
         $types = [];
         //         foreach (Release::$typeslinkable as $key => $val) {
         foreach ($release->getAllTypesForHelpdesk() as $key => $val) {
            $types[] = $key;
         }
         Dropdown::showSelectItemFromItemtypes(['itemtypes'
                                                => $types,
                                                'entity_restrict'
                                                => ($release->fields['is_recursive']
                                                   ? getSonsOf('glpi_entities',
                                                               $release->fields['entities_id'])
                                                   : $release->fields['entities_id'])]);
         echo "</td><td class='center' width='30%'>";
         echo Html::submit(_sx('button', 'Add'), ['name' => 'add', 'class' => 'btn btn-primary']);
         echo Html::hidden('plugin_releases_releases_id', ['value' => $instID]);
         echo "</td></tr>";
         echo "</table>";
         Html::closeForm();
         echo "</div>";
      }

      echo "<div class='spaced'>";
      if ($canedit && $number) {
         Html::openMassiveActionsForm('mass' . __CLASS__ . $rand);
         $massiveactionparams = ['container' => 'mass' . __CLASS__ . $rand];
         Html::showMassiveActions($massiveactionparams);
      }
      echo "<table class='tab_cadre_fixehov'>";
      $header_begin  = "<tr>";
      $header_top    = '';
      $header_bottom = '';
      $header_end    = '';
      if ($canedit && $number) {
         $header_top    .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
         $header_top    .= "</th>";
         $header_bottom .= "<th width='10'>" . Html::getCheckAllAsCheckbox('mass' . __CLASS__ . $rand);
         $header_bottom .= "</th>";
      }
      $header_end .= "<th>" . __('Type') . "</th>";
      $header_end .= "<th>" . __('Entity') . "</th>";
      $header_end .= "<th>" . __('Name') . "</th>";
      $header_end .= "<th>" . __('Serial number') . "</th>";
      $header_end .= "<th>" . __('Inventory number') . "</th></tr>";
      echo $header_begin . $header_top . $header_end;

      $totalnb = 0;
      foreach ($types_iterator as $row) {
//      while ($row = $types_iterator->next()) {
         $itemtype = $row['itemtype'];
         if (!($item = getItemForItemtype($itemtype))) {
            continue;
         }

         if ($item->canView()) {
            $iterator = self::getTypeItems($instID, $itemtype);
            $nb       = count($iterator);

            $prem = true;
            foreach ($iterator as $data) {
               $name = $data["name"];
               if ($_SESSION["glpiis_ids_visible"]
                   || empty($data["name"])) {
                  $name = sprintf(__('%1$s (%2$s)'), $name, $data["id"]);
               }
               $link     = $itemtype::getFormURLWithID($data['id']);
               $namelink = "<a href=\"" . $link . "\">" . $name . "</a>";

               echo "<tr class='tab_bg_1'>";
               if ($canedit) {
                  echo "<td width='10'>";
                  Html::showMassiveActionCheckBox(__CLASS__, $data["linkid"]);
                  echo "</td>";
               }
               if ($prem) {
                  $typename = $item->getTypeName($nb);
                  echo "<td class='center top' rowspan='$nb'>" .
                       (($nb > 1) ? sprintf(__('%1$s: %2$s'), $typename, $nb) : $typename) . "</td>";
                  $prem = false;
               }
               echo "<td class='center'>";
               echo Dropdown::getDropdownName("glpi_entities", $data['entity']) . "</td>";
               echo "<td class='center" .
                    (isset($data['is_deleted']) && $data['is_deleted'] ? " tab_bg_2_2'" : "'");
               echo ">" . $namelink . "</td>";
               echo "<td class='center'>" . (isset($data["serial"]) ? "" . $data["serial"] . "" : "-") .
                    "</td>";
               echo "<td class='center'>" .
                    (isset($data["otherserial"]) ? "" . $data["otherserial"] . "" : "-") . "</td>";
               echo "</tr>";
            }
            $totalnb += $nb;
         }
         $types_iterator->next();
      }

      if ($number) {
         echo $header_begin . $header_bottom . $header_end;
      }

      echo "</table>";
      if ($canedit && $number) {
         $massiveactionparams['ontop'] = false;
         Html::showMassiveActions($massiveactionparams);
         Html::closeForm();
      }
      echo "</div>";
   }

   static function countForItem(CommonDBTM $item) {
      $dbu = new DbUtils();

      if ($item->getType() == 'User') {
         return $dbu->countElementsInTable(getTableForItemType(Release_User::class),
                                           ["users_id" => $item->getID()]);
      } else if ($item->getType() == 'Group') {
         return $dbu->countElementsInTable(getTableForItemType(Group_Release::class),
                                           ["groups_id" => $item->getID()]);
      } else if ($item->getType() == 'Supplier') {
         return $dbu->countElementsInTable(getTableForItemType(Release_Supplier::class),
                                           ["suppliers_id" => $item->getID()]);
      } else {
         $table = getTableForItemType(Release_Item::class);
         return $dbu->countElementsInTable($table,
                                           ["items_id" => $item->getID(),
                                            "itemtype" => $item->getType()]);
      }
   }

   static function countReleaseForItem(CommonDBTM $item) {
      $dbu   = new DbUtils();
      $table = CommonDBTM::getTable(Release_Item::class);
      return $dbu->countElementsInTable($table,
                                        ["plugin_releases_releases_id" => $item->getID()]);
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if (!$withtemplate) {
         $nb = 0;
         switch ($item->getType()) {
             case Release::class :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = self::countReleaseForItem($item);
               }
               return self::createTabEntry(_n('Item', 'Items', Session::getPluralNumber()), $nb);
               break;
            case 'User' :
            case 'Group' :
            case 'Supplier' :
               if ($_SESSION['glpishow_count_on_tabs']) {
                  $nb = self::countForItem($item);
               }
               return self::createTabEntry(Release::getTypeName(Session::getPluralNumber()), $nb);
               break;
            default :
               if (Session::haveRight("plugin_releases_releases", READ)) {
                  if ($_SESSION['glpishow_count_on_tabs']) {
                     // Direct one
                     $nb = self::countForItem($item);
                     // Linked items
                     $linkeditems = $item->getLinkedItems();

                     if (count($linkeditems)) {
                        foreach ($linkeditems as $type => $tab) {
                           $typeitem = new $type;
                           foreach ($tab as $ID) {
                              $typeitem->getFromDB($ID);
                              $nb += self::countForItem($typeitem);
                           }
                        }
                     }
                  }
                  return self::createTabEntry(Release::getTypeName(Session::getPluralNumber()), $nb);
               }
               break;
         }
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      switch ($item->getType()) {
         case Release::class :
            self::showForRelease($item);
            break;

         default :
            Release::showListForItem($item, $withtemplate);
      }
      return true;
   }

}
