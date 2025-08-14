#!/bin/bash

# AutoKosten Calculator - Deployment Script
# Author: Richard Surie
# Version: 1.0.0
# Description: Automated deployment to GitHub and Hostinger

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Configuration
PROJECT_NAME="autovandezaakofprive"
DEV_PATH="/Users/richardsurie/Documents/Development/Projects/autovandezaakofprive"
GITHUB_PATH="/Users/richardsurie/Documents/Development/GitHub/autovandezaakofprive"
LIVE_URL="https://www.pianomanontour.nl/autovandezaakofprive"

# Function to print colored messages
print_message() {
    echo -e "${2}${1}${NC}"
}

# Function to check if directory exists
check_directory() {
    if [ ! -d "$1" ]; then
        print_message "❌ Directory not found: $1" "$RED"
        exit 1
    fi
}

# Start deployment
clear
print_message "🚀 AutoKosten Calculator - Deployment Script" "$GREEN"
print_message "============================================" "$GREEN"
echo ""

# Check if commit message is provided
if [ -z "$1" ]; then
    print_message "❌ Please provide a commit message" "$RED"
    print_message "Usage: ./deploy.sh \"Your commit message\"" "$YELLOW"
    exit 1
fi

COMMIT_MESSAGE="$1"

# Step 1: Verify directories
print_message "📁 Step 1: Verifying directories..." "$YELLOW"
check_directory "$DEV_PATH"
check_directory "$GITHUB_PATH"
print_message "✅ Directories verified" "$GREEN"
echo ""

# Step 2: Sync from Development to GitHub
print_message "📂 Step 2: Syncing files from Development to GitHub..." "$YELLOW"

# Files to sync (excluding system files and archives)
rsync -av --delete \
    --exclude='.DS_Store' \
    --exclude='*.swp' \
    --exclude='*.swo' \
    --exclude='test-local.php' \
    --exclude='ARCHIVE_CHAT_*.md' \
    --exclude='PROJECT_STATUS_*.md' \
    --exclude='.git' \
    "$DEV_PATH/" "$GITHUB_PATH/"

if [ $? -eq 0 ]; then
    print_message "✅ Files synced successfully" "$GREEN"
else
    print_message "❌ Error syncing files" "$RED"
    exit 1
fi
echo ""

# Step 3: Git operations
print_message "🔄 Step 3: Performing Git operations..." "$YELLOW"
cd "$GITHUB_PATH"

# Check git status
git status --short

# Add all changes
git add -A

# Commit with provided message
git commit -m "$COMMIT_MESSAGE"

if [ $? -eq 0 ]; then
    print_message "✅ Changes committed" "$GREEN"
else
    print_message "⚠️  No changes to commit" "$YELLOW"
fi

# Step 4: Push to GitHub
print_message "⬆️  Step 4: Pushing to GitHub..." "$YELLOW"
git push origin main

if [ $? -eq 0 ]; then
    print_message "✅ Successfully pushed to GitHub" "$GREEN"
else
    print_message "❌ Error pushing to GitHub" "$RED"
    print_message "Try: git push --set-upstream origin main" "$YELLOW"
    exit 1
fi
echo ""

# Step 5: Create deployment log
print_message "📝 Step 5: Creating deployment log..." "$YELLOW"
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
LOG_FILE="$DEV_PATH/deployments.log"

echo "[$TIMESTAMP] Deployed: $COMMIT_MESSAGE" >> "$LOG_FILE"
print_message "✅ Deployment logged" "$GREEN"
echo ""

# Step 6: Verify deployment
print_message "🌐 Step 6: Deployment Summary" "$GREEN"
print_message "============================================" "$GREEN"
print_message "✅ Files synced to GitHub repository" "$GREEN"
print_message "✅ Changes pushed to GitHub" "$GREEN"
print_message "✅ Hostinger will auto-deploy from GitHub" "$GREEN"
echo ""
print_message "📱 Live site will be updated at:" "$YELLOW"
print_message "$LIVE_URL" "$GREEN"
echo ""
print_message "⏱️  Note: Hostinger auto-deployment may take 1-5 minutes" "$YELLOW"
echo ""

# Optional: Open live site in browser
read -p "Do you want to open the live site in your browser? (y/n) " -n 1 -r
echo ""
if [[ $REPLY =~ ^[Yy]$ ]]; then
    open "$LIVE_URL"
    print_message "🌐 Opening live site..." "$GREEN"
fi

print_message "🎉 Deployment complete!" "$GREEN"
echo ""

# Show recent commits
print_message "📜 Recent commits:" "$YELLOW"
git log --oneline -5

exit 0
