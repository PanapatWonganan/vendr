#!/bin/bash
# ===================================================================
# Innobic Nginx Configuration - Phase 4
# Run this as root
# ===================================================================

set -e

echo "=========================================="
echo "🌐 Configuring Nginx"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Error: This script must be run as root"
    echo "   Run: sudo bash 04-configure-nginx.sh"
    exit 1
fi

# Copy Nginx configuration
echo "📝 [1/4] Installing Nginx site configuration..."
cp /var/www/innobic/deployment/nginx-innobic.conf /etc/nginx/sites-available/innobic

# Enable site
echo "🔗 [2/4] Enabling site..."
ln -sf /etc/nginx/sites-available/innobic /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

# Test Nginx configuration
echo "🧪 [3/4] Testing Nginx configuration..."
nginx -t

# Restart Nginx
echo "🔄 [4/4] Restarting Nginx..."
systemctl restart nginx

echo ""
echo "✅ Nginx configuration completed!"
echo ""
echo "🌐 Your site should now be accessible at:"
echo "   http://innobicprocurement.com"
echo ""
echo "📋 Next: Install SSL certificate with Certbot"
echo ""
