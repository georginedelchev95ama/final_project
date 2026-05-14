<?php
require_once __DIR__ . '/../core/functions.php';
require_admin($conn);
$pageTitle = 'Admin Users';

$editId = (int) ($_GET['edit'] ?? 0);
$maxLevel = 8;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $points = max(0, (int) ($_POST['points'] ?? 0));
        $wins = max(0, (int) ($_POST['wins'] ?? 0));
        $gamesPlayed = max(0, (int) ($_POST['games_played'] ?? 0));
        $bestLevel = max(1, min($maxLevel, (int) ($_POST['best_level'] ?? 1)));
        $isAdmin = empty($_POST['is_admin']) ? 0 : 1;

        if ($username === '' || $password === '') {
            set_flash('error', 'Username and password are required.');
        } else {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = mysqli_prepare(
                $conn,
                'INSERT INTO users (username, password_hash, points, wins, games_played, best_level, title, is_admin)
                 VALUES (?, ?, ?, ?, ?, ?, "New Player", ?)'
            );
            mysqli_stmt_bind_param($stmt, 'ssiiiii', $username, $passwordHash, $points, $wins, $gamesPlayed, $bestLevel, $isAdmin);
            mysqli_stmt_execute($stmt);

            $newId = mysqli_insert_id($conn);

            if ($newId > 0) {
                update_user_title($conn, $newId);
                set_flash('success', 'User added.');
            } else {
                set_flash('error', 'Could not add user.');
            }
        }

        redirect_to('admin/users.php');
    }

    if ($action === 'update') {
        $id = (int) ($_POST['id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $points = max(0, (int) ($_POST['points'] ?? 0));
        $wins = max(0, (int) ($_POST['wins'] ?? 0));
        $gamesPlayed = max(0, (int) ($_POST['games_played'] ?? 0));
        $bestLevel = max(1, min($maxLevel, (int) ($_POST['best_level'] ?? 1)));
        $isAdmin = empty($_POST['is_admin']) ? 0 : 1;
        $newPassword = $_POST['password'] ?? '';

        if ($id > 0 && $username !== '') {
            if ($newPassword !== '') {
                $hash = password_hash($newPassword, PASSWORD_DEFAULT);

                $stmt = mysqli_prepare(
                    $conn,
                    'UPDATE users
                     SET username = ?, password_hash = ?, points = ?, wins = ?, games_played = ?, best_level = ?, is_admin = ?
                     WHERE id = ?'
                );
                mysqli_stmt_bind_param($stmt, 'ssiiiiii', $username, $hash, $points, $wins, $gamesPlayed, $bestLevel, $isAdmin, $id);
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    'UPDATE users
                     SET username = ?, points = ?, wins = ?, games_played = ?, best_level = ?, is_admin = ?
                     WHERE id = ?'
                );
                mysqli_stmt_bind_param($stmt, 'siiiiii', $username, $points, $wins, $gamesPlayed, $bestLevel, $isAdmin, $id);
            }

            mysqli_stmt_execute($stmt);
            update_user_title($conn, $id);
            set_flash('success', 'User updated.');
        } else {
            set_flash('error', 'Could not update user.');
        }

        redirect_to('admin/users.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);

        if ($id > 0) {
            $stmt = mysqli_prepare($conn, 'DELETE FROM users WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            set_flash('success', 'User deleted.');
        }

        redirect_to('admin/users.php');
    }
}

$usersResult = mysqli_query(
    $conn,
    'SELECT id, username, points, wins, games_played, best_level, title, is_admin
     FROM users
     ORDER BY id ASC'
);

$editUser = null;

if ($editId > 0) {
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, username, points, wins, games_played, best_level, title, is_admin
         FROM users
         WHERE id = ?
         LIMIT 1'
    );
    mysqli_stmt_bind_param($stmt, 'i', $editId);
    mysqli_stmt_execute($stmt);
    $editUser = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
}

require_once __DIR__ . '/../core/header.php';
?>
<section class="split-card">
    <article class="card">
        <h1>Users</h1>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>User</th>
                        <th>Points</th>
                        <th>Wins</th>
                        <th>Games</th>
                        <th>Best Level</th>
                        <th>Title</th>
                        <th>Admin</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($usersResult)): ?>
                        <tr>
                            <td><?php echo (int) $row['id']; ?></td>
                            <td><?php echo esc($row['username']); ?></td>
                            <td><?php echo (int) $row['points']; ?></td>
                            <td><?php echo (int) $row['wins']; ?></td>
                            <td><?php echo (int) $row['games_played']; ?></td>
                            <td><?php echo (int) $row['best_level']; ?></td>
                            <td><?php echo esc($row['title']); ?></td>
                            <td><?php echo !empty($row['is_admin']) ? 'Yes' : 'No'; ?></td>
                            <td>
                                <div class="action-btns">
                                    <a class="btn secondary" href="<?php echo esc(app_url('admin/users.php?edit=' . (int) $row['id'])); ?>">Edit</a>
                                    <form method="post" class="inline-action">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo (int) $row['id']; ?>">
                                        <button type="submit" class="btn ghost" onclick="return confirm('Delete this user?')">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </article>

    <article class="card form-card">
        <?php if ($editUser): ?>
            <h2>Edit User</h2>
            <p class="muted">Best level is limited to 1–<?php echo $maxLevel; ?>.</p>

            <form method="post" class="stack-form">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?php echo (int) $editUser['id']; ?>">

                <label>Username</label>
                <input name="username" value="<?php echo esc($editUser['username']); ?>" required>

                <label>Points</label>
                <input name="points" type="number" min="0" value="<?php echo (int) $editUser['points']; ?>" required>

                <label>Wins</label>
                <input name="wins" type="number" min="0" value="<?php echo (int) $editUser['wins']; ?>" required>

                <label>Games played</label>
                <input name="games_played" type="number" min="0" value="<?php echo (int) $editUser['games_played']; ?>" required>

                <label>Best level</label>
                <input name="best_level" type="number" min="1" max="<?php echo $maxLevel; ?>" value="<?php echo (int) $editUser['best_level']; ?>" required>

                <label>New password (leave blank to keep current password)</label>
                <div class="pass-wrap">
                    <input id="edit_password" name="password" type="password">
                    <button type="button" class="pass-toggle" aria-label="Toggle password visibility" onclick="togglePass('edit_password', this)">&#128065;</button>
                </div>

                <label class="checkbox-row">
                    <input type="checkbox" name="is_admin" value="1" <?php echo !empty($editUser['is_admin']) ? 'checked' : ''; ?>>
                    <span>Admin</span>
                </label>

                <div class="button-row">
                    <button class="btn" type="submit">Update User</button>
                    <a class="btn ghost" href="<?php echo esc(app_url('admin/users.php')); ?>">Cancel</a>
                </div>
            </form>
        <?php else: ?>
            <h2>Add User</h2>
            <p class="muted">Create a new user with a starting score and access level.</p>

            <form method="post" class="stack-form">
                <input type="hidden" name="action" value="add">

                <label>Username</label>
                <input name="username" required>

                <label>Password</label>
                <div class="pass-wrap">
                    <input id="add_password" name="password" type="password" required>
                    <button type="button" class="pass-toggle" aria-label="Toggle password visibility" onclick="togglePass('add_password', this)">&#128065;</button>
                </div>

                <label>Points</label>
                <input name="points" type="number" min="0" value="0" required>

                <label>Wins</label>
                <input name="wins" type="number" min="0" value="0" required>

                <label>Games played</label>
                <input name="games_played" type="number" min="0" value="0" required>

                <label>Best level</label>
                <input name="best_level" type="number" min="1" max="<?php echo $maxLevel; ?>" value="1" required>

                <label class="checkbox-row">
                    <input type="checkbox" name="is_admin" value="1">
                    <span>Admin</span>
                </label>

                <button class="btn" type="submit">Add User</button>
            </form>
        <?php endif; ?>
    </article>
</section>
<script>
function togglePass(id, btn) {
    var input = document.getElementById(id);
    if (input.type === 'password') { input.type = 'text'; btn.classList.add('active'); }
    else { input.type = 'password'; btn.classList.remove('active'); }
}
</script>
<?php require_once __DIR__ . '/../core/footer.php'; ?>