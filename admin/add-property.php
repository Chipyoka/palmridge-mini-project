<?php
// admin/add-property.php
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../includes/db.php';

// RBAC check
if (!isset($_SESSION['user']) || (int)$_SESSION['user']['is_admin'] !== 1) {
    header('Location: /admin-login.php');
    exit;
}

$user = $_SESSION['user'];
$errors   = [];
$success  = "";

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Validate & sanitize ---
    $title         = trim($_POST['title'] ?? '');
    $description   = trim($_POST['description'] ?? '');
    $price         = isset($_POST['price']) ? (float)$_POST['price'] : 0;
    $rooms         = isset($_POST['rooms']) ? (int)$_POST['rooms'] : 0;
    $location      = trim($_POST['location'] ?? '');
    $category      = trim($_POST['category'] ?? '');
    $property_type = trim($_POST['property_type'] ?? '');
    $action        = in_array($_POST['action'] ?? '', ['sale','rent']) ? $_POST['action'] : 'sale';
    $status        = in_array($_POST['status'] ?? '', ['available','sold','rented']) ? $_POST['status'] : 'available';
    $is_furnished  = (int)($_POST['is_furnished'] ?? 0);

    if ($title === '' || $description === '' || $price <= 0) {
        $errors[] = "Title, description and positive price are required.";
    }

    // --- Image upload handling ---
    $imagePath = 'assets/images/default-property.jpg'; // default fallback
    if (!empty($_FILES['photo']['name'])) {
        $allowedExt = ['jpg','jpeg','png'];
        $maxSize    = 2 * 1024 * 1024; // 2 MB
        $uploadDir  = __DIR__ . '/../uploads/';

        if (!is_dir($uploadDir) && !mkdir($uploadDir, 0777, true)) {
            $errors[] = "Failed to create upload directory.";
        } else {
            $fileName  = basename($_FILES['photo']['name']);
            $ext       = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newName   = uniqid('prop_', true) . '.' . $ext;
            $target    = $uploadDir . $newName;

            if (!in_array($ext, $allowedExt)) {
                $errors[] = "Invalid image type. Allowed: " . implode(', ', $allowedExt);
            } elseif ($_FILES['photo']['size'] > $maxSize) {
                $errors[] = "Image exceeds maximum size of 2MB.";
            } elseif (!move_uploaded_file($_FILES['photo']['tmp_name'], $target)) {
                $errors[] = "Image upload failed.";
            } else {
                // Relative path stored in DB (e.g., 'uploads/prop_abc.jpg')
                $imagePath = 'uploads/' . $newName;
            }
        }
    }

    // --- Insert into DB if no errors ---
    if (!$errors) {
        $sql = "
            INSERT INTO properties
            (title, description, price, rooms, location, category,
             property_type, action, status, is_furnished, added_by, agent_id)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $errors[] = "DB prepare failed: " . $conn->error;
        } else {
            $stmt->bind_param(
                'ssdisssssiii',
                $title,
                $description,
                $price,
                $rooms,
                $location,
                $category,
                $property_type,
                $action,
                $status,
                $is_furnished,
                $user['id'],
                $user['id']
            );
            if (!$stmt->execute()) {
                $errors[] = "DB insert failed: " . $stmt->error;
            } else {
                $newPropId = $stmt->insert_id;

                // Store image path in images table
                $imgStmt = $conn->prepare("INSERT INTO images (property_id, image_path) VALUES (?, ?)");
                if ($imgStmt) {
                    $imgStmt->bind_param('is', $newPropId, $imagePath);
                    $imgStmt->execute();
                    $imgStmt->close();
                }

                $success = "Property successfully added.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Add Property – Zambezi Diamond ARPLSS</title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
<header class="top-bar">
    <div class="logo"><h1>Add New Property</h1></div>
    <div class="user-actions">
        <a href="dashboard.php" class="logout">Return</a>
    </div>
</header>

<main class=" add-property">
    <?php if ($errors): ?>
        <div class="error">
            <ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
        </div>
    <?php elseif ($success): ?>
        <div class="alert success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="property-form" novalidate>
        <label>Photo (max 2 MB)
            <input type="file" name="photo" accept="image/*">
        </label>

        <label>Title
            <input type="text" name="title" required>
        </label>

        <label>Description
            <textarea name="description" rows="3" required></textarea>
        </label>

        <hr>

        <div class="row-3 mt-3">
            <label>Price
                <input type="number" name="price" step="0.01" required>
            </label>
            <label>Rooms
                <input type="number" name="rooms" min="0">
            </label>
        </div>
        
        <div class="row-3">
            <label>Location
                <input type="text" name="location">
            </label>
            <label>Category
                <input type="text" name="category">
            </label>
           
          
        </div>

         <label>Property Type
                <input type="text" name="property_type">
        </label>

        <hr>

       <div class="mt-3"></div>

              <label>Action
                <select name="action">
                    <option value="sale">For Sale</option>
                    <option value="rent">For Rent</option>
                </select>
            </label>

        <div class="row-3">
            <label>Status
                <select name="status">
                    <option value="available">Available</option>
                    <option value="sold">Sold</option>
                    <option value="rented">Rented</option>
                </select>
            </label>
            <label>Furnished
                <select name="is_furnished">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </label>
         
        </div>

        <button class="primary-btn w-full mt-2" type="submit">Add Property</button>
    </form>
</main>
</body>
</html>
