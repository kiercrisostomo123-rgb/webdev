<?php

require_once __DIR__ . "/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$search = trim($_GET["search"] ?? "");
$filterDate = trim($_GET["date"] ?? "");

/*
|--------------------------------------------------------------------------
| QUERY ATTENDANCE LOGS
|--------------------------------------------------------------------------
*/
$sql = "
    SELECT
        attendance.id,
        students.student_number,
        students.first_name,
        students.last_name,
        students.year_section,
        students.picture,
        attendance.attendance_date,
        attendance.attendance_time
    FROM attendance
    INNER JOIN students ON students.id = attendance.student_id
    WHERE 1=1
";

$params = [];

if ($search !== "") {
    $sql .= " AND (
        students.student_number LIKE :search
        OR students.first_name LIKE :search
        OR students.last_name LIKE :search
        OR students.year_section LIKE :search
    )";
    $params[":search"] = "%" . $search . "%";
}

if ($filterDate !== "") {
    $sql .= " AND attendance.attendance_date = :filter_date";
    $params[":filter_date"] = $filterDate;
}

$sql .= " ORDER BY attendance.attendance_date DESC, attendance.attendance_time DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$attendance = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| STATS
|--------------------------------------------------------------------------
*/
$todayDate = date("Y-m-d");

$todayStmt = $db->prepare("SELECT COUNT(*) FROM attendance WHERE attendance_date = :today");
$todayStmt->execute([":today" => $todayDate]);
$todayCount = (int) $todayStmt->fetchColumn();

$totalCount = (int) $db->query("SELECT COUNT(*) FROM attendance")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Log - Attendance Monitoring System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        @media print {
            nav, .no-print {
                display: none !important;
            }
            body {
                background: white;
            }
            .shadow-sm, .shadow-md, .shadow {
                box-shadow: none !important;
            }
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
            <a href="dashboard.php" class="hover:text-green-200 transition-colors">
                Attendance
            </a>
            <a href="student_registration.php" class="hover:text-green-200 transition-colors">
                Student Registration
            </a>
            <a href="attendance_log.php" class="bg-[#003826] px-3 py-1.5 rounded-md hover:bg-[#002e1f] transition-colors">
                Attendance Log
            </a>
            <a href="logout.php" onclick="return confirm('Are you sure you want to log out?');" class="bg-red-600/80 hover:bg-red-600 px-3 py-1.5 rounded-md transition-colors">
                Logout
            </a>
        </div>
    </div>
</nav>

<main class="max-w-6xl mx-auto p-4 sm:p-6 lg:p-8">

    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 tracking-tight">
                Attendance Log
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                View, search, and verify all recorded student attendance.
            </p>
        </div>

        <!-- STATS PILLS & PRINT -->
        <div class="flex items-center gap-3 no-print">
            <span class="bg-white border border-gray-200 text-gray-700 text-xs font-semibold px-3 py-1.5 rounded-lg shadow-sm">
                Today: <strong class="text-[#004d34] font-bold"><?= $todayCount ?></strong>
            </span>
            <span class="bg-white border border-gray-200 text-gray-700 text-xs font-semibold px-3 py-1.5 rounded-lg shadow-sm">
                Total: <strong class="text-[#004d34] font-bold"><?= $totalCount ?></strong>
            </span>
            <button onclick="window.print()" class="bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold px-3.5 py-1.5 rounded-lg shadow-sm flex items-center gap-1.5 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                </svg>
                Print Log
            </button>
        </div>
    </div>

    <!-- FILTER & SEARCH BAR -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-4 mb-6 no-print">
        <form method="GET" action="attendance_log.php" class="flex flex-col md:flex-row gap-3">
            <div class="flex-1">
                <input
                    type="text"
                    name="search"
                    value="<?= htmlspecialchars($search) ?>"
                    placeholder="Search by student number, name, or course..."
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-[#004d34] focus:outline-none"
                >
            </div>
            <div class="w-full md:w-48">
                <input
                    type="date"
                    name="date"
                    value="<?= htmlspecialchars($filterDate) ?>"
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2 text-sm focus:ring-2 focus:ring-[#004d34] focus:outline-none text-gray-700"
                >
            </div>
            <div class="flex gap-2">
                <button
                    type="submit"
                    class="bg-[#004d34] hover:bg-[#003c28] text-white px-5 py-2 rounded-lg text-sm font-semibold transition-colors shadow-sm"
                >
                    Filter
                </button>
                <?php if ($search !== "" || $filterDate !== ""): ?>
                    <a
                        href="attendance_log.php"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center justify-center"
                    >
                        Reset
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- TABLE -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead class="bg-gray-50 border-b border-gray-200 text-gray-600 uppercase text-xs tracking-wider">
                    <tr>
                        <th class="p-4">Picture</th>
                        <th class="p-4">Student Number</th>
                        <th class="p-4">Student Name</th>
                        <th class="p-4">Year & Section</th>
                        <th class="p-4">Date</th>
                        <th class="p-4">Time</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    <?php if (!$attendance): ?>
                        <tr>
                            <td colspan="6" class="p-8 text-center text-gray-500">
                                <div class="max-w-xs mx-auto">
                                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <p class="font-medium text-gray-600">No attendance records found.</p>
                                    <p class="text-xs text-gray-400 mt-1">
                                        <?= ($search !== "" || $filterDate !== "") ? "Try adjusting your filter or search query." : "Recorded student attendance will appear here." ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendance as $row): ?>
                            <tr class="hover:bg-gray-50/80 transition-colors">
                                <td class="p-4">
                                    <img
                                        src="<?= htmlspecialchars(UPLOAD_URL . $row["picture"]) ?>"
                                        alt="<?= htmlspecialchars($row["first_name"] . ' ' . $row["last_name"]) ?>"
                                        onerror="this.onerror=null; this.src='kier%20logo.png';"
                                        class="w-11 h-11 rounded-full object-cover border border-gray-200 shadow-sm"
                                    >
                                </td>
                                <td class="p-4 font-semibold text-gray-900">
                                    <?= htmlspecialchars($row["student_number"]) ?>
                                </td>
                                <td class="p-4 text-gray-900">
                                    <span class="font-bold"><?= htmlspecialchars($row["last_name"]) ?></span>,
                                    <?= htmlspecialchars($row["first_name"]) ?>
                                </td>
                                <td class="p-4 text-gray-600">
                                    <span class="bg-gray-100 text-gray-700 px-2.5 py-1 rounded-md text-xs font-medium">
                                        <?= htmlspecialchars($row["year_section"]) ?>
                                    </span>
                                </td>
                                <td class="p-4 text-gray-700">
                                    <?= htmlspecialchars(date("M d, Y", strtotime($row["attendance_date"]))) ?>
                                </td>
                                <td class="p-4">
                                    <span class="inline-flex items-center gap-1 font-semibold text-[#004d34] bg-emerald-50 px-2.5 py-1 rounded-full text-xs border border-emerald-200">
                                        <svg class="w-3 h-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <?= htmlspecialchars(date("h:i A", strtotime($row["attendance_time"]))) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</main>

</body>
</html>
