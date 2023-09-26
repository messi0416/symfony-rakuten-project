<?php
require_once(dirname(__FILE__) . '/TaobaoCheckStatuses.php');

// 開発環境
if (TaobaoCheckStatuses::isEnvDev()) {
  $host        = '127.0.0.1';
  $username    = 'testuser';
  $password    = 'N9tgKzyA';
  $dbname      = 'test_plusnao_db';

/// 本番環境
} else {
  $host        = "160.16.50.121";
  $username    = 'kir084880';
  $password    = 'dadaabc2323';
  $dbname      = 'plusnao_db';

}

