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

/**
 * TaskCategory class
**/
class PluginReleasesTestCategory extends CommonTreeDropdown {

   // From CommonDBTM
   public $dohistory          = true;
   public $can_be_translated  = true;

   static $rightname          = 'testcategory';

   function getAdditionalFields() {

      $tab = parent::getAdditionalFields();

      $tab[] = ['name'  => 'is_active',
                         'label' => __('Active'),
                         'type'  => 'bool'];

      return $tab;
   }


   function getSearchOptions() {

      $tab                      = parent::getSearchOptions();

      $tab[8]['table']         = $this->getTable();
      $tab[8]['field']         = 'is_active';
      $tab[8]['name']          = __('Active');
      $tab[8]['datatype']      = 'bool';

      return $tab;
   }


   static function getTypeName($nb=0) {
      return _n('Test category','Test categories', $nb);
   }

}
