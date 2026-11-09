<?php

require_once __DIR__ . "/database.php";

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$success = "";
$username = "";
$email = "";

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirmPassword = $_POST["confirm_password"] ?? "";

    /*
    |--------------------------------------------------------------------------
    | VALIDATION
    |--------------------------------------------------------------------------
    */
    if ($username === "" || $email === "" || $password === "" || $confirmPassword === "") {
        $error = "Please complete all fields.";
    } elseif (strlen($username) < 3) {
        $error = "Username must be at least 3 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        /*
        |--------------------------------------------------------------------------
        | CHECK EXISTING ACCOUNT
        |--------------------------------------------------------------------------
        */
        $stmt = $db->prepare("
            SELECT id
            FROM users
            WHERE username = :username
               OR email = :email
            LIMIT 1
        ");

        $stmt->execute([
            ":username" => $username,
            ":email" => $email
        ]);

        if ($stmt->fetch()) {
            $error = "Username or email already exists.";
        } else {
            /*
            |--------------------------------------------------------------------------
            | HASH PASSWORD & INSERT
            |--------------------------------------------------------------------------
            */
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $db->prepare("
                INSERT INTO users (username, email, password)
                VALUES (:username, :email, :password)
            ");

            $stmt->execute([
                ":username" => $username,
                ":email" => $email,
                ":password" => $hashedPassword
            ]);

            $success = "Account created successfully. You can now log in.";
            $username = "";
            $email = "";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Attendance Monitoring System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-100 flex items-center justify-center p-6">

<div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-8">

    <div class="text-center mb-6">
        <img src="kier%20logo.png" alt="Logo" class="w-14 h-14 mx-auto rounded-xl object-cover mb-4 shadow-sm">
        <h1 class="text-2xl font-bold text-gray-800">
            Create Account
        </h1>
        <p class="text-sm text-gray-500 mt-1">
            Create an authorized attendance system account.
        </p>
    </div>

    <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-md text-sm">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="mb-4 p-3 bg-green-50 border border-green-200 text-green-700 rounded-md text-sm">
            <p class="font-medium"><?= htmlspecialchars($success) ?></p>
            <a href="index.php" class="inline-block mt-2 font-semibold text-[#004d34] underline underline-offset-2">
                &rarr; Click here to Log in
            </a>
        </div>
    <?php endif; ?>

    <form method="POST" action="create_account.php">

        <!-- USERNAME -->
        <label for="username" class="block text-sm font-medium text-gray-700 mb-1">
            Username
        </label>
        <input
            type="text"
            id="username"
            name="username"
            value="<?= htmlspecialchars($username) ?>"
            required
            autocomplete="username"
            class="w-full border border-gray-300 rounded-md px-3 py-2.5 mb-4 focus:ring-2 focus:ring-[#004d34] focus:outline-none"
        >

        <!-- EMAIL -->
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
            Email
        </label>
        <input
            type="email"
            id="email"
            name="email"
            value="<?= htmlspecialchars($email) ?>"
            required
            autocomplete="email"
            class="w-full border border-gray-300 rounded-md px-3 py-2.5 mb-4 focus:ring-2 focus:ring-[#004d34] focus:outline-none"
        >

        <!-- PASSWORD -->
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
            Password
        </label>
        <div class="relative">
            <input
                type="password"
                id="password"
                name="password"
                required
                minlength="8"
                autocomplete="new-password"
                oninput="checkPassword()"
                class="w-full border border-gray-300 rounded-md px-3 py-2.5 pr-10 focus:ring-2 focus:ring-[#004d34] focus:outline-none"
            >
            <button
                type="button"
                onclick="toggleVisibility('password', 'eye1')"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                aria-label="Toggle password"
            >
                <svg id="eye1" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
        </div>

        <!-- PASSWORD STRENGTH -->
        <div class="mt-2 h-2 bg-gray-200 rounded-full overflow-hidden">
            <div id="strengthBar" class="h-full w-0 transition-all duration-300"></div>
        </div>
        <p id="strengthText" class="text-xs mt-1 text-gray-500">
            Password strength
        </p>

        <!-- CONFIRM PASSWORD -->
        <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1 mt-4">
            Confirm Password
        </label>
        <div class="relative">
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                required
                autocomplete="new-password"
                class="w-full border border-gray-300 rounded-md px-3 py-2.5 pr-10 focus:ring-2 focus:ring-[#004d34] focus:outline-none"
            >
            <button
                type="button"
                onclick="toggleVisibility('confirm_password', 'eye2')"
                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                aria-label="Toggle confirm password"
            >
                <svg id="eye2" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                    <circle cx="12" cy="12" r="3"></circle>
                </svg>
            </button>
        </div>
        <p id="matchText" class="text-xs mt-1"></p>

        <!-- SUBMIT -->
        <button
            type="submit"
            class="w-full bg-[#004d34] hover:bg-[#003c28] text-white py-3 rounded-md font-semibold mt-6 shadow-sm transition-colors"
        >
            Create Account
        </button>

    </form>

    <div class="text-center mt-5">
        <a href="index.php" class="text-sm text-[#004d34] hover:underline font-medium">
            Already have an account? Log in
        </a>
    </div>

</div>

<script>
function toggleVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === "password") {
        input.type = "text";
        icon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    } else {
        input.type = "password";
        icon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    }
}

function checkPassword() {
    const password = document.getElementById("password").value;
    const bar = document.getElementById("strengthBar");
    const text = document.getElementById("strengthText");

    let strength = 0;

    if (password.length >= 8) {
        strength++;
    }
    if (/[A-Z]/.test(password)) {
        strength++;
    }
    if (/[0-9]/.test(password)) {
        strength++;
    }
    if (/[^A-Za-z0-9]/.test(password)) {
        strength++;
    }

    if (password.length === 0) {
        bar.style.width = "0%";
        bar.className = "h-full w-0 transition-all duration-300";
        text.textContent = "Password strength";
        text.className = "text-xs mt-1 text-gray-500";
    } else if (strength <= 1) {
        bar.style.width = "25%";
        bar.className = "h-full bg-red-500 transition-all duration-300";
        text.textContent = "Weak (minimum 8 characters, try adding numbers or symbols)";
        text.className = "text-xs mt-1 text-red-600";
    } else if (strength === 2) {
        bar.style.width = "50%";
        bar.className = "h-full bg-yellow-500 transition-all duration-300";
        text.textContent = "Fair";
        text.className = "text-xs mt-1 text-yellow-600";
    } else if (strength === 3) {
        bar.style.width = "75%";
        bar.className = "h-full bg-blue-500 transition-all duration-300";
        text.textContent = "Good";
        text.className = "text-xs mt-1 text-blue-600";
    } else {
        bar.style.width = "100%";
        bar.className = "h-full bg-green-500 transition-all duration-300";
        text.textContent = "Strong";
        text.className = "text-xs mt-1 text-green-600";
    }

    validateMatch();
}

function validateMatch() {
    const password = document.getElementById("password").value;
    const confirm = document.getElementById("confirm_password").value;
    const text = document.getElementById("matchText");

    if (confirm === "") {
        text.textContent = "";
    } else if (password === confirm) {
        text.textContent = "Passwords match.";
        text.className = "text-xs mt-1 text-green-600";
    } else {
        text.textContent = "Passwords do not match.";
        text.className = "text-xs mt-1 text-red-600";
    }
}

document.getElementById("confirm_password").addEventListener("input", validateMatch);
</script>

</body>
</html>