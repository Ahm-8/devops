# ECS Cluster
resource "aws_ecs_cluster" "main" {
  name = "${var.project_name}-cluster-${var.environment}"

  setting {
    name  = "containerInsights"
    value = "enabled"
  }

  tags = {
    Name = "${var.project_name}-ecs-cluster-${var.environment}"
  }
}

# CloudWatch Log Group for ECS
resource "aws_cloudwatch_log_group" "booking_service" {
  name              = "/ecs/${var.project_name}-booking-${var.environment}"
  retention_in_days = 7

  tags = {
    Name = "${var.project_name}-booking-logs-${var.environment}"
  }
}

resource "aws_cloudwatch_log_group" "weather_service" {
  name              = "/ecs/${var.project_name}-weather-${var.environment}"
  retention_in_days = 7

  tags = {
    Name = "${var.project_name}-weather-logs-${var.environment}"
  }
}

# ECS Task Definition for Booking Service
resource "aws_ecs_task_definition" "booking_service" {
  family                   = "${var.project_name}-booking-${var.environment}"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = "256"
  memory                   = "512"
  execution_role_arn       = "arn:aws:iam::250216242055:role/LabRole"
  task_role_arn            = "arn:aws:iam::250216242055:role/LabRole"

  container_definitions = jsonencode([
    {
      name      = "booking-service"
      image     = "${aws_ecr_repository.booking_service.repository_url}:latest"
      essential = true

      portMappings = [
        {
          containerPort = var.booking_service_port
          protocol      = "tcp"
        }
      ]

      environment = [
        {
          name  = "APP_ENV"
          value = var.environment
        },
        {
          name  = "WEATHER_SERVICE_URL"
          value = "http://${aws_lb.weather_service.dns_name}"
        },
        {
          name  = "AWS_DEFAULT_REGION"
          value = var.aws_region
        },
        {
          name  = "DYNAMODB_BOOKINGS_TABLE"
          value = aws_dynamodb_table.bookings.name
        },
        {
          name  = "DYNAMODB_ROOMS_TABLE"
          value = aws_dynamodb_table.rooms.name
        },
        {
          name  = "DYNAMODB_WEATHER_TABLE"
          value = aws_dynamodb_table.weather.name
        },
        {
          name  = "COGNITO_USER_POOL_ID"
          value = var.enable_cognito ? aws_cognito_user_pool.main[0].id : ""
        },
        {
          name  = "CACHE_STORE"
          value = "file"
        }
      ]

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.booking_service.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "ecs"
        }
      }

      healthCheck = {
        command     = ["CMD-SHELL", "curl -f http://localhost:${var.booking_service_port}/health || exit 1"]
        interval    = 30
        timeout     = 5
        retries     = 3
        startPeriod = 60
      }
    }
  ])

  tags = {
    Name = "${var.project_name}-booking-task-${var.environment}"
  }
}

# ECS Task Definition for Weather Service
resource "aws_ecs_task_definition" "weather_service" {
  family                   = "${var.project_name}-weather-${var.environment}"
  requires_compatibilities = ["FARGATE"]
  network_mode             = "awsvpc"
  cpu                      = "256"
  memory                   = "512"
  execution_role_arn       = "arn:aws:iam::250216242055:role/LabRole"
  task_role_arn            = "arn:aws:iam::250216242055:role/LabRole"

  container_definitions = jsonencode([
    {
      name      = "weather-service"
      image     = "${aws_ecr_repository.weather_service.repository_url}:latest"
      essential = true

      portMappings = [
        {
          containerPort = var.weather_service_port
          protocol      = "tcp"
        }
      ]

      environment = [
        {
          name  = "APP_ENV"
          value = var.environment
        },
        {
          name  = "AWS_DEFAULT_REGION"
          value = var.aws_region
        },
        {
          name  = "DYNAMODB_WEATHER_TABLE"
          value = aws_dynamodb_table.weather.name
        },
        {
          name  = "COGNITO_USER_POOL_ID"
          value = var.enable_cognito ? aws_cognito_user_pool.main[0].id : ""
        },
        {
          name  = "CACHE_STORE"
          value = "file"
        }
      ]

      logConfiguration = {
        logDriver = "awslogs"
        options = {
          "awslogs-group"         = aws_cloudwatch_log_group.weather_service.name
          "awslogs-region"        = var.aws_region
          "awslogs-stream-prefix" = "ecs"
        }
      }

      healthCheck = {
        command     = ["CMD-SHELL", "curl -f http://localhost:${var.weather_service_port}/health || exit 1"]
        interval    = 30
        timeout     = 5
        retries     = 3
        startPeriod = 60
      }
    }
  ])

  tags = {
    Name = "${var.project_name}-weather-task-${var.environment}"
  }
}

# ECS Service for Booking Service
resource "aws_ecs_service" "booking_service" {
  name            = "${var.project_name}-booking-service-${var.environment}"
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.booking_service.arn
  desired_count   = 1
  launch_type     = "FARGATE"

  network_configuration {
    subnets          = aws_subnet.public[*].id
    security_groups  = [aws_security_group.booking_service.id]
    assign_public_ip = true
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.booking_service.arn
    container_name   = "booking-service"
    container_port   = var.booking_service_port
  }

  depends_on = [
    aws_lb_listener.booking_service_http
  ]

  tags = {
    Name = "${var.project_name}-booking-service-${var.environment}"
  }
}

# ECS Service for Weather Service
resource "aws_ecs_service" "weather_service" {
  name            = "${var.project_name}-weather-service-${var.environment}"
  cluster         = aws_ecs_cluster.main.id
  task_definition = aws_ecs_task_definition.weather_service.arn
  desired_count   = 1
  launch_type     = "FARGATE"

  network_configuration {
    subnets          = aws_subnet.public[*].id
    security_groups  = [aws_security_group.weather_service.id]
    assign_public_ip = true
  }

  load_balancer {
    target_group_arn = aws_lb_target_group.weather_service.arn
    container_name   = "weather-service"
    container_port   = var.weather_service_port
  }

  depends_on = [aws_lb_listener.weather_service_http]

  tags = {
    Name = "${var.project_name}-weather-service-${var.environment}"
  }
}
