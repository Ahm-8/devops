# ECR Repository for Booking Service
resource "aws_ecr_repository" "booking_service" {
  name                 = "${var.project_name}-booking-service"
  image_tag_mutability = "MUTABLE"

  image_scanning_configuration {
    scan_on_push = true
  }

  tags = {
    Name = "${var.project_name}-booking-service-ecr"
  }
}

# ECR Repository for Weather Service
resource "aws_ecr_repository" "weather_service" {
  name                 = "${var.project_name}-weather-service"
  image_tag_mutability = "MUTABLE"

  image_scanning_configuration {
    scan_on_push = true
  }

  tags = {
    Name = "${var.project_name}-weather-service-ecr"
  }
}

# Lifecycle policy to keep only recent images
resource "aws_ecr_lifecycle_policy" "booking_service" {
  repository = aws_ecr_repository.booking_service.name

  policy = jsonencode({
    rules = [{
      rulePriority = 1
      description  = "Keep last 5 images"
      selection = {
        tagStatus   = "any"
        countType   = "imageCountMoreThan"
        countNumber = 5
      }
      action = {
        type = "expire"
      }
    }]
  })
}

resource "aws_ecr_lifecycle_policy" "weather_service" {
  repository = aws_ecr_repository.weather_service.name

  policy = jsonencode({
    rules = [{
      rulePriority = 1
      description  = "Keep last 5 images"
      selection = {
        tagStatus   = "any"
        countType   = "imageCountMoreThan"
        countNumber = 5
      }
      action = {
        type = "expire"
      }
    }]
  })
}
