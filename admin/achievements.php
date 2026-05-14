<?php
require_once __DIR__ . '/../core/functions.php';
require_admin($conn);
$pageTitle = 'Admin Achievements';

$editId = (int) ($_GET['edit'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');

        if ($id > 0 && $name !== '' && $description !== '') {
            $stmt = mysqli_prepare($conn, 'UPDATE achievements SET name = ?, description = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'ssi', $name, $description, $id);
            mysqli_stmt_execute($stmt);
            set_flash('success', 'Achievement updated.');
        } else {
            set_flash('error', 'Could not update achievement.');
        }

        redirect_to('admin/achievements.php');
    }

}

$achievementsResult = mysqli_query($conn, 'SELECT id, code, name, description FROM achievements ORDER BY id ASC');
$editAchievement = null;

if ($editId > 0) {
    $stmt = mysqli_prepare($conn, 'SELECT id, code, name, description FROM achievements WHERE id = ? LIMIT 1');
    mysqli_stmt_bind_param($stmt, 'i', $editId);
    mysqli_stmt_execute($stmt);
    $editAchievement = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

require_once __DIR__ . '/../core/header.php';
?>
<section class="split-card">
    <article class="card">
        <h1>Achievements</h1>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($achievement = mysqli_fetch_assoc($achievementsResult)): ?>
                        <tr>
                            <td><?php echo (int) $achievement['id']; ?></td>
                            <td><?php echo esc($achievement['code']); ?></td>
                            <td><?php echo esc($achievement['name']); ?></td>
                            <td>
                                <a class="btn secondary" href="<?php echo esc(app_url('admin/achievements.php?edit=' . (int) $achievement['id'])); ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="card form-card">
        <?php if ($editAchievement): ?>
            <h2>Edit Achievement</h2>
            <p class="muted">Only the title and description can be edited here. The achievement code stays fixed.</p>

            <form method="post" class="stack-form">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo (int) $editAchievement['id']; ?>">

                <label>Code</label>
                <input value="<?php echo esc($editAchievement['code']); ?>" disabled>

                <label>Title</label>
                <input name="name" value="<?php echo esc($editAchievement['name']); ?>" required>

                <label>Description</label>
                <textarea name="description" rows="8" required><?php echo esc($editAchievement['description']); ?></textarea>

                <div class="button-row">
                    <button class="btn" type="submit">Update Achievement</button>
                    <a class="btn ghost" href="<?php echo esc(app_url('admin/achievements.php')); ?>">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <h2>Select an achievement</h2>
            <p class="muted">Choose an achievement from the list to edit it.</p>
        <?php endif; ?>
    </article>
</section>
<?php require_once __DIR__ . '/../core/footer.php'; ?>