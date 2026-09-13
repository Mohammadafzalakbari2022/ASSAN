param(
	[ValidateSet( 'deploys', 'logs' )]
	[string]$What = 'deploys',
	[int]$Limit = 5
)

# Render API key is stored in render-api-key.txt next to this script (git-ignored).
$dir = Split-Path -Parent $MyInvocation.MyCommand.Path
$key = (Get-Content ( Join-Path $dir 'render-api-key.txt' ) -Raw).Trim()
$headers = @{ Authorization = 'Bearer ' + $key }
$serviceId = 'srv-dahi4d67bikc73e9ricg'

if( $What -eq 'deploys' )
{
	$dep = Invoke-RestMethod -Uri ( "https://api.render.com/v1/services/$serviceId/deploys?limit=$Limit" ) -Headers $headers -TimeoutSec 60
	foreach( $entry in $dep )
	{
		$d = $entry.deploy
		$msg = ($d.commit.message -split "`n")[0]
		$st = $d.status; if( -not $st ) { $st = $entry.status }
		"{0}  {1}  {2}  {3}" -f ([string]$st).PadRight(12), [string]$d.commit.id.Substring(0,8), $d.createdAt, $msg
	}
}
elseif( $What -eq 'logs' )
{
	$uri = "https://api.render.com/v1/services/$serviceId/logs?limit=$Limit&type=RUNTIME"
	$resp = Invoke-WebRequest -UseBasicParsing -Uri $uri -Headers $headers -TimeoutSec 60
	$resp.Content
}