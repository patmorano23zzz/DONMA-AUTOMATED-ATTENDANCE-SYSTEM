<?php
/**
 * Enroll Student — Admin & Teacher
 * Admin: auto-approved. Teacher: status=pending until admin approves.
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (empty($_SESSION['user_id'])) { header('Location: ../authentication/login.php'); exit; }

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';
$pdo      = getDB();
$u        = APP_URL;
$userRole = $_SESSION['role'];
$userId   = (int)$_SESSION['user_id'];
$success  = false;
$error    = '';

// Teacher can only enroll into their own section
$mySection = null;
if ($userRole === 'teacher') {
    $ts = $pdo->prepare('SELECT section FROM users WHERE id = ? LIMIT 1');
    $ts->execute([$userId]);
    $mySection = $ts->fetchColumn();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $lrn          = preg_replace('/\D/', '', trim($_POST['lrn'] ?? ''));
    $firstName    = trim($_POST['first_name']    ?? '');
    $middleName   = trim($_POST['middle_name']   ?? '');
    $lastName     = trim($_POST['last_name']     ?? '');
    $gradeSection = trim($_POST['grade_section'] ?? '');
    $birthdate    = $_POST['birthdate']           ?? '';
    $gender       = $_POST['gender']              ?? '';
    $guardianName = trim($_POST['guardian_name']  ?? '');
    $guardianPhone= trim($_POST['guardian_phone'] ?? '');

    if (!$lrn || strlen($lrn) < 10) {
        $error = 'Valid LRN (10–12 digits) is required.';
    } elseif (!$firstName || !$lastName || !$gradeSection) {
        $error = 'First name, last name, and grade/section are required.';
    } else {
        // Check duplicate LRN
        $chk = $pdo->prepare('SELECT id FROM students WHERE lrn = ? LIMIT 1');
        $chk->execute([$lrn]);
        if ($chk->fetch()) {
            $error = 'A student with this LRN already exists.';
        } else {
            // Admin = approved immediately; Teacher = pending
            $status    = ($userRole === 'admin') ? 'approved' : 'pending';
            $teacherId = ($userRole === 'teacher') ? $userId : ((int)($_POST['teacher_id'] ?? 0) ?: null);
            $section   = ($userRole === 'teacher' && $mySection) ? $mySection : $gradeSection;

            $ins = $pdo->prepare(
                'INSERT INTO students
                 (lrn, first_name, middle_name, last_name, grade_section,
                  birthdate, gender, guardian_name, guardian_phone,
                  enrolled_by, teacher_id, enrollment_status, created_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,NOW())'
            );
            $ins->execute([
                $lrn, $firstName, $middleName ?: null, $lastName, $section,
                $birthdate ?: null, $gender ?: null,
                $guardianName ?: null, $guardianPhone ?: null,
                $userId, $teacherId ?: null, $status
            ]);
            $msg = $status === 'pending'
                ? "Student enrolled. Pending admin approval before they can register."
                : "Student enrolled successfully.";
            $_SESSION['flash'] = ['type' => $status === 'pending' ? 'warning' : 'success', 'msg' => $msg];
            header('Location: enroll.php'); exit;
        }
    }
}

// Teachers list for admin dropdown
$teachers = [];
if ($userRole === 'admin') {
    $teachers = $pdo->query("SELECT id, name, section FROM users WHERE role='teacher' AND is_active=1 ORDER BY name")->fetchAll();
}

$activePage = 'enroll-student';
$pageTitle  = 'Enroll Student';
$base       = './../';
?>
<!DOCTYPE html>
<html lang="en">
<head><?php include __DIR__ . '/../includes/head.php'; ?></head>
<body>
  <?php include __DIR__ . '/../includes/sidebar.php'; ?>
  <div class="wrapper d-flex flex-column min-vh-100">
    <?php include __DIR__ . '/../includes/topbar.php'; ?>
    <div class="body flex-grow-1">
      <div class="container-lg px-4">

        <nav aria-label="breadcrumb" class="mb-3">
          <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= $u ?>/index.php">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="<?= $u ?>/students/list.php">Students</a></li>
            <li class="breadcrumb-item active">Enroll Student</li>
          </ol>
        </nav>

        <div class="d-flex justify-content-between align-items-center mb-4">
          <div>
            <h4 class="mb-0">Enroll Student</h4>
            <?php if ($userRole === 'teacher'): ?>
            <p class="text-body-secondary small mb-0 mt-1">
              Enrolled students will be <strong>pending admin approval</strong> before they can register.
            </p>
            <?php endif; ?>
          </div>
          <a href="<?= $u ?>/students/list.php" class="btn btn-outline-secondary btn-sm">← Back</a>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <script>document.addEventListener('DOMContentLoaded',()=>DonmaModal.toast({message:<?= json_encode($error) ?>,type:'danger',duration:5000}));</script>
        <?php endif; ?>

        <div class="row justify-content-center">
          <div class="col-lg-8">
            <div class="card">
              <div class="card-header fw-semibold">Student Information</div>
              <div class="card-body">
                <form method="POST" action="<?= $u ?>
                  <?= csrf_field() ?>/students/enroll.php" novalidate>

                  <!-- LRN -->
                  <div class="mb-3">
                    <label class="form-label fw-semibold" for="lrn">
                      LRN (Learner Reference Number) <span class="text-danger">*</span>
                    </label>
                    <input class="form-control font-monospace" id="lrn" name="lrn" type="text"
                      maxlength="12" placeholder="e.g. 123456789012"
                      value="<?= htmlspecialchars($_POST['lrn'] ?? '') ?>" required>
                    <div class="form-text">10–12 digit DepEd LRN</div>
                  </div>

                  <!-- Name row -->
                  <div class="row g-3 mb-3">
                    <div class="col-sm-4">
                      <label class="form-label fw-semibold" for="last_name">Last Name <span class="text-danger">*</span></label>
                      <input class="form-control" id="last_name" name="last_name" type="text"
                        value="<?= htmlspecialchars($_POST['last_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label fw-semibold" for="first_name">First Name <span class="text-danger">*</span></label>
                      <input class="form-control" id="first_name" name="first_name" type="text"
                        value="<?= htmlspecialchars($_POST['first_name'] ?? '') ?>" required>
                    </div>
                    <div class="col-sm-4">
                      <label class="form-label" for="middle_name">Middle Name</label>
                      <input class="form-control" id="middle_name" name="middle_name" type="text"
                        value="<?= htmlspecialchars($_POST['middle_name'] ?? '') ?>">
                    </div>
                  </div>

                  <!-- Grade / Section -->
                  <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                      <label class="form-label fw-semibold" for="grade_section">
                        Grade / Section <span class="text-danger">*</span>
                      </label>
                      <?php if ($userRole === 'teacher' && $mySection): ?>
                      <input class="form-control" type="text" value="<?= htmlspecialchars($mySection) ?>" readonly>
                      <input type="hidden" name="grade_section" value="<?= htmlspecialchars($mySection) ?>">
                      <div class="form-text">Locked to your assigned section.</div>
                      <?php else: ?>
                      <input class="form-control" id="grade_section" name="grade_section"
                        list="section-opts" autocomplete="off"
                        placeholder="e.g. Grade 1"
                        value="<?= htmlspecialchars($_POST['grade_section'] ?? '') ?>" required>
                      <datalist id="section-opts">
                        <?php foreach (['Grade 1','Grade 2','Grade 3','Grade 4','Grade 5','Grade 6'] as $g): ?>
                        <option value="<?= $g ?>">
                        <?php endforeach; ?>
                      </datalist>
                      <?php endif; ?>
                    </div>
                    <div class="col-sm-3">
                      <label class="form-label" for="gender">Gender</label>
                      <select class="form-select" id="gender" name="gender">
                        <option value="">— Select —</option>
                        <option value="M" <?= ($_POST['gender']??'')==='M' ? 'selected':'' ?>>Male</option>
                        <option value="F" <?= ($_POST['gender']??'')==='F' ? 'selected':'' ?>>Female</option>
                        <option value="Other" <?= ($_POST['gender']??'')==='Other' ? 'selected':'' ?>>Other</option>
                      </select>
                    </div>
                    <div class="col-sm-3">
                      <label class="form-label" for="birthdate">Birthdate</label>
                      <input class="form-control" id="birthdate" name="birthdate" type="date"
                        value="<?= htmlspecialchars($_POST['birthdate'] ?? '') ?>">
                    </div>
                  </div>

                  <!-- Guardian -->
                  <div class="row g-3 mb-3">
                    <div class="col-sm-6">
                      <label class="form-label" for="guardian_name">Guardian Name</label>
                      <input class="form-control" id="guardian_name" name="guardian_name" type="text"
                        value="<?= htmlspecialchars($_POST['guardian_name'] ?? '') ?>">
                    </div>
                    <div class="col-sm-6">
                      <label class="form-label" for="guardian_phone">Guardian Phone</label>
                      <input class="form-control" id="guardian_phone" name="guardian_phone" type="tel"
                        value="<?= htmlspecialchars($_POST['guardian_phone'] ?? '') ?>">
                    </div>
                  </div>

                  <!-- Admin: assign teacher -->
                  <?php if ($userRole === 'admin' && !empty($teachers)): ?>
                  <div class="mb-3">
                    <label class="form-label" for="teacher_id">Assign to Teacher <span class="text-body-secondary small">(optional)</span></label>
                    <select class="form-select" id="teacher_id" name="teacher_id">
                      <option value="">— No specific teacher —</option>
                      <?php foreach ($teachers as $t): ?>
                      <option value="<?= $t['id'] ?>"
                        <?= ($_POST['teacher_id']??'')==$t['id'] ? 'selected':'' ?>>
                        <?= htmlspecialchars($t['name']) ?>
                        <?= $t['section'] ? ' — ' . htmlspecialchars($t['section']) : '' ?>
                      </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <?php endif; ?>

                  <div class="d-flex gap-2 mt-4">
                    <button class="btn btn-primary px-4" type="submit">
                      <?= $userRole === 'teacher' ? 'Submit for Approval' : 'Enroll Student' ?>
                    </button>
                    <a href="<?= $u ?>/students/list.php" class="btn btn-outline-secondary">Cancel</a>
                  </div>

                </form>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
    <footer class="footer px-4 py-3 border-top">
      <div class="text-body-secondary small">&copy; <?= date('Y') ?> Don Marcelo C. Marty Elementary School — ATS</div>
    </footer>
  </div>
  <script src="<?= $u ?>/vendors/@coreui/coreui/js/coreui.bundle.min.js"></script>
  <script src="<?= $u ?>/vendors/simplebar/js/simplebar.min.js"></script>
</body>
</html>
