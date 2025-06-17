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

## Mess Member Management

### List Mess Members
**Endpoint**: `GET /api/member/list`
**Access**: Protected (requires mess membership)

#### Response
```json
{
    "success": true,
    "message": "Mess members retrieved successfully",
    "data": [
        {
            "id": 1,
            "user": {
                "id": 1,
                "name": "John Doe",
                "email": "john@example.com"
            },
            "role": "admin",
            "status": "active"
        }
    ]
}
```

### Create User and Add to Mess
**Endpoint**: `POST /api/member/create-and-add`
**Access**: Protected (requires USER_ADD or USER_MANAGEMENT permission)

#### Request Body
```json
{
    "name": "Jane Doe",
    "email": "jane@example.com",
    "country_id": 1,
    "phone": "1234567891",
    "city": "New York",
    "gender": "female",
    "password": "password123",
    "password_confirmation": "password123"
}
```

#### Validation Rules
Same as sign-up endpoint

#### Response
```json
{
    "success": true,
    "message": "User created and added to mess successfully",
    "data": {
        "user": {
            "id": 2,
            "name": "Jane Doe",
            "email": "jane@example.com"
        },
        "mess_user": {
            "id": 2,
            "role": "member",
            "status": "active"
        }
    }
}
```

### Initiate User for Month
**Endpoint**: `POST /api/member/initiate/add/{mess_user_id}`
**Access**: Protected (requires active month)

#### Response
```json
{
    "success": true,
    "message": "User initiated successfully",
    "data": {
        "initiated": true
    }
}
```

### Initiate All Users
**Endpoint**: `POST /api/member/initiate/add/all`
**Access**: Protected

#### Response
```json
{
    "success": true,
    "message": "All users initiated successfully",
    "data": {
        "initiated_count": 5
    }
}
```

### Get Initiated Users
**Endpoint**: `GET /api/member/initiated/{status}`
**Access**: Protected

#### Parameters
- `status`: boolean (true/false) - filter by initiation status

#### Response
```json
{
    "success": true,
    "message": "Initiated users retrieved",
    "data": [
        {
            "id": 1,
            "user": {
                "name": "John Doe"
            },
            "initiated": true
        }
    ]
}
```

## Month Management

### Create Month
**Endpoint**: `POST /api/month/create`
**Access**: Protected (requires mess membership)

#### Request Body

##### Automatic Month
```json
{
    "name": "January 2025",
    "type": "automatic",
    "month": 1,
    "year": 2025,
    "force_close_other": false
}
```

##### Manual Month
```json
{
    "name": "Custom Period",
    "type": "manual",
    "start_at": "2025-01-15",
    "force_close_other": false
}
```

#### Validation Rules
- `name`: nullable, string, max 20 characters
- `type`: required, enum (automatic, manual)
- `month`: nullable, integer, 1-12, required if type is automatic
- `year`: nullable, integer, current year, required if type is automatic
- `start_at`: nullable, date, required if type is manual
- `force_close_other`: nullable, boolean

#### Response
```json
{
    "success": true,
    "message": "Month created successfully",
    "data": {
        "month": {
            "id": 1,
            "name": "January 2025",
            "type": "automatic",
            "start_date": "2025-01-01",
            "status": "active"
        }
    }
}
```

### List Months
**Endpoint**: `GET /api/month/list`
**Access**: Protected (requires mess membership)

#### Response
```json
{
    "success": true,
    "message": "Months retrieved successfully",
    "data": [
        {
            "id": 1,
            "name": "January 2025",
            "type": "automatic",
            "start_date": "2025-01-01",
            "status": "active"
        }
    ]
}
```

### Change Month Status
**Endpoint**: `PUT /api/month/change-status`
**Access**: Protected (requires mess membership)

#### Request Body
```json
{
    "status": true
}
```

#### Validation Rules
- `status`: required, boolean

### Get Month Details
**Endpoint**: `GET /api/month/show/{monthId?}`
**Access**: Protected (requires mess membership)

#### Parameters
- `monthId` (optional): Specific month ID (defaults to current active month)

#### Response
```json
{
    "success": true,
    "message": "Month details retrieved successfully",
    "data": {
        "month": {
            "id": 1,
            "name": "January 2025",
            "type": "automatic",
            "start_at": "2025-01-01T00:00:00Z",
            "end_at": "2025-01-31T23:59:59Z",
            "is_active": true
        },
        "user_count": 5,
        "total_meals": {
            "breakfast": 150,
            "lunch": 180,
            "dinner": 170
        },
        "financial_summary": {
            "total_deposits": 25000.00,
            "total_purchases": 18000.00,
            "total_other_costs": 2000.00,
            "balance": 5000.00
        },
        "recent_activities": {
            "latest_meals": [...],
            "latest_deposits": [...],
            "latest_purchases": [...]
        }
    }
}
```

### Get Month Summary
**Endpoint**: `GET /api/month/summary/{monthId?}`
**Access**: Protected (requires mess membership)

#### Parameters
- `monthId` (optional): Specific month ID (defaults to current active month)

#### Query Parameters
- `include_user_details`: boolean - Include per-user breakdown
- `include_daily_breakdown`: boolean - Include daily activity breakdown

#### Response
```json
{
    "success": true,
    "message": "Month summary retrieved successfully",
    "data": {
        "month_info": {
            "id": 1,
            "name": "January 2025",
            "type": "automatic",
            "start_date": "2025-01-01",
            "end_date": "2025-01-31",
            "is_active": true
        },
        "financial_summary": {
            "total_deposits": 25000.00,
            "total_purchases": 18000.00,
            "total_other_costs": 2000.00,
            "net_balance": 5000.00
        },
        "meal_summary": {
            "total_breakfast": 150,
            "total_lunch": 180,
            "total_dinner": 170,
            "total_meals": 500
        },
        "user_summary": {
            "total_users": 5,
            "active_users": 5
        },
        "user_details": [
            {
                "user_id": 1,
                "name": "John Doe",
                "total_deposits": 5000.00,
                "total_meals": 90,
                "meal_cost_breakdown": {
                    "breakfast": 30,
                    "lunch": 35,
                    "dinner": 32
                }
            }
        ],
        "daily_breakdown": [
            {
                "date": "2025-01-01",
                "meals": {
                    "breakfast": 5,
                    "lunch": 5,
                    "dinner": 5
                },
                "deposits": 1000.00,
                "purchases": 500.00,
                "other_costs": 0.00
            }
        ]
    }
}
```

### Close Month
**Endpoint**: `POST /api/month/close`
**Access**: Protected (requires mess membership)

#### Request Body
```json
{
    "create_next_month": true,
    "next_month_type": "automatic",
    "next_month_name": "February 2025"
}
```

#### Validation Rules
- `create_next_month`: nullable, boolean
- `next_month_type`: nullable, enum (automatic, manual)
- `next_month_name`: nullable, string, max 20 characters

#### Response
```json
{
    "success": true,
    "message": "Month closed successfully and next month created",
    "data": {
        "closed_month": {
            "id": 1,
            "name": "January 2025",
            "end_at": "2025-01-31T23:59:59Z"
        },
        "next_month": {
            "id": 2,
            "name": "February 2025",
            "type": "automatic",
            "start_at": "2025-02-01T00:00:00Z"
        }
    }
}
```

### Duplicate Month
**Endpoint**: `POST /api/month/{monthId}/duplicate`
**Access**: Protected (requires mess membership)

#### Request Body
```json
{
    "name": "March 2025",
    "type": "automatic",
    "month": 3,
    "year": 2025,
    "copy_initiated_users": true
}
```

#### Validation Rules
- `name`: required, string, max 20 characters
- `type`: required, enum (automatic, manual)
- `month`: nullable, integer, 1-12, required if type is automatic
- `year`: nullable, integer, min current year, required if type is automatic
- `start_at`: nullable, date, required if type is manual
- `copy_initiated_users`: nullable, boolean

#### Response
```json
{
    "success": true,
    "message": "Month duplicated successfully",
    "data": {
        "id": 3,
        "name": "March 2025",
        "type": "automatic",
        "start_at": "2025-03-01T00:00:00Z",
        "copied_users": 5
    }
}
```

### Compare Months
**Endpoint**: `GET /api/month/compare`
**Access**: Protected (requires mess membership)

#### Query Parameters
- `month1_id`: required, integer - First month to compare
- `month2_id`: required, integer - Second month to compare
- `comparison_type`: nullable, enum (financial, meals, users, all)

#### Response
```json
{
    "success": true,
    "message": "Month comparison completed successfully",
    "data": {
        "month1": {
            "id": 1,
            "name": "January 2025",
            "period": "2025-01-01 to 2025-01-31"
        },
        "month2": {
            "id": 2,
            "name": "February 2025",
            "period": "2025-02-01 to 2025-02-29"
        },
        "financial_comparison": {
            "deposits": {
                "month1": 25000.00,
                "month2": 28000.00,
                "difference": -3000.00
            },
            "expenses": {
                "month1": 20000.00,
                "month2": 22000.00,
                "difference": -2000.00
            }
        },
        "meal_comparison": {
            "total_meals": {
                "month1": 500,
                "month2": 520
            },
            "breakdown": {
                "breakfast": {
                    "month1": 150,
                    "month2": 155
                },
                "lunch": {
                    "month1": 180,
                    "month2": 185
                },
                "dinner": {
                    "month1": 170,
                    "month2": 180
                }
            }
        },
        "user_comparison": {
            "initiated_users": {
                "month1": 5,
                "month2": 6,
                "difference": -1
            }
        }
    }
}
```

### Get Month Statistics
**Endpoint**: `GET /api/month/statistics`
**Access**: Protected (requires mess membership)

#### Query Parameters
- `period`: nullable, enum (last_3_months, last_6_months, last_year, all)
- `metrics[]`: nullable, array of strings (total_deposits, total_expenses, total_meals, user_count, avg_meal_cost)

#### Response
```json
{
    "success": true,
    "message": "Statistics retrieved successfully",
    "data": {
        "period": "last_6_months",
        "month_count": 6,
        "date_range": {
            "start": "2024-08-01",
            "end": "2025-01-31"
        },
        "total_deposits": 150000.00,
        "total_expenses": 120000.00,
        "total_meals": 3000,
        "avg_user_count": 5.2,
        "avg_meal_cost": 40.00,
        "monthly_breakdown": [
            {
                "month_id": 1,
                "name": "January 2025",
                "deposits": 25000.00,
                "expenses": 20000.00,
                "meals": 500,
                "users": 5
            }
        ]
    }
}
```

### Export Month Data
**Endpoint**: `GET /api/month/export/{monthId?}`
**Access**: Protected (requires mess membership)

#### Parameters
- `monthId` (optional): Specific month ID (defaults to current active month)

#### Query Parameters
- `format`: nullable, enum (json, csv, excel)
- `include_details`: nullable, boolean
- `sections[]`: nullable, array (meals, deposits, purchases, other_costs, funds, summary)

#### Response
```json
{
    "success": true,
    "message": "Month data exported successfully",
    "data": {
        "month_info": {
            "id": 1,
            "name": "January 2025",
            "type": "automatic",
            "start_date": "2025-01-01",
            "end_date": "2025-01-31",
            "exported_at": "2025-06-16T10:00:00Z"
        },
        "summary": {
            "total_deposits": 25000.00,
            "total_purchases": 18000.00,
            "total_other_costs": 2000.00,
            "total_meals": 500,
            "user_count": 5
        },
        "meals": [...],
        "deposits": [...],
        "purchases": [...]
    }
}
```

### Get Month Timeline
**Endpoint**: `GET /api/month/timeline/{monthId?}`
**Access**: Protected (requires mess membership)

#### Parameters
- `monthId` (optional): Specific month ID (defaults to current active month)

#### Query Parameters
- `start_date`: nullable, date
- `end_date`: nullable, date
- `activity_types[]`: nullable, array (meals, deposits, purchases, other_costs, user_actions)
- `user_id`: nullable, integer - Filter by specific user

#### Response
```json
{
    "success": true,
    "message": "Activity timeline retrieved successfully",
    "data": {
        "timeline": [
            {
                "type": "meal",
                "date": "2025-01-15",
                "user": "John Doe",
                "details": "Breakfast: 1, Lunch: 1, Dinner: 1",
                "data": {...}
            },
            {
                "type": "deposit",
                "date": "2025-01-15",
                "user": "Jane Doe",
                "details": "Deposit: ৳1000.00",
                "data": {...}
            }
        ],
        "period": {
            "start": "2025-01-01",
            "end": "2025-01-31"
        },
        "total_activities": 250
    }
}
```

### Get Budget Analysis
**Endpoint**: `GET /api/month/budget-analysis/{monthId?}`
**Access**: Protected (requires mess membership)

#### Parameters
- `monthId` (optional): Specific month ID (defaults to current active month)

#### Query Parameters
- `budget_amount`: nullable, numeric - Total budget amount
- `category_budgets[groceries]`: nullable, numeric - Category-wise budget
- `category_budgets[utilities]`: nullable, numeric
- `category_budgets[maintenance]`: nullable, numeric

#### Response
```json
{
    "success": true,
    "message": "Budget analysis completed successfully",
    "data": {
        "month_info": {
            "name": "January 2025",
            "start_date": "2025-01-01",
            "end_date": "2025-01-31"
        },
        "expenses": {
            "total_purchases": 18000.00,
            "total_other_costs": 2000.00,
            "total_expenses": 20000.00
        },
        "income": {
            "total_deposits": 25000.00
        },
        "balance": 5000.00,
        "budget_analysis": {
            "budget_amount": 22000.00,
            "actual_expenses": 20000.00,
            "variance": 2000.00,
            "percentage_used": 90.91,
            "status": "within_budget"
        }
    }
}
```

### Validate Month Data
**Endpoint**: `GET /api/month/validate/{monthId?}`
**Access**: Protected (requires mess membership)

#### Parameters
- `monthId` (optional): Specific month ID (defaults to current active month)

#### Response
```json
{
    "success": true,
    "message": "Month data validation completed",
    "data": {
        "month_id": 1,
        "validation_date": "2025-06-16T10:00:00Z",
        "status": "valid",
        "issues": [],
        "warnings": [
            "Found 2 meals from users not initiated for this month"
        ],
        "summary": {
            "total_issues": 0,
            "total_warnings": 1
        }
    }
}
```

### Get Performance Metrics
**Endpoint**: `GET /api/month/performance/{monthId?}`
**Access**: Protected (requires mess membership)

#### Parameters
- `monthId` (optional): Specific month ID (defaults to current active month)

#### Query Parameters
- `compare_with_previous`: nullable, boolean
- `include_trends`: nullable, boolean

#### Response
```json
{
    "success": true,
    "message": "Performance metrics retrieved successfully",
    "data": {
        "month_info": {
            "id": 1,
            "name": "January 2025",
            "period": "2025-01-01 to 2025-01-31"
        },
        "performance_indicators": {
            "total_users": 5,
            "active_users_percentage": 100.0,
            "avg_meals_per_user": 100.0,
            "avg_deposit_per_user": 5000.00,
            "cost_per_meal": 40.00
        },
        "comparison_with_previous": {
            "previous_month": "December 2024",
            "user_change": 1,
            "expense_change": -500.00,
            "meal_change": 50
        },
        "trends": {
            "expense_trend": [
                {
                    "month": "January 2025",
                    "expenses": 20000.00
                }
            ],
            "user_trend": [
                {
                    "month": "January 2025",
                    "users": 5
                }
            ]
        }
    }
}
```

## Meal Management

### Add Meal
**Endpoint**: `POST /api/meal/add`
**Access**: Protected (requires active month)

#### Request Body
```json
{
    "mess_user_id": 1,
    "date": "2025-01-15",
    "breakfast": 1,
    "lunch": 1,
    "dinner": 1
}
```

#### Validation Rules
- `mess_user_id`: required, numeric, must exist in current mess and be initiated
- `date`: required, date
- `breakfast`: required, numeric, min 0
- `lunch`: required, numeric, min 0
- `dinner`: required, numeric, min 0

#### Response
```json
{
    "success": true,
    "message": "Meal added successfully",
    "data": {
        "meal": {
            "id": 1,
            "mess_user_id": 1,
            "date": "2025-01-15",
            "breakfast": 1,
            "lunch": 1,
            "dinner": 1
        }
    }
}
```

### Update Meal
**Endpoint**: `PUT /api/meal/{meal_id}/update`
**Access**: Protected (requires active month)

#### Request Body
```json
{
    "breakfast": 0,
    "lunch": 1,
    "dinner": 1
}
```

#### Validation Rules
- `breakfast`: sometimes, numeric, min 0
- `lunch`: sometimes, numeric, min 0
- `dinner`: sometimes, numeric, min 0

#### Response
```json
{
    "success": true,
    "message": "Meal updated successfully",
    "data": {
        "meal": {
            "id": 1,
            "breakfast": 0,
            "lunch": 1,
            "dinner": 1
        }
    }
}
```

### Delete Meal
**Endpoint**: `DELETE /api/meal/{meal_id}/delete`
**Access**: Protected (requires active month)

#### Response
```json
{
    "success": true,
    "message": "Meal deleted successfully",
    "data": null
}
```

### List Meals
**Endpoint**: `GET /api/meal/list`
**Access**: Protected (requires active month)

#### Response
```json
{
    "success": true,
    "message": "Meals retrieved successfully",
    "data": [
        {
            "id": 1,
            "mess_user": {
                "id": 1,
                "user": {
                    "name": "John Doe"
                }
            },
            "date": "2025-01-15",
            "breakfast": 1,
            "lunch": 1,
            "dinner": 1
        }
    ]
}
```

### Get User Meal by Date
**Endpoint**: `GET /api/meal/user/{mess_user_id}/by-date`
**Access**: Protected (requires active month)

#### Query Parameters
```
?date=2025-01-15
```

#### Validation Rules
- `date`: required, date

#### Response
```json
{
    "success": true,
    "message": "User meal retrieved successfully",
    "data": {
        "meal": {
            "id": 1,
            "date": "2025-01-15",
            "breakfast": 1,
            "lunch": 1,
            "dinner": 1
        }
    }
}
```

## Deposit Management

### Add Deposit
**Endpoint**: `POST /api/deposit/add`
**Access**: Protected (requires active month)

#### Request Body
```json
{
    "mess_user_id": 1,
    "date": "2025-01-15",
    "amount": 1000.50
}
```

#### Validation Rules
- `mess_user_id`: required, numeric, must exist in current mess and be initiated
- `date`: required, date
- `amount`: required, numeric, min 0

#### Response
```json
{
    "success": true,
    "message": "Deposit added successfully",
    "data": {
        "deposit": {
            "id": 1,
            "mess_user_id": 1,
            "date": "2025-01-15",
            "amount": 1000.50
        }
    }
}
```

### Update Deposit
**Endpoint**: `PUT /api/deposit/{deposit_id}/update`
**Access**: Protected (requires active month)

#### Request Body
```json
{
    "date": "2025-01-16",
    "amount": 1200.00
}
```

#### Validation Rules
- `date`: sometimes, date
- `amount`: sometimes, numeric, min 0

#### Response
```json
{
    "success": true,
    "message": "Deposit updated successfully",
    "data": {
        "deposit": {
            "id": 1,
            "date": "2025-01-16",
            "amount": 1200.00
        }
    }
}
```

### Delete Deposit
**Endpoint**: `DELETE /api/deposit/{deposit_id}/delete`
**Access**: Protected (requires active month)

#### Response
```json
{
    "success": true,
    "message": "Deposit deleted successfully",
    "data": null
}
```

### List Deposits
**Endpoint**: `GET /api/deposit/list`
**Access**: Protected (requires active month)

#### Response
```json
{
    "success": true,
    "message": "Deposits retrieved successfully",
    "data": [
        {
            "id": 1,
            "mess_user": {
                "id": 1,
                "user": {
                    "name": "John Doe"
                }
            },
            "date": "2025-01-15",
            "amount": 1000.50
        }
    ]
}
```

### Get Deposit History
**Endpoint**: `GET /api/deposit/history/{mess_user_id}`
**Access**: Protected (requires active month)

#### Response
```json
{
    "success": true,
    "message": "Deposit history retrieved successfully",
    "data": [
        {
            "id": 1,
            "date": "2025-01-15",
            "amount": 1000.50
        }
    ]
}
```

## Purchase Management

### Add Purchase
**Endpoint**: `POST /api/purchase/add`
**Access**: Protected (requires active month)

#### Request Body
```json
{
    "mess_user_id": 1,
    "date": "2025-01-15",
    "price": 250.75,
    "product": "Vegetables and Rice"
}
```

#### Validation Rules
- `mess_user_id`: required, numeric, must exist in current mess and be initiated
- `date`: required, date
- `price`: required, numeric, min 1
- `product`: required, string, max 255 characters

#### Response
```json
{
    "success": true,
    "message": "Purchase added successfully",
    "data": {
        "purchase": {
            "id": 1,
            "mess_user_id": 1,
            "date": "2025-01-15",
            "price": 250.75,
            "product": "Vegetables and Rice"
        }
    }
}
```

### Update Purchase
**Endpoint**: `PUT /api/purchase/{purchase_id}/update`
**Access**: Protected (requires active month)

#### Request Body
```json
{
    "date": "2025-01-16",
    "price": 300.00,
    "product": "Vegetables, Rice and Fish"
}
```

#### Validation Rules
- `date`: sometimes, date
- `price`: sometimes, numeric, min 1
- `product`: sometimes, string

#### Response
```json
{
    "success": true,
    "message": "Purchase updated successfully",
    "data": {
        "purchase": {
            "id": 1,
            "date": "2025-01-16",
            "price": 300.00,
            "product": "Vegetables, Rice and Fish"
        }
    }
}
```

### Delete Purchase
**Endpoint**: `DELETE /api/purchase/{purchase_id}/delete`
**Access**: Protected (requires active month)

#### Response
```json
{
    "success": true,
    "message": "Purchase deleted successfully",
    "data": null
}
```

### List Purchases
**Endpoint**: `GET /api/purchase/list`
**Access**: Protected (requires active month)

#### Response
```json
{
    "success": true,
    "message": "Purchases retrieved successfully",
    "data": [
        {
            "id": 1,
            "mess_user": {
                "id": 1,
                "user": {
                    "name": "John Doe"
                }
            },
            "date": "2025-01-15",
            "price": 250.75,
            "product": "Vegetables and Rice"
        }
    ]
}
```

## Purchase Request Management

### Create Purchase Request
**Endpoint**: `POST /api/purchase-request/add`
**Access**: Protected (requires active month and mess user status)

#### Request Body
```json
{
    "date": "2025-01-15",
    "price": 250.75,
    "product": "Vegetables and Rice",
    "product_json": "[{\"item\": \"Rice\", \"quantity\": \"5kg\"}, {\"item\": \"Vegetables\", \"quantity\": \"2kg\"}]",
    "purchase_type": "grocery",
    "deposit_request": false,
    "comment": "Weekly grocery shopping"
}
```

#### Validation Rules
- `date`: required, date
- `price`: required, numeric, min 1
- `product`: sometimes, string, max 255 characters
- `product_json`: sometimes, valid JSON, nullable
- `purchase_type`: required, enum (grocery, utility, maintenance, etc.)
- `deposit_request`: sometimes, boolean
- `comment`: sometimes, string, nullable

#### Response
```json
{
    "success": true,
    "message": "Purchase request created successfully",
    "data": {
        "purchase_request": {
            "id": 1,
            "mess_user_id": 1,
            "date": "2025-01-15",
            "price": 250.75,
            "product": "Vegetables and Rice",
            "product_json": [
                {"item": "Rice", "quantity": "5kg"},
                {"item": "Vegetables", "quantity": "2kg"}
            ],
            "purchase_type": "grocery",
            "deposit_request": false,
            "comment": "Weekly grocery shopping",
            "status": 0
        }
    }
}
```

### Update Purchase Request
**Endpoint**: `PUT /api/purchase-request/{request_id}/update`
**Access**: Protected (requires active month and mess user status or proper permissions)

#### Request Body
```json
{
    "date": "2025-01-16",
    "price": 300.00,
    "product": "Vegetables, Rice and Fish",
    "product_json": "[{\"item\": \"Rice\", \"quantity\": \"5kg\"}, {\"item\": \"Fish\", \"quantity\": \"1kg\"}]",
    "purchase_type": "grocery",
    "status": "approved",
    "deposit_request": true,
    "comment": "Updated grocery shopping list"
}
```

#### Validation Rules
- `date`: sometimes, date
- `price`: sometimes, numeric, min 1
- `product`: sometimes, string, max 255 characters
- `product_json`: sometimes, valid JSON, nullable
- `purchase_type`: required, enum
- `status`: required, enum (pending, approved, rejected, completed)
- `deposit_request`: sometimes, boolean
- `comment`: sometimes, string, nullable

#### Response
```json
{
    "success": true,
    "message": "Purchase request updated successfully",
    "data": {
        "purchase_request": {
            "id": 1,
            "date": "2025-01-16",
            "price": 300.00,
            "product": "Vegetables, Rice and Fish",
            "status": "approved"
        }
    }
}
```

### Update Purchase Request Status
**Endpoint**: `PUT /api/purchase-request/{request_id}/update/status`
**Access**: Protected (requires PURCHASE_REQUEST_MANAGEMENT or PURCHASE_REQUEST_UPDATE permission)

#### Request Body
```json
{
    "status": 1,
    "comment": "Approved for purchase",
    "is_deposit": false
}
```

#### Validation Rules
- `status`: required, integer (status code)
- `comment`: sometimes, string, nullable
- `is_deposit`: sometimes, boolean, nullable

#### Response
```json
{
    "success": true,
    "message": "Purchase request status updated successfully",
    "data": {
        "purchase_request": {
            "id": 1,
            "status": 1,
            "comment": "Approved for purchase"
        }
    }
}
```

### Delete Purchase Request
**Endpoint**: `DELETE /api/purchase-request/{request_id}/delete`
**Access**: Protected (requires active month and mess user status)

#### Response
```json
{
    "success": true,
    "message": "Purchase request deleted successfully",
    "data": null
}
```

### List Purchase Requests
**Endpoint**: `GET /api/purchase-request/`
**Access**: Protected (requires active month and mess user status)

#### Query Parameters
```
?status=pending&purchase_type=grocery&deposit_request=false
```

#### Validation Rules
- `status`: sometimes, enum (pending, approved, rejected, completed)
- `purchase_type`: sometimes, enum
- `deposit_request`: sometimes, boolean

#### Response
```json
{
    "success": true,
    "message": "Purchase requests retrieved successfully",
    "data": [
        {
            "id": 1,
            "mess_user": {
                "id": 1,
                "user": {
                    "name": "John Doe"
                }
            },
            "date": "2025-01-15",
            "price": 250.75,
            "product": "Vegetables and Rice",
            "purchase_type": "grocery",
            "status": 0,
            "deposit_request": false
        }
    ]
}
```

### Get Purchase Request Details
**Endpoint**: `GET /api/purchase-request/{request_id}`
**Access**: Protected (requires active month and mess user status)

#### Response
```json
{
    "success": true,
    "message": "Purchase request retrieved successfully",
    "data": {
        "purchase_request": {
            "id": 1,
            "mess_user": {
                "id": 1,
                "user": {
                    "name": "John Doe"
                }
            },
            "date": "2025-01-15",
            "price": 250.75,
            "product": "Vegetables and Rice",
            "product_json": [
                {"item": "Rice", "quantity": "5kg"},
                {"item": "Vegetables", "quantity": "2kg"}
            ],
            "purchase_type": "grocery",
            "status": 0,
            "deposit_request": false,
            "comment": "Weekly grocery shopping"
        }
    }
}
```

## Other Cost Management

### Add Other Cost
**Endpoint**: `POST /api/other-cost/add`
**Access**: Protected (requires active month)

#### Request Body
```json
{
    "mess_user_id": 1,
    "date": "2025-01-15",
    "price": 150.00,
    "product": "Gas bill"
}
```

#### Validation Rules
- `mess_user_id`: required, numeric, must exist in current mess and be initiated
- `date`: required, date
- `price`: required, numeric, min 0
- `product`: required, string, max 255 characters

#### Response
```json
{
    "success": true,
    "message": "Other cost added successfully",
    "data": {
        "other_cost": {
            "id": 1,
            "mess_user_id": 1,
            "date": "2025-01-15",
            "price": 150.00,
            "product": "Gas bill"
        }
    }
}
```

### Update Other Cost
**Endpoint**: `PUT /api/other-cost/{cost_id}/update`
**Access**: Protected (requires active month)

#### Request Body
```json
{
    "date": "2025-01-16",
    "price": 175.00,
    "product": "Gas and electricity bill"
}
```

#### Validation Rules
- `date`: sometimes, date
- `price`: sometimes, numeric, min 0
- `product`: sometimes, string

#### Response
```json
{
    "success": true,
    "message": "Other cost updated successfully",
    "data": {
        "other_cost": {
            "id": 1,
            "date": "2025-01-16",
            "price": 175.00,
            "product": "Gas and electricity bill"
        }
    }
}
```

### Delete Other Cost
**Endpoint**: `DELETE /api/other-cost/{cost_id}/delete`
**Access**: Protected (requires active month)

#### Response
```json
{
    "success": true,
    "message": "Other cost deleted successfully",
    "data": null
}
```

### List Other Costs
**Endpoint**: `GET /api/other-cost/list`
**Access**: Protected (requires active month)

#### Response
```json
{
    "success": true,
    "message": "Other costs retrieved successfully",
    "data": [
        {
            "id": 1,
            "mess_user": {
                "id": 1,
                "user": {
                    "name": "John Doe"
                }
            },
            "date": "2025-01-15",
            "price": 150.00,
            "product": "Gas bill"
        }
    ]
}
```

## Fund Management

### Add Fund
**Endpoint**: `POST /api/fund/add`
**Access**: Protected (requires active month)

#### Request Body
```json
{
    "date": "2025-01-15",
    "amount": 500.00,
    "comment": "Emergency fund from external source"
}
```

#### Validation Rules
- `date`: required, date
- `amount`: required, numeric, min 0
- `comment`: required, string

#### Response
```json
{
    "success": true,
    "message": "Fund added successfully",
    "data": {
        "fund": {
            "id": 1,
            "date": "2025-01-15",
            "amount": 500.00,
            "comment": "Emergency fund from external source"
        }
    }
}
```

### Update Fund
**Endpoint**: `PUT /api/fund/{fund_id}/update`
**Access**: Protected (requires active month)

#### Request Body
```json
{
    "date": "2025-01-16",
    "amount": 600.00,
    "comment": "Updated emergency fund amount"
}
```

#### Validation Rules
- `date`: sometimes, date
- `amount`: sometimes, numeric, min 0
- `comment`: nullable, string

#### Response
```json
{
    "success": true,
    "message": "Fund updated successfully",
    "data": {
        "fund": {
            "id": 1,
            "date": "2025-01-16",
            "amount": 600.00,
            "comment": "Updated emergency fund amount"
        }
    }
}
```

### Delete Fund
**Endpoint**: `DELETE /api/fund/{fund_id}/delete`
**Access**: Protected (requires active month)

#### Response
```json
{
    "success": true,
    "message": "Fund deleted successfully",
    "data": null
}
```

### List Funds
**Endpoint**: `GET /api/fund/list`
**Access**: Protected (requires active month)

#### Response
```json
{
    "success": true,
    "message": "Funds retrieved successfully",
    "data": [
        {
            "id": 1,
            "date": "2025-01-15",
            "amount": 500.00,
            "comment": "Emergency fund from external source"
        }
    ]
}
```

## Summary and Reports

### Get Month Summary
**Endpoint**: `GET /api/summary/months/{type}`
**Access**: Protected (requires active month)

#### Parameters
- `type`: string (minimal|details) - type of summary to retrieve

#### Minimal Summary Response
```json
{
    "success": true,
    "message": "Monthly minimal summary retrieved",
    "data": {
        "month": {
            "id": 1,
            "name": "January 2025"
        },
        "total_deposits": 5000.00,
        "total_purchases": 3000.00,
        "total_other_costs": 500.00,
        "total_funds": 1000.00,
        "balance": 2500.00,
        "total_meals": {
            "breakfast": 150,
            "lunch": 180,
            "dinner": 170
        }
    }
}
```

#### Detailed Summary Response
```json
{
    "success": true,
    "message": "Monthly detailed summary retrieved",
    "data": {
        "month": {
            "id": 1,
            "name": "January 2025"
        },
        "financial_summary": {
            "total_deposits": 5000.00,
            "total_purchases": 3000.00,
            "total_other_costs": 500.00,
            "total_funds": 1000.00,
            "balance": 2500.00
        },
        "meal_summary": {
            "total_meals": {
                "breakfast": 150,
                "lunch": 180,
                "dinner": 170
            },
            "cost_per_meal": {
                "breakfast": 12.50,
                "lunch": 15.00,
                "dinner": 14.25
            }
        },
        "user_summaries": [
            {
                "mess_user_id": 1,
                "user_name": "John Doe",
                "total_deposits": 1000.00,
                "total_meals": 45,
                "meal_cost": 675.00,
                "balance": 325.00
            }
        ]
    }
}
```

### Get User Summary
**Endpoint**: `GET /api/summary/months/user/{type}`
**Access**: Protected (requires active month)

#### Parameters
- `type`: string (minimal|details) - type of user summary to retrieve

#### Query Parameters
```
?mess_user_id=1
```

#### Validation Rules
- `mess_user_id`: nullable, numeric, must exist in mess_users table

#### User Minimal Summary Response
```json
{
    "success": true,
    "message": "User minimal summary retrieved",
    "data": {
        "user": {
            "mess_user_id": 1,
            "name": "John Doe"
        },
        "total_deposits": 1000.00,
        "total_meals": 45,
        "meal_cost": 675.00,
        "balance": 325.00
    }
}
```

#### User Detailed Summary Response
```json
{
    "success": true,
    "message": "User detailed summary retrieved",
    "data": {
        "user": {
            "mess_user_id": 1,
            "name": "John Doe"
        },
        "deposits": [
            {
                "id": 1,
                "date": "2025-01-15",
                "amount": 1000.00
            }
        ],
        "meals": [
            {
                "id": 1,
                "date": "2025-01-15",
                "breakfast": 1,
                "lunch": 1,
                "dinner": 1
            }
        ],
        "financial_summary": {
            "total_deposits": 1000.00,
            "total_meal_cost": 675.00,
            "balance": 325.00
        }
    }
}
```

## Enumerations

### Gender
- `Male`: Male gender
- `Female`: Female gender  
- `Other`: Other/Undefined gender

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

## Rate Limiting
API endpoints may be subject to rate limiting. Standard Laravel rate limiting applies.

## Versioning
The API supports versioning through URL prefixes:
- Current version: `/api/v1`
- Future versions will be available at `/api/v2`, etc.

---

**Note**: This documentation covers the current state of the API. For the most up-to-date information, please refer to the source code or contact the development team.

## Related Documentation

- **[Model Relationships & Dependencies](./model-relationships.md)**: Complete database schema and model relationship documentation
- **[Postman Collection](./postman-collection.json)**: Ready-to-import API testing collection
- **[OpenAPI Specification](./openapi.yaml)**: Machine-readable API specification
