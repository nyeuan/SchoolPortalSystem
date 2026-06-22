<?php

include 'db.php';

$id = $_GET['id'];

$sql =
"DELETE FROM Users
 WHERE User_ID = $id";

$pdo->exec($sql);

header(
"Location: admin-roles.php"
);

?>