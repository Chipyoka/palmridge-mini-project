<?php
// sse/status-stream.php
ignore_user_abort(true);
set_time_limit(0);
ob_implicit_flush(true);

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive', true);
header('Access-Control-Allow-Origin: http://localhost:3000');

require_once __DIR__ . '/../includes/db.php'; // adjust the path if db.php is elsewhere

$lastCheck = date('Y-m-d H:i:s');

while (true) {
    $stmt = $mysqli->prepare(
        "SELECT id, status, updated_at
         FROM properties
         WHERE updated_at > ?"
    );
    $stmt->bind_param('s', $lastCheck);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        echo "event: statusUpdate\n";
        echo "data: " . json_encode($row) . "\n\n";
    }
    $stmt->close();

    $lastCheck = date('Y-m-d H:i:s');

    echo ": heartbeat\n\n";
    ob_flush();
    flush();
    sleep(2);

    if (connection_aborted()) break;
}
?>
