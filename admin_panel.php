<?php
session_start();
include 'config.php';

// Check if admin is logged in
if (!isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit();
}

// Fetch summary counts
$window_count = $conn->query("SELECT COUNT(*) as count FROM windows")->fetch_assoc()['count'];
$course_count = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
$staff_count = $conn->query("SELECT COUNT(*) as count FROM staff")->fetch_assoc()['count'];
$document_count = $conn->query("SELECT COUNT(*) as count FROM documents")->fetch_assoc()['count'];

// Fetch recent staff activities (assuming you have a recent_activities table)
$recent_staff = $conn->query("SELECT * FROM staff ORDER BY id DESC LIMIT 5");

// Fetch active windows
$active_windows = $conn->query("SELECT * FROM windows WHERE status = 'Active' ORDER BY id DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - PHCP Queue System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .dashboard-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .stats-icon {
            font-size: 2.5rem;
            opacity: 0.8;
        }
        .card-link {
            text-decoration: none;
            color: inherit;
        }
        .card-link:hover {
            text-decoration: none;
            color: inherit;
        }
    </style>
</head>
<body>
    <?php include 'top-bar.php'; ?>

    <div class="container mt-4">
        <h2 class="text-center mb-4">Admin Dashboard</h2>
        
        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <a href="window_page.php" class="card-link">
                    <div class="card dashboard-card bg-primary text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total Windows</h6>
                                    <h2 class="mb-0"><?php echo $window_count; ?></h2>
                                </div>
                                <i class="fas fa-window-maximize stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3">
                <a href="course_page.php" class="card-link">
                    <div class="card dashboard-card bg-success text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total Courses</h6>
                                    <h2 class="mb-0"><?php echo $course_count; ?></h2>
                                </div>
                                <i class="fas fa-graduation-cap stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3">
                <a href="staff_page.php" class="card-link">
                    <div class="card dashboard-card bg-info text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total Staff</h6>
                                    <h2 class="mb-0"><?php echo $staff_count; ?></h2>
                                </div>
                                <i class="fas fa-users stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            
            <div class="col-md-3">
                <a href="document_page.php" class="card-link">
                    <div class="card dashboard-card bg-warning text-white">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title">Total Documents</h6>
                                    <h2 class="mb-0"><?php echo $document_count; ?></h2>
                                </div>
                                <i class="fas fa-file-alt stats-icon"></i>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Activities and Active Windows -->
        <div class="row">
            <!-- Recent Staff List -->
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Recent Staff</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php while($staff = $recent_staff->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($staff['staff_name']); ?></h6>
                                        <small>Window <?php echo htmlspecialchars($staff['window_number']); ?></small>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Windows -->
            <div class="col-md-6">
                <div class="card dashboard-card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-window-maximize"></i> Active Windows</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php while($window = $active_windows->fetch_assoc()): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">Window <?php echo htmlspecialchars($window['window_number']); ?></h6>
                                        <span class="badge badge-success">Active</span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.bundle.min.js"></script>
</body>
</html>