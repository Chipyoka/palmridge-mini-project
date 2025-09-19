<?php
session_name('ADMINSESSID');
session_start();
require_once __DIR__ . '/../includes/db.php';

// Check if user is admin
if (!isset($_SESSION['user']) || (int)$_SESSION['user']['is_admin'] !== 1) {
    $_SESSION['error'] = "Unauthorized access. Admin privileges required.";
 header("Location: /odl_mini_projects/zambezi-mini-project/index.php");
    exit;
}

// Get property ID from URL parameter
$propertyId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($propertyId <= 0) {
    $_SESSION['error'] = "Invalid property ID.";
    header('Location: dashboard.php');
    exit;
}

try {
    // Begin transaction to ensure data consistency
    $conn->begin_transaction();
    
    // First, fetch all image paths for this property
    $getImages = $conn->prepare("SELECT image_path FROM images WHERE property_id = ?");
    $getImages->bind_param('i', $propertyId);
    $getImages->execute();
    $imageResult = $getImages->get_result();
    $imagesToDelete = [];
    
    while ($image = $imageResult->fetch_assoc()) {
        if (!empty($image['image_path'])) {
            $imagesToDelete[] = $image['image_path'];
        }
    }
    $getImages->close();
    
    // Delete bids for this property
    $deleteBids = $conn->prepare("DELETE FROM bids WHERE property_id = ?");
    $deleteBids->bind_param('i', $propertyId);
    $deleteBids->execute();
    $deleteBids->close();
    
    // Delete images for this property from database
    $deleteImages = $conn->prepare("DELETE FROM images WHERE property_id = ?");
    $deleteImages->bind_param('i', $propertyId);
    $deleteImages->execute();
    $deleteImages->close();
    
    // Now delete the property itself
    $deleteProperty = $conn->prepare("DELETE FROM properties WHERE id = ?");
    $deleteProperty->bind_param('i', $propertyId);
    $deleteProperty->execute();
    
    if ($deleteProperty->affected_rows > 0) {
        // Commit the transaction if all queries succeeded
        $conn->commit();
        
        // Delete the actual image files from server
        $deletedFiles = 0;
        $errors = [];
        
        foreach ($imagesToDelete as $imagePath) {
            $fullPath = __DIR__ . '/../' . $imagePath;
            
            // Check if file exists and is within the expected directory
            if (file_exists($fullPath) && strpos($fullPath, __DIR__ . '/../uploads/') === 0) {
                if (unlink($fullPath)) {
                    $deletedFiles++;
                } else {
                    $errors[] = "Could not delete file: " . basename($imagePath);
                }
            }
        }
        
        // Prepare success message with file deletion info
        $message = "Property deleted successfully.";
        if ($deletedFiles > 0) {
            $message .= " Removed {$deletedFiles} image file(s).";
        }
        if (!empty($errors)) {
            $message .= " " . implode(" ", $errors);
        }
        
        $_SESSION['success'] = $message;
    } else {
        // Rollback if no rows were affected (property didn't exist)
        $conn->rollback();
        $_SESSION['error'] = "Property not found or already deleted.";
    }
    
    $deleteProperty->close();
    
} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn) {
        $conn->rollback();
    }
    $_SESSION['error'] = "Error deleting property: " . $e->getMessage();
}

// Redirect back to dashboard
header('Location: dashboard.php');
exit;
?>