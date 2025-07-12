#!/bin/bash

# GitHub-Postman Integration Setup Script
# This script helps set up GitHub integration with Postman for the My Dining v2 API

echo "🚀 GitHub-Postman Integration Setup for My Dining v2"
echo "================================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if we're in the right directory
if [ ! -f "composer.json" ] || [ ! -d "app" ]; then
    print_error "This script must be run from the Laravel project root directory!"
    exit 1
fi

print_status "Setting up GitHub-Postman integration..."

# Step 1: Create .gitignore entries for Postman
print_status "Updating .gitignore for Postman files..."

if [ ! -f ".gitignore" ]; then
    touch .gitignore
fi

# Add Postman entries if they don't exist
if ! grep -q "# Postman" .gitignore; then
    cat >> .gitignore << EOF

# Postman
postman/newman-report.html
postman/*.backup.json
postman/temp-*
EOF
    print_success "Updated .gitignore with Postman entries"
else
    print_warning ".gitignore already contains Postman entries"
fi

# Step 2: Verify Postman files exist
print_status "Checking Postman collection files..."

required_files=(
    "postman/My_Dining_v2_Meal_Request_API.postman_collection.json"
    "postman/development.postman_environment.json"
    "postman/production.postman_environment.json"
    "postman/README.md"
)

missing_files=()
for file in "${required_files[@]}"; do
    if [ ! -f "$file" ]; then
        missing_files+=("$file")
    fi
done

if [ ${#missing_files[@]} -eq 0 ]; then
    print_success "All Postman files are present"
else
    print_error "Missing Postman files:"
    for file in "${missing_files[@]}"; do
        echo "  - $file"
    done
    print_error "Please ensure all Postman files are created first!"
    exit 1
fi

# Step 3: Setup GitHub Actions workflow
print_status "Checking GitHub Actions workflow..."

if [ -f ".github/workflows/api-testing.yml" ]; then
    print_success "GitHub Actions workflow file exists"
else
    print_warning "GitHub Actions workflow file not found"
    print_status "The workflow file should be at: .github/workflows/api-testing.yml"
fi

# Step 4: Git setup
print_status "Setting up Git repository..."

if [ ! -d ".git" ]; then
    print_status "Initializing Git repository..."
    git init
    print_success "Git repository initialized"
fi

# Add Postman files to Git
print_status "Adding Postman files to Git..."
git add postman/
git add .github/workflows/api-testing.yml 2>/dev/null || true

if [ -n "$(git status --porcelain)" ]; then
    print_status "Files staged for commit. Run the following commands:"
    echo ""
    echo "git commit -m 'Add Postman collection and GitHub integration'"
    echo "git push origin main"
    echo ""
else
    print_success "All files are already committed"
fi

# Step 5: Instructions for GitHub Personal Access Token
echo ""
print_status "Next Steps for GitHub Integration:"
echo ""
echo "1. 🔑 Create GitHub Personal Access Token:"
echo "   - Go to: https://github.com/settings/tokens"
echo "   - Click 'Generate new token (classic)'"
echo "   - Select scopes: 'repo', 'read:org'"
echo "   - Copy the generated token"
echo ""
echo "2. 📋 Connect Postman to GitHub:"
echo "   - Open Postman"
echo "   - Import collection: postman/My_Dining_v2_Meal_Request_API.postman_collection.json"
echo "   - Import environments: postman/*.postman_environment.json"
echo "   - Go to Collection → ... → Integrations"
echo "   - Select 'GitHub'"
echo "   - Repository: $(git remote get-url origin 2>/dev/null || echo 'your-username/my_dining_v2')"
echo "   - Branch: main"
echo "   - Directory: postman/"
echo "   - Enter your Personal Access Token"
echo ""
echo "3. 🚀 Enable GitHub Actions:"
echo "   - Go to your GitHub repository"
echo "   - Click 'Actions' tab"
echo "   - Enable workflows if prompted"
echo "   - The API testing workflow will run on every push"
echo ""
echo "4. 🔧 Configure Environment Variables:"
echo "   - Update postman/development.postman_environment.json with your local settings"
echo "   - Update postman/production.postman_environment.json for production"
echo "   - Never commit real passwords or API keys!"
echo ""

# Step 6: Additional tools check
print_status "Checking for additional tools..."

# Check if Newman is installed globally
if command -v newman &> /dev/null; then
    print_success "Newman (Postman CLI) is installed"
    echo "   Version: $(newman --version)"
else
    print_warning "Newman (Postman CLI) not found"
    echo "   Install with: npm install -g newman"
    echo "   This allows running Postman collections from command line"
fi

# Check if Node.js is installed
if command -v node &> /dev/null; then
    print_success "Node.js is installed"
    echo "   Version: $(node --version)"
else
    print_warning "Node.js not found (required for Newman)"
    echo "   Download from: https://nodejs.org/"
fi

echo ""
print_success "Setup completed! 🎉"
echo ""
print_status "Quick test commands:"
echo "   # Test locally with Newman:"
echo "   newman run postman/My_Dining_v2_Meal_Request_API.postman_collection.json \\"
echo "          --environment postman/development.postman_environment.json"
echo ""
echo "   # Start Laravel development server:"
echo "   php artisan serve"
echo ""
print_status "Documentation:"
echo "   - Postman setup: postman/README.md"
echo "   - API documentation: docs/meal-request-system.md"
echo "   - Implementation details: MEAL_REQUEST_IMPLEMENTATION.md"
echo ""
print_status "Support:"
echo "   - Postman docs: https://learning.postman.com/"
echo "   - GitHub Actions: https://docs.github.com/en/actions"
echo "   - Laravel API: https://laravel.com/docs/api-authentication"
