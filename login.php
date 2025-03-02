<?php
session_start();

$alertMessage = ''; // Store the alert script

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // In a real-world scenario, use prepared statements and password hashing
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin'] = true;
        $alertMessage = "
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    Swal.fire({
                        icon: 'success',
                        title: 'Login Successful',
                        text: 'Redirecting to admin panel...',
                        timer: 2000,
                        showConfirmButton: true
                    }).then(() => {
                        window.location.href = 'admin_panel.php';
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
                        text: 'Please check your username and password!',
                    });
                });
            </script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">Admin Login</h2>
        <div class="card p-4 shadow">
            <form method="POST">
                <div class="form-group">
                    <label for="username"><i class="fa fa-user"></i> Username</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="form-group">
                    <label for="password"><i class="fa fa-lock"></i> Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-block"><i class="fa fa-sign-in-alt"></i> Login</button>
            </form>
        </div>
    </div>

    <?php
    // This ensures the script runs *after* the page is fully loaded
    echo $alertMessage;
    ?>

</body>
</html>
