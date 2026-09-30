<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/audit.php';

requireRole(['admin', 'hr']);

$pdo = getDBConnection();
$errors = [];


/*
|--------------------------------------------------------------------------
| Handle Notice Creation
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'create_notice'
) {

    validateCSRFOrDie();

    $title = sanitizeInput($_POST['title'] ?? '');
    $content = sanitizeInput($_POST['content'] ?? '');

    if (empty($title) || empty($content)) {

        $errors[] = "Both title and notice content are required.";

    } else {

        try {

            $stmt = $pdo->prepare("
                INSERT INTO notices (
                    title,
                    content,
                    category,
                    status,
                    author_id,
                    published_date
                )
                VALUES (?, ?, 'general', 'published', ?, CURDATE())
            ");

            $stmt->execute([
                $title,
                $content,
                getCurrentUserId()
            ]);

            $noticeId = $pdo->lastInsertId();

            audit(
                'notice_created',
                'notices',
                $noticeId,
                "Published notice: {$title}"
            );

            setFlash(
                'success',
                'Company notice published successfully.'
            );

            header("Location: /SecuraHR/admin/notices.php");
            exit;

        } catch (Exception $e) {

            $errors[] = "Failed to publish notice: " . $e->getMessage();
        }
    }
}


/*
|--------------------------------------------------------------------------
| Handle Notice Deletion
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    && isset($_POST['action'])
    && $_POST['action'] === 'delete_notice'
) {

    validateCSRFOrDie();

    $noticeId = filter_var(
        $_POST['notice_id'] ?? 0,
        FILTER_VALIDATE_INT
    );

    if ($noticeId) {

        try {

            $stmt = $pdo->prepare("
                DELETE FROM notices
                WHERE id = ?
            ");

            $stmt->execute([$noticeId]);

            audit(
                'notice_deleted',
                'notices',
                $noticeId,
                "Deleted notice ID {$noticeId}"
            );

            setFlash(
                'success',
                'Notice removed successfully.'
            );

        } catch (Exception $e) {

            $errors[] = "Failed to delete notice: " . $e->getMessage();
        }
    }

    header("Location: /SecuraHR/admin/notices.php");
    exit;
}


/*
|--------------------------------------------------------------------------
| Fetch All Notices
|--------------------------------------------------------------------------
|
| author_id belongs to users.id.
| Employee's name is stored in employees.full_name.
|
*/

$notices = $pdo->query("
    SELECT
        n.*,
        COALESCE(e.full_name, 'Administrator') AS author_name
    FROM notices n
    LEFT JOIN employees e
        ON n.author_id = e.user_id
    ORDER BY n.created_at DESC
")->fetchAll();


require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; min-height: calc(100vh - 75px);">

```
<?php require_once __DIR__ . '/includes/sidebar.php'; ?>

<main style="
    flex: 1;
    padding: 2rem;
    background: var(--bg-main);
">

    <h1 style="
        color: var(--primary);
        margin-bottom: 2rem;
    ">
        Company Notice Board Management
    </h1>


    <!-- Error Messages -->

    <?php if (!empty($errors)): ?>

        <div style="
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
        ">

            <ul style="margin-left: 1.25rem;">

                <?php foreach ($errors as $err): ?>

                    <li>
                        <?= htmlspecialchars($err) ?>
                    </li>

                <?php endforeach; ?>

            </ul>

        </div>

    <?php endif; ?>


    <!-- Notice Publishing Form -->

    <div class="card" style="margin-bottom: 2rem;">

        <h3 style="
            color: var(--primary);
            margin-bottom: 1rem;
        ">
            Publish New Announcement
        </h3>


        <form action="" method="POST">

            <?php renderCSRFField(); ?>

            <input
                type="hidden"
                name="action"
                value="create_notice"
            >


            <div style="margin-bottom: 1rem;">

                <label style="
                    display: block;
                    font-weight: 600;
                    margin-bottom: 0.5rem;
                ">
                    Notice Title
                </label>

                <input
                    type="text"
                    name="title"
                    class="form-control"
                    style="width: 100%;"
                    placeholder="E.g., Upcoming Company Holiday & Office Closure"
                    required
                >

            </div>


            <div style="margin-bottom: 1rem;">

                <label style="
                    display: block;
                    font-weight: 600;
                    margin-bottom: 0.5rem;
                ">
                    Notice Details
                </label>

                <textarea
                    name="content"
                    class="form-control"
                    style="
                        width: 100%;
                        min-height: 120px;
                    "
                    placeholder="Write full details here..."
                    required
                ></textarea>

            </div>


            <button
                type="submit"
                class="btn btn-primary"
                style="
                    padding: 0.75rem 1.5rem;
                    font-weight: 600;
                "
            >
                📢 Broadcast Notice
            </button>

        </form>

    </div>


    <!-- Active Notices List -->

    <div class="card">

        <h3 style="
            color: var(--primary);
            margin-bottom: 1rem;
        ">
            Published Company Notices
        </h3>


        <table style="
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        ">

            <thead>

                <tr style="
                    border-bottom: 2px solid var(--border-color);
                    background: rgba(0,0,0,0.02);
                ">

                    <th style="padding: 0.75rem;">
                        Title
                    </th>

                    <th style="padding: 0.75rem;">
                        Content Preview
                    </th>

                    <th style="padding: 0.75rem;">
                        Author
                    </th>

                    <th style="padding: 0.75rem;">
                        Posted On
                    </th>

                    <th style="padding: 0.75rem;">
                        Action
                    </th>

                </tr>

            </thead>


            <tbody>

                <?php if (empty($notices)): ?>

                    <tr>

                        <td
                            colspan="5"
                            style="
                                padding: 1rem;
                                text-align: center;
                                color: var(--text-muted);
                            "
                        >
                            No active notices published yet.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($notices as $n): ?>

                        <tr style="
                            border-bottom: 1px solid var(--border-color);
                        ">

                            <td style="
                                padding: 0.75rem;
                                font-weight: 600;
                                color: var(--primary);
                            ">

                                <?= htmlspecialchars(
                                    $n['title']
                                ) ?>

                            </td>


                            <td style="
                                padding: 0.75rem;
                                max-width: 300px;
                                color: var(--text-muted);
                                font-size: 0.85rem;
                            ">

                                <?= htmlspecialchars(
                                    mb_strimwidth(
                                        $n['content'],
                                        0,
                                        80,
                                        '...'
                                    )
                                ) ?>

                            </td>


                            <td style="
                                padding: 0.75rem;
                                font-size: 0.85rem;
                            ">

                                <?= htmlspecialchars(
                                    $n['author_name'] ?? 'System Admin'
                                ) ?>

                            </td>


                            <td style="
                                padding: 0.75rem;
                                font-size: 0.85rem;
                            ">

                                <?= formatDate(
                                    $n['published_date']
                                    ?? $n['created_at']
                                ) ?>

                            </td>


                            <td style="padding: 0.75rem;">

                                <form
                                    action=""
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to remove this notice?');"
                                >

                                    <?php renderCSRFField(); ?>

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete_notice"
                                    >

                                    <input
                                        type="hidden"
                                        name="notice_id"
                                        value="<?= (int)$n['id'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-danger"
                                        style="
                                            padding: 0.25rem 0.6rem;
                                            font-size: 0.8rem;
                                        "
                                    >
                                        Delete
                                    </button>

                                </form>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>
```

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
