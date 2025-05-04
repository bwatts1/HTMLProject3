<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Save operation
    $name = isset($_GET['name']) ? $_GET['name'] : '';
    $grid = isset($_GET['grid']) ? $_GET['grid'] : '';

    // Define file path
    $filePath = 'grids/' . $name . '.txt';

    // Create the grid folder if it doesn't exist
    if (!file_exists('grids')) {
        mkdir('grids', 0777, true);
    }

    // Save the grid to a file
    if (file_put_contents($filePath, $grid)) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Save failed']);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Load operation
    $name = isset($_GET['name']) ? $_GET['name'] : '';

    // Define file path
    $filePath = 'grids/' . $name . '.txt';

    // Check if the file exists
    if (file_exists($filePath)) {
        // Get the grid data from the file
        $grid = file_get_contents($filePath);
        // Return the grid as a response
        echo json_encode(['success' => true, 'grid' => $grid]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Grid not found']);
    }
}
?>