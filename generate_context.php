<?php
// generate_context.php

$outputFile = 'CELR_App_Codebase_Context.md';
$rootPath = __DIR__;

$ignoreDirs = ['.git', '.gemini', 'uploads', 'vendor', '.vscode', 'node_modules', 'backup'];
$ignoreFiles = ['composer.lock', 'package-lock.json', $outputFile, 'generate_context.php'];
$extensions = ['php', 'js', 'css', 'sql', 'md', 'html'];

$handle = fopen($outputFile, 'w');
fwrite($handle, "# CELR App Codebase Context\n\n");
fwrite($handle, "Generated on: " . date('Y-m-d H:i:s') . "\n\n");

fwrite($handle, "## Project Overview\n");
fwrite($handle, "CELR-App is a comprehensive fleet and logistics management system designed for heavy cargo transport. It handles trip life-cycles from creation to settlement, including expense tracking, fuel management, and driver commissions.\n\n");

fwrite($handle, "## Technical Stack\n");
fwrite($handle, "- **Language:** PHP 8.x\n");
fwrite($handle, "- **Framework:** Custom MVC Structure (evolving from legacy PHP scripts)\n");
fwrite($handle, "- **Frontend:** Tailwind CSS, Alpine.js\n");
fwrite($handle, "- **Database:** MySQL (MariaDB)\n");
fwrite($handle, "- **APIs:** RESTful endpoints handled by ApiController.php\n\n");

fwrite($handle, "## Core Modules\n");
fwrite($handle, "1. **Trips (Viajes):** Operational core. Manages routes, cargo details, and profitability.\n");
fwrite($handle, "2. **Expenses (Gastos):** Operative cost tracking linked to vehicles or specific trips.\n");
fwrite($handle, "3. **Settlements (Liquidaciones):** Financial closure of trip periods for drivers and vehicle owners.\n");
fwrite($handle, "4. **Locations:** Hierarchical Country -> State -> City system with custom 'Aliases' for frequent spots.\n");
fwrite($handle, "5. **Audit Logs:** Full traceability system for all CRUD operations.\n\n");

fwrite($handle, "---\n\n");

function scanDirRecursive($dir, $handle)
{
    global $rootPath, $ignoreDirs, $ignoreFiles, $extensions;

    $files = scandir($dir);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..')
            continue;

        $path = $dir . '/' . $file;
        $relativePath = str_replace($rootPath . '/', '', $path);

        if (is_dir($path)) {
            if (in_array($file, $ignoreDirs))
                continue;
            scanDirRecursive($path, $handle);
        } else {
            if (in_array($file, $ignoreFiles))
                continue;

            $ext = pathinfo($path, PATHINFO_EXTENSION);
            if (!in_array($ext, $extensions))
                continue;

            fwrite($handle, "## File: " . $relativePath . "\n\n");
            fwrite($handle, "```" . $ext . "\n");

            // Read file content
            $content = file_get_contents($path);

            // Basic cleanup if needed (e.g. avoid huge binary dumps if extensions were loose)
            fwrite($handle, $content);

            fwrite($handle, "\n```\n\n");
            echo "Processed: $relativePath\n";
        }
    }
}

echo "Starting codebase scan...\n";
scanDirRecursive($rootPath, $handle);
fclose($handle);

echo "Done! Context saved to $outputFile\n";
?>