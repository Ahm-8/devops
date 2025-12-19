# Application Load Balancer for Booking Service
resource "aws_lb" "booking_service" {
  name               = "booking-alb-${var.environment}"
  internal           = false
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb.id]
  subnets            = aws_subnet.public[*].id

  enable_deletion_protection = false
  enable_http2               = true

  tags = {
    Name = "${var.project_name}-booking-alb-${var.environment}"
  }
}

# Target Group for Booking Service
resource "aws_lb_target_group" "booking_service" {
  name        = "booking-tg-${var.environment}"
  port        = var.booking_service_port
  protocol    = "HTTP"
  vpc_id      = aws_vpc.main.id
  target_type = "ip"

  health_check {
    enabled             = true
    healthy_threshold   = 2
    unhealthy_threshold = 3
    timeout             = 5
    interval            = 30
    path                = "/health"
    protocol            = "HTTP"
    matcher             = "200"
  }

  deregistration_delay = 30

  tags = {
    Name = "${var.project_name}-booking-tg-${var.environment}"
  }
}

# HTTP Listener for Booking Service (without Cognito - HTTP doesn't support it)
resource "aws_lb_listener" "booking_service_http" {
  load_balancer_arn = aws_lb.booking_service.arn
  port              = "80"
  protocol          = "HTTP"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.booking_service.arn
  }
}

# Application Load Balancer for Weather Service
resource "aws_lb" "weather_service" {
  name               = "weather-alb-${var.environment}"
  internal           = true # Internal - only accessible from booking service
  load_balancer_type = "application"
  security_groups    = [aws_security_group.alb.id]
  subnets            = aws_subnet.public[*].id

  enable_deletion_protection = false
  enable_http2               = true

  tags = {
    Name = "${var.project_name}-weather-alb-${var.environment}"
  }
}

# Target Group for Weather Service
resource "aws_lb_target_group" "weather_service" {
  name        = "weather-tg-${var.environment}"
  port        = var.weather_service_port
  protocol    = "HTTP"
  vpc_id      = aws_vpc.main.id
  target_type = "ip"

  health_check {
    enabled             = true
    healthy_threshold   = 2
    unhealthy_threshold = 3
    timeout             = 5
    interval            = 30
    path                = "/health"
    protocol            = "HTTP"
    matcher             = "200"
  }

  deregistration_delay = 30

  tags = {
    Name = "${var.project_name}-weather-tg-${var.environment}"
  }
}

# HTTP Listener for Weather Service (internal, no auth needed)
resource "aws_lb_listener" "weather_service_http" {
  load_balancer_arn = aws_lb.weather_service.arn
  port              = "80"
  protocol          = "HTTP"

  default_action {
    type             = "forward"
    target_group_arn = aws_lb_target_group.weather_service.arn
  }
}
