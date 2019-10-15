<?php

ini_set('display_errors', 'On'); // сообщения с ошибками будут показываться
error_reporting(E_ALL); // E_ALL - отображаем ВСЕ ошибки
define("HIDE_SIDEBAR", true);
require($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");
include('simple_html_dom.php');
include('parsing.php');

use Bitrix\Main\Loader;

Loader::includeModule("iblock");
Cmodule::IncludeModule("catalog");

$load = new parsing();
?>

<script>
    //location.reload();
</script>

