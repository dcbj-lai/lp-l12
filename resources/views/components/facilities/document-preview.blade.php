<dialog id="facility-document-preview" class="facility-document-dialog" aria-labelledby="facility-preview-title">
    <header class="flex flex-wrap items-center justify-between gap-3 border-b p-4 dark:border-zinc-700">
        <h2 id="facility-preview-title" class="font-semibold">Floor plan</h2>
        <div class="flex items-center gap-3"><a data-preview-download class="text-sm text-blue-700 underline dark:text-blue-300">Download</a><button type="button" data-preview-close class="rounded-md border px-3 py-2">Close</button></div>
    </header>
    <p data-preview-unsupported class="p-5" hidden>Preview is unavailable for this file type. Use Download to open it.</p>
    <p data-preview-message class="p-5" role="status" hidden></p>
    <iframe data-preview-pdf title="Floor plan document" class="facility-document-frame" hidden></iframe>
    <img data-preview-image alt="Reservation floor plan" class="facility-document-image" hidden>
</dialog>
<script>
if (!window.facilityPreviewInstalled) {
    window.facilityPreviewInstalled = true;
    let requestController, objectUrl;
    function clearPreview(dialog) {
        requestController?.abort();
        if (objectUrl) URL.revokeObjectURL(objectUrl);
        objectUrl = null;
        dialog.querySelectorAll('iframe,img').forEach(element => element.removeAttribute('src'));
    }
    document.addEventListener('click', async function (event) {
        const dialog = document.getElementById('facility-document-preview');
        if (!dialog) return;
        const trigger = event.target.closest('[data-floor-plan-preview]');
        if (trigger) {
            clearPreview(dialog);
            dialog.querySelector('#facility-preview-title').textContent = 'Floor plan · ' + trigger.dataset.title;
            const download = dialog.querySelector('[data-preview-download]');
            download.href = trigger.dataset.url + '?download=1'; download.hidden = false;
            const pdf = dialog.querySelector('[data-preview-pdf]'), image = dialog.querySelector('[data-preview-image]');
            const message = dialog.querySelector('[data-preview-message]');
            pdf.hidden = true; image.hidden = true; message.hidden = true;
            dialog.querySelector('[data-preview-unsupported]').hidden = trigger.dataset.type !== 'download';
            dialog.showModal();
            if (trigger.dataset.type !== 'download') {
                message.textContent = 'Loading floor plan…'; message.hidden = false;
                const controller = new AbortController(); requestController = controller;
                try {
                    const response = await fetch(trigger.dataset.url, {credentials:'same-origin', cache:'no-store', signal:controller.signal});
                    if (!response.ok) throw new Error('Unavailable');
                    const mime = response.headers.get('Content-Type') || '';
                    if (trigger.dataset.type === 'pdf' ? !mime.startsWith('application/pdf') : !mime.startsWith('image/')) throw new Error('Unsupported');
                    const file = await response.blob();
                    if (!file.size) throw new Error('Empty');
                    if (controller.signal.aborted || !dialog.open) return;
                    objectUrl = URL.createObjectURL(file);
                    const preview = trigger.dataset.type === 'pdf' ? pdf : image;
                    preview.src = objectUrl; preview.hidden = false; message.hidden = true;
                } catch (error) {
                    if (error.name === 'AbortError') return;
                    message.textContent = 'This floor plan is unavailable. Please contact Life College to check the attachment.';
                    download.hidden = true;
                }
            }
        }
        if (event.target.closest('[data-preview-close]')) dialog.close();
    });
    document.addEventListener('close', function(event) {
        if (event.target.id === 'facility-document-preview') clearPreview(event.target);
    }, true);
}
</script>
