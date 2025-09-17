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

                // Store success message in session and redirect to prevent resubmission
                $_SESSION['form_success'] = "Property successfully added.";
                $_SESSION['form_data'] = []; // Clear any stored form data
                
                // Redirect to same page using GET method (PRG pattern)
                header('Location: ' . $_SERVER['PHP_SELF']);
                exit;
            }
            $stmt->close();
        }
    }
    
    // If we have errors, store form data to repopulate fields
    if ($errors) {
        $_SESSION['form_data'] = [
            'title' => $title,
            'description' => $description,
            'price' => $price,
            'rooms' => $rooms,
            'location' => $location,
            'category' => $category,
            'property_type' => $property_type,
            'action' => $action,
            'status' => $status,
            'is_furnished' => $is_furnished
        ];
    }
}

// Check for success message from session (after redirect)
if (isset($_SESSION['form_success'])) {
    $success = $_SESSION['form_success'];
    unset($_SESSION['form_success']);
}

// Check for stored form data (for repopulating after errors)
$formData = $_SESSION['form_data'] ?? [];
if (isset($_SESSION['form_data'])) {
    unset($_SESSION['form_data']);
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

<main class="add-property">
    <?php if ($errors): ?>
        <div id="alertBox2" class="alert-error">
            <ul><?php foreach ($errors as $e) echo "<li>".htmlspecialchars($e)."</li>"; ?></ul>
        </div>
    <?php elseif ($success): ?>
        <div id="alertBox2" class="alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="property-form mt-2" novalidate>
        <label>Photo (max 2 MB)
            <input type="file" name="photo" accept="image/*">
        </label>

        <label>Title
            <input type="text" name="title" value="<?= htmlspecialchars($formData['title'] ?? '') ?>" required>
        </label>

        <label>Description
            <textarea name="description" rows="3" required><?= htmlspecialchars($formData['description'] ?? '') ?></textarea>
        </label>

        <hr>

        <div class="row-3 mt-3">
            <label>Price
                <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($formData['price'] ?? '') ?>" required>
            </label>
            <label>Rooms
                <input type="number" name="rooms" min="0" value="<?= htmlspecialchars($formData['rooms'] ?? '') ?>">
            </label>
        </div>
        
        <div class="row-3">
            <label>Location
                <input type="text" name="location" value="<?= htmlspecialchars($formData['location'] ?? '') ?>">
            </label>
            <label>Category
                <input type="text" name="category" value="<?= htmlspecialchars($formData['category'] ?? '') ?>">
            </label>
        </div>

        <label>Property Type
            <input type="text" name="property_type" value="<?= htmlspecialchars($formData['property_type'] ?? '') ?>">
        </label>

        <hr>

        <div class="mt-3"></div>

        <label>Action
            <select name="action">
                <option value="sale" <?= ($formData['action'] ?? 'sale') === 'sale' ? 'selected' : '' ?>>For Sale</option>
                <option value="rent" <?= ($formData['action'] ?? 'sale') === 'rent' ? 'selected' : '' ?>>For Rent</option>
            </select>
        </label>

        <div class="row-3">
            <label>Status
                <select name="status">
                    <option value="available" <?= ($formData['status'] ?? 'available') === 'available' ? 'selected' : '' ?>>Available</option>
                    <option value="sold" <?= ($formData['status'] ?? 'available') === 'sold' ? 'selected' : '' ?>>Sold</option>
                    <option value="rented" <?= ($formData['status'] ?? 'available') === 'rented' ? 'selected' : '' ?>>Rented</option>
                </select>
            </label>
            <label>Furnished
                <select name="is_furnished">
                    <option value="1" <?= ($formData['is_furnished'] ?? 0) == 1 ? 'selected' : '' ?>>Yes</option>
                    <option value="0" <?= ($formData['is_furnished'] ?? 0) == 0 ? 'selected' : '' ?>>No</option>
                </select>
            </label>
        </div>

        <button class="primary-btn w-full mt-2" type="submit">Add Property</button>
    </form>
</main>

<!-- Add main.js -->
<script src="/assets/js/main.js"></script>
</body>
</html>