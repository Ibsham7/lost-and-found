<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';
requireLogin();

// Get item_id and claim_by from query
if (!isset($_GET['item_id'])) {
    header('Location: home.php');
    exit();
}
$item_id = $_GET['item_id'];
$claim_by = isset($_GET['claim_by']) ? $_GET['claim_by'] : null;

// Fetch pending claim for this item (and user if claim_by is set)
if ($claim_by) {
    $stmt = $pdo->prepare("SELECT c.*, u.username, u.email, u.phone, u.did, d.name as department_name 
                           FROM Claims c 
                           JOIN Users u ON c.claim_by = u.user_id 
                           LEFT JOIN Departments d ON u.did = d.department_id
                           WHERE c.item_id = ? AND c.claim_by = ? AND c.claim_status = 'pending' LIMIT 1");
    $stmt->execute([$item_id, $claim_by]);
} else {
    $stmt = $pdo->prepare("SELECT c.*, u.username, u.email, u.phone, u.did, d.name as department_name 
                           FROM Claims c 
                           JOIN Users u ON c.claim_by = u.user_id 
                           LEFT JOIN Departments d ON u.did = d.department_id
                           WHERE c.item_id = ? AND c.claim_status = 'pending' LIMIT 1");
    $stmt->execute([$item_id]);
}
$claim = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$claim) {
    $error = "No pending claim found for this item.";
}

// Approve claim
if (isset($_POST['approve']) && $claim) {
    $pdo->beginTransaction();
    try {
        // Approve claim
        $updateClaim = $pdo->prepare("UPDATE Claims SET claim_status = 'approved' WHERE claim_id = ?");
        $updateClaim->execute([$claim['claim_id']]);
        // Set item as claimed
        $updateItem = $pdo->prepare("UPDATE Items SET status = 'claimed' WHERE item_id = ?");
        $updateItem->execute([$item_id]);
        // Delete related notifications for this item and claim_by
        $deleteNotif = $pdo->prepare("DELETE FROM Notifications WHERE user_id = (SELECT user_id FROM Items WHERE item_id = ?) AND message LIKE ?");
        $deleteNotif->execute([$item_id, "%approve.php?item_id=$item_id&claim_by={$claim['claim_by']}%"]);
        $pdo->commit();
        header('Location: home.php');
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Failed to approve claim: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Lost and Found</title>
  <link rel="stylesheet" href="approve.css">
  <style>
    .popup {
      display: none;
      position: fixed;
      z-index: 100;
      left: 0; top: 0; width: 100vw; height: 100vh;
      background: rgba(0,0,0,0.4);
      justify-content: center; align-items: center;
    }
    .popup-content {
      background: #fff; padding: 30px; border-radius: 8px; min-width: 300px; text-align: center;
    }
    .popup-content h3 { margin-top: 0; }
    .close-btn { margin-top: 20px; background: #8a2c14; color: #fff; border: none; padding: 8px 20px; cursor: pointer; }
  </style>
</head>
<body>
  <header>
    <img src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png" class="logo" alt="NUST Logo">
    <div class="title-section">
      <h1>Lost and Found</h1>
      <nav>
        <a href="home.php">Home</a> | 
        <a href="upload.php">Upload</a> | 
        <a href="uploads.php">View Items</a>
      </nav>
    </div>
  </header>
  <main>
    <div class="claim-box">
      <h2>Claim Request</h2>
      <?php if(isset($error)): ?>
        <div class="readonly-field proof" style="color:#a00; text-align:center;"> <?php echo htmlspecialchars($error); ?> </div>
      <?php elseif($claim): ?>
      <div class="form-group">
        <label>Name :</label>
        <div class="readonly-field"><?php echo htmlspecialchars($claim['username']); ?></div>
        <label>School / DEPT :</label>
        <div class="readonly-field"><?php echo !empty($claim['department_name']) ? htmlspecialchars($claim['department_name']) : 'Not assigned'; ?></div>
        <label>Proof (if any) :</label>
        <div class="readonly-field proof"><?php echo nl2br(htmlspecialchars($claim['proof_of_ownership'])); ?></div>
      </div>
    </div>
    <form method="post" class="buttons" style="display: flex; gap: 20px; justify-content: center;">
      <button type="button" class="contact" onclick="showPopup()">Contact claimer</button>
      <button type="submit" name="approve" class="confirm">Confirm Claim</button>
    </form>
    <!-- Popup for contact info -->
    <div class="popup" id="contactPopup">
      <div class="popup-content">
        <h3>Claimer Contact Info</h3>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($claim['email']); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($claim['phone']); ?></p>
        <button class="close-btn" onclick="hidePopup()">Close</button>
      </div>
    </div>
    <?php endif; ?>
    <div class="footer-logo">
      <img src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png" alt="NUST Logo">
      <p>NUST<br><span>Defining futures</span></p>
    </div>
  </main>
  <script>
    function showPopup() {
      document.getElementById('contactPopup').style.display = 'flex';
    }
    function hidePopup() {
      document.getElementById('contactPopup').style.display = 'none';
    }
  </script>
</body>
</html> 