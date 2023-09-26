<?php
header('Content-type: application/json; charset=utf-8');

$data = array(
    'get' => $_GET
  , 'post' => $_POST
  , 'cookie' => $_COOKIE
);


echo json_encode($data);

?>
