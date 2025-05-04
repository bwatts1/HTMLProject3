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

$sql = "SHOW TABLES";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "Tables in the database:<br>";
    while ($row = $result->fetch_array()) {
        echo "<strong>" . $row[0] . "</strong><br>";

        $columns_sql = "SHOW COLUMNS FROM " . $row[0];
        $columns_result = $conn->query($columns_sql);

        if ($columns_result->num_rows > 0) {
            echo "Columns:<br>";
            while ($column = $columns_result->fetch_assoc()) {
                echo "- " . $column['Field'] . " (" . $column['Type'] . ")<br>";
            }
        } else {
            echo "No columns found for this table.<br>";
        }
        echo "<br>";
    }
} else {
    echo "No tables found in the database.";
}

// Add new table
if (isset($_POST['add_table'])) {
    $table_name = $_POST['table_name'];
    $columns = $_POST['columns'];

    $column_defs = [];

    foreach ($columns as $col) {
        $name = $col['name'];
        $type = $col['type'];
        $null = isset($col['null']) ? '' : 'NOT NULL';
        $column_defs[] = "$name $type $null";
    }

    $sql = "CREATE TABLE $table_name (
        id INT AUTO_INCREMENT PRIMARY KEY,
        " . implode(", ", $column_defs) . "
    )";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Table '$table_name' created successfully.</p>";
    } else {
        echo "<p>Error creating table: " . $conn->error . "</p>";
    }
}

// Delete table
if (isset($_POST['delete_table'])) {
    $delete_table_name = $_POST['delete_table_name'];
    $sql = "DROP TABLE $delete_table_name";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Table '$delete_table_name' deleted successfully.</p>";
    } else {
        echo "<p>Error deleting table: " . $conn->error . "</p>";
    }
}

// Insert single value
if (isset($_POST['add_info'])) {
    $table_name_add_info = $_POST['table_name_add_info'];
    $column_name = $_POST['column_name'];
    $info_value = $_POST['info_value'];

    $sql = "INSERT INTO $table_name_add_info ($column_name) VALUES ('$info_value')";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Information added successfully to table '$table_name_add_info'.</p>";
    } else {
        echo "<p>Error adding information: " . $conn->error . "</p>";
    }
}

// Load columns for full row insertion
if (isset($_POST['load_table_columns'])) {
    $row_table_name = $_POST['row_table_name'];
    $_SESSION['row_table_name'] = $row_table_name;
    $columns_result = $conn->query("SHOW COLUMNS FROM $row_table_name");

    if ($columns_result && $columns_result->num_rows > 0) {
        echo "<h3>Insert Row into '$row_table_name'</h3>";
        echo "<form method='post'>";
        echo "<input type='hidden' name='final_table_name' value='$row_table_name'>";
        while ($column = $columns_result->fetch_assoc()) {
            $field = $column['Field'];
            if (strtolower($field) === 'id' && strpos($column['Extra'], 'auto_increment') !== false) {
                continue;
            }
            echo "<label for='col_$field'>$field:</label>";
            echo "<input type='text' name='row_data[$field]' id='col_$field' required><br>";
        }
        echo "<button type='submit' name='insert_full_row'>Insert Row</button>";
        echo "</form>";
    } else {
        echo "<p>Error loading columns: " . $conn->error . "</p>";
    }
}

// Insert full row into table
if (isset($_POST['insert_full_row'])) {
    $final_table_name = $_POST['final_table_name'];
    $row_data = $_POST['row_data'];

    $columns = implode(", ", array_keys($row_data));
    $values = "'" . implode("', '", array_values($row_data)) . "'";

    $sql = "INSERT INTO $final_table_name ($columns) VALUES ($values)";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Row inserted successfully into '$final_table_name'.</p>";
    } else {
        echo "<p>Error inserting row: " . $conn->error . "</p>";
    }
}

// Clear session
if (isset($_POST['clear_sessions'])) {
    session_unset();
    session_destroy();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

if (!isset($_SESSION['example'])) {
    $_SESSION['example'] = "This is an active session.";
}
if (isset($_POST['view_table'])) {
    $view_table_name = $_POST['view_table_name'];
    $sql = "SELECT * FROM $view_table_name";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        echo "<h3>Data in '$view_table_name'</h3>";
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
        echo "<p>No data found in table '$view_table_name' or table does not exist.</p>";
    }
}
if (isset($_POST['encrypt_column'])) {
    $encrypt_table_name = $_POST['encrypt_table_name'];
    $encrypt_column_name = $_POST['encrypt_column_name'];
    $encrypt_id = $_POST['encrypt_id']; // Get the specific ID

    // Sanitize inputs to prevent SQL injection
    $encrypt_table_name = $conn->real_escape_string($encrypt_table_name);
    $encrypt_column_name = $conn->real_escape_string($encrypt_column_name);
    $encrypt_id = (int) $encrypt_id; // Ensure the ID is treated as an integer

    // Fetch the value of the specified column for the given ID
    $sql = "SELECT id, $encrypt_column_name FROM $encrypt_table_name WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $encrypt_id);  // Bind the ID as an integer
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $original_value = $row[$encrypt_column_name];

        // Check if the value is already encrypted (use a more robust check here if necessary)
        if (password_verify($original_value, $original_value)) {
            echo "<p>Data for ID $encrypt_id is already encrypted. Skipping.</p>";
        } else {
            // Encrypt the value
            $encrypted_value = password_hash($original_value, PASSWORD_DEFAULT);

            // Update the table with the encrypted value
            $update_sql = "UPDATE $encrypt_table_name SET $encrypt_column_name = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $encrypted_value, $encrypt_id);  // Bind the encrypted value and ID
            if ($update_stmt->execute()) {
                echo "<p>Column '$encrypt_column_name' for ID $encrypt_id encrypted successfully.</p>";
            } else {
                echo "<p>Error encrypting data for ID $encrypt_id: " . $conn->error . "</p>";
            }
        }
    } else {
        echo "<p>No data found for ID $encrypt_id in column '$encrypt_column_name' of table '$encrypt_table_name'.</p>";
    }
}
if (isset($_POST['create_users_table'])) {
    $sql = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        pass VARCHAR(255) NOT NULL,
        role VARCHAR(255) NOT NULL
    )";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Users table created successfully.</p>";
    } else {
        echo "<p>Error creating users table: " . $conn->error . "</p>";
    }
}
if (isset($_POST['create_game_sessions_table'])) {
    $sql = "CREATE TABLE IF NOT EXISTS gameSessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user VARCHAR(255),
        Start_Time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        End_Time INT,
        Generations INT

    )";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Game Sessions table created successfully.</p>";
    } else {
        echo "<p>Error creating gameSessions table: " . $conn->error . "</p>";
    }
}
if (isset($_POST['create_grid_table'])) {
    $sql = "CREATE TABLE IF NOT EXISTS grids (
        grid VARCHAR(255) PRIMARY KEY,
        arr TEXT
    )";

    if ($conn->query($sql) === TRUE) {
        echo "<p>Grid table created successfully.</p>";
    } else {
        echo "<p>Error creating grid table: " . $conn->error . "</p>";
    }
}
if (isset($_POST['insert_pattern'])) {
    $grid_pattern = $_POST['grid_pattern']; 
    $grid_name = $_POST['grid_name'];

    $sql = "INSERT INTO grids (grid, arr) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $grid_name, $grid_pattern);

    if ($stmt->execute()) {
        echo "<p>Pattern and name inserted successfully into the grids table.</p>";
    } else {
        echo "<p>Error inserting pattern and name: " . $conn->error . "</p>";
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
    <h1>Active Sessions</h1>
    <ul>
        <?php
        if (!empty($_SESSION)) {
            foreach ($_SESSION as $key => $value) {
                echo "<li><strong>$key:</strong> $value</li>";
            }
        } else {
            echo "<li>No active sessions.</li>";
        }
        ?>
    </ul>
    <form method="post">
        <button type="submit" name="clear_sessions">Clear Sessions</button>
    </form>

    <h2>Add Table to Database</h2>
    <form method="post">
        <label for="table_name">Table Name:</label>
        <input type="text" id="table_name" name="table_name" required>
        <div id="columns">
            <div class="column">
                <label for="column_name_1">Column Name:</label>
                <input type="text" id="column_name_1" name="columns[1][name]" required>
                <label for="column_type_1">Column Type:</label>
                <select id="column_type_1" name="columns[1][type]" required>
                    <option value="VARCHAR(255)">VARCHAR(255)</option>
                    <option value="INT">INT</option>
                    <option value="TEXT">TEXT</option>
                    <option value="DATE">DATE</option>
                </select>
                <label for="column_null_1">Allow NULL:</label>
                <input type="checkbox" id="column_null_1" name="columns[1][null]">
            </div>
        </div>
        <button type="button" onclick="addColumn()">Add Column</button>
        <button type="submit" name="add_table">Add Table</button>
    </form>

    <script>
        let columnCount = 1;

        function addColumn() {
            columnCount++;
            const columnsDiv = document.getElementById('columns');
            const newColumnDiv = document.createElement('div');
            newColumnDiv.classList.add('column');
            newColumnDiv.innerHTML = `
                <label for="column_name_${columnCount}">Column Name:</label>
                <input type="text" id="column_name_${columnCount}" name="columns[${columnCount}][name]" required>
                <label for="column_type_${columnCount}">Column Type:</label>
                <select id="column_type_${columnCount}" name="columns[${columnCount}][type]" required>
                    <option value="VARCHAR(255)">VARCHAR(255)</option>
                    <option value="INT">INT</option>
                    <option value="TEXT">TEXT</option>
                    <option value="DATE">DATE</option>
                </select>
                <label for="column_null_${columnCount}">Allow NULL:</label>
                <input type="checkbox" id="column_null_${columnCount}" name="columns[${columnCount}][null]">
            `;
            columnsDiv.appendChild(newColumnDiv);
        }
    </script>

    <h2>Delete Table from Database</h2>
    <form method="post">
        <label for="delete_table_name">Table Name:</label>
        <input type="text" id="delete_table_name" name="delete_table_name" required>
        <button type="submit" name="delete_table">Delete Table</button>
    </form>

    <h2>Add Information to a Table (One Column Only)</h2>
    <form method="post">
        <label for="table_name_add_info">Table Name:</label>
        <input type="text" id="table_name_add_info" name="table_name_add_info" required>
        <label for="column_name">Column Name:</label>
        <input type="text" id="column_name" name="column_name" required>
        <label for="info_value">Value:</label>
        <input type="text" id="info_value" name="info_value" required>
        <button type="submit" name="add_info">Add Information</button>
    </form>

    <h2>Add Full Row to Table</h2>
    <form method="post">
        <label for="row_table_name">Select Table:</label>
        <input type="text" name="row_table_name" id="row_table_name" required>
        <button type="submit" name="load_table_columns">Load Columns</button>
    </form>
    <h2>View Table Information</h2>
    <form method="post">
        <label for="view_table_name">Select Table:</label>
        <input type="text" name="view_table_name" id="view_table_name" required>
        <button type="submit" name="view_table">View Table</button>
    </form>
    <h2>Encrypt Column Data for Specific ID</h2>
    <form method="post">
        <label for="encrypt_table_name">Table Name:</label>
        <input type="text" id="encrypt_table_name" name="encrypt_table_name" required>
        <label for="encrypt_column_name">Column Name:</label>
        <input type="text" id="encrypt_column_name" name="encrypt_column_name" required>
        <label for="encrypt_id">Enter ID to Encrypt:</label>
        <input type="number" id="encrypt_id" name="encrypt_id" required>
        <button type="submit" name="encrypt_column">Encrypt Column</button>
    </form>
</body>
</html>
<h2>Create Users Table</h2>
<form method="post">
    <input type="hidden" name="table_name" value="users">
    <button type="submit" name="create_users_table">Create Users Table</button>
</form>
<h2>Create Game Sessions Table</h2>
<form method="post">
    <input type="hidden" name="table_name" value="gameSessions">
    <button type="submit" name="create_game_sessions_table">Create Game Sessions Table</button>
</form>
<h2>Create Grid Table</h2>
<form method="post">
    <input type="hidden" name="table_name" value="grids">
    <button type="submit" name="create_grid_table">Create Grid Table</button>
</form>
<h2>Insert Pattern into Grids Table</h2>
<form method="post">
    <label for="grid_name">Grid Name:</label>
    <input type="text" id="grid_name" name="grid_name" required>
    <label for="grid_pattern">Grid Pattern (Integer):</label>
    <input type="number" id="grid_pattern" name="grid_pattern" required>
    <button type="submit" name="insert_pattern">Insert Pattern</button>
</form>
<h2>Get Grid Pattern</h2>
<form method="post">
    <label for="grid_name_search">Grid Name:</label>
    <input type="text" id="grid_name_search" name="grid_name_search" required>
    <button type="submit" name="get_grid_pattern">Get Pattern</button>
</form>

<?php
if (isset($_POST['get_grid_pattern'])) {
    $grid_name_search = $_POST['grid_name_search'];

    // Sanitize input to prevent SQL injection
    $grid_name_search = $conn->real_escape_string($grid_name_search);

    $sql = "SELECT arr FROM grids WHERE grid = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $grid_name_search);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $grid_pattern = $row['arr'];

        // Convert the grid pattern into an array
        $grid_array = json_decode($grid_pattern, true);

        echo "<h3>Pattern for Grid '$grid_name_search'</h3>";
        echo "<pre>" . print_r($grid_array, true) . "</pre>";
    } else {
        echo "<p>No pattern found for grid '$grid_name_search'.</p>";
    }
}
//BLOCK 0000000000000000000000000110000000000110000000000000000000000000000000000000000000000000000000000000
//BOAT 0000000000000000000010110000000101000000010000000000000000000000000000000000000000000000000000000000
//BEEHIVE 0000000000000000000000110000010010000000110000000000000000000000000000000000000000000000000000000000
//BLINKER 0000000000000000000000000000011100000000000000000000000000000000000000000000000000000000000000000000
//BEACON 0000000000000000000001100000110000000011000000110000000000000000000000000000000000000000000000000000
//GLIDER 0000000000000000000000010000000001000000111000000000000000000000000000000000000000000000000000000000
//GOSPER_GLIDER_GUN 000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000100000000000001100000000000000000011000000000000001100000000000000000011000000000000000001100000000000000000000000000000001100000000000000000000000000000000001100000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000000

?>
<?php $conn->close(); ?>
