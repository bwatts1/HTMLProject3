<?php
session_start();

if (!isset($_SESSION['host'], $_SESSION['username'], $_SESSION['password'], $_SESSION['dbname'])) {
    header("Location: SQL.php");
    exit();
}

$conn = new mysqli($_SESSION['host'], $_SESSION['username'], $_SESSION['password'], $_SESSION['dbname']);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (isset($_POST['insert_full_row'])) {
    $row_data = $_POST['row_data'] ?? [];

    if (!empty($row_data)) {
        // Hash the password before inserting
        if (isset($row_data['pass'])) {
            $row_data['pass'] = password_hash($row_data['pass'], PASSWORD_DEFAULT);
        }           

        $row_data['role'] = 'user';

        $columns = implode(", ", array_keys($row_data));
        $placeholders = implode(", ", array_fill(0, count($row_data), '?'));
        $types = str_repeat('s', count($row_data)); // assuming all values are strings

        $stmt = $conn->prepare("INSERT INTO users ($columns) VALUES ($placeholders)");

        if ($stmt) {
            $stmt->bind_param($types, ...array_values($row_data));

            if ($stmt->execute()) {
                echo "<p>Account created successfully!</p>";
                header("Location: login.php");
                exit();
            } else {
                echo "<p>Error inserting row: " . $stmt->error . "</p>";
            }
            $stmt->close();
        } else {
            echo "<p>Preparation failed: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Missing form data.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Database Interface</title>
</head>
<body>
    <h2>Register</h2>
    <form method="post">

        <label for="row_data[name]">Name:</label>
        <input type="text" name="row_data[name]" id="row_data[name]" required>
        <br><br>

        <label for="row_data[email]">Email:</label>
        <input type="email" name="row_data[email]" id="row_data[email]" required>
        <br><br>

        <label for="row_data[pass]">Password:</label>
        <input type="password" name="row_data[pass]" id="row_data[pass]" required>
        <br><br>

        <button type="submit" name="insert_full_row">Create Account</button>
    </form>
</body>
</html>
