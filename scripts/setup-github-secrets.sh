#!/bin/bash

# Setup GitHub secrets for CI/CD

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║          GitHub Secrets Configuration                      ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""

# Check if gh CLI is installed
if ! command -v gh &> /dev/null; then
    echo "❌ GitHub CLI (gh) is required but not installed."
    echo ""
    echo "Install it with:"
    echo "  macOS: brew install gh"
    echo "  Linux: See https://github.com/cli/cli#installation"
    echo ""
    exit 1
fi

# Check if authenticated
if ! gh auth status &> /dev/null; then
    echo "Please authenticate with GitHub:"
    gh auth login
fi

echo ""
echo "This script will set up GitHub secrets from Terraform outputs."
echo ""

# Read Lab 1 outputs
if [ ! -f "infrastructure/lab1-outputs.json" ]; then
    echo "❌ Lab 1 outputs not found. Run initial-setup.sh first."
    exit 1
fi

# Read Lab 2 outputs
if [ ! -f "infrastructure/lab2-outputs.json" ]; then
    echo "❌ Lab 2 outputs not found. Run initial-setup.sh first."
    exit 1
fi

echo "Reading Terraform outputs..."

# Extract Lab 1 values
FRONTEND_BUCKET=$(jq -r '.frontend_s3_bucket.value' infrastructure/lab1-outputs.json)
CLOUDFRONT_ID=$(jq -r '.frontend_cloudfront_id.value' infrastructure/lab1-outputs.json)
CLOUDFRONT_URL=$(jq -r '.frontend_cloudfront_url.value' infrastructure/lab1-outputs.json)
CLOUDFRONT_DOMAIN=$(echo "$CLOUDFRONT_URL" | sed 's|https://||')
BOOKING_URL=$(jq -r '.booking_service_alb_url.value' infrastructure/lab1-outputs.json)

# Extract Lab 2 values
COGNITO_POOL_ID=$(jq -r '.cognito_user_pool_id.value' infrastructure/lab2-outputs.json)
COGNITO_CLIENT_ID=$(jq -r '.cognito_user_pool_client_id.value' infrastructure/lab2-outputs.json)
COGNITO_DOMAIN=$(jq -r '.cognito_domain.value' infrastructure/lab2-outputs.json)

echo ""
echo "Setting GitHub secrets..."

# Set secrets
echo "$FRONTEND_BUCKET" | gh secret set FRONTEND_S3_BUCKET
echo "$CLOUDFRONT_ID" | gh secret set CLOUDFRONT_DISTRIBUTION_ID
echo "$CLOUDFRONT_DOMAIN" | gh secret set CLOUDFRONT_DOMAIN
echo "$BOOKING_URL" | gh secret set BOOKING_SERVICE_URL
echo "$COGNITO_POOL_ID" | gh secret set COGNITO_USER_POOL_ID
echo "$COGNITO_CLIENT_ID" | gh secret set COGNITO_CLIENT_ID
echo "$COGNITO_DOMAIN" | gh secret set COGNITO_DOMAIN

echo ""
echo "✅ GitHub secrets configured!"
echo ""
echo "════════════════════════════════════════════════════════════"
echo "⚠️  IMPORTANT: AWS Credentials"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "You still need to manually set AWS credentials in GitHub:"
echo ""
echo "1. Go to Learner Lab → AWS Details → Show"
echo "2. Copy the credentials"
echo "3. Go to GitHub → Settings → Secrets → New secret"
echo ""
echo "Add these secrets:"
echo "  - AWS_ACCESS_KEY_ID"
echo "  - AWS_SECRET_ACCESS_KEY"
echo "  - AWS_SESSION_TOKEN"
echo ""
echo "Or run: ./scripts/update-credentials.sh"
echo "   and choose option 2 (Update GitHub secrets)"
echo ""
echo "════════════════════════════════════════════════════════════"
echo ""
echo "Next: Push your code to GitHub to trigger deployments!"
echo ""
