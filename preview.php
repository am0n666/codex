<?php
session_start();

header('Content-Type: text/html; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

$uploadDir = 'uploads/';
$outputDir = 'output/';

if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

if (!file_exists($outputDir)) {
    mkdir($outputDir, 0777, true);
}

try {
    $files = $_FILES['source_files'] ?? [];
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $date = $_POST['date'] ?? date('Y-m-d');
    $language = $_POST['language'] ?? 'pl-PL';
    $template = $_POST['template'] ?? 'default';
    $toc = isset($_POST['toc']) ? true : false;
    $config = $_POST['config'] ?? [];
    
    if (empty($files['name'][0])) {
        throw new Exception('Nie wybrano żadnych plików');
    }
    
    $timestamp = time();
    $uploadedFiles = [];
    
    // Upload files
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $tempName = $files['tmp_name'][$i];
            $originalName = $files['name'][$i];
            $safeFileName = $timestamp . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $originalName);
            $uploadPath = $uploadDir . $safeFileName;
            
            if (move_uploaded_file($tempName, $uploadPath)) {
                $uploadedFiles[] = $uploadPath;
            }
        }
    }
    
    if (empty($uploadedFiles)) {
        throw new Exception('Nie udało się przesłać żadnego pliku');
    }
    
    // Create combined markdown file for preview
    $combinedFile = $uploadDir . $timestamp . '_combined.md';
    $combinedContent = '';
    
    foreach ($uploadedFiles as $file) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $content = file_get_contents($file);
        
        // Convert to markdown if needed
        if ($ext !== 'md') {
            $tempOutput = $uploadDir . $timestamp . '_temp.md';
            $cmd = escapeshellcmd("pandoc") . " " . escapeshellarg($file) . " -o " . escapeshellarg($tempOutput);
            exec($cmd . " 2>&1", $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($tempOutput)) {
                $content = file_get_contents($tempOutput);
                unlink($tempOutput);
            }
        }
        
        $combinedContent .= $content . "\n\n";
    }
    
    file_put_contents($combinedFile, $combinedContent);
    
    // Check if combined file was created
    if (!file_exists($combinedFile)) {
        throw new Exception('Nie udało się utworzyć pliku tymczasowego');
    }
    
    // Build pandoc command for PDF preview
    $outputFile = $outputDir . $timestamp . '_preview.pdf';
    $cmd = escapeshellcmd("pandoc") . " " . escapeshellarg($combinedFile);
    $cmd .= " -o " . escapeshellarg($outputFile);
    $cmd .= " --pdf-engine=pdflatex";  // Changed from xelatex to pdflatex for better compatibility
    
    // Add template variables for page numbers and headers
    if (!empty($config['page_numbers'])) {
        $cmd .= " --variable pagestyle=plain";
    }
    
    // Paper size
    if (!empty($config['paper_size'])) {
        $paperSize = str_replace('paper', '', $config['paper_size']); // Convert a4paper to a4
        $cmd .= " --variable papersize=" . escapeshellarg($paperSize);
    }
    
    // Margins
    if (!empty($config['margins'])) {
        $marginMap = [
            'narrow' => '1.27cm',
            'normal' => '2.54cm',
            'wide' => '3.81cm'
        ];
        $margin = $marginMap[$config['margins']] ?? '2.54cm';
        $cmd .= " -V geometry:margin=" . escapeshellarg($margin);
    } else {
        // Default margins
        $cmd .= " -V geometry:margin=2.54cm";
    }
    
    // Font settings
    if (!empty($config['font_size'])) {
        $cmd .= " --variable fontsize=" . escapeshellarg($config['font_size']);
    }
    
    if (!empty($config['font_family'])) {
        $cmd .= " --variable mainfont=" . escapeshellarg($config['font_family']);
    }
    
    // Line spacing
    if (!empty($config['line_spacing'])) {
        $cmd .= " --variable linestretch=" . escapeshellarg($config['line_spacing']);
    }
    
    // Two column layout
    if (!empty($config['two_column'])) {
        $cmd .= " --variable classoption=twocolumn";
    }
    
    // Paragraph indent
    if (!empty($config['paragraph_indent'])) {
        $cmd .= " --variable indent=true";
    }
    
    // Header and footer
    if (!empty($config['header_footer'])) {
        $cmd .= " --variable pagestyle=headings";
    }
    
    // Metadata
    if (!empty($title)) {
        $cmd .= " --metadata title=" . escapeshellarg($title);
    }
    if (!empty($author)) {
        $cmd .= " --metadata author=" . escapeshellarg($author);
    }
    if (!empty($date)) {
        $cmd .= " --metadata date=" . escapeshellarg($date);
    }
    if (!empty($language)) {
        $cmd .= " --metadata lang=" . escapeshellarg($language);
    }
    
    // Table of contents
    if ($toc) {
        $cmd .= " --toc";
        if (!empty($config['toc_depth'])) {
            $cmd .= " --toc-depth=" . intval($config['toc_depth']);
        }
    }
    
    // Syntax highlighting
    if (!empty($config['syntax_highlighting'])) {
        $cmd .= " --highlight-style=" . escapeshellarg($config['syntax_highlighting']);
    }
    
    // Math rendering
    if (!empty($config['math_rendering'])) {
        switch ($config['math_rendering']) {
            case 'mathjax':
                $cmd .= " --mathjax";
                break;
            case 'mathml':
                $cmd .= " --mathml";
                break;
            case 'webtex':
                $cmd .= " --webtex";
                break;
            case 'katex':
                $cmd .= " --katex";
                break;
        }
    }
    
    // Configuration options
    if (!empty($config['numbered_sections'])) {
        $cmd .= " --number-sections";
    }
    
    // Font size
    if (!empty($config['font_size'])) {
        $cmd .= " --variable fontsize=" . escapeshellarg($config['font_size']);
    }
    
    // Font family
    if (!empty($config['font_family'])) {
        $cmd .= " --variable mainfont=" . escapeshellarg($config['font_family']);
    }
    
    // Paper size
    if (!empty($config['paper_size'])) {
        $cmd .= " --variable papersize=" . escapeshellarg($config['paper_size']);
    }
    
    // Line spacing
    if (!empty($config['line_spacing'])) {
        $cmd .= " --variable linestretch=" . escapeshellarg($config['line_spacing']);
    }
    
    // Margins
    if (!empty($config['margins'])) {
        $marginMap = [
            'narrow' => '1.27cm',
            'normal' => '2.54cm',
            'wide' => '3.81cm'
        ];
        $margin = $marginMap[$config['margins']] ?? '2.54cm';
        $cmd .= " --variable margin-left=" . escapeshellarg($margin);
        $cmd .= " --variable margin-right=" . escapeshellarg($margin);
        $cmd .= " --variable margin-top=" . escapeshellarg($margin);
        $cmd .= " --variable margin-bottom=" . escapeshellarg($margin);
    }
    
    // Remove CSS reference since file doesn't exist
    // $cmd .= " --css=assets/css/preview.css";
    
    // Log the command for debugging
    error_log("Pandoc command: " . $cmd);
    
    // Execute pandoc
    exec($cmd . " 2>&1", $output, $returnCode);
    
    // Log output for debugging
    error_log("Pandoc return code: " . $returnCode);
    error_log("Pandoc output: " . implode("\n", $output));
    
    // Clean up uploaded files
    foreach ($uploadedFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    if (file_exists($combinedFile)) {
        unlink($combinedFile);
    }
    
    if ($returnCode !== 0) {
        if (file_exists($outputFile)) {
            unlink($outputFile);
        }
        throw new Exception('Błąd konwersji (kod: ' . $returnCode . '): ' . implode("\n", $output));
    }
    
    // Check if PDF was generated
    if (file_exists($outputFile)) {
        // Send PDF headers
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="preview.pdf"');
        header('Cache-Control: no-cache, must-revalidate');
        
        // Output PDF content
        readfile($outputFile);
        
        // Clean up output file
        unlink($outputFile);
        exit();
    } else {
        throw new Exception('Nie udało się wygenerować podglądu PDF');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Błąd podglądu</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            color: #721c24;
            background: #f8d7da;
        }
        .error {
            border: 1px solid #f5c6cb;
            background: white;
            padding: 20px;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <div class="error">
        <h2>Błąd generowania podglądu</h2>
        <p>' . htmlspecialchars($e->getMessage()) . '</p>
    </div>
</body>
</html>';
}
?>