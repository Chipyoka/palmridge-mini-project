<?php
// sse/status-stream.php

// ------------------------------
// SSE / Real-time property status stream
// ------------------------------

ignore_user_abort(true);   // keep running even if client disconnects
set_time_limit(0);         // no timeout
ob_implicit_flush(true);   // flush output immediately

// ------------------------------
// SSE headers
// ------------------------------
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive', true);
header('Access-Control-Allow-Origin: http://localhost:3000'); // adjust if needed

// ------------------------------
// Load DB config and connect
// ------------------------------
$config = require_once __DIR__ . '/../config/config.php';

$port = $config['db_port'] ?? 3306; // default MySQL port

$mysqli = new mysqli(
    $config['db_host'],
    $config['db_user'],
    $config['db_pass'],
    $config['db_name'],
    $port
);

if ($mysqli->connect_errno) {
    http_response_code(500);
    echo "data: {\"error\":\"Database connection failed: {$mysqli->connect_error}\"}\n\n";
    flush();
    exit;
}

// ------------------------------
// Initialize lastCheck timestamp
// ------------------------------
$lastCheck = date('Y-m-d H:i:s');

// ------------------------------
// SSE main loop
// ------------------------------
while (true) {
    // Prepare statement to fetch recently updated properties
    $stmt = $mysqli->prepare(
        "SELECT id, status, updated_at
         FROM properties
         WHERE updated_at > ?"
    );

    if (!$stmt) {
        echo "data: {\"error\":\"Failed to prepare statement: {$mysqli->error}\"}\n\n";
        flush();
        sleep(2);
        continue;
    }

    $stmt->bind_param('s', $lastCheck);

    if (!$stmt->execute()) {
        echo "data: {\"error\":\"Query execution failed: {$stmt->error}\"}\n\n";
        $stmt->close();
        flush();
        sleep(2);
        continue;
    }

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        echo "event: statusUpdate\n";
        echo "data: " . json_encode($row) . "\n\n";
    }

    $stmt->close();

    // Update lastCheck for next iteration
    $lastCheck = date('Y-m-d H:i:s');

    // Send heartbeat to keep connection alive
    echo ": heartbeat\n\n";

    ob_flush();
    flush();

    // Sleep briefly before next poll
    sleep(2);

    // Exit if client disconnected
    if (connection_aborted()) break;
}

// ------------------------------
// Close DB connection (optional)
// ------------------------------
$mysqli->close();
?>
