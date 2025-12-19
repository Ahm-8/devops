# Infrastructure

This directory contains Terraform configurations for the Conference Booking System split across two AWS Learner Labs.

## Structure

```
infrastructure/
├── lab1-backend/          # Lab 1: Services & Frontend
│   ├── main.tf           # Provider configuration
│   ├── variables.tf      # Input variables
│   ├── outputs.tf        # Output values
│   ├── vpc.tf            # VPC and networking
│   ├── security_groups.tf # Security groups
│   ├── ecr.tf            # Docker image repositories
│   ├── iam.tf            # IAM roles and policies
│   ├── alb.tf            # Application Load Balancers
│   ├── ecs.tf            # ECS cluster, tasks, and services
│   └── frontend.tf       # S3 + CloudFront for React app
│
├── lab2-data/            # Lab 2: Data & Authentication
│   ├── main.tf           # Provider configuration
│   ├── variables.tf      # Input variables
│   ├── outputs.tf        # Output values
│   ├── dynamodb.tf       # DynamoDB tables
│   └── cognito.tf        # AWS Cognito user pool
│
└── DEPLOYMENT_GUIDE.md   # Complete deployment instructions
```

## Quick Start

### First Time Setup

Run the automated setup script:

```bash
cd ..
./scripts/initial-setup.sh
```

This will:
1. Deploy Lab 2 (Cognito + DynamoDB)
2. Deploy Lab 1 (Services + Frontend)
3. Configure everything automatically
4. Save all outputs

### Manual Deployment

**Deploy Lab 2 first:**

```bash
cd lab2-data
terraform init
terraform apply

# Save outputs
terraform output -json > ../lab2-outputs.json
```

**Then deploy Lab 1:**

```bash
cd ../lab1-backend

# Create terraform.tfvars with values from Lab 2
cat > terraform.tfvars << EOF
cognito_user_pool_arn       = "arn:aws:cognito-idp:..."
cognito_user_pool_client_id = "your-client-id"
cognito_user_pool_domain    = "your-domain"
booking_dynamodb_table_arn  = "arn:aws:dynamodb:..."
weather_dynamodb_table_arn  = "arn:aws:dynamodb:..."
EOF

terraform init
terraform apply
```

## What Gets Deployed

### Lab 1 (Services Layer)
- **VPC** with public subnets (10.1.0.0/16)
- **ECR Repositories** for booking and weather services
- **ECS Cluster** with Fargate tasks
- **Application Load Balancers**:
  - Public ALB for booking service (with Cognito auth)
  - Internal ALB for weather service
- **S3 + CloudFront** for React frontend hosting
- **IAM Roles** with DynamoDB access permissions

### Lab 2 (Data & Auth Layer)
- **AWS Cognito** user pool and client
- **DynamoDB Tables**:
  - `bookings` - Booking records
  - `rooms` - Conference room data
  - `weather-cache` - Weather forecast cache

## Important Notes

### Learner Lab Credentials
- Credentials expire after 4 hours
- Update with: `../scripts/update-credentials.sh`
- See `../docs/LEARNER_LAB_GUIDE.md` for details

### Deployment Order
1. **Always deploy Lab 2 first** (data layer)
2. **Then deploy Lab 1** using Lab 2 outputs
3. **Update Lab 2 Cognito** with Lab 1 CloudFront URL

### Cost Management
- Lab 2: ~$0-5/month (serverless)
- Lab 1: ~$26-31/month (ECS + ALB)
- Stop ECS services when not in use to save budget

## Outputs

After deployment, get important values:

```bash
# Lab 1
cd lab1-backend
terraform output frontend_cloudfront_url
terraform output booking_service_alb_url
terraform output ecr_booking_service_url

# Lab 2
cd ../lab2-data
terraform output cognito_user_pool_id
terraform output cognito_domain
terraform output dynamodb_bookings_table_name
```

## Cleanup

Always destroy Lab 1 before Lab 2:

```bash
# Destroy Lab 1 first
cd lab1-backend
terraform destroy

# Then destroy Lab 2
cd ../lab2-data
terraform destroy
```

## Documentation

- **[DEPLOYMENT_GUIDE.md](./DEPLOYMENT_GUIDE.md)** - Complete deployment guide with architecture diagrams
- **[../docs/LEARNER_LAB_GUIDE.md](../docs/LEARNER_LAB_GUIDE.md)** - Learner Lab tips and workarounds
- **[../.github/CICD_SETUP.md](../.github/CICD_SETUP.md)** - GitHub Actions CI/CD setup

## Support

For issues or questions, check the documentation above or review the Terraform plan before applying:

```bash
terraform plan  # Always review before applying!
```
