# ☕ Coffee Shop Management System

An artisanal storefront and management system designed for bespoke coffee businesses. This system focuses on high-fidelity user experiences and streamlines inventory and order management with a specialized focus on Cash on Delivery (COD) services.

---

## 🏗️ System Architecture

The system follows a classic LAMP stack pattern with a focus on real-time asynchronous interactions.

```mermaid
graph TD
    Client[Browser / User] <--> Server[PHP Backend]
    Server <--> DB[(MySQL Database)]
    Client -- AJAX/Fetch API --> Server
    Server -- mysqli --> DB
    style Client fill:#f9f,stroke:#333,stroke-width:2px
    style Server fill:#bbf,stroke:#333,stroke-width:2px
    style DB fill:#dfd,stroke:#333,stroke-width:2px
```

---

## 🌟 Key Features

### 🛒 Customer Multi-Item Cart
- **Floating Cart Bar**: A real-time summary of the current order at the bottom of the screen.
- **Dynamic Quantity Control**: Customers can add/remove items and adjust quantities directly from the menu or cart preview.
- **Auth Gate**: Seamless registration and login flow integrated into the checkout process.

### 🛍️ Secure Checkout (COD)
- **Delivery Management**: Captures Full Name, Address, and Contact Number.
- **Input Validation**: Contact numbers are restricted to numerical input with an 11-digit limit.
- **COD Exclusivity**: Designed specifically for regional Cash on Delivery business models.

### 📊 Admin Orchestration
- **Order Lifecycle**: 3-stage status management (`Pending` → `Processing` → `Completed`).
- **Real-Time Dash**: Immediate visibility into customer details and delivery requirements.
- **Sales Analytics**: Daily revenue tracking based on completed order history.

---

## 📊 Database Relations (ERD)

The database consists of 5 core tables managing users, inventory, and sales.

```mermaid
erDiagram
    USERS ||--o{ ORDERS : places
    CATEGORIES ||--|{ MENU_ITEMS : contains
    ORDERS ||--|{ ORDER_ITEMS : includes
    MENU_ITEMS ||--o{ ORDER_ITEMS : "sold as"

    USERS {
        int id PK
        string username
        string password
        string full_name
        enum role "admin, customer"
        timestamp created_at
    }

    CATEGORIES {
        int id PK
        string name
    }

    MENU_ITEMS {
        int id PK
        int category_id FK
        string name
        text description
        decimal price
        string image
        bool available
    }

    ORDERS {
        int id PK
        int user_id FK
        string customer_name
        text address
        string contact
        enum status "pending, processing, completed, cancelled"
        decimal total_amount
        timestamp created_at
    }

    ORDER_ITEMS {
        int id PK
        int order_id FK
        int menu_item_id FK
        int quantity
        decimal price
        decimal subtotal
    }
```

---

## 🔄 Order Lifecycle Flowchart

```mermaid
sequenceDiagram
    participant C as Customer
    participant S as System (Cart)
    participant A as Admin Dashboard

    C->>S: Adds items to cart
    C->>S: Clicks Checkout
    S-->>C: Prompt Login/Register (if guest)
    C->>S: Provides Delivery Info
    S->>A: Order appears (Status: Pending)
    A->>A: Process Order (Status: Processing)
    A->>A: Deliver & Collect (Status: Completed)
    S-->>C: Order History Updated
```

---

## 🛠️ Getting Started

### Prerequisites
- XAMPP / WAMP / LAMP environment.
- PHP 7.4+ and MySQL.

### Installation
1.  **Clone** this repository to your server's web root.
2.  **Database Configuration**:
    - Create a database named `coffee_shop`.
    - Import `coffee_shop_db.sql` found in the root directory.
    - Configuration is handled in `config/database.php`.
3.  **Access Management**:
    - **Guest/Customer**: Access `index.php` to browse and order.
    - **Admin**: Login via `login.php` with credentials: **admin** / **admin123**.

---


aaaaaa

