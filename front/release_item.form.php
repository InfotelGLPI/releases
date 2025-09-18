<?php


use Glpi\Exception\Http\BadRequestHttpException;
use GlpiPlugin\Releases\Release_Item;

Session::checkLoginUser();

$item = new Release_Item();

if (isset($_POST["add"])) {
   $item->check(-1, CREATE, $_POST);
   $item->add($_POST);
   Html::back();

}

throw new BadRequestHttpException("lost");
