<?php
session_start();
include('connect.php');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$tasks = $data['tasks'] ?? null;  // Change 'item' to 'tasks'
$action = $data['action'] ?? null; // 'add', 'remove', or 'fetch'

// Initialize response array
$response = [];

// Fetch all to-do items
if ($action === 'fetch') {
    $query = "SELECT tasks FROM todo_list";
    $result = $conn->query($query);
    
    if ($result) {
        $todos = [];
        while ($row = $result->fetch_assoc()) {
            $todos[] = $row['tasks'];
        }
        $response = ['status' => 'success', 'data' => $todos];
    } else {
        $response = ['status' => 'error', 'message' => 'Failed to fetch todos: ' . $conn->error];
    }
} else {
    try {
        // Check action type and perform accordingly
        if ($action === 'add') {
            // Prepare to insert a new todo item
            $query = "INSERT INTO todo_list (tasks) VALUES (?)"; // Use 'tasks' instead of 'item'
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $tasks); // Change 'item' to 'tasks'

            if ($stmt->execute()) {
                resetAutoIncrement($conn); // Call function to reset auto-increment after adding
                $response = ['status' => 'success', 'message' => 'Todo added successfully.'];
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to add todo: ' . $stmt->error];
            }
        } elseif ($action === 'remove') {
            // Prepare to delete a todo item
            $query = "DELETE FROM todo_list WHERE tasks = ?"; // Use 'tasks' instead of 'item'
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $tasks); // Change 'item' to 'tasks'

            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    resetAutoIncrement($conn); // Call function to reset auto-increment after deletion
                    $response = ['status' => 'success', 'message' => 'Todo removed successfully.'];
                } else {
                    $response = ['status' => 'error', 'message' => 'Todo item not found.'];
                }
            } else {
                $response = ['status' => 'error', 'message' => 'Failed to remove todo: ' . $stmt->error];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'Invalid action.'];
        }
    } catch (Exception $e) {
        $response = ['status' => 'error', 'message' => 'An unexpected error occurred: ' . $e->getMessage()];
    }
}

// Function to reset the auto-increment value
function resetAutoIncrement($conn) {
    // Get the maximum ID from the table
    $result = $conn->query("SELECT MAX(id) AS max_id FROM todo_list");
    $row = $result->fetch_assoc();
    $maxId = $row['max_id'] ?? 0;

    // Reset the auto-increment value
    $newAutoIncrementValue = $maxId + 1; // Set it to max ID + 1
    $conn->query("ALTER TABLE todo_list AUTO_INCREMENT = $newAutoIncrementValue");
}

// Return response as JSON
echo json_encode($response);

if (isset($stmt)) {
    $stmt->close();
}
$conn->close();
?>
