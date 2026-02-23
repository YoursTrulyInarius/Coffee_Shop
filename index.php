<?php
require_once 'config/database.php';
$conn = getConnection();

// Fetch categories for filtering
$categories_query = "SELECT * FROM categories ORDER BY name ASC";
$categories_result = $conn->query($categories_query);
$categories = [];
while($row = $categories_result->fetch_assoc()) {
    $categories[] = $row;
}

include 'includes/header.php';
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <span class="hero-tag">A Modern Coffee Experience</span>
            <h1>Crafted for the<br>Curious Mind</h1>
            <p>Step away from the ordinary and immerse yourself in the art of artisanal brewing, where every cup tells a story of passion and precision.</p>
            <div class="hero-actions">
                <a href="#menu" class="btn btn-primary">Discover Menu</a>
                <a href="#location" class="btn btn-outline">Find Us</a>
            </div>
        </div>
    </div>
</section>

<!-- Menu Display Section -->
<section id="menu" class="section">
    <div style="width: 100%;">
        <div class="section-intro-block">
            <span class="hero-tag" style="background: var(--clay); color: var(--white); padding: 4px 12px; border-radius: 4px;">Foundations</span>
            <h2 class="section-title">The Seasonal Palette</h2>
            <p style="max-width: 600px; margin: 0 auto; color: var(--text-muted); font-size: 1.1rem;">
                Crafted from earth. Brewed for soul. Experience our latest small-batch arrivals.
            </p>
            
            <div class="cat-pills" style="margin-top: 40px;">
                <div class="pill active" onclick="loadMenu('all', this)">All Collections</div>
                <?php foreach($categories as $cat): ?>
                    <div class="pill" onclick="loadMenu(<?php echo $cat['id']; ?>, this)">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="container" style="margin-top: 60px;">
            <div class="menu-grid" id="menuGrid">
                <!-- AJAX content -->
            </div>
        </div>
    </div>
</section>

<!-- Location Section -->
<section id="location" class="section section-alt">
    <div class="location-grid">
        <div class="location-map-side">
            <img src="https://images.unsplash.com/photo-1559925393-8be0ec4767c8?q=80&w=1400" alt="Yours Truly SF Roastery">
        </div>
        <div class="location-info-side">
            <span class="hero-tag" style="color: var(--accent);">Our Home</span>
            <h2>Established<br>In San Francisco</h2>
            
            <div class="info-block" style="margin-top: 50px;">
                <h4>The Address</h4>
                <p>123 Coffee Lane, Artisanal District<br>San Francisco, CA 94103</p>
            </div>
            
            <div class="info-block">
                <h4>Open House</h4>
                <p>Mon - Fri: 7:00 AM - 8:00 PM<br>Sat - Sun: 8:00 AM - 9:00 PM</p>
            </div>
            
            <div class="info-block">
                <h4>Say Hello</h4>
                <p>Phone: (555) 123-4567<br>Email: hello@yours-truly.coffee</p>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    loadMenu('all');

    // --- Dynamic Navigation (ScrollSpy) ---
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.header-menu a');

    const observerOption = {
        root: null,
        rootMargin: '0px',
        threshold: 0.3
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.getAttribute('id') || 'home';
                updateActiveLink(id);
            }
        });
    }, observerOption);

    // Observe all sections including a "home" target if possible
    sections.forEach(section => observer.observe(section));
    // Also handle top of page
    const hero = document.querySelector('.hero-section');
    if(hero) {
        if(!hero.id) hero.id = 'home';
        observer.observe(hero);
    }

    function updateActiveLink(id) {
        navLinks.forEach(link => {
            link.classList.remove('active');
            const href = link.getAttribute('href');
            if (href === 'index.php' && id === 'home') {
                link.classList.add('active');
            } else if (href.includes('#' + id)) {
                link.classList.add('active');
            }
        });
    }

    // Header Scroll Effect
    window.addEventListener('scroll', function() {
        const header = document.querySelector('.main-header');
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });
});

function loadMenu(catId, el) {
    if(el) {
        document.querySelectorAll('.cat-pills .pill').forEach(p => p.classList.remove('active'));
        el.classList.add('active');
    }

    fetch(`ajax/get_menu_items.php?category_id=${catId}`)
        .then(r => r.text())
        .then(html => {
            document.getElementById('menuGrid').innerHTML = html;
        });
}
</script>

<?php include 'includes/footer.php'; ?>
