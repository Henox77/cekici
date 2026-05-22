<?php

$root = realpath(__DIR__ . '/../');
$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root));
$files = [];
foreach ($rii as $file) {
    if ($file->isDir()) continue;
    $path = $file->getPathname();
    if (stripos($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR) !== false) continue;
    if (preg_match('/\\.php$/i', $path) || preg_match('/\\.sql$/i', $path)) {
        $files[] = $path;
    }
}

function strip_php_comments($code) {
    $tokens = token_get_all($code);
    $out = '';
    foreach ($tokens as $token) {
        if (is_array($token)) {
            $id = $token[0];
            $text = $token[1];
            if ($id === T_COMMENT || $id === T_DOC_COMMENT) {
                
                continue;
            }
            $out .= $text;
        } else {
            $out .= $token;
        }
    }
    return $out;
}

function strip_sql_comments($code) {
    
    $code = preg_replace('#/\*[\s\S]*?\*/#', '', $code);
    
    $code = preg_replace('/(^|\n)\s*--.*?(\n|$)/', "\n", $code);
    return $code;
}

foreach ($files as $file) {
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $orig = file_get_contents($file);
    if ($ext === 'php') {
        
        if (strpos($orig, '<?') === false) {
            
            continue;
        }
        $clean = strip_php_comments($orig);
    } else {
        $clean = strip_sql_comments($orig);
    }
    
    $clean = preg_replace('/\n{3,}/', "\n\n", $clean);
    file_put_contents($file, $clean);
    echo "Processed: $file\n";
}

echo "Done.\n";
