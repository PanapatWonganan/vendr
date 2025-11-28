#!/bin/bash
# ===================================================================
# Innobic SSL Certificate Installation - Phase 5
# Run this as root
# ===================================================================

set -e

echo "=========================================="
echo "🔒 Installing SSL Certificate"
echo "=========================================="
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then
    echo "❌ Error: This script must be run as root"
    exit 1
fi

# Install Certbot
echo "📦 [1/3] Installing Certbot..."
apt-get update
apt-get install -y certbot python3-certbot-nginx

# Obtain SSL certificate
echo "🔐 [2/3] Obtaining SSL certificate..."
echo "   Domain: innobicprocurement.com"
echo ""
certbot --nginx -d innobicprocurement.com -d www.innobicprocurement.com --non-interactive --agree-tos --email admin@innobicprocurement.com --redirect

# Test auto-renewal
echo "🔄 [3/3] Testing certificate auto-renewal..."
certbot renew --dry-run

echo ""
echo "✅ SSL certificate installed successfully!"
echo ""
echo "🌐 Your site is now accessible at:"
echo "   https://innobicprocurement.com"
echo ""
echo "📋 Certificate will auto-renew every 60 days"
echo ""
echo "🔐 Cloudflare SSL/TLS setting:"
echo "   Change to: Full (strict)"
echo ""
