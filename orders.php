<?php
require_once 'includes/auth_check.php';
checkAuth('admin');
$pageTitle = 'Orders';
include 'includes/header.php';
?>

<!-- Tabs -->
<div class="tabs">
    <button class="tab-btn active" data-tab-group="orders" data-tab="new-order" onclick="switchTab('orders', 'new-order')">
        🛒 New Order
    </button>
    <button class="tab-btn" data-tab-group="orders" data-tab="history" onclick="switchTab('orders', 'history'); loadOrderHistory();">
        📜 Order History
    </button>
</div>

<!-- Tab: New Order -->
<div class="tab-content active" data-tab-content-group="orders" data-tab-content="new-order">
    <div class="order-layout">
        <!-- Menu Items Grid -->
        <div class="menu-selection-side">
            <div class="toolbar">
                <div class="search-box">
                    <svg class="search-icon icon-svg" viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                        <path d="M15.5 14h-.79l-.28-.27C15.41 12.59 16 11.11 16 9.5 16 5.91 13.09 3 9.5 3S3 5.91 3 9.5 5.91 16 9.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/>
                    </svg>
                    <input type="text" id="orderMenuSearch" class="form-control" placeholder="Search menu items..." onkeyup="loadOrderMenu()">
                </div>
                <div class="filter-group">
                    <select class="form-control" id="orderCategoryFilter" onchange="loadOrderMenu()" style="width: 180px;">
                        <option value="">All Categories</option>
                    </select>
                </div>
            </div>
            <div class="menu-grid" id="orderMenuGrid" style="margin-top: 0; padding-bottom: 20px;">
                <div class="text-center text-muted w-100" style="padding: 40px;">Loading menu...</div>
            </div>
        </div>

        <!-- Customer Orders Panel -->
        <div class="cart-panel" style="display:flex;flex-direction:column;gap:0;">
            <div class="cart-header" style="display:flex;justify-content:space-between;align-items:center;">
                <h3>📋 Customer Orders</h3>
                <button onclick="loadCustomerOrders()" title="Refresh" style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:var(--text-muted);padding:4px 8px;border-radius:8px;transition:background 0.2s;" onmouseover="this.style.background='var(--paper)'" onmouseout="this.style.background='none'">🔄</button>
            </div>

            <!-- Summary Bar -->
            <div style="display:flex;gap:12px;padding:12px 16px;background:var(--paper);border-bottom:1px solid rgba(0,0,0,0.06);flex-shrink:0;">
                <div style="flex:1;text-align:center;">
                    <div id="coSummaryQty" style="font-size:1.4rem;font-weight:800;color:var(--primary);">0</div>
                    <div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);font-weight:600;">Total Items</div>
                </div>
                <div style="width:1px;background:rgba(0,0,0,0.08);"></div>
                <div style="flex:1;text-align:center;">
                    <div id="coSummaryAmount" style="font-size:1.4rem;font-weight:800;color:var(--accent);">₱0.00</div>
                    <div style="font-size:0.7rem;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);font-weight:600;">Total Value</div>
                </div>
            </div>

            <!-- Orders List -->
            <div id="customerOrdersList" style="flex:1;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:10px;">
                <div style="text-align:center;padding:40px 0;color:var(--text-muted);">
                    <div style="font-size:2rem;margin-bottom:8px;">📋</div>
                    <p>Loading orders...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tab: Order History -->
<div class="tab-content" data-tab-content-group="orders" data-tab-content="history">
    <div class="toolbar">
        <div class="filter-group">
            <input type="date" class="form-control" id="historyDateFrom" style="width: 160px;" onchange="loadOrderHistory()">
            <span class="text-muted">to</span>
            <input type="date" class="form-control" id="historyDateTo" style="width: 160px;" onchange="loadOrderHistory()">
            <select class="form-control" id="historyStatus" onchange="loadOrderHistory()" style="width: 150px;">
                <option value="">All Status</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>
    <div class="card">
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order #</th>
                        <th>Customer</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="orderHistoryBody">
                    <tr><td colspan="8" class="text-center text-muted" style="padding:40px;">Click "Order History" tab to load</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Order Detail Modal -->
<div class="modal-overlay" id="orderDetailModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="orderDetailTitle">Order Details</h3>
            <button class="modal-close" onclick="closeModal('orderDetailModal')">&times;</button>
        </div>
        <div class="modal-body" id="orderDetailBody">
            Loading...
        </div>
        <div class="modal-footer" id="orderDetailFooter"></div>
    </div>
</div>

<script>
    // ---- Cart State ----
    let cart = [];
    let customerOrdersRefreshTimer = null;

    // ---- Customer Orders Panel ----
    function loadCustomerOrders() {
        ajaxRequest('ajax/order_actions.php', { action: 'list', status: '' }, function(res) {
            const list = document.getElementById('customerOrdersList');
            const summaryQty = document.getElementById('coSummaryQty');
            const summaryAmt = document.getElementById('coSummaryAmount');

            if (!res.success || !res.data || res.data.length === 0) {
                list.innerHTML = `
                    <div style="text-align:center;padding:40px 0;color:var(--text-muted);">
                        <div style="font-size:2rem;margin-bottom:8px;">🛒</div>
                        <p>No customer orders yet.</p>
                    </div>`;
                summaryQty.textContent = '0';
                summaryAmt.textContent = '₱0.00';
                return;
            }

            // Only show customer orders (those with customer_name set)
            const customerOrders = res.data.filter(o => o.customer_name && o.customer_name.trim() !== '');

            let totalQty = 0, totalAmt = 0;
            customerOrders.forEach(o => {
                totalQty += parseInt(o.item_count || 0);
                totalAmt += parseFloat(o.total_amount || 0);
            });

            summaryQty.textContent = totalQty;
            summaryAmt.textContent = '₱' + totalAmt.toFixed(2);

            const statusColors = {
                pending:    { bg: '#FEF3C7', color: '#92400E' },
                processing: { bg: '#DBEAFE', color: '#1E40AF' },
                completed:  { bg: '#D1FAE5', color: '#065F46' },
                cancelled:  { bg: '#FEE2E2', color: '#991B1B' }
            };

            if (customerOrders.length === 0) {
                list.innerHTML = `
                    <div style="text-align:center;padding:40px 0;color:var(--text-muted);">
                        <div style="font-size:2rem;margin-bottom:8px;">🛒</div>
                        <p>No customer orders yet.</p>
                    </div>`;
                return;
            }

            list.innerHTML = customerOrders.map(order => {
                const sc = statusColors[order.status] || { bg: '#F3F4F6', color: '#374151' };
                return `
                <div style="background:#fff;border:1px solid rgba(0,0,0,0.08);border-radius:14px;padding:14px 16px;cursor:pointer;transition:box-shadow 0.2s;" onclick="viewOrder(${order.id})" onmouseover="this.style.boxShadow='0 4px 16px rgba(0,0,0,0.10)'" onmouseout="this.style.boxShadow='none'">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                        <div>
                            <div style="font-weight:700;color:var(--primary);font-size:0.9rem;">${escapeHtml(order.customer_name)}</div>
                            <div style="font-size:0.72rem;color:var(--text-muted);margin-top:2px;">ORD-${order.id.toString().padStart(4,'0')} · ${formatDate(order.created_at)}</div>
                        </div>
                        <span style="background:${sc.bg};color:${sc.color};padding:3px 10px;border-radius:50px;font-size:0.68rem;font-weight:700;text-transform:uppercase;letter-spacing:0.5px;white-space:nowrap;">${order.status}</span>
                    </div>
                    <div style="display:flex;gap:16px;margin-top:6px;">
                        <div style="display:flex;align-items:center;gap:5px;">
                            <span style="font-size:0.78rem;color:var(--text-muted);">🛒</span>
                            <span style="font-size:0.82rem;font-weight:600;color:var(--primary);">${order.item_count} item${order.item_count != 1 ? 's' : ''}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:5px;">
                            <span style="font-size:0.78rem;color:var(--text-muted);">💰</span>
                            <span style="font-size:0.9rem;font-weight:800;color:var(--accent);">₱${parseFloat(order.total_amount).toFixed(2)}</span>
                        </div>
                    </div>
                </div>`;
            }).join('');
        });
    }

    // Emoji map for categories
    const categoryEmojis = {
        'Hot Drinks': '☕',
        'Cold Drinks': '🧊',
        'Pastries': '🥐',
        'Snacks': '🥪'
    };

    // Load categories for order filter
    function loadOrderCategories() {
        ajaxRequest('ajax/menu_actions.php', { action: 'categories' }, function(res) {
            if (res.success) {
                const select = document.getElementById('orderCategoryFilter');
                res.data.forEach(cat => {
                    select.innerHTML += `<option value="${cat.id}">${cat.name}</option>`;
                });
            }
        });
    }

    // Load menu items for ordering
    function loadOrderMenu() {
        const search = document.getElementById('orderMenuSearch').value;
        const category = document.getElementById('orderCategoryFilter').value;

        ajaxRequest('ajax/menu_actions.php', {
            action: 'list',
            search: search,
            category_id: category
        }, function(res) {
            const grid = document.getElementById('orderMenuGrid');
            if (res.success && res.data.length > 0) {
                grid.innerHTML = res.data
                    .filter(item => item.available == 1)
                    .map(item => `
                        <div class="menu-item-card" onclick="addToCart(${item.id}, '${escapeHtml(item.name)}', ${item.price}, '${escapeHtml(item.category_name)}')">
                            <div class="item-emoji">${item.image ? `<img src="uploads/${item.image}" style="width:60px;height:60px;border-radius:8px;object-fit:cover;margin:0 auto;">` : (categoryEmojis[item.category_name] || '☕')}</div>
                            <div class="item-name">${escapeHtml(item.name)}</div>
                            <div class="item-price">₱${parseFloat(item.price).toFixed(2)}</div>
                            <div class="item-category">${escapeHtml(item.category_name)}</div>
                        </div>
                    `).join('');

                if (grid.innerHTML.trim() === '') {
                    grid.innerHTML = '<div class="text-center text-muted" style="grid-column:1/-1;padding:40px;">No available items found.</div>';
                }
            } else {
                grid.innerHTML = '<div class="text-center text-muted" style="grid-column:1/-1;padding:40px;">No items found.</div>';
            }
        });
    }

    // Add item to cart
    function addToCart(id, name, price, category) {
        const existing = cart.find(item => item.id === id);
        if (existing) {
            existing.qty++;
        } else {
            cart.push({ id, name, price: parseFloat(price), qty: 1, category });
        }
        renderCart();
        showToast(`${name} added to cart`, 'success');
    }

    // Update quantity
    function updateQty(id, delta) {
        const item = cart.find(i => i.id === id);
        if (item) {
            item.qty += delta;
            if (item.qty <= 0) {
                cart = cart.filter(i => i.id !== id);
            }
        }
        renderCart();
    }

    // Remove from cart
    function removeFromCart(id) {
        cart = cart.filter(i => i.id !== id);
        renderCart();
    }

    // Render cart
    function renderCart() {
        const container = document.getElementById('cartItems');
        const totalEl = document.getElementById('cartTotal');
        const placeBtn = document.getElementById('placeOrderBtn');

        if (cart.length === 0) {
            container.innerHTML = `
                <div class="cart-empty">
                    <div class="empty-icon">🛒</div>
                    <p>Cart is empty</p>
                    <p style="font-size: 0.78rem; margin-top: 4px;">Click on items to add them</p>
                </div>
            `;
            totalEl.textContent = '₱0.00';
            placeBtn.disabled = true;
            return;
        }

        let total = 0;
        container.innerHTML = cart.map(item => {
            const subtotal = item.price * item.qty;
            total += subtotal;
            return `
                <div class="cart-item">
                    <div class="cart-item-info">
                        <div class="cart-item-title">${escapeHtml(item.name)}</div>
                        <div class="cart-item-price">₱${item.price.toFixed(2)}</div>
                    </div>
                    
                    <div class="cart-item-actions">
                        <button class="qty-btn" onclick="updateQty(${item.id}, -1)">−</button>
                        <span style="font-weight:700; width:20px; text-align:center; font-size:0.95rem;">${item.qty}</span>
                        <button class="qty-btn" onclick="updateQty(${item.id}, 1)">+</button>
                    </div>

                    <div style="font-weight:700; color:var(--primary); min-width: 60px; text-align:right;">₱${subtotal.toFixed(2)}</div>
                    
                    <button class="action-btn delete" onclick="removeFromCart(${item.id})" title="Remove" style="margin-left: 10px;">
                        <svg class="icon-svg" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                    </button>
                </div>
            `;
        }).join('');

        totalEl.textContent = formatCurrency(total);
        placeBtn.disabled = false;
    }

    // Place order
    function placeOrder() {
        if (cart.length === 0) return;

        confirmAction('Place this order?', function() {
            const orderData = {
                action: 'place',
                items: JSON.stringify(cart.map(item => ({
                    menu_item_id: item.id,
                    quantity: item.qty,
                    price: item.price
                })))
            };

            ajaxRequest('ajax/order_actions.php', orderData, function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    cart = [];
                    renderCart();
                } else {
                    showToast(res.message, 'error');
                }
            });
        });
    }

    // Load order history
    function loadOrderHistory() {
        const dateFrom = document.getElementById('historyDateFrom').value;
        const dateTo = document.getElementById('historyDateTo').value;
        const status = document.getElementById('historyStatus').value;

        ajaxRequest('ajax/order_actions.php', {
            action: 'list',
            date_from: dateFrom,
            date_to: dateTo,
            status: status
        }, function(res) {
            const tbody = document.getElementById('orderHistoryBody');
            if (res.success && res.data.length > 0) {
                tbody.innerHTML = res.data.map(order => `
                    <tr>
                        <td><strong>ORD-${order.id.toString().padStart(4, '0')}</strong></td>
                        <td>
                            <div style="font-weight:600">${escapeHtml(order.customer_name || 'Walk-in')}</div>
                            ${order.contact ? `<div style="font-size:0.75rem;color:var(--text-muted)">${escapeHtml(order.contact)}</div>` : ''}
                        </td>
                        <td>${order.item_count} item(s)</td>
                        <td class="fw-600">₱${parseFloat(order.total_amount).toFixed(2)}</td>
                        <td><span style="background:#FEF3C7;color:#92400E;padding:2px 10px;border-radius:50px;font-size:0.72rem;font-weight:700;">COD</span></td>
                        <td><span class="badge badge-${order.status}">${capitalize(order.status)}</span></td>
                        <td>${formatDate(order.created_at)}</td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn view" onclick="viewOrder(${order.id})" title="View">👁️</button>
                                ${order.status === 'pending' ? `
                                    <button class="action-btn edit" onclick="updateOrderStatus(${order.id}, 'processing')" title="Processing">🔄</button>
                                    <button class="action-btn delete" onclick="updateOrderStatus(${order.id}, 'cancelled')" title="Cancel">❌</button>
                                ` : ''}
                                ${order.status === 'processing' ? `
                                    <button class="action-btn edit" onclick="updateOrderStatus(${order.id}, 'completed')" title="Complete">✅</button>
                                    <button class="action-btn delete" onclick="updateOrderStatus(${order.id}, 'cancelled')" title="Cancel">❌</button>
                                ` : ''}
                            </div>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted" style="padding:40px;">No orders found.</td></tr>';
            }
        });
    }

    // View order details
    function viewOrder(id) {
        ajaxRequest('ajax/order_actions.php', { action: 'details', id: id }, function(res) {
            if (res.success) {
                const order = res.data;
                document.getElementById('orderDetailTitle').textContent = `Order #${order.id.toString().padStart(4, '0')}`;

                let html = `
                    <div class="flex-between mb-2">
                        <span class="badge badge-${order.status}">${capitalize(order.status)}</span>
                        <span style="background:#FEF3C7;color:#92400E;padding:3px 12px;border-radius:50px;font-size:0.75rem;font-weight:700;">💵 Cash on Delivery</span>
                    </div>
                    <div style="background:var(--paper);border-radius:12px;padding:16px;margin-bottom:16px;">
                        <p style="margin:0 0 6px;font-size:0.75rem;text-transform:uppercase;letter-spacing:1px;color:var(--text-muted);font-weight:700;">Delivery Info</p>
                        <p style="margin:4px 0;"><strong>Name:</strong> ${escapeHtml(order.customer_name || 'N/A')}</p>
                        <p style="margin:4px 0;"><strong>Address:</strong> ${escapeHtml(order.address || 'N/A')}</p>
                        <p style="margin:4px 0;"><strong>Contact:</strong> ${escapeHtml(order.contact || 'N/A')}</p>
                        ${order.notes ? `<p style="margin:4px 0;"><strong>Notes:</strong> ${escapeHtml(order.notes)}</p>` : ''}
                    </div>
                    <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:12px;">Date: ${formatDate(order.created_at)}</div>
                    <table class="order-detail-items">
                        <thead><tr><th>Item</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr></thead>
                        <tbody>
                            ${order.items.map(item => `
                                <tr>
                                    <td>${escapeHtml(item.name)}</td>
                                    <td>₱${parseFloat(item.price).toFixed(2)}</td>
                                    <td>${item.quantity}</td>
                                    <td class="fw-600">₱${parseFloat(item.subtotal).toFixed(2)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    <div class="order-detail-total">
                        <span>Total</span>
                        <span>₱${parseFloat(order.total_amount).toFixed(2)}</span>
                    </div>
                `;

                document.getElementById('orderDetailBody').innerHTML = html;

                let footer = '<button class="btn btn-outline btn-sm" onclick="closeModal(\'orderDetailModal\')">Close</button>';
                if (order.status === 'pending') {
                    footer = `
                        <button class="btn btn-outline btn-sm" onclick="closeModal('orderDetailModal')">Close</button>
                        <button class="btn btn-primary btn-sm" onclick="updateOrderStatus(${order.id}, 'processing'); closeModal('orderDetailModal');">🔄 Mark Processing</button>
                        <button class="btn btn-danger btn-sm" onclick="updateOrderStatus(${order.id}, 'cancelled'); closeModal('orderDetailModal');">Cancel</button>
                    `;
                } else if (order.status === 'processing') {
                    footer = `
                        <button class="btn btn-outline btn-sm" onclick="closeModal('orderDetailModal')">Close</button>
                        <button class="btn btn-success btn-sm" onclick="updateOrderStatus(${order.id}, 'completed'); closeModal('orderDetailModal');">✅ Mark Completed</button>
                        <button class="btn btn-danger btn-sm" onclick="updateOrderStatus(${order.id}, 'cancelled'); closeModal('orderDetailModal');">Cancel</button>
                    `;
                }
                document.getElementById('orderDetailFooter').innerHTML = footer;

                openModal('orderDetailModal');
            }
        });
    }

    // Update order status
    function updateOrderStatus(id, status) {
        const action_label = status === 'completed' ? 'complete' : 'cancel';
        confirmAction(`Mark this order as ${status}?`, function() {
            ajaxRequest('ajax/order_actions.php', {
                action: 'update_status',
                id: id,
                status: status
            }, function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    loadOrderHistory();
                } else {
                    showToast(res.message, 'error');
                }
            });
        });
    }

    // Helper
    function capitalize(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function formatDate(dateStr) {
        const d = new Date(dateStr);
        return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }) +
               ' ' + d.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Init
    loadOrderCategories();
    loadOrderMenu();
    loadCustomerOrders();
    // Auto-refresh customer orders every 15 seconds
    customerOrdersRefreshTimer = setInterval(loadCustomerOrders, 15000);
</script>

<?php include 'includes/footer.php'; ?>
