<?php
session_start();
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pandoc E-book Generator</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Generator E-booków</h1>
        <div class="main-content">
            <div class="upload-section">
            <form action="convert.php" method="post" enctype="multipart/form-data">
                <div class="form-section">
                    <div class="form-section-title">Pliki źródłowe</div>
                <div class="form-group">
                    <label for="file">Wybierz pliki (rozdziały):</label>
                    <input type="file" name="source_files[]" id="file" multiple required accept=".txt,.md,.html,.docx,.odt,.tex,.rst">
                    <small>Obsługiwane formaty: TXT, Markdown, HTML, DOCX, ODT, LaTeX, reStructuredText</small>
                    <small>Możesz wybrać wiele plików - zostaną połączone jako rozdziały</small>
                </div>

                <div class="form-group">
                    <label for="output_format">Format wyjściowy:</label>
                    <select name="output_format" id="output_format" required>
                        <option value="pdf" selected>PDF</option>
                        <option value="epub">EPUB</option>
                        <option value="mobi">MOBI (Kindle)</option>
                        <option value="azw3">AZW3 (Kindle)</option>
                        <option value="fb2">FB2</option>
                    </select>
                </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Metadane dokumentu</div>

                <div class="form-group">
                    <label for="title">Tytuł książki:</label>
                    <input type="text" name="title" id="title" placeholder="Wprowadź tytuł">
                </div>

                <div class="form-group">
                    <label for="author">Autor:</label>
                    <input type="text" name="author" id="author" placeholder="Imię i nazwisko autora">
                </div>

                <div class="form-group">
                    <label for="date">Data:</label>
                    <input type="text" name="date" id="date" placeholder="np. 2024-01-15 lub Styczeń 2024">
                    <small>Możesz wpisać datę w dowolnym formacie lub pozostawić puste dla daty bieżącej</small>
                </div>
                <small>Jeśli pozostawisz puste, data nie zostanie dodana do metadanych.</small>
                <div class="form-group">
                    <label for="language">Język:</label>
                    <select name="language" id="language">
                        <option value="pl-PL">Polski (pl-PL)</option>
                        <option value="en-US">English (en-US)</option>
                        <option value="en-GB">English (en-GB)</option>
                        <option value="de-DE">Deutsch (de-DE)</option>
                        <option value="fr-FR">Français (fr-FR)</option>
                        <option value="es-ES">Español (es-ES)</option>
                        <option value="it-IT">Italiano (it-IT)</option>
                        <option value="pt-PT">Português (pt-PT)</option>
                        <option value="pt-BR">Português (pt-BR)</option>
                        <option value="ru-RU">Русский (ru-RU)</option>
                        <option value="ja-JP">日本語 (ja-JP)</option>
                        <option value="zh-CN">中文 (zh-CN)</option>
                        <option value="ko-KR">한국어 (ko-KR)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="cover_image">Okładka (opcjonalnie):</label>
                    <input type="file" name="cover_image" id="cover_image" accept="image/*">
                </div>
                </div>

                <div class="form-section">
                    <div class="form-section-title">Opcje dokumentu</div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="toc" value="1" checked>
                        Generuj spis treści
                    </label>
                </div>

                <div class="form-group">
                    <label>
                        <input type="checkbox" name="chapter_break" value="1">
                        Każdy rozdział od nowej strony (PDF/EPUB)
                    </label>
                </div>

                <div class="form-group">
                    <label for="template">Szablon dokumentu:</label>
                    <select name="template" id="template">
                        <option value="default">Domyślny</option>
                        <option value="eisvogel" selected>Eisvogel (profesjonalny PDF)</option>
                        <option value="academic">Akademicki</option>
                        <option value="novel">Powieść</option>
                        <option value="report">Raport</option>
                    </select>
                </div>
                </div>

                <div class="form-group template-config">
                    <h3>Konfiguracja szablonu:</h3>
                    
                    <!-- Layout i struktura -->
                    <fieldset>
                        <legend>Layout i struktura</legend>
                        <div class="config-grid">
                            <div class="config-item">
                                <label title="Numeruje sekcje i rozdziały (np. 1. Wprowadzenie, 1.1. Podrozdział)">
                                    <input type="checkbox" name="config[numbered_sections]" value="1">
                                    Numerowane sekcje
                                </label>
                            </div>
                            <div class="config-item">
                                <label title="Wyświetla numery stron u dołu strony">
                                    <input type="checkbox" name="config[page_numbers]" value="1" checked>
                                    Numery stron
                                </label>
                            </div>
                            <div class="config-item">
                                <label title="Dodaje nagłówki z tytułem rozdziału i stopki z numerami stron">
                                    <input type="checkbox" name="config[header_footer]" value="1">
                                    Nagłówki i stopki
                                </label>
                            </div>
                            <div class="config-item">
                                <label title="Układa tekst w dwóch kolumnach">
                                    <input type="checkbox" name="config[two_column]" value="1">
                                    Układ dwukolumnowy
                                </label>
                            </div>
                        </div>
                    </fieldset>
                    
                    <!-- Typografia -->
                    <fieldset>
                        <legend>Typografia</legend>
                        <div class="config-grid">
                            <div class="config-item">
                                <label for="font_size" title="Podstawowy rozmiar czcionki w dokumencie">Rozmiar czcionki:</label>
                                <select name="config[font_size]" id="font_size">
                                    <option value="10pt">10pt (mały)</option>
                                    <option value="11pt" selected>11pt (standardowy)</option>
                                    <option value="12pt">12pt (duży)</option>
                                    <option value="14pt">14pt (bardzo duży)</option>
                                </select>
                            </div>
                            <div class="config-item">
                                <label for="font_family" title="Czcionka używana w dokumencie (musi być zainstalowana w systemie)">Czcionka:</label>
                                <select name="config[font_family]" id="font_family">
                                    <option value="">Domyślna</option>
                                    <option value="Times New Roman">Times New Roman</option>
                                    <option value="Arial">Arial</option>
                                    <option value="Calibri">Calibri</option>
                                    <option value="Georgia">Georgia</option>
                                    <option value="Helvetica">Helvetica</option>
                                    <option value="DejaVu Serif">DejaVu Serif</option>
                                    <option value="Liberation Serif">Liberation Serif</option>
                                </select>
                            </div>
                            <div class="config-item">
                                <label for="line_spacing" title="Odstęp między liniami tekstu">Interlinia:</label>
                                <select name="config[line_spacing]" id="line_spacing">
                                    <option value="1">1.0 (pojedyncza)</option>
                                    <option value="1.15">1.15</option>
                                    <option value="1.5">1.5 (półtorej)</option>
                                    <option value="2">2.0 (podwójna)</option>
                                </select>
                            </div>
                            <div class="config-item">
                                <label title="Wcięcie pierwszej linii akapitu">
                                    <input type="checkbox" name="config[paragraph_indent]" value="1" checked>
                                    Wcięcie akapitów
                                </label>
                            </div>
                        </div>
                    </fieldset>
                    
                    <!-- Strona i marginesy -->
                    
                    <!-- Lista plików do reorder -->
                    <div id="fileList" style="display: none;">
                        <h3>Lista rozdziałów (przeciągnij, aby zmienić kolejność):</h3>
                        <ul id="chapterList" style="list-style-type: none; padding: 0; margin: 0;">
                        </ul>
                    </div>
                    
                    <script>
                        document.getElementById('file').addEventListener('change', function(e) {
                            const fileList = document.getElementById('fileList');
                            const chapterList = document.getElementById('chapterList');
                            chapterList.innerHTML = '';
                            for (let i = 0; i < e.target.files.length; i++) {
                                let li = document.createElement('li');
                                li.textContent = e.target.files[i].name;
                                li.draggable = true;
                                li.ondragstart = function(ev) { ev.dataTransfer.setData('text/plain', i); };
                                li.ondragover = function(ev) { ev.preventDefault(); };
                                li.ondrop = function(ev) {
                                    ev.preventDefault();
                                    let fromIndex = ev.dataTransfer.getData('text');
                                    let toIndex = i;
                                    let files = Array.from(e.target.files);
                                    let temp = files[fromIndex];
                                    files[fromIndex] = files[toIndex];
                                    files[toIndex] = temp;
                                    // Update the list visually
                                    chapterList.innerHTML = '';
                                    files.forEach((file, index) => {
                                        let newLi = document.createElement('li');
                                        newLi.textContent = file.name;
                                        newLi.draggable = true;
                                        newLi.ondragstart = function(ev) { ev.dataTransfer.setData('text/plain', index); };
                                        newLi.ondragover = function(ev) { ev.preventDefault(); };
                                        newLi.ondrop = function(ev) {
                                            ev.preventDefault();
                                            let fromIndex = ev.dataTransfer.getData('text');
                                            let toIndex = index;
                                            let temp = files[fromIndex];
                                            files[fromIndex] = files[toIndex];
                                            files[toIndex] = temp;
                                            chapterList.innerHTML = '';
                                            files.forEach((file, idx) => {
                                                let newLi2 = document.createElement('li');
                                                newLi2.textContent = file.name;
                                                newLi2.draggable = true;
                                                newLi2.ondragstart = function(ev) { ev.dataTransfer.setData('text/plain', idx); };
                                                newLi2.ondragover = function(ev) { ev.preventDefault(); };
                                                newLi2.ondrop = function(ev) { ev.preventDefault(); /* ... recursive logic ... */ };
                                                chapterList.appendChild(newLi2);
                                            });
                                        };
                                        chapterList.appendChild(newLi);
                                    });
                                };
                                chapterList.appendChild(li);
                            }
                            fileList.style.display = 'block';
                        });
                        
                        // Save settings to localStorage on form submit
                        const form = document.querySelector('form');
                        form.addEventListener('submit', function(e) {
                            let formData = new FormData(this);
                            let settings = {};
                            for (let [key, value] of formData.entries()) {
                                // Handle multiple values (like file inputs or multi-select, though not used here)
                                if (settings[key]) {
                                    if (!Array.isArray(settings[key])) {
                                        settings[key] = [settings[key]];
                                    }
                                    settings[key].push(value);
                                } else {
                                    settings[key] = value;
                                }
                            }
                            localStorage.setItem('ebookSettings', JSON.stringify(settings));
                        });
                        
                        // Load settings from localStorage on page load
                        window.onload = function() {
                            const savedSettings = localStorage.getItem('ebookSettings');
                            if (!savedSettings) return;
                            const settings = JSON.parse(savedSettings);
                            const inputs = form.querySelectorAll('input, select, textarea');
                            inputs.forEach(input => {
                                const name = input.name;
                                if (settings[name]) {
                                    if (input.type === 'checkbox' || input.type === 'radio') {
                                        input.checked = settings[name] === '1' || settings[name] === input.value;
                                    } else {
                                        input.value = settings[name];
                                    }
                                }
                            });
                        };
                    </script>
                    <fieldset>
                        <legend>Strona i marginesy</legend>
                        <div class="config-grid">
                            <div class="config-item">
                                <label for="paper_size" title="Format papieru dla dokumentu PDF">Format papieru:</label>
                                <select name="config[paper_size]" id="paper_size">
                                    <option value="a4paper">A4 (210×297 mm)</option>
                                    <option value="letterpaper">Letter (8.5×11 in)</option>
                                    <option value="a5paper">A5 (148×210 mm)</option>
                                    <option value="b5paper">B5 (176×250 mm)</option>
                                    <option value="legalpaper">Legal (8.5×14 in)</option>
                                </select>
                            </div>
                            <div class="config-item">
                                <label for="margins" title="Wielkość marginesów wokół tekstu">Marginesy:</label>
                                <select name="config[margins]" id="margins">
                                    <option value="narrow">Wąskie (1.27 cm)</option>
                                    <option value="normal" selected>Normalne (2.54 cm)</option>
                                    <option value="wide">Szerokie (3.81 cm)</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>
                    
                    <!-- Spis treści -->
                    <fieldset>
                        <legend>Spis treści</legend>
                        <div class="config-grid">
                            <div class="config-item">
                                <label for="toc_depth" title="Ile poziomów nagłówków uwzględnić w spisie treści">Głębokość spisu:</label>
                                <select name="config[toc_depth]" id="toc_depth">
                                    <option value="1">1 poziom (tylko rozdziały)</option>
                                    <option value="2">2 poziomy</option>
                                    <option value="3" selected>3 poziomy</option>
                                    <option value="4">4 poziomy</option>
                                    <option value="5">5 poziomów</option>
                                </select>
                            </div>
                            <div class="config-item">
                                <label title="Generuje spis treści zawierający tylko nazwy rozdziałów wraz z numerami stron">
                                    <input type="checkbox" name="config[chapters_only_toc]" value="1">
                                    Spis tylko z rozdziałami i numerami stron
                                </label>
                            </div>
                        </div>
                    </fieldset>
                    
                    <!-- Kod i składnia -->
                    <fieldset>
                        <legend>Kod i składnia</legend>
                        <div class="config-grid">
                            <div class="config-item">
                                <label for="syntax_highlighting" title="Styl kolorowania składni kodu">Kolorowanie składni:</label>
                                <select name="config[syntax_highlighting]" id="syntax_highlighting">
                                    <option value="">Brak</option>
                                    <option value="pygments">Pygments (domyślny)</option>
                                    <option value="tango">Tango</option>
                                    <option value="espresso">Espresso</option>
                                    <option value="zenburn">Zenburn</option>
                                    <option value="kate">Kate</option>
                                    <option value="monochrome">Monochromatyczny</option>
                                    <option value="breezedark">Breeze Dark</option>
                                    <option value="haddock">Haddock</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>
                    
                    <!-- Linki i odnośniki -->
                    <fieldset>
                        <legend>Linki i odnośniki</legend>
                        <div class="config-grid">
                            <div class="config-item">
                                <label for="link_color" title="Kolor linków w dokumencie PDF">Kolor linków:</label>
                                <select name="config[link_color]" id="link_color">
                                    <option value="">Domyślny (niebieski)</option>
                                    <option value="black">Czarny</option>
                                    <option value="red">Czerwony</option>
                                    <option value="blue">Niebieski</option>
                                    <option value="green">Zielony</option>
                                    <option value="magenta">Magenta</option>
                                    <option value="cyan">Cyjan</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>
                    
                    <!-- Matematyka -->
                    <fieldset>
                        <legend>Matematyka</legend>
                        <div class="config-grid">
                            <div class="config-item">
                                <label for="math_rendering" title="Sposób renderowania wzorów matematycznych">Renderowanie wzorów:</label>
                                <select name="config[math_rendering]" id="math_rendering">
                                    <option value="">Domyślne</option>
                                    <option value="mathjax">MathJax (HTML)</option>
                                    <option value="mathml">MathML</option>
                                    <option value="webtex">WebTeX (obrazki)</option>
                                    <option value="katex">KaTeX</option>
                                </select>
                            </div>
                        </div>
                    </fieldset>
                    
                    <!-- Bibliografia -->
                    <fieldset>
                        <legend>Bibliografia</legend>
                        <div class="config-grid">
                            <div class="config-item full-width">
                                <label for="bibliography" title="Ścieżka do pliku BibTeX/BibLaTeX z bibliografią">Plik bibliografii (.bib):</label>
                                <input type="text" name="config[bibliography]" id="bibliography" placeholder="np. bibliografia.bib">
                            </div>
                        </div>
                    </fieldset>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-convert">Konwertuj na E-book</button>
                </div>
            </form>
            </div>
        </div>

        <?php if (isset($_SESSION['success']) && isset($_SESSION['file'])): ?>
            <div class="alert success">
                E-book został wygenerowany pomyślnie! 
                <form action="download.php" method="post" style="display: inline;">
                    <input type="hidden" name="file" value="<?php echo htmlspecialchars($_SESSION['file']); ?>">
                    <button type="submit" class="download-link" style="background: none; border: none; color: #007bff; text-decoration: underline; cursor: pointer;">Pobierz plik</button>
                </form>
            </div>
            <?php 
                unset($_SESSION['success']);
                unset($_SESSION['file']);
            ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert error">
                Błąd: <?php echo htmlspecialchars($_SESSION['error']); ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </div>
</body>
</html>