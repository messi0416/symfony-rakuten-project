<?php
require_once(dirname(__FILE__) . '/AlibabaCheckStatuses.php');

/*
$server_name = "localhost";
$db_user = "root";
$db_password = "";
$db_name = "test";
*/
/// 開発環境
if (AlibabaCheckStatuses::isEnvDev()) {
  $server_name = "127.0.0.1";
  $db_user = "testuser";
  $db_password = "N9tgKzyA";
  $db_name = "test_plusnao_db";
/// 本番環境
} else {
  $server_name = "160.16.50.121";
  $db_user = "kir084880";
  $db_password = "dadaabc2323";
  $db_name = "plusnao_db";
}

