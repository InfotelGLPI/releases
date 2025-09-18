UPDATE `glpi_displaypreferences` SET `itemtype` = 'GlpiPlugin\\Releases\\Release' WHERE `itemtype` = 'PluginReleasesRelease';
UPDATE `glpi_displaypreferences` SET `itemtype` = 'GlpiPlugin\\Releases\\Releasetemplate' WHERE `itemtype` = 'PluginReleasesReleasetemplate';
UPDATE `glpi_notifications` SET `itemtype` = 'GlpiPlugin\\Releases\\Releasetemplate' WHERE `itemtype` = 'PluginReleasesRelease';
UPDATE `glpi_notificationtemplates` SET `itemtype` = 'GlpiPlugin\\Releases\\Releasetemplate' WHERE `itemtype` = 'PluginReleasesRelease';
