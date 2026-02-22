<?php
require_once 'includes/auth_check.php';
checkAuth('admin');
$pageTitle = 'Sales Report';
include 'includes/header.php';
?>

<!-- Filters -->
<div class="toolbar">
    <div class="filter-group">
        <label style="font-weight: 600; font-size: 0.85rem; color: var(--gray-600);">From:</label>
        <input type="date" class="form-control" id="salesDateFrom" style="width: 160px;" value="<?php echo date('Y-m-01'); ?>">
        <label style="font-weight: 600; font-size: 0.85rem; color: var(--gray-600);">To:</label>
        <input type="date" class="form-control" id="salesDateTo" style="width: 160px;" value="<?php echo date('Y-m-d'); ?>">
        <button class="btn btn-complement btn-sm" onclick="loadSalesReport()">Generate Report</button>
    </div>
</div>

<!-- Summary Cards -->
<div class="sales-summary">
    <div class="summary-card">
        <div class="summary-value" id="totalSales">₱0.00</div>
        <div class="summary-label">Total Sales</div>
    </div>
    <div class="summary-card">
        <div class="summary-value" id="totalOrders">0</div>
        <div class="summary-label">Total Orders</div>
    </div>
    <div class="summary-card">
        <div class="summary-value" id="avgOrder">₱0.00</div>
        <div class="summary-label">Average Order Value</div>
    </div>
</div>

<!-- Sales Table -->
<div class="card">
    <div class="card-header">
        <h3>Sales Breakdown</h3>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Orders</th>
                    <th>Completed</th>
                    <th>Cancelled</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody id="salesTableBody">
                <tr><td colspan="5" class="text-center text-muted" style="padding:40px;">Click "Generate Report" to load data</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Top Items -->
<div class="card mt-3">
    <div class="card-header">
        <h3>Top Selling Items</h3>
    </div>
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Item Name</th>
                    <th>Category</th>
                    <th>Qty Sold</th>
                    <th>Revenue</th>
                </tr>
            </thead>
            <tbody id="topItemsBody">
                <tr><td colspan="5" class="text-center text-muted" style="padding:40px;">Click "Generate Report" to load data</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    function loadSalesReport() {
        const dateFrom = document.getElementById('salesDateFrom').value;
        const dateTo = document.getElementById('salesDateTo').value;

        if (!dateFrom || !dateTo) {
            showToast('Please select a date range.', 'error');
            return;
        }

        ajaxRequest('ajax/sales_actions.php', {
            action: 'report',
            date_from: dateFrom,
            date_to: dateTo
        }, function(res) {
            if (res.success) {
                // Update summary cards
                document.getElementById('totalSales').textContent = formatCurrency(res.summary.total_sales);
                document.getElementById('totalOrders').textContent = res.summary.total_orders;
                document.getElementById('avgOrder').textContent = formatCurrency(res.summary.avg_order);

                // Update daily breakdown
                const tbody = document.getElementById('salesTableBody');
                if (res.daily.length > 0) {
                    tbody.innerHTML = res.daily.map(day => `
                        <tr>
                            <td class="fw-600">${formatSimpleDate(day.order_date)}</td>
                            <td>${day.total_orders}</td>
                            <td class="text-success fw-600">${day.completed_orders}</td>
                            <td class="text-danger">${day.cancelled_orders}</td>
                            <td class="fw-600">₱${parseFloat(day.revenue).toFixed(2)}</td>
                        </tr>
                    `).join('');
                } else {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted" style="padding:40px;">No sales data found for this period.</td></tr>';
                }

                // Update top items
                const topBody = document.getElementById('topItemsBody');
                if (res.top_items.length > 0) {
                    topBody.innerHTML = res.top_items.map((item, idx) => `
                        <tr>
                            <td class="fw-600">${idx + 1}</td>
                            <td class="fw-600">${escapeHtml(item.name)}</td>
                            <td>${escapeHtml(item.category_name)}</td>
                            <td>${item.total_qty}</td>
                            <td class="fw-600">₱${parseFloat(item.total_revenue).toFixed(2)}</td>
                        </tr>
                    `).join('');
                } else {
                    topBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted" style="padding:40px;">No items sold in this period.</td></tr>';
                }
            } else {
                showToast(res.message, 'error');
            }
        });
    }

    function formatSimpleDate(dateStr) {
        const d = new Date(dateStr + 'T00:00:00');
        return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Auto-load report on page load
    loadSalesReport();
</script>

<?php include 'includes/footer.php'; ?>
