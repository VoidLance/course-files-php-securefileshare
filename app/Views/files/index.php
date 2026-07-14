<?php use App\Core\Csrf; ?>
<section class="card">
    <h1>My Files</h1>

    <form action="<?= rtrim((string) app_config('base_url'), '/') ?>/files" method="get" class="row-form">
        <input type="text" name="q" value="<?= htmlspecialchars((string) ($query ?? '')) ?>" placeholder="Search name, folder, tags...">
        <button type="submit">Search</button>
    </form>

    <form id="uploadForm" action="<?= rtrim((string) app_config('base_url'), '/') ?>/files/upload" method="post" enctype="multipart/form-data" class="stack">
        <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">

        <label>Folder Name</label>
        <input type="text" name="folder_name" value="root" required>

        <label>Tags (comma separated)</label>
        <input type="text" name="tags" placeholder="work,report,pdf">

        <!-- Drag and drop zone because clicking file inputs is so 2010. -->
        <div id="dropZone" class="drop-zone">
            <p>Drop file here or click to select</p>
            <input id="fileInput" type="file" name="file" required>
        </div>

        <progress id="uploadProgress" value="0" max="100">0%</progress>
        <button type="submit">Upload File</button>
    </form>
</section>

<section class="card">
    <h2>Stored Files</h2>
    <?php if (!$files): ?>
        <p>No files uploaded yet. This shelf is empty.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>Name</th>
                <th>Folder</th>
                <th>Type</th>
                <th>Size</th>
                <th>Tags</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($files as $file): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $file['original_name']) ?></td>
                    <td><?= htmlspecialchars((string) $file['folder_name']) ?></td>
                    <td><?= htmlspecialchars((string) $file['mime_type']) ?></td>
                    <td><?= number_format(((int) $file['size_bytes']) / 1024, 2) ?> KB</td>
                    <td><?= htmlspecialchars((string) ($file['tags'] ?? '-')) ?></td>
                    <td>
                        <a href="<?= rtrim((string) app_config('base_url'), '/') ?>/files/download?id=<?= (int) $file['id'] ?>">Download</a>
                        <form action="<?= rtrim((string) app_config('base_url'), '/') ?>/shares/create" method="post" class="inline-form">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars(Csrf::token()) ?>">
                            <input type="hidden" name="file_id" value="<?= (int) $file['id'] ?>">
                            <input type="number" name="expires_in_days" value="7" min="1" max="30" title="Days">
                            <select name="permission">
                                <option value="view">View</option>
                                <option value="download">Download</option>
                                <option value="edit">Edit</option>
                            </select>
                            <input type="text" name="share_password" placeholder="Optional password">
                            <button type="submit">Create Share Link</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
