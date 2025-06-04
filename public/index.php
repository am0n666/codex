<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

$templates = [
    "classic" => "Klasyczny szablon",
    "modern" => "Nowoczesny szablon"
];

$styles = [
    "light" => "Jasny",
    "dark" => "Ciemny"
];

?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zaawansowany Kreator e-booków PDF</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Zaawansowany Kreator e-booków PDF</h1>
    <div id="ebook-ui">
        <form id="ebook-form" enctype="multipart/form-data">
            <fieldset>
                <legend>Dane e-booka</legend>
                <label>Tytuł: <input type="text" name="title" id="title" value="Mój e-book"></label><br>
                <label>Autor: <input type="text" name="author" id="author" value="am0n666"></label><br>
                <label>Szablon: 
                    <select name="template" id="template">
                        <?php foreach($templates as $key => $desc): ?>
                        <option value="<?= $key ?>"><?= $desc ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>Styl: 
                    <select name="style" id="style">
                        <?php foreach($styles as $key => $desc): ?>
                        <option value="<?= $key ?>"><?= $desc ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </fieldset>

            <fieldset>
                <legend>Okładka</legend>
                <input type="file" name="cover" id="cover" accept="image/*">
            </fieldset>
            
            <fieldset>
                <legend>Rozdziały</legend>
                <div id="chapters">
                    <div class="chapter">
                        <label>Tytuł rozdziału: <input type="text" name="chapters[0][title]" value="Rozdział 1"></label><br>
                        <label>Treść:<br>
                            <textarea name="chapters[0][content]" rows="6" cols="50">Wpisz treść rozdziału...</textarea>
                        </label>
                        <button type="button" class="remove-chapter" onclick="removeChapter(this)">Usuń rozdział</button>
                    </div>
                </div>
                <button type="button" id="add-chapter">Dodaj rozdział</button>
            </fieldset>

            <fieldset>
                <legend>Zaawansowane ustawienia PDF</legend>
                <label>Format strony: 
                    <select name="pdf_format" id="pdf_format">
                        <option value="A4">A4</option>
                        <option value="A5">A5</option>
                        <option value="Letter">Letter</option>
                    </select>
                </label>
                <label>Orientacja: 
                    <select name="pdf_orientation" id="pdf_orientation">
                        <option value="portrait">Pionowa</option>
                        <option value="landscape">Pozioma</option>
                    </select>
                </label>
                <label>Marginesy: <input type="text" name="pdf_margin" id="pdf_margin" value="10"></label>
            </fieldset>

            <!-- Panel do zapisu/eksportu/importu zostanie dodany przez JS -->
            <button type="button" onclick="generatePreview()">Podgląd PDF</button>
            <button type="submit">Pobierz PDF</button>
        </form>
        <h2>Podgląd na żywo</h2>
        <iframe id="pdf-preview" width="500" height="700"></iframe>
    </div>
    <script src="app.js"></script>
</body>
</html>