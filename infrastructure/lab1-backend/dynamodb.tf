resource "aws_dynamodb_table" "bookings" {
  name         = "${var.project_name}-bookings-${var.environment}"
  billing_mode = "PAY_PER_REQUEST"

  hash_key  = "locationDate"
  range_key = "roomName"

  attribute {
    name = "locationDate"
    type = "S"
  }

  attribute {
    name = "roomName"
    type = "S"
  }

  tags = {
    Service = "booking-service"
  }
}

resource "aws_dynamodb_table" "rooms" {
  name         = "${var.project_name}-rooms-${var.environment}"
  billing_mode = "PAY_PER_REQUEST"

  hash_key  = "location"
  range_key = "roomName"

  attribute {
    name = "location"
    type = "S"
  }

  attribute {
    name = "roomName"
    type = "S"
  }

  tags = {
    Service = "booking-service"
  }
}

resource "aws_dynamodb_table" "weather" {
  name         = "${var.project_name}-weather-${var.environment}"
  billing_mode = "PAY_PER_REQUEST"

  hash_key  = "location"
  range_key = "date"

  attribute {
    name = "location"
    type = "S"
  }

  attribute {
    name = "date"
    type = "S"
  }
}
