<?php
session_start();
include 'db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

$startDateTime = $_POST['start_date_time'];
$endDateTime = $_POST['end_date_time'];
$dateSchedule = $_POST['date_schedule'];

// Convert to DateTime objects for easier manipulation
$startDT = new DateTime($startDateTime);
$endDT = new DateTime($endDateTime);
$scheduleDT = new DateTime($dateSchedule);
$date = $scheduleDT->format('Y-m-d');

// Check for conflicts with the requested time slot
$stmtOverlap = $conn->prepare(
    "SELECT s.ScheduleID, s.start_date_time, s.end_date_time, e.Events_name, c.clients_name 
    FROM Schedule s
    JOIN Events e ON s.EventID = e.EventID
    JOIN Clients c ON e.ClientID = c.ClientID
    WHERE DATE(s.date_schedule) = DATE(?) 
    AND (
        (? BETWEEN s.start_date_time AND s.end_date_time) OR 
        (? BETWEEN s.start_date_time AND s.end_date_time) OR
        (s.start_date_time BETWEEN ? AND ?) OR
        (s.end_date_time BETWEEN ? AND ?)
    )"
);
$stmtOverlap->bind_param("sssssss", 
    $dateSchedule, 
    $startDateTime, $endDateTime,
    $startDateTime, $endDateTime,
    $startDateTime, $endDateTime
);
$stmtOverlap->execute();
$overlapResult = $stmtOverlap->get_result();

$response = [];

if ($overlapResult->num_rows > 0) {
    $conflictRow = $overlapResult->fetch_assoc();
    $conflictStart = date('h:i A', strtotime($conflictRow['start_date_time']));
    $conflictEnd = date('h:i A', strtotime($conflictRow['end_date_time']));
    
    // Store conflict details
    $response['conflict'] = [
        'event_name' => $conflictRow['Events_name'],
        'client_name' => $conflictRow['clients_name'],
        'start_time' => $conflictStart,
        'end_time' => $conflictEnd
    ];
    
    // Get all existing schedules for the requested date to find valid gaps
    $stmt = $conn->prepare(
        "SELECT s.start_date_time, s.end_date_time 
        FROM Schedule s 
        WHERE DATE(s.date_schedule) = ? 
        ORDER BY s.start_date_time ASC"
    );
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Build array of occupied time slots
    $occupiedSlots = [];
    while ($row = $result->fetch_assoc()) {
        $occupiedSlots[] = [
            'start' => new DateTime($row['start_date_time']),
            'end' => new DateTime($row['end_date_time'])
        ];
    }
    
    // Business hours and duration
    $businessStart = 8;  // 8:00 AM
    $businessEnd = 19;   // 7:00 PM
    $duration = ($endDT->getTimestamp() - $startDT->getTimestamp()) / 60; // in minutes
    
    // Generate suggestions for current day and next day
    $currentDaySuggestions = generateSuggestionsForDay($date, $occupiedSlots, $duration, $businessStart, $businessEnd);
    
    // If we found less than 2 viable slots, also look at tomorrow
    if (count($currentDaySuggestions) < 2) {
        $nextDay = (clone $scheduleDT)->modify('+1 day');
        $nextDayDate = $nextDay->format('Y-m-d');
        
        // Get schedules for the next day
        $stmtNextDay = $conn->prepare(
            "SELECT s.start_date_time, s.end_date_time 
            FROM Schedule s 
            WHERE DATE(s.date_schedule) = ? 
            ORDER BY s.start_date_time ASC"
        );
        $stmtNextDay->bind_param("s", $nextDayDate);
        $stmtNextDay->execute();
        $nextDayResult = $stmtNextDay->get_result();
        
        $nextDayOccupiedSlots = [];
        while ($row = $nextDayResult->fetch_assoc()) {
            $nextDayOccupiedSlots[] = [
                'start' => new DateTime($row['start_date_time']),
                'end' => new DateTime($row['end_date_time'])
            ];
        }
        
        $nextDaySuggestions = generateSuggestionsForDay($nextDayDate, $nextDayOccupiedSlots, $duration, $businessStart, $businessEnd);
        
        // Combine suggestions
        $allSuggestions = array_merge($currentDaySuggestions, $nextDaySuggestions);
        
        // If we still don't have enough suggestions, try one more day
        if (count($allSuggestions) < 3) {
            $dayAfterNext = (clone $nextDay)->modify('+1 day');
            $dayAfterNextDate = $dayAfterNext->format('Y-m-d');
            
            $stmtDayAfter = $conn->prepare(
                "SELECT s.start_date_time, s.end_date_time 
                FROM Schedule s 
                WHERE DATE(s.date_schedule) = ? 
                ORDER BY s.start_date_time ASC"
            );
            $stmtDayAfter->bind_param("s", $dayAfterNextDate);
            $stmtDayAfter->execute();
            $dayAfterResult = $stmtDayAfter->get_result();
            
            $dayAfterOccupiedSlots = [];
            while ($row = $dayAfterResult->fetch_assoc()) {
                $dayAfterOccupiedSlots[] = [
                    'start' => new DateTime($row['start_date_time']),
                    'end' => new DateTime($row['end_date_time'])
                ];
            }
            
            $dayAfterSuggestions = generateSuggestionsForDay($dayAfterNextDate, $dayAfterOccupiedSlots, $duration, $businessStart, $businessEnd);
            $allSuggestions = array_merge($allSuggestions, $dayAfterSuggestions);
        }
    } else {
        $allSuggestions = $currentDaySuggestions;
    }
    
    // Format available slots for JSON response (limited to top 5 suggestions)
    $suggestions = [];
    foreach (array_slice($allSuggestions, 0, 5) as $slot) {
        $suggestions[] = [
            'start' => $slot['start']->format('Y-m-d H:i:s'),
            'end' => $slot['end']->format('Y-m-d H:i:s'),
            'start_formatted' => $slot['start']->format('D, M j, Y g:i A'),
            'end_formatted' => $slot['end']->format('g:i A')
        ];
    }
    
    $response['suggestions'] = $suggestions;
    
    // Add a message if we're suggesting a different day
    if (count($currentDaySuggestions) == 0) {
        $response['message'] = "No available times found for today. Here are slots for the next available days.";
    }
} else {
    $response['conflict'] = false;
}

echo json_encode($response);

/**
 * Generate time slot suggestions for a specific day
 */
function generateSuggestionsForDay($date, $occupiedSlots, $duration, $businessStart, $businessEnd) {
    $suggestions = [];
    
    // Create DateTime objects for business hours
    $dayStart = new DateTime($date . ' ' . sprintf('%02d:00:00', $businessStart));
    $dayEnd = new DateTime($date . ' ' . sprintf('%02d:00:00', $businessEnd));
    
    // Sort occupied slots
    usort($occupiedSlots, function($a, $b) {
        return $a['start']->getTimestamp() - $b['start']->getTimestamp();
    });
    
    // No schedules for this day - offer standard time slots
    if (empty($occupiedSlots)) {
        // Early morning slot
        $suggestions[] = [
            'start' => clone $dayStart,
            'end' => (clone $dayStart)->modify("+{$duration} minutes")
        ];
        
        // Mid-morning slot
        $midMorningStart = new DateTime($date . ' 10:00:00');
        if ($midMorningStart > $dayStart && $midMorningStart->modify("+{$duration} minutes") <= $dayEnd) {
            $suggestions[] = [
                'start' => clone $midMorningStart,
                'end' => (clone $midMorningStart)->modify("+{$duration} minutes")
            ];
        }
        
        // Afternoon slot
        $afternoonStart = new DateTime($date . ' 14:00:00');
        if ($afternoonStart > $dayStart && $afternoonStart->modify("+{$duration} minutes") <= $dayEnd) {
            $suggestions[] = [
                'start' => clone $afternoonStart,
                'end' => (clone $afternoonStart)->modify("+{$duration} minutes")
            ];
        }
        
        // Late afternoon slot
        $lateAfternoonStart = new DateTime($date . ' 16:00:00');
        if ($lateAfternoonStart > $dayStart && $lateAfternoonStart->modify("+{$duration} minutes") <= $dayEnd) {
            $suggestions[] = [
                'start' => clone $lateAfternoonStart,
                'end' => (clone $lateAfternoonStart)->modify("+{$duration} minutes")
            ];
        }
        
        return $suggestions;
    }
    
    // Check for slot before first schedule
    if ($dayStart < $occupiedSlots[0]['start']) {
        $gapDuration = ($occupiedSlots[0]['start']->getTimestamp() - $dayStart->getTimestamp()) / 60;
        
        if ($gapDuration >= $duration) {
            // Early slot
            $suggestions[] = [
                'start' => clone $dayStart,
                'end' => (clone $dayStart)->modify("+{$duration} minutes")
            ];
            
            // If there's enough time, add a mid-gap slot
            if ($gapDuration >= $duration * 2) {
                $midGapStart = clone $dayStart;
                $midGapStart->modify('+' . (int)($gapDuration / 2 - $duration / 2) . ' minutes');
                $suggestions[] = [
                    'start' => $midGapStart,
                    'end' => (clone $midGapStart)->modify("+{$duration} minutes")
                ];
            }
        }
    }
    
    // Check for slots between existing schedules
    for ($i = 0; $i < count($occupiedSlots) - 1; $i++) {
        $currentEnd = $occupiedSlots[$i]['end'];
        $nextStart = $occupiedSlots[$i + 1]['start'];
        
        $gapDuration = ($nextStart->getTimestamp() - $currentEnd->getTimestamp()) / 60;
        
        // Only suggest if the gap is large enough
        if ($gapDuration >= $duration) {
            // Slot right after current schedule
            $suggestions[] = [
                'start' => clone $currentEnd,
                'end' => (clone $currentEnd)->modify("+{$duration} minutes")
            ];
            
            // If gap is large enough, also add a slot before the next schedule
            if ($gapDuration >= $duration * 2) {
                $endSlotStart = (clone $nextStart)->modify("-{$duration} minutes");
                $suggestions[] = [
                    'start' => clone $endSlotStart,
                    'end' => clone $nextStart
                ];
            }
        }
    }
    
    // Check for slot after last schedule
    if (!empty($occupiedSlots) && $occupiedSlots[count($occupiedSlots) - 1]['end'] < $dayEnd) {
        $lastEnd = $occupiedSlots[count($occupiedSlots) - 1]['end'];
        $gapDuration = ($dayEnd->getTimestamp() - $lastEnd->getTimestamp()) / 60;
        
        if ($gapDuration >= $duration) {
            // Slot right after last schedule
            $suggestions[] = [
                'start' => clone $lastEnd,
                'end' => (clone $lastEnd)->modify("+{$duration} minutes")
            ];
            
            // If there's enough time, add an end-of-day slot
            if ($gapDuration >= $duration * 2) {
                $endOfDayStart = (clone $dayEnd)->modify("-{$duration} minutes");
                $suggestions[] = [
                    'start' => clone $endOfDayStart,
                    'end' => clone $dayEnd
                ];
            }
        }
    }
    
    // Verify each suggestion against all existing schedules to ensure no conflicts
    $validSuggestions = [];
    foreach ($suggestions as $suggestion) {
        $hasConflict = false;
        foreach ($occupiedSlots as $occupiedSlot) {
            // Check if this suggestion overlaps with an occupied slot
            if (
                ($suggestion['start'] >= $occupiedSlot['start'] && $suggestion['start'] < $occupiedSlot['end']) ||
                ($suggestion['end'] > $occupiedSlot['start'] && $suggestion['end'] <= $occupiedSlot['end']) ||
                ($suggestion['start'] <= $occupiedSlot['start'] && $suggestion['end'] >= $occupiedSlot['end'])
            ) {
                $hasConflict = true;
                break;
            }
        }
        
        if (!$hasConflict) {
            $validSuggestions[] = $suggestion;
        }
    }
    
    return $validSuggestions;
}
?>