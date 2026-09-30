<?php
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/validation.php';
require_once __DIR__ . '/includes/audit.php';
require_once __DIR__ . '/includes/functions.php';

$pdo = getDBConnection();

$departments = $pdo->query(
    "SELECT id, name 
     FROM departments 
     WHERE status = 'active' 
     ORDER BY name ASC"
)->fetchAll();

$errors = [];
$formData = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    validateCSRFOrDie();

    // Collect form data
    $formData['full_name'] = sanitizeInput($_POST['full_name'] ?? '');
    $formData['email'] = validateEmail($_POST['email'] ?? '');
    $formData['phone'] = sanitizeInput($_POST['phone'] ?? '');
    $formData['dob'] = sanitizeInput($_POST['dob'] ?? '');
    $formData['gender'] = sanitizeInput($_POST['gender'] ?? '');
    $formData['department_id'] = filter_var(
        $_POST['department_id'] ?? null,
        FILTER_VALIDATE_INT
    );
    $formData['designation'] = sanitizeInput($_POST['designation'] ?? '');
    $formData['emergency_contact'] = sanitizeInput(
        $_POST['emergency_contact'] ?? ''
    );
    $formData['address'] = sanitizeInput($_POST['address'] ?? '');

    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // -----------------------------
    // Validation
    // -----------------------------

    if (empty($formData['full_name'])) {
        $errors[] = "Full name is required.";
    }

    if (!$formData['email']) {
        $errors[] = "A valid email address is required.";
    }

    if (
        empty($formData['phone']) ||
        !validatePhone($formData['phone'])
    ) {
        $errors[] = "Valid Bangladesh phone number is required (+8801... or 01...).";
    }

    if (empty($formData['dob'])) {
        $errors[] = "Date of birth is required.";
    }

    $allowedGenders = ['male', 'female', 'other'];

    if (!in_array($formData['gender'], $allowedGenders, true)) {
        $errors[] = "Please select a valid gender.";
    }

    if (!$formData['department_id']) {
        $errors[] = "Please select a valid department.";
    }

    if (empty($formData['designation'])) {
        $errors[] = "Designation is required.";
    }

    if (empty($formData['emergency_contact'])) {
        $errors[] = "Emergency contact is required.";
    }

    if (empty($password) || strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }

    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // -----------------------------
    // Check duplicate email
    // -----------------------------

    if ($formData['email']) {

        $chk = $pdo->prepare(
            "SELECT id FROM users WHERE email = ? LIMIT 1"
        );

        $chk->execute([$formData['email']]);

        if ($chk->fetch()) {
            $errors[] = "This email address is already registered.";
        }
    }

    // -----------------------------
    // Photo Upload
    // -----------------------------

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
                'employee_' .
                time() .
                '_' .
                bin2hex(random_bytes(4)) .
                '.' .
                $uploadCheck['ext'];

            $targetDir = __DIR__ . '/uploads/profiles/';

            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $targetPath = $targetDir . $fileName;

            if (
                move_uploaded_file(
                    $_FILES['profile_photo']['tmp_name'],
                    $targetPath
                )
            ) {

                $photoPath = 'uploads/profiles/' . $fileName;

            } else {

                $errors[] = "Failed to upload profile photo.";
            }
        }
    }

    // -----------------------------
    // Create User + Employee
    // -----------------------------

    if (empty($errors)) {

        try {

            $pdo->beginTransaction();

            /*
             * Generate employee code
             */
            $empCode = 'EMP-' . strtoupper(
                bin2hex(random_bytes(3))
            );

            /*
             * Hash password
             */
            $passwordHash = password_hash(
                $password,
                PASSWORD_DEFAULT
            );

            /*
             * Insert into users table
             *
             * New registrations are ALWAYS:
             * role   = employee
             * status = pending
             *
             * Existing admin account is not affected.
             */
            $userSql = "
                INSERT INTO users
                (
                    email,
                    password_hash,
                    role,
                    status
                )
                VALUES
                (
                    ?,
                    ?,
                    'employee',
                    'pending'
                )
            ";

            $stmt = $pdo->prepare($userSql);

            $stmt->execute([
                $formData['email'],
                $passwordHash
            ]);

            $userId = $pdo->lastInsertId();

            /*
             * Insert employee information
             *
             * These fields belong to employees table.
             */
            $empSql = "
                INSERT INTO employees
                (
                    user_id,
                    employee_code,
                    full_name,
                    phone,
                    dob,
                    gender,
                    address,
                    emergency_contact,
                    photo,
                    department_id,
                    designation,
                    joining_date,
                    employment_type,
                    employment_status,
                    basic_salary
                )
                VALUES
                (
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    ?,
                    NULL,
                    'full_time',
                    'probation',
                    0.00
                )
            ";

            $empStmt = $pdo->prepare($empSql);

            $empStmt->execute([
                $userId,
                $empCode,
                $formData['full_name'],
                $formData['phone'],
                $formData['dob'],
                $formData['gender'],
                $formData['address'],
                $formData['emergency_contact'],
                $photoPath,
                $formData['department_id'],
                $formData['designation']
            ]);

            $pdo->commit();

            /*
             * Audit registration
             */
            audit(
                'candidate_registered',
                'auth',
                $userId,
                "Candidate self-registered using {$formData['email']}"
            );

            /*
             * Success message
             */
            setFlash(
                'success',
                'Registration submitted successfully! Your account is currently pending HR administrator approval.'
            );

            header('Location: /SecuraHR/login.php');
            exit;

        } catch (Exception $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log(
                "Registration Failed: " . $e->getMessage()
            );

            $errors[] =
                "An error occurred during registration. Please try again.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div
    class="container"
    style="margin-top: 3rem; margin-bottom: 4rem; max-width: 720px;"
>

    <div class="card">

        <h1
            style="
                color: var(--primary);
                text-align: center;
                margin-bottom: 0.5rem;
            "
        >
            Employee Self-Registration
        </h1>

        <p
            style="
                text-align: center;
                color: var(--text-muted);
                margin-bottom: 2rem;
            "
        >
            Apply for onboarding at DDM Industries Ltd.
        </p>

        <?php if (!empty($errors)): ?>

            <div
                style="
                    background: rgba(239, 68, 68, 0.15);
                    color: var(--danger);
                    padding: 1rem;
                    border-radius: var(--radius);
                    margin-bottom: 1.5rem;
                "
            >

                <ul style="margin-left: 1.25rem;">

                    <?php foreach ($errors as $err): ?>

                        <li>
                            <?= htmlspecialchars($err) ?>
                        </li>

                    <?php endforeach; ?>

                </ul>

            </div>

        <?php endif; ?>

        <form
            action=""
            method="POST"
            enctype="multipart/form-data"
        >

            <?php renderCSRFField(); ?>


            <!-- Full Name + Email -->

            <div
                style="
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 1rem;
                "
            >

                <div class="form-group">

                    <label class="form-label">
                        Full Name *
                    </label>

                    <input
                        type="text"
                        name="full_name"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars(
                            $formData['full_name'] ?? ''
                        ) ?>"
                    >

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Email Address *
                    </label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars(
                            $formData['email'] ?? ''
                        ) ?>"
                    >

                </div>

            </div>


            <!-- Phone + Date of Birth -->

            <div
                style="
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 1rem;
                "
            >

                <div class="form-group">

                    <label class="form-label">
                        Phone Number *
                    </label>

                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        placeholder="01XXXXXXXXX"
                        required
                        value="<?= htmlspecialchars(
                            $formData['phone'] ?? ''
                        ) ?>"
                    >

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Date of Birth *
                    </label>

                    <input
                        type="date"
                        name="dob"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars(
                            $formData['dob'] ?? ''
                        ) ?>"
                    >

                </div>

            </div>


            <!-- Gender + Emergency Contact -->

            <div
                style="
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 1rem;
                "
            >

                <div class="form-group">

                    <label class="form-label">
                        Gender *
                    </label>

                    <select
                        name="gender"
                        class="form-control"
                        required
                    >

                        <option value="">
                            -- Select Gender --
                        </option>

                        <option
                            value="male"
                            <?= ($formData['gender'] ?? '') === 'male'
                                ? 'selected'
                                : '' ?>
                        >
                            Male
                        </option>

                        <option
                            value="female"
                            <?= ($formData['gender'] ?? '') === 'female'
                                ? 'selected'
                                : '' ?>
                        >
                            Female
                        </option>

                        <option
                            value="other"
                            <?= ($formData['gender'] ?? '') === 'other'
                                ? 'selected'
                                : '' ?>
                        >
                            Other
                        </option>

                    </select>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Emergency Contact *
                    </label>

                    <input
                        type="text"
                        name="emergency_contact"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars(
                            $formData['emergency_contact'] ?? ''
                        ) ?>"
                    >

                </div>

            </div>


            <!-- Department + Designation -->

            <div
                style="
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 1rem;
                "
            >

                <div class="form-group">

                    <label class="form-label">
                        Target Department *
                    </label>

                    <select
                        name="department_id"
                        class="form-control"
                        required
                    >

                        <option value="">
                            -- Select Department --
                        </option>

                        <?php foreach ($departments as $d): ?>

                            <option
                                value="<?= $d['id'] ?>"
                                <?= ($formData['department_id'] ?? '') == $d['id']
                                    ? 'selected'
                                    : '' ?>
                            >
                                <?= htmlspecialchars($d['name']) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Designation *
                    </label>

                    <input
                        type="text"
                        name="designation"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars(
                            $formData['designation'] ?? ''
                        ) ?>"
                    >

                </div>

            </div>


            <!-- Address -->

            <div class="form-group">

                <label class="form-label">
                    Residential Address
                </label>

                <textarea
                    name="address"
                    class="form-control"
                    rows="3"
                ><?= htmlspecialchars(
                    $formData['address'] ?? ''
                ) ?></textarea>

            </div>


            <!-- Profile Photo -->

            <div class="form-group">

                <label class="form-label">
                    Profile Photo
                </label>

                <input
                    type="file"
                    name="profile_photo"
                    class="form-control"
                    accept="image/*"
                >

                <small style="color: var(--text-muted);">
                    Maximum 5MB.
                </small>

            </div>


            <!-- Password + Confirm Password -->

            <div
                style="
                    display: grid;
                    grid-template-columns: 1fr 1fr;
                    gap: 1rem;
                "
            >

                <div class="form-group">

                    <label class="form-label">
                        Password *
                    </label>

                    <input
                        type="password"
                        name="password"
                        class="form-control"
                        required
                        minlength="8"
                    >

                </div>


                <div class="form-group">

                    <label class="form-label">
                        Confirm Password *
                    </label>

                    <input
                        type="password"
                        name="confirm_password"
                        class="form-control"
                        required
                        minlength="8"
                    >

                </div>

            </div>


            <button
                type="submit"
                class="btn btn-primary"
                style="
                    width: 100%;
                    padding: 0.8rem;
                    margin-top: 1rem;
                "
            >
                Submit Application
            </button>

        </form>

    </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
