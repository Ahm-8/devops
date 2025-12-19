# GitHub Actions CI/CD Setup Guide

This guide explains how to set up automatic deployments from GitHub to AWS.

## Architecture

```
GitHub Push → GitHub Actions → AWS
  ├─ frontend/ changes    → Build React → S3 + CloudFront
  ├─ booking-service/     → Build Docker → ECR → ECS
  └─ weather-service/     → Build Docker → ECR → ECS
```

## Setup Steps

### 1. Get AWS Credentials from Learner Lab

For Learner Labs, you'll need to update credentials regularly as they expire after 4 hours.

**Option A: Use GitHub Secrets (Update every 4 hours)**
1. Start your Learner Lab
2. Click "AWS Details"
3. Copy credentials and add to GitHub Secrets

**Option B: Use Long-lived Credentials (Recommended)**
If you have a regular AWS account, create an IAM user with programmatic access.

### 2. Add GitHub Secrets

Go to your GitHub repository → Settings → Secrets and variables → Actions → New repository secret

Add these secrets:

```
# AWS Credentials (from Learner Lab or IAM user)
AWS_ACCESS_KEY_ID
AWS_SECRET_ACCESS_KEY
AWS_SESSION_TOKEN  # Only needed for Learner Lab

# From Lab 1 Terraform outputs
FRONTEND_S3_BUCKET             # e.g., conference-booking-frontend-dev
CLOUDFRONT_DISTRIBUTION_ID      # e.g., E1234567890ABC
CLOUDFRONT_DOMAIN              # e.g., d1234567890abc.cloudfront.net
BOOKING_SERVICE_URL            # e.g., http://booking-alb-123.elb.amazonaws.com

# From Lab 2 Terraform outputs
COGNITO_USER_POOL_ID           # e.g., us-east-1_AbCdEfGhI
COGNITO_CLIENT_ID              # e.g., 1a2b3c4d5e6f7g8h9i0j
COGNITO_DOMAIN                 # e.g., conference-booking-dev-abc123
```

### 3. Get Terraform Outputs

After deploying infrastructure, get these values:

**Lab 1:**
```bash
cd infrastructure/lab1-backend
terraform output frontend_s3_bucket
terraform output frontend_cloudfront_id
terraform output frontend_cloudfront_url
terraform output booking_service_alb_url
```

**Lab 2:**
```bash
cd infrastructure/lab2-data
terraform output cognito_user_pool_id
terraform output cognito_user_pool_client_id
terraform output cognito_domain
```

### 4. Test GitHub Actions Locally (Optional)

Install act to test workflows locally:
```bash
brew install act

# Test frontend deployment
act -W .github/workflows/deploy-frontend.yml --secret-file .env.secrets
```

### 5. Trigger Deployment

**Automatic:**
- Push changes to `main` branch
- Changes to `frontend/` → Deploys frontend
- Changes to `booking-service/` → Deploys booking service
- Changes to `weather-service/` → Deploys weather service

**Manual:**
- Go to Actions tab in GitHub
- Select workflow
- Click "Run workflow"

## Frontend React App Setup

### Install AWS Amplify for Cognito

```bash
cd frontend
npm install aws-amplify @aws-amplify/ui-react
```

### Configure Amplify

Create `frontend/src/aws-config.js`:

```javascript
const awsConfig = {
  Auth: {
    region: process.env.REACT_APP_COGNITO_REGION,
    userPoolId: process.env.REACT_APP_COGNITO_USER_POOL_ID,
    userPoolWebClientId: process.env.REACT_APP_COGNITO_CLIENT_ID,
    oauth: {
      domain: `${process.env.REACT_APP_COGNITO_DOMAIN}.auth.${process.env.REACT_APP_COGNITO_REGION}.amazoncognito.com`,
      scope: ['email', 'openid', 'profile'],
      redirectSignIn: window.location.origin + '/callback',
      redirectSignOut: window.location.origin + '/login',
      responseType: 'code'
    }
  },
  API: {
    endpoints: [
      {
        name: 'BookingAPI',
        endpoint: process.env.REACT_APP_API_URL
      }
    ]
  }
};

export default awsConfig;
```

### Update App.js

```javascript
import { Amplify } from 'aws-amplify';
import { Authenticator } from '@aws-amplify/ui-react';
import '@aws-amplify/ui-react/styles.css';
import awsConfig from './aws-config';

Amplify.configure(awsConfig);

function App() {
  return (
    <Authenticator>
      {({ signOut, user }) => (
        <div>
          <h1>Welcome {user.username}</h1>
          <button onClick={signOut}>Sign out</button>
          {/* Your app components */}
        </div>
      )}
    </Authenticator>
  );
}

export default App;
```

### Make Authenticated API Calls

```javascript
import { Auth, API } from 'aws-amplify';

// Get bookings
async function getBookings() {
  try {
    const session = await Auth.currentSession();
    const token = session.getIdToken().getJwtToken();
    
    const response = await fetch(`${process.env.REACT_APP_API_URL}/api/bookings`, {
      headers: {
        'Authorization': `Bearer ${token}`
      }
    });
    
    return await response.json();
  } catch (error) {
    console.error('Error fetching bookings:', error);
  }
}

// Create booking
async function createBooking(bookingData) {
  try {
    const session = await Auth.currentSession();
    const token = session.getIdToken().getJwtToken();
    
    const response = await fetch(`${process.env.REACT_APP_API_URL}/api/bookings`, {
      method: 'POST',
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      },
      body: JSON.stringify(bookingData)
    });
    
    return await response.json();
  } catch (error) {
    console.error('Error creating booking:', error);
  }
}
```

### Frontend Environment Variables

Create `frontend/.env.example`:

```bash
REACT_APP_API_URL=http://your-booking-alb.elb.amazonaws.com
REACT_APP_COGNITO_USER_POOL_ID=us-east-1_XXXXXXXXX
REACT_APP_COGNITO_CLIENT_ID=xxxxxxxxxxxxxxxxxxxxxxxxxx
REACT_APP_COGNITO_REGION=us-east-1
REACT_APP_COGNITO_DOMAIN=conference-booking-dev-xxxxx
```

## Deployment Workflow

### 1. Initial Setup (Once)
```bash
# Deploy infrastructure
cd infrastructure/lab2-data && terraform apply
cd ../lab1-backend && terraform apply

# Add GitHub secrets from Terraform outputs
# Create .env file for local development
```

### 2. Daily Development
```bash
# Make changes to your code
git add .
git commit -m "Add new feature"
git push origin main

# GitHub Actions automatically:
# - Builds your code
# - Pushes to ECR/S3
# - Deploys to ECS/CloudFront
```

### 3. Update Learner Lab Credentials (Every 4 hours)

**Quick Script:**
Create `scripts/update-github-secrets.sh`:

```bash
#!/bin/bash

# Get new credentials from Learner Lab
echo "Paste AWS_ACCESS_KEY_ID:"
read AWS_ACCESS_KEY_ID

echo "Paste AWS_SECRET_ACCESS_KEY:"
read AWS_SECRET_ACCESS_KEY

echo "Paste AWS_SESSION_TOKEN:"
read AWS_SESSION_TOKEN

# Update GitHub secrets using GitHub CLI
gh secret set AWS_ACCESS_KEY_ID -b"$AWS_ACCESS_KEY_ID"
gh secret set AWS_SECRET_ACCESS_KEY -b"$AWS_SECRET_ACCESS_KEY"
gh secret set AWS_SESSION_TOKEN -b"$AWS_SESSION_TOKEN"

echo "✅ GitHub secrets updated!"
```

Run it:
```bash
chmod +x scripts/update-github-secrets.sh
./scripts/update-github-secrets.sh
```

## Monitoring Deployments

### View Workflow Status
- Go to GitHub repository → Actions tab
- Click on the running workflow
- View logs in real-time

### Check Deployment Health

**Frontend:**
```bash
# Check CloudFront
aws cloudfront get-distribution --id YOUR_DISTRIBUTION_ID

# Check S3
aws s3 ls s3://your-frontend-bucket/
```

**Backend:**
```bash
# Check ECS service
aws ecs describe-services \
  --cluster conference-booking-cluster-dev \
  --services conference-booking-booking-service-dev

# Check container logs
aws logs tail /ecs/conference-booking-booking-dev --follow
```

## Troubleshooting

### Workflow fails with "Credentials expired"
→ Update GitHub secrets with new Learner Lab credentials

### Frontend builds but shows blank page
→ Check browser console for errors
→ Verify REACT_APP_* environment variables
→ Check CloudFront invalidation completed

### ECS service fails to start
→ Check CloudWatch logs for container errors
→ Verify ECR image was pushed successfully
→ Check ECS task definition environment variables

### CORS errors from API
→ Add CloudFront URL to ALB allowed origins
→ Check API response headers include CORS headers

## Cost Optimization

### Free Tier Usage:
- **CloudFront**: 1TB data transfer, 10M requests/month free
- **S3**: 5GB storage, 20K GET requests free
- **GitHub Actions**: 2000 minutes/month free

### Keep Costs Down:
- Use CloudFront price class 100 (US/EU only)
- Set S3 lifecycle rules to delete old versions
- Use ECR lifecycle policies (keep last 5 images)
- Stop ECS services when not testing

## Advanced: Custom Domain

If you want `app.yourdomain.com` instead of CloudFront URL:

1. **Register domain** (Route 53 or external)
2. **Create SSL certificate** (ACM)
3. **Update Terraform**:

```hcl
# In lab1-backend/frontend.tf
viewer_certificate {
  acm_certificate_arn = aws_acm_certificate.cert.arn
  ssl_support_method  = "sni-only"
  minimum_protocol_version = "TLSv1.2_2021"
}
```

4. **Update Route 53** with CloudFront alias
5. **Update Cognito callbacks** with new domain

## Next Steps

1. ✅ Set up GitHub secrets
2. ✅ Configure frontend with Cognito
3. ✅ Push code to trigger first deployment
4. ✅ Test authentication flow
5. ✅ Monitor CloudWatch logs
6. ✅ Set up alerts for failures
