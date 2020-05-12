DROP TABLE IF EXISTS `glpi_plugin_releases_releases`;
CREATE TABLE `glpi_plugin_releases_releases` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `release_area` longtext COLLATE utf8_unicode_ci,
  `date_preproduction` datetime DEFAULT NULL,
  `date_production` datetime DEFAULT NULL,
  `service_shutdown` tinyint(1) NOT NULL default '0',
  `service_shutdown_details` longtext COLLATE utf8_unicode_ci,
  `hour_type` tinyint(1) NOT NULL default '0',
  `communication` tinyint(1) NOT NULL default '0',
  `communication_type` varchar(255) NOT NULL default 'ALL',
  `target` longtext COLLATE utf8_unicode_ci,
  `state` int(11) NOT NULL default '7',
  `risk_state` tinyint(1) NOT NULL default '0',
  `rollback_state` tinyint(1) NOT NULL default '0',
  `test_state` tinyint(1) NOT NULL default '0',
  `date_mod` datetime default NULL,
  `date_creation` datetime DEFAULT NULL,
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
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `users_id` int(11) NOT NULL DEFAULT '0',
  `users_id_editor` int(11) NOT NULL DEFAULT '0',
   `content` text collate utf8_unicode_ci,
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
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `users_id` int(11) NOT NULL DEFAULT '0',
   `users_id_editor` int(11) NOT NULL DEFAULT '0',
--    `users_id_tech` int(11) NOT NULL DEFAULT '0',
--    `groups_id_tech` int(11) NOT NULL DEFAULT '0',
   `content` longtext COLLATE utf8_unicode_ci,
   `date_mod` datetime DEFAULT NULL,
   `date_creation` datetime DEFAULT NULL,
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
--    `date_mod` datetime default NULL,
--    `date_creation` datetime DEFAULT NULL,
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
  `date` datetime DEFAULT NULL,
  `begin` datetime DEFAULT NULL,
  `end` datetime DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT '0',
  `users_id_editor` int(11) NOT NULL DEFAULT '0',
  `users_id_tech` int(11) NOT NULL DEFAULT '0',
  `groups_id_tech` int(11) NOT NULL DEFAULT '0',
  `content` longtext COLLATE utf8_unicode_ci,
  `actiontime` int(11) NOT NULL DEFAULT '0',
  `date_mod` datetime DEFAULT NULL,
  `date_creation` datetime DEFAULT NULL,
  `plugin_releases_deploytasktemplates_id` int(11) NOT NULL DEFAULT '0',
  `timeline_position` tinyint(1) NOT NULL DEFAULT '0',
  `is_private` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE TABLE `glpi_plugin_releases_deploytasktemplates` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `plugin_releases_typedeploytasks_id` int(11) NOT NULL DEFAULT '0',
  `state` int(11) NOT NULL DEFAULT '1',
  `date` datetime DEFAULT NULL,
  `begin` datetime DEFAULT NULL,
  `end` datetime DEFAULT NULL,
  `users_id` int(11) NOT NULL DEFAULT '0',
  `users_id_editor` int(11) NOT NULL DEFAULT '0',
  `users_id_tech` int(11) NOT NULL DEFAULT '0',
  `groups_id_tech` int(11) NOT NULL DEFAULT '0',
  `content` longtext COLLATE utf8_unicode_ci,
  `comment` longtext COLLATE utf8_unicode_ci,
  `actiontime` int(11) NOT NULL DEFAULT '0',
  `date_mod` datetime DEFAULT NULL,
  `date_creation` datetime DEFAULT NULL,
  `plugin_releases_deploytasktemplates_id` int(11) NOT NULL DEFAULT '0',
  `timeline_position` tinyint(1) NOT NULL DEFAULT '0',
  `is_private` tinyint(1) NOT NULL DEFAULT '0',
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_rollbacks`;
CREATE TABLE `glpi_plugin_releases_rollbacks` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `plugin_releases_releases_id` int(11) NOT NULL default '0',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
--    `comment` text collate utf8_unicode_ci,
   `users_id` int(11) NOT NULL DEFAULT '0',
   `users_id_editor` int(11) NOT NULL DEFAULT '0',
--    `users_id_tech` int(11) NOT NULL DEFAULT '0',
--    `groups_id_tech` int(11) NOT NULL DEFAULT '0',
   `content` longtext COLLATE utf8_unicode_ci,
   `plugin_releases_rollbacktemplates_id` int(11) NOT NULL DEFAULT '0',
   `date_mod` datetime DEFAULT NULL,
   `date_creation` datetime DEFAULT NULL,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_reviews`;
CREATE TABLE `glpi_plugin_releases_reviews` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `plugin_releases_releases_id` int(11) NOT NULL default '0',
   `real_date_release` datetime DEFAULT NULL,
   `conforming_realization` tinyint(1) NOT NULL default '0',
   `incident` tinyint(1) NOT NULL default '0',
   `incident_description` longtext collate utf8_unicode_ci,
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_globalstatues`;
CREATE TABLE `glpi_plugin_releases_globalstatues` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `plugin_releases_releases_id` int(11) NOT NULL default '0',
   `itemtype` varchar(255) DEFAULT NULL,
   `state` int(11) NOT NULL default '0',
   PRIMARY KEY  (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_releasetemplates`;
CREATE TABLE `glpi_plugin_releases_releasetemplates` (
  `id` int(11) NOT NULL auto_increment,
  `entities_id` int(11) NOT NULL default '0',
  `is_recursive` tinyint(1) NOT NULL default '0',
  `name` varchar(255) collate utf8_unicode_ci default NULL,
  `release_area` longtext COLLATE utf8_unicode_ci,
  `comment` longtext COLLATE utf8_unicode_ci,
  `date_preproduction` datetime DEFAULT NULL,
  `date_production` datetime DEFAULT NULL,
  `service_shutdown` tinyint(1) NOT NULL default '0',
  `service_shutdown_details` longtext COLLATE utf8_unicode_ci,
  `hour_type` tinyint(1) NOT NULL default '0',
  `communication` tinyint(1) NOT NULL default '0',
  `communication_type` varchar(255) NOT NULL default 'ALL',
  `target` longtext COLLATE utf8_unicode_ci,
  `state` int(11) NOT NULL default '7',
  `risk_state` tinyint(1) NOT NULL default '0',
  `rollback_state` tinyint(1) NOT NULL default '0',
  `test_state` tinyint(1) NOT NULL default '0',
  `date_mod` datetime default NULL,
  `date_creation` datetime DEFAULT NULL,
  `plugin_releases_releasetemplates_id` int(11) NOT NULL DEFAULT '0',
  `is_deleted` tinyint(1) NOT NULL default '0',
  `rollbacks` varchar(255) collate utf8_unicode_ci default NULL,
  `tests` varchar(255) collate utf8_unicode_ci default NULL,
  `tasks` varchar(255) collate utf8_unicode_ci default NULL,
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
   `plugin_releases_typetests_id` int(11) NOT NULL default '0',
   `plugin_releases_risks_id` int(11) NOT NULL default '0',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `comment` text collate utf8_unicode_ci,
   `content` longtext COLLATE utf8_unicode_ci,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_risktemplates`;
CREATE TABLE `glpi_plugin_releases_risktemplates` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `plugin_releases_typerisks_id` int(11) NOT NULL default '0',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `content` text collate utf8_unicode_ci,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_rollbacktemplates`;
CREATE TABLE `glpi_plugin_releases_rollbacktemplates` (
   `id` int(11) NOT NULL auto_increment,
   `entities_id` int(11) NOT NULL default '0',
   `name` varchar(255) collate utf8_unicode_ci default NULL,
   `comment` text collate utf8_unicode_ci,
   `content` longtext COLLATE utf8_unicode_ci,
   PRIMARY KEY  (`id`),
   KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;