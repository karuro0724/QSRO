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
            margin-left: 60px;
        }

        .nav-links {
            display: flex;
            gap: 30px;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            transition: 0.3s ease;
        }

        .nav-links a:hover {
            text-decoration: underline;
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
        <div class="nav-links">
            <a href="admin_panel.php">Dashboard</a>
            <a href="window_page.php">Window</a>
            <a href="course_page.php">Course</a>
            <a href="staff_page.php">Staff</a>
            <a href="document_page.php">Document</a>
        </div>
    </nav>
</body>
</html>