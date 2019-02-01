DROP TABLE IF EXISTS `glpi_plugin_releases_releases`;
CREATE TABLE `glpi_plugin_releases_releases` (
  `id`           INT(11)    NOT NULL                     AUTO_INCREMENT,
  `entities_id`  INT(11)    NOT NULL                     DEFAULT '0',
  `is_recursive` TINYINT(1) NOT NULL                     DEFAULT '0',
  `name`         VARCHAR(255) COLLATE utf8_unicode_ci    DEFAULT NULL,
  `changes_id`   int(11)    NOT NULL                     default '0'
  COMMENT 'RELATION to change',
  `is_release`   int(11)    NOT NULL                     default '0',
  PRIMARY KEY (`id`), -- index
  KEY `changes_id` (`changes_id`) -- index
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

### Dump table glpi_changes_tickets

DROP TABLE IF EXISTS `glpi_plugin_releases_changes_releases`;
CREATE TABLE `glpi_plugin_releases_changes_releases` (
  `id`                          int(11) NOT NULL AUTO_INCREMENT,
  `changes_id`                  int(11) NOT NULL DEFAULT '0',
  `plugin_releases_releases_id` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `unicity` (`changes_id`, `plugin_releases_releases_id`),
  KEY `plugin_releases_releases_id` (`plugin_releases_releases_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_overviews`;
CREATE TABLE `glpi_plugin_releases_overviews` (
  `id`                  int(11) NOT NULL AUTO_INCREMENT,
  `changes_id`          int(11) NOT NULL DEFAULT '0'
  COMMENT 'RELATION to change',
  `is_validate_analyse` int(11) NOT NULL DEFAULT '0',
  `is_validate_cost`    int(11) NOT NULL DEFAULT '0',
  `is_validate_plan`    int(11) NOT NULL DEFAULT '0',
  `is_test_done`        int(11) NOT NULL DEFAULT '0',
  `is_info_done`        int(11) NOT NULL DEFAULT '0',
  `is_deployment_done`  int(11) NOT NULL DEFAULT '0',
  `is_end`              int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`), -- index
  KEY `changes_id` (`changes_id`) -- index
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_releases_tests`;
CREATE TABLE `glpi_plugin_releases_tests` (
  `id`                int(11)                              NOT NULL                     AUTO_INCREMENT,
  `changes_id`        int(11)                              NOT NULL                     DEFAULT '0'
  COMMENT 'RELATION to change',
  `name`              varchar(255) COLLATE utf8_unicode_ci                              DEFAULT NULL,
  `content`           varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `taskcategories_id` varchar(255) COLLATE utf8_unicode_ci                              DEFAULT NULL,
  `state`             int(11)                              NOT NULL                     DEFAULT '0',
  `date`              date                                                              DEFAULT NULL,
  `begin`             date                                                              DEFAULT NULL,
  `end`               date                                                              DEFAULT NULL,
  `users_id`          int(11)                              NOT NULL                     DEFAULT '0',
  `users_id_tech`     int(11)                              NOT NULL                     DEFAULT '0',
  `groups_id_tech`    int(11)                              NOT NULL                     DEFAULT '0',
  `actiontime`        int(11)                              NOT NULL                     DEFAULT '0',
  `date_mod`          date                                                              DEFAULT NULL,
  PRIMARY KEY (`id`), -- index
  KEY `changes_id` (`changes_id`) -- index
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_releases_tasks`;
CREATE TABLE `glpi_plugin_releases_tasks` (
  `id`                int(11)                              NOT NULL                     AUTO_INCREMENT,
  `changes_id`        int(11)                              NOT NULL                     DEFAULT '0'
  COMMENT 'RELATION to change',
  `name`              varchar(255) COLLATE utf8_unicode_ci                              DEFAULT NULL,
  `content`           varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `taskcategories_id` varchar(255) COLLATE utf8_unicode_ci                              DEFAULT NULL,
  `state`             int(11)                              NOT NULL                     DEFAULT '0',
  `date`              date                                                              DEFAULT NULL,
  `begin`             date                                                              DEFAULT NULL,
  `end`               date                                                              DEFAULT NULL,
  `users_id`          int(11)                              NOT NULL                     DEFAULT '0',
  `users_id_tech`     int(11)                              NOT NULL                     DEFAULT '0',
  `groups_id_tech`    int(11)                              NOT NULL                     DEFAULT '0',
  `actiontime`        int(11)                              NOT NULL                     DEFAULT '0',
  `date_mod`          date                                                              DEFAULT NULL,
  PRIMARY KEY (`id`), -- index
  KEY `changes_id` (`changes_id`) -- index
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_releases_informations`;
CREATE TABLE `glpi_plugin_releases_informations` (
  `id`         int(11) NOT NULL AUTO_INCREMENT,
  `alerts_id`  int(11) NOT NULL DEFAULT '0'
  COMMENT 'RELATION to plugin_mydashboard_alerts',
  `is_active`  int(11) NOT NULL DEFAULT '0',
  `changes_id` int(11) NOT NULL DEFAULT '0'
  COMMENT 'RELATION to change',
  PRIMARY KEY (`id`), -- index
  KEY `changes_id` (`changes_id`) -- index
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_releases_deployments`;
CREATE TABLE `glpi_plugin_releases_deployments` (
  `id`         int(11) NOT NULL AUTO_INCREMENT,
  `changes_id` int(11) NOT NULL
  COMMENT 'RELATION to change',
  `type`       int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`), -- index
  KEY `changes_id` (`changes_id`) -- index
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_phases`;
CREATE TABLE `glpi_plugin_releases_phases` (
  `id`             int(11) NOT NULL AUTO_INCREMENT,
  `deployments_id` int(11) NOT NULL DEFAULT '0'
  COMMENT 'RELATION to plugin_releases_deployments',
  `name`           varchar(255)     DEFAULT NULL,
  `comment`        text,
  PRIMARY KEY (`id`), -- index
  KEY `deployments_id` (`deployments_id`) -- index
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;




