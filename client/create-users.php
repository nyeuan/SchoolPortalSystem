<?php

include 'db.php';

if(isset($_POST['create'])) {

    $fname = $_POST['fname'];
    $lname = $_POST['lname'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $sql = "
    INSERT INTO Users
    (
        FirstName,
        LastName,
        Email,
        PasswordHash,
        DateCreated,
        Status,
        FK_Role_ID
    )
    VALUES
    (
        '$fname',
        '$lname',
        '$email',
        '$password',
        NOW(),
        'Active',
        '$role'
    )
    ";

    $conn->query($sql);

    header("Location: admin-roles.php");
}

?>

<form method="POST">

    <h2>Create User</h2>

    <input
        type="text"
        name="fname"
        placeholder="First Name"
        required>

    <br><br>

    <input
        type="text"
        name="lname"
        placeholder="Last Name"
        required>

    <br><br>

    <input
        type="email"
        name="email"
        placeholder="Email"
        required>

    <br><br>

    <input
        type="password"
        name="password"
        placeholder="Password"
        required>

    <br><br>

    <select name="role">

        <option value="1">Admin</option>
        <option value="2">Professor</option>
        <option value="3">Student</option>

    </select>

    <br><br>

    <button
        type="submit"
        name="create">

        Create User

    </button>

</form>
