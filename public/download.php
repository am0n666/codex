<?php

declare(strict_types=1);
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/Parsedown.php';
use Dompdf\Dompdf;

// Współdzielona funkcja z preview.php
function render_ebook_html($data, $coverTmpPath = null) {
    $template = $data['template'] ?? 'classic';
    $style = $data['style'] ?? 'light';
    $title = htmlspecialchars($data['title'] ?? 'E-book');
    $author = htmlspecialchars($data['author'] ?? 'Autor');
    $chapters = $data['chapters'] ?? [];
    $cover = $coverTmpPath;

    $styleBlock = '';
    if ($style === 'dark') {
        $styleBlock = 'body{background:#222;color:#eee;}h1,h2{color:#8bd;}';
    }

    $coverHtml = $cover ? '<div style="text-align:center;"><img src="data:image/*;base64,'.base64_encode(file_get_contents($cover)).'" style="max-width:80%;max-height:400px;margin-bottom:16px;"></div>' : '';

    // --- Spis treści ---
    $tocHtml = "<h2>Spis treści</h2><ul>";
    foreach ($chapters as $i => $ch) {
        $chapterTitle = htmlspecialchars($ch['title'] ?? '');
        $tocHtml .= "<li><a href=\"#ch{$i}\">{$chapterTitle}</a></li>";
    }
    $tocHtml .= "</ul>";

    // --- Rozdziały z anchorami ---
    $chaptersHtml = '';
    $md = new Parsedown();
    $md->setSafeMode(true);
    foreach ($chapters as $i => $ch) {
        $chapterTitle = htmlspecialchars($ch['title'] ?? '');
        $chapterContent = $md->text($ch['content'] ?? '');
        $chaptersHtml .= "<h2 id=\"ch{$i}\">{$chapterTitle}</h2><div>{$chapterContent}</div><hr>";
    }

    if ($template === 'modern') {
        $templateHtml = "
            <style>{$styleBlock} h1{font-family:sans-serif;color:#39c;} h2{border-bottom:1px solid #ccc;}</style>
            {$coverHtml}
            <h1>{$title}</h1>
            <h3>{$author}</h3>
            {$tocHtml}
            <div>{$chaptersHtml}</div>
        ";
    } else {
        $templateHtml = "
            <style>{$styleBlock} h1{font-family:serif;color:#333;} h2{font-style:italic;border-left:4px solid #ccc;padding-left:6px;}</style>
            {$coverHtml}
            <h1>{$title}</h1>
            <h3>{$author}</h3>
            {$tocHtml}
            <div>{$chaptersHtml}</div>
        ";
    }
    return $templateHtml;
}

$data = $_POST;
$coverTmpPath = null;
if (!empty($_FILES['cover']['tmp_name'])) {
    $coverTmpPath = $_FILES['cover']['tmp_name'];
}

$html = render_ebook_html($data, $coverTmpPath);

$pdf_format = $_POST['pdf_format'] ?? 'A4';
$pdf_orientation = $_POST['pdf_orientation'] ?? 'portrait';

$dompdf = new Dompdf();
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper($pdf_format, $pdf_orientation);
$dompdf->render();

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="ebook.pdf"');
echo $dompdf->output();
