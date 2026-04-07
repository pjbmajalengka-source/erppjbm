#!/bin/bash
# ERP-PJBM Automated Update Script
APP_DIR="/home/pjbstag/web/testapp.pjb.my.id/app"

if [ ! -d "$APP_DIR" ]; then
    echo "ERROR: Folder $APP_DIR tidak ditemukan!"
    exit 1
fi

cd "$APP_DIR" || exit

echo "--- Melakukan Sinkronisasi dari GitHub ---"
git fetch origin
git reset --hard origin/main

echo "--- Perbaikan Izin File ---"
chown -R pjbstag:pjbstag .
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type d -exec chmod 775 {} \;

echo "--- Selesai Update ---"
