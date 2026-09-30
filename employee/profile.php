<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/validation.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

requireRole('employee');

$pdo = getDBConnection();
$userId = $_SESSION['user_id'];
$errors = [];

/* =========================
   Update Profile
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    validateCSRFOrDie();

    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');

    if (empty($phone) || !validatePhone($phone)) {
        $errors[] = "Please provide a valid phone number.";
    }

    /* =========================
       Photo Upload
    ========================= */
    $photoPath = null;

    if (
        isset($_FILES['profile_photo']) &&
        $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        $uploadCheck = validateFileUpload($_FILES['profile_photo']);

        if (!$uploadCheck['status']) {

            $errors[] = $uploadCheck['error'];

        } else {

            $fileName =
                'user_' .
                time() .
                '_' .
                bin2hex(random_bytes(4)) .
                '.' .
                $uploadCheck['ext'];

            $targetDir = __DIR__ . '/../uploads/profiles/';

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            if (
                move_uploaded_file(
                    $_FILES['profile_photo']['tmp_name'],
                    $targetDir . $fileName
                )
            ) {

                $photoPath = 'uploads/profiles/' . $fileName;

            } else {

                $errors[] = "Failed to upload profile photo.";
            }
        }
    }


    /* =========================
       Save Changes
    ========================= */
    if (empty($errors)) {

        try {

            $pdo->beginTransaction();

            if ($photoPath) {

                $sql = "
                    UPDATE employees
                    SET
                        phone = ?,
                        address = ?,
                        photo = ?
                    WHERE user_id = ?
                ";

                $pdo->prepare($sql)->execute([
                    $phone,
                    $address,
                    $photoPath,
                    $userId
                ]);

                $_SESSION['profile_photo'] = $photoPath;

            } else {

                $sql = "
                    UPDATE employees
                    SET
                        phone = ?,
                        address = ?
                    WHERE user_id = ?
                ";

                $pdo->prepare($sql)->execute([
                    $phone,
                    $address,
                    $userId
                ]);
            }

            $pdo->commit();

            audit(
                'profile_updated',
                'employee',
                $userId,
                'Employee updated profile details'
            );

            setFlash(
                'success',
                'Profile updated successfully.'
            );

            header(
                "Location: /SecuraHR/employee/profile.php"
            );

            exit;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $errors[] = "Failed to save profile updates.";
        }
    }
}


/* =========================
   Fetch Profile Data
========================= */
$stmt = $pdo->prepare("
    SELECT
        e.*,
        u.email,
        u.role,
        u.status,
        d.name AS department_name
    FROM employees e
    INNER JOIN users u
        ON e.user_id = u.id
    LEFT JOIN departments d
        ON e.department_id = d.id
    WHERE e.user_id = ?
");

$stmt->execute([$userId]);

$user = $stmt->fetch();


require_once __DIR__ . '/../includes/header.php';
?>

<div style="display: flex; min-height: calc(100vh - 75px);">

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
            My Profile & Personal Information
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


        <div style="
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
        ">


            <!-- =========================
                 Profile Overview
            ========================== -->
            <div
                class="card"
                style="text-align: center;"
            >

                <?php if (
                    !empty($user['photo']) &&
                    file_exists(
                        __DIR__ . '/../' . $user['photo']
                    )
                ): ?>

                    <img
                        src="/SecuraHR/<?= htmlspecialchars($user['photo']) ?>"
                        alt="Profile"
                        style="
                            width: 120px;
                            height: 120px;
                            border-radius: 50%;
                            object-fit: cover;
                            margin-bottom: 1rem;
                            border: 3px solid var(--primary);
                        "
                    >

                <?php else: ?>

                    <div style="
                        width: 120px;
                        height: 120px;
                        border-radius: 50%;
                        background: var(--primary);
                        color: white;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 3rem;
                        margin: 0 auto 1rem;
                    ">

                        <?= strtoupper(
                            substr(
                                $user['full_name'] ?? 'E',
                                0,
                                1
                            )
                        ) ?>

                    </div>

                <?php endif; ?>


                <h3 style="margin-bottom: 0.25rem;">

                    <?= htmlspecialchars(
                        $user['full_name'] ?? 'Employee'
                    ) ?>

                </h3>


                <p style="
                    color: var(--text-muted);
                    font-size: 0.9rem;
                    margin-bottom: 1rem;
                ">

                    <?= htmlspecialchars(
                        $user['designation'] ?? 'Staff'
                    ) ?>

                </p>


                <span class="badge badge-success">

                    <?= strtoupper(
                        $user['status'] ?? 'active'
                    ) ?>

                </span>


                <div style="
                    border-top: 1px solid var(--border-color);
                    margin-top: 1.5rem;
                    padding-top: 1rem;
                    text-align: left;
                    font-size: 0.85rem;
                ">

                    <p>
                        <strong>Employee Code:</strong>

                        <?= htmlspecialchars(
                            $user['employee_code'] ?? 'N/A'
                        ) ?>
                    </p>


                    <p>
                        <strong>Department:</strong>

                        <?= htmlspecialchars(
                            $user['department_name'] ?? 'Unassigned'
                        ) ?>
                    </p>


                    <p>
                        <strong>Joining Date:</strong>

                        <?= !empty($user['joining_date'])
                            ? formatDate($user['joining_date'])
                            : 'Not Set'
                        ?>
                    </p>


                    <p>
                        <strong>Employment Type:</strong>

                        <?= !empty($user['employment_type'])
                            ? strtoupper(
                                str_replace(
                                    '_',
                                    ' ',
                                    $user['employment_type']
                                )
                            )
                            : 'Not Set'
                        ?>
                    </p>


                    <p>
                        <strong>Employment Status:</strong>

                        <?= htmlspecialchars(
                            $user['employment_status'] ?? 'N/A'
                        ) ?>
                    </p>

                </div>

            </div>


            <!-- =========================
                 Editable Profile Form
            ========================== -->
            <div class="card">

                <h3 style="
                    color: var(--primary);
                    margin-bottom: 1.5rem;
                ">
                    Edit Contact Information
                </h3>


                <form
                    action=""
                    method="POST"
                    enctype="multipart/form-data"
                >

                    <?php renderCSRFField(); ?>


                    <!-- Full Name & Email -->
                    <div style="
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 1rem;
                    ">

                        <div class="form-group">

                            <label class="form-label">
                                Full Name (Read-only)
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $user['full_name'] ?? ''
                                ) ?>"
                                disabled
                            >

                        </div>


                        <div class="form-group">

                            <label class="form-label">
                                Email Address (Read-only)
                            </label>

                            <input
                                type="email"
                                class="form-control"
                                value="<?= htmlspecialchars(
                                    $user['email'] ?? ''
                                ) ?>"
                                disabled
                            >

                        </div>

                    </div>


                    <!-- Phone -->
                    <div class="form-group">

                        <label class="form-label">
                            Phone Number *
                        </label>

                        <input
                            type="text"
                            name="phone"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars(
                                $user['phone'] ?? ''
                            ) ?>"
                        >

                    </div>


                    <!-- Address -->
                    <div class="form-group">

                        <label class="form-label">
                            Residential Address
                        </label>

                        <textarea
                            name="address"
                            class="form-control"
                            rows="4"
                        ><?= htmlspecialchars(
                            $user['address'] ?? ''
                        ) ?></textarea>

                    </div>


                    <!-- Profile Photo -->
                    <div class="form-group">

                        <label class="form-label">
                            Update Profile Photo
                        </label>

                        <input
                            type="file"
                            name="profile_photo"
                            class="form-control"
                            accept="image/*"
                        >

                    </div>


                    <button
                        type="submit"
                        class="btn btn-primary"
                        style="
                            padding: 0.75rem 2rem;
                            margin-top: 1rem;
                        "
                    >
                        Save Changes
                    </button>

                </form>

            </div>

        </div>

    </main>

</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>