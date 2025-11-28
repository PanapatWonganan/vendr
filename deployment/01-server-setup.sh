#!/bin/bash
# ===================================================================
# Innobic Server Setup Script - Phase 1
# สำหรับ: Ubuntu 22.04 LTS บน Vultr
# ===================================================================

set -e  # Exit on error

echo "=========================================="
echo "🚀 Innobic Production Server Setup"
echo "=========================================="
echo ""

# Update system packages
echo "📦 [1/8] Updating system packages..."
apt-get update
DEBIAN_FRONTEND=noninteractive apt-get upgrade -y

# Set timezone to Bangkok
echo "🕐 [2/8] Setting timezone to Asia/Bangkok..."
timedatectl set-timezone Asia/Bangkok

# Create deployment user
echo "👤 [3/8] Creating deployment user 'innobic'..."
if id "innobic" &>/dev/null; then
    echo "   User 'innobic' already exists"
else
    useradd -m -s /bin/bash innobic
    usermod -aG sudo innobic
    echo "   User 'innobic' created successfully"
fi

# Setup SSH key authentication (you'll need to add your public key)
echo "🔑 [4/8] Setting up SSH directory for deployment user..."
mkdir -p /home/innobic/.ssh
chmod 700 /home/innobic/.ssh
chown innobic:innobic /home/innobic/.ssh

echo ""
echo "⚠️  IMPORTANT: Add your SSH public key to /home/innobic/.ssh/authorized_keys"
echo ""

# Install essential packages
echo "📦 [5/8] Installing essential packages..."
apt-get install -y \
    software-properties-common \
    curl \
    wget \
    git \
    unzip \
    ufw \
    fail2ban \
    htop \
    vim \
    ca-certificates \
    apt-transport-https

# Setup Firewall
echo "🔥 [6/8] Setting up UFW firewall..."
ufw --force reset
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp   # SSH
ufw allow 80/tcp   # HTTP
ufw allow 443/tcp  # HTTPS
ufw --force enable

# Setup Fail2ban
echo "🛡️  [7/8] Setting up Fail2ban..."
systemctl enable fail2ban
systemctl start fail2ban

# Create application directory
echo "📁 [8/8] Creating application directory..."
mkdir -p /var/www/innobic
chown innobic:innobic /var/www/innobic

echo ""
echo "✅ Server basic setup completed!"
echo ""
echo "📋 Next steps:"
echo "   1. Add your SSH public key to /home/innobic/.ssh/authorized_keys"
echo "   2. Test SSH login as 'innobic' user"
echo "   3. Run the next script: 02-install-software.sh"
echo ""
