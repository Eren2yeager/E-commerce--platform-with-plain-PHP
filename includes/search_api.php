<?php
// API endpoint for AJAX search
header('Content-Type: application/json');
require_once('../config/database.php');

// Get search term from query string
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Return empty if search is too short
if (strlen($search) < 2) {
    echo json_encode([
        'success' => false,
        'message' => 'Search term too short',
        'products' => []
    ]);
    exit;
}

// Build search query
$sql = "SELECT id, name, price, sale_price, image, category, stock 
        FROM products 
        WHERE (name LIKE ? OR description LIKE ? OR brand LIKE ?)
        AND stock > 0
        ORDER BY name ASC
        LIMIT 8";

$search_term = "%$search%";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sss", $search_term, $search_term, $search_term);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Build products array
$products = [];
while ($row = mysqli_fetch_assoc($result)) {
    $isOnSale = !empty($row['sale_price']) && $row['sale_price'] > 0 && $row['sale_price'] < $row['price'];
    $currentPrice = $isOnSale ? $row['sale_price'] : $row['price'];
    
    $products[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'price' => number_format($currentPrice, 2),
        'original_price' => $isOnSale ? number_format($row['price'], 2) : null,
        'image' => !empty($row['image']) ? $row['image'] : '../assets/placeholder.png',
        'category' => $row['category'],
        'is_on_sale' => $isOnSale,
        'stock' => $row['stock']
    ];
}

// Return JSON response
echo json_encode([
    'success' => true,
    'count' => count($products),
    'products' => $products
]);
?>
