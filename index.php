<?php

require_once __DIR__ . "/database.php";

/*
|--------------------------------------------------------------------------
| IF ALREADY LOGGED IN
|--------------------------------------------------------------------------
*/

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";
$info = "";

if (isset($_GET["registered"])) {
    $info = "Account created successfully. You can now log in.";
} elseif (isset($_GET["logged_out"])) {
    $info = "You have been logged out successfully.";
}

$username = "";

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {
        $error = "Please enter your username/email and password.";
    } else {
        /*
        |--------------------------------------------------------------------------
        | SEARCH USER
        |--------------------------------------------------------------------------
        */
        $stmt = $db->prepare("
            SELECT *
            FROM users
            WHERE username = :username
               OR email = :email
            LIMIT 1
        ");

        $stmt->execute([
            ":username" => $username,
            ":email" => $username
        ]);

        $user = $stmt->fetch();

        /*
        |--------------------------------------------------------------------------
        | VERIFY PASSWORD
        |--------------------------------------------------------------------------
        */
        if ($user && password_verify($password, $user["password"])) {
            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            $_SESSION["email"] = $user["email"];

            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Invalid username/email or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log in - Attendance Monitoring System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f8f9fa;
        }
        .bg-brand-green {
            background-color: #004d34;
        }
        .text-brand-green {
            color: #004d34;
        }
        .custom-input {
            background-color: #fcfcfc;
            border: 1px solid #d1d5db;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .custom-input:focus {
            border-color: #004d34;
            box-shadow: 0 0 0 3px rgba(0, 77, 52, 0.15);
        }
        .btn-outline {
            border: 1px solid #004d34;
            color: #004d34;
        }
        .logo-squircle {
            border-radius: 12px;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center p-4 sm:p-6 lg:p-8">

<div class="w-full max-w-5xl min-h-[680px] bg-white rounded-2xl shadow-xl overflow-hidden grid grid-cols-1 md:grid-cols-2">

    <!-- =====================================================
         LEFT SIDE
    ===================================================== -->
    <div class="relative min-h-[300px] md:min-h-full bg-slate-900 flex flex-col justify-end p-8 text-white overflow-hidden">
        <img src="kier.jpg" alt="Login Visual Banner" class="absolute inset-0 w-full h-full object-cover z-0">
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent z-10"></div>
        <div class="relative z-20 space-y-2">
            <h2 class="text-2xl font-bold tracking-tight text-white">
                Welcome Back!
            </h2>
            <p class="text-sm text-gray-200 max-w-sm">
                Access your account securely and manage your attendance portal with ease.
            </p>
        </div>
    </div>

    <!-- =====================================================
         RIGHT SIDE
    ===================================================== -->
    <div class="flex items-center justify-center p-6 sm:p-10 md:p-12 bg-white">
        <div class="w-full max-w-[360px] flex flex-col items-center">

            <!-- LOGO -->
            <div class="mb-5 flex justify-center">
                <img src="kier%20logo.png" alt="Logo" class="w-12 h-12 logo-squircle object-cover shadow-sm">
            </div>

            <!-- TITLE -->
            <h1 class="text-[26px] font-bold text-[#2d2d2d] mb-6 tracking-tight">
                Log in
            </h1>

            <!-- ERROR MESSAGE -->
            <?php if ($error): ?>
                <div class="w-full mb-4 p-3 rounded-md bg-red-50 border border-red-200 text-red-700 text-sm">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <!-- INFO / SUCCESS MESSAGE -->
            <?php if ($info): ?>
                <div class="w-full mb-4 p-3 rounded-md bg-green-50 border border-green-200 text-green-700 text-sm">
                    <?= htmlspecialchars($info) ?>
                </div>
            <?php endif; ?>

            <div class="w-full space-y-4">

                <!-- GOOGLE BUTTON -->
                <button type="button" class="w-full py-2.5 px-4 bg-white btn-outline rounded-md text-[14px] font-medium flex items-center justify-center gap-2.5 shadow-sm hover:bg-gray-50 transition-colors">
                    <strong class="text-lg">G</strong>
                    Log in via Google
                </button>

                <!-- DIVIDER -->
                <div class="relative flex items-center justify-center my-5">
                    <div class="border-t border-gray-200 w-full"></div>
                    <span class="bg-white px-3 text-[11px] font-medium text-gray-400 uppercase tracking-wider absolute">
                        OR
                    </span>
                </div>

                <!-- LOGIN FORM -->
                <form method="POST" action="index.php">

                    <!-- USERNAME -->
                    <div class="mb-4">
                        <label for="username" class="block text-[13px] font-medium text-gray-700 mb-1.5">
                            Email or username
                        </label>
                        <input
                            type="text"
                            id="username"
                            name="username"
                            value="<?= htmlspecialchars($username) ?>"
                            required
                            autofocus
                            autocomplete="username"
                            class="w-full px-3.5 py-2.5 rounded-md text-[14px] custom-input text-gray-900 focus:outline-none"
                        >
                    </div>

                    <!-- PASSWORD -->
                    <div class="mb-3">
                        <label for="password" class="block text-[13px] font-medium text-gray-700 mb-1.5">
                            Password
                        </label>
                        <div class="relative">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                class="w-full px-3.5 py-2.5 pr-12 rounded-md text-[14px] custom-input text-gray-900 focus:outline-none"
                            >
                            <button
                                type="button"
                                id="togglePasswordBtn"
                                onclick="togglePassword()"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-gray-700"
                                aria-label="Toggle password visibility"
                            >
                                <svg id="eyeIcon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                    <circle cx="12" cy="12" r="3"></circle>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- FORGOT PASSWORD -->
                    <div class="mb-5">
                        <a href="#" onclick="alert('Please contact your administrator to reset your password.'); return false;" class="text-[13px] text-brand-green hover:underline font-medium inline-block underline-offset-2">
                            Forgot password?
                        </a>
                    </div>

                    <!-- LOGIN BUTTON -->
                    <button type="submit" class="w-full py-3 px-4 bg-brand-green text-white font-semibold text-[14px] rounded-md shadow-sm hover:opacity-95 transition-opacity">
                        Log in
                    </button>

                </form>

                <!-- CREATE ACCOUNT -->
                <div class="pt-4 space-y-2 text-center">
                    <p class="text-[13px] text-gray-700 font-normal">
                        No account?
                    </p>
                    <a href="create_account.php" class="w-full py-2.5 px-4 bg-white btn-outline rounded-md text-[14px] font-medium shadow-sm hover:bg-gray-50 transition-colors flex justify-center">
                        Create account
                    </a>
                </div>

            </div>
        </div>
    </div>

</div>

<script>
function togglePassword() {
    const password = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");

    if (password.type === "password") {
        password.type = "text";
        eyeIcon.innerHTML = '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>';
    } else {
        password.type = "password";
        eyeIcon.innerHTML = '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
    }
}
</script>

</body>
</html>