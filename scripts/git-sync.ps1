# ERP-PJBM GitHub Sync Wrapper
# ---------------------------------------------------------
param (
    [Parameter(Position = 0)]
    [string]$Message = "Update from Antigravity AI"
)

Write-Host ">>> Synchronizing to GitHub..." -ForegroundColor Cyan

# Menjalankan rangkaian perintah Git
git add .
git commit -m "$Message"
git push origin main # Atau ganti master/branch lain

if ($LASTEXITCODE -eq 0) {
    Write-Host ">>> Success! Changes are pushed to GitHub." -ForegroundColor Green
}
else {
    Write-Host ">>> Failed! Please check your Git repository and remote." -ForegroundColor Red
}
