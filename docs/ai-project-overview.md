# AI Project Overview - My Dining v2 (Mess Management System)

> **Last Updated**: 2024-12-28  
> **Version**: 2.0  
> **Purpose**: Ultimate reference guide for AI assistants (Copilot/Conpilot) to understand, maintain, and extend this Laravel mess management system.

## 🎯 Project Purpose

**My Dining v2** is a comprehensive mess (dining hall) management system built with Laravel 11. It manages communal dining operations including user management, meal tracking, financial transactions, monthly budgeting, purchasing, and detailed analytics.

### Core Business Domain
- **Mess Management**: Multi-tenant system where users can join different messes (dining halls)
- **Meal Tracking**: Daily meal consumption tracking for breakfast, lunch, dinner
- **Financial Management**: Deposits, purchases, monthly settlements, fund management
- **User Roles**: Admin, Manager, Members with granular permissions
- **Monthly Cycles**: Complete month-based financial and meal reporting cycles
- **Analytics**: Comprehensive reporting and analytics for mess operations

## 🏗️ Architecture Overview

### Tech Stack
- **Backend**: Laravel 11 (PHP 8.2+)
- **Database**: SQLite (development), supports MySQL/PostgreSQL
- **API**: RESTful APIs with Sanctum authentication
- **Frontend**: Planned (APIs ready)
- **Documentation**: OpenAPI 3.0, Postman collections

### Key Architectural Patterns
1. **Service Layer Pattern**: Business logic in dedicated service classes
2. **Repository Pattern**: Data access abstraction (implicit through Eloquent)
3. **DTO Pattern**: Data Transfer Objects for complex operations
4. **Observer Pattern**: Model observers for automated actions
5. **Policy-based Authorization**: Laravel policies for permissions
6. **Exception Handling**: Custom exceptions for business logic errors

## 📊 Core Domain Models & Relationships

### Primary Models

#### 1. **User** (`app/Models/User.php`)
- **Purpose**: System users who can join messes
- **Key Fields**: name, email, phone, gender, account_status
- **Relationships**:
  - `messUsers()` → Many MessUser records (can join multiple messes)
  - `deposits()` → Many Deposit records
  - `meals()` → Many Meal records
  - `purchases()` → Many Purchase records

#### 2. **Mess** (`app/Models/Mess.php`)
- **Purpose**: Dining hall/mess entity
- **Key Fields**: name, description, status, settings
- **Relationships**:
  - `messUsers()` → Many MessUser records (members)
  - `months()` → Many Month records
  - `purchases()` → Many Purchase records
  - `banners()` → Many Banner records

#### 3. **MessUser** (`app/Models/MessUser.php`)
- **Purpose**: Pivot model for User-Mess relationship with roles
- **Key Fields**: user_id, mess_id, role, status, permissions
- **Relationships**:
  - `user()` → User
  - `mess()` → Mess
  - `meals()` → Many Meal records
  - `deposits()` → Many Deposit records

#### 4. **Month** (`app/Models/Month.php`) ⭐ **Recently Extended**
- **Purpose**: Monthly cycle for mess operations and accounting
- **Key Fields**: mess_id, name, type, start_date, end_date, meal_rate, status
- **Relationships**:
  - `mess()` → Mess
  - `meals()` → Many Meal records
  - `deposits()` → Many Deposit records
  - `purchases()` → Many Purchase records
- **Recent Extensions**: Analytics, reporting, budget analysis, performance metrics

#### 5. **Meal** (`app/Models/Meal.php`)
- **Purpose**: Daily meal consumption tracking
- **Key Fields**: mess_user_id, month_id, date, breakfast, lunch, dinner
- **Relationships**:
  - `messUser()` → MessUser
  - `month()` → Month

#### 6. **Purchase** (`app/Models/Purchase.php`)
- **Purpose**: Expense tracking for mess supplies
- **Key Fields**: mess_id, month_id, amount, description, type, status
- **Relationships**:
  - `mess()` → Mess
  - `month()` → Month
  - `user()` → User (purchaser)

#### 7. **Deposit** (`app/Models/Deposit.php`)
- **Purpose**: Member financial deposits
- **Key Fields**: mess_user_id, month_id, amount, description, status
- **Relationships**:
  - `messUser()` → MessUser
  - `month()` → Month

### Model Relationship Summary
```
User ←→ MessUser ←→ Mess
         ↓          ↓
       Meals     Months
         ↓          ↓
     Deposits  Purchases
```

## 🔧 Service Layer Architecture

### Core Services

#### 1. **MonthService** (`app/Services/MonthService.php`) ⭐ **Recently Extended**
- **Purpose**: All month-related business logic and analytics
- **Key Methods**:
  - `create()`, `update()`, `delete()` - CRUD operations
  - `closeMonth()` - Month closing with financial calculations
  - `duplicate()` - Copy month structure
  - `getStatistics()` - Comprehensive month analytics
  - `exportData()` - Data export functionality
  - `getBudgetAnalysis()` - Budget vs actual analysis
  - `getPerformanceMetrics()` - Performance analytics
  - `compare()` - Month comparison analytics

#### 2. **MessService** (`app/Services/MessService.php`)
- **Purpose**: Mess management and user operations
- **Key Methods**: Member management, mess settings, permissions

#### 3. **MealService** (`app/Services/MealService.php`)
- **Purpose**: Meal tracking and calculations
- **Key Methods**: Meal recording, rate calculations, consumption analytics

#### 4. **PurchaseService** (`app/Services/PurchaseService.php`)
- **Purpose**: Purchase management and expense tracking
- **Key Methods**: Purchase recording, expense analytics, approval workflows

### Service Pattern
```php
// Typical service method structure
public function methodName(DTO $dto): array
{
    // 1. Validation
    $this->validateInput($dto);
    
    // 2. Business Logic
    $result = $this->performBusinessLogic($dto);
    
    // 3. Database Operations
    $this->persistData($result);
    
    // 4. Return Response
    return $this->formatResponse($result);
}
```

## 🛡️ Security & Permissions

### Authentication
- **Laravel Sanctum**: API token-based authentication
- **Email Verification**: Required for account activation

### Authorization System
- **Mess-level Permissions**: Users have different roles per mess
- **Permission Constants**: Defined in `app/Constants/MessPermission.php`
- **Role Constants**: Defined in `app/Constants/MessUserRole.php`

### Key Permissions
```php
// MessPermission constants
MEAL_MANAGEMENT = 'meal_management'
REPORT_MANAGEMENT = 'report_management'
USER_MANAGEMENT = 'user_management'
PURCHASE_MANAGEMENT = 'purchase_management'
DEPOSIT_MANAGEMENT = 'deposit_management'
NOTICE_MANAGEMENT = 'notice_management'
```

### Roles
```php
// MessUserRole constants
ADMIN = 'admin'        // Full access
MANAGER = 'manager'    // Most permissions
MEMBER = 'member'      // Basic access
```

## 🔌 API Structure

### API Versioning
- **Base URL**: `/api/v1/`
- **Authentication**: Bearer token (Sanctum)
- **Response Format**: JSON with consistent structure

### Controller Pattern
```php
// Standard controller structure
class ExampleController extends Controller
{
    public function __construct(private ExampleService $service) {}
    
    public function index(Request $request): JsonResponse
    {
        // 1. Validate request
        // 2. Call service method
        // 3. Return formatted response
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => 'Success message'
        ]);
    }
}
```

### Recently Extended: MonthController
**Location**: `app/Http/Controllers/Api/MonthController.php`

**New Endpoints**:
- `GET /api/v1/months/{id}` - Show month details
- `GET /api/v1/months/{id}/summary` - Month summary
- `POST /api/v1/months/{id}/close` - Close month
- `POST /api/v1/months/{id}/duplicate` - Duplicate month
- `GET /api/v1/months/compare` - Compare months
- `GET /api/v1/months/{id}/statistics` - Month statistics
- `GET /api/v1/months/{id}/export` - Export month data
- `GET /api/v1/months/{id}/timeline` - Month timeline
- `GET /api/v1/months/{id}/budget-analysis` - Budget analysis
- `POST /api/v1/months/{id}/validate` - Validate month
- `GET /api/v1/months/{id}/performance` - Performance metrics

## 📁 Project Structure Guide

### Key Directories
```
app/
├── Constants/          # Business constants and enums
├── DTOs/              # Data Transfer Objects
├── Enums/             # PHP 8+ enums
├── Exceptions/        # Custom exceptions
├── Http/
│   ├── Controllers/   # API controllers
│   ├── Middleware/    # Custom middleware
│   └── Requests/      # Form request validation
├── Models/            # Eloquent models
├── Services/          # Business logic layer
├── Policies/          # Authorization policies
└── Observers/         # Model observers

docs/                  # Documentation
├── api-documentation.md      # API docs
├── openapi.yaml             # OpenAPI spec
├── postman-collection.json  # Postman collection
├── model-relationships.md   # Model relationships
├── database-schema.md       # Database structure
└── ai-project-overview.md   # This file

config/
├── mess.php           # Mess-specific configurations
└── features.php       # Feature flags
```

## 🗄️ Database Schema Patterns

### Migration Naming Convention
```
YYYY_MM_DD_HHMMSS_action_table_name.php
```

### Key Tables
- `users` - System users
- `messes` - Mess entities
- `mess_users` - User-mess relationships with roles
- `months` - Monthly cycles
- `meals` - Daily meal tracking
- `purchases` - Expense records
- `deposits` - Financial deposits
- `funds` - Mess fund management

### Foreign Key Pattern
- All foreign keys use `_id` suffix
- Soft deletes implemented where needed
- Timestamps on all core tables

## 🔄 Business Logic Patterns

### Month Lifecycle
1. **Creation**: New month with initial settings
2. **Active**: Daily operations (meals, purchases, deposits)
3. **Closing**: Financial calculations and validations
4. **Closed**: Read-only, archived state

### Meal Rate Calculation
```php
// Basic meal rate calculation
$totalExpenses = $month->purchases()->sum('amount');
$totalMeals = $month->meals()->sum(['breakfast', 'lunch', 'dinner']);
$mealRate = $totalExpenses / $totalMeals;
```

### Financial Settlement
- Monthly deposits vs expenses
- Per-member meal consumption
- Automatic balance calculations
- Deficit/surplus handling

## 🧪 Testing Strategy

### Test Structure
```
tests/
├── Feature/           # Integration tests
└── Unit/             # Unit tests
```

### Testing Patterns
- **Feature Tests**: End-to-end API testing
- **Unit Tests**: Service and model testing
- **Database**: SQLite in-memory for testing
- **Factories**: Model factories for test data

## 📖 Documentation Standards

### API Documentation
- **File**: `docs/api-documentation.md`
- **Format**: Detailed endpoint documentation with examples
- **Updates**: Must be updated when adding new endpoints

### OpenAPI Specification
- **File**: `docs/openapi.yaml`
- **Version**: 3.0
- **Updates**: Must include all new endpoints and schemas

### Postman Collection
- **File**: `docs/postman-collection.json`
- **Structure**: Organized by feature modules
- **Updates**: Include example requests for all endpoints

## 🚀 Extension Guidelines

### Adding New Features
1. **Model Changes**: Update models and relationships
2. **Migration**: Create database migrations
3. **Service Layer**: Implement business logic in services
4. **Controller**: Add API endpoints
5. **Tests**: Write feature and unit tests
6. **Documentation**: Update all docs (API, OpenAPI, Postman)
7. **Update This File**: Add new features to this overview

### Code Style Standards
- **PSR-12**: Follow PSR-12 coding standards
- **Laravel Conventions**: Use Laravel naming conventions
- **Type Hints**: Use PHP 8+ type hints
- **Documentation**: PHPDoc for all public methods

### Recent Extensions Example
The MonthController extension demonstrates the proper pattern:
- Service methods for business logic
- Controller methods for API endpoints
- Comprehensive documentation updates
- Consistent error handling and validation

## 🔍 Debugging & Monitoring

### Error Handling
- **Custom Exceptions**: Located in `app/Exceptions/`
- **API Responses**: Consistent JSON error responses
- **Logging**: Laravel logging for debugging

### Key Custom Exceptions
- `CustomException` - Base custom exception
- `PermissionDeniedException` - Authorization errors
- `NoMessException` - User not in mess errors
- `EmailNotVerifiedException` - Email verification errors

## 📋 Maintenance Checklist

### When Adding New Features
- [ ] Update models and relationships
- [ ] Create/update migrations
- [ ] Implement service layer logic
- [ ] Add controller endpoints
- [ ] Update routes
- [ ] Write tests
- [ ] Update API documentation
- [ ] Update OpenAPI specification
- [ ] Update Postman collection
- [ ] Update this AI overview document

### When Modifying Existing Features
- [ ] Check impact on related models
- [ ] Update service methods
- [ ] Modify controller endpoints if needed
- [ ] Update tests
- [ ] Update documentation
- [ ] Test backwards compatibility

## 🎯 Future Development Notes

### Current User Profile API Status
**Current Capabilities**: ✅ **Core Profile Management Implemented** (June 2025)

#### **✅ Implemented Features:**

##### **1. Core Profile Management** ✅ **COMPLETED**
```php
GET    /api/profile              // Get current user profile ✅
PUT    /api/profile              // Update profile information ✅
POST   /api/profile/avatar       // Upload profile photo ✅
DELETE /api/profile/avatar       // Remove profile photo ✅
```

**Features**:
- ✅ **Update Profile Information**: Users can modify name, city, gender
- ✅ **Avatar/Photo Upload**: File upload with validation (JPEG, PNG, JPG, GIF, max 2MB)
- ✅ **Profile Validation**: Data integrity checks and validation rules
- ✅ **Data Consistency**: Profile updates maintain mess relationships
- ✅ **Profile Completion**: Percentage calculation of profile completeness

**Implementation Status**:
- ✅ UserController extended with profile methods
- ✅ UserService enhanced with profile management logic
- ✅ Request validation classes created (UpdateProfileRequest, UploadAvatarRequest)
- ✅ API routes configured under `/api/profile`
- ✅ API documentation updated with examples
- ✅ File storage configured for avatar uploads

### Planned User Profile Extensions

#### **2. Security Features**
**Priority**: High - **NEXT TO IMPLEMENT**
```php
GET    /api/v1/profile              // Get current user profile
PUT    /api/v1/profile              // Update profile information
POST   /api/v1/profile/avatar       // Upload profile photo
DELETE /api/v1/profile/avatar       // Remove profile photo
```

**Features**:
- **Update Profile Information**: Allow users to modify name, city, gender, and other profile fields
- **Avatar/Photo Upload**: File upload with validation, storage, and URL management
- **Profile Validation**: Data integrity checks and validation rules
- **Data Consistency**: Ensure profile updates don't break mess relationships

#### **2. Security Features**
**Priority**: High
```php
PUT    /api/v1/profile/password     // Change password with verification
PUT    /api/v1/profile/email        // Change email with verification process
PUT    /api/v1/profile/phone        // Update phone number with OTP
GET    /api/v1/profile/sessions     // View active authentication sessions
DELETE /api/v1/profile/sessions/{id} // Revoke specific session
DELETE /api/v1/profile/sessions     // Revoke all sessions except current
```

**Features**:
- **Password Change with Verification**: Require current password before allowing change
- **Email Change with Verification**: Email verification process for new email addresses
- **Phone Number Updates with OTP**: SMS/call verification for phone number changes
- **Session Management**: View and revoke active authentication sessions for security

**Implementation Requirements**:
- New migration for `user_sessions` table to track active sessions
- OTP service integration for phone verification
- Email verification service for email changes
- Enhanced UserService methods for security operations
- New ProfileController for profile-specific operations

### Other Planned Features
- Frontend implementation (APIs ready)
- Real-time notifications
- Advanced reporting dashboard
- Mobile app support
- Integration with payment gateways

### Scalability Considerations
- Multi-tenancy support (already partially implemented)
- Caching layer for analytics
- Background job processing for heavy operations
- API rate limiting

## 📞 Key Contact Points for AI Development

### Critical Files to Monitor
1. **Models**: Any changes affect relationships and business logic
2. **Services**: Core business logic implementations
3. **Controllers**: API endpoint definitions
4. **Documentation**: Must stay synchronized with code

### Common Patterns to Follow
1. **Service-First Development**: Business logic in services, not controllers
2. **Consistent API Responses**: Use established response patterns
3. **Permission Checking**: Always validate user permissions
4. **Error Handling**: Use custom exceptions for business logic errors

---

**⚠️ IMPORTANT**: This document must be updated whenever significant changes are made to the project. It serves as the single source of truth for AI assistants to understand the project structure and patterns.

---

*This document is designed to be the definitive guide for AI assistants working on this project. When making changes, always reference this document for patterns and standards, and update it to reflect new features or architectural changes.*
