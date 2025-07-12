# Meal Request System Documentation

## Overview
The meal request system allows users to request meals that need to be approved by administrators or users with meal management permissions before being added to the actual meal records.

## Key Features

### User Capabilities
- **Create Meal Requests**: Users can request meals for specific dates
- **Update Requests**: Users can modify their pending meal requests
- **Delete Requests**: Users can delete their pending meal requests
- **Cancel Requests**: Users can cancel their pending meal requests
- **View Own Requests**: Users can view their meal request history

### Admin/Manager Capabilities
- **Approve Requests**: Convert meal requests into actual meals
- **Reject Requests**: Reject meal requests with optional reason
- **View All Requests**: See all pending meal requests
- **Manage Requests**: Full management capabilities

## API Endpoints Specification

**Important: Month Context Required**
All meal request API endpoints require a month ID to be passed in the `Month-ID` header. This identifies which month the meal request belongs to. The month ID should be obtained from the months management system or set to the current active month.

**Common Headers for All Endpoints:**
```
Authorization: Bearer {auth_token}
Content-Type: application/json
Accept: application/json
Month-ID: {month_id}
```

### For Regular Users

#### Create Meal Request
```
POST /api/meal-request/add
```

**Headers:**
```
Authorization: Bearer {auth_token}
Content-Type: application/json
Accept: application/json
Month-ID: {month_id}
```

**Required Headers:**
- `Authorization`: Bearer token for authentication
- `Content-Type`: Must be `application/json`
- `Accept`: Must be `application/json`
- `Month-ID`: ID of the current active month (required for all meal request operations)

**Request Body:**
```json
{
    "mess_user_id": 1,
    "date": "2025-07-15",
    "breakfast": 1,
    "lunch": 1,
    "dinner": 0,
    "comment": "Regular meal request"
}
```

**Request Body Schema:**
- `mess_user_id` (integer, required): ID of the mess user making the request
- `date` (string, required): Date in YYYY-MM-DD format
- `breakfast` (integer, required): Breakfast quantity (0 or 1)
- `lunch` (integer, required): Lunch quantity (0 or 1)
- `dinner` (integer, required): Dinner quantity (0 or 1)
- `comment` (string, optional): Additional comment from user

**Success Response (201 Created):**
```json
{
    "success": true,
    "message": "Meal request created successfully",
    "data": {
        "id": 15,
        "mess_user_id": 1,
        "mess_id": 1,
        "month_id": 7,
        "date": "2025-07-15",
        "breakfast": 1,
        "lunch": 1,
        "dinner": 0,
        "status": 0,
        "comment": "Regular meal request",
        "approved_by": null,
        "approved_at": null,
        "rejected_reason": null,
        "created_at": "2025-07-10T14:30:00.000000Z",
        "updated_at": "2025-07-10T14:30:00.000000Z",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "status_text": "Pending"
    }
}
```

**Error Response (422 Validation Error):**
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "date": ["The date field is required."],
        "breakfast": ["At least one meal type must be requested."]
    }
}
```

#### Update Meal Request
```
PUT /api/meal-request/{id}/update
```

**Headers:**
```
Authorization: Bearer {auth_token}
Content-Type: application/json
Accept: application/json
```

**URL Parameters:**
- `id` (integer, required): ID of the meal request to update

**Request Body:**
```json
{
    "date": "2025-07-16",
    "breakfast": 0,
    "lunch": 1,
    "dinner": 1,
    "comment": "Updated meal request"
}
```

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Meal request updated successfully",
    "data": {
        "id": 15,
        "mess_user_id": 1,
        "mess_id": 1,
        "month_id": 7,
        "date": "2025-07-16",
        "breakfast": 0,
        "lunch": 1,
        "dinner": 1,
        "status": 0,
        "comment": "Updated meal request",
        "approved_by": null,
        "approved_at": null,
        "rejected_reason": null,
        "created_at": "2025-07-10T14:30:00.000000Z",
        "updated_at": "2025-07-10T14:45:00.000000Z",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "status_text": "Pending"
    }
}
```

**Error Response (403 Forbidden):**
```json
{
    "success": false,
    "message": "Cannot update non-pending meal request",
    "error_code": "MEAL_REQUEST_NOT_EDITABLE"
}
```

#### Delete Meal Request
```
DELETE /api/meal-request/{id}/delete
```

**Headers:**
```
Authorization: Bearer {auth_token}
Accept: application/json
```

**URL Parameters:**
- `id` (integer, required): ID of the meal request to delete

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Meal request deleted successfully"
}
```

**Error Response (404 Not Found):**
```json
{
    "success": false,
    "message": "Meal request not found",
    "error_code": "MEAL_REQUEST_NOT_FOUND"
}
```

#### Cancel Meal Request
```
POST /api/meal-request/{id}/cancel
```

**Headers:**
```
Authorization: Bearer {auth_token}
Content-Type: application/json
Accept: application/json
```

**URL Parameters:**
- `id` (integer, required): ID of the meal request to cancel

**Request Body:**
```json
{
    "reason": "Change of plans"
}
```

**Request Body Schema:**
- `reason` (string, optional): Reason for cancellation

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Meal request cancelled successfully",
    "data": {
        "id": 15,
        "mess_user_id": 1,
        "mess_id": 1,
        "month_id": 7,
        "date": "2025-07-15",
        "breakfast": 1,
        "lunch": 1,
        "dinner": 0,
        "status": 3,
        "comment": "Regular meal request",
        "approved_by": null,
        "approved_at": null,
        "rejected_reason": "Change of plans",
        "created_at": "2025-07-10T14:30:00.000000Z",
        "updated_at": "2025-07-10T15:00:00.000000Z",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "status_text": "Cancelled"
    }
}
```

#### View My Requests
```
GET /api/meal-request/my-requests
```

**Headers:**
```
Authorization: Bearer {auth_token}
Accept: application/json
```

**Query Parameters:**
- `page` (integer, optional): Page number for pagination (default: 1)
- `per_page` (integer, optional): Items per page (default: 15, max: 100)
- `status` (integer, optional): Filter by status (0=pending, 1=approved, 2=rejected, 3=cancelled)
- `date_from` (string, optional): Start date filter (YYYY-MM-DD)
- `date_to` (string, optional): End date filter (YYYY-MM-DD)

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Meal requests retrieved successfully",
    "data": {
        "current_page": 1,
        "last_page": 3,
        "per_page": 15,
        "total": 42,
        "data": [
            {
                "id": 15,
                "mess_user_id": 1,
                "mess_id": 1,
                "month_id": 7,
                "date": "2025-07-15",
                "breakfast": 1,
                "lunch": 1,
                "dinner": 0,
                "status": 0,
                "comment": "Regular meal request",
                "approved_by": null,
                "approved_at": null,
                "rejected_reason": null,
                "created_at": "2025-07-10T14:30:00.000000Z",
                "updated_at": "2025-07-10T14:30:00.000000Z",
                "user": {
                    "id": 1,
                    "name": "John Doe",
                    "email": "john@example.com"
                },
                "status_text": "Pending",
                "total_meals": 2
            }
        ]
    }
}
```

**Key Pagination Fields:**
- `current_page`: Current page number
- `last_page`: Total number of pages
- `per_page`: Items per page
- `total`: Total number of items
- `data`: Array of meal requests

*Note: The API also returns pagination URLs (`first_page_url`, `next_page_url`, etc.) but these are typically not needed for frontend implementation.*
```

### For Admins/Managers

#### Approve Meal Request
```
POST /api/meal-request/{id}/approve
```

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: application/json
Accept: application/json
```

**URL Parameters:**
- `id` (integer, required): ID of the meal request to approve

**Request Body:**
```json
{
    "comment": "Approved by admin"
}
```

**Request Body Schema:**
- `comment` (string, optional): Admin comment for approval

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Meal request approved successfully",
    "data": {
        "meal_request": {
            "id": 15,
            "mess_user_id": 1,
            "mess_id": 1,
            "month_id": 7,
            "date": "2025-07-15",
            "breakfast": 1,
            "lunch": 1,
            "dinner": 0,
            "status": 1,
            "comment": "Approved by admin",
            "approved_by": 2,
            "approved_at": "2025-07-10T15:30:00.000000Z",
            "rejected_reason": null,
            "created_at": "2025-07-10T14:30:00.000000Z",
            "updated_at": "2025-07-10T15:30:00.000000Z",
            "user": {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com"
            },
            "approved_by_user": {
                "id": 2,
                "name": "Admin User",
                "email": "admin@example.com"
            },
            "status_text": "Approved"
        },
        "created_meal": {
            "id": 45,
            "mess_user_id": 1,
            "mess_id": 1,
            "month_id": 7,
            "date": "2025-07-15",
            "breakfast": 1,
            "lunch": 1,
            "dinner": 0,
            "created_at": "2025-07-10T15:30:00.000000Z",
            "updated_at": "2025-07-10T15:30:00.000000Z"
        }
    }
}
```

#### Reject Meal Request
```
POST /api/meal-request/{id}/reject
```

**Headers:**
```
Authorization: Bearer {admin_token}
Content-Type: application/json
Accept: application/json
```

**URL Parameters:**
- `id` (integer, required): ID of the meal request to reject

**Request Body:**
```json
{
    "rejected_reason": "Insufficient budget for this date"
}
```

**Request Body Schema:**
- `rejected_reason` (string, required): Reason for rejection

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Meal request rejected successfully",
    "data": {
        "id": 15,
        "mess_user_id": 1,
        "mess_id": 1,
        "month_id": 7,
        "date": "2025-07-15",
        "breakfast": 1,
        "lunch": 1,
        "dinner": 0,
        "status": 2,
        "comment": "Regular meal request",
        "approved_by": 2,
        "approved_at": "2025-07-10T15:30:00.000000Z",
        "rejected_reason": "Insufficient budget for this date",
        "created_at": "2025-07-10T14:30:00.000000Z",
        "updated_at": "2025-07-10T15:30:00.000000Z",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "approved_by_user": {
            "id": 2,
            "name": "Admin User",
            "email": "admin@example.com"
        },
        "status_text": "Rejected"
    }
}
```

#### View Pending Requests
```
GET /api/meal-request/pending
```

**Headers:**
```
Authorization: Bearer {admin_token}
Accept: application/json
```

**Query Parameters:**
- `page` (integer, optional): Page number for pagination (default: 1)
- `per_page` (integer, optional): Items per page (default: 15, max: 100)
- `date_from` (string, optional): Start date filter (YYYY-MM-DD)
- `date_to` (string, optional): End date filter (YYYY-MM-DD)

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Pending meal requests retrieved successfully",
    "data": {
        "current_page": 1,
        "last_page": 2,
        "per_page": 15,
        "total": 23,
        "data": [
            {
                "id": 15,
                "mess_user_id": 1,
                "mess_id": 1,
                "month_id": 7,
                "date": "2025-07-15",
                "breakfast": 1,
                "lunch": 1,
                "dinner": 0,
                "status": 0,
                "comment": "Regular meal request",
                "approved_by": null,
                "approved_at": null,
                "rejected_reason": null,
                "created_at": "2025-07-10T14:30:00.000000Z",
                "updated_at": "2025-07-10T14:30:00.000000Z",
                "user": {
                    "id": 1,
                    "name": "John Doe",
                    "email": "john@example.com"
                },
                "status_text": "Pending",
                "total_meals": 2,
                "days_pending": 0
            }
        ]
    }
}
```
```

#### View All Requests
```
GET /api/meal-request/
```

**Headers:**
```
Authorization: Bearer {admin_token}
Accept: application/json
```

**Query Parameters:**
- `page` (integer, optional): Page number for pagination (default: 1)
- `per_page` (integer, optional): Items per page (default: 15, max: 100)
- `status` (integer, optional): Filter by status (0=pending, 1=approved, 2=rejected, 3=cancelled)
- `date_from` (string, optional): Start date filter (YYYY-MM-DD)
- `date_to` (string, optional): End date filter (YYYY-MM-DD)
- `user_id` (integer, optional): Filter by specific user ID
- `search` (string, optional): Search in user name or comment

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Meal requests retrieved successfully",
    "data": {
        "current_page": 1,
        "last_page": 5,
        "per_page": 15,
        "total": 67,
        "data": [
            {
                "id": 15,
                "mess_user_id": 1,
                "mess_id": 1,
                "month_id": 7,
                "date": "2025-07-15",
                "breakfast": 1,
                "lunch": 1,
                "dinner": 0,
                "status": 1,
                "comment": "Regular meal request",
                "approved_by": 2,
                "approved_at": "2025-07-10T15:30:00.000000Z",
                "rejected_reason": null,
                "created_at": "2025-07-10T14:30:00.000000Z",
                "updated_at": "2025-07-10T15:30:00.000000Z",
                "user": {
                    "id": 1,
                    "name": "John Doe",
                    "email": "john@example.com"
                },
                "approved_by_user": {
                    "id": 2,
                    "name": "Admin User",
                    "email": "admin@example.com"
                },
                "status_text": "Approved",
                "total_meals": 2
            }
        ]
    },
    "summary": {
        "total_requests": 67,
        "pending_requests": 12,
        "approved_requests": 45,
        "rejected_requests": 8,
        "cancelled_requests": 2
    }
}
```
```

#### Get Single Request Details
```
GET /api/meal-request/{id}
```

**Headers:**
```
Authorization: Bearer {admin_token}
Accept: application/json
```

**URL Parameters:**
- `id` (integer, required): ID of the meal request to view

**Success Response (200 OK):**
```json
{
    "success": true,
    "message": "Meal request retrieved successfully",
    "data": {
        "id": 15,
        "mess_user_id": 1,
        "mess_id": 1,
        "month_id": 7,
        "date": "2025-07-15",
        "breakfast": 1,
        "lunch": 1,
        "dinner": 0,
        "status": 1,
        "comment": "Regular meal request",
        "approved_by": 2,
        "approved_at": "2025-07-10T15:30:00.000000Z",
        "rejected_reason": null,
        "created_at": "2025-07-10T14:30:00.000000Z",
        "updated_at": "2025-07-10T15:30:00.000000Z",
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "profile_image": null
        },
        "approved_by_user": {
            "id": 2,
            "name": "Admin User",
            "email": "admin@example.com",
            "profile_image": null
        },
        "mess": {
            "id": 1,
            "name": "Test Mess",
            "description": "Test mess description"
        },
        "month": {
            "id": 7,
            "name": "July 2025",
            "year": 2025,
            "month": 7
        },
        "status_text": "Approved",
        "total_meals": 2,
        "created_meal": {
            "id": 45,
            "mess_user_id": 1,
            "mess_id": 1,
            "month_id": 7,
            "date": "2025-07-15",
            "breakfast": 1,
            "lunch": 1,
            "dinner": 0,
            "created_at": "2025-07-10T15:30:00.000000Z",
            "updated_at": "2025-07-10T15:30:00.000000Z"
        }
    }
}
```

## Permission System

### Required Permissions
- `MEAL_REQUEST_MANAGEMENT`: Full meal request management
- `MEAL_REQUEST_APPROVE`: Can approve meal requests
- `MEAL_REQUEST_REJECT`: Can reject meal requests
- `MEAL_MANAGEMENT`: General meal management (includes request management)

### Status Flow
1. **PENDING (0)**: Initial status when request is created
2. **APPROVED (1)**: Request approved and meal created
3. **REJECTED (2)**: Request rejected by admin
4. **CANCELLED (3)**: Request cancelled by user

## Database Schema

### meal_requests table
- `id`: Primary key
- `mess_user_id`: Foreign key to mess_users
- `mess_id`: Foreign key to mess
- `month_id`: Foreign key to months
- `date`: Date of the meal request
- `breakfast`: Breakfast quantity
- `lunch`: Lunch quantity
- `dinner`: Dinner quantity
- `status`: Request status (0=pending, 1=approved, 2=rejected, 3=cancelled)
- `comment`: Optional comment from admin
- `approved_by`: Foreign key to mess_users (who approved/rejected)
- `approved_at`: Timestamp of approval/rejection
- `rejected_reason`: Reason for rejection
- `created_at`: Creation timestamp
- `updated_at`: Update timestamp

## Business Rules & Validation

### 1. User Restrictions
- **Ownership**: Users can only view and modify their own meal requests
- **Status Limitations**: Users can only modify requests with status "Pending" (0)
- **Meal Requirements**: At least one meal type (breakfast, lunch, dinner) must be requested
- **Date Restrictions**: Cannot request meals for past dates
- **Mess Membership**: User must be an active member of a mess to create requests
- **Duplicate Prevention**: Only one request per user per date is allowed

### 2. Admin/Manager Restrictions
- **Permission Requirements**: Must have appropriate permissions to approve/reject requests
  - `MEAL_REQUEST_MANAGEMENT`: Full meal request management
  - `MEAL_REQUEST_APPROVE`: Can approve meal requests
  - `MEAL_REQUEST_REJECT`: Can reject meal requests
  - `MEAL_MANAGEMENT`: General meal management (includes request management)
- **Status Changes**: Can only approve/reject requests with status "Pending" (0)
- **Automatic Meal Creation**: Approved requests automatically create meal records
- **Immutable Approvals**: Once approved, requests cannot be modified or reversed

### 3. Approval Process Flow
1. **Request Creation**: User creates meal request with status "Pending"
2. **Admin Review**: Admin views pending requests and decides to approve/reject
3. **Approval Action**: 
   - Sets status to "Approved" (1)
   - Creates corresponding meal record
   - Records approval timestamp and admin ID
   - Optional approval comment
4. **Rejection Action**:
   - Sets status to "Rejected" (2)
   - Records rejection reason (required)
   - Records rejection timestamp and admin ID
5. **Cancellation** (User initiated):
   - Sets status to "Cancelled" (3)
   - Optional cancellation reason
   - Records cancellation timestamp

### 4. Data Validation Rules

#### Date Validation
- **Format**: Must be in YYYY-MM-DD format
- **Range**: Cannot be in the past (based on server timezone)
- **Existence**: Must be a valid calendar date
- **Mess Period**: Date must fall within an active mess period/month

#### Meal Type Validation
- **Values**: Each meal type (breakfast, lunch, dinner) must be 0 or 1
- **Requirement**: At least one meal type must be set to 1
- **Combination**: Any combination of meal types is allowed

#### Comment Validation
- **Length**: Maximum 500 characters
- **Content**: No HTML tags or special characters that could cause security issues
- **Optional**: Comments are optional for user requests and admin approvals

#### Rejection Reason Validation
- **Required**: Must be provided when rejecting a request
- **Length**: Maximum 500 characters
- **Content**: Should be meaningful and helpful to the user

### 5. Status Transition Rules

#### Valid Status Transitions
- **Pending (0) → Approved (1)**: Admin approval
- **Pending (0) → Rejected (2)**: Admin rejection
- **Pending (0) → Cancelled (3)**: User cancellation
- **Pending (0) → Pending (0)**: User update (same status)

#### Invalid Status Transitions
- **Approved (1) → Any other status**: Approved requests are final
- **Rejected (2) → Any other status**: Rejected requests are final
- **Cancelled (3) → Any other status**: Cancelled requests are final

### 6. Permission Matrix

| Action | User (Own Requests) | User (Others) | Admin | Manager |
|--------|-------------------|---------------|--------|---------|
| Create | ✅ | ❌ | ✅ | ✅ |
| View Own | ✅ | ❌ | ✅ | ✅ |
| View Others | ❌ | ❌ | ✅ | ✅ |
| Update (Pending) | ✅ | ❌ | ✅ | ✅ |
| Cancel | ✅ | ❌ | ✅ | ✅ |
| Delete | ✅ | ❌ | ✅ | ✅ |
| Approve | ❌ | ❌ | ✅ | ✅ |
| Reject | ❌ | ❌ | ✅ | ✅ |

### 7. Business Logic Implementation

#### Automatic Meal Creation on Approval
When a meal request is approved:
1. Validate the request is still pending
2. Create new meal record with identical data
3. Update request status to approved
4. Set approval timestamp and admin ID
5. Return both request and created meal data

#### Duplicate Prevention
- Check for existing requests for the same user and date
- Prevent creation if duplicate exists (unless previous is cancelled/rejected)
- Allow new request if previous request was cancelled or rejected

#### Notification System (Optional)
- Notify user when their request is approved/rejected
- Notify admins when new requests are created
- Send daily summary of pending requests to admins

### 8. Integration Points

#### With Existing Meal System
- **Coexistence**: Request system works alongside direct meal addition
- **Data Consistency**: Approved requests create standard meal records
- **Reporting**: Meals from requests are included in all meal reports
- **Permissions**: Existing meal permissions are respected

#### With User Management
- **Mess Membership**: Only active mess members can create requests
- **Role-Based Access**: Different permissions for different user roles
- **User Status**: Inactive users cannot create or manage requests

#### With Financial System
- **Meal Costing**: Approved meals are included in cost calculations
- **Billing**: Meals from requests are included in user billing
- **Expense Tracking**: Approved requests contribute to mess expenses

## Authentication Response Format

The login endpoint (`POST /api/auth/login`) returns the following response structure:

```json
{
    "error": false,
    "message": "Success",
    "data": {
        "user": {
            "id": 1,
            "name": "Maruf Ahmed",
            "user_name": null,
            "email": "maruf@email.com",
            "email_verified_at": null,
            "country_id": 19,
            "phone": "1778473031",
            "gender": "Male",
            "city": "Khulna",
            "status": "active",
            "join_date": null,
            "leave_date": null,
            "photo_url": null,
            "fcm_token": null,
            "version": 0,
            "last_active": null,
            "created_at": "2025-06-26T06:30:54.000000Z",
            "updated_at": "2025-06-26T06:30:54.000000Z",
            "is_email_verified": false,
            "model_name": "User",
            "country": {
                "id": 19,
                "name": "Bangladesh",
                "code": "BD",
                "dial_code": "880",
                "status": 1,
                "created_at": null,
                "updated_at": null,
                "model_name": "Country"
            }
        },
        "mess_user": {
            "id": 3,
            "mess_id": 13,
            "user_id": 1,
            "mess_role_id": 4,
            "joined_at": "2025-07-07 12:35:03",
            "left_at": null,
            "status": "active",
            "created_at": "2025-07-07T12:35:03.000000Z",
            "updated_at": "2025-07-07T12:35:03.000000Z",
            "is_user_left_mess": false,
            "model_name": "MessUser",
            "mess": {
                "id": 13,
                "name": "Mama kaka",
                "status": "active",
                "ad_free": false,
                "all_user_add_meal": false,
                "fund_add_enabled": true,
                "created_at": "2025-07-07T12:35:03.000000Z",
                "updated_at": "2025-07-07T12:35:03.000000Z"
            },
            "role": {
                "id": 4,
                "mess_id": 13,
                "role": "admin",
                "is_default": true,
                "is_admin": true,
                "created_at": "2025-07-07T12:35:03.000000Z",
                "updated_at": "2025-07-07T12:35:03.000000Z",
                "permissions": []
            },
            "user": {
                "id": 1,
                "name": "Maruf Ahmed",
                "user_name": null,
                "email": "maruf@email.com",
                "email_verified_at": null,
                "country_id": 19,
                "phone": "1778473031",
                "gender": "Male",
                "city": "Khulna",
                "status": "active",
                "join_date": null,
                "leave_date": null,
                "photo_url": null,
                "fcm_token": null,
                "version": 0,
                "last_active": null,
                "created_at": "2025-06-26T06:30:54.000000Z",
                "updated_at": "2025-06-26T06:30:54.000000Z",
                "is_email_verified": false,
                "model_name": "User"
            }
        },
        "token": "18|wiUkDCPwOIJyOOEsiwuwDok17QFrH0MM8SiDdFUl8802a090"
    },
    "errors": null,
    "error_code": null
}
```

**Important Notes:**
- The authentication token is located at `data.token`
- User information is at `data.user`
- Mess user information (including mess and role) is at `data.mess_user`
- The `mess_user.id` should be used for `mess_user_id` in meal requests
- The `mess_user.mess_id` indicates which mess the user belongs to
- The `mess_user.role.permissions` array contains the user's permissions

## Integration with Existing Meal System

The meal request system works alongside the existing meal system:
- **Direct Meal Addition**: Users with `MEAL_ADD` or `MEAL_MANAGEMENT` permissions can still add meals directly
- **Request-Based Addition**: Regular users must go through the request process
- **Approval Creates Meals**: Approved requests automatically create meal records

This ensures that meal management permissions are properly enforced while providing a smooth workflow for users without administrative privileges.

## Error Handling & Status Codes

### HTTP Status Codes
- `200 OK`: Request successful
- `201 Created`: Resource created successfully
- `400 Bad Request`: Invalid request data
- `401 Unauthorized`: Authentication required
- `403 Forbidden`: Access denied
- `404 Not Found`: Resource not found
- `422 Unprocessable Entity`: Validation failed
- `500 Internal Server Error`: Server error

### Error Response Format
All error responses follow this structure:

```json
{
    "success": false,
    "message": "Error description",
    "error_code": "ERROR_CODE_IDENTIFIER",
    "errors": {
        "field_name": ["Error message for field"]
    }
}
```

### Common Error Codes
- `MEAL_REQUEST_NOT_FOUND`: Meal request does not exist
- `MEAL_REQUEST_NOT_EDITABLE`: Cannot modify non-pending requests
- `MEAL_REQUEST_NOT_OWNED`: User can only modify their own requests
- `INSUFFICIENT_PERMISSIONS`: User lacks required permissions
- `INVALID_MEAL_COMBINATION`: At least one meal type must be requested
- `DUPLICATE_MEAL_REQUEST`: Request already exists for this date
- `MESS_NOT_FOUND`: User is not member of any mess
- `INVALID_DATE_FORMAT`: Date must be in YYYY-MM-DD format
- `PAST_DATE_NOT_ALLOWED`: Cannot request meals for past dates

### Field Validation Rules

#### For Create/Update Requests:
- `mess_user_id`: Required, must be valid mess user ID
- `date`: Required, must be valid date (YYYY-MM-DD), cannot be in the past
- `breakfast`: Required, must be 0 or 1
- `lunch`: Required, must be 0 or 1
- `dinner`: Required, must be 0 or 1
- At least one of breakfast, lunch, or dinner must be 1
- `comment`: Optional, max 500 characters

#### For Approve/Reject:
- `comment`: Optional for approve, max 500 characters
- `rejected_reason`: Required for reject, max 500 characters

### Frontend Implementation Guidelines

#### Authentication
Always include the Bearer token in the Authorization header:
```javascript
headers: {
    'Authorization': `Bearer ${token}`,
    'Content-Type': 'application/json',
    'Accept': 'application/json'
}
```

#### Request Validation
Validate data on frontend before sending to API:
```javascript
// Example validation for meal request
const validateMealRequest = (data) => {
    const errors = {};
    
    if (!data.date) {
        errors.date = 'Date is required';
    } else if (new Date(data.date) < new Date().setHours(0,0,0,0)) {
        errors.date = 'Cannot request meals for past dates';
    }
    
    if (!data.breakfast && !data.lunch && !data.dinner) {
        errors.meals = 'At least one meal type must be requested';
    }
    
    return errors;
};
```

#### Error Handling
Handle different error scenarios:
```javascript
const handleApiError = (error) => {
    if (error.response) {
        const { status, data } = error.response;
        
        switch (status) {
            case 401:
                // Redirect to login
                break;
            case 403:
                // Show access denied message
                break;
            case 422:
                // Show validation errors
                return data.errors;
            case 404:
                // Show not found message
                break;
            default:
                // Show generic error message
                break;
        }
    }
};
```

#### Status Display
Display user-friendly status text:
```javascript
const getStatusText = (status) => {
    const statusMap = {
        0: 'Pending',
        1: 'Approved',
        2: 'Rejected',
        3: 'Cancelled'
    };
    return statusMap[status] || 'Unknown';
};

const getStatusColor = (status) => {
    const colorMap = {
        0: 'orange',    // Pending
        1: 'green',     // Approved
        2: 'red',       // Rejected
        3: 'gray'       // Cancelled
    };
    return colorMap[status] || 'gray';
};
```

#### Date Formatting
Format dates for display:
```javascript
const formatDate = (dateString) => {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
};

const formatDateTime = (dateTimeString) => {
    const date = new Date(dateTimeString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
```

#### Pagination Handling
Handle paginated responses (focus on essential fields):
```javascript
const handlePaginatedResponse = (response) => {
    const { data, current_page, last_page, total, per_page } = response.data;
    
    return {
        items: data,
        currentPage: current_page,
        lastPage: last_page,
        total: total,
        perPage: per_page,
        hasNext: current_page < last_page,
        hasPrev: current_page > 1
    };
};

// Example usage for pagination component
const PaginationInfo = ({ pagination }) => (
    <div className="pagination-info">
        <span>Page {pagination.currentPage} of {pagination.lastPage}</span>
        <span>Total: {pagination.total} items</span>
    </div>
);
```

## Data Models & Types

### TypeScript Interfaces

```typescript
// Authentication Response
interface AuthResponse {
  error: boolean;
  message: string;
  data: {
    user: User;
    mess_user: MessUser;
    token: string;
  };
  errors: any | null;
  error_code: string | null;
}

interface User {
  id: number;
  name: string;
  user_name: string | null;
  email: string;
  email_verified_at: string | null;
  country_id: number;
  phone: string;
  gender: string;
  city: string;
  status: string;
  join_date: string | null;
  leave_date: string | null;
  photo_url: string | null;
  fcm_token: string | null;
  version: number;
  last_active: string | null;
  created_at: string;
  updated_at: string;
  is_email_verified: boolean;
  model_name: string;
  country?: Country;
}

interface Country {
  id: number;
  name: string;
  code: string;
  dial_code: string;
  status: number;
  created_at: string | null;
  updated_at: string | null;
  model_name: string;
}

interface MessUser {
  id: number;
  mess_id: number;
  user_id: number;
  mess_role_id: number;
  joined_at: string;
  left_at: string | null;
  status: string;
  created_at: string;
  updated_at: string;
  is_user_left_mess: boolean;
  model_name: string;
  mess?: Mess;
  role?: MessRole;
  user?: User;
}

interface Mess {
  id: number;
  name: string;
  status: string;
  ad_free: boolean;
  all_user_add_meal: boolean;
  fund_add_enabled: boolean;
  created_at: string;
  updated_at: string;
}

interface MessRole {
  id: number;
  mess_id: number;
  role: string;
  is_default: boolean;
  is_admin: boolean;
  created_at: string;
  updated_at: string;
  permissions: string[];
}

// Meal Request Interfaces
interface MealRequest {
  id: number;
  mess_user_id: number;
  requested_for: string; // YYYY-MM-DD format
  breakfast: number;
  lunch: number;
  dinner: number;
  status: 'pending' | 'approved' | 'rejected';
  notes: string | null;
  admin_notes: string | null;
  requested_at: string;
  processed_at: string | null;
  processed_by: number | null;
  created_at: string;
  updated_at: string;
  user?: User;
  processed_by_user?: User;
}

interface MealRequestCreatePayload {
  requested_for: string; // YYYY-MM-DD format
  breakfast: number;
  lunch: number;
  dinner: number;
  notes?: string;
}

interface MealRequestUpdatePayload {
  breakfast?: number;
  lunch?: number;
  dinner?: number;
  notes?: string;
}

interface MealRequestProcessPayload {
  status: 'approved' | 'rejected';
  admin_notes?: string;
}

// API Response Types
interface ApiResponse<T> {
  error: boolean;
  message: string;
  data: T;
  errors: any | null;
  error_code: string | null;
}

interface PaginatedResponse<T> {
  data: T[];
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  // Note: API also returns first_page_url, last_page_url, next_page_url, 
  // prev_page_url, path, from, to, links but these are usually not needed for frontend
}

// Error Response
interface ErrorResponse {
  error: boolean;
  message: string;
  data: null;
  errors: {
    [field: string]: string[];
  };
  error_code: string;
}
```

### API Service Examples

```typescript
class MealRequestService {
    private baseUrl = 'http://localhost:8000/api';
    private token: string;
    private monthId: number;

    constructor(token: string, monthId: number) {
        this.token = token;
        this.monthId = monthId;
    }

    private getHeaders() {
        return {
            'Authorization': `Bearer ${this.token}`,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Month-ID': this.monthId.toString()
        };
    }

    async createMealRequest(data: CreateMealRequestData): Promise<ApiResponse<MealRequest>> {
        const response = await fetch(`${this.baseUrl}/meal-request/add`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(data)
        });
        return response.json();
    }

    async updateMealRequest(id: number, data: UpdateMealRequestData): Promise<ApiResponse<MealRequest>> {
        const response = await fetch(`${this.baseUrl}/meal-request/${id}/update`, {
            method: 'PUT',
            headers: this.getHeaders(),
            body: JSON.stringify(data)
        });
        return response.json();
    }

    async deleteMealRequest(id: number): Promise<ApiResponse<void>> {
        const response = await fetch(`${this.baseUrl}/meal-request/${id}/delete`, {
            method: 'DELETE',
            headers: this.getHeaders()
        });
        return response.json();
    }

    async cancelMealRequest(id: number, data: CancelMealRequestData): Promise<ApiResponse<MealRequest>> {
        const response = await fetch(`${this.baseUrl}/meal-request/${id}/cancel`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(data)
        });
        return response.json();
    }

    async getMyRequests(params?: {
        page?: number;
        per_page?: number;
        status?: number;
        date_from?: string;
        date_to?: string;
    }): Promise<ApiResponse<PaginatedResponse<MealRequest>>> {
        const queryParams = new URLSearchParams();
        if (params) {
            Object.entries(params).forEach(([key, value]) => {
                if (value !== undefined) {
                    queryParams.append(key, value.toString());
                }
            });
        }

        const response = await fetch(`${this.baseUrl}/meal-request/my-requests?${queryParams}`, {
            headers: this.getHeaders()
        });
        return response.json();
    }

    async approveMealRequest(id: number, data: ApproveMealRequestData): Promise<ApiResponse<{meal_request: MealRequest, created_meal: Meal}>> {
        const response = await fetch(`${this.baseUrl}/meal-request/${id}/approve`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(data)
        });
        return response.json();
    }

    async rejectMealRequest(id: number, data: RejectMealRequestData): Promise<ApiResponse<MealRequest>> {
        const response = await fetch(`${this.baseUrl}/meal-request/${id}/reject`, {
            method: 'POST',
            headers: this.getHeaders(),
            body: JSON.stringify(data)
        });
        return response.json();
    }

    async getPendingRequests(params?: {
        page?: number;
        per_page?: number;
        date_from?: string;
        date_to?: string;
    }): Promise<ApiResponse<PaginatedResponse<MealRequest>>> {
        const queryParams = new URLSearchParams();
        if (params) {
            Object.entries(params).forEach(([key, value]) => {
                if (value !== undefined) {
                    queryParams.append(key, value.toString());
                }
            });
        }

        const response = await fetch(`${this.baseUrl}/meal-request/pending?${queryParams}`, {
            headers: this.getHeaders()
        });
        return response.json();
    }

    async getAllRequests(params?: {
        page?: number;
        per_page?: number;
        status?: number;
        date_from?: string;
        date_to?: string;
        user_id?: number;
        search?: string;
    }): Promise<ApiResponse<PaginatedResponse<MealRequest> & {summary: MealRequestSummary}>> {
        const queryParams = new URLSearchParams();
        if (params) {
            Object.entries(params).forEach(([key, value]) => {
                if (value !== undefined) {
                    queryParams.append(key, value.toString());
                }
            });
        }

        const response = await fetch(`${this.baseUrl}/meal-request/?${queryParams}`, {
            headers: this.getHeaders()
        });
        return response.json();
    }

    async getMealRequest(id: number): Promise<ApiResponse<MealRequest>> {
        const response = await fetch(`${this.baseUrl}/meal-request/${id}`, {
            headers: this.getHeaders()
        });
        return response.json();
    }
}
```

## Frontend UI Components Suggestions

### 1. Meal Request Form Component
```jsx
const MealRequestForm = ({ onSubmit, initialData = null, isEditing = false }) => {
    const [formData, setFormData] = useState({
        date: initialData?.date || '',
        breakfast: initialData?.breakfast || 0,
        lunch: initialData?.lunch || 0,
        dinner: initialData?.dinner || 0,
        comment: initialData?.comment || ''
    });

    const [errors, setErrors] = useState({});
    const [loading, setLoading] = useState(false);

    const validateForm = () => {
        const newErrors = {};
        
        if (!formData.date) {
            newErrors.date = 'Date is required';
        } else if (new Date(formData.date) < new Date().setHours(0,0,0,0)) {
            newErrors.date = 'Cannot request meals for past dates';
        }
        
        if (!formData.breakfast && !formData.lunch && !formData.dinner) {
            newErrors.meals = 'At least one meal type must be requested';
        }
        
        return newErrors;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        const validationErrors = validateForm();
        
        if (Object.keys(validationErrors).length > 0) {
            setErrors(validationErrors);
            return;
        }

        setLoading(true);
        try {
            await onSubmit(formData);
        } catch (error) {
            setErrors(error.errors || { general: 'An error occurred' });
        } finally {
            setLoading(false);
        }
    };

    return (
        <form onSubmit={handleSubmit}>
            <div className="form-group">
                <label htmlFor="date">Date</label>
                <input
                    type="date"
                    id="date"
                    value={formData.date}
                    onChange={(e) => setFormData({...formData, date: e.target.value})}
                    min={new Date().toISOString().split('T')[0]}
                    required
                />
                {errors.date && <span className="error">{errors.date}</span>}
            </div>

            <div className="form-group">
                <label>Meals</label>
                <div className="checkbox-group">
                    <label>
                        <input
                            type="checkbox"
                            checked={formData.breakfast === 1}
                            onChange={(e) => setFormData({...formData, breakfast: e.target.checked ? 1 : 0})}
                        />
                        Breakfast
                    </label>
                    <label>
                        <input
                            type="checkbox"
                            checked={formData.lunch === 1}
                            onChange={(e) => setFormData({...formData, lunch: e.target.checked ? 1 : 0})}
                        />
                        Lunch
                    </label>
                    <label>
                        <input
                            type="checkbox"
                            checked={formData.dinner === 1}
                            onChange={(e) => setFormData({...formData, dinner: e.target.checked ? 1 : 0})}
                        />
                        Dinner
                    </label>
                </div>
                {errors.meals && <span className="error">{errors.meals}</span>}
            </div>

            <div className="form-group">
                <label htmlFor="comment">Comment (Optional)</label>
                <textarea
                    id="comment"
                    value={formData.comment}
                    onChange={(e) => setFormData({...formData, comment: e.target.value})}
                    maxLength={500}
                    placeholder="Any additional comments..."
                />
            </div>

            <button type="submit" disabled={loading}>
                {loading ? 'Submitting...' : (isEditing ? 'Update Request' : 'Create Request')}
            </button>
        </form>
    );
};
```

### 2. Meal Request List Component
```jsx
const MealRequestList = ({ requests, onApprove, onReject, onEdit, onCancel, isAdmin = false }) => {
    const getStatusBadge = (status) => {
        const statusConfig = {
            0: { text: 'Pending', color: 'orange' },
            1: { text: 'Approved', color: 'green' },
            2: { text: 'Rejected', color: 'red' },
            3: { text: 'Cancelled', color: 'gray' }
        };
        
        const config = statusConfig[status] || statusConfig[0];
        return (
            <span className={`badge badge-${config.color}`}>
                {config.text}
            </span>
        );
    };

    const getMealTypes = (request) => {
        const meals = [];
        if (request.breakfast) meals.push('Breakfast');
        if (request.lunch) meals.push('Lunch');
        if (request.dinner) meals.push('Dinner');
        return meals.join(', ');
    };

    return (
        <div className="meal-request-list">
            {requests.map(request => (
                <div key={request.id} className="meal-request-card">
                    <div className="card-header">
                        <h3>{formatDate(request.date)}</h3>
                        {getStatusBadge(request.status)}
                    </div>
                    
                    <div className="card-body">
                        <p><strong>Meals:</strong> {getMealTypes(request)}</p>
                        <p><strong>Requested by:</strong> {request.user.name}</p>
                        {request.comment && <p><strong>Comment:</strong> {request.comment}</p>}
                        
                        {request.approved_by_user && (
                            <p><strong>Processed by:</strong> {request.approved_by_user.name}</p>
                        )}
                        
                        {request.rejected_reason && (
                            <p><strong>Rejection reason:</strong> {request.rejected_reason}</p>
                        )}
                        
                        <p><strong>Created:</strong> {formatDateTime(request.created_at)}</p>
                    </div>
                    
                    <div className="card-actions">
                        {request.status === 0 && (
                            <>
                                {isAdmin ? (
                                    <>
                                        <button 
                                            onClick={() => onApprove(request.id)}
                                            className="btn btn-success"
                                        >
                                            Approve
                                        </button>
                                        <button 
                                            onClick={() => onReject(request.id)}
                                            className="btn btn-danger"
                                        >
                                            Reject
                                        </button>
                                    </>
                                ) : (
                                    <>
                                        <button 
                                            onClick={() => onEdit(request)}
                                            className="btn btn-primary"
                                        >
                                            Edit
                                        </button>
                                        <button 
                                            onClick={() => onCancel(request.id)}
                                            className="btn btn-warning"
                                        >
                                            Cancel
                                        </button>
                                    </>
                                )}
                            </>
                        )}
                    </div>
                </div>
            ))}
        </div>
    );
};
```

### 3. Pagination Component
```jsx
const Pagination = ({ pagination, onPageChange }) => {
    const { currentPage, lastPage, hasNext, hasPrev } = pagination;

    return (
        <div className="pagination">
            <button 
                onClick={() => onPageChange(currentPage - 1)}
                disabled={!hasPrev}
                className="btn btn-secondary"
            >
                Previous
            </button>
            
            <span className="page-info">
                Page {currentPage} of {lastPage}
            </span>
            
            <button 
                onClick={() => onPageChange(currentPage + 1)}
                disabled={!hasNext}
                className="btn btn-secondary"
            >
                Next
            </button>
        </div>
    );
};
```
