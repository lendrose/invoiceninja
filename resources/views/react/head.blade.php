<?php
/**
 * Dynamic React Assets Head Generator
 * Scans the /react/assets directory and generates appropriate HTML tags
 */

// Define the assets directory path relative to public
$assetsDir = public_path('react/assets');

// Initialize arrays to store different file types
$jsFiles = [];
$cssFiles = [];
$indexJsFile = null;

// Check if assets directory exists
if (is_dir($assetsDir)) {
    // Scan the assets directory
    $files = scandir($assetsDir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }
        
        $filePath = $assetsDir . DIRECTORY_SEPARATOR . $file;
        
        if (is_file($filePath)) {
            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            $filename = pathinfo($file, PATHINFO_FILENAME);
            
            // Handle JavaScript files
            if ($extension === 'js') {
                // Check if this is the index.js file
                if (strpos($filename, 'index') === 0) {
                    $indexJsFile = $file;
                } else {
                    $jsFiles[] = $file;
                }
            }
            // Handle CSS files
            elseif ($extension === 'css') {
                $cssFiles[] = $file;
            }
        }
    }
}

// Sort files for consistent output
sort($jsFiles);
sort($cssFiles);
?>

{{-- Static assets that don't change between builds --}}
<link rel="icon" href="/react/favicon.ico" />
<link rel="apple-touch-icon" href="/react/logo180.png" />
<link rel="manifest" href="/react/manifest.json" />

{{-- Dynamic JavaScript files --}}
@if($indexJsFile)
<script type="module" crossorigin src="/react/assets/{{ $indexJsFile }}"></script>
@endif

@foreach($jsFiles as $jsFile)
<link rel="modulepreload" crossorigin href="/react/assets/{{ $jsFile }}">
@endforeach

{{-- Dynamic CSS files --}}
@foreach($cssFiles as $cssFile)
<link rel="stylesheet" crossorigin href="/react/assets/{{ $cssFile }}">
@endforeach
