<?php
require_once 'config/database.php';
if (session_status() === PHP_SESSION_NONE) session_start();
$conn = getConnection();

// Fetch categories for filtering
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
$categories = [];
while($row = $categories_result->fetch_assoc()) $categories[] = $row;

// Pass login state — use explicit strings to avoid PHP bool -> empty string JS bug
$jsLoggedIn   = isset($_SESSION['user_id']) ? 'true' : 'false';
$jsFullName   = isset($_SESSION['full_name']) ? addslashes(htmlspecialchars($_SESSION['full_name'])) : '';

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
            <div class="menu-grid" id="menuGrid"><!-- AJAX --></div>
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
            <div class="info-block" style="margin-top: 50px;"><h4>The Address</h4><p>123 Coffee Lane, Artisanal District<br>San Francisco, CA 94103</p></div>
            <div class="info-block"><h4>Open House</h4><p>Mon - Fri: 7:00 AM - 8:00 PM<br>Sat - Sun: 8:00 AM - 9:00 PM</p></div>
            <div class="info-block"><h4>Say Hello</h4><p>Phone: (555) 123-4567<br>Email: hello@yours-truly.coffee</p></div>
        </div>
    </div>
</section>

<!-- ═══════════════════════════════════════════════ -->
<!-- LOGIN / REGISTER MODAL                          -->
<!-- ═══════════════════════════════════════════════ -->
<div id="authModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(44,24,16,0.6);backdrop-filter:blur(10px);z-index:99999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:32px;width:90%;max-width:440px;overflow:hidden;box-shadow:0 30px 80px rgba(44,24,16,0.25);">
        <!-- Tabs -->
        <div style="display:flex;border-bottom:1px solid rgba(0,0,0,0.07);">
            <button id="tabLogin" onclick="switchAuthTab('login')"
                style="flex:1;padding:22px;font-weight:700;font-size:0.9rem;text-transform:uppercase;letter-spacing:1px;border:none;cursor:pointer;background:var(--primary);color:#fff;transition:all 0.3s;">
                Sign In
            </button>
            <button id="tabRegister" onclick="switchAuthTab('register')"
                style="flex:1;padding:22px;font-weight:700;font-size:0.9rem;text-transform:uppercase;letter-spacing:1px;border:none;cursor:pointer;background:var(--dust);color:var(--text-muted);transition:all 0.3s;">
                Create Account
            </button>
        </div>

        <div style="padding:40px;">
            <div style="text-align:center;margin-bottom:30px;">
                <div style="font-size:2.5rem;">☕</div>
                <h2 id="authTitle" style="font-family:'Playfair Display',serif;color:var(--primary);margin-top:10px;">Welcome Back</h2>
                <p id="authSubtitle" style="color:var(--text-muted);font-size:0.9rem;margin-top:5px;">Sign in to place your order</p>
            </div>

            <div id="authAlert" style="display:none;padding:12px 16px;border-radius:10px;margin-bottom:20px;font-size:0.9rem;font-weight:600;"></div>

            <!-- Login Form -->
            <form id="loginFormPublic" onsubmit="handlePublicLogin(event)" style="display:block;">
                <div style="margin-bottom:18px;">
                    <label style="display:block;font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--primary);margin-bottom:8px;">Username</label>
                    <input id="loginUsername" type="text" placeholder="Your username" required
                        style="width:100%;padding:14px 18px;border:1.5px solid var(--dust);border-radius:12px;font-size:1rem;background:var(--paper);font-family:inherit;">
                </div>
                <div style="margin-bottom:24px;">
                    <label style="display:block;font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--primary);margin-bottom:8px;">Password</label>
                    <input id="loginPassword" type="password" placeholder="Your password" required
                        style="width:100%;padding:14px 18px;border:1.5px solid var(--dust);border-radius:12px;font-size:1rem;background:var(--paper);font-family:inherit;">
                </div>
                <button type="submit" id="loginBtn"
                    style="width:100%;padding:16px;background:var(--primary);color:#fff;border:none;border-radius:50px;font-weight:700;font-size:0.9rem;text-transform:uppercase;letter-spacing:1px;cursor:pointer;">
                    Sign In & Order
                </button>
            </form>

            <!-- Register Form -->
            <form id="registerFormPublic" onsubmit="handlePublicRegister(event)" style="display:none;">
                <div style="margin-bottom:18px;">
                    <label style="display:block;font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--primary);margin-bottom:8px;">Full Name</label>
                    <input id="regFullName" type="text" placeholder="e.g. Juan Dela Cruz" required
                        style="width:100%;padding:14px 18px;border:1.5px solid var(--dust);border-radius:12px;font-size:1rem;background:var(--paper);font-family:inherit;">
                </div>
                <div style="margin-bottom:18px;">
                    <label style="display:block;font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--primary);margin-bottom:8px;">Username</label>
                    <input id="regUsername" type="text" placeholder="Choose a username" required
                        style="width:100%;padding:14px 18px;border:1.5px solid var(--dust);border-radius:12px;font-size:1rem;background:var(--paper);font-family:inherit;">
                </div>
                <div style="margin-bottom:24px;">
                    <label style="display:block;font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--primary);margin-bottom:8px;">Password</label>
                    <input id="regPassword" type="password" placeholder="Min. 6 characters" required
                        style="width:100%;padding:14px 18px;border:1.5px solid var(--dust);border-radius:12px;font-size:1rem;background:var(--paper);font-family:inherit;">
                </div>
                <button type="submit" id="registerBtn"
                    style="width:100%;padding:16px;background:var(--accent);color:#fff;border:none;border-radius:50px;font-weight:700;font-size:0.9rem;text-transform:uppercase;letter-spacing:1px;cursor:pointer;">
                    Create Account & Order
                </button>
            </form>

            <div style="text-align:center;margin-top:20px;">
                <button onclick="closeAuthModal()" style="background:none;border:none;color:var(--text-muted);cursor:pointer;font-size:0.85rem;">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- CHECKOUT MODAL                                  -->
<!-- ═══════════════════════════════════════════════ -->
<div id="checkoutModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(44,24,16,0.6);backdrop-filter:blur(10px);z-index:99999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:32px;width:90%;max-width:480px;max-height:90vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 30px 80px rgba(44,24,16,0.25);">
        <!-- Header -->
        <div style="padding:30px 35px 20px;border-bottom:1px solid rgba(0,0,0,0.06);flex-shrink:0;display:flex;justify-content:space-between;align-items:center;">
            <div>
                <h3 style="font-family:'Playfair Display',serif;color:var(--primary);font-size:1.5rem;margin:0;">Your Order</h3>
                <span style="background:var(--accent);color:#fff;font-size:0.7rem;font-weight:800;padding:3px 10px;border-radius:50px;letter-spacing:1px;text-transform:uppercase;">COD · Cash on Delivery</span>
            </div>
            <button onclick="closeCheckout()" style="width:36px;height:36px;border-radius:50%;border:1.5px solid var(--dust);background:none;cursor:pointer;font-size:1.2rem;color:var(--text-muted);">×</button>
        </div>

        <!-- Scrollable Body -->
        <div style="padding:25px 35px;overflow-y:auto;flex-grow:1;">
            <!-- Item Summary -->
            <div id="checkoutItemSummary" style="background:var(--paper);border-radius:16px;padding:18px;margin-bottom:20px;font-size:0.9rem;"></div>

            <!-- Delivery Details -->
            <p style="font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);margin-bottom:16px;">Delivery Details</p>
            <div id="checkoutAlert" style="display:none;padding:12px 16px;border-radius:10px;margin-bottom:16px;font-size:0.85rem;font-weight:600;"></div>

            <div style="margin-bottom:16px;">
                <label style="display:block;font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--primary);margin-bottom:8px;">Full Name</label>
                <input id="coName" type="text" placeholder="Name of recipient" required
                    style="width:100%;padding:13px 16px;border:1.5px solid var(--dust);border-radius:12px;font-size:0.95rem;background:var(--paper);font-family:inherit;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block;font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--primary);margin-bottom:8px;">Delivery Address</label>
                <textarea id="coAddress" placeholder="Full delivery address" required rows="2"
                    style="width:100%;padding:13px 16px;border:1.5px solid var(--dust);border-radius:12px;font-size:0.95rem;background:var(--paper);font-family:inherit;box-sizing:border-box;resize:none;"></textarea>
            </div>
            <div style="margin-bottom:16px;">
                <label style="display:block;font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--primary);margin-bottom:8px;">Contact Number</label>
                <input id="coContact" type="text" inputmode="numeric" placeholder="e.g. 09123456789" required
                    oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 11)"
                    style="width:100%;padding:13px 16px;border:1.5px solid var(--dust);border-radius:12px;font-size:0.95rem;background:var(--paper);font-family:inherit;box-sizing:border-box;">
            </div>
            <div style="margin-bottom:8px;">
                <label style="display:block;font-weight:700;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--primary);margin-bottom:8px;">Notes <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span></label>
                <textarea id="coNotes" placeholder="Special instructions..." rows="2"
                    style="width:100%;padding:13px 16px;border:1.5px solid var(--dust);border-radius:12px;font-size:0.95rem;background:var(--paper);font-family:inherit;box-sizing:border-box;resize:none;"></textarea>
            </div>
        </div>

        <!-- Footer -->
        <div style="padding:20px 35px 30px;border-top:1px solid rgba(0,0,0,0.06);flex-shrink:0;">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
                <span style="font-weight:600;color:var(--text-muted);">Total</span>
                <span id="checkoutTotal" style="font-size:1.4rem;font-weight:700;color:var(--accent);">₱0.00</span>
            </div>
            <button id="placeOrderBtn" onclick="placeOrder()"
                style="width:100%;padding:16px;background:var(--primary);color:#fff;border:none;border-radius:50px;font-weight:700;font-size:0.95rem;cursor:pointer;transition:all 0.3s;text-transform:uppercase;letter-spacing:1px;">
                🛍 Place Order (COD)
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- ORDER SUCCESS MODAL                             -->
<!-- ═══════════════════════════════════════════════ -->
<div id="successModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(44,24,16,0.6);backdrop-filter:blur(10px);z-index:99999;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:32px;width:90%;max-width:400px;padding:50px 40px;text-align:center;box-shadow:0 30px 80px rgba(44,24,16,0.25);">
        <div style="font-size:4rem;margin-bottom:20px;">🎉</div>
        <h2 style="font-family:'Playfair Display',serif;color:var(--primary);margin-bottom:12px;">Order Placed!</h2>
        <p id="successMsg" style="color:var(--text-muted);line-height:1.6;margin-bottom:30px;"></p>
        <button onclick="closeSuccessModal()"
            style="padding:14px 40px;background:var(--primary);color:#fff;border:none;border-radius:50px;font-weight:700;cursor:pointer;font-size:0.9rem;text-transform:uppercase;letter-spacing:1px;">
            Continue Browsing
        </button>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- FLOATING CART BAR                               -->
<!-- ═══════════════════════════════════════════════ -->
<div id="cartBar" style="display:none;position:fixed;bottom:0;left:0;right:0;z-index:9000;background:var(--primary);color:#fff;padding:16px 24px;box-shadow:0 -8px 30px rgba(44,24,16,0.25);">
    <div style="max-width:900px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:16px;">
        <div style="display:flex;align-items:center;gap:16px;">
            <span style="font-size:1.5rem;">🛒</span>
            <div>
                <div id="cartBarCount" style="font-weight:700;font-size:1rem;">0 items</div>
                <div id="cartBarTotal" style="font-size:0.8rem;opacity:0.8;">₱0.00</div>
            </div>
        </div>
        <div style="display:flex;gap:10px;">
            <button onclick="openCartPreview()"
                style="padding:10px 22px;background:rgba(255,255,255,0.2);color:#fff;border:1.5px solid rgba(255,255,255,0.4);border-radius:50px;font-weight:600;cursor:pointer;font-size:0.85rem;">
                View Cart
            </button>
            <button onclick="proceedToCheckout()"
                style="padding:10px 26px;background:#fff;color:var(--primary);border:none;border-radius:50px;font-weight:700;cursor:pointer;font-size:0.85rem;">
                Checkout →
            </button>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════ -->
<!-- CART PREVIEW MODAL                              -->
<!-- ═══════════════════════════════════════════════ -->
<div id="cartModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(44,24,16,0.6);backdrop-filter:blur(10px);z-index:99998;align-items:center;justify-content:center;">
    <div style="background:#fff;border-radius:32px;width:90%;max-width:480px;max-height:85vh;display:flex;flex-direction:column;overflow:hidden;box-shadow:0 30px 80px rgba(44,24,16,0.25);">
        <div style="padding:28px 35px 18px;border-bottom:1px solid rgba(0,0,0,0.06);flex-shrink:0;display:flex;justify-content:space-between;align-items:center;">
            <h3 style="font-family:'Playfair Display',serif;color:var(--primary);font-size:1.4rem;margin:0;">🛒 Your Cart</h3>
            <button onclick="closeCartModal()" style="width:34px;height:34px;border-radius:50%;border:1.5px solid var(--dust);background:none;cursor:pointer;font-size:1.1rem;color:var(--text-muted);">×</button>
        </div>
        <div id="cartModalBody" style="padding:20px 35px;overflow-y:auto;flex-grow:1;"></div>
        <div style="padding:18px 35px 28px;border-top:1px solid rgba(0,0,0,0.06);flex-shrink:0;">
            <div style="display:flex;justify-content:space-between;margin-bottom:14px;">
                <span style="color:var(--text-muted);font-weight:600;">Total</span>
                <span id="cartModalTotal" style="font-size:1.3rem;font-weight:700;color:var(--accent);">₱0.00</span>
            </div>
            <button onclick="closeCartModal(); proceedToCheckout();"
                style="width:100%;padding:15px;background:var(--primary);color:#fff;border:none;border-radius:50px;font-weight:700;font-size:0.95rem;cursor:pointer;text-transform:uppercase;letter-spacing:1px;">
                Proceed to Checkout →
            </button>
        </div>
    </div>
</div>

<script>
// === STATE ===
let isLoggedIn = <?php echo $jsLoggedIn; ?>;
let userFullName = '<?php echo $jsFullName; ?>';
let pendingCheckout = false; // waiting for auth before checkout
let cart = {}; // { itemId: { id, name, price, qty } }

// === MENU LOAD ===
document.addEventListener('DOMContentLoaded', () => {
    loadMenu('all');
    initScrollSpy();
    initHeaderScroll();
});

function loadMenu(catId, el) {
    if (el) {
        document.querySelectorAll('.cat-pills .pill').forEach(p => p.classList.remove('active'));
        el.classList.add('active');
    }
    fetch(`ajax/get_menu_items.php?category_id=${catId}`)
        .then(r => r.text())
        .then(html => { document.getElementById('menuGrid').innerHTML = html; });
}

// === CART ===
function addToCart(itemId, itemName, itemPrice) {
    if (cart[itemId]) {
        cart[itemId].qty++;
    } else {
        cart[itemId] = { id: itemId, name: itemName, price: parseFloat(itemPrice), qty: 1 };
    }
    updateCartBar();
    // Visual feedback on button
    const btn = document.getElementById('add-btn-' + itemId);
    if (btn) {
        const prev = btn.textContent;
        btn.style.background = 'var(--primary)';
        btn.style.color = '#fff';
        btn.textContent = '✓ Added';
        setTimeout(() => {
            btn.style.background = '';
            btn.style.color = '';
            btn.textContent = `+ Add (${cart[itemId].qty})`;
        }, 700);
    }
}

function updateCartBar() {
    const items = Object.values(cart);
    const totalQty = items.reduce((s, i) => s + i.qty, 0);
    const totalAmt = items.reduce((s, i) => s + i.price * i.qty, 0);

    const bar = document.getElementById('cartBar');
    if (totalQty === 0) {
        bar.style.display = 'none';
        return;
    }
    bar.style.display = 'flex';
    document.getElementById('cartBarCount').textContent = `${totalQty} item${totalQty > 1 ? 's' : ''} in cart`;
    document.getElementById('cartBarTotal').textContent = `₱${totalAmt.toFixed(2)}`;
}

function openCartPreview() {
    const items = Object.values(cart);
    const totalAmt = items.reduce((s, i) => s + i.price * i.qty, 0);

    let html = '';
    if (items.length === 0) {
        html = '<p style="text-align:center;color:var(--text-muted);padding:30px 0;">Your cart is empty.</p>';
    } else {
        items.forEach(item => {
            html += `
            <div style="display:flex;align-items:center;gap:12px;padding:12px 0;border-bottom:1px solid rgba(0,0,0,0.06);">
                <div style="flex:1;">
                    <div style="font-weight:700;color:var(--primary);font-size:0.95rem;">${item.name}</div>
                    <div style="color:var(--text-muted);font-size:0.8rem;">₱${item.price.toFixed(2)} each</div>
                </div>
                <div style="display:flex;align-items:center;gap:8px;">
                    <button onclick="changeQty(${item.id}, -1)"
                        style="width:28px;height:28px;border-radius:50%;border:1.5px solid var(--dust);background:none;cursor:pointer;font-size:1rem;line-height:1;">−</button>
                    <span style="font-weight:700;min-width:20px;text-align:center;">${item.qty}</span>
                    <button onclick="changeQty(${item.id}, 1)"
                        style="width:28px;height:28px;border-radius:50%;border:1.5px solid var(--dust);background:none;cursor:pointer;font-size:1rem;line-height:1;">+</button>
                </div>
                <div style="font-weight:700;color:var(--accent);min-width:60px;text-align:right;">₱${(item.price * item.qty).toFixed(2)}</div>
                <button onclick="removeFromCart(${item.id})"
                    style="background:none;border:none;cursor:pointer;color:var(--text-muted);font-size:1.1rem;padding:0 4px;">✕</button>
            </div>`;
        });
    }

    document.getElementById('cartModalBody').innerHTML = html;
    document.getElementById('cartModalTotal').textContent = '₱' + totalAmt.toFixed(2);
    document.getElementById('cartModal').style.display = 'flex';
}

function closeCartModal() {
    document.getElementById('cartModal').style.display = 'none';
}

function changeQty(itemId, delta) {
    if (!cart[itemId]) return;
    cart[itemId].qty += delta;
    if (cart[itemId].qty <= 0) delete cart[itemId];
    updateCartBar();
    openCartPreview(); // refresh modal
    // refresh add buttons
    Object.values(cart).forEach(item => {
        const btn = document.getElementById('add-btn-' + item.id);
        if (btn) btn.textContent = `+ Add (${item.qty})`;
    });
    // if item was removed fully, reset its button
    const btn = document.getElementById('add-btn-' + itemId);
    if (btn && !cart[itemId]) btn.textContent = '+ Add';
}

function removeFromCart(itemId) {
    const btn = document.getElementById('add-btn-' + itemId);
    if (btn) btn.textContent = '+ Add';
    delete cart[itemId];
    updateCartBar();
    if (Object.keys(cart).length === 0) { closeCartModal(); return; }
    openCartPreview();
}

// === CHECKOUT FLOW ===
function proceedToCheckout() {
    if (Object.keys(cart).length === 0) return;
    if (!isLoggedIn) {
        pendingCheckout = true;
        showAuthModal();
    } else {
        openCheckout(Object.values(cart));
    }
}

// === AUTH MODAL ===
function showAuthModal() {
    document.getElementById('authModal').style.display = 'flex';
}

function closeAuthModal() {
    document.getElementById('authModal').style.display = 'none';
    pendingCheckout = false;
    clearAuthAlert();
}

function switchAuthTab(tab) {
    const isLogin = tab === 'login';
    document.getElementById('loginFormPublic').style.display  = isLogin ? 'block' : 'none';
    document.getElementById('registerFormPublic').style.display = isLogin ? 'none'  : 'block';
    document.getElementById('authTitle').textContent    = isLogin ? 'Welcome Back'              : 'Create Account';
    document.getElementById('authSubtitle').textContent = isLogin ? 'Sign in to place your order' : 'Join us to start ordering';
    const loginTab = document.getElementById('tabLogin');
    const regTab   = document.getElementById('tabRegister');
    loginTab.style.background = isLogin ? 'var(--primary)' : 'var(--dust)';
    loginTab.style.color      = isLogin ? '#fff' : 'var(--text-muted)';
    regTab.style.background   = !isLogin ? 'var(--primary)' : 'var(--dust)';
    regTab.style.color        = !isLogin ? '#fff' : 'var(--text-muted)';
    clearAuthAlert();
}

function showAuthAlert(msg, isError = true) {
    const el = document.getElementById('authAlert');
    el.textContent = msg;
    el.style.display = 'block';
    el.style.background = isError ? '#FEE2E2' : '#D1FAE5';
    el.style.color      = isError ? '#991B1B' : '#065F46';
}
function clearAuthAlert() { const el = document.getElementById('authAlert'); el.style.display = 'none'; el.textContent = ''; }

function handlePublicLogin(e) {
    e.preventDefault();
    const btn = document.getElementById('loginBtn');
    btn.textContent = 'Signing in...'; btn.disabled = true;
    clearAuthAlert();
    const fd = new FormData();
    fd.append('username', document.getElementById('loginUsername').value);
    fd.append('password', document.getElementById('loginPassword').value);
    fetch('login.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.textContent = 'Sign In & Order'; btn.disabled = false;
            if (res.success) {
                isLoggedIn = true;
                document.getElementById('authModal').style.display = 'none';
                if (pendingCheckout) { pendingCheckout = false; openCheckout(Object.values(cart)); }
            } else { showAuthAlert(res.message); }
        })
        .catch(() => { btn.textContent = 'Sign In & Order'; btn.disabled = false; showAuthAlert('An error occurred.'); });
}

function handlePublicRegister(e) {
    e.preventDefault();
    const btn = document.getElementById('registerBtn');
    btn.textContent = 'Creating account...'; btn.disabled = true;
    clearAuthAlert();
    const fd = new FormData();
    fd.append('full_name', document.getElementById('regFullName').value);
    fd.append('username',  document.getElementById('regUsername').value);
    fd.append('password',  document.getElementById('regPassword').value);
    fetch('register.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.textContent = 'Create Account & Order'; btn.disabled = false;
            if (res.success) {
                isLoggedIn = true;
                showAuthAlert(res.message, false);
                setTimeout(() => {
                    document.getElementById('authModal').style.display = 'none';
                    clearAuthAlert();
                    if (pendingCheckout) { pendingCheckout = false; openCheckout(Object.values(cart)); }
                }, 1000);
            } else { showAuthAlert(res.message); }
        })
        .catch(() => { btn.textContent = 'Create Account & Order'; btn.disabled = false; showAuthAlert('An error occurred.'); });
}

// === CHECKOUT MODAL ===
let checkoutItems = [];

function openCheckout(items) {
    checkoutItems = items;
    let total = 0, html = '';
    items.forEach(it => {
        const sub = it.price * it.qty;
        total += sub;
        html += `<div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid rgba(0,0,0,0.05);">
            <span style="font-weight:600;color:var(--primary);">${it.name} <span style="font-weight:400;color:var(--text-muted);">× ${it.qty}</span></span>
            <span style="font-weight:700;color:var(--accent);">₱${sub.toFixed(2)}</span>
        </div>`;
    });
    document.getElementById('checkoutItemSummary').innerHTML = html;
    document.getElementById('checkoutTotal').textContent = '₱' + total.toFixed(2);
    if (userFullName) document.getElementById('coName').value = userFullName;
    document.getElementById('checkoutAlert').style.display = 'none';
    document.getElementById('checkoutModal').style.display = 'flex';
}

function closeCheckout() { document.getElementById('checkoutModal').style.display = 'none'; }

function placeOrder() {
    const name    = document.getElementById('coName').value.trim();
    const address = document.getElementById('coAddress').value.trim();
    const contact = document.getElementById('coContact').value.trim();
    const notes   = document.getElementById('coNotes').value.trim();
    if (!name || !address || !contact) { showCheckoutAlert('Please fill in Name, Address, and Contact Number.'); return; }

    const btn = document.getElementById('placeOrderBtn');
    btn.textContent = 'Placing order...'; btn.disabled = true;

    const fd = new FormData();
    fd.append('action', 'place');
    fd.append('customer_name', name);
    fd.append('address', address);
    fd.append('contact', contact);
    fd.append('notes', notes);
    fd.append('items', JSON.stringify(checkoutItems.map(i => ({ menu_item_id: i.id, quantity: i.qty, price: i.price }))));

    fetch('ajax/order_actions.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(res => {
            btn.textContent = '🛍 Place Order (COD)'; btn.disabled = false;
            if (res.success) {
                // Clear cart
                cart = {};
                updateCartBar();
                document.querySelectorAll('[id^="add-btn-"]').forEach(b => b.textContent = '+ Add');
                closeCheckout();
                document.getElementById('successMsg').textContent = res.message;
                document.getElementById('successModal').style.display = 'flex';
            } else if (res.needs_auth) {
                closeCheckout(); showAuthModal();
            } else {
                showCheckoutAlert(res.message);
            }
        })
        .catch(() => { btn.textContent = '🛍 Place Order (COD)'; btn.disabled = false; showCheckoutAlert('An error occurred. Please try again.'); });
}

function showCheckoutAlert(msg) {
    const el = document.getElementById('checkoutAlert');
    el.textContent = msg; el.style.display = 'block';
    el.style.background = '#FEE2E2'; el.style.color = '#991B1B';
}

function closeSuccessModal() { document.getElementById('successModal').style.display = 'none'; }

// === SCROLL SPY + HEADER ===
function initScrollSpy() {
    const sections = document.querySelectorAll('section[id]');
    const navLinks = document.querySelectorAll('.header-menu a');
    const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const id = entry.target.id || 'home';
                navLinks.forEach(link => {
                    link.classList.remove('active');
                    if (link.href.includes('#' + id) || (link.getAttribute('href') === 'index.php' && id === 'home'))
                        link.classList.add('active');
                });
            }
        });
    }, { threshold: 0.3 });
    sections.forEach(s => observer.observe(s));
    const hero = document.querySelector('.hero-section');
    if (hero) { if (!hero.id) hero.id = 'home'; observer.observe(hero); }
}
function initHeaderScroll() {
    window.addEventListener('scroll', () => {
        document.querySelector('.main-header')?.classList.toggle('scrolled', window.scrollY > 50);
    });
}
</script>
<?php include 'includes/footer.php'; ?>
