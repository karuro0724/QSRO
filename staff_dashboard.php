<?php
session_start();
include 'config.php';

if (!isset($_SESSION['staff_id'])) {
    header('Location: staff_login.php');
    exit();
}

// ===== QUEUE FUNCTIONS =====
function fetchQueueData($conn, $window_number) {
    try {
        // Current queue
        $stmt = $conn->prepare("SELECT * FROM queue WHERE window_number = ? AND status = 'active' ORDER BY created_at ASC LIMIT 1");
        $stmt->bind_param("i", $window_number);
        $stmt->execute();
        $current_queue = $stmt->get_result()->fetch_assoc();

        // Waiting queues
        $stmt = $conn->prepare("SELECT * FROM queue WHERE window_number = ? AND status = 'waiting' ORDER BY created_at ASC");
        $stmt->bind_param("i", $window_number);
        $stmt->execute();
        $waiting_queues = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        return ['status' => 'success', 'current_queue' => $current_queue, 'waiting_queues' => $waiting_queues];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Failed to fetch queue data: ' . $e->getMessage()];
    }
}

function processNextQueue($conn, $window_number) {
    try {
        $conn->begin_transaction();

        // Complete current active queue
        $stmt = $conn->prepare("UPDATE queue SET status = 'completed' WHERE window_number = ? AND status = 'active'");
        $stmt->bind_param("i", $window_number);
        $stmt->execute();

        // Get next waiting queue
        $stmt = $conn->prepare("SELECT id FROM queue WHERE window_number = ? AND status = 'waiting' ORDER BY created_at ASC LIMIT 1");
        $stmt->bind_param("i", $window_number);
        $stmt->execute();
        $next_queue = $stmt->get_result()->fetch_assoc();

        if ($next_queue) {
            // Activate next queue
            $stmt = $conn->prepare("UPDATE queue SET status = 'active', display_status = 'inactive' WHERE id = ?");
            $stmt->bind_param("i", $next_queue['id']);
            $stmt->execute();
        }

        $conn->commit();
        return ['status' => 'success', 'message' => $next_queue ? 'Next queue activated' : 'No waiting queues'];
    } catch (Exception $e) {
        $conn->rollback();
        return ['status' => 'error', 'message' => 'Failed to process next queue: ' . $e->getMessage()];
    }
}

function notifyQueue($conn, $window_number, $queue_number) {
    try {
        // Verify queue exists and is active
        $stmt = $conn->prepare("SELECT id FROM queue WHERE window_number = ? AND queue_number = ? AND status = 'active'");
        $stmt->bind_param("is", $window_number, $queue_number);
        $stmt->execute();
        $queue = $stmt->get_result()->fetch_assoc();

        if (!$queue) {
            return ['status' => 'error', 'message' => 'Queue not found or not active'];
        }

        // Update display status
        $stmt = $conn->prepare("UPDATE queue SET display_status = 'active' WHERE id = ?");
        $stmt->bind_param("i", $queue['id']);
        $stmt->execute();

        return ['status' => 'success', 'message' => 'Queue notification sent'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Failed to send notification: ' . $e->getMessage()];
    }
}

// ===== DOCUMENT FUNCTIONS =====
function fetchDocumentRequests($conn, $staff_id) {
    try {
        $stmt = $conn->prepare("SELECT * FROM form_responses WHERE staff_id = ? ORDER BY submission_time DESC");
        $stmt->bind_param("i", $staff_id);
        $stmt->execute();
        return ['status' => 'success', 'requests' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Failed to fetch document requests: ' . $e->getMessage()];
    }
}

function updateDocumentStatus($conn, $request_id, $status, $staff_id) {
    try {
        // Verify ownership
        $stmt = $conn->prepare("SELECT id FROM form_responses WHERE id = ? AND staff_id = ?");
        $stmt->bind_param("ii", $request_id, $staff_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows == 0) {
            return ['status' => 'error', 'message' => 'You are not authorized to update this request'];
        }

        // Update status
        $stmt = $conn->prepare("UPDATE form_responses SET status = ? WHERE id = ? AND staff_id = ?");
        $stmt->bind_param("sii", $status, $request_id, $staff_id);
        
        return $stmt->execute() 
            ? ['status' => 'success', 'message' => 'Document status updated successfully']
            : ['status' => 'error', 'message' => 'Database update failed'];
    } catch (Exception $e) {
        return ['status' => 'error', 'message' => 'Failed to update document status: ' . $e->getMessage()];
    }
}

// ===== HANDLE AJAX REQUESTS =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'fetch_queue_data':
            echo json_encode(fetchQueueData($conn, $_POST['window_number']));
            exit();
            
        case 'next_queue':
            echo json_encode(processNextQueue($conn, $_POST['window_number']));
            exit();
            
        case 'notify_queue':
            echo json_encode(notifyQueue($conn, $_POST['window_number'], $_POST['queue_number']));
            exit();
            
        case 'toggle_status':
            $staff_id = $_SESSION['staff_id'];
            
            // Get current status
            $stmt = $conn->prepare("SELECT status FROM staff WHERE id = ?");
            $stmt->bind_param("i", $staff_id);
            $stmt->execute();
            $current_status = $stmt->get_result()->fetch_assoc()['status'];
            
            // Toggle status
            $new_status = ($current_status === 'Open') ? 'Closed' : 'Open';
            $stmt = $conn->prepare("UPDATE staff SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $staff_id);
            
            echo json_encode($stmt->execute() 
                ? ['status' => 'success', 'new_status' => $new_status]
                : ['status' => 'error', 'message' => 'Failed to update status']
            );
            exit();
            
        case 'fetch_document_requests':
            echo json_encode(fetchDocumentRequests($conn, $_SESSION['staff_id']));
            exit();
            
        case 'update_document_status':
            echo json_encode(updateDocumentStatus(
                $conn, 
                $_POST['request_id'], 
                $_POST['status'], 
                $_SESSION['staff_id']
            ));
            exit();
    }
}

// Get current staff status
$stmt = $conn->prepare("SELECT status FROM staff WHERE id = ?");
$stmt->bind_param("i", $_SESSION['staff_id']);
$stmt->execute();
$current_status = $stmt->get_result()->fetch_assoc()['status'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .queue-card { border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .queue-number { font-size: 2.5em; font-weight: bold; color: #1D4DA1; }
        .waiting-queue { transition: all 0.3s ease; }
        .waiting-queue:hover { transform: translateX(5px); }
        .nav-tabs .nav-link.active { background-color: #f8f9fa; border-bottom-color: #f8f9fa; }
        .status-processing { color: #17a2b8; }
        .status-claimable { color: #ffc107; }
        .status-completed { color: #28a745; }
        .status-rejected { color: #dc3545; }
        .refresh-btn { transition: transform 0.3s ease; }
        .refresh-btn:hover { transform: rotate(180deg); }
        .refresh-spin { animation: spin 1s linear infinite; }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <?php include 'top-bar-staff.php' ?>

    <div class="container-fluid mt-4">
        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="queue-tab" data-toggle="tab" href="#queue" role="tab">
                    <i class="fas fa-users"></i> Queue Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="documents-tab" data-toggle="tab" href="#documents" role="tab">
                    <i class="fas fa-file-alt"></i> Document Requests
                </a>
            </li>
        </ul>
        
        <div class="tab-content mt-3" id="myTabContent">
            <!-- Queue Management Tab -->
            <div class="tab-pane fade show active" id="queue" role="tabpanel">
                <div class="row">
                    <!-- Current Queue -->
                    <div class="col-md-6">
                        <div class="card queue-card h-100">
                            <div class="card-header bg-primary text-white">
                                <h4><i class="fas fa-user-clock"></i> Current Queue</h4>
                            </div>
                            <div class="card-body" id="currentQueueContainer">
                                <!-- Current queue content loaded via AJAX -->
                            </div>
                        </div>
                    </div>

                    <!-- Waiting Queue -->
                    <div class="col-md-6">
                        <div class="card queue-card h-100">
                            <div class="card-header bg-info text-white">
                                <h4><i class="fas fa-users"></i> Waiting Queue</h4>
                            </div>
                            <div class="card-body" id="waitingQueueContainer">
                                <!-- Waiting queue content loaded via AJAX -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Document Requests Tab -->
            <div class="tab-pane fade" id="documents" role="tabpanel">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4><i class="fas fa-file-alt"></i> Document Requests</h4>
                        <button id="refreshDocumentRequests" class="btn btn-light btn-sm" title="Refresh Document Requests">
                            <i class="fas fa-sync-alt refresh-btn"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="documentRequestsTable">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Student ID</th>
                                        <th>Reference No.</th>
                                        <th>Email</th>
                                        <th>Course</th>
                                        <th>Document</th>
                                        <th>Status</th>
                                        <th>Submission Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="documentRequestsBody">
                                    <!-- Document requests loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        $(document).ready(function() {
            const windowNumber = <?php echo $_SESSION['window_number']; ?>;
            const staffId = <?php echo $_SESSION['staff_id']; ?>;
            
            // ===== QUEUE MANAGEMENT =====
            function fetchQueueData() {
                $.ajax({
                    url: 'staff_dashboard.php',
                    type: 'POST',
                    data: { action: 'fetch_queue_data', window_number: windowNumber },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            updateCurrentQueue(data.current_queue);
                            updateWaitingQueue(data.waiting_queues);
                        }
                    }
                });
            }
            
            function updateCurrentQueue(queue) {
                const container = $('#currentQueueContainer');
                if (queue) {
                    const isNotified = queue.display_status === 'active';
                    container.html(`
                        <div class="text-center mb-3">
                            <div class="queue-number">${queue.queue_number}</div>
                            <small class="text-muted">Status: ${isNotified ? 'Notified' : 'Waiting for notification'}</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-cog"></i> Service:</strong> ${queue.service_type}</p>
                                <p><strong><i class="fas fa-user"></i> Name:</strong> ${queue.name}</p>
                                <p><strong><i class="fas fa-phone"></i> Contact:</strong> ${queue.contact}</p>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <button class="btn btn-warning btn-lg mr-2" id="notifyBtn" ${isNotified ? 'disabled' : ''}>
                                <i class="fas fa-bell"></i> Notify
                            </button>
                            <button class="btn btn-success btn-lg" id="nextBtn" ${!isNotified ? 'disabled' : ''}>
                                <i class="fas fa-forward"></i> Next
                            </button>
                        </div>
                    `);
                } else {
                    const hasWaiting = $('#waitingQueueContainer .list-group-item').length > 0;
                    container.html(`
                        <div class="text-center py-4">
                            <i class="fas fa-user-clock fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No current queue</h5>
                            ${hasWaiting ? `
                                <button class="btn btn-success btn-lg mt-3" id="nextBtn">
                                    <i class="fas fa-forward"></i> Call Next
                                </button>
                            ` : ''}
                        </div>
                    `);
                }
            }

            function updateWaitingQueue(queues) {
                const container = $('#waitingQueueContainer');
                if (queues && queues.length > 0) {
                    const list = queues.map(queue => `
                        <div class="list-group-item list-group-item-action waiting-queue">
                            <div class="d-flex w-100 justify-content-between">
                                <h5 class="mb-1">${queue.queue_number}</h5>
                                <small>${new Date(queue.created_at).toLocaleTimeString()}</small>
                            </div>
                            <p class="mb-1">${queue.name}</p>
                            <small class="text-muted"><i class="fas fa-cog"></i> ${queue.service_type}</small>
                        </div>
                    `).join('');
                    container.html(`<div class="list-group">${list}</div>`);
                } else {
                    container.html(`
                        <div class="text-center py-4">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No waiting queue</h5>
                        </div>
                    `);
                }
            }
            
            // ===== DOCUMENT MANAGEMENT =====
            function fetchDocumentRequests() {
                return new Promise((resolve, reject) => {
                    $.ajax({
                        url: 'staff_dashboard.php',
                        type: 'POST',
                        data: { action: 'fetch_document_requests' },
                        success: function(response) {
                            const data = JSON.parse(response);
                            if (data.status === 'success') {
                                updateDocumentRequestsTable(data.requests);
                                resolve(data);
                            } else {
                                reject(data.message || 'Unknown error');
                            }
                        },
                        error: function(xhr, status, error) {
                            reject(error);
                        }
                    });
                });
            }
            
            function updateDocumentRequestsTable(requests) {
                const tbody = $('#documentRequestsBody');
                if (requests && requests.length > 0) {
                    const rows = requests.map(request => {
                        // Get status info
                        let statusClass = '';
                        switch(request.status) {
                            case 'processing': statusClass = 'status-processing'; break;
                            case 'claimable': statusClass = 'status-claimable'; break;
                            case 'completed': statusClass = 'status-completed'; break;
                            case 'rejected': statusClass = 'status-rejected'; break;
                            default: statusClass = 'status-processing';
                        }
                        
                        const displayStatus = request.status ? 
                            request.status.charAt(0).toUpperCase() + request.status.slice(1) : 'Processing';
                        
                        const disableNotify = ['claimable', 'completed', 'rejected'].includes(request.status);
                        const disableReject = request.status === 'rejected';
                        
                        return `
                            <tr>
                                <td>${request.id}</td>
                                <td>${request.student_id}</td>
                                <td>${request.reference_number || 'N/A'}</td>
                                <td>${request.email}</td>
                                <td>${request.course}</td>
                                <td>${request.documents}</td>
                                <td><span class="font-weight-bold ${statusClass}">${displayStatus}</span></td>
                                <td>${new Date(request.submission_time).toLocaleString()}</td>
                                <td>
                                    <button class="btn btn-sm btn-success notify-btn" data-id="${request.id}" 
                                        data-email="${request.email}" ${disableNotify ? 'disabled' : ''}>
                                        <i class="fas fa-envelope"></i> Notify
                                    </button>
                                    <button class="btn btn-sm btn-danger reject-btn" data-id="${request.id}" 
                                        data-email="${request.email}" ${disableReject ? 'disabled' : ''}>
                                        <i class="fas fa-times-circle"></i> Reject
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                    tbody.html(rows);
                } else {
                    tbody.html(`
                        <tr>
                            <td colspan="9" class="text-center py-3">
                                <i class="fas fa-file-alt fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No document requests found</p>
                            </td>
                        </tr>
                    `);
                }
            }
            
            function updateDocumentStatus(requestId, status) {
                $.ajax({
                    url: 'staff_dashboard.php',
                    type: 'POST',
                    data: {
                        action: 'update_document_status',
                        request_id: requestId,
                        status: status
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Status Updated',
                                text: `Document request has been ${status === 'rejected' ? 'rejected' : 'marked as claimable'}`,
                                timer: 1500
                            });
                            fetchDocumentRequests();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Update Failed',
                                text: data.message
                            });
                        }
                    }
                });
            }

            // ===== EVENT HANDLERS =====
            // Next queue button
            $(document).on('click', '#nextBtn', function() {
                if ($(this).prop('disabled')) return;
                
                $.ajax({
                    url: 'staff_dashboard.php',
                    type: 'POST',
                    data: { action: 'next_queue', window_number: windowNumber },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Next Queue Called',
                                text: data.message,
                                timer: 1500
                            });
                            fetchQueueData();
                        }
                    }
                });
            });

            // Notify queue button
            $(document).on('click', '#notifyBtn', function() {
                if ($(this).prop('disabled')) return;
                
                $.ajax({
                    url: 'staff_dashboard.php',
                    type: 'POST',
                    data: {
                        action: 'notify_queue',
                        window_number: windowNumber,
                        queue_number: $('#currentQueueContainer .queue-number').text()
                    },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            $('#notifyBtn').prop('disabled', true);
                            $('#nextBtn').prop('disabled', false);
                            Swal.fire({
                                icon: 'success',
                                title: 'Notification Sent',
                                timer: 1500
                            });
                        }
                    }
                });
            });
            
            // Refresh document requests
            $('#refreshDocumentRequests').click(function() {
                const $button = $(this);
                const $icon = $button.find('i');
                
                $icon.addClass('refresh-spin');
                $button.prop('disabled', true);
                
                fetchDocumentRequests()
                    .then(() => {
                        Swal.fire({
                            icon: 'success',
                            title: 'Refreshed',
                            text: 'Document requests have been refreshed',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 1500
                        });
                    })
                    .catch((error) => {
                        Swal.fire({
                            icon: 'error',
                            title: 'Refresh Failed',
                            text: error,
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    })
                    .finally(() => {
                        setTimeout(() => {
                            $icon.removeClass('refresh-spin');
                            $button.prop('disabled', false);
                        }, 500);
                    });
            });
            
            // Notify document button
            $(document).on('click', '.notify-btn', function() {
                const requestId = $(this).data('id');
                const email = $(this).data('email');
                
                Swal.fire({
                    title: 'Notify Student',
                    text: 'This will send an email notification to the student that their document is ready to claim.',
                    icon: 'info',
                    showCancelButton: true,
                    confirmButtonText: 'Send Notification',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'notify_document.php',
                            type: 'POST',
                            data: {
                                request_id: requestId,
                                email: email
                            },
                            success: function(response) {
                                const data = JSON.parse(response);
                                if (data.status === 'success') {
                                    updateDocumentStatus(requestId, 'claimable');
                                    
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Notification Sent',
                                        text: 'The student has been notified that their document is ready to claim.',
                                        timer: 2000
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Failed to Send Notification',
                                        text: data.message
                                    });
                                }
                            }
                        });
                    }
                });
            });

            // Reject document button
            $(document).on('click', '.reject-btn', function() {
                const requestId = $(this).data('id');
                const email = $(this).data('email');
                
                Swal.fire({
                    title: 'Reject Document Request',
                    html: `
                        <div class="text-left">
                            <p>Please provide a reason for rejection:</p>
                            <textarea id="rejectionReason" class="form-control" rows="3" placeholder="Enter rejection reason"></textarea>
                        </div>
                    `,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Reject Request',
                    cancelButtonText: 'Cancel',
                    preConfirm: () => {
                        const reason = document.getElementById('rejectionReason').value;
                        if (!reason.trim()) {
                            Swal.showValidationMessage('Please enter a rejection reason');
                        }
                        return reason;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const reason = result.value;
                        
                        $.ajax({
                            url: 'reject_document.php',
                            type: 'POST',
                            data: {
                                request_id: requestId,
                                email: email,
                                reason: reason
                            },
                            success: function(response) {
                                const data = JSON.parse(response);
                                if (data.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Request Rejected',
                                        text: 'The student has been notified that their document request was rejected.',
                                        timer: 2000
                                    });
                                    fetchDocumentRequests();
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Rejection Failed',
                                        text: data.message || 'An error occurred while rejecting the request.'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Rejection Failed',
                                    text: 'An error occurred while rejecting the request.'
                                });
                            }
                        });
                    }
                });
            });

            // Toggle window status
            $('#toggleStatus').click(function() {
                $.ajax({
                    url: 'staff_dashboard.php',
                    type: 'POST',
                    data: { action: 'toggle_status' },
                    success: function(response) {
                        const data = JSON.parse(response);
                        if (data.status === 'success') {
                            const $btn = $('#toggleStatus');
                            if (data.new_status === 'Open') {
                                $btn.removeClass('btn-danger').addClass('btn-success');
                                $btn.html('<i class="fas fa-door-open"></i> Window Open');
                            } else {
                                $btn.removeClass('btn-success').addClass('btn-danger');
                                $btn.html('<i class="fas fa-door-closed"></i> Window Closed');
                            }
                            
                            Swal.fire({
                                icon: 'success',
                                title: 'Status Updated',
                                text: `Window is now ${data.new_status}`,
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    }
                });
            });
            
            // Tab change handler
            $('a[data-toggle="tab"]').on('shown.bs.tab', function (e) {
                if ($(e.target).attr('id') === 'documents-tab') {
                    fetchDocumentRequests();
                }
            });

            // Initial data load
            fetchQueueData();
            setInterval(fetchQueueData, 1000);
            if ($('#documents-tab').hasClass('active')) {
                fetchDocumentRequests();
            }
        });
    </script>
</body>
</html>