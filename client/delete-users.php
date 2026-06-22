<?php

include 'db_connect.php';

$id = $_GET['id'];

$sql =
"DELETE FROM Users
 WHERE User_ID = $id";

$conn->query($sql);

header(
"Location: admin-roles.php"
);

?>