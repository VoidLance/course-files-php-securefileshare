// Tiny front-end helper for drag/drop and upload progress.
(() => {
    const form = document.getElementById('uploadForm');
    const dropZone = document.getElementById('dropZone');
    const fileInput = document.getElementById('fileInput');
    const progress = document.getElementById('uploadProgress');

    if (!form || !dropZone || !fileInput || !progress) {
        return;
    }

    dropZone.addEventListener('click', () => fileInput.click());

    ['dragenter', 'dragover'].forEach((eventName) => {
        dropZone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropZone.classList.add('drag-over');
        });
    });

    ['dragleave', 'drop'].forEach((eventName) => {
        dropZone.addEventListener(eventName, (event) => {
            event.preventDefault();
            dropZone.classList.remove('drag-over');
        });
    });

    dropZone.addEventListener('drop', (event) => {
        const files = event.dataTransfer?.files;
        if (files && files.length > 0) {
            fileInput.files = files;
        }
    });

    form.addEventListener('submit', (event) => {
        event.preventDefault();

        const formData = new FormData(form);
        const request = new XMLHttpRequest();

        request.open('POST', form.action, true);

        request.upload.addEventListener('progress', (uploadEvent) => {
            if (!uploadEvent.lengthComputable) {
                return;
            }

            const percent = Math.round((uploadEvent.loaded / uploadEvent.total) * 100);
            progress.value = percent;
        });

        request.onload = () => {
            // Reload page to pick up flash message + updated file table.
            window.location.reload();
        };

        request.onerror = () => {
            alert('Upload failed. The network goblins may be back.');
        };

        request.send(formData);
    });
})();

// Share-link helper: gives immediate copy action after link creation.
(() => {
    const copyButton = document.getElementById('copyShareLinkButton');
    const shareLinkField = document.getElementById('shareLinkField');

    if (!copyButton || !shareLinkField) {
        return;
    }

    copyButton.addEventListener('click', async () => {
        const linkValue = shareLinkField.value;

        try {
            await navigator.clipboard.writeText(linkValue);
            copyButton.textContent = 'Copied';
            setTimeout(() => {
                copyButton.textContent = 'Copy';
            }, 1200);
        } catch (_error) {
            shareLinkField.select();
            document.execCommand('copy');
            copyButton.textContent = 'Copied';
            setTimeout(() => {
                copyButton.textContent = 'Copy';
            }, 1200);
        }
    });
})();
