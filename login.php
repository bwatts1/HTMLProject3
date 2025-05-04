<?php
session_start();

if (!isset($_SESSION['host'], $_SESSION['username'], $_SESSION['password'], $_SESSION['dbname'])) {
    header("Location: SQL.php"); // Redirect if not
    exit();
}

$conn = new mysqli($_SESSION['host'], $_SESSION['username'], $_SESSION['password'], $_SESSION['dbname']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['username'], $_POST['password'])) {
        $email = $_POST['username'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        if ($stmt) {
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();


            if ($row && password_verify($password, $row['pass'])) {
                $_SESSION['authorized'] = "ok";
                $_SESSION['name'] = $row['name'];
                $_SESSION['role'] = $row['role'];

                if ($row['role'] === 'admin') {
                    header("Location: admin_dashboard.php");
                    exit();
                } else {
                    header("Location: main.html");
                    exit();
                }
            } else {
                echo "<p style='color: red;'>Invalid username or password.</p>";
            }

            $stmt->close();
        } else {
            echo "<p style='color: red;'>Database error: " . $conn->error . "</p>";
        }
    }
}

$conn->close();
?>

<body>
<h1>Login</h1>
    <h>Login</h>
    <form method="POST" action="login.php">
        <label for="username">Email:</label>
        <input type="text" id="username" name="username" required>
        <br>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <button type="submit">Login</button>
    </form>
</body>