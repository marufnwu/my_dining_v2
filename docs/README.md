# API Documentation Files

This directory contains comprehensive API documentation for the My Dining application.

## Available Documentation

### 1. [API Documentation](./api-documentation.md)
- **Format**: Markdown
- **Description**: Complete human-readable API documentation with detailed endpoint descriptions, request/response examples, and validation rules
- **Best for**: Developers reading documentation, integration planning

### 2. [Postman Collection](./postman-collection.json)
- **Format**: JSON (Postman Collection v2.1.0)
- **Description**: Ready-to-import Postman collection with all API endpoints configured
- **Best for**: API testing, development workflow, quick endpoint testing

#### How to use Postman Collection:
1. Open Postman
2. Click "Import" → "Upload Files"
3. Select `postman-collection.json`
4. Set the collection variables:
   - `base_url`: Your API base URL (default: `http://localhost:8000/api`)
   - `auth_token`: Will be auto-set after successful login

### 3. [OpenAPI Specification](./openapi.yaml)
- **Format**: YAML (OpenAPI 3.0.3)
- **Description**: Machine-readable API specification following OpenAPI standards
- **Best for**: Code generation, API tooling, integration with Swagger UI

#### How to use OpenAPI Specification:
1. **Swagger UI**: Upload to [Swagger Editor](https://editor.swagger.io/) or host with Swagger UI
2. **Code Generation**: Use with OpenAPI Generator to create client SDKs
3. **API Tools**: Import into tools like Insomnia, Paw, or other OpenAPI-compatible tools

## API Overview

The My Dining API is a RESTful service for mess (dining group) management with the following core features:

### 🔐 Authentication
- JWT token-based authentication via Laravel Sanctum
- User registration and login
- Protected routes requiring authentication

### 🏠 Mess Management
- Create and manage dining groups (messes)
- Member invitation and management
- Role-based permissions

### 📅 Monthly Cycles
- Automatic and manual month creation
- Month-based data organization
- Status management

### 🍽️ Meal Tracking
- Daily meal logging (breakfast, lunch, dinner)
- User-specific meal records
- Date-based meal queries

### 💰 Financial Management
- **Deposits**: Member contributions
- **Purchases**: Grocery and supply purchases
- **Other Costs**: Utilities and additional expenses
- **Funds**: External funding sources

### 📋 Purchase Requests
- Request-approval workflow
- Detailed purchase planning
- JSON-based product specifications

### 📊 Reports & Analytics
- Monthly financial summaries
- User-specific reports
- Meal cost calculations
- Balance tracking

## Base URL Structure

```
/api/                           # Main API prefix
├── /auth/                      # Authentication endpoints
├── /country/                   # Country data
├── /mess/                      # Mess management
├── /member/                    # Member management
├── /month/                     # Month management
├── /meal/                      # Meal tracking
├── /deposit/                   # Deposit management
├── /purchase/                  # Purchase management
├── /purchase-request/          # Purchase requests
├── /other-cost/               # Other costs
├── /fund/                     # Fund management
└── /summary/                  # Reports and summaries
```

## Response Format

All API responses follow a consistent structure:

```json
{
    "success": boolean,
    "message": string,
    "data": object|array|null,
    "errors": object|null
}
```

## Authentication

Most endpoints require authentication via Bearer token:

```http
Authorization: Bearer {your-token-here}
Content-Type: application/json
Accept: application/json
```

## Environment Variables

Ensure these environment variables are configured:

```env
APP_URL=http://localhost:8000
SANCTUM_STATEFUL_DOMAINS=localhost:3000,127.0.0.1:3000
```

## Error Handling

The API returns appropriate HTTP status codes:

- `200` - Success
- `201` - Created
- `400` - Bad Request
- `401` - Unauthorized
- `403` - Forbidden
- `404` - Not Found
- `422` - Validation Error
- `500` - Server Error

## Rate Limiting

API endpoints are subject to Laravel's default rate limiting (60 requests per minute per IP).

## Support

For API questions or issues:
- Review the detailed documentation in `api-documentation.md`
- Check the example requests in the Postman collection
- Validate requests against the OpenAPI specification

---

**Last Updated**: June 16, 2025  
**API Version**: 1.0.0
