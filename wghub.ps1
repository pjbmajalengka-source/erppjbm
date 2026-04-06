param (
    [Parameter(Position = 0)]
    [string]$Command
)

# WGHUB Oracle VPS Configuration (Direct Corrected)
# ---------------------------------------------------------
$WghubIP  = "10.40.0.1"
$WghubKey = "D:\Dokumen\oracleVM1key\keyprivate1.key"

# SSH Arguments
$SshArgs = @(
    "-i", "`"$WghubKey`"", 
    "-o", "StrictHostKeyChecking=no", 
    "root@$WghubIP"
)

if ($null -ne $Command -and $Command -ne "") {
    Write-Host ">>> Executing on WGHUB (${WghubIP}): $Command" -ForegroundColor Cyan
    ssh $SshArgs $Command
}
else {
    Write-Host ">>> Connecting to WGHUB (${WghubIP})..." -ForegroundColor Green
    ssh $SshArgs
}
