# My Dining API Documentation

## Overview
This is the comprehensive API documentation for the My Dining application - a mess management system that handles meal tracking, deposits, purchases, and user management.

## Documentation Index

### Core Documentation
- **[Model Relationships & Dependencies](./model-relationships.md)** - Complete database relationships and data flow
- **[Database Schema](./database-schema.md)** - Visual database structure and architecture
- **[Postman Collection](./postman-collection.json)** - API testing collection
- **[OpenAPI Specification](./openapi.yaml)** - Machine-readable API specs

## Base Information
- **Base URL**: `/api`
- **Version**: v1 (available at `/api/v1`)
- **Authentication**: Sanctum Token Authentication
- **Content-Type**: `application/json`
- **Accept**: `application/json`

## Response Format
All API responses follow a consistent structure:

### Success Response
```json
{
    "success": true,
    "message": "Success message",
    "data": {
        // Response data
    }
}
```

### Error Response
```json
{
    "success": false,
    "message": "Error message",
    "errors": {
        // Error details or validation errors
    }
}
```

### Validation Error Response
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field_name": ["validation error messages"]
    }
}
```

## Authentication

### Sign Up
**Endpoint**: `POST /api/auth/sign-up`
**Access**: Public

#### Request Body
```json
{
    "name": "John Doe",
    "email": "john@example.com",
    "country_id": 1,
    "country_code": "+1",
    "phone": "1234567890",
    "city": "New York",
    "gender": "male",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### Validation Rules
- `name`: required, string, max 255 characters
- `email`: required, email, max 255 characters, unique
- `country_id`: required without country_code, must exist in countries table
- `country_code`: required without country_id, string, max 5 characters, must exist in countries
- `phone`: required, string, max 15 characters, valid phone number for country
- `city`: required, string, max 30 characters
- `gender`: required, enum (male, female, other)
- `password`: required, string, min 8 characters, confirmed

#### Response
```json
{
    "success": true,
    "message": "Account created successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "token": "sanctum_token_here"
    }
}
```

### Login
**Endpoint**: `POST /api/auth/login`
**Access**: Public

#### Request Body
```json
{
    "email": "john@example.com",
    "password": "password123"
}
```

#### Validation Rules
- `email`: required, email, max 255 characters
- `password`: required, string, min 8 characters

#### Response
```json
{
    "success": true,
    "message": "Login successful",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        },
        "token": "sanctum_token_here"
    }
}
```

### Check Login Status
**Endpoint**: `GET /api/auth/check-login`
**Access**: Protected (requires authentication)

#### Response
```json
{
    "success": true,
    "message": "User is authenticated",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com"
        }
    }
}
```

## Profile Management

### Get Profile
**Endpoint**: `GET /api/profile`
**Access**: Protected (requires authentication)

#### Response
```json
{
    "success": true,
    "message": "Profile retrieved successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "phone": "+1-1234567890",
            "city": "New York",
            "gender": "male",
            "photo_url": "/storage/avatars/1_1234567890.jpg",
            "country": {
                "id": 1,
                "name": "United States",
                "dial_code": "+1",
                "code": "US"
            }
        },
        "profile_completion": 85,
        "last_updated": "2025-06-17T10:30:00.000000Z"
    }
}
```

### Update Profile
**Endpoint**: `PUT /api/profile`
**Access**: Protected (requires authentication)

#### Request Body
```json
{
    "name": "John Smith",
    "city": "Los Angeles",
    "gender": "male"
}
```

#### Validation Rules
- `name`: sometimes, required, string, max 255 characters
- `city`: sometimes, required, string, max 255 characters
- `gender`: sometimes, required, enum (male, female, other)

#### Response
```json
{
    "success": true,
    "message": "Profile updated successfully",
    "data": {
        "user": {
            "id": 1,
            "name": "John Smith",
            "email": "john@example.com",
            "city": "Los Angeles",
            "gender": "male"
        }
    }
}
```

### Upload Avatar
**Endpoint**: `POST /api/profile/avatar`
**Access**: Protected (requires authentication)

#### Request Body
- Content-Type: `multipart/form-data`
- Form field: `avatar` (image file)

#### Validation Rules
- `avatar`: required, image, mimes (jpeg, png, jpg, gif), max 2MB

#### Response
```json
{
    "success": true,
    "message": "Avatar uploaded successfully",
    "data": {
        "photo_url": "/storage/avatars/1_1234567890.jpg"
    }
}
```

### Remove Avatar
**Endpoint**: `DELETE /api/profile/avatar`
**Access**: Protected (requires authentication)

#### Response
```json
{
    "success": true,
    "message": "Avatar removed successfully",
    "data": {}
}
```

## Country Management

### Get Countries List
**Endpoint**: `GET /api/country/list`
**Access**: Public

#### Response
```json
{
    "success": true,
    "message": "Countries retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "United States",
            "dial_code": "+1",
            "code": "US"
        }
    ]
}
```

## Mess Management

### Create Mess
**Endpoint**: `POST /api/mess/create`
**Access**: Protected (user must not be in a mess)

#### Request Body
```json
{
    "mess_name": "My Dining Mess"
}
```

#### Validation Rules
- `mess_name`: required, string, max 255 characters

#### Response
```json
{
    "success": true,
    "message": "Mess created successfully",
    "data": {
        "mess": {
            "id": 1,
            "name": "My Dining Mess",
            "created_at": "2025-06-16T10:00:00Z"
        }
    }
}
```

### Get Mess User Information
**Endpoint**: `GET /api/mess/mess-user/{user_id?}`
**Access**: Protected (requires mess membership)

#### Parameters
- `user_id` (optional): User ID to get mess information for (defaults to authenticated user)

#### Response
```json
{
    "success": true,
    "message": "Mess user information retrieved",
    "data": {
        "mess_user": {
            "id": 1,
            "user_id": 1,
            "mess_id": 1,
            "role": "admin",
            "status": "active"
        }
    }
}
```

## Mess Management (Extended)

### Get Current Mess Information
**Endpoint**: `GET /api/mess-management/info`
**Access**: Protected (requires mess membership)

#### Response
```json
{
    "success": true,
    "message": "Current mess information retrieved successfully",
    "data": {
        "mess": {
            "id": 1,
            "name": "My Dining Mess",
            "created_at": "2025-01-01T00:00:00Z",
            "updated_at": "2025-01-15T10:30:00Z"
        },
        "user_role": {
            "id": 1,
            "role": "admin",
            "permissions": [
                "USER_MANAGEMENT",
                "MESS_CLOSE",
                "JOIN_REQUEST_MANAGEMENT"
            ]
        },
        "member_count": 5,
        "active_month": {
            "id": 1,
            "name": "January 2025",
            "start_date": "2025-01-01",
            "end_date": "2025-01-31",
            "is_active": true
        },
        "recent_activities": [
            {
                "type": "user_joined",
                "user": "Jane Doe",
                "date": "2025-01-10T14:30:00Z"
            }
        ]
    }
}
```

### Leave Current Mess
**Endpoint**: `POST /api/mess-management/leave`
**Access**: Protected (requires mess membership)

#### Response
```json
{
    "success": true,
    "message": "Successfully left the mess",
    "data": {
        "left_mess": {
            "id": 1,
            "name": "My Dining Mess"
        },
        "left_at": "2025-06-18T10:30:00Z"
    }
}
```

#### Error Response (Only Admin)
```json
{
    "success": false,
    "message": "Cannot leave mess: You are the only administrator. Please assign another administrator before leaving.",
    "errors": {
        "admin_constraint": "Admin users cannot leave if they are the only admin"
    }
}
```

### Close Mess
**Endpoint**: `POST /api/mess-management/close`
**Access**: Protected (requires MESS_CLOSE permission)

#### Request Body
```json
{
    "confirmation": true,
    "reason": "Mess operations concluded"
}
```

#### Validation Rules
- `confirmation`: required, boolean, must be true
- `reason`: optional, string, max 500 characters

#### Response
```json
{
    "success": true,
    "message": "Mess has been successfully closed",
    "data": {
        "closed_mess": {
            "id": 1,
            "name": "My Dining Mess",
            "closed_at": "2025-06-18T10:30:00Z",
            "closed_by": "John Doe"
        },
        "final_summary": {
            "total_members": 5,
            "total_months": 6,
            "final_balance": 1500.00
        }
    }
}
```

### Get Available Messes
**Endpoint**: `GET /api/mess-management/available`
**Access**: Protected (authenticated users only)

#### Query Parameters
- `search`: optional, string - Search by mess name
- `limit`: optional, integer - Limit results (default: 20)

#### Response
```json
{
    "success": true,
    "message": "Available messes retrieved successfully",
    "data": {
        "messes": [
            {
                "id": 2,
                "name": "University Hostel Mess",
                "member_count": 8,
                "created_at": "2025-01-01T00:00:00Z",
                "location": "Dhaka",
                "description": "Hostel mess for university students",
                "is_accepting_members": true,
                "join_request_exists": false
            },
            {
                "id": 3,
                "name": "Office Colleagues Mess",
                "member_count": 6,
                "created_at": "2025-02-01T00:00:00Z",
                "location": "Chittagong",
                "description": "Mess for office colleagues",
                "is_accepting_members": true,
                "join_request_exists": true
            }
        ],
        "total": 2,
        "current_user_mess_status": "not_in_mess"
    }
}
```

### Send Join Request
**Endpoint**: `POST /api/mess-management/join-request/{mess_id}`
**Access**: Protected (authenticated users only)

#### Parameters
- `mess_id`: required, integer - ID of the mess to join

#### Request Body
```json
{
    "message": "I would like to join your mess. I am a responsible member and will follow all rules."
}
```

#### Validation Rules
- `message`: optional, string, max 500 characters

#### Response
```json
{
    "success": true,
    "message": "Join request sent successfully",
    "data": {
        "join_request": {
            "id": 1,
            "mess_id": 2,
            "mess_name": "University Hostel Mess",
            "user_id": 1,
            "status": "pending",
            "message": "I would like to join your mess. I am a responsible member and will follow all rules.",
            "requested_at": "2025-06-18T10:30:00Z"
        }
    }
}
```

#### Error Response (Already Has Pending Request)
```json
{
    "success": false,
    "message": "You already have a pending join request. Please cancel it before creating a new one.",
    "errors": {
        "pending_request": {
            "mess_name": "Office Colleagues Mess",
            "request_id": 2,
            "requested_at": "2025-06-17T14:20:00Z"
        }
    }
}
```

### Get User's Join Requests
**Endpoint**: `GET /api/mess-management/join-requests`
**Access**: Protected (requires mess membership)

#### Query Parameters
- `status`: optional, enum (pending, accepted, rejected) - Filter by status
- `limit`: optional, integer - Limit results (default: 20)

#### Response
```json
{
    "success": true,
    "message": "Join requests retrieved successfully",
    "data": {
        "join_requests": [
            {
                "id": 1,
                "mess": {
                    "id": 2,
                    "name": "University Hostel Mess",
                    "member_count": 8
                },
                "status": "pending",
                "message": "I would like to join your mess.",
                "requested_at": "2025-06-18T10:30:00Z",
                "updated_at": "2025-06-18T10:30:00Z",
                "can_cancel": true
            },
            {
                "id": 2,
                "mess": {
                    "id": 3,
                    "name": "Office Colleagues Mess",
                    "member_count": 6
                },
                "status": "rejected",
                "message": "Looking to join for lunch arrangements",
                "requested_at": "2025-06-15T09:00:00Z",
                "updated_at": "2025-06-16T11:30:00Z",
                "rejection_reason": "Currently not accepting new members",
                "can_cancel": false
            }
        ],
        "total": 2,
        "pending_count": 1
    }
}
```

### Cancel Join Request
**Endpoint**: `DELETE /api/mess-management/join-requests/{request_id}`
**Access**: Protected (requires mess membership)

#### Parameters
- `request_id`: required, integer - ID of the join request to cancel

#### Response
```json
{
    "success": true,
    "message": "Join request cancelled successfully",
    "data": {
        "cancelled_request": {
            "id": 1,
            "mess_name": "University Hostel Mess",
            "cancelled_at": "2025-06-18T11:00:00Z"
        }
    }
}
```

### Get Incoming Join Requests (Admin)
**Endpoint**: `GET /api/mess-management/incoming-requests`
**Access**: Protected (requires JOIN_REQUEST_MANAGEMENT permission)

#### Query Parameters
- `status`: optional, enum (pending, accepted, rejected) - Filter by status
- `limit`: optional, integer - Limit results (default: 20)

#### Response
```json
{
    "success": true,
    "message": "Incoming join requests retrieved successfully",
    "data": {
        "join_requests": [
            {
                "id": 3,
                "user": {
                    "id": 5,
                    "name": "Alice Johnson",
                    "email": "alice@example.com",
                    "phone": "+880-1234567890",
                    "city": "Dhaka"
                },
                "status": "pending",
                "message": "I am looking for a reliable mess to join. I can contribute to cooking duties.",
                "requested_at": "2025-06-18T09:15:00Z",
                "user_background": {
                    "previous_mess_experience": true,
                    "dietary_restrictions": "Vegetarian"
                }
            },
            {
                "id": 4,
                "user": {
                    "id": 6,
                    "name": "Bob Smith",
                    "email": "bob@example.com",
                    "phone": "+880-1234567891",
                    "city": "Dhaka"
                },
                "status": "pending",
                "message": "Student looking for affordable mess arrangement near university.",
                "requested_at": "2025-06-17T16:45:00Z"
            }
        ],
        "total": 2,
        "pending_count": 2
    }
}
```

### Accept Join Request (Admin)
**Endpoint**: `POST /api/mess-management/incoming-requests/{request_id}/accept`
**Access**: Protected (requires JOIN_REQUEST_MANAGEMENT permission)

#### Parameters
- `request_id`: required, integer - ID of the join request to accept

#### Request Body
```json
{
    "welcome_message": "Welcome to our mess! Please check the house rules document.",
    "assign_role": "member",
    "initiate_for_current_month": true
}
```

#### Validation Rules
- `welcome_message`: optional, string, max 500 characters
- `assign_role`: optional, enum (member, admin), default: member
- `initiate_for_current_month`: optional, boolean, default: true

#### Response
```json
{
    "success": true,
    "message": "Join request accepted successfully",
    "data": {
        "accepted_request": {
            "id": 3,
            "user": {
                "id": 5,
                "name": "Alice Johnson",
                "email": "alice@example.com"
            },
            "accepted_at": "2025-06-18T11:30:00Z",
            "accepted_by": "John Doe"
        },
        "new_member": {
            "mess_user_id": 6,
            "role": "member",
            "status": "active",
            "initiated_for_current_month": true
        },
        "welcome_message": "Welcome to our mess! Please check the house rules document."
    }
}
```

### Reject Join Request (Admin)
**Endpoint**: `POST /api/mess-management/incoming-requests/{request_id}/reject`
**Access**: Protected (requires JOIN_REQUEST_MANAGEMENT permission)

#### Parameters
- `request_id`: required, integer - ID of the join request to reject

#### Request Body
```json
{
    "reason": "Currently at full capacity. Please try again next month.",
    "allow_future_requests": true
}
```

#### Validation Rules
- `reason`: optional, string, max 500 characters
- `allow_future_requests`: optional, boolean, default: true

#### Response
```json
{
    "success": true,
    "message": "Join request rejected",
    "data": {
        "rejected_request": {
            "id": 4,
            "user": {
                "id": 6,
                "name": "Bob Smith",
                "email": "bob@example.com"
            },
            "rejected_at": "2025-06-18T12:00:00Z",
            "rejected_by": "John Doe",
            "reason": "Currently at full capacity. Please try again next month."
        }
    }
}
```

## Enumerations

### Gender
- `male`: Male gender
- `female`: Female gender  
- `other`: Other/Undefined gender

### Month Type
- `manual`: Manually created month with custom start date
- `automatic`: Automatically created month based on calendar month/year

### Purchase Type
- `other`: Other types of purchases
- `meal`: Meal-related purchases

### Purchase Request Status
- `0` (PENDING): Request is pending approval
- `1` (APPROVED): Request has been approved
- `2` (REJECTED): Request has been rejected

### Mess Join Request Status
- `0` (PENDING): Join request is pending review
- `1` (ACCEPTED): Join request has been accepted
- `2` (REJECTED): Join request has been rejected
- `3` (CANCELLED): Join request was cancelled by the user

## Status Codes

- **200 OK**: Request successful
- **201 Created**: Resource created successfully
- **400 Bad Request**: Invalid request data
- **401 Unauthorized**: Authentication required
- **403 Forbidden**: Access denied (permissions)
- **404 Not Found**: Resource not found
- **422 Unprocessable Entity**: Validation failed
- **500 Internal Server Error**: Server error

## Error Handling

### Common Error Responses

#### Authentication Required
```json
{
    "success": false,
    "message": "Unauthenticated.",
    "errors": null
}
```

#### Permission Denied
```json
{
    "success": false,
    "message": "Access denied. Required permission: USER_MANAGEMENT",
    "errors": null
}
```

#### Resource Not Found
```json
{
    "success": false,
    "message": "The requested resource was not found.",
    "errors": null
}
```

#### Validation Error
```json
{
    "success": false,
    "message": "Validation failed",
    "errors": {
        "email": ["The email field is required."],
        "password": ["The password must be at least 8 characters."]
    }
}
```

## Middleware and Permissions

### Authentication Middleware
- `auth:sanctum`: Requires valid Sanctum token
- `EmailVerified`: Requires verified email address

### Mess-related Middleware
- `MessJoinChecker`: User must be part of a mess
- `MustNotMessJoinChecker`: User must NOT be part of a mess
- `MonthChecker`: Validates active month requirements
- `mess.user`: Requires mess user status

### Permission System
- `MessPermission`: Checks specific mess permissions
  - `USER_ADD`: Can add users to mess
  - `USER_MANAGEMENT`: Can manage users
  - `MESS_CLOSE`: Can close/terminate a mess
  - `JOIN_REQUEST_MANAGEMENT`: Can view, accept, or reject join requests

### Mess Roles
- `admin`: Full administrative access to mess management
- `member`: Standard member access to mess features
- `viewer`: Read-only access to mess information

## Rate Limiting
API endpoints may be subject to rate limiting. Standard Laravel rate limiting applies.

## Versioning
The API supports versioning through URL prefixes:
- Current version: `/api/v1`
- Future versions will be available at `/api/v2`, etc.

---

**Note**: This documentation covers the current state of the API including the new mess management features. For the most up-to-date information, please refer to the source code or contact the development team.

## Related Documentation

- **[Model Relationships & Dependencies](./model-relationships.md)**: Complete database schema and model relationship documentation
- **[Mess Management API](./mess-management-api.md)**: Detailed documentation for mess management endpoints
- **[Postman Collection](./postman-collection.json)**: Ready-to-import API testing collection
- **[OpenAPI Specification](./openapi.yaml)**: Machine-readable API specification
