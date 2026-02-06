<?php
session_start();
require_once '../config/dbconnect.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    
    // Role: default to 'user', can be 'admin' via hidden input
    $role = $_POST['role'] ?? 'client';

    // Validation
    if (empty($full_name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Full Name, Email, and Password are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $error = "Email is already registered.";
        } else {
            // Hash the password
            $hashed = password_hash($password, PASSWORD_DEFAULT);

            // Insert user with role
            $insert = $conn->prepare("INSERT INTO users (fullname, email, password, phone, address, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            $insert->bind_param("ssssss", $full_name, $email, $hashed, $phone, $address, $role);

            if ($insert->execute()) {
                $_SESSION['user_id'] = $insert->insert_id;
                $_SESSION['user_name'] = $full_name;

                // Redirect: admins to admin dashboard, users to client dashboard
                if ($role === 'admin') {
                    header("Location: ../admin/dashboard.php");
                } else {
                    header("Location: ../client/dashboard.php");
                }
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
            $insert->close();
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Register - Triple M's Car Rental</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<!-- Tailwind CSS -->
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="bg-gray-100">

<!-- Header -->
<header class="fixed w-full top-0 left-0 z-50 bg-white/50 backdrop-blur-md shadow-md">
    <div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center">
        <a href="../index.php" class="text-xl font-bold text-blue-900 flex items-center gap-2">
            <svg class="w-6 h-6 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Triple M's Car Rental
        </a>
        <nav>
            <a href="../index.php" class="text-gray-700 hover:text-blue-500 px-3">Home</a>
            <a href="login.php" class="text-gray-700 hover:text-blue-500 px-3">Login</a>
        </nav>
    </div>
</header>

<!-- Main Content -->
<main class="min-h-screen flex items-center justify-center pt-20 pb-20 relative">
    <!-- Background Blur -->
    <div class="absolute inset-0 opacity-10">
        <div class="absolute top-20 left-10 w-72 h-72 bg-blue-400 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-10 w-96 h-96 bg-blue-300/50 rounded-full blur-3xl"></div>
    </div>

    <!-- Register Card -->
    <div class="relative bg-white rounded-xl shadow-xl p-8 z-10 w-full max-w-md animate-scale-in">
        <h1 class="text-2xl font-bold text-gray-900 text-center">Create Your Account</h1>
        <p class="text-gray-500 text-center mt-2">Join Triple M's Car Rental and start your journey</p>

        <?php if ($error): ?>
            <p class="text-red-500 text-sm text-center mt-2"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <form method="POST" class="mt-6 space-y-4">
            <input type="text" name="full_name" placeholder="Full Name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" required
                   class="w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-400 focus:outline-none">

            <input type="email" name="email" placeholder="Email Address" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required
                   class="w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-400 focus:outline-none">

            <input type="text" name="phone" placeholder="Phone Number" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                   class="w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-400 focus:outline-none">

            <input type="text" name="address" placeholder="Address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>"
                   class="w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-400 focus:outline-none">

            <input type="password" name="password" placeholder="Password" required
                   class="w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-400 focus:outline-none">

            <input type="password" name="confirm_password" placeholder="Confirm Password" required
                   class="w-full border border-gray-300 rounded-md py-2 px-3 focus:ring-2 focus:ring-blue-400 focus:outline-none">

            <!-- Hidden role field: change value to 'admin' to create an admin -->
            <input type="hidden" name="role" value="user">

            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-md">
                Create Account
            </button>
        </form>

        <p class="text-center text-gray-500 mt-6">
            Already have an account? <a href="login.php" class="text-blue-500 hover:underline font-semibold">Sign in here</a>
        </p>
    </div>
</main>

<!-- Footer -->
<footer class="bg-white/50 backdrop-blur-md shadow-inner mt-20">
    <div class="max-w-6xl mx-auto px-4 py-4 text-center text-gray-700">
        &copy; <?= date("Y") ?> DriveEase. All rights reserved.
    </div>
</footer>
</body>
</html>
