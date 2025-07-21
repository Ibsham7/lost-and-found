<?php
require_once '../includes/config.php';

try {
    $stmt = $pdo->query("SELECT i.*, GROUP_CONCAT(ii.image_url) as image_urls FROM Items i LEFT JOIN ItemImages ii ON i.item_id = ii.item_id WHERE i.status != 'claimed' GROUP BY i.item_id");
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo "Error fetching items: " . $e->getMessage();
    $items = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Lost and Found</title>
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background-color: #1e3b63;
      color: #000;
    }

    header {
      background-color: #d6d6d6;
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 40px;
    }

    header img {
      height: 80px;
    }

    header h1 {
      font-size: 36px;
      font-weight: bold;
      margin: 0;
    }

    nav {
      font-size: 16px;
    }

    nav a {
      color: black;
      margin-left: 10px;
      text-decoration: none;
      font-weight: 500;
    }

    .grid-container {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 30px;
      padding: 40px;
      max-width: 1200px;
      margin: auto;
    }

    .item-card {
      background-color: #f7e4d7;
      border: 2px solid #ccc;
      padding: 0;
      text-align: center;
      border-radius: 6px;
      overflow: hidden;
    }

    .item-image {
      position: relative;
      background-color: white;
      height: 200px;
      overflow: hidden;
    }

    .item-image img {
      width: 100%;
      height: 100%;
      object-fit: contain;
      background-color: white;
    }

    .image-counter {
      position: absolute;
      bottom: 5px;
      right: 5px;
      background: rgba(0, 0, 0, 0.5);
      color: white;
      padding: 2px 6px;
      border-radius: 10px;
      font-size: 12px;
    }

    .item-details {
      padding: 10px;
      text-align: left;
    }

    .item-details p {
      margin: 5px 0;
    }

    .claim-button {
      display: block;
      background-color: #842c17;
      color: white;
      padding: 10px;
      font-weight: bold;
      text-align: center;
      border: none;
      cursor: pointer;
      text-decoration: none;
    }

    .claim-button:hover {
      background-color: #6a2312;
    }

    .claim-button:disabled {
      background-color: #666;
      cursor: not-allowed;
    }

    footer {
      text-align: center;
      padding: 20px;
      background-color: #153055;
    }

    footer img {
      height: 50px;
    }
  </style>
</head>
<body>

  <header>
    <img src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png" alt="NUST Logo">
    <h1>Lost and Found</h1>
    <nav>
      <a href="home.php">Home</a> |
      <a href="upload.php">Upload Item</a> |
      <a href="uploads.php">View Items</a>
    </nav>
  </header>

  <section class="grid-container">
    <?php foreach ($items as $item): ?>
      <div class="item-card">
        <div class="item-image">
          <?php 
          $images = $item['image_urls'] ? explode(',', $item['image_urls']) : [];
          if (!empty($images)): 
          ?>
            <img src="<?php echo htmlspecialchars('../' . $images[0]); ?>" alt="Item Image">
            <?php if (count($images) > 1): ?>
              <div class="image-counter">+<?php echo count($images) - 1; ?></div>
            <?php endif; ?>
          <?php else: ?>
            <img src="https://via.placeholder.com/300x200.png?text=No+Image" alt="No image available">
          <?php endif; ?>
        </div>
        <div class="item-details">
          <p><strong>Title:</strong> <?php echo htmlspecialchars($item['title']); ?></p>
          <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
          <p><strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
          <p><strong>Status:</strong> <?php echo htmlspecialchars($item['status']); ?></p>
        </div>
        <?php if ($item['status'] !== 'claimed'): ?>
          <a href="claim-item.php?item_id=<?php echo htmlspecialchars($item['item_id']); ?>" class="claim-button">CLAIM ITEM</a>
        <?php else: ?>
          <button class="claim-button" disabled>ITEM CLAIMED</button>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </section>

  <footer>
    <img src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png" alt="NUST Logo">
  </footer>

</body>
</html>
