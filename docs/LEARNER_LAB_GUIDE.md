# Learner Lab Limitations & Solutions

## The 4-Hour Problem

AWS Learner Lab credentials **expire after 4 hours** and **cannot be extended**. This is a hard limit set by AWS Academy.

## Solutions & Workarounds

### Option 1: Quick Credential Updates (Recommended)

Use the automation script provided:

```bash
# Every time you restart Learner Lab (every 4 hours)
./scripts/update-credentials.sh

# Choose:
# 1 - Update local (for Terraform/AWS CLI)
# 2 - Update GitHub (for CI/CD)
# 3 - Both
```

**Time required:** ~30 seconds

### Option 2: Keep Infrastructure Alive

Since you have **two Learner Labs**, you can:

1. **Lab 2 (Data)** - Keep running as long as possible (costs ~$0)
   - Cognito is serverless (no ongoing cost)
   - DynamoDB on-demand (pay per request)
   
2. **Lab 1 (Services)** - Only run when actively developing
   - ECS costs money while running
   - Stop ECS services when not in use

**Stop ECS services to save budget:**
```bash
# Stop services (keeps infrastructure, stops containers)
aws ecs update-service --cluster conference-booking-cluster-dev \
  --service conference-booking-booking-service-dev \
  --desired-count 0

aws ecs update-service --cluster conference-booking-cluster-dev \
  --service conference-booking-weather-service-dev \
  --desired-count 0

# Start when needed
aws ecs update-service --cluster conference-booking-cluster-dev \
  --service conference-booking-booking-service-dev \
  --desired-count 1
```

### Option 3: Develop Locally, Deploy Less Often

1. **Run services locally** with Docker Compose
2. **Connect to AWS resources** (DynamoDB, Cognito) from local
3. **Only deploy to AWS** when testing full integration

Create `docker-compose.yml`:
```yaml
version: '3.8'
services:
  booking-service:
    build: ./booking-service
    ports:
      - "8000:8000"
    environment:
      AWS_ACCESS_KEY_ID: ${AWS_ACCESS_KEY_ID}
      AWS_SECRET_ACCESS_KEY: ${AWS_SECRET_ACCESS_KEY}
      AWS_SESSION_TOKEN: ${AWS_SESSION_TOKEN}
      AWS_DEFAULT_REGION: us-east-1
      DYNAMODB_BOOKINGS_TABLE: conference-booking-bookings-dev
      WEATHER_SERVICE_URL: http://weather-service:8001

  weather-service:
    build: ./weather-service
    ports:
      - "8001:8001"
    environment:
      AWS_ACCESS_KEY_ID: ${AWS_ACCESS_KEY_ID}
      AWS_SECRET_ACCESS_KEY: ${AWS_SECRET_ACCESS_KEY}
      AWS_SESSION_TOKEN: ${AWS_SESSION_TOKEN}
      AWS_DEFAULT_REGION: us-east-1
      DYNAMODB_WEATHER_TABLE: conference-booking-weather-cache-dev
```

Run locally:
```bash
# Load credentials
source ~/.aws-learner-lab-credentials

# Run services
docker-compose up
```

### Option 4: Use GitHub Actions Less Frequently

Instead of auto-deploying on every push:

1. **Develop locally** with frequent commits
2. **Deploy manually** when ready:
   ```bash
   # Trigger workflow manually from GitHub UI
   # Actions → Select workflow → Run workflow
   ```

3. **Or use workflow_dispatch only:**

Update `.github/workflows/deploy-*.yml`:
```yaml
on:
  # Remove push trigger, keep only manual
  workflow_dispatch:
```

### Option 5: Terraform State Lock Workaround

If you hit "state locked" errors across sessions:

```bash
# Force unlock (use carefully!)
terraform force-unlock <LOCK_ID>

# Or use local state instead of S3
# Comment out the backend in main.tf
```

## Recommended Workflow

### Daily Development (Active Work)
```bash
# Morning: Start both labs
./scripts/update-credentials.sh  # Choose option 3 (both)

# Work locally with Docker Compose
docker-compose up

# Test changes locally
curl http://localhost:8000/api/bookings

# When satisfied, commit
git commit -m "Add new feature"
git push origin develop  # Push to develop, not main

# Evening: Stop ECS services to save money
aws ecs update-service ... --desired-count 0
```

### Weekly Deployment (to Main)
```bash
# Start Learner Labs
./scripts/update-credentials.sh  # Update GitHub secrets

# Merge to main (triggers auto-deployment)
git checkout main
git merge develop
git push origin main

# GitHub Actions deploys everything

# Verify deployment
curl https://your-cloudfront-url.cloudfront.net
```

### Before Deadline (Final Testing)
```bash
# Make sure both labs running
./scripts/update-credentials.sh

# Full infrastructure test
# Let it run for a few hours

# Demo to professor with live system
```

## Time-Saving Tips

### 1. One-Time Initial Setup
```bash
# Run once (takes ~15 minutes)
./scripts/initial-setup.sh

# Saves Lab 1 & Lab 2 outputs
# Sets up everything automatically
```

### 2. Auto-Source Credentials
Add to your `~/.zshrc`:
```bash
# Auto-load AWS credentials if available
[ -f ~/.aws-learner-lab-credentials ] && source ~/.aws-learner-lab-credentials
```

### 3. Use Terraform Workspaces
Keep state separate for quick switches:
```bash
terraform workspace new dev
terraform workspace new prod
terraform workspace select dev
```

### 4. Pre-built Docker Images
Build images once, reuse:
```bash
# Build and tag
docker build -t booking:latest ./booking-service

# Push to DockerHub (free tier)
docker tag booking:latest yourusername/booking:latest
docker push yourusername/booking:latest

# Update ECS to pull from DockerHub instead of ECR
```

## Budget Management

### Monitor Spending
```bash
# Check current month costs
aws ce get-cost-and-usage \
  --time-period Start=2025-12-01,End=2025-12-16 \
  --granularity DAILY \
  --metrics BlendedCost

# Or use AWS Console → Billing Dashboard
```

### What Costs Money
- ✅ **FREE**: Cognito, DynamoDB (light usage), S3 (5GB), CloudFront (1TB)
- 💰 **COSTS**: ECS Fargate (~$0.04/hour/task), ALB (~$0.02/hour), NAT Gateway (~$0.045/hour)

### Cost Optimization
```bash
# When not actively testing:
# 1. Stop ECS services (desired count = 0)
# 2. Keep infrastructure up (no cost)
# 3. Can restart in seconds when needed

# Avoid destroying/recreating infrastructure
# (Takes 10-15 minutes each time)
```

## When Credentials Expire Mid-Deployment

If GitHub Actions fails with "ExpiredToken":

```bash
# Quick fix:
1. Go to Learner Lab → AWS Details → Show
2. Copy credentials
3. Update GitHub secrets:
   ./scripts/update-credentials.sh  # Choose option 2

4. Re-run failed workflow:
   Go to Actions → Click failed workflow → Re-run jobs
```

## Alternative: Use Regular AWS Account

If you have access to a regular AWS account (not Learner Lab):

**Pros:**
- No 4-hour limit
- No budget cap
- All services available
- Better for production

**Cons:**
- Costs real money (~$30-50/month)
- Need credit card
- Your responsibility to manage costs

**If using regular AWS:**
1. Create IAM user with programmatic access
2. Set credentials in GitHub (they never expire)
3. No need to run update scripts

## Summary

**Best approach for Learner Lab:**
1. ✅ Run `./scripts/update-credentials.sh` every session (~30 seconds)
2. ✅ Develop locally with Docker Compose
3. ✅ Deploy to AWS only when needed
4. ✅ Stop ECS services when not testing
5. ✅ Use GitHub Actions sparingly (manual triggers)

**The 4-hour limit is annoying but manageable with automation!**
