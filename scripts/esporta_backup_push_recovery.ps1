$ErrorActionPreference = 'Stop'

$outer = 'c:\xampp\htdocs\Nuoto-Libero-Le-Naiadi'
$inner = Join-Path $outer 'Nuoto-Libero-Le-Naiadi'

if (-not (Test-Path $inner)) {
    throw "Repo interna non trovata: $inner"
}

$outerFiles = Get-ChildItem -Path $outer -Recurse -File | Where-Object {
    $full = $_.FullName
    if ($full.StartsWith($inner, [System.StringComparison]::OrdinalIgnoreCase)) { return $false }
    if ($full -match '\\logs\\') { return $false }
    if ($full -match '\\uploads\\') { return $false }
    if ($full -match '\\vendor\\') { return $false }
    if ($full -match '\\.git\\') { return $false }
    return $true
}

$diffs = New-Object System.Collections.Generic.List[Object]

foreach ($f in $outerFiles) {
    $rel = $f.FullName.Substring($outer.Length + 1)
    $innerPath = Join-Path $inner $rel

    if (-not (Test-Path $innerPath)) {
        $hash = (Get-FileHash -Path $f.FullName -Algorithm SHA256).Hash
        $diffs.Add([PSCustomObject]@{
                RelativePath  = $rel
                Reason        = 'missing_in_inner'
                Size          = $f.Length
                LastWriteTime = $f.LastWriteTime.ToString('yyyy-MM-dd HH:mm:ss')
                Sha256        = $hash
            })
        continue
    }

    $innerItem = Get-Item -LiteralPath $innerPath
    if ($f.Length -ne $innerItem.Length) {
        $hash = (Get-FileHash -Path $f.FullName -Algorithm SHA256).Hash
        $diffs.Add([PSCustomObject]@{
                RelativePath  = $rel
                Reason        = 'size_diff'
                Size          = $f.Length
                LastWriteTime = $f.LastWriteTime.ToString('yyyy-MM-dd HH:mm:ss')
                Sha256        = $hash
            })
        continue
    }

    $hashOuter = (Get-FileHash -Path $f.FullName -Algorithm SHA256).Hash
    $hashInner = (Get-FileHash -Path $innerPath -Algorithm SHA256).Hash
    if ($hashOuter -ne $hashInner) {
        $diffs.Add([PSCustomObject]@{
                RelativePath  = $rel
                Reason        = 'hash_diff'
                Size          = $f.Length
                LastWriteTime = $f.LastWriteTime.ToString('yyyy-MM-dd HH:mm:ss')
                Sha256        = $hashOuter
            })
    }
}

$stamp = Get-Date -Format 'yyyyMMdd_HHmmss'
$backupDir = Join-Path $outer ("DOCUMENTAZIONE_E_CONFIG\BACKUP_PUSH_RECOVERY_" + $stamp)
$filesDir = Join-Path $backupDir 'files'
New-Item -ItemType Directory -Path $filesDir -Force | Out-Null

foreach ($d in $diffs) {
    $src = Join-Path $outer $d.RelativePath
    $dst = Join-Path $filesDir $d.RelativePath
    $dstDir = Split-Path -Parent $dst
    if (-not (Test-Path $dstDir)) {
        New-Item -ItemType Directory -Path $dstDir -Force | Out-Null
    }
    Copy-Item -LiteralPath $src -Destination $dst -Force
}

$manifestPath = Join-Path $backupDir 'manifest_differenze.csv'
$diffs | Sort-Object RelativePath | Export-Csv -NoTypeInformation -Encoding UTF8 -Path $manifestPath

$listPath = Join-Path $backupDir 'lista_file.txt'
$diffs | Sort-Object RelativePath | ForEach-Object {
    "{0}`t{1}`t{2}" -f $_.Reason, $_.RelativePath, $_.Size
} | Set-Content -Encoding UTF8 -Path $listPath

$summaryPath = Join-Path $backupDir 'README_RECUPERO.txt'
$summary = @()
$summary += 'BACKUP PUSH RECOVERY'
$summary += 'Creato: ' + (Get-Date -Format 'yyyy-MM-dd HH:mm:ss')
$summary += 'Outer (non git): ' + $outer
$summary += 'Inner (git): ' + $inner
$summary += 'File differenze: ' + $diffs.Count
$summary += ''
$summary += 'Passi rapidi:'
$summary += '1) Clona/usa repo git valida.'
$summary += '2) Copia contenuto di files\ sopra la root repo.'
$summary += '3) git add -A && git commit -m "chore: recupero modifiche backup" && git push origin main'
$summary += ''
$summary += 'Dettagli in manifest_differenze.csv'
$summary | Set-Content -Encoding UTF8 -Path $summaryPath

$zipPath = $backupDir + '.zip'
if (Test-Path $zipPath) {
    Remove-Item -LiteralPath $zipPath -Force
}
Compress-Archive -Path (Join-Path $backupDir '*') -DestinationPath $zipPath -Force

Write-Output ('BACKUP_DIR=' + $backupDir)
Write-Output ('BACKUP_ZIP=' + $zipPath)
Write-Output ('FILES_COUNT=' + $diffs.Count)
