<?php

require_once __DIR__ . "/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$error = "";
$success = "";

$firstName = "";
$lastName = "";
$studentNumber = "";
$yearSection = "";

if (($_SERVER["REQUEST_METHOD"] ?? "") === "POST") {
    $firstName = trim($_POST["first_name"] ?? "");
    $lastName = trim($_POST["last_name"] ?? "");
    $studentNumber = trim($_POST["student_number"] ?? "");
    $yearSection = trim($_POST["year_section"] ?? "");

    /*
    |--------------------------------------------------------------------------
    | BASIC VALIDATION
    |--------------------------------------------------------------------------
    */
    if ($firstName === "" || $lastName === "" || $studentNumber === "" || $yearSection === "") {
        $error = "Please complete all fields.";
    } elseif (!isset($_FILES["picture"]) || $_FILES["picture"]["error"] !== UPLOAD_ERR_OK) {
        $error = "Please upload a valid student picture (JPG or PNG).";
    } else {
        $file = $_FILES["picture"];

        /*
        |--------------------------------------------------------------------------
        | 2 MB LIMIT
        |--------------------------------------------------------------------------
        */
        if ($file["size"] > 2 * 1024 * 1024) {
            $error = "Picture must not exceed 2 MB.";
        } else {
            /*
            |--------------------------------------------------------------------------
            | CHECK IMAGE TYPE
            |--------------------------------------------------------------------------
            */
            $imageInfo = @getimagesize($file["tmp_name"]);

            if (!$imageInfo) {
                $error = "Uploaded file is not a valid image.";
            } else {
                $mime = $imageInfo["mime"];
                $allowedMimes = ["image/jpeg", "image/png"];

                if (!in_array($mime, $allowedMimes, true)) {
                    $error = "Only JPG, JPEG, and PNG pictures are allowed.";
                } else {
                    /*
                    |--------------------------------------------------------------------------
                    | CHECK EXISTING STUDENT
                    |--------------------------------------------------------------------------
                    */
                    $stmt = $db->prepare("
                        SELECT id
                        FROM students
                        WHERE student_number = :student_number
                        LIMIT 1
                    ");

                    $stmt->execute([
                        ":student_number" => $studentNumber
                    ]);

                    if ($stmt->fetch()) {
                        $error = "A student with student number '" . htmlspecialchars($studentNumber) . "' already exists.";
                    } else {
                        /*
                        |--------------------------------------------------------------------------
                        | SANITIZE FILE NAME & SAVE
                        |--------------------------------------------------------------------------
                        */
                        $safeStudentNumber = preg_replace('/[^a-zA-Z0-9_-]/', '_', $studentNumber);
                        $fileName = $safeStudentNumber . ".jpg";
                        $destination = UPLOAD_DIR . $fileName;

                        $saved = false;

                        if ($mime === "image/jpeg" && function_exists("imagecreatefromjpeg")) {
                            $img = @imagecreatefromjpeg($file["tmp_name"]);
                            if ($img) {
                                $saved = imagejpeg($img, $destination, 90);
                                imagedestroy($img);
                            }
                        } elseif ($mime === "image/png" && function_exists("imagecreatefrompng")) {
                            $img = @imagecreatefrompng($file["tmp_name"]);
                            if ($img) {
                                $width = imagesx($img);
                                $height = imagesy($img);
                                $bg = imagecreatetruecolor($width, $height);
                                $white = imagecolorallocate($bg, 255, 255, 255);
                                imagefill($bg, 0, 0, $white);
                                imagecopy($bg, $img, 0, 0, 0, 0, $width, $height);
                                $saved = imagejpeg($bg, $destination, 90);
                                imagedestroy($img);
                                imagedestroy($bg);
                            }
                        }

                        // Fallback to direct move_uploaded_file if GD did not process
                        if (!$saved) {
                            $saved = move_uploaded_file($file["tmp_name"], $destination);
                        }

                        if (!$saved) {
                            $error = "Unable to save the uploaded picture. Please check folder permissions.";
                        } else {
                            /*
                            |--------------------------------------------------------------------------
                            | INSERT STUDENT
                            |--------------------------------------------------------------------------
                            */
                            $stmt = $db->prepare("
                                INSERT INTO students (student_number, first_name, last_name, year_section, picture)
                                VALUES (:student_number, :first_name, :last_name, :year_section, :picture)
                            ");

                            $stmt->execute([
                                ":student_number" => $studentNumber,
                                ":first_name" => $firstName,
                                ":last_name" => $lastName,
                                ":year_section" => $yearSection,
                                ":picture" => $fileName
                            ]);

                            $success = "Student " . htmlspecialchars($firstName . " " . $lastName) . " registered successfully.";
                            $firstName = "";
                            $lastName = "";
                            $studentNumber = "";
                            $yearSection = "";
                        }
                    }
                }
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
    <title>Student Registration - Attendance Monitoring System</title>
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
            <a href="dashboard.php" class="hover:text-green-200 transition-colors">
                Attendance
            </a>
            <a href="student_registration.php" class="bg-[#003826] px-3 py-1.5 rounded-md hover:bg-[#002e1f] transition-colors">
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

<main class="max-w-2xl mx-auto p-4 sm:p-6 lg:p-8">

    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 sm:p-8">

        <div class="mb-6 pb-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-800 tracking-tight">
                    Student Registration
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Add a new student profile and photo to the system database.
                </p>
            </div>
            <a href="dashboard.php" class="text-sm text-[#004d34] hover:underline font-medium">
                &larr; Dashboard
            </a>
        </div>

        <?php if ($error): ?>
            <div class="mb-5 p-3 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-5 p-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm flex items-center justify-between">
                <div><?= $success ?></div>
                <a href="dashboard.php" class="font-bold underline text-[#004d34]">Go to Attendance &rarr;</a>
            </div>
        <?php endif; ?>

        <form method="POST" action="student_registration.php" enctype="multipart/form-data">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                <div>
                    <label for="first_name" class="block text-sm font-semibold text-gray-700 mb-1">
                        First Name
                    </label>
                    <input
                        type="text"
                        id="first_name"
                        name="first_name"
                        value="<?= htmlspecialchars($firstName) ?>"
                        required
                        placeholder="e.g. Juan"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-[#004d34] focus:outline-none"
                    >
                </div>

                <div>
                    <label for="last_name" class="block text-sm font-semibold text-gray-700 mb-1">
                        Last Name
                    </label>
                    <input
                        type="text"
                        id="last_name"
                        name="last_name"
                        value="<?= htmlspecialchars($lastName) ?>"
                        required
                        placeholder="e.g. Dela Cruz"
                        class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-[#004d34] focus:outline-none"
                    >
                </div>
            </div>

            <div class="mb-4">
                <label for="student_number" class="block text-sm font-semibold text-gray-700 mb-1">
                    Student Number
                </label>
                <input
                    type="text"
                    id="student_number"
                    name="student_number"
                    value="<?= htmlspecialchars($studentNumber) ?>"
                    required
                    placeholder="e.g. 2024-00000-SR-0"
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-[#004d34] focus:outline-none"
                >
            </div>

            <div class="mb-4">
                <label for="year_section" class="block text-sm font-semibold text-gray-700 mb-1">
                    Year & Section / Course
                </label>
                <input
                    type="text"
                    id="year_section"
                    name="year_section"
                    value="<?= htmlspecialchars($yearSection) ?>"
                    required
                    placeholder="e.g. BSIT 4-1"
                    class="w-full border border-gray-300 rounded-lg px-3.5 py-2.5 focus:ring-2 focus:ring-[#004d34] focus:outline-none"
                >
            </div>

            <div class="mb-6">
                <label for="picture" class="block text-sm font-semibold text-gray-700 mb-1">
                    Student Picture
                </label>

                <!-- LIVE PREVIEW CONTAINER -->
                <div class="flex items-center gap-4 mb-2">
                    <img
                        id="imagePreview"
                        src="kier%20logo.png"
                        alt="Preview"
                        class="w-20 h-20 rounded-xl object-cover border border-gray-200 bg-gray-50 shadow-sm hidden"
                    >
                    <div class="flex-1">
                        <input
                            type="file"
                            id="picture"
                            name="picture"
                            accept=".jpg,.jpeg,.png,image/jpeg,image/png"
                            required
                            onchange="previewPhoto(event)"
                            class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-[#004d34] focus:outline-none file:mr-3 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                        >
                        <p class="text-xs text-gray-500 mt-1">
                            Supported: JPG, JPEG, PNG. Maximum file size: 2 MB.
                        </p>
                    </div>
                </div>
            </div>

            <button
                type="submit"
                class="w-full bg-[#004d34] hover:bg-[#003c28] text-white py-3 rounded-lg font-semibold shadow-sm transition-colors text-base"
            >
                Register Student
            </button>

        </form>

    </div>

</main>

<script>
function previewPhoto(event) {
    const file = event.target.files[0];
    const preview = document.getElementById("imagePreview");
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            preview.src = e.target.result;
            preview.classList.remove("hidden");
        };
        reader.readAsDataURL(file);
    } else {
        preview.classList.add("hidden");
    }
}
</script>

</body>
</html>