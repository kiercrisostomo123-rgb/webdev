<?php

require_once __DIR__ . "/database.php";

/*
|--------------------------------------------------------------------------
| REQUIRE LOGIN
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$student = null;
$message = "";
$messageType = "";
$displayDate = "";
$displayTime = "";

/*
|--------------------------------------------------------------------------
| STUDENT NUMBER SUBMITTED
|--------------------------------------------------------------------------
*/
if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    $studentNumber = trim($_POST["student_number"] ?? "");

    if ($studentNumber === "") {
        $message = "Please enter a student number.";
        $messageType = "error";
    } else {
        /*
        |--------------------------------------------------------------------------
        | FIND STUDENT
        |--------------------------------------------------------------------------
        */
        $stmt = $db->prepare("
            SELECT *
            FROM students
            WHERE student_number = :student_number
            LIMIT 1
        ");

        $stmt->execute([
            ":student_number" => $studentNumber
        ]);

        $student = $stmt->fetch();

        if (!$student) {
            $message = "Student number not found.";
            $messageType = "error";
        } else {
            /*
            |--------------------------------------------------------------------------
            | CURRENT PHILIPPINE DATE/TIME
            |--------------------------------------------------------------------------
            */
            $date = date("Y-m-d");
            $time = date("H:i:s");

            /*
            |--------------------------------------------------------------------------
            | CHECK IF ALREADY ATTENDED TODAY
            |--------------------------------------------------------------------------
            */
            $stmt = $db->prepare("
                SELECT id, attendance_date, attendance_time
                FROM attendance
                WHERE student_id = :student_id
                  AND attendance_date = :attendance_date
                LIMIT 1
            ");

            $stmt->execute([
                ":student_id" => $student["id"],
                ":attendance_date" => $date
            ]);

            $existing = $stmt->fetch();

            if ($existing) {
                $attendedTime = date("h:i A", strtotime($existing["attendance_time"]));
                $attendedDate = date("F d, Y", strtotime($existing["attendance_date"]));
                $displayTime = $attendedTime;
                $displayDate = $attendedDate;

                $message = "Attendance was already recorded today at " . $attendedTime . " for "
                    . $student["first_name"] . " " . $student["last_name"] . ".";
                $messageType = "warning";
            } else {
                /*
                |--------------------------------------------------------------------------
                | RECORD ATTENDANCE
                |--------------------------------------------------------------------------
                */
                $stmt = $db->prepare("
                    INSERT INTO attendance (student_id, attendance_date, attendance_time)
                    VALUES (:student_id, :attendance_date, :attendance_time)
                ");

                $stmt->execute([
                    ":student_id" => $student["id"],
                    ":attendance_date" => $date,
                    ":attendance_time" => $time
                ]);

                $attendedTime = date("h:i A", strtotime($time));
                $attendedDate = date("F d, Y", strtotime($date));
                $displayTime = $attendedTime;
                $displayDate = $attendedDate;

                $message = "Attendance recorded successfully for "
                    . $student["first_name"] . " " . $student["last_name"] . " at " . $attendedTime . ".";
                $messageType = "success";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Dashboard - Attendance Monitoring System</title>
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
<body class="bg-gray-100 min-h-screen">

<!-- NAVBAR -->
<nav class="bg-[#004d34] text-white px-6 py-4 shadow-md">
    <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-3">
        <div class="flex items-center gap-3">
            <img src="kier%20logo.png" alt="Logo" class="w-9 h-9 rounded-lg object-cover bg-white p-0.5 shadow-sm">
            <div>
                <h1 class="font-bold text-lg leading-tight">
                    Attendance Monitoring System
                </h1>
                <p class="text-xs text-green-200">
                    Welcome, <?= htmlspecialchars($_SESSION["username"] ?? "User") ?>
                </p>
            </div>
        </div>

        <div class="flex items-center gap-4 text-sm font-medium">
            <a href="dashboard.php" class="bg-[#003826] px-3 py-1.5 rounded-md hover:bg-[#002e1f] transition-colors">
                Attendance
            </a>
            <a href="student_registration.php" class="hover:text-green-200 transition-colors">
                Student Registration
            </a>
            <a href="attendance_log.php" class="hover:text-green-200 transition-colors">
                Attendance Log
            </a>
            <a href="logout.php" onclick="return confirm('Are you sure you want to log out?');" class="bg-red-600/80 hover:bg-red-600 px-3 py-1.5 rounded-md transition-colors">
                Logout
            </a>
        </div>
    </div>
</nav>

<!-- CONTENT -->
<main class="max-w-5xl mx-auto p-4 sm:p-6 lg:p-8">

    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800 tracking-tight">
            Attendance Dashboard
        </h2>
        <p class="text-gray-600 text-sm mt-1">
            Enter the student number to record today's attendance.
        </p>
    </div>

    <!-- SEARCH FORM -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <form method="POST" id="attendanceForm" action="dashboard.php">
            <label for="student_number" class="block text-sm font-semibold text-gray-700 mb-2">
                Student Number
            </label>
            <div class="flex flex-col sm:flex-row gap-3">
                <input
                    type="text"
                    name="student_number"
                    id="student_number"
                    autofocus
                    autocomplete="off"
                    placeholder="Enter student number (e.g. 2024-00000-SR-0)"
                    class="w-full border border-gray-300 rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-[#004d34] focus:outline-none transition-shadow"
                >
                <button
                    type="submit"
                    class="sm:w-auto px-8 bg-[#004d34] hover:bg-[#003c28] text-white py-3 rounded-lg font-semibold whitespace-nowrap shadow-sm transition-colors"
                >
                    Record Attendance
                </button>
            </div>
        </form>
    </div>

    <!-- MESSAGE BANNER -->
    <?php if ($message): ?>
        <div class="mb-6 p-4 rounded-xl border flex items-center gap-3 text-sm font-medium
            <?= $messageType === "success"
                ? "bg-green-50 border-green-200 text-green-800"
                : ($messageType === "warning"
                    ? "bg-amber-50 border-amber-200 text-amber-800"
                    : "bg-red-50 border-red-200 text-red-800")
            ?>">
            <?php if ($messageType === "success"): ?>
                <svg class="w-5 h-5 text-green-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
            <?php elseif ($messageType === "warning"): ?>
                <svg class="w-5 h-5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                </svg>
            <?php else: ?>
                <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            <?php endif; ?>
            <span><?= htmlspecialchars($message) ?></span>
        </div>
    <?php endif; ?>

    <!-- STUDENT CARD -->
    <?php if ($student): ?>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 sm:p-8">
            <h3 class="text-xl font-bold text-gray-800 mb-6 pb-3 border-b border-gray-100 flex items-center justify-between">
                <span>Student Information</span>
                <span class="text-xs px-2.5 py-1 rounded-full font-semibold <?= $messageType === 'success' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-800' ?>">
                    <?= $messageType === 'success' ? 'Recorded' : 'Already Logged' ?>
                </span>
            </h3>

            <div class="grid md:grid-cols-3 gap-6 items-center">
                <!-- PICTURE -->
                <div class="flex justify-center">
                    <img
                        src="<?= htmlspecialchars(UPLOAD_URL . $student["picture"]) ?>"
                        alt="<?= htmlspecialchars($student["first_name"] . ' ' . $student["last_name"]) ?>"
                        onerror="this.onerror=null; this.src='kier%20logo.png';"
                        class="w-48 h-48 object-cover rounded-xl border border-gray-200 shadow-inner"
                    >
                </div>

                <!-- INFORMATION -->
                <div class="md:col-span-2 space-y-4">
                    <div>
                        <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">
                            Full Name
                        </p>
                        <p class="text-xl font-bold text-gray-900 mt-0.5">
                            <?= htmlspecialchars($student["last_name"]) ?>, <?= htmlspecialchars($student["first_name"]) ?>
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">
                                Student Number
                            </p>
                            <p class="text-base font-semibold text-gray-800 mt-0.5">
                                <?= htmlspecialchars($student["student_number"]) ?>
                            </p>
                        </div>

                        <div>
                            <p class="text-xs uppercase tracking-wider text-gray-500 font-semibold">
                                Year & Section
                            </p>
                            <p class="text-base font-semibold text-gray-800 mt-0.5">
                                <?= htmlspecialchars($student["year_section"]) ?>
                            </p>
                        </div>
                    </div>

                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs text-gray-500 font-semibold">Date</p>
                            <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($displayDate ?: date("F d, Y")) ?></p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 font-semibold">Time Logged</p>
                            <p class="text-sm font-bold text-[#004d34]"><?= htmlspecialchars($displayTime ?: date("h:i A")) ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</main>

<script>
window.onload = function() {
    const studentInput = document.getElementById("student_number");
    if (studentInput) {
        studentInput.focus();
        studentInput.select();
    }
};
</script>

</body>
</html>