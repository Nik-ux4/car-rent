<?php
// Other existing config code (like DB connection, session start, etc.)

/**
 * Get the correct relative path for assets (CSS, JS, images)
 */
function asset($path) {
    $currentDir = __DIR__;
    $documentRoot = $_SERVER['DOCUMENT_ROOT'];

    $relativePath = str_replace($documentRoot, '', $currentDir);
    $relativePath = ltrim($relativePath, '/');

    $folderDepth = ($relativePath === '') ? 0 : substr_count($relativePath, '/');
    $base = str_repeat('../', $folderDepth);

    return $base . $path;
}