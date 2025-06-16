# Database Schema Visualization

## Complete Entity Relationship Diagram

This diagram shows the complete database schema with all relationships and key fields for the My Dining application.

```mermaid
erDiagram
    users {
        bigint id PK
        string name
        string email UK
        bigint country_id FK
        string phone
        string gender
        string city
        string password
        timestamp email_verified_at
        timestamp created_at
        timestamp updated_at
    }
    
    countries {
        bigint id PK
        string name
        string code
        string dial_code UK
        timestamp created_at
        timestamp updated_at
    }
    
    messes {
        bigint id PK
        string name
        enum status
        boolean ad_free
        boolean all_user_add_meal
        boolean fund_add_enabled
        timestamp created_at
        timestamp updated_at
    }
    
    mess_users {
        bigint id PK
        bigint user_id FK
        bigint mess_id FK
        bigint mess_role_id FK
        timestamp joined_at
        timestamp left_at
        enum status
        timestamp created_at
        timestamp updated_at
    }
    
    mess_roles {
        bigint id PK
        bigint mess_id FK
        string role
        boolean is_default
        boolean is_admin
        timestamp created_at
        timestamp updated_at
    }
    
    mess_role_permissions {
        bigint id PK
        bigint mess_role_id FK
        string permission
        timestamp created_at
        timestamp updated_at
    }
    
    months {
        bigint id PK
        bigint mess_id FK
        string name
        enum type
        timestamp start_at
        timestamp end_at
        timestamp created_at
        timestamp updated_at
    }
    
    initiate_users {
        bigint id PK
        bigint month_id FK
        bigint mess_user_id FK
        bigint mess_id FK
        boolean active
        timestamp created_at
        timestamp updated_at
    }
    
    meals {
        bigint id PK
        bigint month_id FK
        bigint mess_user_id FK
        bigint mess_id FK
        date date
        decimal breakfast
        decimal lunch
        decimal dinner
        timestamp created_at
        timestamp updated_at
    }
    
    deposits {
        bigint id PK
        bigint month_id FK
        bigint mess_user_id FK
        bigint mess_id FK
        decimal amount
        date date
        integer type
        timestamp created_at
        timestamp updated_at
    }
    
    purchases {
        bigint id PK
        bigint month_id FK
        bigint mess_user_id FK
        bigint mess_id FK
        date date
        decimal price
        string product
        timestamp created_at
        timestamp updated_at
    }
    
    purchase_requests {
        bigint id PK
        bigint month_id FK
        bigint mess_user_id FK
        bigint mess_id FK
        date date
        decimal price
        string product
        json product_json
        enum purchase_type
        enum status
        boolean deposit_request
        text comment
        timestamp created_at
        timestamp updated_at
    }
    
    other_costs {
        bigint id PK
        bigint month_id FK
        bigint mess_user_id FK
        bigint mess_id FK
        date date
        decimal price
        string product
        timestamp created_at
        timestamp updated_at
    }
    
    funds {
        bigint id PK
        bigint month_id FK
        bigint mess_id FK
        date date
        decimal amount
        text comment
        timestamp created_at
        timestamp updated_at
    }

    %% Core Relationships
    countries ||--o{ users : "country_id"
    users ||--o{ mess_users : "user_id"
    messes ||--o{ mess_users : "mess_id"
    messes ||--o{ mess_roles : "mess_id"
    messes ||--o{ months : "mess_id"
    mess_roles ||--o{ mess_users : "mess_role_id"
    mess_roles ||--o{ mess_role_permissions : "mess_role_id"
    
    %% Month-based relationships
    months ||--o{ initiate_users : "month_id"
    months ||--o{ meals : "month_id"
    months ||--o{ deposits : "month_id"
    months ||--o{ purchases : "month_id"
    months ||--o{ purchase_requests : "month_id"
    months ||--o{ other_costs : "month_id"
    months ||--o{ funds : "month_id"
    
    %% User-based financial relationships
    mess_users ||--o{ initiate_users : "mess_user_id"
    mess_users ||--o{ meals : "mess_user_id"
    mess_users ||--o{ deposits : "mess_user_id"
    mess_users ||--o{ purchases : "mess_user_id"
    mess_users ||--o{ purchase_requests : "mess_user_id"
    mess_users ||--o{ other_costs : "mess_user_id"
    
    %% Mess-based relationships (for data isolation)
    messes ||--o{ initiate_users : "mess_id"
    messes ||--o{ meals : "mess_id"
    messes ||--o{ deposits : "mess_id"
    messes ||--o{ purchases : "mess_id"
    messes ||--o{ purchase_requests : "mess_id"
    messes ||--o{ other_costs : "mess_id"
    messes ||--o{ funds : "mess_id"
```

## Simplified Core Architecture

This simplified diagram shows the main entity relationships without all the detailed fields:

```mermaid
graph TD
    U[User] --> MU[MessUser]
    C[Country] --> U
    
    M[Mess] --> MU
    M --> MR[MessRole]
    M --> MON[Month]
    
    MR --> MRP[MessRolePermission]
    MR --> MU
    
    MON --> IU[InitiateUser]
    MON --> MEA[Meal]
    MON --> D[Deposit]
    MON --> P[Purchase]
    MON --> PR[PurchaseRequest]
    MON --> OC[OtherCost]
    MON --> F[Fund]
    
    MU --> IU
    MU --> MEA
    MU --> D
    MU --> P
    MU --> PR
    MU --> OC
    
    style U fill:#e1f5fe
    style M fill:#f3e5f5
    style MON fill:#e8f5e8
    style MU fill:#fff3e0
    style MR fill:#fce4ec
```

## Data Flow Architecture

This diagram shows how data flows through the system during typical operations:

```mermaid
flowchart TD
    A[User Registration] --> B[Country Selection]
    B --> C[Mess Creation/Join]
    C --> D[Role Assignment]
    
    D --> E[Month Creation]
    E --> F[User Initiation]
    
    F --> G[Daily Operations]
    G --> H[Meal Logging]
    G --> I[Financial Transactions]
    
    I --> J[Deposits]
    I --> K[Purchase Requests]
    I --> L[Purchase Approvals]
    I --> M[Actual Purchases]
    I --> N[Other Costs]
    I --> O[Fund Management]
    
    H --> P[Monthly Reports]
    I --> P
    J --> P
    M --> P
    N --> P
    O --> P
    
    P --> Q[Summary Generation]
    Q --> R[User Balances]
    Q --> S[Mess Financials]
    
    style A fill:#ffebee
    style E fill:#e8f5e8
    style G fill:#e3f2fd
    style P fill:#f1f8e9
    style Q fill:#fff8e1
```

## Permission & Access Control

This shows the role-based access control structure:

```mermaid
graph LR
    M[Mess] --> AR[Admin Role]
    M --> MR[Manager Role]
    M --> MEM[Member Role]
    
    AR --> ARP[Admin Permissions]
    MR --> MAP[Manager Permissions]
    MEM --> MEP[Member Permissions]
    
    ARP --> U1[User 1]
    MAP --> U2[User 2]
    MEP --> U3[User 3]
    MEP --> U4[User 4]
    
    U1 --> AC1[All Actions]
    U2 --> AC2[Management Actions]
    U3 --> AC3[Basic Actions]
    U4 --> AC3
    
    style AR fill:#ffcdd2
    style MR fill:#fff9c4
    style MEM fill:#dcedc8
    style AC1 fill:#ffebee
    style AC2 fill:#fff8e1
    style AC3 fill:#f1f8e9
```

## Database Indexes & Performance

Key indexes for optimal performance:

```sql
-- User lookups
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_country ON users(country_id);

-- Mess user relationships
CREATE INDEX idx_mess_users_user ON mess_users(user_id);
CREATE INDEX idx_mess_users_mess ON mess_users(mess_id);
CREATE INDEX idx_mess_users_active ON mess_users(mess_id, status, left_at);

-- Month-based queries
CREATE INDEX idx_months_mess_active ON months(mess_id, start_at, end_at);

-- Financial transactions (month-based)
CREATE INDEX idx_meals_month_user ON meals(month_id, mess_user_id);
CREATE INDEX idx_deposits_month_user ON deposits(month_id, mess_user_id);
CREATE INDEX idx_purchases_month_user ON purchases(month_id, mess_user_id);
CREATE INDEX idx_other_costs_month_user ON other_costs(month_id, mess_user_id);

-- Date-based queries
CREATE INDEX idx_meals_date ON meals(date);
CREATE INDEX idx_deposits_date ON deposits(date);
CREATE INDEX idx_purchases_date ON purchases(date);

-- Purchase request workflow
CREATE INDEX idx_purchase_requests_status ON purchase_requests(status);
CREATE INDEX idx_purchase_requests_month_status ON purchase_requests(month_id, status);
```

## Common Query Patterns

### 1. Get User's Current Mess Info
```sql
SELECT u.*, m.name as mess_name, mr.role, mr.is_admin
FROM users u
JOIN mess_users mu ON u.id = mu.user_id
JOIN messes m ON mu.mess_id = m.id
JOIN mess_roles mr ON mu.mess_role_id = mr.id
WHERE u.id = ? AND mu.left_at IS NULL;
```

### 2. Get Month Financial Summary
```sql
-- Deposits
SELECT SUM(amount) as total_deposits FROM deposits WHERE month_id = ?;

-- Purchases  
SELECT SUM(price) as total_purchases FROM purchases WHERE month_id = ?;

-- Other Costs
SELECT SUM(price) as total_other_costs FROM other_costs WHERE month_id = ?;

-- Funds
SELECT SUM(amount) as total_funds FROM funds WHERE month_id = ?;
```

### 3. Get User's Monthly Meal Count
```sql
SELECT 
    SUM(breakfast) as total_breakfast,
    SUM(lunch) as total_lunch,
    SUM(dinner) as total_dinner
FROM meals 
WHERE month_id = ? AND mess_user_id = ?;
```

This database schema supports the complete mess management workflow with proper normalization, referential integrity, and performance optimization.
