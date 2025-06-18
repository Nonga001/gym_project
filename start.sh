#!/bin/bash

# Build and start the containers
docker-compose up --build -d

# Wait for MongoDB to be ready
echo "Waiting for MongoDB to be ready..."
sleep 10

# Show the logs
docker-compose logs -f 