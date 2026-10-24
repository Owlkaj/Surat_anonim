# ==============================================================================
# Surat Anonim - Server Manager (PowerShell Core)
# Mendukung: Start, Stop, Toggle, Status, Restart
# ==============================================================================

param (
    [string]$Action = "toggle"
)

$Host.UI.RawUI.WindowTitle = "Surat Anonim - Server Manager"
$ProjectRoot = $PSScriptRoot
$LogDir = Join-Path $ProjectRoot "storage\logs"
if (-not (Test-Path $LogDir)) { New-Item -ItemType Directory -Path $LogDir -Force | Out-Null }

$CloudflaredExe = Join-Path $ProjectRoot "cloudflared.exe"
$CloudflaredLog = Join-Path $LogDir "cloudflared.log"
$ServeLog = Join-Path $LogDir "laravel_server.log"
$ServeErrLog = Join-Path $LogDir "laravel_server_err.log"
$WorkerLog = Join-Path $LogDir "queue_worker.log"
$WorkerErrLog = Join-Path $LogDir "queue_worker_err.log"
$PidFile = Join-Path $ProjectRoot "storage\framework\server_pids.json"

function Write-Banner {
    Clear-Host
    Write-Host "================================================================" -ForegroundColor Cyan
    Write-Host "         SURAT ANONIM - PUSAT KONTROL SERVER                    " -ForegroundColor White
    Write-Host "================================================================" -ForegroundColor Cyan
    Write-Host ""
}

function Find-PhpExecutable {
    $php = Get-Command "php" -ErrorAction SilentlyContinue
    if ($php) { return $php.Source }
    
    $commonPaths = @(
        "C:\xampp\php\php.exe",
        "C:\php\php.exe",
        "C:\laragon\bin\php\current\php.exe"
    )
    foreach ($p in $commonPaths) {
        if (Test-Path $p) { return $p }
    }
    return $null
}

function Get-RunningServerProcesses {
    $procs = @()
    
    # 1. Periksa PID dari file jika ada
    if (Test-Path $PidFile) {
        try {
            $saved = Get-Content $PidFile -Raw | ConvertFrom-Json
            foreach ($key in @('serve_pid', 'worker_pid', 'cloudflared_pid')) {
                if ($saved.$key) {
                    $p = Get-Process -Id $saved.$key -ErrorAction SilentlyContinue
                    if ($p -and ($procs.Id -notcontains $p.Id)) {
                        $procs += $p
                    }
                }
            }
        } catch {}
    }
    
    # 2. Periksa proses aktif terkait project ini lewat WMI
    $wmi = Get-CimInstance Win32_Process -ErrorAction SilentlyContinue | Where-Object {
        ($_.Name -eq "cloudflared.exe" -and ($_.ExecutablePath -like "*$ProjectRoot*" -or $_.CommandLine -like "*$ProjectRoot*")) -or
        ($_.Name -eq "php.exe" -and $_.CommandLine -like "*$ProjectRoot*" -and ($_.CommandLine -like "*artisan serve*" -or $_.CommandLine -like "*artisan queue:work*"))
    }
    
    foreach ($item in $wmi) {
        $p = Get-Process -Id $item.ProcessId -ErrorAction SilentlyContinue
        if ($p -and ($procs.Id -notcontains $p.Id)) {
            $procs += $p
        }
    }
    
    # 3. Periksa port 8000
    $net = Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue
    if ($net) {
        foreach ($conn in $net) {
            $p = Get-Process -Id $conn.OwningProcess -ErrorAction SilentlyContinue
            if ($p -and ($procs.Id -notcontains $p.Id)) {
                $procs += $p
            }
        }
    }
    
    return $procs
}

function Stop-AllServers {
    Write-Host "[*] Menghentikan semua layanan server..." -ForegroundColor Yellow
    $procs = Get-RunningServerProcesses
    
    if ($procs.Count -eq 0) {
        Write-Host "[i] Tidak ada server yang sedang berjalan." -ForegroundColor DarkGray
    } else {
        foreach ($p in $procs) {
            try {
                Write-Host "    [-] Mematikan proses $($p.ProcessName) (PID: $($p.Id))..." -ForegroundColor DarkYellow
                Stop-Process -Id $p.Id -Force -ErrorAction SilentlyContinue
            } catch {}
        }
        Start-Sleep -Milliseconds 800
    }
    
    # Hapus file PID jika ada
    if (Test-Path $PidFile) {
        Remove-Item $PidFile -Force -ErrorAction SilentlyContinue
    }
    
    Write-Host ""
    Write-Host "================================================================" -ForegroundColor Green
    Write-Host "   [V] SEMUA SERVER TELAH BERHASIL DIMATIKAN!                  " -ForegroundColor Green
    Write-Host "================================================================" -ForegroundColor Green
    Write-Host ""
}

function Start-AllServers {
    # Pastikan server tidak double running
    $existing = Get-RunningServerProcesses
    if ($existing.Count -gt 0) {
        Write-Host "[!] Ditemukan layanan yang sudah aktif. Mereset terlebih dahulu..." -ForegroundColor Yellow
        Stop-AllServers
        Start-Sleep -Seconds 1
    }
    
    $phpExe = Find-PhpExecutable
    if (-not $phpExe) {
        Write-Host "[X] ERROR: PHP tidak ditemukan di PATH atau folder XAMPP!" -ForegroundColor Red
        return
    }
    
    if (-not (Test-Path $CloudflaredExe)) {
        Write-Host "[X] ERROR: cloudflared.exe tidak ditemukan di folder project!" -ForegroundColor Red
        return
    }
    
    Write-Host "[1/3] Menjalankan Laravel Web Server (Port 8000)..." -ForegroundColor Cyan
    $serveProcess = Start-Process -FilePath $phpExe `
        -ArgumentList "artisan", "serve", "--port=8000" `
        -WorkingDirectory $ProjectRoot `
        -RedirectStandardOutput $ServeLog `
        -RedirectStandardError $ServeErrLog `
        -WindowStyle Hidden `
        -PassThru
        
    Write-Host "[2/3] Menjalankan Queue Worker (Pengirim Email Otomatis)..." -ForegroundColor Cyan
    $workerProcess = Start-Process -FilePath $phpExe `
        -ArgumentList "artisan", "queue:work", "--sleep=2", "--tries=5" `
        -WorkingDirectory $ProjectRoot `
        -RedirectStandardOutput $WorkerLog `
        -RedirectStandardError $WorkerErrLog `
        -WindowStyle Hidden `
        -PassThru

    Write-Host "[3/3] Menghubungkan Cloudflare Tunnel ke Internet..." -ForegroundColor Cyan
    if (Test-Path $CloudflaredLog) { Remove-Item $CloudflaredLog -Force -ErrorAction SilentlyContinue }
    
    $cloudflaredProcess = Start-Process -FilePath $CloudflaredExe `
        -ArgumentList "tunnel", "--url", "http://127.0.0.1:8000" `
        -WorkingDirectory $ProjectRoot `
        -RedirectStandardError $CloudflaredLog `
        -WindowStyle Hidden `
        -PassThru

    # Simpan PIDs
    $pidsData = @{
        serve_pid = $serveProcess.Id
        worker_pid = $workerProcess.Id
        cloudflared_pid = $cloudflaredProcess.Id
        started_at = (Get-Date).ToString("o")
    } | ConvertTo-Json
    Set-Content -Path $PidFile -Value $pidsData -Force
    
    # Tunggu dan ekstrak URL Cloudflare
    Write-Host "    [*] Menunggu alokasi domain publik Cloudflare..." -NoNewline -ForegroundColor DarkGray
    $publicUrl = $null
    for ($i = 0; $i -lt 15; $i++) {
        Start-Sleep -Seconds 1
        Write-Host "." -NoNewline -ForegroundColor DarkGray
        if (Test-Path $CloudflaredLog) {
            $logContent = Get-Content $CloudflaredLog -Raw -ErrorAction SilentlyContinue
            if ($logContent -match 'https://[a-zA-Z0-9-]+\.trycloudflare\.com') {
                $publicUrl = $matches[0]
                break
            }
        }
    }
    Write-Host ""
    
    Write-Host ""
    Write-Host "================================================================" -ForegroundColor Green
    Write-Host "   [V] SEMUA SERVER BERHASIL DINYALAKAN & AKTIF!               " -ForegroundColor Green
    Write-Host "================================================================" -ForegroundColor Green
    Write-Host ""
    Write-Host " Akses Lokal    : " -NoNewline -ForegroundColor White
    Write-Host "http://127.0.0.1:8000" -ForegroundColor Yellow
    
    if ($publicUrl) {
        Write-Host " Akses Internet : " -NoNewline -ForegroundColor White
        Write-Host "$publicUrl" -ForegroundColor Cyan
        Write-Host ""
        Write-Host " (Link publik telah disalin otomatis ke Clipboard Anda!)" -ForegroundColor DarkGray
        try { Set-Clipboard -Value $publicUrl } catch {}
        
        # Buka link di browser default
        Start-Process $publicUrl
    } else {
        Write-Host " Akses Internet : " -NoNewline -ForegroundColor White
        Write-Host "Memerlukan waktu sebentar, periksa storage/logs/cloudflared.log" -ForegroundColor Yellow
        Start-Process "http://127.0.0.1:8000"
    }
    
    Write-Host " Worker Email   : " -NoNewline -ForegroundColor White
    Write-Host "AKTIF (Mengirim email otomatis via kajejh745@gmail.com)" -ForegroundColor Green
    Write-Host ""
}

function Show-Status {
    $isPortOpen = $null -ne (Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue)
    $isWorkerRunning = $null -ne (Get-CimInstance Win32_Process -ErrorAction SilentlyContinue | Where-Object { 
        $_.Name -eq "php.exe" -and $_.CommandLine -like "*artisan queue:work*" 
    })
    $isCfRunning = $null -ne (Get-CimInstance Win32_Process -ErrorAction SilentlyContinue | Where-Object { 
        $_.Name -eq "cloudflared.exe" 
    })
    
    Write-Host "Status Layanan Saat Ini:" -ForegroundColor White
    if ($isPortOpen) {
        Write-Host "  * Laravel Server : " -NoNewline
        Write-Host "AKTIF (Port 8000)" -ForegroundColor Green
    } else {
        Write-Host "  * Laravel Server : " -NoNewline
        Write-Host "MATI" -ForegroundColor Red
    }
    
    if ($isWorkerRunning) {
        Write-Host "  * Worker Email   : " -NoNewline
        Write-Host "AKTIF (Queue Worker)" -ForegroundColor Green
    } else {
        Write-Host "  * Worker Email   : " -NoNewline
        Write-Host "MATI" -ForegroundColor Red
    }
    
    if ($isCfRunning) {
        Write-Host "  * Cloudflare     : " -NoNewline
        Write-Host "AKTIF (Tunnel Siap)" -ForegroundColor Green
        
        $foundUrl = $null
        if (Test-Path $CloudflaredLog) {
            $logContent = Get-Content $CloudflaredLog -Raw -ErrorAction SilentlyContinue
            if ($logContent -match 'https://[a-zA-Z0-9-]+\.trycloudflare\.com') {
                $foundUrl = $matches[0]
            }
        }
        if ($foundUrl) {
            Write-Host "  * Link Publik    : " -NoNewline
            Write-Host "$foundUrl" -ForegroundColor Cyan
        }
    } else {
        Write-Host "  * Cloudflare     : " -NoNewline
        Write-Host "MATI" -ForegroundColor Red
    }
    Write-Host ""
}

function Show-Menu {
    while ($true) {
        Write-Banner
        Show-Status
        Write-Host "Pilih Aksi:" -ForegroundColor White
        Write-Host " [1] Hidupkan Server (Start All)" -ForegroundColor Green
        Write-Host " [2] Matikan Server (Stop All)" -ForegroundColor Red
        Write-Host " [3] Restart Server" -ForegroundColor Yellow
        Write-Host " [4] Buka Website di Browser" -ForegroundColor Cyan
        Write-Host " [5] Keluar" -ForegroundColor Gray
        Write-Host ""
        
        $choice = Read-Host "Masukkan pilihan [1-5]"
        switch ($choice) {
            "1" { Start-AllServers; Read-Host "Tekan Enter untuk kembali ke menu..." }
            "2" { Stop-AllServers; Read-Host "Tekan Enter untuk kembali ke menu..." }
            "3" { Stop-AllServers; Start-Sleep -Seconds 1; Start-AllServers; Read-Host "Tekan Enter untuk kembali ke menu..." }
            "4" {
                if (Test-Path $CloudflaredLog) {
                    $log = Get-Content $CloudflaredLog -Raw -ErrorAction SilentlyContinue
                    if ($log -match 'https://[a-zA-Z0-9-]+\.trycloudflare\.com') {
                        Start-Process $matches[0]
                    } else {
                        Start-Process "http://127.0.0.1:8000"
                    }
                } else {
                    Start-Process "http://127.0.0.1:8000"
                }
            }
            "5" { exit }
            default { Write-Host "Pilihan tidak valid." -ForegroundColor Red; Start-Sleep -Seconds 1 }
        }
    }
}

# ==============================================================================
# MAIN EXECUTION
# ==============================================================================
switch ($Action.ToLower()) {
    "start" {
        Write-Banner
        Start-AllServers
    }
    "stop" {
        Write-Banner
        Stop-AllServers
    }
    "restart" {
        Write-Banner
        Stop-AllServers
        Start-Sleep -Seconds 1
        Start-AllServers
    }
    "status" {
        Write-Banner
        Show-Status
    }
    "menu" {
        Show-Menu
    }
    "toggle" {
        Write-Banner
        $procs = Get-RunningServerProcesses
        $isPortOpen = $null -ne (Get-NetTCPConnection -LocalPort 8000 -State Listen -ErrorAction SilentlyContinue)
        
        if ($procs.Count -gt 0 -or $isPortOpen) {
            Write-Host "Status Terdeteksi: SERVER SEDANG HIDUP." -ForegroundColor Green
            Write-Host "Tindakan         : MEMATIKAN SERVER..." -ForegroundColor Yellow
            Write-Host ""
            Stop-AllServers
        } else {
            Write-Host "Status Terdeteksi: SERVER SEDANG MATI." -ForegroundColor Red
            Write-Host "Tindakan         : MENGHIDUPKAN SERVER..." -ForegroundColor Yellow
            Write-Host ""
            Start-AllServers
        }
    }
    default {
        Write-Banner
        Show-Menu
    }
}
