#!/bin/bash
# Azure App Service Linux Custom Startup Script

echo "Applying custom Nginx configuration..."

# Copy custom Nginx configuration over the Azure default ones
cp /home/site/wwwroot/nginx-custom.conf /etc/nginx/sites-available/default
cp /home/site/wwwroot/nginx-custom.conf /etc/nginx/sites-enabled/default

# Reload Nginx so the new rules (404 pages, redirects) take effect
service nginx reload

echo "Nginx reloaded successfully!"
