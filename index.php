<?php
require_once 'includes/auth_check.php';
require_once 'config/database.php';

// index.php is a public page (isPublic = true)
checkAuth(null, true);

$conn = getConnection();
$pageTitle = 'Explore Our Menu';

// Fetch products for customer view
$categories = $conn->query("SELECT * FROM categories ORDER BY name");
$conn->close();

include 'includes/header.php';
?>

<!-- Hero Wrapper -->
<div class="hero-wrapper">
    <div class="hero-section">
        <div class="hero-badge">The Art of Coffee</div>
        <h1>Brewed to Perfection</h1>
        <p>Experience the finest selection of handcrafted beans and artisanal treats, delivered with a touch of elegance.</p>
    </div>
</div>

<!-- Glassmorphism Controls Section -->
<div class="controls-container">
    <div class="controls-glass">
        <!-- Search Box -->
        <div class="search-box">
            <input type="text" id="catalogSearch" placeholder="Find your perfect brew..." onkeyup="filterCatalog()">
            <span class="search-icon">🔍</span>
        </div>
        
        <!-- Advanced Category Navigation -->
        <div class="category-nav">
            <div class="cat-pill active" onclick="selectCategory(0, this)">Signature All</div>
            <?php while($cat = $categories->fetch_assoc()): ?>
                <div class="cat-pill" onclick="selectCategory(<?php echo $cat['id']; ?>, this)">
                    <?php echo htmlspecialchars($cat['name']); ?>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

<!-- Main Grid -->
<div class="page-body" style="padding-top: 20px;">
    <div class="menu-grid" id="catalogGrid">
        <div class="empty-state">
            <div class="empty-icon">☕</div>
            <h2 style="color: var(--primary); font-weight: 800;">Our Menu is Brewing...</h2>
            <p>We're currently perfecting our selection. Please check back in a few moments!</p>
        </div>
    </div>
</div>

<script>
    let currentCategoryId = 0;

    function selectCategory(id, el) {
        currentCategoryId = id;
        
        // Update UI
        document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
        el.classList.add('active');
        
        filterCatalog();
    }

    function filterCatalog() {
        const search = document.getElementById('catalogSearch').value;

        ajaxRequest('ajax/menu_actions.php', {
            action: 'list',
            search: search,
            category_id: currentCategoryId
        }, function(res) {
            const grid = document.getElementById('catalogGrid');
            if (res.success && Array.isArray(res.data)) {
                const availableItems = res.data.filter(item => item.available == 1);
                
                if (availableItems.length > 0) {
                    grid.innerHTML = availableItems.map(item => `
                        <div class="menu-item-card">
                            <div class="item-image-wrapper">
                                ${item.image ? `<img src="uploads/${item.image}" alt="${escapeHtml(item.name)}">` : '<div style="display:flex;align-items:center;justify-content:center;height:100%;font-size:4rem;background:#F5F0EB;">☕</div>'}
                                <div class="item-badge">${escapeHtml(item.category_name)}</div>
                            </div>
                            <div class="item-content">
                                <div class="item-name">${escapeHtml(item.name)}</div>
                                <div class="item-description">${escapeHtml(item.description || '')}</div>
                                <div class="item-footer">
                                    <div class="item-price">₱${parseFloat(item.price).toFixed(2)}</div>
                                    <div class="order-btn">Order Now</div>
                                </div>
                            </div>
                        </div>
                    `).join('');
                } else {
                    showEmptyState();
                }
            } else {
                showEmptyState();
            }
        });
    }

    function showEmptyState() {
        document.getElementById('catalogGrid').innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">🔍</div>
                <h2 style="color: var(--primary); font-weight: 800;">No Matches Found</h2>
                <p>We couldn't find any coffee matching your criteria. Try another search or category!</p>
            </div>
        `;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initial load
    document.addEventListener('DOMContentLoaded', function() {
        filterCatalog();
    });
</script>

<?php include 'includes/footer.php'; ?>
