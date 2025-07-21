<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Require user to be logged in
requireLogin();

// Check if item_id is provided
if (!isset($_GET['item_id'])) {
    header('Location: home.php');
    exit();
}

$item_id = $_GET['item_id'];

// Fetch item details
try {
    $stmt = $pdo->prepare("SELECT i.*, GROUP_CONCAT(ii.image_url) as image_urls 
                          FROM Items i 
                          LEFT JOIN ItemImages ii ON i.item_id = ii.item_id 
                          WHERE i.item_id = ? 
                          GROUP BY i.item_id");
    $stmt->execute([$item_id]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        header('Location: home.php');
        exit();
    }

    // Get all images for this item
    $images = [];
    if ($item['image_urls']) {
        $images = explode(',', $item['image_urls']);
    }
} catch(PDOException $e) {
    echo "Error fetching item: " . $e->getMessage();
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get the logged-in user's ID from session
        $user_id = $_SESSION['user_id'];

        // Check if user has already claimed this item
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM Claims WHERE item_id = ? AND claim_by = ?");
        $checkStmt->execute([$item_id, $user_id]);
        if ($checkStmt->fetchColumn() > 0) {
            $error = "You have already claimed this item.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO Claims (item_id, claim_by, proof_of_ownership, claim_status, claim_date) 
                                  VALUES (?, ?, ?, 'pending', CURDATE())");
            $stmt->execute([
                $item_id,
                $user_id,
                $_POST['proof']
            ]);
            
            // Notify item owner
            $itemOwnerStmt = $pdo->prepare("SELECT user_id FROM Items WHERE item_id = ?");
            $itemOwnerStmt->execute([$item_id]);
            $itemOwnerId = $itemOwnerStmt->fetchColumn();
            $notificationStmt = $pdo->prepare("INSERT INTO Notifications (user_id, message) VALUES (?, ?)");
            $notificationStmt->execute([
                $itemOwnerId,
                "User <b>" . htmlspecialchars($_SESSION['username']) . "</b> has requested to claim your item. <a href='approve.php?item_id=$item_id&claim_by=$user_id'>View Claim</a>"
            ]);
            
            header('Location: home.php');
            exit();
        }
    } catch(PDOException $e) {
        $error = "Error submitting claim: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Claim Item - Lost and Found</title>
    <link rel="stylesheet" href="claim-item.css">
    <style>
        .image-carousel {
            position: relative;
            width: 100%;
            height: 300px;
            overflow: hidden;
        }

        .carousel-container {
            display: flex;
            transition: transform 0.3s ease-in-out;
            height: 100%;
        }

        .carousel-image {
            min-width: 100%;
            height: 100%;
            object-fit: contain;
            background-color: white;
        }

        .carousel-button {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 10px 15px;
            cursor: pointer;
            z-index: 2;
        }

        .carousel-button:hover {
            background: rgba(0, 0, 0, 0.7);
        }

        .prev-button {
            left: 10px;
        }

        .next-button {
            right: 10px;
        }

        .image-counter {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.5);
            color: white;
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 14px;
        }

        .error {
            color: #842c17;
            background-color: #f7e4d7;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            text-align: center;
        }
    </style>
</head>
<body>
    <header>
        <img src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png" alt="NUST Logo" class="logo">
        <h1>Lost and Found</h1>
        <nav>
            <a href="home.php">Home</a> | 
            <a href="upload.php">Upload Item</a> | 
            <a href="uploads.php">View Items</a>
        </nav>
    </header>

    <main>
        <div class="left-box">
            <div class="image-carousel">
                <div class="carousel-container">
                    <?php if (!empty($images)): ?>
                        <?php foreach ($images as $image): ?>
                            <img src="<?php echo htmlspecialchars('../' . $image); ?>" alt="Item Image" class="carousel-image">
                        <?php endforeach; ?>
                    <?php else: ?>
                        <img src="https://via.placeholder.com/300x200.png?text=No+Image" alt="No image available" class="carousel-image">
                    <?php endif; ?>
                </div>
                <?php if (count($images) > 1): ?>
                    <button class="carousel-button prev-button" onclick="prevImage()">❮</button>
                    <button class="carousel-button next-button" onclick="nextImage()">❯</button>
                    <div class="image-counter">1 / <?php echo count($images); ?></div>
                <?php endif; ?>
            </div>
            <p><strong>Title:</strong> <?php echo htmlspecialchars($item['title']); ?></p>
            <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($item['location']); ?></p>
            <p><strong>Status:</strong> <?php echo htmlspecialchars($item['status']); ?></p>
        </div>

        <div class="right-box">
            <form method="POST" action="">
                <h2>Claim Form</h2>
                <?php if (isset($error)): ?>
                    <div class="error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <label>Proof of Ownership:</label>
                <textarea name="proof" placeholder="Please provide any proof or additional information that can help verify your claim" required></textarea>
                
                <button type="submit">Submit Claim</button>
            </form>
        </div>
    </main>

    <footer>
        <img src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png" alt="NUST Logo" class="footer-logo">
    </footer>

    <script>
        let currentImageIndex = 0;
        const images = document.querySelectorAll('.carousel-image');
        const container = document.querySelector('.carousel-container');
        const counter = document.querySelector('.image-counter');

        function updateCarousel() {
            container.style.transform = `translateX(-${currentImageIndex * 100}%)`;
            if (counter) {
                counter.textContent = `${currentImageIndex + 1} / ${images.length}`;
            }
        }

        function nextImage() {
            currentImageIndex = (currentImageIndex + 1) % images.length;
            updateCarousel();
        }

        function prevImage() {
            currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
            updateCarousel();
        }
    </script>
</body>
</html> 