DROP TABLE IF EXISTS `glpi_plugin_releases_releases`;
CREATE TABLE `glpi_plugin_releases_releases` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `content` longtext COLLATE utf8_unicode_ci,
  `date` timestamp NULL DEFAULT NULL,
  `users_id_recipient` int(11) NOT NULL DEFAULT '0',
  `users_id_lastupdater` int(11) NOT NULL DEFAULT '0',
  `date_preproduction` TIMESTAMP NULL DEFAULT NULL,
  `date_production` TIMESTAMP NULL DEFAULT NULL,
  `begin_waiting_date` TIMESTAMP NULL DEFAULT NULL,
  `service_shutdown` tinyint(1) NOT NULL default '0',
  `service_shutdown_details` longtext COLLATE utf8_unicode_ci,
  `hour_type` tinyint(1) NOT NULL default '0',
  `communication` tinyint(1) NOT NULL default '0',
  `communication_type` varchar(255) NOT NULL default 'ALL',
  `target` longtext COLLATE utf8_unicode_ci,
  `status` int(11) NOT NULL default '7',
  `locations_id` int(11) NOT NULL default '0',
  `date_mod` TIMESTAMP NULL default NULL,
  `date_creation` TIMESTAMP NULL DEFAULT NULL,
  `is_deleted` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_changes_releases`;
CREATE TABLE `glpi_plugin_releases_changes_releases` (
  `id` int(11) NOT NULL auto_increment,
  `plugin_releases_releases_id` int(11) NOT NULL,
  `changes_id` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_typerisks`;
CREATE TABLE `glpi_plugin_releases_typerisks` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `comment` text collate utf8_unicode_ci,
    `plugin_releases_typerisks_id` int(11)    NOT NULL                  DEFAULT '0',
    `completename`                  text COLLATE utf8_unicode_ci,
    `level`                         int(11)    NOT NULL                  DEFAULT '0',
    `ancestors_cache`               longtext COLLATE utf8_unicode_ci,
    `sons_cache`                    longtext COLLATE utf8_unicode_ci,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_risks`;
CREATE TABLE `glpi_plugin_releases_risks` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `plugin_releases_releases_id` int(11) NOT NULL default '0',
   `plugin_releases_typerisks_id` int(11) NOT NULL default '0',
   `state` int(11) NOT NULL DEFAULT '1',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `users_id` int(11) NOT NULL DEFAULT '0',
   `users_id_editor` int(11) NOT NULL DEFAULT '0',
   `content` text collate utf8_unicode_ci,
   `date_mod` TIMESTAMP NULL DEFAULT NULL,
   `date_creation` TIMESTAMP NULL DEFAULT NULL,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_typetests`;
CREATE TABLE `glpi_plugin_releases_typetests` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `comment` text collate utf8_unicode_ci,
   `plugin_releases_typetests_id` int(11)    NOT NULL                  DEFAULT '0',
   `completename`                  text COLLATE utf8_unicode_ci,
   `level`                         int(11)    NOT NULL                  DEFAULT '0',
   `ancestors_cache`               longtext COLLATE utf8_unicode_ci,
   `sons_cache`                    longtext COLLATE utf8_unicode_ci,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_tests`;
CREATE TABLE `glpi_plugin_releases_tests` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `plugin_releases_releases_id` int(11) NOT NULL default '0',
   `plugin_releases_typetests_id` int(11) NOT NULL default '0',
   `plugin_releases_risks_id` int(11) NOT NULL default '0',
   `state` int(11) NOT NULL DEFAULT '1',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `users_id` int(11) NOT NULL DEFAULT '0',
   `users_id_editor` int(11) NOT NULL DEFAULT '0',
--    `users_id_tech` int(11) NOT NULL DEFAULT '0',
--    `groups_id_tech` int(11) NOT NULL DEFAULT '0',
   `content` longtext COLLATE utf8_unicode_ci,
   `date_mod` TIMESTAMP NULL DEFAULT NULL,
   `date_creation` TIMESTAMP NULL DEFAULT NULL,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_typedeploytasks`;
CREATE TABLE `glpi_plugin_releases_typedeploytasks` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `comment` text collate utf8_unicode_ci,
   `plugin_releases_typedeploytasks_id` int(11)    NOT NULL                  DEFAULT '0',
   `completename`                  text COLLATE utf8_unicode_ci,
   `level`                         int(11)    NOT NULL                  DEFAULT '0',
   `ancestors_cache`               longtext COLLATE utf8_unicode_ci,
   `sons_cache`                    longtext COLLATE utf8_unicode_ci,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_deploytasks`;
-- CREATE TABLE `glpi_plugin_release_deploytasks` (
--    `id` int(11) NOT NULL auto_increment,
--    `entities_id` int(11) NOT NULL default '0',
--    `plugin_release_releases_id` int(11) NOT NULL default '0',
--    `plugin_release_typedeploytasks_id` int(11) NOT NULL default '0',
--    `plugin_release_risks_id` int(11) NOT NULL default '0',
--    `users_id` INT(11) NOT NULL,
--    `state` int(11) NOT NULL default '1',
--    `name` varchar(255) collate utf8_unicode_ci default NULL,
--    `date_mod` TIMESTAMP NULL default NULL,
--    `date_creation` TIMESTAMP NULL DEFAULT NULL,
--    `comment` text collate utf8_unicode_ci,
--    PRIMARY KEY  (`id`),
--    KEY `name` (`name`)
-- ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
CREATE TABLE `glpi_plugin_releases_deploytasks` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `plugin_releases_releases_id` int(11) NOT NULL DEFAULT '0',
  `plugin_releases_typedeploytasks_id` int(11) NOT NULL DEFAULT '0',
  `plugin_releases_risks_id` int(11) NOT NULL default '0',
  `state` int(11) NOT NULL DEFAULT '1',
  `date` TIMESTAMP NULL DEFAULT NULL,
  `begin` TIMESTAMP NULL DEFAULT NULL,
  `end` TIMESTAMP NULL DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT '0',
  `users_id_editor` int(11) NOT NULL DEFAULT '0',
  `users_id_tech` int(11) NOT NULL DEFAULT '0',
  `groups_id_tech` int(11) NOT NULL DEFAULT '0',
  `content` longtext COLLATE utf8_unicode_ci,
  `actiontime` int(11) NOT NULL DEFAULT '0',
  `date_mod` TIMESTAMP NULL DEFAULT NULL,
  `date_creation` TIMESTAMP NULL DEFAULT NULL,
  `plugin_releases_deploytasktemplates_id` int(11) NOT NULL DEFAULT '0',
  `timeline_position` tinyint(1) NOT NULL DEFAULT '0',
  `plugin_releases_deploytasks_id` int(11) NOT NULL default 0,
  `level` int(11) NOT NULL default 0,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_deploytasktemplates`;
CREATE TABLE `glpi_plugin_releases_deploytasktemplates` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `plugin_releases_releasetemplates_id` int(11) NOT NULL DEFAULT '0',
  `plugin_releases_typedeploytasks_id` int(11) NOT NULL DEFAULT '0',
  `plugin_releases_risks_id` int(11) NOT NULL default '0',
  `state` int(11) NOT NULL DEFAULT '1',
  `date` TIMESTAMP NULL DEFAULT NULL,
  `begin` TIMESTAMP NULL DEFAULT NULL,
  `end` TIMESTAMP NULL DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT '0',
  `users_id_editor` int(11) NOT NULL DEFAULT '0',
  `users_id_tech` int(11) NOT NULL DEFAULT '0',
  `groups_id_tech` int(11) NOT NULL DEFAULT '0',
  `content` longtext COLLATE utf8_unicode_ci,
  `actiontime` int(11) NOT NULL DEFAULT '0',
  `date_mod` TIMESTAMP NULL DEFAULT NULL,
  `date_creation` TIMESTAMP NULL DEFAULT NULL,
  `plugin_releases_deploytasktemplates_id` int(11) NOT NULL DEFAULT '0',
  `timeline_position` tinyint(1) NOT NULL DEFAULT '0',
  `level` int(11) NOT NULL default 0,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_rollbacks`;
CREATE TABLE `glpi_plugin_releases_rollbacks` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `plugin_releases_releases_id` int(11) NOT NULL default '0',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `state` int(11) NOT NULL DEFAULT '1',
--    `comment` text collate utf8_unicode_ci,
   `users_id` int(11) NOT NULL DEFAULT '0',
   `users_id_editor` int(11) NOT NULL DEFAULT '0',
--    `users_id_tech` int(11) NOT NULL DEFAULT '0',
--    `groups_id_tech` int(11) NOT NULL DEFAULT '0',
   `content` longtext COLLATE utf8_unicode_ci,
   `plugin_releases_rollbacktemplates_id` int(11) NOT NULL DEFAULT '0',
   `date_mod` TIMESTAMP NULL DEFAULT NULL,
   `date_creation` TIMESTAMP NULL DEFAULT NULL,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_reviews`;
CREATE TABLE `glpi_plugin_releases_reviews` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `plugin_releases_releases_id` int(11) NOT NULL default '0',
   `real_date_release` TIMESTAMP NULL DEFAULT NULL,
   `conforming_realization` tinyint(1) NOT NULL default '0',
   `incident` tinyint(1) NOT NULL default '0',
   `incident_description` longtext collate utf8_unicode_ci,
   `date_lock` tinyint(1) NOT NULL default '0',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_releases_releasetemplates`;
CREATE TABLE `glpi_plugin_releases_releasetemplates` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `content` longtext COLLATE utf8_unicode_ci,
  `date_preproduction` TIMESTAMP NULL DEFAULT NULL,
  `date_production` TIMESTAMP NULL DEFAULT NULL,
  `service_shutdown` tinyint(1) NOT NULL default '0',
  `service_shutdown_details` longtext COLLATE utf8_unicode_ci,
  `locations_id` int(11) NOT NULL default '0',
  `hour_type` tinyint(1) NOT NULL default '0',
  `communication` tinyint(1) NOT NULL default '0',
  `communication_type` varchar(255) NOT NULL default 'ALL',
  `target` longtext COLLATE utf8_unicode_ci,
  `status` int(11) NOT NULL default '7',
  `date_mod` TIMESTAMP NULL default NULL,
  `date_creation` TIMESTAMP NULL DEFAULT NULL,
  `plugin_releases_releasetemplates_id` int(11) NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `entities_id` (`entities_id`),
  KEY `date_mod` (`date_mod`),
  KEY `date_creation` (`date_creation`),
  KEY `is_deleted` (`is_deleted`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_testtemplates`;
CREATE TABLE `glpi_plugin_releases_testtemplates` (
    `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `plugin_releases_releasetemplates_id` int(11) NOT NULL default '0',
   `plugin_releases_typetests_id` int(11) NOT NULL default '0',
   `plugin_releases_risks_id` int(11) NOT NULL default '0',
   `state` int(11) NOT NULL DEFAULT '1',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `users_id` int(11) NOT NULL DEFAULT '0',
   `users_id_editor` int(11) NOT NULL DEFAULT '0',
-- `users_id_tech` int(11) NOT NULL DEFAULT '0',
-- `groups_id_tech` int(11) NOT NULL DEFAULT '0',
   `content` longtext COLLATE utf8_unicode_ci,
   `date_mod` TIMESTAMP NULL DEFAULT NULL,
   `date_creation` TIMESTAMP NULL DEFAULT NULL,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_risktemplates`;
CREATE TABLE `glpi_plugin_releases_risktemplates` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `plugin_releases_releasetemplates_id` int(11) NOT NULL default '0',
   `plugin_releases_typerisks_id` int(11) NOT NULL default '0',
   `state` int(11) NOT NULL DEFAULT '1',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `users_id` int(11) NOT NULL DEFAULT '0',
   `users_id_editor` int(11) NOT NULL DEFAULT '0',
   `content` text collate utf8_unicode_ci,
   `date_mod` TIMESTAMP NULL DEFAULT NULL,
   `date_creation` TIMESTAMP NULL DEFAULT NULL,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_rollbacktemplates`;
CREATE TABLE `glpi_plugin_releases_rollbacktemplates` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `plugin_releases_releasetemplates_id` int(11) NOT NULL default '0',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `state` int(11) NOT NULL DEFAULT '1',
--    `comment` text collate utf8_unicode_ci,
   `users_id` int(11) NOT NULL DEFAULT '0',
   `users_id_editor` int(11) NOT NULL DEFAULT '0',
--    `users_id_tech` int(11) NOT NULL DEFAULT '0',
--    `groups_id_tech` int(11) NOT NULL DEFAULT '0',
   `content` longtext COLLATE utf8_unicode_ci,
   `plugin_releases_rollbacktemplates_id` int(11) NOT NULL DEFAULT '0',
   `date_mod` TIMESTAMP NULL DEFAULT NULL,
   `date_creation` TIMESTAMP NULL DEFAULT NULL,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_releases_items`;
CREATE TABLE `glpi_plugin_releases_releases_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_releases_releases_id` int(11) NOT NULL DEFAULT '0',
  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_releases_releases_id`,`itemtype`,`items_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_releasetemplates_items`;
CREATE TABLE `glpi_plugin_releases_releasetemplates_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_releases_releasetemplates_id` int(11) NOT NULL DEFAULT '0',
  `itemtype` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `items_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_releases_releasetemplates_id`,`itemtype`,`items_id`),
  KEY `item` (`itemtype`,`items_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_groups_releases`;
CREATE TABLE `glpi_plugin_releases_groups_releases` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_releases_releases_id` int(11) NOT NULL DEFAULT '0',
  `groups_id` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_releases_releases_id`,`type`,`groups_id`),
  KEY `group` (`groups_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_releases_users`;
CREATE TABLE `glpi_plugin_releases_releases_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_releases_releases_id` int(11) NOT NULL DEFAULT '0',
  `users_id` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '1',
  `use_notification` tinyint(1) NOT NULL DEFAULT '0',
  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_releases_releases_id`,`type`,`users_id`),
  KEY `group` (`users_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_releases_suppliers`;
CREATE TABLE `glpi_plugin_releases_releases_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_releases_releases_id` int(11) NOT NULL DEFAULT '0',
  `suppliers_id` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '1',
  `use_notification` tinyint(1) NOT NULL DEFAULT '1',
  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_releases_releases_id`,`type`,`suppliers_id`),
  KEY `group` (`suppliers_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_releases_groups_releasetemplates`;
CREATE TABLE `glpi_plugin_releases_groups_releasetemplates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_releases_releasetemplates_id` int(11) NOT NULL DEFAULT '0',
  `groups_id` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_releases_releasetemplates_id`,`type`,`groups_id`),
  KEY `group` (`groups_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_releasetemplates_users`;
CREATE TABLE `glpi_plugin_releases_releasetemplates_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_releases_releasetemplates_id` int(11) NOT NULL DEFAULT '0',
  `users_id` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '1',
  `use_notification` tinyint(1) NOT NULL DEFAULT '0',
  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_releases_releasetemplates_id`,`type`,`users_id`),
  KEY `group` (`users_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_releasetemplates_suppliers`;
CREATE TABLE `glpi_plugin_releases_releasetemplates_suppliers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `plugin_releases_releasetemplates_id` int(11) NOT NULL DEFAULT '0',
  `suppliers_id` int(11) NOT NULL DEFAULT '0',
  `type` int(11) NOT NULL DEFAULT '1',
  `use_notification` tinyint(1) NOT NULL DEFAULT '1',
  `alternative_email` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`plugin_releases_releasetemplates_id`,`type`,`suppliers_id`),
  KEY `group` (`suppliers_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;