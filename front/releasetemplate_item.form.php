<?php


use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Releases\Releasetemplate_Item;

Session::checkLoginUser();

$item = new Releasetemplate_Item();

if (isset($_POST["add"])) {
   $item->check(-1, CREATE, $_POST);
   $item->add($_POST);
   Html::back();

}

throw new BadRequestHttpException("lost");
