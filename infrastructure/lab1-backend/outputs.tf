output "vpc_id" {
  description = "VPC ID for Lab 1"
  value       = aws_vpc.main.id
}

output "vpc_cidr" {
  description = "VPC CIDR for Lab 1"
  value       = aws_vpc.main.cidr_block
}

output "booking_service_alb_url" {
  description = "Booking Service ALB DNS (public - for frontend)"
  value       = "http://${aws_lb.booking_service.dns_name}"
}

output "weather_service_alb_url" {
  description = "Weather Service ALB DNS (internal - for booking service)"
  value       = "http://${aws_lb.weather_service.dns_name}"
}

output "ecr_booking_service_url" {
  description = "ECR repository URL for booking service"
  value       = aws_ecr_repository.booking_service.repository_url
}

output "ecr_weather_service_url" {
  description = "ECR repository URL for weather service"
  value       = aws_ecr_repository.weather_service.repository_url
}

output "ecs_cluster_name" {
  description = "ECS cluster name"
  value       = aws_ecs_cluster.main.name
}

output "public_subnet_ids" {
  description = "Public subnet IDs"
  value       = aws_subnet.public[*].id
}

output "frontend_s3_bucket" {
  description = "S3 bucket name for frontend"
  value       = aws_s3_bucket.frontend.bucket
}

output "frontend_website_url" {
  description = "S3 website URL for frontend"
  value       = "http://${aws_s3_bucket_website_configuration.frontend.website_endpoint}"
}

# Cognito Outputs
output "cognito_user_pool_id" {
  description = "Cognito User Pool ID (for frontend)"
  value       = var.enable_cognito ? aws_cognito_user_pool.main[0].id : "Cognito disabled"
}

output "cognito_user_pool_arn" {
  description = "Cognito User Pool ARN (for ALB)"
  value       = var.enable_cognito ? aws_cognito_user_pool.main[0].arn : "Cognito disabled"
}

output "cognito_user_pool_client_id" {
  description = "Cognito User Pool Client ID (for frontend and ALB)"
  value       = var.enable_cognito ? aws_cognito_user_pool_client.main[0].id : "Cognito disabled"
  sensitive   = true
}

output "cognito_domain" {
  description = "Cognito domain for hosted UI"
  value       = var.enable_cognito ? aws_cognito_user_pool_domain.main[0].domain : "Cognito disabled"
}

output "cognito_hosted_ui_url" {
  description = "Cognito Hosted UI URL (for frontend login)"
  value       = var.enable_cognito ? "https://${aws_cognito_user_pool_domain.main[0].domain}.auth.${var.aws_region}.amazoncognito.com/login" : "Cognito disabled"
}

# DynamoDB Outputs
output "dynamodb_bookings_table_name" {
  description = "DynamoDB bookings table name"
  value       = aws_dynamodb_table.bookings.name
}

output "dynamodb_bookings_table_arn" {
  description = "DynamoDB bookings table ARN"
  value       = aws_dynamodb_table.bookings.arn
}

output "dynamodb_rooms_table_name" {
  description = "DynamoDB rooms table name"
  value       = aws_dynamodb_table.rooms.name
}

output "dynamodb_rooms_table_arn" {
  description = "DynamoDB rooms table ARN"
  value       = aws_dynamodb_table.rooms.arn
}

output "dynamodb_weather_table_name" {
  description = "DynamoDB weather table name"
  value       = aws_dynamodb_table.weather.name
}

output "dynamodb_weather_table_arn" {
  description = "DynamoDB weather table ARN"
  value       = aws_dynamodb_table.weather.arn
}
