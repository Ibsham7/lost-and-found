<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cms_id = $_POST['cms_id'];
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE user_id = ?");
        $stmt->execute([$cms_id]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            header("Location: home.php");
            exit();
        } else {
            $error = "Invalid CMS ID or password";
        }
    } catch(PDOException $e) {
        $error = "Login failed: " . $e->getMessage();
    }
}

// Get success message from session if it exists
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']); // Clear after retrieving
?>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Lost and Found</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
    />
    <style>
        .custom-shift{
            margin-left: 65px;
        }
        @import url("https://fonts.googleapis.com/css2?family=Times+New+Roman&display=swap");
    </style>
</head>
<body class="bg-[#1d3e6a] font-[Times New Roman] min-h-screen flex flex-col justify-between">
    <!-- Header -->
    <header class="bg-[#ccc] flex items-center px-6 py-3 rounded-b-md relative" style="height: 80px">
        <img
            src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png"
            alt="NUST University circular logo with blue and white colors"
            class="absolute left-6 top-1/2 -translate-y-1/2 w-20 h-20 object-contain"
            width="80"
            height="80"
        />
        <h1 class="mx-auto text-3xl font-bold leading-none select-none" style="font-family: 'Times New Roman', serif">
            Lost and Found
        </h1>
        <nav class="absolute right-6 top-1/2 -translate-y-1/2 text-black text-lg font-normal space-x-2 select-none" style="font-family: 'Times New Roman', serif">
            <a href="#" class="hover:underline">lose it</a>
            <span>|</span>
            <a href="#" class="hover:underline">list it</a>
            <span>|</span>
            <a href="#" class="hover:underline">find it</a>
        </nav>
    </header>

    <!-- Main content -->
    <main class="flex justify-center items-center flex-grow">
        <div class="relative w-80 h-80 bg-white shadow-lg flex items-center justify-center">
            <!-- Background Image -->
            <img
                src="https://storage.googleapis.com/a1aa/image/160d6906-0bd9-4195-11fe-6c35a109d4c1.jpg"
                alt="Faint line drawing of NUST campus buildings in light blue behind login form"
                class="absolute inset-0 w-full h-full object-cover opacity-20"
            />

            <!-- Border -->
            <div class="absolute top-0 left-0 right-0 bottom-0 border-8 border-[#d9942a] pointer-events-none" style="box-sizing: content-box;"></div>

            <!-- Form Centered Vertically and Horizontally -->
            <form method="POST" action="" aria-label="Access to platform login form" class="relative bg-[#d9942a] p-6 w-64 flex flex-col gap-4 rounded shadow-md">
                <h2 class="font-bold text-lg text-black text-center">ACCESS TO PLATFORM</h2>
                
                <?php if (isset($error)) echo "<p class='text-red-500 text-sm text-center'>$error</p>"; ?>
                <?php if (!empty($success_message)) echo "<p class='text-green-600 text-sm text-center'>$success_message</p>"; ?>

                <label for="cms_id" class="font-semibold text-black">CMS ID:</label>
                <input
                    id="cms_id"
                    name="cms_id"
                    type="text"
                    required
                    class="p-2 w-full bg-[#ccc] border border-gray-300 focus:outline-none"
                />

                <label for="password" class="font-semibold text-black">Password:</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    required
                    class="p-2 w-full bg-[#ccc] border border-gray-300 focus:outline-none"
                />

                <button type="submit" class="bg-[#ccc] text-black font-semibold px-4 py-2 w-max self-center">
                    Log in
                </button>

                <p class="text-black text-sm text-center">
                    Don't have an account?
                    <a href="/DBMS/pages/signup.php" class="underline">Sign up</a>
                </p>
            </form>
        </div>
    </main>

    <!-- Footer -->
    <footer class="relative flex justify-center items-center py-4">
        <hr class="border-gray-400 w-full absolute top-1/2 left-0 -z-10" />
        <img
            src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png"
            alt="NUST logo with text Defining futures below it in white on blue background"
            class="relative z-10 w-20 h-20 object-contain"
            width="80"
            height="80"
        />
    </footer>
</body>
</html> 