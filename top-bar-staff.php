<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perpetual Help College of Pangasinan</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            list-style: none;
            text-decoration: none;
            font-family: 'Franklin Gothic', sans-serif;
        }

        .navbar {
            width: 100%;
            height: 80px;
            background-color: #1D4DA1;
            display: flex;
            align-items: center;
            padding: 0 50px;
            justify-content: space-between;
        }

        .logo {
            color: white;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .bold {
            font-weight: bold;
            display: block;
        }

        .logo img {
            width: 57px;
            height: 80px;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
        }

        .pangasinan {
            margin-left: 60px; /* Adds some indent to center with the text above */
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="images/Perpetual-LOGO.PNG" alt="Logo">
            <div class="logo-text">
                <span class="bold">PERPETUAL HELP COLLEGE OF</span>
                <span class="pangasinan">PANGASINAN</span>
            </div>
        </div>
        <span class="navbar-brand mb-0 h1">
            <i class="fas fa-window-maximize"></i> Window <?php echo $_SESSION['window_number']; ?>
        </span>
        <div>
            <button id="toggleStatus" class="btn <?php echo $current_status === 'Open' ? 'btn-success' : 'btn-danger'; ?> mr-2">
                <i class="fas <?php echo $current_status === 'Open' ? 'fa-door-open' : 'fa-door-closed'; ?>"></i>
                Window <?php echo $current_status; ?>
            </button>
            <a href="staff_logout.php" class="btn btn-outline-light">
            <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </nav>
</body>
</html>