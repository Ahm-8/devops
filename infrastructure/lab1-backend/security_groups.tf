# ALB Security Group - allows HTTPS from internet
resource "aws_security_group" "alb" {
  name_prefix = "${var.project_name}-alb-sg-${var.environment}"
  description = "Security group for Application Load Balancers"
  vpc_id      = aws_vpc.main.id

  ingress {
    description = "HTTP from anywhere"
    from_port   = 80
    to_port     = 80
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  ingress {
    description = "HTTPS from anywhere"
    from_port   = 443
    to_port     = 443
    protocol    = "tcp"
    cidr_blocks = ["0.0.0.0/0"]
  }

  egress {
    description = "Allow all outbound traffic"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "${var.project_name}-alb-sg-${var.environment}"
  }
}

# Booking Service Security Group
resource "aws_security_group" "booking_service" {
  name_prefix = "${var.project_name}-booking-sg-${var.environment}"
  description = "Security group for Booking Service ECS tasks"
  vpc_id      = aws_vpc.main.id

  ingress {
    description     = "Allow traffic from ALB"
    from_port       = var.booking_service_port
    to_port         = var.booking_service_port
    protocol        = "tcp"
    security_groups = [aws_security_group.alb.id]
  }

  egress {
    description = "Allow all outbound traffic"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "${var.project_name}-booking-service-sg-${var.environment}"
  }
}

# Weather Service Security Group
resource "aws_security_group" "weather_service" {
  name_prefix = "${var.project_name}-weather-sg-${var.environment}"
  description = "Security group for Weather Service ECS tasks"
  vpc_id      = aws_vpc.main.id

  ingress {
    description     = "Allow traffic from ALB"
    from_port       = var.weather_service_port
    to_port         = var.weather_service_port
    protocol        = "tcp"
    security_groups = [aws_security_group.alb.id]
  }

  ingress {
    description     = "Allow traffic from Booking Service"
    from_port       = var.weather_service_port
    to_port         = var.weather_service_port
    protocol        = "tcp"
    security_groups = [aws_security_group.booking_service.id]
  }

  egress {
    description = "Allow all outbound traffic"
    from_port   = 0
    to_port     = 0
    protocol    = "-1"
    cidr_blocks = ["0.0.0.0/0"]
  }

  tags = {
    Name = "${var.project_name}-weather-service-sg-${var.environment}"
  }
}
