# Mess Management API Endpoints

## Overview
The Mess Management system has been extended with new endpoints to support comprehensive mess lifecycle management, including viewing mess information, leaving/closing messes, joining other messes, and managing join requests.

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

#### Leave Current Mess
```
POST /api/mess-management/leave
```
Allows a user to leave their current mess. Admin users cannot leave if they are the only admin.

#### Close Mess (Admin Only)
```
POST /api/mess-management/close
```
Allows mess administrators to permanently close a mess. Requires `MESS_CLOSE` permission.

#### Get User's Join Requests
```
GET /api/mess-management/join-requests
```
Returns all join requests made by the current user.

#### Cancel Join Request
```
DELETE /api/mess-management/join-requests/{request}
```
Allows users to cancel their pending join requests.

#### Get Incoming Join Requests (Admin)
```
GET /api/mess-management/incoming-requests
```
Returns pending join requests for the user's mess. Requires `JOIN_REQUEST_MANAGEMENT` permission.

#### Accept Join Request (Admin)
```
POST /api/mess-management/incoming-requests/{request}/accept
```
Accepts a pending join request. Requires `JOIN_REQUEST_MANAGEMENT` permission.

#### Reject Join Request (Admin)
```
POST /api/mess-management/incoming-requests/{request}/reject
```
Rejects a pending join request. Requires `JOIN_REQUEST_MANAGEMENT` permission.

### For All Authenticated Users
*These endpoints are available to all authenticated users*

#### Get Available Messes
```
GET /api/mess-management/available
```
Returns a list of messes that the user can potentially join.

#### Send Join Request
```
POST /api/mess-management/join-request/{mess}
```
Sends a join request to the specified mess. Users can only have one pending request at a time.

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
All endpoints return JSON responses with appropriate HTTP status codes:
- 200: Success
- 400: Bad Request (validation errors, business rule violations)
- 403: Forbidden (insufficient permissions)
- 404: Not Found
- 422: Unprocessable Entity (validation errors)

## Error Handling
The system includes comprehensive error handling for:
- Permission violations
- Business rule violations (e.g., trying to leave as the only admin)
- Invalid requests (e.g., duplicate join requests)
- Resource not found errors
