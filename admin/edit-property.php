<?php
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../includes/db.php';

// Only admins/staff may access
if (!isset($_SESSION['user']) || (int)$_SESSION['user']['is_admin'] !== 1) {
    header('Location: login.php');
    exit;
}

$propId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($propId <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Fetch current property data
$stmt = $conn->prepare("SELECT * FROM properties WHERE id = ?");
$stmt->bind_param('i', $propId);
$stmt->execute();
$property = $stmt->get_result()->fetch_assoc();
if (!$property) {
    echo "Property not found.";
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price       = (float)$_POST['price'];
    $rooms       = (int)$_POST['rooms'];
    $location    = trim($_POST['location']);
    $category    = trim($_POST['category']);
    $property_type = trim($_POST['property_type']);
    $action      = $_POST['action'];
    $status      = $_POST['status'];
    $is_furnished  = (int)($_POST['is_furnished'] ?? 0);

    $update = $conn->prepare("
        UPDATE properties
        SET title=?, description=?, price=?, rooms=?, location=?, category=?, property_type=?, action=?, status=?, is_furnished=?
        WHERE id=?
    ");
    $update->bind_param(
        'ssdisssssii',
        $title, $description, $price, $rooms, $location,
        $category, $property_type, $action, $status,
        $is_furnished, $propId
    );
    $update->execute();

    // Redirect back to view-property page
    header("Location: view-property.php?id={$propId}");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Property – <?= htmlspecialchars($property['title']); ?></title>
<link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<header class="top-bar">
    <div class="logo"><h1> Edit Property</h1></div>
    <div class="user-actions">
        <a class="logout"  href="view-property.php?id=<?= $propId ?>" >Return</a>
    </div>
</header>

<main class="edit-property">
    <form method="post" class="property-form">
        <label>Title
            <input type="text" name="title" value="<?= htmlspecialchars($property['title']); ?>" required>
        </label>

        <label>Description
            <textarea name="description" rows="5" required><?= htmlspecialchars($property['description']); ?></textarea>
        </label>

        <hr>
        <div class="row-3 mt-3">

            <label>Price
                <input type="number" name="price" step="0.01" value="<?= htmlspecialchars($property['price']); ?>" required>
            </label>
    
            <label>Rooms
                <input type="number" name="rooms" value="<?= (int)$property['rooms']; ?>" min="0">
            </label>
        </div>

        
        
        <div class="row-3 ">

            <label>Location
                <input type="text" name="location" value="<?= htmlspecialchars($property['location']); ?>">
            </label>
    
            <label>Category
                <input type="text" name="category" value="<?= htmlspecialchars($property['category']); ?>">
            </label>
        </div>

        

            <label>Property Type
                <input type="text" name="property_type" value="<?= htmlspecialchars($property['property_type']); ?>">
            </label>
    
            
       

        <hr>

       <div class="mt-3"></div>
            <label >Action
                <select name="action">
                    <option value="sale"  <?= $property['action']==='sale'?'selected':''; ?>>For Sale</option>
                    <option value="rent"  <?= $property['action']==='rent'?'selected':''; ?>>For Rent</option>
                </select>
            </label>
        
        <div class="row">


            <label>Status
                <select name="status">
                    <option value="available" <?= $property['status']==='available'?'selected':''; ?>>Available</option>
                    <option value="sold"      <?= $property['status']==='sold'?'selected':''; ?>>Sold</option>
                    <option value="rented"    <?= $property['status']==='rented'?'selected':''; ?>>Rented</option>
                </select>
            </label>

            <label for="is_furnished">Furnished
                <select name="is_furnished" >
                    <option value="1" <?= $property['is_furnished'] ? 'selected' : ''; ?>>Yes</option>
                    <option value="0" <?= !$property['is_furnished'] ? 'selected' : ''; ?>>No</option>
                </select>
            </label>
        </div>

        <button class="primary-btn w-full mt-2" type="submit">Save Changes</button>
    </form>
</main>

</body>
</html>
