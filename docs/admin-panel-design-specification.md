# 🍽️ Admin Panel Design Specification  
### *Dining Management System*

---

## 1. 🎯 Introduction: Designing for Control & Clarity

Welcome, **Designer**!  
This guide will help you craft an admin panel that is **powerful**, **intuitive**, and **beautiful**—making complex dining management feel effortless.

---

### **Core Goals**
- **Empower Admins:** Efficiently manage users, messes, finances, and content.
- **Simplify Complexity:** Turn intricate data relationships into clear, actionable workflows.
- **Ensure Accuracy:** Minimize errors, promote correct data entry.
- **Provide Insight:** Deliver dashboards and reports for smart decisions.

---

### **Admin Personas**
| Persona      | Focus Area                                 | Needs                                  |
|--------------|--------------------------------------------|----------------------------------------|
| **Alex**     | Super Admin                                | System-wide control, deep data access  |
| **Sarah**    | Mess Manager/Support                       | Mess-specific ops, member management   |
| **David**    | Finance Admin                              | Subscriptions, plans, revenue tracking |
| **Chloe**    | Content Manager                            | App-wide content, banners, guides      |

---

## 2. 🌐 The World We're Managing: Key Concepts

> **Tip:** For field-level details, see the [Model Fields and Descriptions](../models/fields.md).

### **Mess (Dining Group)**
- *Fields:* `name`, `status`, `ad_free`, `all_user_add_meal`
- *Design:* "Mess Hub"—all views/actions scoped to the selected Mess.

### **Users & MessUser (Roles in Mess)**
- *User Fields:* `name`, `email`, `phone`, `country_id`
- *MessUser Fields:* `mess_role_id`, `status`, `joined_at`, `left_at`
- *Design:*  
  - Global User Profile: List all Messes and roles.
  - Mess Hub: Show MessUser details for that Mess.

### **Months (Operational Periods)**
- *Fields:* `name`, `start_at`, `end_at`, `type`, `is_active`
- *Design:*  
  - All meal/finance management is within a Month context.

### **Subscriptions & Plans**
- *Subscription Fields:* `plan_package_id`, `start_date`, `end_date`, `status`
- *Plan Fields:* `name`, `keyword`, `is_free`
- *PlanFeature Fields:* `name`, `is_countable`, `usage_limit`
- *Design:*  
  - Global: Manage Plans, Packages, Features.
  - Mess Hub: View/manage current Subscription.

### **Content & Settings**
- *MainSlider:* `thumb_url`, `action_url`, `title`
- *UserGuide:* `title`, `thumb_url`, `action_url`
- *Banner:* `image_url`, `action_url`, `visible`
- *Country:* `name`, `code`, `dial_code`
- *Role:* `role`, `is_admin`
- *Setting:* `key`, `value`, `type`
- *Design:*  
  - Managed in global sections, not tied to a Mess.

### **Financials & Requests**
- *Deposits/Purchases/OtherCosts/Meals:* Tied to MessUser & Month.
- *Funds:* General financial entries, often tied to Month & Mess.
- *MessRequests/PurchaseRequests:* User applications and purchase approvals.
- *Design:*  
  - Forms must clearly select MessUser & Month.
  - Request queues need clear action paths.

---

#### **Entity Relationship Map**
```
User ──┐
       ├─ MessUser (Role) ── Mess ── Subscription ── Plan/Features
       │                        │
       │                        └─ Month ── Meals/Deposits/Purchases/OtherCosts/InitiateUser/Funds
       └─ Country
```

---

## 3. 🧭 Layout & Navigation

### **Header (Always-On)**
- **Logo/Brand**
- **Breadcrumb Trail** (clickable, always shows context)
- **Global Search**
- **Notifications**
- **Profile Dropdown**

### **Sidebar (Contextual)**
- **Global:** Dashboard, Users, Messes, Subscriptions, Plans, Content, Settings, Roles & Logs
- **Mess Hub:** Overview, Members, Roles, Months, Subscription, Reports, Settings
- **Month:** Overview, Meals, Deposits, Purchases, Other Costs, Initiated Users, Summary

---

## 4. 🛠️ Core Interaction Patterns

- **Hub Pattern:** Each Mess/Month has a dashboard with tabs/sections for related data.
- **Breadcrumbs:** Always visible, clickable for easy navigation.
- **Master-Detail:** Lists with expandable or linked detail views.
- **Contextual Creation:** Forms pre-fill parent IDs (e.g., Mess, Month).
- **Smart Selectors:** Searchable, filtered dropdowns for linking entities.
- **Confirmation Modals:** For deletes, subscription changes, closing months.

---

## 5. 🧑‍💻 Admin Experience: Section by Section

### **5.1 Global Dashboard**
- **KPI Cards:** Users, Messes, Subscriptions, Revenue, Pending Requests
- **Charts:** User/Mess/Subscription trends, Revenue by Plan
- **Activity Feed:** New Messes, subscription changes, alerts
- **Quick Links:** Create Mess, Manage Plans, View Requests

### **5.2 User Management**
- **User List:** `name`, `email`, `country`, active Mess, role, status, join date
- **Profile:** Details, Mess Memberships (with role/status), Add to Mess, Activity Log

### **5.3 Mess Hub**
- **Dashboard:** Mess name, status, plan, member/financial KPIs
- **Members:** Table of users, role, status, join/leave dates, add/edit/remove
- **Roles:** List roles, assign permissions, create/edit/delete
- **Months:** List months, status, initiated users, financials, create/activate/close
- **Subscription:** Plan/package, status, dates, features, usage, change/cancel
- **Finances:** Fund entries, add/edit/delete
- **Settings:** Mess-specific fields

### **5.4 Month Detail**
- **Dashboard:** KPIs for meals, deposits, purchases, costs, meal rate, initiated users
- **Meals/Deposits/Purchases/Other Costs:** Tables, add/edit/delete, filter by user/date
- **Initiated Users:** List, add/remove, show initiation date
- **Summary:** Per-member and overall financial breakdown

### **5.5 Plans & Subscriptions**
- **Plans:** List, create/edit/delete, associate features
- **Packages:** List, create/edit/delete, link to plan
- **Features:** List, create/edit/delete, set limits
- **Subscriptions:** List, filter, view/edit/cancel, create manually

### **5.6 Financials & Requests**
- **Mess Requests:** List, approve/reject, show user/mess/status
- **Funds:** List, add/edit/delete, filter by mess/month
- **Purchase Requests:** List, approve/reject, show details

### **5.7 Content Management**
- **Main Sliders/User Guides/Banners:** List, preview, CRUD, upload images, set status

### **5.8 System Settings**
- **Countries:** List, CRUD
- **Admin Roles:** List, CRUD, assign permissions
- **Settings:** Key-value pairs, edit types
- **Audit Logs:** List admin actions

---

## 6. 🧩 UI Kit & Components

- **Data Tables:** Sorting, pagination, filtering, row actions
- **Forms:** Clear labels, correct input types, validation, disabled states
- **Modals:** For confirmations and quick edits
- **Tabs/Accordions:** For complex pages
- **Status Badges:** Colorful, icon-based for quick recognition
- **Buttons:** Primary, secondary, destructive
- **Search/Filters:** Panels or dropdowns, date pickers
- **Charts:** Clean, interactive, with tooltips
- **Toasts:** For feedback

---

## 7. 🎨 Design System Basics

- **Color Palette:** Primary, secondary, accent, status (success, warning, error, info)
- **Typography:** Headings, body, captions, weights, line heights
- **Spacing/Grid:** Consistent scale (4px, 8px, 16px, etc.)
- **Icons:** Consistent set (Heroicons, Feather, etc.)

---

## 8. 📱 Responsive & Accessibility

- **Mobile:** Sidebar as drawer, stacked forms/tables, large touch targets
- **Tablet:** Collapsed sidebar, two-column layouts
- **Desktop:** Full layout, advanced filtering, keyboard shortcuts
- **Accessibility:** Keyboard navigation, color contrast, ARIA, focus indicators, alt text

---

## 9. ⚡ Technical & Performance Notes

- **Performance:** Lazy loading, avoid heavy animations, use skeleton loaders
- **Data Volume:** Pagination for large lists, avoid infinite scroll for critical data
- **Frontend Stack:** Vue.js 3 / React 18, Tailwind CSS (for designer awareness)

---

> **Design with empathy:** Always make the current context clear, minimize cognitive load, and ensure every action is safe and reversible.  
> **Reference the [Model Fields and Descriptions](../models/fields.md) for field-level details.**

---
