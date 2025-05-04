<?php
session_start(); // Start session at the beginning of the script

// Validate and set session variables
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['host'], $_POST['username'], $_POST['password'], $_POST['dbname'])) {
        $_SESSION['host'] = $_POST['host'];
        $_SESSION['username'] = $_POST['username'];
        $_SESSION['password'] = $_POST['password'];
        $_SESSION['dbname'] = $_POST['dbname'];
    }
}

if (!isset($_SESSION['host']) || !isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname'])) {
    ?>
    <form method="POST" action="">
        <p>Please enter your host:"localhost"</p>
        <input type="text" name="host" required>
        <p>Please enter your username:</p>
        <input type="text" name="username" required>
        <p>Please enter your password:</p>
        <input type="password" name="password" required>
        <p>Please enter your database name:</p>
        <input type="text" name="dbname" required>
        <input type="submit" value="Login">
    </form>
    <?php
    exit();
}

$host = $_SESSION['host'];
$user = $_SESSION['username'];
$pass = $_SESSION['password'];
$dbname = $_SESSION['dbname'];

// Establish database connection
$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    echo "Connected successfully<br>";
}

echo "MySQL Server Info: " . $conn->server_info . "<br>";

// Query to get all tables in the database
$sql = "SHOW TABLES";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    echo "Tables in the database:<br>";
    while ($row = $result->fetch_array()) {
        echo $row[0] . "<br>";
    }
} else {
    echo "No tables found in the database.";
}

$conn->close();
?>