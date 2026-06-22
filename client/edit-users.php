<?php

include 'db.php';

$id = $_GET['id'];

$getUser =
$conn->query(
"SELECT * FROM Users
 WHERE User_ID = $id"
);

$user =
$getUser->fetch_assoc();

if(isset($_POST['update'])) {

    $fname =
    $_POST['fname'];

    $lname =
    $_POST['lname'];

    $email =
    $_POST['email'];

    $role =
    $_POST['role'];

    $sql =
    "
    UPDATE Users
    SET
    FirstName='$fname',
    LastName='$lname',
    Email='$email',
    FK_Role_ID='$role'
    WHERE User_ID='$id'
    ";

    $conn->query($sql);

    header(
    "Location: admin-roles.php"
    );
}

?>

<form method="POST">

    <h2>Edit User</h2>

    <input
    type="text"
    name="fname"
    value="<?php echo $user['FirstName']; ?>">

    <br><br>

    <input
    type="text"
    name="lname"
    value="<?php echo $user['LastName']; ?>">

    <br><br>

    <input
    type="email"
    name="email"
    value="<?php echo $user['Email']; ?>">

    <br><br>

    <select name="role">

        <option value="1">
            Admin
        </option>

        <option value="2">
            Professor
        </option>

        <option value="3">
            Student
        </option>

    </select>

    <br><br>

    <button
    type="submit"
    name="update">

        Update User

    </button>

</form>
