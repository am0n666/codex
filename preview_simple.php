<?php
session_start();

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="preview.pdf"');
header('Cache-Control: no-cache, must-revalidate');

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
    
    // Create combined markdown file
    $combinedFile = $uploadDir . $timestamp . '_combined.md';
    $combinedContent = '';
    
    // Add metadata header
    $combinedContent .= "---\n";
    if (!empty($title)) {
        $combinedContent .= "title: \"$title\"\n";
    }
    if (!empty($author)) {
        $combinedContent .= "author: \"$author\"\n";
    }
    if (!empty($date)) {
        $combinedContent .= "date: \"$date\"\n";
    }
    if (!empty($language)) {
        $combinedContent .= "lang: \"$language\"\n";
    }
    $combinedContent .= "---\n\n";
    
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
    
    // Build pandoc command for PDF
    $outputFile = $outputDir . $timestamp . '_preview.pdf';
    $cmd = escapeshellcmd("pandoc") . " " . escapeshellarg($combinedFile);
    $cmd .= " -o " . escapeshellarg($outputFile);
    
    // Basic PDF settings
    $cmd .= " --pdf-engine=pdflatex";
    $cmd .= " -V documentclass=article";
    
    // Table of contents
    if ($toc) {
        $cmd .= " --toc";
        if (!empty($config['toc_depth'])) {
            $cmd .= " --toc-depth=" . intval($config['toc_depth']);
        }
    }
    
    // Numbered sections
    if (!empty($config['numbered_sections'])) {
        $cmd .= " --number-sections";
    }
    
    // Geometry (margins and paper size)
    $geometry = [];
    
    // Paper size
    $paperSize = 'a4paper';
    if (!empty($config['paper_size'])) {
        $paperSize = $config['paper_size'];
    }
    $geometry[] = $paperSize;
    
    // Margins
    $margin = '2.54cm';
    if (!empty($config['margins'])) {
        $marginMap = [
            'narrow' => '1.27cm',
            'normal' => '2.54cm',
            'wide' => '3.81cm'
        ];
        $margin = $marginMap[$config['margins']] ?? '2.54cm';
    }
    $geometry[] = "margin=$margin";
    
    $cmd .= " -V geometry:" . implode(',', $geometry);
    
    // Font size
    $fontSize = '11pt';
    if (!empty($config['font_size'])) {
        $fontSize = $config['font_size'];
    }
    $cmd .= " -V fontsize=$fontSize";
    
    // Line spacing
    if (!empty($config['line_spacing'])) {
        $cmd .= " -V linestretch=" . $config['line_spacing'];
    }
    
    // Page style (for headers/footers/page numbers)
    if (!empty($config['header_footer'])) {
        $cmd .= " -V pagestyle=headings";
    } elseif (!empty($config['page_numbers'])) {
        $cmd .= " -V pagestyle=plain";
    } else {
        $cmd .= " -V pagestyle=empty";
    }
    
    // Two column layout
    if (!empty($config['two_column'])) {
        $cmd .= " -V classoption=twocolumn";
    }
    
    // Paragraph indent
    if (!empty($config['paragraph_indent'])) {
        $cmd .= " -V indent";
    }
    
    // Syntax highlighting
    if (!empty($config['syntax_highlighting'])) {
        $cmd .= " --highlight-style=" . escapeshellarg($config['syntax_highlighting']);
    }
    
    // Execute pandoc
    error_log("Preview command: " . $cmd);
    exec($cmd . " 2>&1", $output, $returnCode);
    
    // Clean up
    foreach ($uploadedFiles as $file) {
        if (file_exists($file)) {
            unlink($file);
        }
    }
    if (file_exists($combinedFile)) {
        unlink($combinedFile);
    }
    
    if ($returnCode !== 0) {
        error_log("Pandoc error: " . implode("\n", $output));
        throw new Exception('Błąd generowania PDF: ' . implode("\n", $output));
    }
    
    // Output PDF
    if (file_exists($outputFile)) {
        readfile($outputFile);
        unlink($outputFile);
    } else {
        throw new Exception('Nie udało się wygenerować pliku PDF');
    }
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Błąd: ' . $e->getMessage();
}
?>