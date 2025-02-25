<tbody>
<?php
while ($row = $result->fetch_assoc()) {
    echo "<tr>
            <td>" . htmlspecialchars($row['ScheduleID']) . "</td>
            <td>" . htmlspecialchars($row['Events_name']) . "</td>
            <td>" . htmlspecialchars($row['clients_name']) . "</td>
            <td>" . htmlspecialchars($row['updated_status']) . "</td>
            <td>" . htmlspecialchars($row['start_date_time']) . "</td>
            <td>" . htmlspecialchars($row['end_date_time']) . "</td>
          </tr>";
    // If schedule was declined, display the decline message.
    if ($row['updated_status'] === 'declined' && !empty($row['decline_message'])) {
        echo "<tr class='table-danger'>
                <td colspan='6'>Decline Reason: " . htmlspecialchars($row['decline_message']) . "</td>
              </tr>";
    }
}
?>
</tbody>
