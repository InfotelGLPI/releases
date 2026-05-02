<?php


use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Releases\ReleaseTemplate_Item;

Session::checkLoginUser();

$item = new ReleaseTemplate_Item();

if (isset($_POST["add"])) {
   $item->check(-1, CREATE, $_POST);
   $item->add($_POST);
   Html::back();

}

throw new BadRequestHttpException("lost");
