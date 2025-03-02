<?php
include 'config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Display</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body {
            background: url('images/Perpetual-Background.jpg') no-repeat center center fixed;
            background-size: cover;
            color: white;
            font-family: 'Arial', sans-serif;
        }

        .header {
            background: #1D4DA1;
            padding: 10px 0;
            margin-bottom: 30px;
        }

        .window-display {
            background:rgb(255, 255, 255);
            padding: 40px;
            margin: 10px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }

        .window-display:hover {
            transform: translateY(-5px);
        }

        .window-header {
            color: #1D4DA1;
            font-size: 1.5em;
            margin-bottom: 15px;
            text-align: center;
        }

        .queue-number {
            font-size: 3.5em;
            font-weight: bold;
            color:rgb(66, 66, 66);
            text-shadow: 0 0 10px rgba(0, 0, 0, 0.3);
            text-align: center;
        }

        .course-name {
            color: #ffd700;
            font-size: 1.2em;
            margin-top: 10px;
        }

        .waiting-list {
            background: #2d2d2d;
            border-radius: 15px;
            padding: 20px;
            opacity: 0.9;
        }

        .waiting-item {
            background: #3d3d3d;
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            border-left: 5px solid #1D4DA1;
            transition: all 0.3s ease;
        }

        .waiting-item:hover {
            transform: translateX(10px);
            background: #4d4d4d;
        }

        .scroll-text {
            background: #1D4DA1;
            color: white;
            padding: 10px;
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 1.5em;
        }
        .queue-number.closed {
    color: #ff4444;
    font-size: 2.5em;
    font-weight: bold;
    text-shadow: 0 0 10px rgba(255,0,0,0.3);
}

.window-display {
    position: relative;
}

.window-display::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.1);
    border-radius: 15px;
    opacity: 0;
    transition: opacity 0.3s ease;
}

.window-display:has(.closed)::before {
    opacity: 1;
}
    </style>
</head>
<body>
    <?php include 'top-bar-tv.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Current Queue Display -->
            <div class="col-md-8">
                <div class="row" id="windowsContainer">
                    <!-- Debug placeholder -->
                    <div class="col-12 text-white">Loading windows...</div>
                </div>
            </div>

            <!-- Waiting List -->
            <div class="col-md-4">
                <div class="waiting-list">
                    <h3><i class="fas fa-users"></i> Waiting List</h3>
                    <div id="waitingQueueContainer">
                        <!-- Debug placeholder -->
                        <div class="text-white">Loading waiting list...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="scroll-text">
        <marquee>Welcome to Perpetual Help College of Pangasinan's Registrar Office. Please wait for your number to display. Thank you for your patience.</marquee>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Debug flag
        const DEBUG = true;

        // Function to fetch and update TV queue data
        function fetchTVQueueData() {
            if (DEBUG) console.log('Fetching TV queue data...');
            
            $.ajax({
                url: 'function/fetch_tv_queue_data.php',
                type: 'GET',
                success: function(response) {
                    if (DEBUG) console.log('Response received:', response);
                    
                    try {
                        let data = typeof response === 'string' ? JSON.parse(response) : response;
                        if (DEBUG) console.log('Parsed data:', data);
                        
                        if (data.status === 'success') {
                            if (DEBUG) console.log('Updating windows with:', data.windows);
                            updateWindows(data.windows);
                            
                            if (DEBUG) console.log('Updating waiting queue with:', data.waiting_queues);
                            updateWaitingQueue(data.waiting_queues);
                        } else {
                            console.error('Server returned error:', data.message);
                            showError('Failed to fetch queue data');
                        }
                    } catch (e) {
                        console.error('Error processing response:', e);
                        showError('Error processing server response');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Ajax request failed:', {
                        status: status,
                        error: error,
                        response: xhr.responseText
                    });
                    showError('Failed to connect to server');
                }
            });
        }

        function showError(message) {
            $('#windowsContainer').html(`
                <div class="col-12">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> ${message}
                    </div>
                </div>
            `);
        }

        // Function to update windows UI
        function updateWindows(windows) {
            if (!Array.isArray(windows)) {
                console.error('Windows data is not an array:', windows);
                return;
            }

            let container = $('#windowsContainer');
            let content = windows.map(window => `
                <div class="col-md-6">
                    <div class="window-display">
                        <div class="window-header">
                            <i class="fas fa-window-maximize"></i> Window ${window.window_number}
                        </div>
                        ${window.staff_status === 'Open' ? `
                            <div class="queue-number ${window.queue_number ? 'blink' : ''}">
                                ${window.queue_number ?? 'WAITING'}
                            </div>
                        ` : `
                            <div class="queue-number closed">
                                CLOSED
                            </div>
                        `}
                    </div>
                </div>
            `).join('');
            container.html(content);
        }

        // Function to update waiting queue UI
        function updateWaitingQueue(queues) {
            if (!Array.isArray(queues)) {
                console.error('Waiting queues data is not an array:', queues);
                return;
            }

            let container = $('#waitingQueueContainer');
            if (queues.length === 0) {
                container.html('<div class="waiting-item">No waiting queues</div>');
                return;
            }

            let content = queues.map(queue => `
                <div class="waiting-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>${queue.queue_number}</strong>
                            <div class="text-muted">Window ${queue.window_number}</div>
                        </div>
                    </div>
                </div>
            `).join('');
            container.html(content);
        }

        // Wait for document to be ready
        $(document).ready(function() {
            console.log('Document ready, starting queue display...');
            // Initial fetch
            fetchTVQueueData();

            // Polling every second
            setInterval(fetchTVQueueData, 1000);
        });
    </script>
</body>
</html>