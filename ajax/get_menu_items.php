<?php
require_once dirname(__FILE__) . '/../config/database.php';
$conn = getConnection();

$categoryId = $_GET['category_id'] ?? 'all';

$sql = "SELECT m.*, IFNULL(c.name, 'Uncategorized') as category_name 
        FROM menu_items m 
        LEFT JOIN categories c ON m.category_id = c.id 
        WHERE m.available = 1";

if ($categoryId !== 'all') {
    $sql .= " AND m.category_id = " . intval($categoryId);
}

$sql .= " ORDER BY m.name ASC";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    while ($item = $result->fetch_assoc()) {
        $imagePath = !empty($item['image'])
            ? 'uploads/' . $item['image']
            : 'https://images.unsplash.com/photo-1541167760496-162955ed8a9f?q=80&w=800';
        $priceFormatted = number_format($item['price'], 2);
        $nameEsc        = htmlspecialchars($item['name'], ENT_QUOTES);
        ?>
        <div class="menu-card">
            <div class="menu-img">
                <img src="<?php echo $imagePath; ?>" alt="<?php echo $nameEsc; ?>" loading="lazy">
            </div>
            <div class="menu-info">
                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                <p><?php echo htmlspecialchars($item['description'] ?? ''); ?></p>
                <div class="menu-footer">
                    <span class="price">₱<?php echo $priceFormatted; ?></span>
                    <button class="btn-add"
                        id="add-btn-<?php echo $item['id']; ?>"
                        onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo $nameEsc; ?>', <?php echo $item['price']; ?>)">
                        + Add
                    </button>
                </div>
            </div>
        </div>
        <?php
    }
} else {
    echo '<div style="grid-column: 1/-1; text-align: center; padding: 100px; opacity: 0.5;">';
    echo '<p>Our collection is currently breathing...</p></div>';
}
?>
