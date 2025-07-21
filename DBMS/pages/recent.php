<?php
require_once '../includes/config.php';

// Fetch recent approved claims with item and user info
$stmt = $pdo->query("SELECT c.*, i.title, i.description, i.location, GROUP_CONCAT(ii.image_url) as image_urls, u.username, u.email FROM Claims c JOIN Items i ON c.item_id = i.item_id LEFT JOIN ItemImages ii ON i.item_id = ii.item_id JOIN Users u ON c.claim_by = u.user_id WHERE c.claim_status = 'approved' GROUP BY c.claim_id ORDER BY c.updated_at DESC LIMIT 10");
$claims = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Lost and Found - Cards</title>
  <link rel="stylesheet" href="recent.css">
</head>
<body>
  <div class="header">
    <img src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png" alt="NUST Logo" class="logo">
    <div class="title-bar">
      <h1>Lost and Found</h1>
      <div class="nav-links">lose it | list it | find it</div>
    </div>
  </div>

  <div class="card-container">
    <?php foreach ($claims as $claim): ?>
      <div class="card">
        <div class="image-box">
          <?php 
            $images = $claim['image_urls'] ? explode(',', $claim['image_urls']) : [];
            if (!empty($images)) {
              echo '<img src="' . htmlspecialchars('../' . $images[0]) . '" alt="Item Image" style="max-width:100%;max-height:100%;object-fit:contain;">';
            } else {
              echo 'picture';
            }
          ?>
        </div>
        <div class="details">
          <p><strong>description:</strong> <?php echo htmlspecialchars($claim['description']); ?></p>
          <p><strong>location:</strong> <?php echo htmlspecialchars($claim['location']); ?></p>
          <p><strong>contact:</strong> <?php echo htmlspecialchars($claim['email']); ?></p>
          <p><strong>claimed by:</strong> <?php echo htmlspecialchars($claim['username']); ?></p>
        </div>
      </div>
    <?php endforeach; ?>
    <?php if (empty($claims)): ?>
      <div style="color:white;text-align:center;width:100%;">No recent claims found.</div>
    <?php endif; ?>
  </div>

  <div class="footer">
    <img src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png" alt="NUST Footer Logo" class="footer-logo">
  </div>
</body>
</html> 