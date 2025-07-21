<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;

// Fetch user data for profile display
$user_data = null;
if ($user_id) {
    $userStmt = $pdo->prepare("SELECT username, user_id, email, phone, profile_pic, d.name as department_name 
                              FROM Users u 
                              LEFT JOIN Departments d ON u.did = d.department_id 
                              WHERE user_id = ?");
    $userStmt->execute([$user_id]);
    $user_data = $userStmt->fetch(PDO::FETCH_ASSOC);
}

$notifications = [];
if ($user_id) {
    $stmt = $pdo->prepare("SELECT * FROM Notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lost and Found</title>
  <link rel="stylesheet" href="home.css" />
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
  <style>
    /* User Profile Styles */
    .user-profile {
      position: absolute;
      top: 20px;
      right: 200px;
      display: flex;
      align-items: center;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 50px;
      padding: 5px 15px 5px 5px;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
      z-index: 100;
    }
    
    .profile-pic {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #1d3e6a;
      margin-right: 10px;
    }
    
    .profile-info {
      display: flex;
      flex-direction: column;
    }
    
    .profile-name {
      font-weight: bold;
      color: #1d3e6a;
      font-size: 14px;
      margin: 0;
    }
    
    .profile-cms {
      color: #555;
      font-size: 12px;
      margin: 0;
    }
    
    .profile-dropdown {
      display: none;
      position: absolute;
      top: 55px;
      right: 0;
      background: white;
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
      width: 200px;
      border-radius: 5px;
      z-index: 101;
    }
    
    .profile-dropdown a {
      display: block;
      padding: 10px 15px;
      text-decoration: none;
      color: #333;
      border-bottom: 1px solid #eee;
    }
    
    .profile-dropdown a:hover {
      background-color: #f5f5f5;
    }
    
    .user-profile:hover .profile-dropdown {
      display: block;
    }

    .user-profile-text {
      position: absolute;
      top: 30px;
      right: 60px;
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      background: rgba(255,255,255,0.95);
      border-radius: 8px;
      padding: 8px 18px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.08);
      font-size: 15px;
      z-index: 101;
    }
    .profile-name-text {
      font-weight: bold;
      color: #1d3e6a;
      margin-bottom: 2px;
    }
    .profile-cms-text {
      color: #555;
      font-size: 13px;
    }
  </style>
</head>
<body>
  <!-- Top Header -->
  <header class="top-bar">
    <img src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png" alt="NUST Logo" class="logo" />
    <div class="header-center">
      <h1>Lost and Found</h1>
      <nav>
        <a href="#">lose it</a> |
        <a href="#">list it</a> |
        <a href="#">find it</a>
      </nav>
    </div>
    
    <!-- User Profile Section -->
    <?php /* User profile display removed as per request */ ?>
    <!-- End User Profile Section -->
  </header>

  <!-- Notification Button -->
  <div class="notification-container">
    <button class="notification-button" onclick="toggleDropdown()">
      <img src="https://cdn0.iconfinder.com/data/icons/social-messaging-ui-color-shapes/128/notification-circle-blue-512.png" alt="Notifications">
    </button>
    <div id="notification-dropdown" class="dropdown-content">
      <?php if (!empty($notifications)): ?>
        <?php foreach ($notifications as $notif): ?>
          <div class="notification-item" style="padding: 10px; border-bottom: 1px solid #eee; background: #dc9735; color: #222;">
            <?php echo $notif['message']; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No new notifications</p>
      <?php endif; ?>
    </div>
  </div>

  <!-- Main Buttons Section -->
  <div class="button-container">
    <div class="button-wrapper">
      <form action="upload.php" method="get">
        <button class="icon-button" type="submit">
          <img src="https://img.icons8.com/ios-filled/100/upload.png" alt="Upload Icon">
        </button>
      </form>
      <div class="label">
        <strong>UPLOAD</strong><br><em>LOST ITEM</em>
      </div>
    </div>

    <div class="button-wrapper">
      <form action="uploads.php" method="get">
        <button class="icon-button" type="submit">
          <img src="https://img.icons8.com/ios-filled/100/search--v1.png" alt="Search Icon">
        </button>
      </form>
      <div class="label">
        <strong>SEARCH</strong><br><em>LOST ITEM</em>
      </div>
    </div>

    <div class="button-wrapper">
      <form action="recent.php" method="get">
        <button class="icon-button" type="submit">
          <img src="https://img.icons8.com/ios-filled/100/clock--v1.png" alt="View Icon">
        </button>
      </form>
      <div class="label">
        <strong>VIEW</strong><br><em>Recent Claims</em>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    <img src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png" alt="NUST Logo Small" />
    <p>NUST - Defining futures</p>
  </footer>

  <script>
    function toggleDropdown() {
      const dropdown = document.getElementById("notification-dropdown");
      dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
    }

    window.onclick = function(event) {
      if (!event.target.closest('.notification-container') && !event.target.closest('.user-profile')) {
        document.getElementById("notification-dropdown").style.display = "none";
      }
    }
  </script>
</body>
</html>
