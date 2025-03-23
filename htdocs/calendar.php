<?php
session_start();
include 'db.php';
include 'navigation.php';

// Updated SQL query to include clients_name
$schedules = [];
if ($stmt = $conn->prepare("SELECT s.date_schedule, s.start_date_time, s.end_date_time, 
                           e.Events_name, c.clients_name 
                           FROM Schedule s 
                           LEFT JOIN Events e ON s.EventID = e.EventID
                           LEFT JOIN Clients c ON e.ClientID = c.ClientID")) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $schedules[] = $row;
    }
    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Calendar</title>
    <?php

    ?>
    <!-- Bootstrap CSS and Font Awesome -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
        /* CSS Styles */
        body {
            margin: 0;
            background-color: #f8f9fa;
        }
        .content-container {
            padding: 20px;
            margin: 0 auto;
            width: 100%;
            max-width: 1000px;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .calendar-container {
            width: 100%;
            max-width: 700px;
            height: auto;
            background-color: #ffffff;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            padding: 25px;
            display: flex;
            flex-direction: column;
            margin-top: -56px; /* Offset for navbar height */
        }
        .calendar-header {
            text-align: center;
            margin-bottom: 1rem;
        }
        .calendar-header h4 {
            margin: 0;
            font-weight: bold;
            color: #007bff;
        }
        .calendar-nav {
            background: none;
            border: none;
            font-size: 1.5rem;
            color: #007bff;
            cursor: pointer;
        }
        .calendar-nav:hover {
            color: #0056b3;
        }
        .calendar-table {
            width: 100%;
            text-align: center;
        }
        .calendar-table th {
            height: 40px; /* Reduced from 60px */
            width: 40px; /* Reduced from 60px */
            font-size: 0.9rem; /* Slightly smaller font */
            text-align: center;
            color: #6c757d;
            font-weight: bold;
            padding: 8px;
        }
        .calendar-table td {
            height: 45px; /* Reduced from 60px */
            width: 45px; /* Reduced from 60px */
            vertical-align: middle;
            padding: 4px;
            font-size: 0.9rem;
            text-align: center;
            border: 1px solid #dee2e6;
        }
        .calendar-table td.today {
            background-color: rgb(133, 176, 223);
            color: #fff;
            border-radius: 0;
            border: 2px solid rgb(191, 205, 221);
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.2);
        }
        /* Add styles for scheduled dates */
        .calendar-table td.scheduled {
            background-color: rgba(220, 53, 69, 0.1); /* Light red background */
            color: #dc3545; /* Red text */
            font-weight: bold;
            cursor: pointer;
        }
        .calendar-table td.scheduled:hover {
            background-color: rgba(220, 53, 69, 0.2); /* Darker red on hover */
        }

        /* Added style for schedule sign */
        .schedule-sign {
            background-color: #ffc107;
            color: #000;
            font-size: 0.65rem; /* Slightly reduced font size */
            font-weight: bold;
            border-radius: 3px;
            padding: 1px;
            margin-top: 1px;
        }

        .schedule-container {
            display: flex;
            flex-direction: column;
            gap: 2px;
            margin-top: 2px;
        }

        .schedule-sign {
            background-color: #dc3545;
            color: white;
            font-size: 0.65rem;
            padding: 2px 4px;
            border-radius: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 100%;
            margin-bottom: 1px;
        }

        .calendar-table td.scheduled {
            background-color: #ffebee;
            position: relative;
            padding: 2px;
            min-height: 45px;
        }

        .calendar-table td.scheduled:hover {
            background-color: #ffcdd2;
        }

        /* Add these new styles for the modal */
        .schedule-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .schedule-modal-content {
            position: relative;
            background: white;
            width: 90%;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }

        .close-modal {
            position: absolute;
            right: 20px;
            top: 15px;
            font-size: 24px;
            cursor: pointer;
            color: #dc3545;
        }

        .calendar-table td.available {
            background-color: rgba(40, 167, 69, 0.1); /* Light green background */
            color: #28a745; /* Green text */
        }

        .calendar-table td.available:hover {
            background-color: rgba(40, 167, 69, 0.2); /* Darker green on hover */
        }
    </style>
</head>
<body>
    <!-- Calendar Container -->
    <div class="content-container">
        <div class="calendar-container">
            <!-- Calendar Header -->
            <div class="calendar-header text-center">
                <div class="d-flex justify-content-center align-items-center mb-2">
                    <i class="fas fa-calendar-alt me-2" style="font-size: 1.5rem; color: #007bff;"></i>
                    <span style="font-size: 1.2rem; font-weight: bold; color: #007bff;">Event Calendar</span>
                </div>
                <h4 id="currentMonth" class="mb-0" style="font-weight: bold; color: #007bff;"></h4>
            </div>
            <!-- Navigation Buttons -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <!-- Previous Month Button -->
                <button class="calendar-nav" id="prevMonth">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <!-- Next Month Button -->
                <button class="calendar-nav" id="nextMonth">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <!-- Calendar Table -->
            <table class="table table-bordered calendar-table text-center">
                <thead class="table-primary">
                    <tr>
                        <th>Sun</th>
                        <th>Mon</th>
                        <th>Tue</th>
                        <th>Wed</th>
                        <th>Thu</th>
                        <th>Fri</th>
                        <th>Sat</th>
                    </tr>
                </thead>
                <tbody id="calendarBody">
                    <!-- Calendar days will be dynamically generated here -->
                </tbody>
            </table>
            <!-- Create New Schedule Button -->
            <div class="text-center mt-3">
                <a href="create_schedule.php" class="btn btn-primary w-100">
                    <i class="fas fa-plus"></i> Create New Schedule
                </a>
            </div>
        </div>
    </div>

    <!-- Add this modal HTML after the existing calendar container -->
    <div id="scheduleModal" class="schedule-modal">
        <div class="schedule-modal-content">
            <span class="close-modal">&times;</span>
            <h3 id="modalDate" class="mb-4"></h3>
            <div id="modalContent"></div>
        </div>
    </div>

    <!-- Include Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // JavaScript for Calendar
        const calendarBody = document.getElementById('calendarBody');
        const currentMonth = document.getElementById('currentMonth');
        const prevMonth = document.getElementById('prevMonth');
        const nextMonth = document.getElementById('nextMonth');

        let date = new Date();
        let schedules = <?php echo json_encode($schedules); ?>.map(schedule => ({
            ...schedule,
            start: new Date(schedule.start_date_time),
            end: new Date(schedule.end_date_time)
        }));

        // Update the renderCalendar function
        function renderCalendar() {
            const year = date.getFullYear();
            const month = date.getMonth();
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            currentMonth.textContent = date.toLocaleString('default', { month: 'long', year: 'numeric' });

            calendarBody.innerHTML = '';
            let row = document.createElement('tr');

            // Add empty cells for days before the first day of the month
            for (let i = 0; i < firstDay; i++) {
                row.appendChild(document.createElement('td'));
            }

            // Replace the existing for loop in renderCalendar function
            for (let day = 1; day <= daysInMonth; day++) {
                const cell = document.createElement('td');
                const currentDate = new Date(year, month, day);
                cell.textContent = day;

                // Check if date is past
                const today = new Date();
                today.setHours(0, 0, 0, 0);
                const isDatePast = currentDate < today;

                // Check for schedules on this day
                const daySchedules = schedules.filter(schedule => {
                    const scheduleStart = new Date(schedule.start_date_time);
                    const scheduleEnd = new Date(schedule.end_date_time);
                    const currentDateStart = new Date(year, month, day, 0, 0, 0);
                    const currentDateEnd = new Date(year, month, day, 23, 59, 59);
                    return (scheduleStart <= currentDateEnd && scheduleEnd >= currentDateStart);
                });

                if (isDatePast) {
                    // Past dates - no special styling
                } else if (daySchedules.length > 0) {
                    // Scheduled dates - red styling
                    cell.classList.add('scheduled');
                    
                    const alertContainer = document.createElement('div');
                    alertContainer.className = 'schedule-container';
                    
                    const alert = document.createElement('div');
                    alert.className = 'schedule-sign';
                    alert.textContent = `${daySchedules.length} Schedule${daySchedules.length > 1 ? 's' : ''}`;
                    alertContainer.appendChild(alert);
                    cell.appendChild(alertContainer);
                    
                    cell.addEventListener('click', () => {
                        showScheduleDetails(currentDate, daySchedules);
                    });
                } else {
                    // Available dates - green styling
                    cell.classList.add('available');
                    
                    const availableContainer = document.createElement('div');
                    availableContainer.className = 'schedule-container';
                    
                    const availableText = document.createElement('div');
                    availableText.className = 'schedule-sign available-sign';
                    availableText.style.backgroundColor = '#28a745';
                    availableText.textContent = 'Available';
                    availableContainer.appendChild(availableText);
                    cell.appendChild(availableContainer);
                }

                row.appendChild(cell);

                if ((firstDay + day) % 7 === 0) {
                    calendarBody.appendChild(row);
                    row = document.createElement('tr');
                }
            }

            // Append any remaining row cells
            if (row.children.length > 0) {
                calendarBody.appendChild(row);
            }
        }

        prevMonth.addEventListener('click', () => {
            date.setMonth(date.getMonth() - 1);
            renderCalendar();
        });

        nextMonth.addEventListener('click', () => {
            date.setMonth(date.getMonth() + 1);
            renderCalendar();
        });

        // Add these new functions for modal handling
        function showScheduleDetails(date, schedules) {
            const modal = document.getElementById('scheduleModal');
            const modalDate = document.getElementById('modalDate');
            const modalContent = document.getElementById('modalContent');
            
            // Format the date for display
            const formattedDate = new Date(date).toLocaleDateString('en-US', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
            
            modalDate.textContent = formattedDate;
            
            // Updated table structure to include Client
            let content = '<div class="table-responsive"><table class="table">';
            content += '<thead><tr><th>Client</th><th>Event</th><th>Start</th><th>End</th></tr></thead><tbody>';
            
            schedules.forEach(schedule => {
                const startDateTime = new Date(schedule.start_date_time).toLocaleString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    hour12: true
                });
                const endDateTime = new Date(schedule.end_date_time).toLocaleString('en-US', {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: 'numeric',
                    minute: 'numeric',
                    hour12: true
                });
                content += `
                    <tr>
                        <td>${schedule.clients_name || 'N/A'}</td>
                        <td>${schedule.Events_name}</td>
                        <td>${startDateTime}</td>
                        <td>${endDateTime}</td>
                    </tr>
                `;
            });
            
            content += '</tbody></table></div>';
            modalContent.innerHTML = content;
            modal.style.display = 'block';
        }

        // Add modal close handler
        document.querySelector('.close-modal').addEventListener('click', () => {
            document.getElementById('scheduleModal').style.display = 'none';
        });

        // Close modal when clicking outside
        window.addEventListener('click', (e) => {
            const modal = document.getElementById('scheduleModal');
            if (e.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Initial render
        document.addEventListener('DOMContentLoaded', function() {
            renderCalendar();
        });
    </script>
</body>
</html>