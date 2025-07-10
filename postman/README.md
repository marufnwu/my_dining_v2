# Postman Integration for My Dining v2 API

## Overview
This directory contains Postman collections and environments for comprehensive testing of the My Dining v2 Mess Management API. The collections cover all major functionality including user management, mess operations, meal requests, financial management, and administrative features.

## Files Structure
```
postman/
├── My_Dining_v2_Complete_API.postman_collection.json     # Complete merged API collection (RECOMMENDED)
├── My_Dining_v2_Meal_Request_API.postman_collection.json # Legacy meal request specific collection
├── development.postman_environment.json                   # Development environment
├── production.postman_environment.json                    # Production environment
└── README.md                                             # This file
```

## 🚀 Quick Start Guide

### Use the Complete Collection
**We recommend using `My_Dining_v2_Complete_API.postman_collection.json`** - it's a merged collection that includes all API endpoints with comprehensive documentation, examples, and automated tests.

## 🚀 Quick Start Guide

### Use the Complete Collection
**We recommend using `My_Dining_v2_Complete_API.postman_collection.json`** - it's a merged collection that includes all API endpoints with comprehensive documentation, examples, and automated tests.

### 1. Import to Postman
1. Open Postman application
2. Click "Import" button
3. Select **`My_Dining_v2_Complete_API.postman_collection.json`** and both environment files
4. Collections and environments will be imported

### 2. Set Environment
1. Select appropriate environment (Development/Production)
2. Update environment variables as needed:
   - `base_url`: Your API base URL
   - `user_email` / `user_password`: Test user credentials
   - `admin_email` / `admin_password`: Admin user credentials

### 3. Authenticate
1. Run the "Login User" request in Authentication folder
2. Token will be automatically saved to environment as `auth_token`
3. For admin operations, run "Login Admin" to set `admin_token`
4. All subsequent requests will use the appropriate token automatically

## 📋 Complete API Coverage

The merged collection includes comprehensive coverage of all API endpoints:

### 🔐 Authentication & User Management
- User registration, login, logout
- Password reset functionality
- Profile management
- Account verification

### 🏠 Mess Management
- Create and manage mess
- Join/leave mess operations
- Mess settings and configuration
- User roles and permissions

### 🍽️ Meal Operations
- Meal request creation and management
- Traditional meal addition (admin)
- Meal history and tracking
- Meal cost calculations

### 💰 Financial Management
- Expense tracking
- Purchase requests
- Payment management
- Financial reports

### 👥 User & Admin Features
- User management (admin)
- Month/period management
- Settings configuration
- Notifications and alerts

## GitHub Integration Steps

### Method 1: Collection Backup to GitHub

1. **Generate GitHub Personal Access Token**:
   ```
   GitHub → Settings → Developer settings → Personal access tokens → Generate new token
   Scopes needed: repo, read:org
   ```

2. **Connect Collection to GitHub**:
   - In Postman, go to your collection
   - Click "..." → Integrations
   - Select "GitHub"
   - Enter repository: `your-username/my_dining_v2`
   - Provide access token
   - Select branch: `main`
   - Set directory: `postman/`

3. **Configure Sync**:
   - Choose what to sync (collection, environment)
   - Set sync frequency (manual/automatic)
   - Enable notifications

### Method 2: Manual GitHub Sync

1. **Commit Postman Files**:
   ```bash
   git add postman/
   git commit -m "Add Postman collection for Meal Request API"
   git push origin main
   ```

2. **Keep Updated**:
   - Export updated collections from Postman
   - Replace files in `postman/` directory
   - Commit and push changes

### Method 3: API Builder Integration

1. **Create API in Postman**:
   - Go to APIs tab in Postman
   - Create new API
   - Connect to GitHub repository

2. **Link Collection**:
   - Connect your collection to the API
   - Enable schema validation
   - Set up automatic sync

## 🧪 API Testing Workflow

### For Regular Users:
1. **Authentication**:
   - Run "Login User" request
   - Token automatically saved as `auth_token`

2. **Mess Operations**:
   - Join or create a mess
   - View mess details and members
   - Manage mess settings (if admin)

3. **Meal Request Lifecycle**:
   - Create meal request
   - View your requests
   - Update pending requests
   - Cancel requests if needed

4. **Financial Operations**:
   - Add expenses
   - Create purchase requests
   - View financial reports

### For Admins:
1. **Authentication**:
   - Run "Login Admin" request
   - Admin token saved as `admin_token`

2. **User Management**:
   - View all users
   - Manage user accounts
   - Update user roles and permissions

3. **Mess Administration**:
   - Manage mess settings
   - Handle member requests
   - Configure mess parameters

4. **Request Management**:
   - View pending meal requests
   - Approve/reject with comments
   - Monitor all requests and meals

5. **Financial Administration**:
   - Approve purchase requests
   - Manage expenses
   - Generate financial reports

6. **System Administration**:
   - Manage months/periods
   - Configure system settings
   - Handle notifications

## 🔧 Environment Variables

### Development Environment
```json
{
  "base_url": "http://localhost:8000",
  "auth_token": "", // Auto-populated after login
  "admin_token": "", // Auto-populated after admin login
  "user_email": "test@example.com",
  "user_password": "password123",
  "admin_email": "admin@example.com",
  "admin_password": "admin123",
  "meal_request_id": "1" // Used for testing specific requests
}
```

### Production Environment
```json
{
  "base_url": "https://your-production-domain.com",
  "auth_token": "", // Auto-populated after login
  "admin_token": "", // Auto-populated after admin login
  "user_email": "your-prod-user@domain.com",
  "user_password": "your-secure-password",
  "admin_email": "your-admin@domain.com",
  "admin_password": "your-secure-admin-password",
  "meal_request_id": "1"
}
```

## ✨ Collection Features

### 🤖 Automated Testing
- Response status validation
- JSON schema validation
- Automatic token management
- Environment variable updates
- Error handling verification

### 📝 Request Examples
- Complete CRUD operations for all entities
- Permission-based access patterns
- Error handling scenarios
- Success and failure cases

### 📖 Comprehensive Documentation
- Detailed request descriptions
- Parameter explanations with examples
- Response structure documentation
- Authentication requirements
- Permission level specifications

## 🔗 API Endpoints Covered

### 🔐 Authentication
- `POST /api/register` - User registration
- `POST /api/login` - User login
- `POST /api/logout` - User logout
- `POST /api/password/reset` - Password reset
- `GET /api/profile` - Get user profile
- `PUT /api/profile` - Update user profile

### 🏠 Mess Management
- `POST /api/mess/create` - Create new mess
- `GET /api/mess/` - Get mess details
- `PUT /api/mess/update` - Update mess settings
- `POST /api/mess/join` - Join mess
- `POST /api/mess/leave` - Leave mess
- `GET /api/mess/members` - Get mess members
- `POST /api/mess/member/role` - Update member role

### 🍽️ Meal Operations
#### For Users:
- `POST /api/meal-request/add` - Create meal request
- `GET /api/meal-request/my-requests` - View own requests
- `PUT /api/meal-request/{id}/update` - Update request
- `POST /api/meal-request/{id}/cancel` - Cancel request
- `DELETE /api/meal-request/{id}/delete` - Delete request

#### For Admins:
- `GET /api/meal-request/pending` - Pending requests
- `GET /api/meal-request/` - All requests with filters
- `POST /api/meal-request/{id}/approve` - Approve request
- `POST /api/meal-request/{id}/reject` - Reject request
- `GET /api/meal-request/{id}` - View specific request
- `POST /api/meal/add` - Direct meal addition
- `GET /api/meal/list` - View meals

### 💰 Financial Management
- `POST /api/expense/add` - Add expense
- `GET /api/expense/list` - List expenses
- `PUT /api/expense/{id}/update` - Update expense
- `DELETE /api/expense/{id}/delete` - Delete expense
- `POST /api/purchase-request/add` - Create purchase request
- `GET /api/purchase-request/list` - List purchase requests
- `POST /api/purchase-request/{id}/approve` - Approve purchase
- `POST /api/purchase-request/{id}/reject` - Reject purchase

### 👥 User & Admin Management
- `GET /api/admin/users` - List all users
- `POST /api/admin/user/status` - Update user status
- `GET /api/admin/month/list` - List months
- `POST /api/admin/month/create` - Create month
- `PUT /api/admin/month/{id}/update` - Update month
- `GET /api/admin/settings` - Get settings
- `POST /api/admin/settings/update` - Update settings

## 🏆 Best Practices

### 🔒 Security & Authentication
- Use separate tokens for user and admin operations
- Tokens are automatically managed by collection scripts
- Never commit production credentials to version control
- Use environment variables for sensitive data
- Regularly rotate API tokens

### 📊 Testing Strategy
- Run authentication requests first
- Test happy path scenarios before edge cases
- Verify both success and error responses
- Use automated tests for CI/CD integration
- Test with different user roles and permissions

### 📝 Version Control
- Keep collections in source control
- Use meaningful commit messages
- Tag releases with collection versions
- Document API changes in commit messages
- Maintain separate branches for major updates

### 🔄 Environment Management
- Use separate environments for different stages
- Keep development and production data separate
- Update environment variables when API changes
- Test in development before production
- Backup environment configurations

## 🚨 Troubleshooting

### Common Issues
1. **401 Unauthorized**: 
   - Check if token is valid and not expired
   - Verify token is set in environment
   - Re-run authentication request

2. **403 Forbidden**: 
   - Verify user has required permissions
   - Check if user is member of mess (for mess operations)
   - Ensure admin role for admin operations

3. **422 Validation Error**: 
   - Check request body format and required fields
   - Verify data types match API expectations
   - Review parameter validation rules

4. **404 Not Found**:
   - Verify endpoint URL is correct
   - Check if resource exists (e.g., meal request ID)
   - Ensure you're using correct API version

### Debug Steps
1. Check environment variables are set correctly
2. Verify authentication token is valid
3. Review request headers and body format
4. Check API endpoint URL and HTTP method
5. Examine response for detailed error messages
6. Test with Postman Console for detailed logs

## 🤝 Contributing

When adding new endpoints to the collection:

1. **Add to Appropriate Folder**: 
   - Group related endpoints together
   - Follow existing folder structure
   - Use clear, descriptive names

2. **Include Proper Authentication**:
   - Set correct token variable
   - Document permission requirements
   - Test with appropriate user roles

3. **Add Comprehensive Tests**:
   - Status code validation
   - Response structure validation
   - Environment variable updates
   - Error handling tests

4. **Document Everything**:
   - Request descriptions
   - Parameter explanations
   - Response examples
   - Business logic notes

5. **Update Documentation**:
   - Add endpoint to this README
   - Update API documentation
   - Include example use cases

## 📚 Collection Migration Guide

### From Legacy Collections
If you're migrating from individual collections:

1. **Export Your Data**: 
   - Export existing collections and environments
   - Backup any custom variables or settings

2. **Import Complete Collection**:
   - Import `My_Dining_v2_Complete_API.postman_collection.json`
   - Update environment variables as needed

3. **Update Workflows**:
   - Collections are now organized by functional area
   - Authentication is centralized
   - Variables are standardized across requests

4. **Test Migration**:
   - Run key workflows to ensure everything works
   - Update any custom scripts or tests
   - Verify all endpoints are accessible

## Additional Resources

- [Postman Documentation](https://learning.postman.com/)
- [GitHub Integration Guide](https://learning.postman.com/docs/integrations/available-integrations/github/overview/)
- [API Testing Best Practices](https://www.postman.com/api-testing/)
- [Laravel API Documentation](https://laravel.com/docs/api-authentication)
