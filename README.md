# Customer Portal Overview & Features

This document explains the technical architecture, features, and configuration of the newly implemented **Customer Dashboard** for the Yours Truly Coffee Shop management system. 

This dashboard transforms the static ordering system into a highly personalized, interactive portal where registered customers can manage their orders seamlessly.

---

## 1. The Customer Dashboard Experience

When a customer creates an account or logs in, they are redirected to their specialized portal (`customer_dashboard.php`). This dashboard replaces the public storefront for registered users, giving them a premium, dedicated interface.

### Key Features:
- **Member Pricing & Menu Access:** Customers can browse a streamlined version of the menu directly from their dashboard.
- **Floating Cart System:** An intuitive, unified cart interface persists across the screen, allowing users to bundle their items before checking out.
- **Detailed History Logging:** Users can view a full history of their past purchases and total amount spent inside their account.

---

## 2. Dynamic Delivery Zones & Smart ETA Integration

The platform no longer relies on arbitrary delivery addresses. It features verified delivery location configuration restricted to **Pagadian City**.

### Configuration via JSON
The list of available barangays and their delivery zones are natively stored in a configuration file rather than hardcoded in the database or JavaScript.
- **File Location:** `assets/data/pagadian_barangays.json`
- **How to Update:** To add or remove delivery zones, simply modify this JSON file. The frontend automatically fetches this list upon loading the dashboard or checkout window.

### Smart ETA Logic
The application automatically computes estimated delivery times based on the customer's selected geographical zone:
- **Near Zone:** 20–30 Minutes
- **Standard Zone:** 30–45 Minutes
- **Distant Zone:** 45–60 Minutes

The generated ETA is dynamically broadcasted on the frontend checkout screen and pushed to the backend `estimated_minutes` database schema when placing an order.

---

## 3. Real-Time Order Tracking

Customers can track their coffee order live as the admin fulfills it through the admin portal.

### The Lifecycle States
The system utilizes server-to-server AJAX polling to continuously map the backend order status directly to the customer's UI:
1. **Pending/Processing:** Order received and backend fulfillment has begun. Admin updates this via `admin_dashboard.php`.
2. **Completed:** Rider is dispatched or delivery has successfully finalized.
3. **Refund Requested:** User escalated a refund payload.
4. **Cancelled/Refunded:** Securely rejected or reimbursed orders.

### No Re-rendering Need
The customer dashboard utilizes `setInterval()` logic to passively ping `ajax/order_actions.php` and refresh the dashboard data seamlessly in the background without needing to refresh the page.

---

## 4. Accelerated Checkout (PayMongo & Cash on Delivery)

When a customer checks out, the backend instantly calculates limits and redirects them securely.

### Cash On Delivery Restrictions
> [!IMPORTANT]
> **₱200 Minimum Limit:** Cash on Delivery (COD) is strictly restricted to orders totaling ₱200 or above.

This minimum balance order logic is dynamically established in JavaScript to prevent low-value orders from wasting rider trips:
- If an order is strictly under **₱200**, the "Cash on Delivery" option is greyed out and visually locked gracefully. The system will forcefully default to Secure E-Wallet.

### E-Wallet (PayMongo Integration)
- If the customer picks **E-wallet**, the server creates a PayMongo checkout session utilizing `callPayMongo()` configured inside `config/paymongo.php`.
- The frontend triggers a loading modal wrapper containing the secure PayMongo payment iframe.
- **Smart Polling Mechanism:** The browser will continually ping the local server to verify if PayMongo has confirmed the payload. Once the backend gets the `paid` status, the frontend auto-detects it, removes the iframe seamlessly, clears the user's cart cache, and refreshes their history interface cleanly showing the new order.

---

## 5. Automated Refunds Functionality

Customers are granted autonomy to initiate refund requests if there is a severe delay or an issue natively from their active orders.

1. **Initiating:** The customer clicks the "Request Refund" red button directly on their active order widget.
2. **Secure Validation:** SweetAlert prompts a final warning. If confirmed, an AJAX payload signals `action: request_refund` and binds it to the unique `order_id`.
3. **Database Escrow:** The order `status` schema safely transits from `pending/processing` to `refund_requested`. 
4. **Admin Approval:** The Administrator sees the visual "Refund Requested" flag in the backend portal and has the power and authority to resolve the financial dispute fully. 

---
_Documentation automatically generated to outline the V9 Architectural Updates._
