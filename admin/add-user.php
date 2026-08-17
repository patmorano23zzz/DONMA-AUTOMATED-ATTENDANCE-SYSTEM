<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../authentication/login.php'); exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
$pdo     = getDB();
$u       = APP_URL;
$success = false;
$error   = '';

// Default grade sections — always available even before students are registered
$defaultSections = [
    'Grade 1 - Mabini',   'Grade 1 - Rizal',
    'Grade 2 - Mabini',   'Grade 2 - Rizal',
    'Grade 3 - Mabini',   'Grade 3 - Rizal',
    'Grade 4 - Mabini',   'Grade 4 - Rizal',
    'Grade 5 - Mabini',   'Grade 5 - Rizal',
    'Grade 6 - Mabini',   'Grade 6 - Rizal',
];

// Merge with any sections already in the students table (no duplicates)
$dbSections = $pdo->query('SELECT DISTINCT grade_section FROM students ORDER BY grade_section')
                  ->fetchAll(PDO::FETCH_COLUMN);
$sections = array_unique(array_merge($defaultSections, $dbSections));
sort($sections);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $name      = trim($_POST['name']       ?? '');
    $email     = trim($_POST['email']      ?? '');
    $teacherId = trim($_POST['teacher_id'] ?? '');
    $role      = $_POST['role']            ?? 'teacher';
    $section   = trim($_POST['section']    ?? '');
    $password  = $_POST['password']        ?? '';
    $confirm   = $_POST['confirm']         ?? '';
    $isActive  = isset($_POST['is_active']) ? 1 : 0;

    // Validate
    if (!$name || !$email || !$password) {
        $error = 'Name, email, and password are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check duplicate
        $chk = $pdo->prepare('SELECT id FROM users WHERE email = ? OR (teacher_id = ? AND teacher_id != "") LIMIT 1');
        $chk->execute([$email, $teacherId]);
        if ($chk->fetch()) {
            $error = 'A user with this email or Teacher ID already exists.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $ins  = $pdo->prepare(
                'INSERT INTO users (name, email, teacher_id, role, section, password_hash, is_active, created_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $ins->execute([
                $name, $email, $teacherId ?: null, $role,
                $section ?: null, $hash, $isActive,
            ]);
            $_SESSION['flash'] = ['type' => 'success', 'msg' => "User '{$name}' created successfully."];
            header('Location: users.php'); exit;
        }
    }
}

$activePage = 'users';
$pageTitle  = 'Add User';
$base       = './../';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include __DIR__ . '/../includes/head.php'; ?>
</head>
<body>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>

  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>

    <div class="body flex-grow-1">
      <div class="container-lg px-4">

        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $u ?>/index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $u ?>/admin/users.php">Users &amp; Teachers</a></li>
            <li class="breadcrumb-item active">Add User</li>
          </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
          <h4 class="mb-0">Add New User</h4>
          <a href="<?= $u ?>/admin/users.php" class="btn btn-outline-secondary btn-sm">← Back to Users</a>
        </div>

        <?php if ($error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <script>document.addEventListener('DOMContentLoaded',()=>DonmaModal.toast({message:<?= json_encode($error) ?>,type:'danger',duration:5000}));</script>
        <?php endif; ?>

        <div class="row justify-content-center">
          <div class="col-lg-7">
            <div class="card">
              <div class="card-header fw-semibold">User Details</div>
              <div class="card-body">
                <form method="POST" action="<?= $u ?>/admin/add-user.php" autocomplete="off" novalidate>
                  <?= csrf_field() ?>

                  <div class="mb-3">
                    <label class="form-label" for="name">Full Name <span class="text-danger">*</span></label>
                    <input class="form-control" id="name" name="name" type="text"
                      value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                  </div>

                  <div class="mb-3">
                    <label class="form-label" for="email">Email Address <span class="text-danger">*</span></label>
                    <input class="form-control" id="email" name="email" type="email"
                      value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                  </div>

                  <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                      <label class="form-label" for="teacher_id">Teacher ID</label>
                      <input class="form-control" id="teacher_id" name="teacher_id" type="text"
                        placeholder="e.g. T-0001"
                        value="<?= htmlspecialchars($_POST['teacher_id'] ?? '') ?>">
                      <div class="form-text">Leave blank for admin accounts.</div>
                    </div>
                    <div class="col-sm-6">
                      <label class="form-label" for="role">Role <span class="text-danger">*</span></label>
                      <select class="form-select" id="role" name="role">
                        <option value="teacher" <?= ($_POST['role'] ?? 'teacher') === 'teacher' ? 'selected' : '' ?>>Teacher</option>
                        <option value="admin"   <?= ($_POST['role'] ?? '') === 'admin'   ? 'selected' : '' ?>>Admin</option>
                      </select>
                    </div>
                  </div>

                  <div class="mb-3" id="section-row">
                    <label class="form-label" for="section">Assigned Section</label>
                    <input class="form-control" id="section" name="section"
                      list="section-list" autocomplete="off"
                      placeholder="e.g. Grade 1, Grade 2 - Mabini …"
                      value="<?= htmlspecialchars($_POST['section'] ?? '') ?>">
                    <datalist id="section-list">
                      <option value="">— None / All Sections —</option>
                      <option value="Grade 1">Grade 1</option>
                      <option value="Grade 2">Grade 2</option>
                      <option value="Grade 3">Grade 3</option>
                      <option value="Grade 4">Grade 4</option>
                      <option value="Grade 5">Grade 5</option>
                      <option value="Grade 6">Grade 6</option>
                      <?php foreach ($dbSections as $sec): ?>
                      <option value="<?= htmlspecialchars($sec) ?>"></option>
                      <?php endforeach; ?>
                    </datalist>
                    <div class="form-text">Choose a grade level or type a custom section name. Leave blank to allow all sections.</div>
                  </div>

                  <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                      <label class="form-label" for="password">Password <span class="text-danger">*</span></label>
                      <input class="form-control" id="password" name="password" type="password"
                        placeholder="Min. 8 characters" required>
                    </div>
                    <div class="col-sm-6">
                      <label class="form-label" for="confirm">Confirm Password <span class="text-danger">*</span></label>
                      <input class="form-control" id="confirm" name="confirm" type="password" required>
                    </div>
                  </div>

                  <div class="mb-4">
                    <label class="form-check">
                      <input class="form-check-input" type="checkbox" name="is_active" value="1"
                        <?= isset($_POST['is_active']) || !isset($_POST['name']) ? 'checked' : '' ?>>
                      <span class="form-check-label">Account active immediately</span>
                    </label>
                    <div class="form-text">Uncheck to create the account in "pending" state.</div>
                  </div>

                  <div class="d-flex gap-2">
                    <button class="btn btn-primary px-4" type="submit">Create User</button>
                    <a href="<?= $u ?>/admin/users.php" class="btn btn-outline-secondary">Cancel</a>
                  </div>

                </form>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>

    <footer class="footer px-4 py-3 border-top">
      <div class="text-body-secondary small">
        &copy; <?= date('Y') ?> Don Marcelo C. Marty Elementary School — ATS
      </div>
    </footer>
  </div>

  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?= $u ?>/vendors/simplebar/js/simplebar.min.js"></script>
  <script>
    // Hide section field when role is admin
    const roleEl    = document.getElementById('role');
    const sectionRow = document.getElementById('section-row');
    function toggleSection() {
      sectionRow.style.display = roleEl.value === 'admin' ? 'none' : '';
    }
    roleEl.addEventListener('change', toggleSection);
    toggleSection();
  </script>
</body>
</html>
