# Updated Multi-Lab Architecture Guide

## Architecture Overview

```
┌─────────────────────────────────────────────────────────────────┐
│                            FRONTEND                              │
│                        (Your Computer)                           │
│                                                                   │
│  1. User logs in via Cognito Hosted UI (Lab 2)                  │
│  2. Gets JWT token                                               │
│  3. Makes requests to Booking Service ALB with token            │
└──────────────────────────┬───────────────────────────────────────┘
                           │ HTTP + JWT Token
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                      LAB 1 - SERVICES                           │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  VPC (10.1.0.0/16)                                        │  │
│  │                                                            │  │
│  │  ┌────────────────────────────────────────┐              │  │
│  │  │   Booking Service ALB (Public)         │              │  │
│  │  │   - Validates Cognito JWT              │              │  │
│  │  │   - Routes to Booking Service          │              │  │
│  │  └────────────────┬───────────────────────┘              │  │
│  │                   │                                        │  │
│  │                   ▼                                        │  │
│  │  ┌────────────────────────────────────────┐              │  │
│  │  │   Booking Service (ECS Fargate)        │              │  │
│  │  │   - Receives validated requests        │              │  │
│  │  │   - Calls Weather Service if needed    │──┐           │  │
│  │  │   - Access DynamoDB (bookings, rooms)  │  │           │  │
│  │  └────────────────────────────────────────┘  │           │  │
│  │                                                │           │  │
│  │  ┌────────────────────────────────────────┐  │           │  │
│  │  │   Weather Service ALB (Internal)       │◄─┘           │  │
│  │  └────────────────┬───────────────────────┘              │  │
│  │                   │                                        │  │
│  │                   ▼                                        │  │
│  │  ┌────────────────────────────────────────┐              │  │
│  │  │   Weather Service (ECS Fargate)        │              │  │
│  │  │   - Returns weather forecast           │              │  │
│  │  │   - Access DynamoDB (weather cache)    │              │  │
│  │  └────────────────────────────────────────┘              │  │
│  │                                                            │  │
│  │  ECR: booking-service, weather-service                    │  │
│  └──────────────────────────────────────────────────────────┘  │
└──────────────────┬──────────────────┬──────────────────────────┘
                   │                  │
     IAM Cross-Account Access         │
                   │                  │
                   ▼                  ▼
┌─────────────────────────────────────────────────────────────────┐
│                    LAB 2 - DATA & AUTH                          │
│                                                                   │
│  ┌─────────────────────────────────────────────────────────┐   │
│  │  AWS Cognito User Pool                                   │   │
│  │  - User authentication                                   │   │
│  │  - JWT token issuance                                    │   │
│  │  - Hosted UI for login                                   │   │
│  └─────────────────────────────────────────────────────────┘   │
│                                                                   │
│  ┌────────────────┐  ┌────────────────┐  ┌──────────────────┐ │
│  │   Bookings     │  │     Rooms      │  │  Weather Cache   │ │
│  │   DynamoDB     │  │   DynamoDB     │  │    DynamoDB      │ │
│  │ (Booking Svc)  │  │ (Booking Svc)  │  │ (Weather Svc)    │ │
│  └────────────────┘  └────────────────┘  └──────────────────┘ │
│                                                                   │
└───────────────────────────────────────────────────────────────────┘
```

## Request Flow

### 1. User Authentication
```
Frontend → Cognito Hosted UI (Lab 2) → User logs in → JWT Token returned
```

### 2. Booking Request
```
Frontend (with JWT) 
  → Booking Service ALB (Lab 1) [validates JWT via Cognito]
  → Booking Service ECS Task
  → DynamoDB in Lab 2 (via IAM role)
```

### 3. Weather Request (from Booking Service)
```
Booking Service 
  → Weather Service ALB (Internal, Lab 1)
  → Weather Service ECS Task
  → Weather DynamoDB in Lab 2 (via IAM role)
  → Response back to Booking Service
  → Response to Frontend
```

## Deployment Steps

### Step 1: Deploy Lab 2 (Authentication & Data)

1. **Start Learner Lab 2**:
   ```bash
   export AWS_ACCESS_KEY_ID=...
   export AWS_SECRET_ACCESS_KEY=...
   export AWS_SESSION_TOKEN=...
   ```

2. **Deploy Lab 2**:
   ```bash
   cd infrastructure/lab2-data
   terraform init
   terraform apply
   ```

3. **Save outputs**:
   ```bash
   terraform output -json > ../lab2-outputs.json
   
   # Important outputs:
   terraform output cognito_user_pool_arn
   terraform output cognito_user_pool_client_id
   terraform output cognito_domain
   terraform output dynamodb_bookings_table_arn
   terraform output dynamodb_weather_table_arn
   ```

### Step 2: Deploy Lab 1 (Services)

1. **Start Learner Lab 1**:
   ```bash
   export AWS_ACCESS_KEY_ID=...
   export AWS_SECRET_ACCESS_KEY=...
   export AWS_SESSION_TOKEN=...
   ```

2. **Create `terraform.tfvars`** in `lab1-backend/`:
   ```hcl
   aws_region     = "us-east-1"
   environment    = "dev"
   project_name   = "conference-booking"
   
   # From Lab 2 outputs
   cognito_user_pool_arn       = "arn:aws:cognito-idp:us-east-1:ACCOUNT:userpool/us-east-1_XXXXX"
   cognito_user_pool_client_id = "your-client-id"
   cognito_user_pool_domain    = "conference-booking-dev-xxxxx"
   
   booking_dynamodb_table_arn  = "arn:aws:dynamodb:us-east-1:ACCOUNT:table/conference-booking-bookings-dev"
   weather_dynamodb_table_arn  = "arn:aws:dynamodb:us-east-1:ACCOUNT:table/conference-booking-weather-cache-dev"
   ```

3. **Deploy Lab 1**:
   ```bash
   cd infrastructure/lab1-backend
   terraform init
   terraform apply
   ```

4. **Save ALB URL**:
   ```bash
   terraform output booking_service_alb_url
   # Example: http://conference-booking-booking-alb-dev-123456789.us-east-1.elb.amazonaws.com
   ```

### Step 3: Update Cognito Callback URLs

After Lab 1 is deployed, update Lab 2 Cognito with the ALB URL:

1. **Update `lab2-data/terraform.tfvars`**:
   ```hcl
   cognito_callback_urls = [
     "http://your-alb-url.elb.amazonaws.com/callback",
     "http://localhost:3000/callback"
   ]
   
   cognito_logout_urls = [
     "http://your-alb-url.elb.amazonaws.com",
     "http://localhost:3000"
   ]
   ```

2. **Re-apply Lab 2**:
   ```bash
   cd infrastructure/lab2-data
   terraform apply
   ```

### Step 4: Build & Deploy Services

1. **Login to ECR** (in Lab 1):
   ```bash
   aws ecr get-login-password --region us-east-1 | docker login --username AWS --password-stdin <ECR_URL>
   ```

2. **Build and push booking service**:
   ```bash
   cd booking-service
   docker build -t booking-service .
   docker tag booking-service:latest <ECR_BOOKING_URL>:latest
   docker push <ECR_BOOKING_URL>:latest
   ```

3. **Build and push weather service**:
   ```bash
   cd ../weather-service
   docker build -t weather-service .
   docker tag weather-service:latest <ECR_WEATHER_URL>:latest
   docker push <ECR_WEATHER_URL>:latest
   ```

4. **Update ECS services** (they'll pull new images automatically or force new deployment):
   ```bash
   aws ecs update-service --cluster conference-booking-cluster-dev \
     --service conference-booking-booking-service-dev --force-new-deployment
   
   aws ecs update-service --cluster conference-booking-cluster-dev \
     --service conference-booking-weather-service-dev --force-new-deployment
   ```

## Service Configuration

### Booking Service Environment Variables

```bash
APP_ENV=dev
AWS_DEFAULT_REGION=us-east-1
WEATHER_SERVICE_URL=http://internal-weather-alb.elb.amazonaws.com
DYNAMODB_BOOKINGS_TABLE=conference-booking-bookings-dev
DYNAMODB_ROOMS_TABLE=conference-booking-rooms-dev
COGNITO_USER_POOL_ID=us-east-1_XXXXX
COGNITO_REGION=us-east-1
```

### Weather Service Environment Variables

```bash
APP_ENV=dev
AWS_DEFAULT_REGION=us-east-1
DYNAMODB_WEATHER_TABLE=conference-booking-weather-cache-dev
```

### Laravel JWT Validation (Booking Service)

```php
use Firebase\JWT\JWT;
use Firebase\JWT\JWK;

// Middleware to validate Cognito JWT
public function validateToken($request) {
    $token = $request->bearerToken();
    
    // Get Cognito JWKs
    $jwksUrl = "https://cognito-idp.{region}.amazonaws.com/{userPoolId}/.well-known/jwks.json";
    $jwks = json_decode(file_get_contents($jwksUrl), true);
    
    // Verify token
    $decoded = JWT::decode($token, JWK::parseKeySet($jwks), ['RS256']);
    
    return $decoded;
}
```

## Cross-Account DynamoDB Access

The services in Lab 1 access DynamoDB in Lab 2 using IAM roles. Make sure:

1. **Lab 1 ECS tasks have IAM roles** with DynamoDB permissions (✅ already configured)
2. **DynamoDB tables in Lab 2** are accessible (DynamoDB is regional, same account = works)
3. **For different AWS accounts**, you'd need to set up cross-account IAM trust

Since both Learner Labs are under your account, IAM roles will work automatically!

## Frontend Integration

```javascript
// Frontend login flow
import { CognitoAuth } from 'amazon-cognito-auth-js';

const auth = new CognitoAuth({
  ClientId: 'your-client-id',
  AppWebDomain: 'conference-booking-dev-xxxxx.auth.us-east-1.amazoncognito.com',
  RedirectUriSignIn: 'http://localhost:3000/callback',
  RedirectUriSignOut: 'http://localhost:3000',
  TokenScopesArray: ['email', 'openid', 'profile']
});

// Login
auth.getSession();

// Make authenticated request
const token = auth.getSignInUserSession().getIdToken().getJwtToken();

fetch('http://your-alb-url/api/bookings', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

## Testing

1. **Test Cognito Login**:
   ```bash
   # Open in browser
   https://your-cognito-domain.auth.us-east-1.amazoncognito.com/login?client_id=YOUR_CLIENT_ID&response_type=token&redirect_uri=http://localhost:3000/callback
   ```

2. **Test Booking Service** (after getting token):
   ```bash
   curl -H "Authorization: Bearer YOUR_JWT_TOKEN" \
     http://your-booking-alb-url/api/bookings
   ```

3. **Check ECS logs**:
   ```bash
   aws logs tail /ecs/conference-booking-booking-dev --follow
   aws logs tail /ecs/conference-booking-weather-dev --follow
   ```

## Cost Breakdown

### Lab 1 (Services):
- ECS Fargate: ~$10-15/month (2 tasks running)
- ALB: ~$16/month (2 ALBs)
- ECR: ~$0 (free tier)
- **Total: ~$26-31/month**

### Lab 2 (Data & Auth):
- DynamoDB: ~$0 (free tier)
- Cognito: ~$0 (free tier up to 50,000 MAUs)
- **Total: ~$0-5/month**

### Combined: ~$26-36/month (within budget!)

## Troubleshooting

### ALB returns 401 Unauthorized
- Check Cognito JWT token is valid
- Verify ALB listener has correct Cognito configuration
- Check Cognito callback URLs include ALB URL

### Service can't access DynamoDB
- Verify ECS task role has DynamoDB permissions
- Check table names match environment variables
- Verify both labs in same AWS region

### Weather Service not responding
- Check security group allows traffic from Booking Service
- Verify internal ALB DNS resolves
- Check ECS service is running (desired count > 0)

## Cleanup

```bash
# Destroy Lab 1 first (has dependencies on Lab 2)
cd infrastructure/lab1-backend
terraform destroy

# Then destroy Lab 2
cd ../lab2-data
terraform destroy
```

## Benefits of This Architecture

✅ **Clear separation**: Services in Lab 1, Data in Lab 2
✅ **Cost optimized**: Each lab under $50/month
✅ **Scalable**: Can scale services independently
✅ **Secure**: JWT validation at ALB, IAM for DynamoDB
✅ **Production-like**: Real authentication, load balancing, microservices
