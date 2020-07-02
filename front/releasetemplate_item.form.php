<?php


include('../../../inc/includes.php');

Session::checkLoginUser();

$item = new PluginReleasesReleasetemplate_Item();

if (isset($_POST["add"])) {
   $item->check(-1, CREATE, $_POST);

   if ($item->add($_POST)) {

   }
   Html::back();

}

Html::displayErrorAndDie("lost");