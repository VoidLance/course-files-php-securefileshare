<section class="grid two-up">
    <article class="card">
        <h2>Hello, <?= htmlspecialchars((string) ($user['name'] ?? 'User')) ?></h2>
        <p>Role: <strong><?= htmlspecialchars((string) ($user['role'] ?? 'regular')) ?></strong></p>
        <?php $percent = $quota > 0 ? min(100, (int) round(($usage / $quota) * 100)) : 0; ?>
        <p>Storage usage: <strong><?= number_format($usage / 1024 / 1024, 2) ?> MB</strong> / <?= number_format($quota / 1024 / 1024, 2) ?> MB</p>
        <div class="progress"><span style="width: <?= $percent ?>%"></span></div>
        <small><?= $percent ?>% used. Storage goblins are watching.</small>
    </article>

    <article class="card">
        <h2>Quick Stats</h2>
        <ul>
            <li>Total files: <?= count($files) ?></li>
            <li>Active shares: <?= count(array_filter($shares, static fn ($s) => $s['revoked_at'] === null)) ?></li>
            <li>Recent activities: <?= count($activities) ?></li>
        </ul>
        <a class="button-link" href="<?= rtrim((string) app_config('base_url'), '/') ?>/files">Manage Files</a>
    </article>
</section>

<section class="card">
    <h2>Recent Activity</h2>
    <?php if (!$activities): ?>
        <p>No activity yet. Suspiciously quiet.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>Event</th>
                <th>Description</th>
                <th>Time</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($activities as $activity): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $activity['event_type']) ?></td>
                    <td><?= htmlspecialchars((string) $activity['description']) ?></td>
                    <td><?= htmlspecialchars((string) $activity['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>

<section class="card">
    <h2>Share Links</h2>
    <?php if (!$shares): ?>
        <p>No links yet. Go share something amazing.</p>
    <?php else: ?>
        <table>
            <thead>
            <tr>
                <th>File</th>
                <th>Permission</th>
                <th>Expires</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($shares as $share): ?>
                <?php
                $isRevoked = $share['revoked_at'] !== null;
                $isExpired = strtotime((string) $share['expires_at']) < time();
                $status = $isRevoked ? 'revoked' : ($isExpired ? 'expired' : 'active');
                ?>
                <tr>
                    <td><?= htmlspecialchars((string) $share['original_name']) ?></td>
                    <td><?= htmlspecialchars((string) $share['permission']) ?></td>
                    <td><?= htmlspecialchars((string) $share['expires_at']) ?></td>
                    <td><?= htmlspecialchars($status) ?></td>
                    <td>
                        <a href="<?= rtrim((string) app_config('base_url'), '/') ?>/shared?token=<?= urlencode((string) $share['token']) ?>" target="_blank">Open</a>
                        <?php if (!$isRevoked): ?>
                            <form action="<?= rtrim((string) app_config('base_url'), '/') ?>/shares/revoke" method="post" class="inline-form">
                                <input type="hidden" name="_token" value="<?= htmlspecialchars(App\Core\Csrf::token()) ?>">
                                <input type="hidden" name="share_id" value="<?= (int) $share['id'] ?>">
                                <button type="submit">Revoke</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</section>
