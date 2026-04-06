param (
    [Parameter(Mandatory = $true, Position = 0)]
    [string]$Target,
    
    [Parameter(ValueFromRemainingArguments = $true)]
    [string[]]$Command
)

# PJBM "Double-Alias" Shortcut Mapping
# ---------------------------------------------------------
# Mendukung identifikasi berdasarkan Ujung IP (Oktet) 
# MAUPUN Nama Alias (PC/VM/LXC).
# ---------------------------------------------------------

$Hosts = @{
    # PC Hosts
    "10" = "192.168.100.10"; "pc1" = "192.168.100.10"
    "2" = "192.168.100.2"; "pc2" = "192.168.100.2"
    "4" = "192.168.100.4"; "pc4" = "192.168.100.4"
    "5" = "192.168.100.5"; "pc5" = "192.168.100.5"

    # VM/LXC Nodes PC1
    "11" = "192.168.100.11"; "zabbix" = "192.168.100.11"
    "12" = "192.168.100.12"; "n8n" = "192.168.100.12"
    "14" = "192.168.100.14"; "open" = "192.168.100.14"
    "15" = "192.168.100.15"; "pihole" = "192.168.100.15"
    "16" = "192.168.100.16"; "hprod" = "192.168.100.16"
    "17" = "192.168.100.17"; "hsatg" = "192.168.100.17"
    "18" = "192.168.100.18"; "hstag" = "192.168.100.18"

    # VM Nodes PC5
    "51" = "192.168.100.51"; "ai" = "192.168.100.51"
    "52" = "192.168.100.52"; "agent" = "192.168.100.52"
}

# Identity File (Standard PJB Key)
$Key = "d:\KOMCSPJBM\VMproxmox\PC Proxmox key All"

# Resolve IP Logic
$IP = $null
$LowerTarget = $Target.ToLower()

if ($Hosts.ContainsKey($LowerTarget)) {
    $IP = $Hosts[$LowerTarget]
}
elseif ($Target -match '^\d+$') {
    # Fallback for ANY octet (e.g. ./pjb 20 -> 192.168.100.20)
    $IP = "192.168.100.$Target"
}
else {
    # Full IP or Manual Alias
    $IP = $Target
}

# Command Execution
$SshArgs = @("-i", "`"$Key`"", "-o", "StrictHostKeyChecking=no", "root@$IP")

if ($null -ne $Command -and $Command.Count -gt 0) {
    Write-Host ">>> Executing on ${IP}: $Command" -ForegroundColor Cyan
    ssh $SshArgs $Command
}
else {
    Write-Host ">>> Connecting to ${IP}..." -ForegroundColor Green
    ssh $SshArgs
}
