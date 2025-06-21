# Mess Management API Endpoints

## Overview
The Mess Management system has been extended with new endpoints to support comprehensive mess lifecycle management, including viewing mess information, leaving/closing messes, joining other messes, and managing join requests.

### Key Features
- **Mess Creation and Management**: Create, view, and manage mess information
- **Membership Management**: Join requests, accept/reject mechanisms, and user roles
- **Mess Status Handling**: Utilizes the `is_accepting_members` attribute, which is automatically calculated based on the mess status
- **Permission-Based Access Control**: Fine-grained control over mess management operations

## Authentication
All endpoints require authentication via Sanctum token and email verification.

## Endpoints

### For Users Currently in a Mess
*These endpoints require the user to be part of a mess (MessJoinChecker middleware)*

#### Get Current Mess Information
```
GET /api/mess-management/info
```
Returns detailed information about the user's current mess.

##### Example Response
```json
{
    "error": false,
    "message": "Success",
    "data": {
        "mess": {
            "id": 1,
            "name": "My Dining Mess",
            "status": "active",
            "ad_free": false,
            "all_user_add_meal": true,
            "fund_add_enabled": true,
            "created_at": "2025-06-16T10:00:00Z",
            "updated_at": "2025-06-16T10:00:00Z",
            "is_accepting_members": true,
            "active_month": {
                "id": 5,
                "name": "June 2025",
                "start_at": "2025-06-01T00:00:00Z",
                "end_at": "2025-06-30T23:59:59Z"
            }
        },
        "user_role": {
            "id": 1,
            "role": "admin",
            "is_admin": true,
            "permissions": [
                "USER_ADD",
                "USER_MANAGEMENT",
                "MEAL_ADD",
                "MEAL_MANAGEMENT",
                "MESS_JOIN_REQUEST_MANAGE"
            ]
        },
        "permissions": [
            "USER_ADD",
            "USER_MANAGEMENT",
            "MEAL_ADD",
            "MEAL_MANAGEMENT",
            "MESS_JOIN_REQUEST_MANAGE"
        ],
        "is_admin": true,
        "joined_at": "2025-06-01T00:00:00Z",
        "status": "active"
    },
    "errors": null,
    "error_code": null
}
```

Note that `is_accepting_members` is determined by the mess's status - it returns `true` when the mess status is `active`.

#### Leave Current Mess
```
POST /api/mess-management/leave
```
Allows a user to leave their current mess. Admin users cannot leave if they are the only admin.

##### Example Response
```json
{
    "error": false,
    "message": "Successfully left the mess",
    "data": null,
    "errors": null,
    "error_code": null
}
```

##### Error Response (Only Admin)
```json
{
    "error": true,
    "message": "Cannot leave mess. You are the only admin. Please assign another admin first.",
    "data": null,
    "errors": [],
    "error_code": null
}
```

#### Close Mess (Admin Only)
```
POST /api/mess-management/close
```
Allows mess administrators to permanently close a mess. Requires `MESS_CLOSE` permission.

##### Example Response
```json
{
    "error": false,
    "message": "Mess closed successfully",
    "data": {
        "status": "deactivated",
        "is_accepting_members": false
    },
    "errors": null,
    "error_code": null
}
```

##### Error Response (No Permission)
```json
{
    "error": true,
    "message": "Only admins can close a mess",
    "data": null,
    "errors": [],
    "error_code": null
}
```

#### Get User's Join Requests
```
GET /api/mess-management/join-requests
```
Returns all join requests made by the current user.

##### Example Response
```json
{
    "error": false,
    "message": "Success",
    "data": [
        {
            "id": 1,
            "old_user_id": 2,
            "new_mess_id": 1,
            "status": "pending",
            "rejection_reason": null,
            "created_at": "2025-06-21T14:30:00Z",
            "updated_at": "2025-06-21T14:30:00Z",
            "mess": {
                "id": 1,
                "name": "My Dining Mess"
            }
        },
        {
            "id": 2,
            "old_user_id": 2,
            "new_mess_id": 3,
            "status": "rejected",
            "rejection_reason": "Mess is currently full",
            "created_at": "2025-06-20T11:15:00Z",
            "updated_at": "2025-06-20T15:45:22Z",
            "mess": {
                "id": 3,
                "name": "Tech Team Lunch"
            }
        }
    ],
    "errors": null,
    "error_code": null
}
```

#### Cancel Join Request
```
DELETE /api/mess-management/join-requests/{request}
```
Allows users to cancel their pending join requests.

##### Example Response
```json
{
    "error": false,
    "message": "Join request cancelled successfully",
    "data": {
        "id": 1,
        "status": "cancelled"
    },
    "errors": null,
    "error_code": null
}
```

##### Error Response (Not Owner)
```json
{
    "error": true,
    "message": "You can only cancel your own join requests",
    "data": null,
    "errors": [],
    "error_code": null
}
```

#### Get Incoming Join Requests (Admin)
```
GET /api/mess-management/incoming-requests
```
Returns pending join requests for the user's mess. Requires `JOIN_REQUEST_MANAGEMENT` permission.

##### Example Response
```json
{
    "error": false,
    "message": "Success",
    "data": [
        {
            "id": 1,
            "old_user_id": 2,
            "new_mess_id": 1,
            "status": "pending",
            "rejection_reason": null,
            "created_at": "2025-06-21T14:30:00Z",
            "updated_at": "2025-06-21T14:30:00Z",
            "user": {
                "id": 2,
                "name": "Jane Doe",
                "email": "jane@example.com",
                "avatar": "https://example.com/avatars/jane.jpg"
            }
        },
        {
            "id": 3,
            "old_user_id": 5,
            "new_mess_id": 1,
            "status": "pending",
            "rejection_reason": null,
            "created_at": "2025-06-21T16:45:12Z",
            "updated_at": "2025-06-21T16:45:12Z",
            "user": {
                "id": 5,
                "name": "Alex Smith",
                "email": "alex@example.com",
                "avatar": null
            }
        }
    ],
    "errors": null,
    "error_code": null
}
```

#### Accept Join Request (Admin)
```
POST /api/mess-management/incoming-requests/{request}/accept
```
Accepts a pending join request. Requires `JOIN_REQUEST_MANAGEMENT` permission.

##### Example Response
```json
{
    "error": false,
    "message": "Join request accepted successfully",
    "data": {
        "id": 1,
        "status": "accepted",
        "mess_user": {
            "id": 5,
            "user_id": 2,
            "mess_id": 1,
            "mess_role_id": 3,
            "joined_at": "2025-06-21T15:10:00Z",
            "left_at": null,
            "status": "active"
        }
    },
    "errors": null,
    "error_code": null
}
```

##### Error Response (Invalid Status)
```json
{
    "error": true,
    "message": "Request is not in pending status",
    "data": null,
    "errors": [],
    "error_code": null
}
```

#### Reject Join Request (Admin)
```
POST /api/mess-management/incoming-requests/{request}/reject
```
Rejects a pending join request. Requires `JOIN_REQUEST_MANAGEMENT` permission.

##### Request Body
```json
{
    "reason": "Mess is currently full"
}
```

##### Validation Rules
- `reason`: optional, string, max 255 characters

##### Example Response
```json
{
    "error": false,
    "message": "Join request rejected successfully",
    "data": {
        "id": 1,
        "status": "rejected", 
        "rejection_reason": "Mess is currently full"
    },
    "errors": null,
    "error_code": null
}
```

##### Error Response (No Permission)
```json
{
    "error": true,
    "message": "You do not have permission to manage join requests",
    "data": null,
    "errors": [],
    "error_code": null
}
```

### For All Authenticated Users
*These endpoints are available to all authenticated users*

#### Get Available Messes
```
GET /api/mess-management/available
```
Returns a list of messes that the user can potentially join.

##### Example Response
```json
{
    "error": false,
    "message": "Success",
    "data": [
        {
            "mess": {
                "id": 1,
                "name": "My Dining Mess",
                "status": "active",
                "ad_free": false,
                "all_user_add_meal": true,
                "fund_add_enabled": true,
                "created_at": "2025-06-16T10:00:00Z",
                "updated_at": "2025-06-16T10:00:00Z",
                "is_accepting_members": true
            },
            "member_count": 5,
            "is_accepting_members": true,
            "join_request_exists": false
        },
        {
            "mess": {
                "id": 2,
                "name": "Office Lunch Group",
                "status": "active",
                "ad_free": false,
                "all_user_add_meal": true,
                "fund_add_enabled": true,
                "created_at": "2025-06-15T09:30:00Z",
                "updated_at": "2025-06-15T09:30:00Z",
                "is_accepting_members": true
            },
            "member_count": 12,
            "is_accepting_members": true,
            "join_request_exists": true
        }
    ],
    "errors": null,
    "error_code": null
}
```

Note: Only messes where `is_accepting_members` is true (derived from the mess status being `active`) are included in this list.

#### Send Join Request
```
POST /api/mess-management/join-request/{mess}
```
Sends a join request to the specified mess. Users can only have one pending request at a time.

##### Example Response
```json
{
    "error": false,
    "message": "Join request sent successfully",
    "data": {
        "id": 1,
        "old_user_id": 1,
        "new_mess_id": 1,
        "status": "pending",
        "rejection_reason": null,
        "created_at": "2025-06-21T14:30:00Z",
        "updated_at": "2025-06-21T14:30:00Z",
        "mess": {
            "id": 1,
            "name": "My Dining Mess"
        }
    },
    "errors": null,
    "error_code": null
}
```

##### Error Response (Already Has Request)
```json
{
    "error": true,
    "message": "You already have a pending request. Please cancel it before sending a new one.",
    "data": null,
    "errors": [],
    "error_code": null
}
```

##### Error Response (Mess Not Accepting)
```json
{
    "error": true,
    "message": "Mess is not active",
    "data": null,
    "errors": [],
    "error_code": null
}
```

## Permission System
The following permissions are used:
- `MESS_CLOSE`: Required to close a mess
- `JOIN_REQUEST_MANAGEMENT`: Required to view, accept, or reject join requests

## Business Rules
1. Users can only be part of one mess at a time
2. Users can only have one pending join request at a time
3. Users must cancel existing join requests before creating new ones
4. Mess administrators cannot leave if they are the only admin
5. Only users with appropriate permissions can manage join requests
6. Only mess administrators can close messes

## Response Format
All endpoints return JSON responses with consistent structure:

### Success Response
```json
{
    "error": false,
    "message": "Success message",
    "data": {
        // Response data specific to each endpoint
    },
    "errors": null,
    "error_code": null
}
```

### Error Response
```json
{
    "error": true,
    "message": "Error message",
    "data": null,
    "errors": {
        // Error details if applicable
    },
    "error_code": null
}
```

### Status Codes
- **200 OK**: Request succeeded
- **201 Created**: Resource created successfully
- **400 Bad Request**: Invalid input data
- **401 Unauthorized**: Missing or invalid authentication
- **403 Forbidden**: Not authorized to perform the action
- **404 Not Found**: Requested resource not found
- **422 Unprocessable Entity**: Validation errors
- **500 Internal Server Error**: Server error

## Error Handling
The system includes comprehensive error handling for:
- Permission violations
- Business rule violations (e.g., trying to leave as the only admin)
- Invalid requests (e.g., duplicate join requests)
- Resource not found errors

## Data Models

### Mess
```json
{
    "id": 1,
    "name": "My Dining Mess",
    "status": "active", // active, deactivated, deleted
    "ad_free": false,
    "all_user_add_meal": true,
    "fund_add_enabled": true,
    "created_at": "2025-06-16T10:00:00Z",
    "updated_at": "2025-06-16T10:00:00Z",
    "is_accepting_members": true
}
```

### MessUser
```json
{
    "id": 1,
    "user_id": 1,
    "mess_id": 1,
    "mess_role_id": 1,
    "joined_at": "2025-06-16T10:00:00Z",
    "left_at": null,
    "status": "active", // active, inactive, removed
    "created_at": "2025-06-16T10:00:00Z",
    "updated_at": "2025-06-16T10:00:00Z"
}
```

### MessRole
```json
{
    "id": 1,
    "mess_id": 1,
    "role": "admin", // admin, manager, member
    "is_default": false,
    "is_admin": true,
    "created_at": "2025-06-16T10:00:00Z",
    "updated_at": "2025-06-16T10:00:00Z"
}
```

### MessRequest
```json
{
    "id": 1,
    "user_id": 2,
    "mess_id": 1,
    "status": "pending", // pending, accepted, rejected, cancelled
    "rejection_reason": null,
    "created_at": "2025-06-21T14:30:00Z",
    "updated_at": "2025-06-21T14:30:00Z"
}
```
