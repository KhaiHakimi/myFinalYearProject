# Deploy Script for Ferrycast (Windows)

Write-Host "Starting Deployment Build..."

# 1. Build Assets
Write-Host "Building Vite Assets..."
cmd /c "npm run build"
if ($LASTEXITCODE -ne 0) { Write-Host "Build failed!"; exit }

# 2. Clear Caches
Write-Host "Clearing Laravel Caches..."
php artisan optimize:clear

# 3. Create Zip Archive
$zipName = "ferrycast_deploy.zip"
Write-Host "Zipping files to $zipName..."

# Remove old zip if exists
if (Test-Path $zipName) { Remove-Item $zipName }

# List of items to include
$exclude = @(
    "node_modules",
    ".git",
    ".idea",
    ".vscode",
    "storage/*.key",
    ".env", 
    "deploy.ps1",
    "$zipName"
)

# Zip using Compress-Archive
Write-Host "Using Compress-Archive..."
$items = Get-ChildItem -Path . -Exclude $exclude | Where-Object { $_.Name -notin $exclude }
Compress-Archive -Path $items -DestinationPath $zipName -Update

Write-Host "SUCCESS: $zipName created successfully!"
Write-Host "Upload this file to Hostinger."
