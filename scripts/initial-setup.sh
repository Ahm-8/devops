#!/bin/bash

# One-time setup script for initial deployment

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║        Conference Booking System - Initial Setup          ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Check prerequisites
echo "Checking prerequisites..."

# Check Terraform
if ! command -v terraform &> /dev/null; then
    echo "❌ Terraform not installed. Install it with: brew install terraform"
    exit 1
fi
echo "✅ Terraform found"

# Check AWS CLI
if ! command -v aws &> /dev/null; then
    echo "❌ AWS CLI not installed. Install it with: brew install awscli"
    exit 1
fi
echo "✅ AWS CLI found"

# Check Docker
if ! command -v docker &> /dev/null; then
    echo "❌ Docker not installed. Install it from: https://www.docker.com/products/docker-desktop"
    exit 1
fi
echo "✅ Docker found"

echo ""
echo "════════════════════════════════════════════════════════════"
echo "STEP 1: Set up Lab 2 credentials (Data & Auth)"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "1. Open Learner Lab 2"
echo "2. Start the lab and wait for it to be ready"
echo "3. Click 'AWS Details' → 'Show'"
echo "4. Copy all THREE export lines"
echo ""
read -p "Press Enter when ready to paste credentials..."

echo ""
echo "Paste the credentials and press Ctrl+D:"
credentials=$(cat)

# Export credentials
eval "$credentials"

echo ""
echo "✅ Lab 2 credentials set"
echo ""

# Deploy Lab 2
echo "════════════════════════════════════════════════════════════"
echo "STEP 2: Deploy Lab 2 (Cognito + DynamoDB)"
echo "════════════════════════════════════════════════════════════"
echo ""

cd infrastructure/lab2-data

# Initialize if not done
if [ ! -d ".terraform" ]; then
    terraform init
fi

echo ""
read -p "Ready to deploy Lab 2? (y/n): " confirm
if [ "$confirm" != "y" ]; then
    echo "Deployment cancelled"
    exit 1
fi

terraform apply -auto-approve

echo ""
echo "✅ Lab 2 deployed!"
echo ""

# Save Lab 2 outputs
echo "Saving Lab 2 outputs..."
terraform output -json > ../lab2-outputs.json

# Extract important values
COGNITO_POOL_ARN=$(terraform output -raw cognito_user_pool_arn 2>/dev/null || echo "")
COGNITO_POOL_ID=$(terraform output -raw cognito_user_pool_id 2>/dev/null || echo "")
COGNITO_CLIENT_ID=$(terraform output -raw cognito_user_pool_client_id 2>/dev/null || echo "")
COGNITO_DOMAIN=$(terraform output -raw cognito_domain 2>/dev/null || echo "")
BOOKING_TABLE_ARN=$(terraform output -raw dynamodb_bookings_table_arn 2>/dev/null || echo "")
WEATHER_TABLE_ARN=$(terraform output -raw dynamodb_weather_table_arn 2>/dev/null || echo "")

cd ../..

echo ""
echo "════════════════════════════════════════════════════════════"
echo "STEP 3: Set up Lab 1 credentials (Services)"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "1. Open Learner Lab 1 (in a NEW browser window)"
echo "2. Start the lab and wait for it to be ready"
echo "3. Click 'AWS Details' → 'Show'"
echo "4. Copy all THREE export lines"
echo ""
read -p "Press Enter when ready to paste Lab 1 credentials..."

echo ""
echo "Paste the credentials and press Ctrl+D:"
credentials=$(cat)

# Export credentials
eval "$credentials"

echo ""
echo "✅ Lab 1 credentials set"
echo ""

# Create terraform.tfvars for Lab 1
echo "Creating Lab 1 configuration..."

cd infrastructure/lab1-backend

cat > terraform.tfvars << EOF
aws_region     = "us-east-1"
environment    = "dev"
project_name   = "conference-booking"

# From Lab 2
cognito_user_pool_arn       = "$COGNITO_POOL_ARN"
cognito_user_pool_client_id = "$COGNITO_CLIENT_ID"
cognito_user_pool_domain    = "$COGNITO_DOMAIN"
booking_dynamodb_table_arn  = "$BOOKING_TABLE_ARN"
weather_dynamodb_table_arn  = "$WEATHER_TABLE_ARN"
EOF

echo ""
echo "════════════════════════════════════════════════════════════"
echo "STEP 4: Deploy Lab 1 (Services + Frontend)"
echo "════════════════════════════════════════════════════════════"
echo ""

# Initialize if not done
if [ ! -d ".terraform" ]; then
    terraform init
fi

echo ""
read -p "Ready to deploy Lab 1? (y/n): " confirm
if [ "$confirm" != "y" ]; then
    echo "Deployment cancelled"
    exit 1
fi

terraform apply -auto-approve

echo ""
echo "✅ Lab 1 deployed!"
echo ""

# Save Lab 1 outputs
echo "Saving Lab 1 outputs..."
terraform output -json > ../lab1-outputs.json

CLOUDFRONT_URL=$(terraform output -raw frontend_cloudfront_url 2>/dev/null || echo "")
CLOUDFRONT_ID=$(terraform output -raw frontend_cloudfront_id 2>/dev/null || echo "")
FRONTEND_BUCKET=$(terraform output -raw frontend_s3_bucket 2>/dev/null || echo "")
BOOKING_ALB_URL=$(terraform output -raw booking_service_alb_url 2>/dev/null || echo "")
ECR_BOOKING=$(terraform output -raw ecr_booking_service_url 2>/dev/null || echo "")
ECR_WEATHER=$(terraform output -raw ecr_weather_service_url 2>/dev/null || echo "")

cd ../..

echo ""
echo "════════════════════════════════════════════════════════════"
echo "STEP 5: Update Cognito with CloudFront URL"
echo "════════════════════════════════════════════════════════════"
echo ""

# Switch back to Lab 2 credentials
echo "Switching back to Lab 2 credentials..."
echo "Paste Lab 2 credentials again and press Ctrl+D:"
credentials=$(cat)
eval "$credentials"

cd infrastructure/lab2-data

# Update Cognito callback URLs
cat > terraform.tfvars << EOF
aws_region     = "us-east-1"
environment    = "dev"
project_name   = "conference-booking"
enable_cognito = true

cognito_callback_urls = [
  "$CLOUDFRONT_URL/callback",
  "http://localhost:3000/callback"
]

cognito_logout_urls = [
  "$CLOUDFRONT_URL",
  "http://localhost:3000"
]
EOF

terraform apply -auto-approve

cd ../..

echo ""
echo "════════════════════════════════════════════════════════════"
echo "✅ DEPLOYMENT COMPLETE!"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "📋 Important URLs and Values:"
echo ""
echo "Frontend (CloudFront): $CLOUDFRONT_URL"
echo "Booking API: $BOOKING_ALB_URL"
echo ""
echo "Cognito User Pool ID: $COGNITO_POOL_ID"
echo "Cognito Client ID: $COGNITO_CLIENT_ID"
echo "Cognito Domain: $COGNITO_DOMAIN"
echo ""
echo "ECR Booking Service: $ECR_BOOKING"
echo "ECR Weather Service: $ECR_WEATHER"
echo ""
echo "S3 Frontend Bucket: $FRONTEND_BUCKET"
echo "CloudFront Distribution ID: $CLOUDFRONT_ID"
echo ""
echo "════════════════════════════════════════════════════════════"
echo "📝 Next Steps:"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "1. Save these values to GitHub Secrets:"
echo "   ./scripts/setup-github-secrets.sh"
echo ""
echo "2. Build and deploy your services:"
echo "   cd booking-service && docker build -t booking ."
echo "   # Push to ECR and deploy"
echo ""
echo "3. Deploy your React frontend:"
echo "   cd frontend && npm run build"
echo "   aws s3 sync build/ s3://$FRONTEND_BUCKET"
echo ""
echo "4. Update credentials when Lab sessions restart:"
echo "   ./scripts/update-credentials.sh"
echo ""
echo "════════════════════════════════════════════════════════════"
