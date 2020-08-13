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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}


/**
 * NotificationTargetChange Class
 *
 * @since 0.85
 **/
class PluginReleasesNotificationTargetRelease extends NotificationTargetCommonITILObject {

   public $private_profiles = [];

   /**
    * Get events related to tickets
    **/
   function getEvents() {

      $events = ['newRelease'    => __('New release', 'releases'),
                 'updateRelease' => __('Update of a release', 'releases'),
                 'closedRelease' => __('Closure of a releases', 'releases'),
                 'deleteRelease' => __('Deleting a releases', 'releases')];

      $events = array_merge($events, parent::getEvents());
      asort($events);
      return $events;
   }


   function getDataForObject(CommonDBTM $item, array $options, $simple = false) {
      global $CFG_GLPI, $DB;
      // Common ITIL data
      //      $data = parent::getDataForObject($item, $options, $simple);
      $objettype = strtolower($item->getType());


      $data["##$objettype.title##"]       = $item->getField('name');
      $data["##$objettype.content##"]     = $item->getField('content');
      $data["##$objettype.description##"] = $item->getField('content');
      $data["##$objettype.id##"]          = sprintf("%07d", $item->getField("id"));


      $data["##review.realproductiondate##"]  = "";
      $data["##review.conformrealization##"]  = "";
      $data["##review.name##"]                = "";
      $data["##review.incident##"]            = "";
      $data["##review.incidentdescription##"] = "";

      $review = new PluginReleasesReview();
      if ($review->getFromDBByCrit(["plugin_releases_releases_id" => $item->getField('id')])) {
         $data["##review.realproductiondate##"]  = Html::convDateTime($review->getField("real_date_release"));
         $data["##review.conformrealization##"]  = Dropdown::getYesNo($review->getField('conforming_realization'));
         $data["##review.name##"]                = Html::clean($review->getField('name'));
         $data["##review.incident##"]            = Dropdown::getYesNo($review->getField('incident'));
         $data["##review.incidentdescription##"] = Html::clean($review->getField('incident_description'));
      }
      $data["##$objettype.url##"]
         = $this->formatURL($options['additionnaloption']['usertype'],
                            $objettype . "_" . $item->getField("id"));


      $entity = new Entity();
      if ($entity->getFromDB($this->getEntity())) {
         $data["##$objettype.entity##"]          = $entity->getField('completename');
         $data["##$objettype.shortentity##"]     = $entity->getField('name');
         $data["##$objettype.entity.phone##"]    = $entity->getField('phonenumber');
         $data["##$objettype.entity.fax##"]      = $entity->getField('fax');
         $data["##$objettype.entity.website##"]  = $entity->getField('website');
         $data["##$objettype.entity.email##"]    = $entity->getField('email');
         $data["##$objettype.entity.address##"]  = $entity->getField('address');
         $data["##$objettype.entity.postcode##"] = $entity->getField('postcode');
         $data["##$objettype.entity.town##"]     = $entity->getField('town');
         $data["##$objettype.entity.state##"]    = $entity->getField('state');
         $data["##$objettype.entity.country##"]  = $entity->getField('country');
      }

      $data["##$objettype.storestatus##"] = $item->getField('status');
      $data["##$objettype.status##"]      = $item->getStatus($item->getField('status'));


      $data["##$objettype.creationdate##"] = Html::convDateTime($item->getField('date'));
      $data["##$objettype.closedate##"]    = Html::convDateTime($item->getField('end_date'));


      $data["##$objettype.authors##"] = '';
      $data['authors']                = [];
      if ($item->countUsers(CommonITILActor::REQUESTER)) {
         $users = [];
         foreach ($item->getUsers(CommonITILActor::REQUESTER) as $tmpusr) {
            $uid      = $tmpusr['users_id'];
            $user_tmp = new User();
            if ($uid
                && $user_tmp->getFromDB($uid)) {
               $users[] = $user_tmp->getName();

               $tmp                    = [];
               $tmp['##author.id##']   = $uid;
               $tmp['##author.name##'] = $user_tmp->getName();

               if ($user_tmp->getField('locations_id')) {
                  $tmp['##author.location##']
                     = Dropdown::getDropdownName('glpi_locations',
                                                 $user_tmp->getField('locations_id'));
               } else {
                  $tmp['##author.location##'] = '';
               }

               if ($user_tmp->getField('usertitles_id')) {
                  $tmp['##author.title##']
                     = Dropdown::getDropdownName('glpi_usertitles',
                                                 $user_tmp->getField('usertitles_id'));
               } else {
                  $tmp['##author.title##'] = '';
               }

               if ($user_tmp->getField('usercategories_id')) {
                  $tmp['##author.category##']
                     = Dropdown::getDropdownName('glpi_usercategories',
                                                 $user_tmp->getField('usercategories_id'));
               } else {
                  $tmp['##author.category##'] = '';
               }

               $tmp['##author.email##']  = $user_tmp->getDefaultEmail();
               $tmp['##author.mobile##'] = $user_tmp->getField('mobile');
               $tmp['##author.phone##']  = $user_tmp->getField('phone');
               $tmp['##author.phone2##'] = $user_tmp->getField('phone2');
               $data['authors'][]        = $tmp;
            } else {
               // Anonymous users only in xxx.authors, not in authors
               $users[] = $tmpusr['alternative_email'];
            }
         }
         $data["##$objettype.authors##"] = implode(', ', $users);
      }

      $data["##$objettype.suppliers##"] = '';
      $data['suppliers']                = [];
      if ($item->countSuppliers(CommonITILActor::ASSIGN)) {
         $suppliers = [];
         foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $tmpspplier) {
            $sid      = $tmpspplier['suppliers_id'];
            $supplier = new Supplier();
            if ($sid
                && $supplier->getFromDB($sid)) {
               $suppliers[] = $supplier->getName();

               $tmp                          = [];
               $tmp['##supplier.id##']       = $sid;
               $tmp['##supplier.name##']     = $supplier->getName();
               $tmp['##supplier.email##']    = $supplier->getField('email');
               $tmp['##supplier.phone##']    = $supplier->getField('phonenumber');
               $tmp['##supplier.fax##']      = $supplier->getField('fax');
               $tmp['##supplier.website##']  = $supplier->getField('website');
               $tmp['##supplier.email##']    = $supplier->getField('email');
               $tmp['##supplier.address##']  = $supplier->getField('address');
               $tmp['##supplier.postcode##'] = $supplier->getField('postcode');
               $tmp['##supplier.town##']     = $supplier->getField('town');
               $tmp['##supplier.state##']    = $supplier->getField('state');
               $tmp['##supplier.country##']  = $supplier->getField('country');
               $tmp['##supplier.comments##'] = $supplier->getField('comment');

               $tmp['##supplier.type##'] = '';
               if ($supplier->getField('suppliertypes_id')) {
                  $tmp['##supplier.type##']
                     = Dropdown::getDropdownName('glpi_suppliertypes',
                                                 $supplier->getField('suppliertypes_id'));
               }

               $data['suppliers'][] = $tmp;
            }
         }
         $data["##$objettype.suppliers##"] = implode(', ', $suppliers);
      }

      $data["##$objettype.openbyuser##"] = '';
      if ($item->getField('users_id_recipient')) {
         $user_tmp = new User();
         $user_tmp->getFromDB($item->getField('users_id_recipient'));
         $data["##$objettype.openbyuser##"] = $user_tmp->getName();
      }

      $data["##$objettype.lastupdater##"] = '';
      if ($item->getField('users_id_lastupdater')) {
         $user_tmp = new User();
         $user_tmp->getFromDB($item->getField('users_id_lastupdater'));
         $data["##$objettype.lastupdater##"] = $user_tmp->getName();
      }

      $data["##$objettype.assigntousers##"] = '';
      if ($item->countUsers(CommonITILActor::ASSIGN)) {
         $users = [];
         foreach ($item->getUsers(CommonITILActor::ASSIGN) as $tmp) {
            $uid      = $tmp['users_id'];
            $user_tmp = new User();
            if ($user_tmp->getFromDB($uid)) {
               $users[$uid] = $user_tmp->getName();
            }
         }
         $data["##$objettype.assigntousers##"] = implode(', ', $users);
      }

      $data["##$objettype.assigntosupplier##"] = '';
      if ($item->countSuppliers(CommonITILActor::ASSIGN)) {
         $suppliers = [];
         foreach ($item->getSuppliers(CommonITILActor::ASSIGN) as $tmp) {
            $uid          = $tmp['suppliers_id'];
            $supplier_tmp = new Supplier();
            if ($supplier_tmp->getFromDB($uid)) {
               $suppliers[$uid] = $supplier_tmp->getName();
            }
         }
         $data["##$objettype.assigntosupplier##"] = implode(', ', $suppliers);
      }

      $data["##$objettype.groups##"] = '';
      if ($item->countGroups(CommonITILActor::REQUESTER)) {
         $groups = [];
         foreach ($item->getGroups(CommonITILActor::REQUESTER) as $tmp) {
            $gid          = $tmp['groups_id'];
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $data["##$objettype.groups##"] = implode(', ', $groups);
      }

      $data["##$objettype.observergroups##"] = '';
      if ($item->countGroups(CommonITILActor::OBSERVER)) {
         $groups = [];
         foreach ($item->getGroups(CommonITILActor::OBSERVER) as $tmp) {
            $gid          = $tmp['groups_id'];
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $data["##$objettype.observergroups##"] = implode(', ', $groups);
      }

      $data["##$objettype.observerusers##"] = '';
      if ($item->countUsers(CommonITILActor::OBSERVER)) {
         $users = [];
         foreach ($item->getUsers(CommonITILActor::OBSERVER) as $tmp) {
            $uid      = $tmp['users_id'];
            $user_tmp = new User();
            if ($uid
                && $user_tmp->getFromDB($uid)) {
               $users[] = $user_tmp->getName();
            } else {
               $users[] = $tmp['alternative_email'];
            }
         }
         $data["##$objettype.observerusers##"] = implode(', ', $users);
      }

      $data["##$objettype.assigntogroups##"] = '';
      if ($item->countGroups(CommonITILActor::ASSIGN)) {
         $groups = [];
         foreach ($item->getGroups(CommonITILActor::ASSIGN) as $tmp) {
            $gid          = $tmp['groups_id'];
            $groups[$gid] = Dropdown::getDropdownName('glpi_groups', $gid);
         }
         $data["##$objettype.assigntogroups##"] = implode(', ', $groups);
      }


      // Complex mode
      if (!$simple) {
         $followup_restrict             = [];
         $followup_restrict['items_id'] = $item->getField('id');
         if (!isset($options['additionnaloption']['show_private'])
             || !$options['additionnaloption']['show_private']) {
            $followup_restrict['is_private'] = 0;
         }
         $followup_restrict['itemtype'] = $objettype;
         $dbu = new DbUtils();

         //Followup infos
         $followups         = $dbu->getAllDataFromTable(
            'glpi_itilfollowups', [
                                   'WHERE' => $followup_restrict,
                                   'ORDER' => ['date_mod DESC', 'id ASC']
                                ]
         );
         $data['followups'] = [];
         foreach ($followups as $followup) {
            $tmp                           = [];
            $tmp['##followup.isprivate##'] = Dropdown::getYesNo($followup['is_private']);

            // Check if the author need to be anonymized
            if (Entity::getUsedConfig('anonymize_support_agents', $item->getField('entities_id'))
                && ITILFollowup::getById($followup['id'])->isFromSupportAgent()
            ) {
               $tmp['##followup.author##'] = __("Helpdesk");
            } else {
               $tmp['##followup.author##'] = Html::clean(getUserName($followup['users_id']));
            }

            $tmp['##followup.requesttype##'] = Dropdown::getDropdownName('glpi_requesttypes',
                                                                         $followup['requesttypes_id']);
            $tmp['##followup.date##']        = Html::convDateTime($followup['date']);
            $tmp['##followup.description##'] = $followup['content'];

            $data['followups'][] = $tmp;
         }

         $data["##$objettype.numberoffollowups##"] = count($data['followups']);

         $data['log'] = [];
         // Use list_limit_max or load the full history ?
         foreach (Log::getHistoryData($item, 0, $CFG_GLPI['list_limit_max']) as $log) {
            $tmp                               = [];
            $tmp["##$objettype.log.date##"]    = $log['date_mod'];
            $tmp["##$objettype.log.user##"]    = $log['user_name'];
            $tmp["##$objettype.log.field##"]   = $log['field'];
            $tmp["##$objettype.log.content##"] = $log['change'];
            $data['log'][]                     = $tmp;
         }

         $data["##$objettype.numberoflogs##"] = count($data['log']);

         //TODO Comment document
         // Document

         $iterator = $DB->request([
                                     'SELECT'    => 'glpi_documents.*',
                                     'FROM'      => 'glpi_documents',
                                     'LEFT JOIN' => [
                                        'glpi_documents_items' => [
                                           'ON' => [
                                              'glpi_documents_items' => 'documents_id',
                                              'glpi_documents'       => 'id'
                                           ]
                                        ]
                                     ],
                                     'WHERE'     => [
                                        $item->getAssociatedDocumentsCriteria(),
                                        'timeline_position' => ['>', CommonITILObject::NO_TIMELINE], // skip inlined images
                                     ]
                                  ]);

         $data["documents"] = [];
         $addtodownloadurl  = '';
         if ($item->getType() == 'Ticket') {
            $addtodownloadurl = "%2526tickets_id=" . $item->fields['id'];
         }
         while ($row = $iterator->next()) {
            $tmp                      = [];
            $tmp['##document.id##']   = $row['id'];
            $tmp['##document.name##'] = $row['name'];
            $tmp['##document.weblink##']
                                      = $row['link'];

            $tmp['##document.url##'] = $this->formatURL($options['additionnaloption']['usertype'],
                                                        "document_" . $row['id']);
            $downloadurl             = "/front/document.send.php?docid=" . $row['id'];

            $tmp['##document.downloadurl##']
               = $this->formatURL($options['additionnaloption']['usertype'],
                                  $downloadurl . $addtodownloadurl);
            $tmp['##document.heading##']
               = Dropdown::getDropdownName('glpi_documentcategories',
                                           $row['documentcategories_id']);

            $tmp['##document.filename##']
               = $row['filename'];

            $data['documents'][] = $tmp;
         }

         $data["##$objettype.urldocument##"]
            = $this->formatURL($options['additionnaloption']['usertype'],
                               $objettype . "_" . $item->getField("id") . '_Document_Item$1');

         $data["##$objettype.numberofdocuments##"]
            = count($data['documents']);


         //Task infos
         $tasktype = PluginReleasesDeploytask::getType();
         $taskobj  = new $tasktype();
         $restrict = [$item->getForeignKeyField() => $item->getField('id')];


         $tasks         = $dbu->getAllDataFromTable(
            $taskobj->getTable(), [
                                   'WHERE' => $restrict,
                                   'ORDER' => ['date_mod DESC', 'id ASC']
                                ]
         );
         $data['tasks'] = [];
         foreach ($tasks as $task) {
            $tmp                = [];
            $tmp['##task.id##'] = $task['id'];

            $tmp['##task.author##'] = Html::clean(getUserName($task['users_id']));
            $tmp['##task.name##']   = Html::clean($task['name']);

            $tmp_taskcatinfo      = Dropdown::getDropdownName('glpi_plugin_releases_typedeploytasks',
                                                              $task['plugin_releases_typedeploytasks_id'], true, true, false);
            $tmp['##task.type##'] = $tmp_taskcatinfo['name'];

            $tmp['##task.date##']        = Html::convDateTime($task['date']);
            $tmp['##task.description##'] = $task['content'];
            $tmp['##task.time##']        = Ticket::getActionTime($task['actiontime']);
            $tmp['##task.status##']      = PluginReleasesDeploytask::getState($task['state']);

            $tmp['##task.user##']  = Html::clean(getUserName($task['users_id_tech']));
            $tmp['##task.group##']
                                   = Html::clean(Toolbox::clean_cross_side_scripting_deep(Dropdown::getDropdownName("glpi_groups",
                                                                                                                    $task['groups_id_tech'])), true, 2, false);
            $tmp['##task.begin##'] = "";
            $tmp['##task.end##']   = "";
            if (!is_null($task['begin'])) {
               $tmp['##task.begin##'] = Html::convDateTime($task['begin']);
               $tmp['##task.end##']   = Html::convDateTime($task['end']);
            }

            $data['tasks'][] = $tmp;
         }

         $data["##$objettype.numberoftasks##"] = count($data['tasks']);

         //Risk infos
         $risktype = PluginReleasesRisk::getType();
         $riskobj  = new $risktype();
         $restrict = [$item->getForeignKeyField() => $item->getField('id')];


         $risks         = $dbu->getAllDataFromTable(
            $riskobj->getTable(), [
                                   'WHERE' => $restrict,
                                   'ORDER' => ['date_mod DESC', 'id ASC']
                                ]
         );
         $data['risks'] = [];
         foreach ($risks as $risk) {
            $tmp                = [];
            $tmp['##risk.id##'] = $risk['id'];

            $tmp['##risk.author##'] = Html::clean(getUserName($risk['users_id']));
            $tmp['##risk.name##']   = Html::clean($risk['name']);

            $tmp_taskcatinfo      = Dropdown::getDropdownName('glpi_plugin_releases_typerisks',
                                                              $risk['plugin_releases_typerisks_id'], true, true, false);
            $tmp['##risk.type##'] = $tmp_taskcatinfo['name'];

            $tmp['##risk.date##']        = Html::convDateTime($risk['date_creation']);
            $tmp['##risk.description##'] = $risk['content'];
            $tmp['##risk.status##']      = Planning::getState($risk['state']);

            $data['risks'][] = $tmp;
         }

         $data["##$objettype.numberofrisks##"] = count($data['risks']);

         //Rollback infos
         $rollbacktype = PluginReleasesRollback::getType();
         $rollbackobj  = new $rollbacktype();
         $restrict     = [$item->getForeignKeyField() => $item->getField('id')];


         $rollbacks         = $dbu->getAllDataFromTable(
            $rollbackobj->getTable(), [
                                       'WHERE' => $restrict,
                                       'ORDER' => ['date_mod DESC', 'id ASC']
                                    ]
         );
         $data['rollbacks'] = [];
         foreach ($rollbacks as $rollback) {
            $tmp                    = [];
            $tmp['##rollback.id##'] = $rollback['id'];

            $tmp['##rollback.author##'] = Html::clean(getUserName($rollback['users_id']));
            $tmp['##rollback.name##']   = Html::clean($rollback['name']);


            $tmp['##rollback.date##']        = Html::convDateTime($rollback['date_creation']);
            $tmp['##rollback.description##'] = $rollback['content'];
            $tmp['##rollback.status##']      = Planning::getState($rollback['state']);

            $data['rollbacks'][] = $tmp;
         }

         $data["##$objettype.numberofrollbacks##"] = count($data['rollbacks']);

         //Test infos
         $testtype = PluginReleasesTest::getType();
         $testobj  = new $testtype();
         $restrict = [$item->getForeignKeyField() => $item->getField('id')];


         $tests         = $dbu->getAllDataFromTable(
            $testobj->getTable(), [
                                   'WHERE' => $restrict,
                                   'ORDER' => ['date_mod DESC', 'id ASC']
                                ]
         );
         $data['tests'] = [];
         foreach ($tests as $test) {
            $tmp                = [];
            $tmp['##test.id##'] = $test['id'];

            $tmp['##test.author##'] = Html::clean(getUserName($test['users_id']));
            $tmp['##test.name##']   = Html::clean($test['name']);
            $tmp_taskcatinfo        = Dropdown::getDropdownName('glpi_plugin_releases_typetests',
                                                                $risk['plugin_releases_typetests_id'], true, true, false);
            $tmp['##risk.type##']   = $tmp_taskcatinfo['name'];

            $tmp['##test.date##']        = Html::convDateTime($test['date_creation']);
            $tmp['##test.description##'] = $test['content'];
            $tmp['##test.status##']      = $testtype::getState($test['state']);

            $data['tests'][] = $tmp;
         }

         $data["##$objettype.numberoftests##"] = count($data['tests']);
      }


      // TODO Specific data for release


      $data["##$objettype.datepreproduction##"]      = Html::convDateTime($item->getField("date_preproduction"));
      $data["##$objettype.dateproduction##"]         = Html::convDateTime($item->getField("date_production"));
      $data["##$objettype.serviceshutdown##"]        = Dropdown::getYesNo($item->getField("service_shutdown"));
      $data["##$objettype.serviceshutdowndetails##"] = "";
      if ($item->getField("service_shutdown")) {
         $data["##$objettype.serviceshutdowndetails##"] = $item->getField("service_shutdown_details");
      }

      $data["##$objettype.hourtype##"]      = Dropdown::getYesNo($item->getField("hour_type"));
      $data["##$objettype.communication##"] = Dropdown::getYesNo($item->getField("communication"));
      if ($item->getField("communication")) {
         $data["##$objettype.communicationtype##"] = $item->getField("communication_type");
         $targets                                  = [];
         $ie                                       = $item->getField("communication_type");
         $obj                                      = new $ie;
         $t                                        = json_decode($item->getField("target"));
         foreach ($t as $target => $val) {
            $targets[] = $obj->getName($val);
         }
         $data["##$objettype.target##"] = implode(', ', $targets);
      }
      $data["##$objettype.location##"] = "";
      if ($item->getField('locations_id') != NOT_AVAILABLE) {
         $data["##$objettype.location##"] = Dropdown::getDropdownName("glpi_locations", $item->getField("locations_id"));
      }


      // Complex mode
      if (!$simple) {
         $restrict = ['plugin_releases_releases_id' => $item->getField('id')];


         $items = $dbu->getAllDataFromTable('glpi_plugin_releases_releases_items', $restrict);

         $data['items'] = [];
         if (count($items)) {
            foreach ($items as $row) {
               if ($item2 = getItemForItemtype($row['itemtype'])) {
                  if ($item2->getFromDB($row['items_id'])) {
                     $tmp                         = [];
                     $tmp['##item.itemtype##']    = $item2->getTypeName();
                     $tmp['##item.name##']        = $item2->getField('name');
                     $tmp['##item.serial##']      = $item2->getField('serial');
                     $tmp['##item.otherserial##'] = $item2->getField('otherserial');
                     $tmp['##item.contact##']     = $item2->getField('contact');
                     $tmp['##item.contactnum##']  = $item2->getField('contactnum');
                     $tmp['##item.location##']    = '';
                     $tmp['##item.user##']        = '';
                     $tmp['##item.group##']       = '';
                     $tmp['##item.model##']       = '';

                     //Object location
                     if ($item2->getField('locations_id') != NOT_AVAILABLE) {
                        $tmp['##item.location##']
                           = Dropdown::getDropdownName('glpi_locations',
                                                       $item2->getField('locations_id'));
                     }

                     //Object user
                     if ($item2->getField('users_id')) {
                        $user_tmp = new User();
                        if ($user_tmp->getFromDB($item2->getField('users_id'))) {
                           $tmp['##item.user##'] = $user_tmp->getName();
                        }
                     }

                     //Object group
                     if ($item2->getField('groups_id')) {
                        $tmp['##item.group##']
                           = Dropdown::getDropdownName('glpi_groups',
                                                       $item2->getField('groups_id'));
                     }

                     $modeltable = getSingular($item2->getTable()) . "models";
                     $modelfield = getForeignKeyFieldForTable($modeltable);

                     if ($item2->isField($modelfield)) {
                        $tmp['##item.model##'] = $item2->getField($modelfield);
                     }

                     $data['items'][] = $tmp;
                  }
               }
            }
         }

         $data['##change.numberofitems##'] = count($data['items']);


         $restrict = ['plugin_releases_releases_id' => $item->getField('id')];


         $changes         = $dbu->getAllDataFromTable('glpi_plugin_releases_changes_releases', $restrict);
         $data['changes'] = [];
         if (count($changes)) {
            $change = new Change();
            foreach ($changes as $row) {
               if ($change->getFromDB($row['changes_id'])) {
                  $tmp = [];

                  $tmp['##change.id##']
                     = $row['changes_id'];
                  $tmp['##change.date##']
                     = $change->getField('date');
                  $tmp['##change.title##']
                     = $change->getField('name');
                  $tmp['##change.url##']
                     = $this->formatURL($options['additionnaloption']['usertype'],
                                        "change_" . $row['changes_id']);
                  $tmp['##change.content##']
                     = $change->getField('content');

                  $data['changes'][] = $tmp;
               }
            }
         }

         $data['##release.numberofchanges##'] = count($data['changes']);

      }
      return $data;
   }


   function getTags() {

      //      parent::getTags();
      //TODO change for release

      $itemtype  = $this->obj->getType();
      $objettype = strtolower($itemtype);
      //Locales
      $tags = [
         $objettype . '.numberofchanges' => _x('quantity', 'Number of changes'),

         'item.name'                       => __('Associated item'),
         'item.serial'                     => __('Serial number'),
         'item.otherserial'                => __('Inventory number'),
         'item.location'                   => __('Location'),
         'item.model'                      => __('Model'),
         'item.contact'                    => __('Alternate username'),
         'item.contactnumber'              => __('Alternate username number'),
         'item.user'                       => __('User'),
         'item.group'                      => __('Group'),
         'risk.author'                     => __('Writer'),
         'risk.name'                       => __('Name'),
         'risk.date'                       => __('Opening date'),
         'risk.description'                => __('Description'),
         'risk.type'                       => _n('Risk type', 'Risk types', 1, 'releases'),
         'risk.state'                      => __('State'),
         $objettype . '.numberofrisks'     => _x('quantity', 'Number of risks', 'releases'),
         'rollback.name'                   => __('Name'),
         'rollback.author'                 => __('Writer'),
         'rollback.date'                   => __('Opening date'),
         'rollback.description'            => __('Description'),
         'rollback.state'                  => __('State'),
         $objettype . '.numberofrollbacks' => _x('quantity', 'Number of rollbacks', 'releases'),
         'test.name'                       => __('Name'),
         'test.author'                     => __('Writer'),
         'test.date'                       => __('Opening date'),
         'test.description'                => __('Description'),
         'test.type'                       => _n('Test type', 'Test types', 1, 'releases'),
         'test.status'                     => __('Status'),
         $objettype . '.numberoftests'     => _x('quantity', 'Number of tests', 'releases'),
         "review.realproductiondate"       => __("Real production run date", 'releases'),
         "review.conformrealization"       => __('Conforming realization', 'releases'),
         "review.name"                     => __('Name'),
         "review.incident"                 => __('Incidents during process', 'releases'),
         "review.incidentdescription"      => __('Description'),
      ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'    => $tag,
                              'label'  => $label,
                              'value'  => true,
                              'events' => NotificationTarget::TAG_FOR_ALL_EVENTS]);
      }


      //Foreach global tags
      $tags = [
         'items'     => _n('Item', 'Items', Session::getPluralNumber()),
         'changes'   => _n('Change', 'Changes', Session::getPluralNumber()),
         'risks'     => _n('Risk', 'Risks', Session::getPluralNumber(), 'releases'),
         'rollbacks' => _n('Rollback', 'Rollbacks', Session::getPluralNumber(), 'releases'),
         'tests'     => _n('Rollback', 'Rollbacks', Session::getPluralNumber(), 'releases'),
         'documents' => _n('Document', 'Documents', Session::getPluralNumber())];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'     => $tag,
                              'label'   => $label,
                              'value'   => false,
                              'foreach' => true]);
      }

      //Tags with just lang
      $tags = [
         $objettype . '.changes' => _n('Change', 'Changes', Session::getPluralNumber()),
         'items'                 => _n('Item', 'Items', Session::getPluralNumber())];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                              'label' => $label,
                              'value' => false,
                              'lang'  => true]);
      }


      //TODO


      //Locales
      $tags = [$objettype . '.id'                 => __('ID'),
               $objettype . '.title'              => __('Title'),
               $objettype . '.url'                => __('URL'),
               $objettype . '.category'           => __('Category'),
               $objettype . '.content'            => __('Description'),
               $objettype . '.description'        => sprintf(__('%1$s: %2$s'), $this->obj->getTypeName(1),
                                                             __('Description')),
               $objettype . '.status'             => __('Status'),
               $objettype . '.time'               => __('Total duration'),
               $objettype . '.creationdate'       => __('Opening date'),
               $objettype . '.closedate'          => __('Closing date'),
               $objettype . '.authors'            => _n('Requester', 'Requesters', Session::getPluralNumber()),
               'author.id'                        => __('Requester ID'),
               'author.name'                      => __('Requester'),
               'author.location'                  => __('Requester location'),
               'author.mobile'                    => __('Mobile phone'),
               'author.phone'                     => __('Phone'),
               'author.phone2'                    => __('Phone 2'),
               'author.email'                     => _n('Email', 'Emails', 1),
               'author.title'                     => _x('person', 'Title'),
               'author.category'                  => __('Category'),
               $objettype . '.suppliers'          => _n('Supplier', 'Suppliers', Session::getPluralNumber()),
               'supplier.id'                      => __('Supplier ID'),
               'supplier.name'                    => __('Supplier'),
               'supplier.phone'                   => __('Phone'),
               'supplier.fax'                     => __('Fax'),
               'supplier.website'                 => __('Website'),
               'supplier.email'                   => __('Email'),
               'supplier.address'                 => __('Address'),
               'supplier.postcode'                => __('Postal code'),
               'supplier.town'                    => __('City'),
               'supplier.state'                   => _x('location', 'State'),
               'supplier.country'                 => __('Country'),
               'supplier.comments'                => _n('Comment', 'Comments', 2),
               'supplier.type'                    => __('Third party type'),
               $objettype . '.openbyuser'         => __('Writer'),
               $objettype . '.lastupdater'        => __('Last updater'),
               $objettype . '.assigntousers'      => __('Assigned to technicians'),
               $objettype . '.assigntosupplier'   => __('Assigned to a supplier'),
               $objettype . '.groups'             => _n('Requester group',
                                                        'Requester groups', Session::getPluralNumber()),
               $objettype . '.observergroups'     => _n('Watcher group', 'Watcher groups', Session::getPluralNumber()),
               $objettype . '.assigntogroups'     => __('Assigned to groups'),
               $objettype . '.observerusers'      => _n('Watcher', 'Watchers', Session::getPluralNumber()),
               $objettype . '.action'             => _n('Event', 'Events', 1),
               'followup.date'                    => __('Opening date'),
               'followup.isprivate'               => __('Private'),
               'followup.author'                  => __('Writer'),
               'followup.description'             => __('Description'),
               'followup.requesttype'             => __('Request source'),
               $objettype . '.numberoffollowups'  => _x('quantity', 'Number of followups'),
               $objettype . '.numberofunresolved' => __('Number of unresolved items'),
               $objettype . '.numberofdocuments'  => _x('quantity', 'Number of documents'),
               'task.author'                      => __('Writer'),
               'task.name'                        => __('Name'),
               'task.isprivate'                   => __('Private'),
               'task.date'                        => __('Opening date'),
               'task.description'                 => __('Description'),
               'task.type'                        => _n('Deploy task type', 'Deploy task types', 1, 'releases'),
               'task.time'                        => __('Total duration'),
               'task.user'                        => __('User assigned to task'),
               'task.group'                       => __('Group assigned to task'),
               'task.begin'                       => __('Start date'),
               'task.end'                         => __('End date'),
               'task.status'                      => __('Status'),
               $objettype . '.numberoftasks'      => _x('quantity', 'Number of tasks', 'releases'),
               $objettype . '.entity.phone'       => sprintf(__('%1$s (%2$s)'),
                                                             __('Entity'), __('Phone')),
               $objettype . '.entity.fax'         => sprintf(__('%1$s (%2$s)'),
                                                             __('Entity'), __('Fax')),
               $objettype . '.entity.website'     => sprintf(__('%1$s (%2$s)'),
                                                             __('Entity'), __('Website')),
               $objettype . '.entity.email'       => sprintf(__('%1$s (%2$s)'),
                                                             __('Entity'), __('Email')),
               $objettype . '.entity.address'     => sprintf(__('%1$s (%2$s)'),
                                                             __('Entity'), __('Address')),
               $objettype . '.entity.postcode'    => sprintf(__('%1$s (%2$s)'),
                                                             __('Entity'), __('Postal code')),
               $objettype . '.entity.town'        => sprintf(__('%1$s (%2$s)'),
                                                             __('Entity'), __('City')),
               $objettype . '.entity.state'       => sprintf(__('%1$s (%2$s)'),
                                                             __('Entity'), _x('location', 'State')),
               $objettype . '.entity.country'     => sprintf(__('%1$s (%2$s)'),
                                                             __('Entity'), __('Country')),
      ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'    => $tag,
                              'label'  => $label,
                              'value'  => true,
                              'events' => parent::TAG_FOR_ALL_EVENTS]);
      }

      //Foreach global tags
      $tags = ['log'       => __('Historical'),
               'followups' => _n('Followup', 'Followups', Session::getPluralNumber()),
               'tasks'     => _n('Deploy task', 'Deploy tasks', Session::getPluralNumber(), 'releases'),
               'tests'     => _n('Test', 'Tests', Session::getPluralNumber(), 'releases'),
               'risks'     => _n('Risk', 'Risks', Session::getPluralNumber(), 'releases'),
               'rollbacks' => _n('Rollback', 'Rollbacks', Session::getPluralNumber(), 'releases'),
               'authors'   => _n('Requester', 'Requesters', Session::getPluralNumber()),
               'suppliers' => _n('Supplier', 'Suppliers', Session::getPluralNumber())];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'     => $tag,
                              'label'   => $label,
                              'value'   => false,
                              'foreach' => true]);
      }

      //Tags with just lang
      $tags = [$objettype . '.days'               => _n('Day', 'Days', Session::getPluralNumber()),
               $objettype . '.attribution'        => __('Assigned to'),
               $objettype . '.entity'             => __('Entity'),
               $objettype . '.nocategoryassigned' => __('No defined category'),
               $objettype . '.log'                => __('Historical'),
               $objettype . '.tasks'              => _n('Deploy task', 'Deploy tasks', Session::getPluralNumber(), 'releases'),
               $objettype . '.tests'              => _n('Test', 'Tests', Session::getPluralNumber(), 'release'),
               $objettype . '.risks'              => _n('Risk', 'Risks', Session::getPluralNumber(), 'release'),
               $objettype . '.rollbacks'          => _n('Rollback', 'Rollbacks', Session::getPluralNumber(), 'release'),
               $objettype . '.release'            => _n('Release', 'Releases', 1, 'releases')
      ];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                              'label' => $label,
                              'value' => false,
                              'lang'  => true]);
      }

      //Tags without lang
      $tags = [$objettype . '.urlapprove'   => __('Web link to approval the solution'),
               $objettype . '.entity'       => sprintf(__('%1$s (%2$s)'),
                                                       __('Entity'), __('Complete name')),
               $objettype . '.shortentity'  => sprintf(__('%1$s (%2$s)'),
                                                       __('Entity'), __('Name')),
               $objettype . '.numberoflogs' => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                       _x('quantity', 'Number of items')),
               $objettype . '.log.date'     => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                       __('Date')),
               $objettype . '.log.user'     => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                       __('User')),
               $objettype . '.log.field'    => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                       __('Field')),
               $objettype . '.log.content'  => sprintf(__('%1$s: %2$s'), __('Historical'),
                                                       _x('name', 'Update')),
               'document.url'               => sprintf(__('%1$s: %2$s'), __('Document'),
                                                       __('URL')),
               'document.downloadurl'       => sprintf(__('%1$s: %2$s'), __('Document'),
                                                       __('Download URL')),
               'document.heading'           => sprintf(__('%1$s: %2$s'), __('Document'),
                                                       __('Heading')),
               'document.id'                => sprintf(__('%1$s: %2$s'), __('Document'),
                                                       __('ID')),
               'document.filename'          => sprintf(__('%1$s: %2$s'), __('Document'),
                                                       __('File')),
               'document.weblink'           => sprintf(__('%1$s: %2$s'), __('Document'),
                                                       __('Web link')),
               'document.name'              => sprintf(__('%1$s: %2$s'), __('Document'),
                                                       __('Name')),
               $objettype . '.urldocument'  => sprintf(__('%1$s: %2$s'),
                                                       _n('Document', 'Documents', Session::getPluralNumber()),
                                                       __('URL'))];

      foreach ($tags as $tag => $label) {
         $this->addTagToList(['tag'   => $tag,
                              'label' => $label,
                              'value' => true,
                              'lang'  => false]);
      }


      asort($this->tag_descriptions);
   }

   /**
    * Add task author
    *
    * @param array $options Options
    *
    * @return void
    */
   function addTaskAuthor($options = []) {
      global $DB;
      //TODO change for release -> OK

      if (isset($options['task_id'])) {
         $tasktable = getTableForItemType(PluginReleasesDeploytask::getType());

         $criteria                           = array_merge_recursive(
            ['INNER JOIN' => [
               User::getTable() => [
                  'ON' => [
                     $tasktable       => 'users_id',
                     User::getTable() => 'id'
                  ]
               ]
            ]],
            $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria()
         );
         $criteria['FROM']                   = $tasktable;
         $criteria['WHERE']["$tasktable.id"] = $options['task_id'];

         $iterator = $DB->request($criteria);
         while ($data = $iterator->next()) {
            $this->addToRecipientsList($data);
         }
      }
   }


   /**
    * Add user assigned to task
    *
    * @param array $options Options
    *
    * @return void
    */
   function addTaskAssignUser($options = []) {
      global $DB;
      //TODO change for release -> OK

      if (isset($options['task_id'])) {
         $tasktable = getTableForItemType(PluginReleasesDeploytask::getType());

         $criteria                           = array_merge_recursive(
            ['INNER JOIN' => [
               User::getTable() => [
                  'ON' => [
                     $tasktable       => 'users_id_tech',
                     User::getTable() => 'id'
                  ]
               ]
            ]],
            $this->getDistinctUserCriteria() + $this->getProfileJoinCriteria()
         );
         $criteria['FROM']                   = $tasktable;
         $criteria['WHERE']["$tasktable.id"] = $options['task_id'];

         $iterator = $DB->request($criteria);
         while ($data = $iterator->next()) {
            $this->addToRecipientsList($data);
         }
      }
   }


   /**
    * Add group assigned to the task
    *
    * @param array $options Options
    *
    * @return void
    * @since 9.1
    *
    */
   function addTaskAssignGroup($options = []) {
      global $DB;

      //TODO change for release -> OK

      if (isset($options['task_id'])) {
         $tasktable = getTableForItemType(PluginReleasesDeploytask::getType());
         $iterator  = $DB->request([
                                      'FROM'       => $tasktable,
                                      'INNER JOIN' => [
                                         'glpi_groups' => [
                                            'ON' => [
                                               'glpi_groups' => 'id',
                                               $tasktable    => 'groups_id_tech'
                                            ]
                                         ]
                                      ],
                                      'WHERE'      => ["$tasktable.id" => $options['task_id']]
                                   ]);
         while ($data = $iterator->next()) {
            $this->addForGroup(0, $data['groups_id_tech']);
         }
      }
   }

}
