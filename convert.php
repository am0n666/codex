<?php
session_start();

$uploadDir = __DIR__ . '/uploads/';
$outputDir = __DIR__ . '/output/';
$templateDir = __DIR__ . '/templates/';

// Ensure working directories exist
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}
if (!is_dir($outputDir)) {
    mkdir($outputDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

try {
    if (!isset($_FILES['source_files']) || empty($_FILES['source_files']['name'][0])) {
        throw new Exception('Błąd podczas przesyłania plików');
    }

    $outputFormat = $_POST['output_format'] ?? 'pdf';
    $title = $_POST['title'] ?? 'Bez tytułu';
    $author = $_POST['author'] ?? 'Nieznany autor';
    $language = $_POST['language'] ?? 'pl-PL';
    $date = $_POST['date'] ?? '';
    $generateToc = isset($_POST['toc']);
    $chapterBreak = isset($_POST['chapter_break']);
    $template = $_POST['template'] ?? 'default';
    $config = $_POST['config'] ?? [];

    $timestamp = time();
    $uploadedFiles = [];

    // Handle multiple file uploads
    foreach ($_FILES['source_files']['tmp_name'] as $key => $tmp_name) {
        if ($_FILES['source_files']['error'][$key] !== UPLOAD_ERR_OK) {
            continue;
        }

        $fileName = $_FILES['source_files']['name'][$key];
        $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        $allowedExts = ['txt', 'md', 'html', 'docx', 'odt', 'tex', 'rst'];

        if (!in_array($fileExt, $allowedExts)) {
            throw new Exception('Nieobsługiwany format pliku: ' . $fileName);
        }

        $uploadedFile = $uploadDir . $timestamp . '_' . $key . '_' . basename($fileName);
        
        if (!move_uploaded_file($tmp_name, $uploadedFile)) {
            throw new Exception('Nie udało się zapisać pliku: ' . $fileName);
        }

        $uploadedFiles[] = $uploadedFile;
    }

    if (empty($uploadedFiles)) {
        throw new Exception('Nie przesłano żadnych plików');
    }

    // Create merged file if multiple files were uploaded
    $sourceFile = $uploadedFiles[0];
    if (count($uploadedFiles) > 1) {
        $mergedFile = $uploadDir . $timestamp . '_merged.md';
        $mergedContent = '';
        
        foreach ($uploadedFiles as $index => $file) {
            $content = file_get_contents($file);
            if ($index > 0) {
                $mergedContent .= "\n\n";
            }
            // Add chapter heading
            $chapterNum = $index + 1;
            $mergedContent .= "# Rozdział $chapterNum\n\n";
            $mergedContent .= $content;
        }
        
        file_put_contents($mergedFile, $mergedContent);
        $sourceFile = $mergedFile;
        
        // Clean up individual files
        foreach ($uploadedFiles as $file) {
            @unlink($file);
        }
    }

    $outputFile = $outputDir . $timestamp . '_output.' . $outputFormat;

    $pandocCmd = "pandoc";
    $pandocArgs = [
        "--data-dir=" . escapeshellarg($templateDir),
        escapeshellarg($sourceFile),
        "-o", escapeshellarg($outputFile),
        "--metadata", "title=" . escapeshellarg($title),
        "--metadata", "author=" . escapeshellarg($author),
        "--metadata", "lang=" . escapeshellarg($language)
    ];
    
    // Add date only if provided and not empty
    if (!empty(trim($date))) {
        $pandocArgs[] = "--metadata";
        $pandocArgs[] = "date=" . escapeshellarg($date);
    }

    // Add table of contents early if needed
    if ($generateToc && in_array($outputFormat, ['epub', 'pdf'])) {
        $pandocArgs[] = "--toc";
        if (isset($config['toc_depth']) && !empty($config['toc_depth'])) {
            $pandocArgs[] = "--toc-depth=" . $config['toc_depth'];
        } else {
            $pandocArgs[] = "--toc-depth=3";
        }
    }

    // Add template-specific options
    if ($template !== 'default') {
        switch ($template) {
            case 'eisvogel':
                $pandocArgs[] = "--template=" . escapeshellarg($templateDir . 'eisvogel.latex');
                $pandocArgs[] = "--listings";
                break;
            case 'academic':
                $pandocArgs[] = "--citeproc";
                if (isset($config['numbered_sections'])) {
                    $pandocArgs[] = "--number-sections";
                }
                break;
            case 'novel':
                $pandocArgs[] = "-V";
                $pandocArgs[] = "documentclass=book";
                $pandocArgs[] = "-V";
                $pandocArgs[] = "classoption=oneside";
                break;
            case 'report':
                $pandocArgs[] = "-V";
                $pandocArgs[] = "documentclass=report";
                if (isset($config['numbered_sections'])) {
                    $pandocArgs[] = "--number-sections";
                }
                break;
        }
    }

    // Apply configuration options
    if (isset($config['font_size'])) {
        $pandocArgs[] = "-V";
        $pandocArgs[] = "fontsize=" . escapeshellarg($config['font_size']);
    }

    if (isset($config['margins'])) {
        $margin = '1in';
        switch ($config['margins']) {
            case 'narrow':
                $margin = '0.5in';
                break;
            case 'wide':
                $margin = '1.5in';
                break;
        }
        $pandocArgs[] = "-V";
        $pandocArgs[] = "geometry:margin=" . $margin;
    }
    
    // Typography options
    if (isset($config['line_spacing'])) {
        $pandocArgs[] = "-V";
        $pandocArgs[] = "linestretch=" . escapeshellarg($config['line_spacing']);
    }
    
    if (isset($config['paragraph_indent'])) {
        $pandocArgs[] = "-V";
        $pandocArgs[] = "indent=" . escapeshellarg($config['paragraph_indent'] ? 'true' : 'false');
    }
    
    if (isset($config['font_family'])) {
        $pandocArgs[] = "-V";
        $pandocArgs[] = "mainfont=" . escapeshellarg($config['font_family']);
    }
    
    // Layout options
    if (isset($config['paper_size'])) {
        $pandocArgs[] = "-V";
        $pandocArgs[] = "papersize=" . escapeshellarg($config['paper_size']);
    }
    
    if (isset($config['two_column'])) {
        $pandocArgs[] = "-V";
        $pandocArgs[] = "classoption=twocolumn";
    }
    
    // Code highlighting
    if (isset($config['syntax_highlighting']) && !empty($config['syntax_highlighting'])) {
        $pandocArgs[] = "--highlight-style=" . $config['syntax_highlighting'];
    }
    
    // Links and references
    if (isset($config['link_color'])) {
        $pandocArgs[] = "-V";
        $pandocArgs[] = "linkcolor=" . escapeshellarg($config['link_color']);
        $pandocArgs[] = "-V";
        $pandocArgs[] = "urlcolor=" . escapeshellarg($config['link_color']);
    }
    
    // Table of contents depth - already handled above
    
    // Bibliography
    if (isset($config['bibliography']) && !empty($config['bibliography'])) {
        $pandocArgs[] = "--bibliography=" . escapeshellarg($config['bibliography']);
        $pandocArgs[] = "--citeproc";
    }
    
    // Math rendering
    if (isset($config['math_rendering']) && !empty($config['math_rendering'])) {
        $pandocArgs[] = "--" . $config['math_rendering'];
    }

    // Handle cover image
    $coverFile = null;
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $coverExt = strtolower(pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION));
        $allowedImageExts = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (in_array($coverExt, $allowedImageExts)) {
            $coverFile = $uploadDir . $timestamp . '_cover.' . $coverExt;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $coverFile)) {
                if ($outputFormat === 'epub') {
                    $pandocArgs[] = "--epub-cover-image=" . escapeshellarg($coverFile);
                }
            }
        }
    }

    // PDF specific options
    if ($outputFormat === 'pdf') {
        $pandocArgs[] = "--pdf-engine=xelatex";
        
        if (!isset($config['margins'])) {
            $pandocArgs[] = "-V";
            $pandocArgs[] = "geometry:margin=1in";
        }
        
        // Set page style based on options
        if (isset($config['header_footer'])) {
            $pandocArgs[] = "-V";
            $pandocArgs[] = "pagestyle=headings";
        
                $pagestyleSet = true;
                        $pandocArgs[] = "pagestyle=plain";
            $pagestyleSet = true;
        } elseif (isset($config['page_numbers'])) {
            $pandocArgs[] = "-V";
            
        }
        
        // Add chapter breaks for PDF
        if ($chapterBreak) {
            $pandocArgs[] = "-V";
            
        } else {
            // Ensure a pagestyle is set even if chapterBreak is not enabled but page_numbers is
            if (isset($config['page_numbers'])) {
                $pandocArgs[] = "-V";
                $pandocArgs[] = "pagestyle=plain";
            }
        }
        
        // Add chapter breaks for PDF with compatibility for eisvogel template
        if ($chapterBreak) {
            $pandocArgs[] = "-V";
            $pandocArgs[] = "documentclass=book";
            // Ensure eisvogel template works with book class by setting frontmatter support
            if ($template === 'eisvogel') {
                $pandocArgs[] = "-V";
                $pandocArgs[] = "titlepage=true";
                $pandocArgs[] = "-V";
                $pandocArgs[] = "frontmatter=false";
                $pandocArgs[] = "-V";
                $pandocArgs[] = "book";
                $latexHeader = "\\providecommand{\\frontmatter}{}\n" .
                    "\\providecommand{\\mainmatter}{}\n" .
                    "\\providecommand{\\backmatter}{}";
                $headerFile = $outputDir . $timestamp . '_custom_header.tex';
                file_put_contents($headerFile, $latexHeader);
                $pandocArgs[] = "--include-in-header=" . escapeshellarg($headerFile);
            }
            $pandocArgs[] = "-V";
            $pandocArgs[] = "classoption=openany";
        }
        
        // Handle PDF cover page
        if ($coverFile !== null) {
            // Create a LaTeX title page with the cover image
            $titlePageContent = "---\n";
            $titlePageContent .= "title: \"" . str_replace('"', '\"', $title) . "\"\n";
            $titlePageContent .= "author: \"" . str_replace('"', '\"', $author) . "\"\n";
            $titlePageContent .= "titlepage: true\n";
            $titlePageContent .= "titlepage-background: \"" . str_replace('"', '\"', $coverFile) . "\"\n";
            $titlePageContent .= "titlepage-rule-height: 0\n";
            $titlePageContent .= "titlepage-text-color: \"FFFFFF\"\n";
            $titlePageContent .= "---\n\n";
            
            // Prepend title page content to the source file
            $sourceContent = file_get_contents($sourceFile);
            file_put_contents($sourceFile, $titlePageContent . $sourceContent);
        }
    }

    if ($outputFormat === 'epub') {
        $pandocArgs[] = "--epub-chapter-level=2";
        if ($chapterBreak) {
            $pandocArgs[] = "--epub-chapter-level=1";
        }
    }

    $command = $pandocCmd . " " . implode(" ", $pandocArgs) . " 2>&1";
    $output = shell_exec($command);

    if (!file_exists($outputFile)) {
        throw new Exception("Konwersja nie powiodła się. Szczegóły: " . $output);
    }

    // Clean up
    @unlink($sourceFile);
    if (isset($mergedFile)) {
        @unlink($mergedFile);
    }
    if (isset($coverFile)) {
        @unlink($coverFile);
    }

    $downloadFile = basename($outputFile);
    $_SESSION['success'] = true;
    $_SESSION['file'] = $downloadFile;
    header("Location: index.php");
    exit;

} catch (Exception $e) {
    // Clean up on error
    if (isset($uploadedFiles)) {
        foreach ($uploadedFiles as $file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        }
    }
    if (isset($mergedFile) && file_exists($mergedFile)) {
        @unlink($mergedFile);
    }
    if (isset($sourceFile) && file_exists($sourceFile)) {
        @unlink($sourceFile);
    }
    if (isset($coverFile) && file_exists($coverFile)) {
        @unlink($coverFile);
    }
    
    $_SESSION['error'] = $e->getMessage();
    header("Location: index.php");
    exit;
}
?>