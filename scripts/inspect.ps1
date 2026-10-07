# Launches the MCP Inspector for an MCP server registered in routes/ai.php.
#
# Workaround: `php artisan mcp:inspector <handle>` only passes --config to
# @modelcontextprotocol/inspector, but newer inspector releases require
# --config and --server together. This script generates the same config
# file Laravel would create and adds the missing --server flag.
#
# Usage: powershell -ExecutionPolicy Bypass -File scripts\inspect.ps1 [handle]

param(
    [string]$Handle = 'tasks'
)

$ErrorActionPreference = 'Stop'

# Resolve the real PHP executable (on Herd, `php` on PATH may be a .bat shim,
# which child_process cannot spawn directly for the stdio transport).
try {
    $php = (& php -r 'echo PHP_BINARY;').Trim()
} catch {
    $php = (Get-Command php -ErrorAction Stop).Source
}

if ([string]::IsNullOrWhiteSpace($php)) {
    $php = (Get-Command php -ErrorAction Stop).Source
}

$artisan = Join-Path $PSScriptRoot '..\artisan' | Resolve-Path

$config = @{
    mcpServers = @{
        $Handle = @{
            type        = 'stdio'
            command     = $php
            args        = @($artisan.ToString(), 'mcp:start', $Handle)
            protocolEra = 'modern'
        }
    }
} | ConvertTo-Json -Depth 6

$configPath = Join-Path ([System.IO.Path]::GetTempPath()) 'mcp-inspector-config.json'
[System.IO.File]::WriteAllText($configPath, $config)  # UTF-8 without BOM

Write-Host "Config  => $configPath"
Write-Host "Server  => $Handle"
Write-Host 'Starting MCP Inspector...'

& npx '@modelcontextprotocol/inspector' --config $configPath --server $Handle
