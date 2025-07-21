<?php
session_start();
require_once '../includes/config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $location = $_POST['location'];
    $status = $_POST['status'];
    $date_found = $_POST['date_found'];
    $department_id = $_POST['department_id'];
    $category_id = $_POST['category_id'];
    $user_id = $_SESSION['user_id']; // Assuming user is logged in and session is started

    try {
        $stmt = $pdo->prepare("INSERT INTO Items (title, description, location, status, date_found, user_id, department_id, category_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $location, $status, $date_found, $user_id, $department_id, $category_id]);
        $item_id = $pdo->lastInsertId();

        // Handle multiple image uploads
        if (isset($_FILES['images'])) {
            $upload_dir = '../uploads/item_images/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    $file_name = uniqid('item_', true) . '_' . basename($_FILES['images']['name'][$key]);
                    $target_path = $upload_dir . $file_name;
                    if (move_uploaded_file($tmp_name, $target_path)) {
                        $image_url = 'uploads/item_images/' . $file_name;
                        $stmt = $pdo->prepare("INSERT INTO ItemImages (item_id, image_url) VALUES (?, ?)");
                        $stmt->execute([$item_id, $image_url]);
                    }
                }
            }
        }
        header("Location: home.php");
        exit();
    } catch(PDOException $e) {
        $error = "Upload failed: " . $e->getMessage();
        echo $error; // Display the error message
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Lost and Found</title>
  <link rel="stylesheet" href="upload.css" />
</head>
<body>
  <!-- Header -->
  <header class="header">
    <div class="header-left">
      <img src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png" alt="NUST Logo" class="logo" />
      <h1 class="title">Lost and Found</h1>
    </div>
    <nav class="nav-links">
      <a href="#">lose it</a> |
      <a href="#">list it</a> |
      <a href="#">find it</a>
    </nav>
  </header>

  <!-- Main Content -->
  <main class="main">
    <!-- Upload Box -->
    <div class="upload-section">
      <div id="preview-container" class="upload-box">
        <p>Image preview will appear here</p>
      </div>
      <button type="button" id="upload-btn">Upload Picture</button>
    </div>
    
    <form class="form-box" method="POST" action="" enctype="multipart/form-data">
      <input type="file" id="image-input" name="images[]" accept="image/*" multiple hidden />
      
      <label for="title">Title:</label>
      <input type="text" id="title" name="title" required>

      <label for="description">Item Description:</label>
      <textarea id="description" name="description" rows="4" placeholder="Describe the item..." required></textarea>

      <label for="location">Location:</label>
      <input id="location" name="location" type="text" placeholder="Enter location" required />

      <label for="status">Status:</label>
      <select id="status" name="status" required>
        <option value="lost">Lost</option>
        <option value="found">Found</option>
      </select>

      <label for="date_found">Date Found:</label>
      <input id="date_found" name="date_found" type="date" required max="<?php echo date('Y-m-d'); ?>" />

      <label for="department_id">Department:</label>
        <select id="department_id" name="department_id" required>
  <option value="1">School of Electrical Engineering and Computer Science (SEECS)</option>
  <option value="2">School of Mechanical and Manufacturing Engineering (SMME)</option>
  <option value="3">School of Social Sciences and Humanities (S3H)</option>
  <option value="4">School of Natural Sciences (SNS)</option>
  <option value="5">NUST Business School (NBS)</option>
  <option value="6">School of Art, Design and Architecture (SADA)</option>
  <option value="7">College of Aeronautical Engineering (CAOE)</option>
  <option value="8">School of Chemical and Materials Engineering (SCME)</option>
  <option value="9">Institute of Environmental Sciences and Engineering (IESE)</option>
  <option value="10">Institute of Geographical Information Systems (IGIS)</option>
  <option value="11">US-Pak Center for Advanced Studies in Energy (USPCAS-E)</option>
</select>


      <label for="category_id">Category:</label>
      <select id="category_id" name="category_id" required>
        <option value="1">Electronics</option>
        <option value="2">Clothing & Accessories</option>
        <option value="3">Documents (ID cards, licenses, etc.)</option>
        <option value="4">Bags & Backpacks</option>
        <option value="5">Stationery & Books</option>
        <option value="6">Keys</option>
        <option value="7">Wallets & Purses</option>
        <option value="8">Jewelry & Watches</option>
        <option value="9">Other</option>
      </select>

      <button type="submit">Submit</button>
    </form>
  </main>

  <!-- Footer -->
  <footer class="footer">
    <img src="https://crystalpng.com/wp-content/uploads/2022/02/national-university-logo.png" alt="NUST Logo" />
  </footer>

  <script>
    const uploadBtn = document.getElementById("upload-btn");
    const imageInput = document.getElementById("image-input");
    const previewContainer = document.getElementById("preview-container");
  
    uploadBtn.addEventListener("click", () => {
      imageInput.click();
    });
  
    imageInput.addEventListener("change", () => {
      previewContainer.innerHTML = ""; // Clear old previews
  
      Array.from(imageInput.files).forEach(file => {
        const reader = new FileReader();
  
        reader.onload = function (e) {
          const img = document.createElement("img");
          img.src = e.target.result;
          previewContainer.appendChild(img);
        };
  
        reader.readAsDataURL(file);
      });
    });
  </script>
</body>
</html>
