<?php
session_start();
require_once "../config/dbconnect.php";

$message = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // Fetch user from the database
    $query = "SELECT * FROM users WHERE email='$email'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['fullname'];
            $_SESSION['role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: ../admin/dashboard.php");
                exit;
            } else {
                header("Location: ../client/dashboard.php");
                exit;
            }

        } else {
            $message = "Incorrect password!";
        }
    } else {
        $message = "No account found with that email!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - CarRental</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-gray-50 flex items-center justify-center p-4">

<div class="relative w-full max-w-md">
    <!-- Login Card -->
    <div class="relative bg-white rounded-xl shadow-xl p-8 z-10 animate-scale-in">
        <!-- Logo -->
        <a href="../index.php" class="flex items-center justify-center gap-3 mb-6">
            <div class="p-2 rounded-xl bg-blue-900">
                <svg class="w-6 h-6 text-blue-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <span class="text-2xl font-bold text-blue-900">Triple M's Car Rental</span>
        </a>

        <h1 class="text-2xl font-bold text-gray-900 text-center">Welcome Back</h1>
        <p class="text-gray-500 text-center mt-2">Sign in to your account to continue</p>

        <form method="POST" class="mt-8 space-y-5">
            <?php if($message): ?>
                <p class="text-red-500 text-sm"><?php echo $message; ?></p>
            <?php endif; ?>

            <!-- Email -->
            <div class="relative">
                <input type="email" name="email" placeholder="Email Address" value="<?php echo htmlspecialchars($email); ?>" required
                       class="w-full border border-gray-300 rounded-md py-2 pl-10 pr-4 focus:ring-2 focus:ring-blue-400 focus:outline-none">
            </div>

            <!-- Password -->
            <div class="relative">
                <input type="password" name="password" placeholder="Password" required
                       class="w-full border border-gray-300 rounded-md py-2 pl-10 pr-10 focus:ring-2 focus:ring-blue-400 focus:outline-none" id="passwordField">
                <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-700">
                    <span id="eyeIcon">Show</span>
                </button>
            </div>

            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-md">
                Sign In
            </button>
        </form>

        <p class="text-center text-gray-500 mt-6">
            Don't have an account? <a href="register.php" class="text-blue-500 hover:underline font-semibold">Create one</a>
        </p>
    </div>
</div>

<script>
function togglePassword() {
    const pwField = document.getElementById('passwordField');
    const eyeIcon = document.getElementById('eyeIcon');
    if (pwField.type === 'password') {
        pwField.type = 'text';
        eyeIcon.textContent = 'Hide';
    } else {
        pwField.type = 'password';
        eyeIcon.textContent = 'Show';
    }
}
</script>
</body>
</html>
