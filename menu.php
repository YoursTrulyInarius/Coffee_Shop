<?php
require_once 'includes/auth_check.php';
checkAuth('admin');
$pageTitle = 'Menu Management';
include 'includes/header.php';
?>

<!-- Toolbar -->
<div class="toolbar">
    <div class="search-box">
        <span class="search-icon">🔍</span>
        <input type="text" id="menuSearch" placeholder="Search menu items..." onkeyup="loadMenuItems()">
    </div>
    <div class="filter-group">
        <button class="btn btn-complement" onclick="openAddModal()">+ Add Item</button>
    </div>
</div>

<!-- Menu Items Table -->
<div class="card">
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="menuTableBody">
                <tr>
                    <td colspan="5" class="text-center text-muted" style="padding: 40px;">Loading...</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Menu Item Modal -->
<div class="modal-overlay" id="menuModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="menuModalTitle">Add Menu Item</h3>
            <button class="modal-close" onclick="closeModal('menuModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="menuForm" enctype="multipart/form-data">
                <input type="hidden" id="itemId" name="id">
                <input type="hidden" name="action" id="formAction" value="create">

                <div class="form-group">
                    <label for="itemName">Item Name</label>
                    <input type="text" id="itemName" name="name" class="form-control" placeholder="e.g. Cappuccino" required>
                </div>

                <div class="form-group">
                    <label for="itemCategory">Category</label>
                    <div class="combobox-container" id="categoryCombobox">
                        <input type="text" id="itemCategory" name="category_name" class="form-control" placeholder="Select or type category..." required autocomplete="off">
                        <span class="dropdown-arrow">▼</span>
                        <div class="custom-dropdown-list" id="categoryDropdownList"></div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="itemPrice">Price (₱)</label>
                    <input type="number" id="itemPrice" name="price" class="form-control" placeholder="0.00" step="0.01" min="0" required>
                </div>

                <div class="form-group">
                    <label for="itemDescription">Description</label>
                    <textarea id="itemDescription" name="description" class="form-control" placeholder="Short description of the item"></textarea>
                </div>

                <div class="form-group">
                    <label for="itemImage">Image</label>
                    <input type="file" id="itemImage" name="image" class="form-control" accept="image/*" onchange="previewImage(this, 'imagePreview')">
                    <div class="image-preview" id="imagePreview">
                        <span>No image selected</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="itemAvailable">Availability</label>
                    <select id="itemAvailable" name="available" class="form-control">
                        <option value="1">Available</option>
                        <option value="0">Unavailable</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline btn-sm" onclick="closeModal('menuModal')">Cancel</button>
            <button class="btn btn-complement btn-sm" onclick="saveMenuItem()">Save Item</button>
        </div>
    </div>
</div>

<script>
    // Load categories for filter, form, and management list
    const hardcodedCategories = ['Hot Coffee', 'Iced Coffee', 'Pastries', 'Sandwiches', 'Tea', 'Snacks'];
    let allCategoryNames = [...hardcodedCategories]; // Start with defaults
    
    function loadCategories() {
        // Render defaults immediately
        renderCategoryDropdown(allCategoryNames);
        
        ajaxRequest('ajax/menu_actions.php', { action: 'categories' }, function(res) {
            if (res.success) {
                const dbCategories = res.data.map(cat => cat.name);
                allCategoryNames = [...new Set([...hardcodedCategories, ...dbCategories])].sort();
                renderCategoryDropdown(allCategoryNames);
            }
        });
    }

    function renderCategoryDropdown(categories) {
        const list = document.getElementById('categoryDropdownList');
        if (!list) return;

        if (categories.length === 0) {
            list.innerHTML = '<div class="dropdown-item no-results">No categories matched</div>';
        } else {
            list.innerHTML = categories.map(cat => `
                <div class="dropdown-item" onclick="selectCategory('${escapeHtml(cat)}')">${escapeHtml(cat)}</div>
            `).join('');
        }
    }

    function selectCategory(name) {
        const input = document.getElementById('itemCategory');
        if (input) {
            input.value = name;
            input.dispatchEvent(new Event('change')); // Trigger any change listeners
        }
        closeCategoryDropdown();
    }

    function toggleCategoryDropdown() {
        const container = document.getElementById('categoryCombobox');
        const list = document.getElementById('categoryDropdownList');
        if (!container || !list) return;
        
        container.classList.toggle('active');
        list.classList.toggle('active');
    }

    function closeCategoryDropdown() {
        const container = document.getElementById('categoryCombobox');
        const list = document.getElementById('categoryDropdownList');
        if (container) container.classList.remove('active');
        if (list) list.classList.remove('active');
    }

    // Event Listeners for Combobox
    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('itemCategory');
        const container = document.getElementById('categoryCombobox');
        const arrow = document.querySelector('.dropdown-arrow');
        
        if (input) {
            input.addEventListener('click', function(e) {
                e.stopPropagation();
                // If already open, keep open. If closed, open.
                const list = document.getElementById('categoryDropdownList');
                if (list && !list.classList.contains('active')) {
                    renderCategoryDropdown(allCategoryNames); // Show all when clicking
                    container.classList.add('active');
                    list.classList.add('active');
                }
            });

            input.addEventListener('input', function() {
                const val = this.value.toLowerCase();
                const filtered = allCategoryNames.filter(cat => cat.toLowerCase().includes(val));
                renderCategoryDropdown(filtered);
                
                const list = document.getElementById('categoryDropdownList');
                if (list) {
                    container.classList.add('active');
                    list.classList.add('active');
                }
            });
        }

        if (arrow) {
            arrow.addEventListener('click', function(e) {
                e.stopPropagation();
                toggleCategoryDropdown();
            });
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (container && !container.contains(e.target)) {
                closeCategoryDropdown();
            }
        });
    });

    function addCategory() {
        // Obsolete but kept as shell if needed, actually handled by saveMenuItem
    }


    function loadMenuItems() {
        const search = document.getElementById('menuSearch').value;

        ajaxRequest('ajax/menu_actions.php', {
            action: 'list',
            search: search,
            category_id: 0
        }, function(res) {
            const tbody = document.getElementById('menuTableBody');
            if (res.success && res.data && res.data.length > 0) {
                tbody.innerHTML = res.data.map(item => `
                    <tr>
                        <td>
                            <div class="flex gap-1" style="align-items: center;">
                                ${item.image ? `<img src="uploads/${item.image}" style="width:40px;height:40px;border-radius:6px;object-fit:cover;">` : '<span style="font-size:1.8rem;">☕</span>'}
                                <div>
                                    <strong>${escapeHtml(item.name)}</strong>
                                    <div class="text-muted" style="font-size:0.78rem;">${escapeHtml(item.description || '')}</div>
                                </div>
                            </div>
                        </td>
                        <td>${escapeHtml(item.category_name)}</td>
                        <td class="fw-600">₱${parseFloat(item.price).toFixed(2)}</td>
                        <td>
                            <span class="badge badge-${item.available == 1 ? 'available' : 'unavailable'}">
                                ${item.available == 1 ? 'Available' : 'Unavailable'}
                            </span>
                        </td>
                        <td>
                            <div class="action-btns">
                                <button class="action-btn edit" onclick="editItem(${item.id})" title="Edit">
                                    <svg class="icon-svg" viewBox="0 0 24 24"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
                                </button>
                                <button class="action-btn delete" onclick="deleteItem(${item.id}, '${escapeHtml(item.name)}')" title="Delete">
                                    <svg class="icon-svg" viewBox="0 0 24 24"><path d="M19 4h-3.5l-1-1h-5l-1 1H5v2h14V4zM6 19a2 2 0 002 2h8a2 2 0 002-2V7H6v12z"/></svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted" style="padding:40px;">No menu items found.</td></tr>';
            }
        });
    }

    function openAddModal() {
        document.getElementById('menuModalTitle').textContent = 'Add Menu Item';
        document.getElementById('formAction').value = 'create';
        document.getElementById('menuForm').reset();
        document.getElementById('itemId').value = '';
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = `
            <div class="flex flex-col items-center gap-1">
                <svg class="icon-svg" style="width:48px;height:48px;opacity:0.2;" viewBox="0 0 24 24"><path d="M21 19V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                <span>No image selected</span>
            </div>
        `;
        preview.classList.remove('has-image');
        openModal('menuModal');
    }

    // Edit item
    function editItem(id) {
        ajaxRequest('ajax/menu_actions.php', { action: 'read', id: id }, function(res) {
            if (res.success) {
                const item = res.data;
                document.getElementById('menuModalTitle').textContent = 'Edit Menu Item';
                document.getElementById('formAction').value = 'update';
                document.getElementById('itemId').value = item.id;
                document.getElementById('itemName').value = item.name;
                document.getElementById('itemCategory').value = item.category_name;
                document.getElementById('itemPrice').value = item.price;
                document.getElementById('itemDescription').value = item.description || '';
                document.getElementById('itemAvailable').value = item.available;

                const preview = document.getElementById('imagePreview');
                if (item.image) {
                    preview.innerHTML = `<img src="uploads/${item.image}" alt="Preview">`;
                    preview.classList.add('has-image');
                } else {
                    preview.innerHTML = `
                        <div class="flex flex-col items-center gap-1">
                            <svg class="icon-svg" style="width:48px;height:48px;opacity:0.2;" viewBox="0 0 24 24"><path d="M21 19V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                            <span>No image uploaded</span>
                        </div>
                    `;
                    preview.classList.remove('has-image');
                }

                openModal('menuModal');
            }
        });
    }

    // Save (create or update)
    function saveMenuItem() {
        const form = document.getElementById('menuForm');
        const formData = new FormData(form);

        if (!formData.get('name') || !formData.get('category_name') || !formData.get('price')) {
            showToast('Please fill in name, category, and price.', 'error');
            return;
        }

        ajaxRequest('ajax/menu_actions.php', formData, function(res) {
            if (res.success) {
                showToast(res.message, 'success');
                closeModal('menuModal');
                loadCategories(); // Refresh datalist
                loadMenuItems();
            } else {
                showToast(res.message, 'error');
            }
        });
    }

    // Delete item
    function deleteItem(id, name) {
        confirmAction(`Delete "${name}" from the menu?`, function() {
            ajaxRequest('ajax/menu_actions.php', { action: 'delete', id: id }, function(res) {
                if (res.success) {
                    showToast(res.message, 'success');
                    loadMenuItems();
                } else {
                    showToast(res.message, 'error');
                }
            });
        });
    }

    // Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Init
    document.addEventListener('DOMContentLoaded', function() {
        loadCategories();
        loadMenuItems();
    });
</script>

<?php include 'includes/footer.php'; ?>
