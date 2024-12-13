<?php
session_start();
include('server.php'); // Include server logic for login validation

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'phpmailer/src/Exception.php';
require 'phpmailer/src/PHPMailer.php';
require 'phpmailer/src/SMTP.php';

$errors = array();
$otp_sent = false;

if (isset($_POST['login_user'])) {
    $con = mysqli_connect('localhost', 'root', '', 'guidancehub');

    if (!$con) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $email = mysqli_real_escape_string($con, $_POST['email']);

    if (empty($email)) {
        array_push($errors, "Email is required");
    }

    if (count($errors) == 0) {
        $query = "SELECT * FROM users WHERE email='$email'";
        $result = mysqli_query($con, $query);

        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
            $email = $user['email'];
            $role = $user['role']; // Assuming 'role' column exists in the users table

            // Generate OTP and set expiration
            $otp = rand(100000, 999999);
            $_SESSION['otp'] = $otp;
            $_SESSION['otp_expiration'] = time() + 300; // 5 minutes from now
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role; // Store the role in the session

            // Send OTP via email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'guidancehub01@gmail.com';
                $mail->Password   = 'zjrtujjwbznuzbzv';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;

                $mail->setFrom('guidancehub01@gmail.com', 'GuidanceHub');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'Login OTP';
                $mail->Body    = "Your OTP is: <b>$otp</b>. This OTP is valid for 5 minutes.";

                $mail->send();
                $otp_sent = true;
            } catch (Exception $e) {
                array_push($errors, "Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
            }
        } else {
            array_push($errors, "Email not found");
        }
    }
}

if (isset($_POST['verify_otp'])) {
    $entered_otp = mysqli_real_escape_string($con, $_POST['otp']);

    // Check if OTP is expired
    if (time() > $_SESSION['otp_expiration']) {
        array_push($errors, "OTP has expired. Please request a new one.");
    } else if ($entered_otp == $_SESSION['otp']) {
        $_SESSION['success'] = "You are now logged in";
        
        // Redirect to the appropriate dashboard based on the role
        if ($_SESSION['role'] == 'counselor') {
            header('Location: /src/counselor/index.php'); // Redirect to counselor dashboard
        } else if ($_SESSION['role'] == 'student') {
            header('Location: /src/student/index.php'); // Redirect to student dashboard
        }
        else if ($_SESSION['role'] == 'admin') {
            header('Location: /src/admin/index.php'); // Redirect to admin dashboard
        }
        exit;
    } else {
        array_push($errors, "Incorrect OTP. Please try again.");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login | GuidanceHub</title>
    <link rel="icon" type="images/x-icon" href="/src/images/UMAK-CGCS-logo.png" />
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <script src="https://cdn.tailwindcss.com"></script>
        <script src="https://kit.fontawesome.com/95c10202b4.js" crossorigin="anonymous"></script>
</head>
<body>
    <!-- Background Image with overlay -->
    <div class="relative flex items-center justify-center bg-center bg-cover hero"
        style="background-image: url('/src/images/UMak-Facade-Admin.jpg'); height: 100vh;">
        <div class="absolute inset-0 bg-black opacity-50"></div> <!-- Dark overlay -->

        <!-- Form Container -->
        <div class="relative z-10 flex items-center justify-center w-full h-full">
            <div class="w-full max-w-md p-8 bg-white rounded-lg shadow-md">
                <div class="float-left mb-4 text-xl font-semibold">
                    <a href="/src/entry-page/index.php">
                        <i class="fa-solid fa-arrow-left"></i>
                    </a>
                </div>
            <h2 class="mb-6 text-2xl font-semibold text-center">Login</h2>

                <?php if (count($errors) > 0): ?>
                    <div class="p-3 mb-4 text-red-700 bg-red-100 rounded-md">
                        <?php foreach ($errors as $error): ?>
                            <p><?php echo $error; ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form Start -->
                <form action="login.php" method="POST">
                    <div class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" id="email" name="email" class="w-full px-4 py-2 mt-1 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600" required>
                        </div>
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="password" id="password" name="password" class="w-full px-4 py-2 mt-1 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600" required>
                        </div>

                        <div class="flex justify-center mt-4">
                            <button type="submit" name="login_user" class="w-full px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-600">Send OTP</button>
                        </div>
                    </div>
                </form>

                <?php if ($otp_sent): ?>
                    <!-- OTP Verification Form -->
                    <form action="login.php" method="POST">
                        <div class="mt-6 space-y-4">
                            <div>
                                <label for="otp" class="block text-sm font-medium text-gray-700">Enter OTP</label>
                                <input type="text" id="otp" name="otp" class="w-full px-4 py-2 mt-1 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-600" required>
                            </div>

                            <div class="flex justify-center mt-4">
                                <button type="submit" name="verify_otp" class="w-full px-4 py-2 text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-600">Verify OTP</button>
                            </div>
                        </div>
                    </form>
                <?php endif; ?>

                <div class="mt-4 text-center">
                    <p class="text-sm text-gray-600">Don't have an account? <a href="/src/entry-page/signup.php" class="text-blue-600 hover:text-blue-800">Sign up here</a></p>
                </div>
            </div>
        </div>
    </div>

</body>

</html>
