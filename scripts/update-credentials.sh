#!/bin/bash

# Quick credential update script for Learner Lab
# Run this every time you restart your Learner Lab session

set -e

echo "╔════════════════════════════════════════════════════════════╗"
echo "║   AWS Learner Lab Credential Auto-Update Script           ║"
echo "╚════════════════════════════════════════════════════════════╝"
echo ""
echo "Instructions:"
echo "1. Start your Learner Lab and wait for it to be ready"
echo "2. Click 'AWS Details' button"
echo "3. Click 'Show' next to AWS CLI credentials"
echo "4. Copy ALL THREE export lines"
echo "5. Paste them below when prompted"
echo ""
echo "Enter your choice:"
echo "  1) Update local environment (for Terraform)"
echo "  2) Update GitHub secrets (for CI/CD)"
echo "  3) Both"
echo ""
read -p "Choice (1/2/3): " choice

echo ""
echo "Paste the THREE export lines here, then press Enter and Ctrl+D:"
echo "════════════════════════════════════════════════════════════"

# Read multi-line input
credentials=$(cat)

# Extract credentials using regex
access_key=$(echo "$credentials" | grep -oP 'AWS_ACCESS_KEY_ID=\K[^ ]+')
secret_key=$(echo "$credentials" | grep -oP 'AWS_SECRET_ACCESS_KEY=\K[^ ]+')
session_token=$(echo "$credentials" | grep -oP 'AWS_SESSION_TOKEN=\K[^ ]+')

if [ -z "$access_key" ] || [ -z "$secret_key" ] || [ -z "$session_token" ]; then
    echo ""
    echo "❌ Error: Could not parse credentials. Make sure you copied all three lines."
    exit 1
fi

echo ""
echo "✅ Credentials parsed successfully!"
echo ""

# Option 1 or 3: Update local environment
if [ "$choice" = "1" ] || [ "$choice" = "3" ]; then
    echo "Exporting credentials to current shell..."
    export AWS_ACCESS_KEY_ID="$access_key"
    export AWS_SECRET_ACCESS_KEY="$secret_key"
    export AWS_SESSION_TOKEN="$session_token"
    
    # Save to a file that can be sourced
    cat > ~/.aws-learner-lab-credentials << EOF
export AWS_ACCESS_KEY_ID="$access_key"
export AWS_SECRET_ACCESS_KEY="$secret_key"
export AWS_SESSION_TOKEN="$session_token"
export AWS_DEFAULT_REGION=us-east-1
EOF
    
    echo "✅ Local credentials updated!"
    echo ""
    echo "To use in other terminal windows, run:"
    echo "   source ~/.aws-learner-lab-credentials"
    echo ""
fi

# Option 2 or 3: Update GitHub secrets
if [ "$choice" = "2" ] || [ "$choice" = "3" ]; then
    # Check if gh CLI is installed
    if ! command -v gh &> /dev/null; then
        echo "⚠️  GitHub CLI (gh) not installed."
        echo "Install it with: brew install gh"
        echo ""
        echo "Or manually update GitHub secrets:"
        echo "  Go to: Settings → Secrets and variables → Actions"
        echo ""
        echo "AWS_ACCESS_KEY_ID=$access_key"
        echo "AWS_SECRET_ACCESS_KEY=$secret_key"
        echo "AWS_SESSION_TOKEN=$session_token"
    else
        echo "Updating GitHub secrets..."
        
        # Check if authenticated
        if ! gh auth status &> /dev/null; then
            echo "Please authenticate with GitHub first:"
            gh auth login
        fi
        
        # Update secrets
        echo "$access_key" | gh secret set AWS_ACCESS_KEY_ID
        echo "$secret_key" | gh secret set AWS_SECRET_ACCESS_KEY
        echo "$session_token" | gh secret set AWS_SESSION_TOKEN
        
        echo "✅ GitHub secrets updated!"
        echo ""
    fi
fi

echo "════════════════════════════════════════════════════════════"
echo "⏰ Remember: These credentials expire in ~4 hours"
echo "   Run this script again when you restart your Learner Lab"
echo "════════════════════════════════════════════════════════════"
