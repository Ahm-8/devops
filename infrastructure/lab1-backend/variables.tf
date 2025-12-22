variable "aws_region" {
  description = "AWS region for Lab 1"
  type        = string
  default     = "us-east-1"
}

variable "environment" {
  description = "Environment name"
  type        = string
  default     = "dev"
}

variable "project_name" {
  description = "Project name"
  type        = string
  default     = "conference-booking"
}

variable "vpc_cidr" {
  description = "CIDR block for VPC"
  type        = string
  default     = "10.1.0.0/16"  # Different from Lab 2
}

variable "availability_zones" {
  description = "Availability zones"
  type        = list(string)
  default     = ["us-east-1a", "us-east-1b"]
}

variable "booking_service_port" {
  description = "Port for booking service"
  type        = number
  default     = 80
}

variable "weather_service_port" {
  description = "Port for weather service"
  type        = number
  default     = 80
}

variable "enable_cognito" {
  description = "Enable AWS Cognito (might not be available in Learner Lab)"
  type        = bool
  default     = true
}

# Cognito Callback URLs (update with CloudFront URL after deployment)
variable "cognito_callback_urls" {
  description = "Callback URLs for Cognito (CloudFront URL)"
  type        = list(string)
  default     = [
    "http://localhost:3000/callback",
    "http://localhost:3000"
  ]
}

variable "cognito_logout_urls" {
  description = "Logout URLs for Cognito"
  type        = list(string)
  default     = [
    "http://localhost:3000",
    "http://localhost:3000/login"
  ]
}
