<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already logged in
if (auth()->isLoggedIn()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $result = auth()->login($username, $password);

    if ($result['success']) {
        header('Location: index.php');
        exit;
    } else {
        $error = $result['message'];
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - <?= APP_NAME ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        * {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-500 to-indigo-600 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md p-8">
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-user-shield text-4xl text-blue-600"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900"><?= APP_NAME ?></h1>
            <p class="text-gray-600 mt-2">Admin Login</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-6">
                <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="username">
                    <i class="fas fa-user mr-2"></i>Username
                </label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                    placeholder="Masukkan username"
                    autocomplete="username"
                >
            </div>

            <div class="mb-6">
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">
                    <i class="fas fa-lock mr-2"></i>Password
                </label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 transition"
                    placeholder="Masukkan password"
                    autocomplete="current-password"
                >
            </div>

            <button
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition duration-200 transform hover:scale-105"
            >
                <i class="fas fa-sign-in-alt mr-2"></i>Login
            </button>
        </form>

        <div class="mt-6 text-center">
            <a href="../index.php" class="text-blue-600 hover:text-blue-700 text-sm">
                <i class="fas fa-arrow-left mr-1"></i>Kembali ke Dashboard
            </a>
        </div>

        <div class="mt-8 pt-6 border-t border-gray-200 text-center text-xs text-gray-500">
            <p>© 2025 <?= INSTITUTION_NAME ?>. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
