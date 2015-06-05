<?php

include_once(dirname(__FILE__) . "/../src/app/database/database_helper.php");
include_once(dirname(__FILE__) . "/../src/app/database/database_manager.php");

$database_manager = new DatabaseManager();
$database_manager->connect();
DatabaseHelper::deleteTwitterPostLog( $database_manager );
$database_manager->close();

echo "end";
?>