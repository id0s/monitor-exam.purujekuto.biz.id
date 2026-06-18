#!/bin/bash
###################################
# Prerequisites

# Get version of RHEL
source /etc/os-release
if [ ${VERSION_ID%.*} -ge 8 ]
then majorver=8
elif [ ${VERSION_ID%.*} -ge 9 ]
then majorver=9
fi

# Download the Microsoft RedHat repository package
curl -sSL -O https://packages.microsoft.com/config/rhel/$majorver/packages-microsoft-prod.rpm

# Register the Microsoft RedHat repository
sudo rpm -i packages-microsoft-prod.rpm

# Delete the downloaded package after installing
rm packages-microsoft-prod.rpm

# Update package index files
sudo dnf update
# Install PowerShell
sudo dnf install powershell -y
