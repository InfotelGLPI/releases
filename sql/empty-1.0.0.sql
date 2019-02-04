DROP TABLE IF EXISTS `glpi_plugin_releases_releases`;
CREATE TABLE `glpi_plugin_releases_releases` (
  `id`              INT(11)    NOT NULL                     AUTO_INCREMENT,
  `entities_id`     INT(11)    NOT NULL                     DEFAULT '0',
  `is_recursive`    TINYINT(1) NOT NULL                     DEFAULT '0',
  `name`            VARCHAR(255) COLLATE utf8_unicode_ci    DEFAULT NULL,
  `content`         TEXT COLLATE utf8_unicode_ci,
  `status`          int(11)    NOT NULL                     DEFAULT '1',
  `priority`        int(11)    NOT NULL                     DEFAULT '1',
  `date`            datetime                                DEFAULT NULL,
  `solvedate`       datetime                                DEFAULT NULL,
  `closedate`       datetime                                DEFAULT NULL,
  `time_to_resolve` datetime                                DEFAULT NULL,
  `date_creation`   datetime                                DEFAULT NULL,
  `date_mod`        DATETIME                                DEFAULT NULL,
  `actiontime`      int(11)    NOT NULL                     DEFAULT '0',
  PRIMARY KEY (`id`), -- index
  KEY `time_to_resolve` (`time_to_resolve`),
  KEY `date` (`date`),
  KEY `solvedate` (`solvedate`),
  KEY `closedate` (`closedate`),
  KEY `status` (`status`),
  KEY `priority` (`priority`),
  KEY date_mod (date_mod),
  KEY date_creation (date_creation)
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

DROP TABLE IF EXISTS `glpi_plugin_releases_releaseoverviews`;
CREATE TABLE `glpi_plugin_releases_releaseoverviews` (
  `id`                          int(11) NOT NULL                     AUTO_INCREMENT,
  `plugin_releases_releases_id` int(11) NOT NULL                     DEFAULT '0'
  COMMENT 'RELATION to glpi_plugin_releases_releases',
  `is_release`                  int(11) NOT NULL                     default '0',
  `is_validate_analyse`         int(11) NOT NULL                     DEFAULT '0',
  `is_validate_cost`            int(11) NOT NULL                     DEFAULT '0',
  `is_validate_plan`            int(11) NOT NULL                     DEFAULT '0',
  `is_test_done`                int(11) NOT NULL                     DEFAULT '0',
  `is_info_done`                int(11) NOT NULL                     DEFAULT '0',
  `is_deployment_done`          int(11) NOT NULL                     DEFAULT '0',
  `is_end`                      int(11) NOT NULL                     DEFAULT '0',
  PRIMARY KEY (`id`), -- index
  KEY `plugin_releases_releases_id` (`plugin_releases_releases_id`) -- index
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_releases_releasetests`;
CREATE TABLE `glpi_plugin_releases_releasetests` (
  `id`                          int(11)                              NOT NULL                     AUTO_INCREMENT,
  `plugin_releases_releases_id` int(11)                              NOT NULL                     DEFAULT '0'
  COMMENT 'RELATION to glpi_plugin_releases_releases',
  `name`                        varchar(255) COLLATE utf8_unicode_ci                              DEFAULT NULL,
  `content`                     varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `taskcategories_id`           varchar(255) COLLATE utf8_unicode_ci                              DEFAULT NULL,
  `state`                       int(11)                              NOT NULL                     DEFAULT '0',
  `date`                        date                                                              DEFAULT NULL,
  `begin`                       date                                                              DEFAULT NULL,
  `end`                         date                                                              DEFAULT NULL,
  `users_id`                    int(11)                              NOT NULL                     DEFAULT '0',
  `users_id_tech`               int(11)                              NOT NULL                     DEFAULT '0',
  `groups_id_tech`              int(11)                              NOT NULL                     DEFAULT '0',
  `actiontime`                  int(11)                              NOT NULL                     DEFAULT '0',
  `date_mod`                    date                                                              DEFAULT NULL,
  PRIMARY KEY (`id`), -- index
  KEY `plugin_releases_releases_id` (`plugin_releases_releases_id`) -- index
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_releases_releasetasks`;
CREATE TABLE `glpi_plugin_releases_releasetasks` (
  `id`                          int(11)                              NOT NULL                     AUTO_INCREMENT,
  `plugin_releases_releases_id` int(11)                              NOT NULL                     DEFAULT '0'
  COMMENT 'RELATION to glpi_plugin_releases_releases',
  `name`                        varchar(255) COLLATE utf8_unicode_ci                              DEFAULT NULL,
  `content`                     varchar(100) COLLATE utf8_unicode_ci NOT NULL,
  `taskcategories_id`           varchar(255) COLLATE utf8_unicode_ci                              DEFAULT NULL,
  `state`                       int(11)                              NOT NULL                     DEFAULT '0',
  `date`                        date                                                              DEFAULT NULL,
  `begin`                       date                                                              DEFAULT NULL,
  `end`                         date                                                              DEFAULT NULL,
  `users_id`                    int(11)                              NOT NULL                     DEFAULT '0',
  `users_id_tech`               int(11)                              NOT NULL                     DEFAULT '0',
  `groups_id_tech`              int(11)                              NOT NULL                     DEFAULT '0',
  `actiontime`                  int(11)                              NOT NULL                     DEFAULT '0',
  `date_mod`                    date                                                              DEFAULT NULL,
  PRIMARY KEY (`id`), -- index
  KEY `plugin_releases_releases_id` (`plugin_releases_releases_id`) -- index
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_releases_releaseinformations`;
CREATE TABLE `glpi_plugin_releases_releaseinformations` (
  `id`                          int(11) NOT NULL AUTO_INCREMENT,
  `alerts_id`                   int(11) NOT NULL DEFAULT '0'
  COMMENT 'RELATION to plugin_mydashboard_alerts',
  `is_active`                   int(11) NOT NULL DEFAULT '0',
  `plugin_releases_releases_id` int(11) NOT NULL DEFAULT '0'
  COMMENT 'RELATION to glpi_plugin_releases_releases',
  PRIMARY KEY (`id`), -- index
  KEY `plugin_releases_releases_id` (`plugin_releases_releases_id`) -- index
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;


DROP TABLE IF EXISTS `glpi_plugin_releases_releasedeployments`;
CREATE TABLE `glpi_plugin_releases_releasedeployments` (
  `id`                          int(11) NOT NULL AUTO_INCREMENT,
  `plugin_releases_releases_id` int(11) NOT NULL
  COMMENT 'RELATION to glpi_plugin_releases_releases',
  `type`                        int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`), -- index
  KEY `plugin_releases_releases_id` (`plugin_releases_releases_id`) -- index
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

DROP TABLE IF EXISTS `glpi_plugin_releases_releasephases`;
CREATE TABLE `glpi_plugin_releases_releasephases` (
  `id`                             int(11) NOT NULL AUTO_INCREMENT,
  `plugin_releases_deployments_id` int(11) NOT NULL DEFAULT '0'
  COMMENT 'RELATION to glpi_plugin_releases_releasedeployments',
  `name`                           varchar(255)     DEFAULT NULL,
  `comment`                        text,
  PRIMARY KEY (`id`), -- index
  KEY `plugin_releases_deployments_id` (`plugin_releases_deployments_id`) -- index
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;




