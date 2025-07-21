<?php
session_start();
require_once '../includes/config.php';

// Fetch departments for the dropdown
$departments = [];
try {
    $deptStmt = $pdo->query("SELECT department_id, name FROM Departments ORDER BY name");
    $departments = $deptStmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    // Silently handle error
}

// If OTP form is submitted
if (isset($_POST['verify_otp'])) {
    $entered_otp = $_POST['otp'];
    
    // Verify OTP
    if (isset($_SESSION['signup_otp']) && $_SESSION['signup_otp'] == $entered_otp) {
        // OTP verified, now register the user
        try {
            $stmt = $pdo->prepare("INSERT INTO Users (user_id, username, email, password_hash, phone, profile_pic, is_verified, is_active, did) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_SESSION['signup_data']['cms_id'],
                $_SESSION['signup_data']['username'],
                $_SESSION['signup_data']['email'],
                $_SESSION['signup_data']['password'],
                $_SESSION['signup_data']['phone'],
                $_SESSION['signup_data']['profile_pic'],
                1, // Set as verified since OTP is confirmed
                $_SESSION['signup_data']['is_active'],
                $_SESSION['signup_data']['did']
            ]);
            
            // Clear session variables
            unset($_SESSION['signup_otp']);
            unset($_SESSION['signup_data']);
            unset($_SESSION['dev_otp_display']); // Clear development OTP display
            
            // Redirect to login
            $_SESSION['success_message'] = "Registration successful! You can now login.";
            header("Location: login.php");
            exit();
        } catch(PDOException $e) {
            $error = "Registration failed: " . $e->getMessage();
        }
    } else {
        $error = "Invalid OTP. Please try again.";
    }
    
    // Show OTP form again if verification failed
    $show_otp_form = true;
}

// If initial signup form is submitted
elseif ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['verify_otp'])) {
    $username = $_POST['name'];
    $cms_id = $_POST['cms_id'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = $_POST['phone'];
    $did = isset($_POST['department']) ? (int)$_POST['department'] : null;
    $is_verified = 0; // Will be set to 1 after OTP verification
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 1;
    $profile_pic = null;

    // Handle profile picture upload
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/profile_pics/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_tmp = $_FILES['profile_pic']['tmp_name'];
        $file_name = uniqid('profile_', true) . '_' . basename($_FILES['profile_pic']['name']);
        $target_path = $upload_dir . $file_name;
        if (move_uploaded_file($file_tmp, $target_path)) {
            $profile_pic = 'uploads/profile_pics/' . $file_name;
        } else {
            $error = "Failed to upload profile picture.";
        }
    }

    // Validate NUST email
    if (!preg_match('/@(nust|seecs)\.edu\.pk$/', $email)) {
        $error = "Please use your official NUST or SEECS email address";
    } 
    // Validate password strength
    elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/', $_POST['password'])) {
        $error = "Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, and one number";
    }
    else {
        // Generate 6-digit OTP
        $otp = rand(100000, 999999);
        
        // Store OTP and user data in session
        $_SESSION['signup_otp'] = $otp;
        $_SESSION['signup_data'] = [
            'username' => $username,
            'cms_id' => $cms_id,
            'email' => $email,
            'password' => $password,
            'phone' => $phone,
            'profile_pic' => $profile_pic,
            'is_verified' => $is_verified,
            'is_active' => $is_active,
            'did' => $did
        ];
        
        // For development: Instead of sending email, just show OTP form and display OTP
        $_SESSION['dev_otp_display'] = $otp; 
        $show_otp_form = true;
        
  
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>NUST Lost and Found</title>
    <link rel="stylesheet" href="signup.css">
</head>
<body>
    <!-- Top Header Section -->
    <header class="top-bar">
        <img src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png" alt="NUST Logo" class="logo">
        <div class="header-content">
            <h1>Lost and Found</h1>
            <nav>
                <a href="#">lose it</a> |
                <a href="#">list it</a> |
                <a href="#">find it</a>
            </nav>
        </div>
    </header>

    <!-- Form Section -->
    <main class="form-wrapper">
        <div class="form-container">
            <?php if (isset($error)) echo "<p class='error'>$error</p>"; ?>
            
            <?php if (isset($show_otp_form) && $show_otp_form): ?>
                <!-- OTP Verification Form -->
                <form class="register-form" method="POST" action="">
                    <h2>Email Verification</h2>
                    <p>We've sent a verification code to your email address. Please enter it below to complete your registration.</p>
                    
                    <?php if (isset($_SESSION['dev_otp_display'])): ?>
                    <div style="background-color: #ffffe0; border: 1px solid #e6db55; padding: 10px; margin-bottom: 15px;">
                        <p><strong>DEVELOPMENT MODE:</strong> Your OTP is: <span style="font-weight: bold;"><?php echo $_SESSION['dev_otp_display']; ?></span></p>
                        <p style="font-size: 0.8em;">This is shown only for development purposes. In production, this would be sent via email.</p>
                    </div>
                    <?php endif; ?>
                    
                    <label for="otp">Enter OTP:</label>
                    <input type="text" id="otp" name="otp" required maxlength="6" pattern="[0-9]{6}" title="Please enter the 6-digit code">
                    
                    <input type="hidden" name="verify_otp" value="1">
                    <button type="submit">Verify & Register</button>
                    
                    <p class="disclaimer">
                        Didn't receive the code? Check your spam folder.
                    </p>
                </form>
            <?php else: ?>
                <!-- Initial Registration Form -->
                <form class="register-form" method="POST" action="" enctype="multipart/form-data">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>

                    <label for="cms_id">CMS ID:</label>
                    <input type="text" id="cms_id" name="cms_id" required>

                    <label for="phone">Phone Number:</label>
                    <input type="tel" id="phone" name="phone" pattern="[0-9]{11}" title="Please enter a valid 11-digit phone number" required>

                    <label for="email">Email (Official NUST or SEECS mail):</label>
                    <input type="email" id="email" name="email" pattern="[a-z0-9._%+-]+@(nust|seecs)\.edu\.pk$" title="Please use your official NUST or SEECS email address" required>

                    <label for="department">Department:</label>
                    <select id="department" name="department" required>
                        <option value="">Select your department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['department_id']; ?>"><?php echo htmlspecialchars($dept['name']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password" 
                           minlength="8" 
                           pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$" 
                           title="Password must be at least 8 characters long and include at least one uppercase letter, one lowercase letter, and one number" 
                           required>
                    <div id="password-requirements" class="password-info" style="font-size: 0.8em; margin-top: 5px; color: #555;">
                        Password must contain:
                        <ul style="margin-top: 5px; margin-left: 20px;">
                            <li id="length-check">✗ At least 8 characters</li>
                            <li id="uppercase-check">✗ At least one uppercase letter (A-Z)</li>
                            <li id="lowercase-check">✗ At least one lowercase letter (a-z)</li>
                            <li id="number-check">✗ At least one number (0-9)</li>
                        </ul>
                    </div>

                    <label for="profile_pic">Profile Picture (optional):</label>
                    <input type="file" id="profile_pic" name="profile_pic" accept="image/*">

                    <input type="hidden" name="is_verified" value="0">
                    <input type="hidden" name="is_active" value="1">

                    <button type="submit">JOIN NOW</button>

                    <p class="disclaimer">
                        By clicking join now, you agree to NUST lost and found privacy policy
                    </p>
                </form>
            <?php endif; ?>
        </div>
    </main>
    <script>
        // Password validation
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const lengthCheck = document.getElementById('length-check');
            const uppercaseCheck = document.getElementById('uppercase-check');
            const lowercaseCheck = document.getElementById('lowercase-check');
            const numberCheck = document.getElementById('number-check');
            
            // Function to validate password and update visual feedback
            function validatePassword() {
                const password = passwordInput.value;
                
                // Check length
                if (password.length >= 8) {
                    lengthCheck.innerHTML = '✓ At least 8 characters';
                    lengthCheck.style.color = 'green';
                } else {
                    lengthCheck.innerHTML = '✗ At least 8 characters';
                    lengthCheck.style.color = '';
                }
                
                // Check uppercase
                if (/[A-Z]/.test(password)) {
                    uppercaseCheck.innerHTML = '✓ At least one uppercase letter (A-Z)';
                    uppercaseCheck.style.color = 'green';
                } else {
                    uppercaseCheck.innerHTML = '✗ At least one uppercase letter (A-Z)';
                    uppercaseCheck.style.color = '';
                }
                
                // Check lowercase
                if (/[a-z]/.test(password)) {
                    lowercaseCheck.innerHTML = '✓ At least one lowercase letter (a-z)';
                    lowercaseCheck.style.color = 'green';
                } else {
                    lowercaseCheck.innerHTML = '✗ At least one lowercase letter (a-z)';
                    lowercaseCheck.style.color = '';
                }
                
                // Check number
                if (/\d/.test(password)) {
                    numberCheck.innerHTML = '✓ At least one number (0-9)';
                    numberCheck.style.color = 'green';
                } else {
                    numberCheck.innerHTML = '✗ At least one number (0-9)';
                    numberCheck.style.color = '';
                }
            }
            
            // Add event listeners
            if (passwordInput) {
                passwordInput.addEventListener('keyup', validatePassword);
                passwordInput.addEventListener('input', validatePassword);
            }
        });
    </script>
</body>
</html> 