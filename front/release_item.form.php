<?php




Session::checkLoginUser();

$item = new PluginReleasesRelease_Item();

if (isset($_POST["add"])) {
   $item->check(-1, CREATE, $_POST);

   if ($item->add($_POST)) {

   }
   Html::back();

}

Html::displayErrorAndDie("lost");