<?php
    session_start();

    if (!isset($_SESSION['host']) || !isset($_SESSION['username']) || !isset($_SESSION['password']) || !isset($_SESSION['dbname'])) {
        header("Location: SQL.php");
        exit();
    }

    $conn = new mysqli($_SESSION['host'], $_SESSION['username'], $_SESSION['password'], $_SESSION['dbname']);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>admin dashboard</title>
</head>
<body>    
    <h2>View users Information</h2>
    <form method="post">
        <button type="submit" name="view_table">View Table</button>
    </form>
    <?php
        if (isset($_POST['view_table'])) {
            $sql = "SELECT * FROM users WHERE role = 'user'";
            $result = $conn->query($sql);

            if ($result && $result->num_rows > 0) {
                echo "<h3>Data in 'users'</h3>";
                echo "<table border='1'>";
                echo "<tr>";
                while ($field_info = $result->fetch_field()) {
                    echo "<th>" . $field_info->name . "</th>";
                }
                echo "</tr>";
                while ($row = $result->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No data found in table 'users' or table does not exist.</p>";
            }
        }
    ?>
    <h2>Edit User info</h2>
    <form method="post">
        <label for="user_id">Enter User ID to edit:</label>
        <input type="number" id="user_id" name="user_id" required>
        <button type="submit" name="edit_user">Edit User</button>
    </form>
    <?php
        if (isset($_POST['edit_user'])) {
            $user_id = intval($_POST['user_id']);
            $edit_sql = "SELECT * FROM users INNER JOIN gameSessions ON users.id = gameSessions.user WHERE users.id = ?";
            $stmt = $conn->prepare($edit_sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {
                $user_data = $result->fetch_assoc();
                echo "<form method='post'>";
                foreach ($user_data as $key => $value) {
                    if ($key !== 'id') { // Exclude ID from edit form
                        echo "<label for='$key'>" . ucfirst($key) . ":</label>";
                        echo "<input type='text' name='$key' id='$key' value='" . htmlspecialchars($value) . "' required><br>";
                    }
                }
                echo "<input type='hidden' name='user_id' value='$user_id'>";
                echo "<button type='submit' name='update_user'>Update User</button>";
                echo "</form>";
            } else {
                $edit_sql = "SELECT * FROM users WHERE id = ?";
                $stmt = $conn->prepare($edit_sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result && $result->num_rows > 0) {
                    $user_data = $result->fetch_assoc();
                    echo "<form method='post'>";
                    foreach ($user_data as $key => $value) {
                        if ($key !== 'id') { // Exclude ID from edit form
                            echo "<label for='$key'>" . ucfirst($key) . ":</label>";
                            echo "<input type='text' name='$key' id='$key' value='" . htmlspecialchars($value) . "' required><br>";
                        }
                    }
                    echo "<input type='hidden' name='user_id' value='$user_id'>";
                    echo "<button type='submit' name='update_user'>Update User</button>";
                    echo "</form>";
                } else {
                    echo "<p>No user found with ID $user_id.</p>";
                }
            }

            $stmt->close();
        }

        if (isset($_POST['update_user'])) {
            $user_id = intval($_POST['user_id']);
            
            // Check if the user has the role 'admin'
            $role_check_sql = "SELECT role FROM users WHERE id = ?";
            $role_stmt = $conn->prepare($role_check_sql);
            $role_stmt->bind_param("i", $user_id);
            $role_stmt->execute();
            $role_result = $role_stmt->get_result();
            
            if ($role_result && $role_result->num_rows > 0) {
                $user_role = $role_result->fetch_assoc()['role'];
                if ($user_role === 'admin') {
                    echo "<p>Cannot update accounts with the role 'admin'.</p>";
                    $role_stmt->close();
                    return;
                }
            } else {
            echo "<p>User not found.</p>";
            $role_stmt->close();
            return;
            }
            $role_stmt->close();

            $update_data = $_POST;
            unset($update_data['update_user'], $update_data['user_id']); // Remove unnecessary fields

            // Prevent updating primary keys
            if (isset($update_data['id'])) {
            unset($update_data['id']);
            }

            // Prepare the update statement
            $set_clause = implode(", ", array_map(function($key) { return "$key = ?"; }, array_keys($update_data)));
            $types = str_repeat('s', count($update_data)); // Assuming all values are strings
            
            $update_sql = "UPDATE users SET $set_clause WHERE id = ?";
            $stmt = $conn->prepare($update_sql);
            
            if ($stmt) {
            // Bind parameters dynamically
            $params = array_values($update_data);
            $params[] = $user_id; // Add user ID to the end
            array_unshift($params, $types . 'i'); // Prepend types and user ID type

            call_user_func_array([$stmt, 'bind_param'], array_values($params));
            
            if ($stmt->execute()) {
                echo "<p>User with ID $user_id has been updated successfully.</p>";
            } else {
                echo "<p>Error updating user: " . $stmt->error . "</p>";
            }
            $stmt->close();
            } else {
            echo "<p>Preparation failed: " . $conn->error . "</p>";
            }
        }
    ?>
    <h2>Delete User</h2>
    <form method="post">
        <label for="user_id">Enter User ID to delete:</label>
        <input type="number" id="user_id" name="user_id" required>
        <button type="submit" name="delete_user">Delete User</button>
    </form>
    <?php
        if (isset($_POST['delete_user'])) {
            $user_id = intval($_POST['user_id']);
            $delete_sql = "DELETE FROM users WHERE id = ?";
            $stmt = $conn->prepare($delete_sql);
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    echo "<p>User with ID $user_id has been deleted successfully.</p>";
                } else {
                    echo "<p>No user found with ID $user_id.</p>";
                }
            } else {
                echo "<p>Error deleting user: " . $stmt->error . "</p>";
            }

            $stmt->close();
        }
    ?>
    <h2>view game sessions</h2>
    <form method="post">
        <label for="column">Select Column:</label>
        <select id="column" name="column" required>
            <option value="column1">Column 1</option>
            <option value="column2">Column 2</option>
            <option value="column3">Column 3</option>
        </select>
        <label for="value">Enter Value:</label>
        <button type="submit" name="view_sessions">View Game Sessions</button>
    </form>
    <?php
        if (isset($_POST['view_sessions'])) {
            $allowed_columns = ['column1', 'column2', 'column3'];
            $column = $_POST['column'];
            
            if (!in_array($column, $allowed_columns)) {
                echo "<p>Invalid column selected.</p>";
                return;
            }

            $session_sql = "SELECT * FROM gameSessions WHERE $column >= ? ORDER BY $column DESC";
            $stmt = $conn->prepare($session_sql);
            $value = intval($_POST['value']);
            $stmt->bind_param("i", $value);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($session_result && $session_result->num_rows > 0) {
                echo "<h3>Game Sessions</h3>";
                echo "<table border='1'>";
                echo "<tr>";
                while ($field_info = $session_result->fetch_field()) {
                    echo "<th>" . $field_info->name . "</th>";
                }
                echo "</tr>";
                while ($row = $session_result->fetch_assoc()) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p>No game sessions found.</p>";
            }
        }
    ?>
</body>
</html>
<?php $conn->close(); ?>