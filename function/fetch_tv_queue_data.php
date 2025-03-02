<?php
include '../config.php';

function fetchTVQueueData($conn) {
    try {
        // Get all windows and their current active queues
        $windows_query = "
            SELECT 
                s.window_number,
                s.status as staff_status,
                q.queue_number,
                q.display_status
            FROM staff s
            LEFT JOIN (
                SELECT * FROM queue 
                WHERE status = 'active' 
                AND display_status = 'active'
            ) q ON s.window_number = q.window_number
            ORDER BY s.window_number";
        
        $windows_result = $conn->query($windows_query);
        $windows = [];
        while ($row = $windows_result->fetch_assoc()) {
            $windows[] = [
                'window_number' => $row['window_number'],
                'staff_status' => $row['staff_status'],
                'queue_number' => $row['queue_number'],
                'display_status' => $row['display_status']
            ];
        }

        // Get waiting queues
        $waiting_query = "
            SELECT queue_number, window_number
            FROM queue 
            WHERE status = 'waiting'
            ORDER BY created_at ASC
            LIMIT 10"; // Show only the next 10 waiting queues
        
        $waiting_result = $conn->query($waiting_query);
        $waiting_queues = [];
        while ($row = $waiting_result->fetch_assoc()) {
            $waiting_queues[] = $row;
        }

        return [
            'status' => 'success',
            'windows' => $windows,
            'waiting_queues' => $waiting_queues
        ];
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => 'Failed to fetch TV queue data: ' . $e->getMessage()
        ];
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode(fetchTVQueueData($conn));
?>