
function toggleHeaderMenu() {
    const menu = document.getElementById('mobileMenu');
    if (menu) {
        menu.classList.toggle('active');
    }
}

// ---------- Toast Notifications ----------
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(40px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ---------- Modal Helpers ----------
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.add('active');
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) modal.classList.remove('active');
}

// Close modal on overlay click
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
});

// ---------- AJAX Helper ----------
function ajaxRequest(url, data, callback, method = 'POST') {
    const xhr = new XMLHttpRequest();
    xhr.open(method, url, true);

    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    callback(response);
                } catch (e) {
                    console.error('JSON parse error:', e);
                    console.error('Response:', xhr.responseText);
                    showToast('An error occurred. Please try again.', 'error');
                }
            } else {
                showToast('Server error. Please try again.', 'error');
            }
        }
    };

    if (method === 'POST') {
        if (data instanceof FormData) {
            xhr.send(data);
        } else {
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            const params = new URLSearchParams(data).toString();
            xhr.send(params);
        }
    } else {
        xhr.send();
    }
}

// ---------- Confirm Dialog ----------
function confirmAction(message, onConfirm) {
    const overlay = document.getElementById('confirmModal');
    const msg = document.getElementById('confirmMessage');
    const btnYes = document.getElementById('confirmYes');

    if (!overlay || !msg || !btnYes) return onConfirm();

    msg.textContent = message;
    overlay.classList.add('active');

    // Remove old listeners
    const newBtn = btnYes.cloneNode(true);
    btnYes.parentNode.replaceChild(newBtn, btnYes);

    newBtn.addEventListener('click', function () {
        overlay.classList.remove('active');
        onConfirm();
    });
}

// ---------- Tab Switching ----------
function switchTab(tabGroup, tabName) {
    const btns = document.querySelectorAll(`[data-tab-group="${tabGroup}"]`);
    const contents = document.querySelectorAll(`[data-tab-content-group="${tabGroup}"]`);

    btns.forEach(btn => {
        btn.classList.toggle('active', btn.dataset.tab === tabName);
    });

    contents.forEach(content => {
        content.classList.toggle('active', content.dataset.tabContent === tabName);
    });
}

// ---------- Format Currency ----------
function formatCurrency(amount) {
    return '₱' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
}

// ---------- Image Preview ----------
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    if (!preview) return;

    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;
            preview.classList.add('has-image');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.innerHTML = `
            <div class="flex flex-col items-center gap-1">
                <svg class="icon-svg" style="width:48px;height:48px;opacity:0.2;" viewBox="0 0 24 24"><path d="M21 19V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                <span>No image selected</span>
            </div>
        `;
        preview.classList.remove('has-image');
    }
}

// ---------- Close mobile menu on window resize ----------
window.addEventListener('resize', function () {
    if (window.innerWidth > 992) {
        const menu = document.getElementById('mobileMenu');
        if (menu) menu.classList.remove('active');
    }
});
