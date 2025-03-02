<?php
session_start();
include 'config.php';

$alertMessage = '';

// Fetch active windows for the dropdown
$windows_query = "
    SELECT w.window_number, w.id 
    FROM windows w 
    INNER JOIN staff s ON w.id = s.window_number 
    WHERE w.status = 'Active' 
    ORDER BY w.window_number ASC
";
$windows_result = $conn->query($windows_query);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $window_number = $_POST['window_number'];
    $password = $_POST['password'];

    // Query to check staff credentials
    $stmt = $conn->prepare("
        SELECT s.*, w.window_number 
        FROM staff s
        JOIN windows w ON s.window_number = w.id
        WHERE s.window_number = ? AND s.password = ?
    ");
    
    $stmt->bind_param("ss", $window_number, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $staff = $result->fetch_assoc();
        $_SESSION['staff_id'] = $staff['id'];
        $_SESSION['staff_name'] = $staff['staff_name'];
        $_SESSION['window_number'] = $staff['window_number'];
        
        $alertMessage = "
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Successful',
                        text: 'Welcome " . htmlspecialchars($staff['staff_name']) . "!',
                        timer: 2000,
                        showConfirmButton: true
                    }).then(() => {
                        window.location.href = 'staff_dashboard.php';
                    });
                });
            </script>";
    } else {
        $alertMessage = "
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Credentials',
                        text: 'Please check your window number and password!',
                    });
                });
            </script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Login - PHCP Queue System</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: url('images/Perpetual-Background.jpg') no-repeat center center fixed;
            background-size: cover;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            opacity: 0.93;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            opacity: 0.93;
        }
        .card-header {
            background-color: #1D4DA1;
            color: white;
            text-align: center;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        .school-logo {
            max-width: 80px;
            margin-bottom: 10px;
        }
        .form-group label {
            font-weight: 500;
        }
        .btn-login {
            background-color: #1D4DA1;
            border-color: #1D4DA1;
        }
        .btn-login:hover {
            background-color: #153a7a;
            border-color: #153a7a;
        }
        .custom-select {
            height: calc(1.5em + .75rem + 2px);
            padding: .375rem 1.75rem .375rem .75rem;
            font-size: 1rem;
            font-weight: 400;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            border: 1px solid #ced4da;
            border-radius: .25rem;
        }
    </style>
</head>
<body>
    <div class="container login-container">
        <div class="card">
            <div class="card-header">
                <img src="images/Perpetual-LOGO.PNG" alt="School Logo" class="school-logo">
                <h4 class="mb-0">Staff Login</h4>
                <small>Perpetual Help College of Pangasinan</small>
            </div>
            <div class="card-body p-4">
                <form method="POST">
                    <div class="form-group">
                        <label for="window_number">
                            <i class="fas fa-window-maximize"></i> Window Number
                        </label>
                        <select class="form-control custom-select" id="window_number" name="window_number" required>
                            <option value="">Select Window</option>
                            <?php while ($window = $windows_result->fetch_assoc()): ?>
                                <option value="<?php echo $window['id']; ?>">
                                    Window <?php echo htmlspecialchars($window['window_number']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="password">
                            <i class="fas fa-lock"></i> Password
                        </label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-login btn-block">
                        <i class="fas fa-sign-in-alt"></i> Login
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php echo $alertMessage; ?>
</body>
</html>