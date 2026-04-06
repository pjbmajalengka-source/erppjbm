# ERP-PJBM Staging Sync Automator
# ---------------------------------------------------------
$SourcePath = "D:\erp-pjbm\"
$TargetIP   = "192.168.100.17"
$TargetUser = "root"
$TargetPath = "/home/erpuser/web/testgaji.pjb.my.id/public_html/"
$SshKey     = "d:\KOMCSPJBM\VMproxmox\PC Proxmox key All"

Write-Host ">>> Synchronizing files to STAGING ($TargetIP)..." -ForegroundColor Cyan

# Menggunakan SCP untuk pengiriman masal (Rekomendasi untuk Windows-to-Linux tanpa rsync)
scp -r -i "$SshKey" -o "StrictHostKeyChecking=no" "$SourcePath*" "${TargetUser}@${TargetIP}:${TargetPath}"

if ($LASTEXITCODE -eq 0) {
    Write-Host ">>> Success! Files are uploaded to Staging." -ForegroundColor Green
}
else {
    Write-Host ">>> Failed! Please check your connection to VM 117." -ForegroundColor Red
}
