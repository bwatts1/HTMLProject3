<?php
session_start();

if (!isset($_SESSION['host'], $_SESSION['username'], $_SESSION['password'], $_SESSION['dbname'])) {
    header("Location: SQL.php");
    exit();
}

// DB connection
$conn = new mysqli($_SESSION['host'], $_SESSION['username'], $_SESSION['password'], $_SESSION['dbname']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Fetch latest grid (if exists)
$gridStmt = $conn->prepare(
    "SELECT arr 
     FROM grids 
     WHERE grid = ? 
     ORDER BY updated_at DESC 
     LIMIT 1"
);
$gridStmt->bind_param("i", $_SESSION['name']);
$gridStmt->execute();
$gridResult = $gridStmt->get_result();
$latestGrid = $gridResult && $gridResult->num_rows > 0 ? $gridResult->fetch_assoc()['arr'] : null;
$gridStmt->close();

// Compute population size (count of 1s)
$population = 0;
$generationCount = 0;
if ($latestGrid) {
    $flat = str_split($latestGrid);
    $population = array_sum(array_map('intval', $flat));
    $generationCount = 0; // Update this if you store `generation` in DB
}

$conn->close();
?>

<div class="playerInfo">
    <img src="images/Default_Avatar.jpg" alt="default" width="200px" height="200px"> <br/>
    <label for="avatarImg">Upload your avatar</label>
    <input type="file" id="avatarImg" name="avatarImg"> <br/>
    <strong>Username:</strong> <?php echo htmlspecialchars($userData['name']); ?>
</div>

<div class="Game_stats">
    <h2>Game Stats</h2>
    <strong><span>Generation Count:</span></strong> <?php echo $generationCount; ?> <br/>
    <strong><span>Population Size:</span></strong> <?php echo $population; ?> <br/>
</div>
