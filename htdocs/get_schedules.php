<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

// Fetch all schedules with event and client information
$stmt = $conn->prepare("SELECT s.ScheduleID, s.start_date_time, s.end_date_time, s.date_schedule, 
                        e.Events_name, e.EventID, c.clients_name, us.updated_status 
                        FROM Schedule s
                        JOIN Events e ON s.EventID = e.EventID
                        JOIN Clients c ON e.ClientID = c.ClientID
                        JOIN Updated_Status us ON s.StatusID = us.StatusID");
$stmt->execute();
$result = $stmt->get_result();

$schedules = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $schedules[] = [
            'id' => $row['ScheduleID'],
            'event_name' => $row['Events_name'],
            'event_id' => $row['EventID'],
            'client_name' => $row['clients_name'],
            'start_date_time' => $row['start_date_time'],
            'end_date_time' => $row['end_date_time'],
            'date_schedule' => $row['date_schedule'],
            'status' => $row['updated_status']
        ];
    }
}

echo json_encode($schedules);
?>