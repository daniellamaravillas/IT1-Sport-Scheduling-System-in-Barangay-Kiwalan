<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('Asia/Manila');
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $startDT = $_POST['start_date_time'] ?? '';
    $endDT = $_POST['end_date_time'] ?? '';
    if (!$startDT || !$endDT) {
        echo json_encode(['error' => 'Invalid input.']);
        exit();
    }
    // Overlap check: existing.start_date_time < new_end and existing.end_date_time > new_start
    $stmt = $conn->prepare("SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name, c.clients_name 
                           FROM Schedule s
                           JOIN Events e ON s.EventID = e.EventID 
                           JOIN Clients c ON e.ClientID = c.ClientID
                           WHERE (? BETWEEN s.start_date_time AND s.end_date_time)
                           OR (? BETWEEN s.start_date_time AND s.end_date_time)
                           OR (s.start_date_time BETWEEN ? AND ?)
                           OR (s.end_date_time BETWEEN ? AND ?)");
    
    $stmt->bind_param("ssssss", $startDT, $endDT, $startDT, $endDT, $startDT, $endDT);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Found a conflict
        $response = [
            'conflict' => true,
            'event_name' => $row['Events_name'],
            'client_name' => $row['clients_name'],
            'start_time' => date('F j, Y, g:i A', strtotime($row['start_date_time'])),
            'end_time' => date('F j, Y, g:i A', strtotime($row['end_date_time'])),
            'suggestions' => []
        ];
        
        // Get next available slot
        $next_slot_start = $row['end_date_time'];
        $next_slot_end = date('Y-m-d H:i:s', strtotime($next_slot_start . ' + 1 hour'));
        $response['suggestions'][] = "Next available time: " . date('F j, Y, g:i A', strtotime($next_slot_start));
        
        echo json_encode($response);
    } else {
        echo json_encode(['conflict' => false]);
    }
    $stmt->close();
    exit();
}
?>
