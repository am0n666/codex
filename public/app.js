let chapterCount = 1;

document.getElementById('add-chapter').addEventListener('click', function() {
    const chaptersDiv = document.getElementById('chapters');
    const chapterDiv = document.createElement('div');
    chapterDiv.className = 'chapter';
    chapterDiv.innerHTML = `
        <label>Tytuł rozdziału: <input type="text" name="chapters[${chapterCount}][title]" value="Rozdział ${chapterCount+1}"></label><br>
        <label>Treść:<br>
            <textarea name="chapters[${chapterCount}][content]" rows="6" cols="50">Wpisz treść rozdziału...</textarea>
        </label>
        <input type="file" accept=".md" style="display:none" onchange="readMarkdown(this)">
        <button type="button" onclick="this.previousElementSibling.click()">Wczytaj Markdown</button>
        <button type="button" class="remove-chapter" onclick="removeChapter(this)">Usuń rozdział</button>
    `;
    chaptersDiv.appendChild(chapterDiv);
    chapterCount++;
});

function removeChapter(btn) {
    btn.parentNode.remove();
}

function readMarkdown(input) {
    if (!input.files.length) return;
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
        const textarea = input.parentNode.querySelector('textarea');
        if (textarea) textarea.value = e.target.result;
    };
    reader.readAsText(file);
}

// --- Zapisywanie/Wczytywanie projektu w LocalStorage ---
function saveProject() {
    const form = document.getElementById('ebook-form');
    const data = new FormData(form);
    const obj = {};
    for (const [k, v] of data.entries()) {
        if (k.endsWith(']')) {
            const match = k.match(/^chapters\[(\d+)\]\[(title|content)\]$/);
            if (match) {
                const idx = match[1];
                const field = match[2];
                if (!obj.chapters) obj.chapters = {};
                if (!obj.chapters[idx]) obj.chapters[idx] = {};
                obj.chapters[idx][field] = v;
            }
        } else {
            obj[k] = v;
        }
    }
    localStorage.setItem('ebook-project', JSON.stringify(obj));
    alert('Projekt zapisany w pamięci przeglądarki!');
}

function loadProject() {
    const obj = JSON.parse(localStorage.getItem('ebook-project') || '{}');
    if (!obj || !obj.title) {
        alert("Brak projektu do wczytania.");
        return;
    }
    if (obj.title) document.getElementById('title').value = obj.title;
    if (obj.author) document.getElementById('author').value = obj.author;
    if (obj.template) document.getElementById('template').value = obj.template;
    if (obj.style) document.getElementById('style').value = obj.style;
    if (obj.pdf_format) document.getElementById('pdf_format').value = obj.pdf_format;
    if (obj.pdf_orientation) document.getElementById('pdf_orientation').value = obj.pdf_orientation;
    if (obj.pdf_margin) document.getElementById('pdf_margin').value = obj.pdf_margin;
    if (obj.chapters) {
        const chaptersDiv = document.getElementById('chapters');
        chaptersDiv.innerHTML = '';
        let i = 0;
        for (const idx in obj.chapters) {
            const ch = obj.chapters[idx];
            const chapterDiv = document.createElement('div');
            chapterDiv.className = 'chapter';
            chapterDiv.innerHTML = `
                <label>Tytuł rozdziału: <input type="text" name="chapters[${i}][title]" value="${ch.title}"></label><br>
                <label>Treść:<br>
                    <textarea name="chapters[${i}][content]" rows="6" cols="50">${ch.content}</textarea>
                </label>
                <input type="file" accept=".md" style="display:none" onchange="readMarkdown(this)">
                <button type="button" onclick="this.previousElementSibling.click()">Wczytaj Markdown</button>
                <button type="button" class="remove-chapter" onclick="removeChapter(this)">Usuń rozdział</button>
            `;
            chaptersDiv.appendChild(chapterDiv);
            i++;
        }
        chapterCount = i;
    }
}

// --- Eksport/Import projektu jako plik JSON ---
function exportProject() {
    const form = document.getElementById('ebook-form');
    const data = new FormData(form);
    const obj = {};
    for (const [k, v] of data.entries()) {
        if (k.endsWith(']')) {
            const match = k.match(/^chapters\[(\d+)\]\[(title|content)\]$/);
            if (match) {
                const idx = match[1];
                const field = match[2];
                if (!obj.chapters) obj.chapters = {};
                if (!obj.chapters[idx]) obj.chapters[idx] = {};
                obj.chapters[idx][field] = v;
            }
        } else {
            obj[k] = v;
        }
    }
    const blob = new Blob([JSON.stringify(obj, null, 2)], {type: "application/json"});
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'ebook-project.json';
    link.click();
}

function importProject(event) {
    const input = event.target;
    if (!input.files.length) return;
    const file = input.files[0];
    const reader = new FileReader();
    reader.onload = function(e) {
        try {
            const obj = JSON.parse(e.target.result);
            localStorage.setItem('ebook-project', JSON.stringify(obj));
            loadProject();
            alert('Projekt zaimportowany!');
        } catch (err) {
            alert('Błąd importu: nieprawidłowy plik JSON.');
        }
    };
    reader.readAsText(file);
}

document.addEventListener('DOMContentLoaded', function() {
    const panel = document.createElement('div');
    panel.style.marginBottom = "12px";
    panel.innerHTML = `
        <button type="button" onclick="saveProject()">💾 Zapisz projekt</button>
        <button type="button" onclick="loadProject()">📂 Wczytaj projekt</button>
        <button type="button" onclick="exportProject()">⬇️ Eksportuj projekt</button>
        <label style="margin-left:8px;cursor:pointer;">⬆️ Importuj projekt
            <input type="file" id="import-file" style="display:none;">
        </label>
    `;
    document.getElementById('ebook-form').insertBefore(panel, document.getElementById('ebook-form').firstChild);

    document.getElementById('import-file').addEventListener('change', importProject);

    if (localStorage.getItem('ebook-project')) {
        if (confirm('Wczytać ostatnio zapisany projekt?')) {
            loadProject();
        }
    }
});

function generatePreview() {
    const form = document.getElementById('ebook-form');
    const data = new FormData(form);

    fetch('preview.php', {
        method: 'POST',
        body: data
    })
    .then(response => {
        if (response.ok) return response.blob();
        else throw new Error('Błąd generowania podglądu');
    })
    .then(blob => {
        document.getElementById('pdf-preview').src = URL.createObjectURL(blob);
    });
}

document.getElementById('ebook-form').addEventListener('submit', function(e) {
    e.preventDefault();
    const form = e.target;
    const data = new FormData(form);
    fetch('download.php', {
        method: 'POST',
        body: data
    })
    .then(response => response.blob())
    .then(blob => {
        const link = document.createElement('a');
        link.href = window.URL.createObjectURL(blob);
        link.download = 'ebook.pdf';
        link.click();
    });
});