# PJBM Mass Maintenance Script
# Performs: apt update && apt upgrade -y
# ---------------------------------------------------------

$Targets = @(
    "pc1",  # PC1 (10)
    "pc2",  # PC2 (2)
    "pc4",  # PC4 (4)
    "pc5",  # PC5 (5)
    "17",   # Hestia-Prod
    "18",   # Hestia-Stag
    "51",   # AI-Core
    "52"    # Agent-Box
)

Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "   PJBM CLUSTER AUTO-UPGRADE SYSTEM       " -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan

foreach ($Node in $Targets) {
    Write-Host "`n[ RUNNING ] Maintaining node: $Node" -ForegroundColor Yellow
    powershell -ExecutionPolicy Bypass -File "./pjb.ps1" $Node "apt update && apt upgrade -y"
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "[ SUCCESS ] Node $Node is up to date." -ForegroundColor Green
    } else {
        Write-Host "[ FAILED  ] Error maintaining node $Node." -ForegroundColor Red
    }
}

Write-Host "`n==========================================" -ForegroundColor Cyan
Write-Host "   ALL TARGETS PROCESSED SUCCESSFULLY     " -ForegroundColor Cyan
Write-Host "==========================================" -ForegroundColor Cyan
