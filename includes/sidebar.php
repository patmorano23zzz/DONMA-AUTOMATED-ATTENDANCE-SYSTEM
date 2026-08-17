<?php
$activePage = $activePage ?? '';
$userRole   = $_SESSION['role'] ?? 'guest';
$u          = APP_URL;

function navActive(string $page, string $current): string {
    return $page === $current ? ' active' : '';
}
?>
<div class="sidebar sidebar-dark sidebar-fixed border-end" id="sidebar" data-coreui-unfoldable="false">
  <div class="sidebar-header border-bottom">
    <a class="sidebar-brand" href="<?= $u ?>/index.php">
      <img class="sidebar-brand-narrow"
           src="<?= $u ?>/assets/img/school-logo.png?v=202608092157"
           alt="DCMMES" width="46" height="46"
           style="object-fit:contain;flex-shrink:0;">
      <div class="sidebar-brand-full" style="display:flex;align-items:center;gap:.5rem;">
        <img src="<?= $u ?>/assets/img/school-logo.png?v=202608092157"
             alt="DCMMES" width="40" height="40"
             style="object-fit:contain;flex-shrink:0;">
        <div>
          <div class="fw-bold text-white" style="font-size:.82rem;line-height:1.2;">Don Marcelo C. Marty</div>
          <div style="font-size:.63rem;opacity:.6;color:#fff;">Elementary School</div>
          <span class="badge bg-primary mt-1" style="font-size:.55rem;">ATS</span>
        </div>
      </div>
    </a>
    <button class="btn-close d-lg-none" type="button" data-coreui-theme="dark" aria-label="Close"
      onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"></button>
  </div>

  <ul class="sidebar-nav" data-coreui="navigation" data-simplebar>

    <li class="nav-item">
      <a class="nav-link<?= navActive('dashboard', $activePage) ?>" href="<?= $u ?>/index.php">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color,currentcolor)" d="M0 0h240v240H0zm272 0h240v240H272zM0 272h240v240H0zm272 0h240v240H272z" class="ci-primary"/>
        </svg>
        Dashboard
      </a>
    </li>

    <li class="nav-title">Attendance</li>

    <li class="nav-item">
      <a class="nav-link<?= navActive('kiosk', $activePage) ?>" href="<?= $u ?>/kiosk.php">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color,currentcolor)" d="M256 48a208 208 0 1 1 0 416A208 208 0 0 1 256 48zm0-48C114.6 0 0 114.6 0 256s114.6 256 256 256 256-114.6 256-256S397.4 0 256 0zm24 128h-48v136l88 52 24-40-64-38V128z" class="ci-primary"/>
        </svg>
        Student Kiosk
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link<?= navActive('attendance', $activePage) ?>" href="<?= $u ?>/attendance/records.php">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color,currentcolor)" d="M432 64H336V16h-32v48H208V16h-32v48H80C53.5 64 32 85.5 32 112v352c0 26.5 21.5 48 48 48h352c26.5 0 48-21.5 48-48V112c0-26.5-21.5-48-48-48zm16 400c0 8.8-7.2 16-16 16H80c-8.8 0-16-7.2-16-16V176h384v288zm0-320H64v-32c0-8.8 7.2-16 16-16h48v32h32v-32h96v32h32v-32h96v32h32v-32h48c8.8 0 16 7.2 16 16v32z" class="ci-primary"/>
        </svg>
        Attendance Records
      </a>
    </li>

    <?php if (in_array($userRole, ['admin', 'teacher'])): ?>
    <li class="nav-title">Students</li>

    <li class="nav-item">
      <a class="nav-link<?= navActive('students', $activePage) ?>" href="<?= $u ?>/students/list.php">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color,currentcolor)" d="M256 48C141.1 48 48 141.1 48 256s93.1 208 208 208 208-93.1 208-208S370.9 48 256 48zm0 80c35.3 0 64 28.7 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64zm0 272c-52.9 0-100.2-24.3-131.5-62.4C145.6 310 199 288 256 288s110.4 22 131.5 49.6C356.2 375.7 308.9 400 256 400z" class="ci-primary"/>
        </svg>
        Student List
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link<?= navActive('enroll-student', $activePage) ?>" href="<?= $u ?>/students/enroll.php">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color,currentcolor)" d="M256 0C114.6 0 0 114.6 0 256s114.6 256 256 256 256-114.6 256-256S397.4 0 256 0zm112 272h-96v96h-32v-96h-96v-32h96v-96h32v96h96v32z" class="ci-primary"/>
        </svg>
        Enroll Student
        <?php if ($userRole === 'teacher'): ?>
        <span class="badge badge-sm bg-info ms-auto">Needs Approval</span>
        <?php endif; ?>
      </a>
    </li>

    <?php if ($userRole === 'teacher'): ?>
    <li class="nav-item">
      <a class="nav-link<?= navActive('teacher-reports', $activePage) ?>" href="<?= $u ?>/teacher/reports.php">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color,currentcolor)" d="M104 496H56c-13.3 0-24-10.7-24-24V328c0-13.3 10.7-24 24-24h48c13.3 0 24 10.7 24 24v144c0 13.3-10.7 24-24 24zm120 0h-48c-13.3 0-24-10.7-24-24V232c0-13.3 10.7-24 24-24h48c13.3 0 24 10.7 24 24v240c0 13.3-10.7 24-24 24zm120 0h-48c-13.3 0-24-10.7-24-24V152c0-13.3 10.7-24 24-24h48c13.3 0 24 10.7 24 24v320c0 13.3-10.7 24-24 24zm120 0h-48c-13.3 0-24-10.7-24-24V72c0-13.3 10.7-24 24-24h48c13.3 0 24 10.7 24 24v400c0 13.3-10.7 24-24 24z" class="ci-primary"/>
        </svg>
        My Class Report
      </a>
    </li>
    <?php endif; ?>

    <?php endif; ?>

    <?php if ($userRole === 'admin'): ?>
    <li class="nav-title">Administration</li>

    <li class="nav-item">
      <a class="nav-link<?= navActive('users', $activePage) ?>" href="<?= $u ?>/admin/users.php">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color,currentcolor)" d="M192 256c61.9 0 112-50.1 112-112S253.9 32 192 32 80 82.1 80 144s50.1 112 112 112zm0-176c35.3 0 64 28.7 64 64s-28.7 64-64 64-64-28.7-64-64 28.7-64 64-64zm96 192H96C43 272 0 315 0 368v16h384v-16c0-53-43-96-96-96zm-176 48c6-17.3 19.5-31 36.5-38.5C158 276.7 174.7 272 192 272s34 4.7 43.5 9.5C252.5 289 266 302.7 272 320H16zm304-208h-16v-48h-32v48h-16c-8.8 0-16 7.2-16 16v128c0 8.8 7.2 16 16 16h64c8.8 0 16-7.2 16-16V128c0-8.8-7.2-16-16-16zm-16 128h-32v-96h32v96z" class="ci-primary"/>
        </svg>
        Users &amp; Teachers
      </a>
    </li>

    <?php
    // Badge count for pending enrollments
    try {
        $pendingCount = getDB()->query("SELECT COUNT(*) FROM students WHERE enrollment_status='pending'")->fetchColumn();
    } catch(Exception $e) { $pendingCount = 0; }
    ?>
    <li class="nav-item">
      <a class="nav-link<?= navActive('pending-enrollments', $activePage) ?>" href="<?= $u ?>/admin/pending-enrollments.php">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color,currentcolor)" d="M256 512A256 256 0 1 0 256 0a256 256 0 1 0 0 512zm0-384c13.3 0 24 10.7 24 24v112c0 13.3-10.7 24-24 24s-24-10.7-24-24V152c0-13.3 10.7-24 24-24zm32 224a32 32 0 1 1-64 0 32 32 0 0 1 64 0z" class="ci-primary"/>
        </svg>
        Pending Enrollments
        <?php if ($pendingCount > 0): ?>
        <span class="badge badge-sm bg-warning ms-auto"><?= $pendingCount ?></span>
        <?php endif; ?>
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link<?= navActive('reports', $activePage) ?>" href="<?= $u ?>/admin/reports.php">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color,currentcolor)" d="M104 496H56c-13.3 0-24-10.7-24-24V328c0-13.3 10.7-24 24-24h48c13.3 0 24 10.7 24 24v144c0 13.3-10.7 24-24 24zm120 0h-48c-13.3 0-24-10.7-24-24V232c0-13.3 10.7-24 24-24h48c13.3 0 24 10.7 24 24v240c0 13.3-10.7 24-24 24zm120 0h-48c-13.3 0-24-10.7-24-24V152c0-13.3 10.7-24 24-24h48c13.3 0 24 10.7 24 24v320c0 13.3-10.7 24-24 24zm120 0h-48c-13.3 0-24-10.7-24-24V72c0-13.3 10.7-24 24-24h48c13.3 0 24 10.7 24 24v400c0 13.3-10.7 24-24 24z" class="ci-primary"/>
        </svg>
        Reports
      </a>
    </li>

    <li class="nav-item">
      <a class="nav-link<?= navActive('settings', $activePage) ?>" href="<?= $u ?>/admin/settings.php">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color,currentcolor)" d="M495.9 166.6c3.2 8.7 .5 18.4-6.4 24.6l-43.3 39.4c1.1 8.3 1.7 16.8 1.7 25.4s-.6 17.1-1.7 25.4l43.3 39.4c6.9 6.2 9.6 15.9 6.4 24.6c-4.4 11.9-9.7 23.3-15.8 34.3l-4.7 8.1c-6.6 11-14 21.4-22.1 31.2c-5.9 7.2-15.7 9.6-24.5 6.8l-55.7-17.7c-13.4 10.3-28.2 18.9-44 25.4l-12.5 57.1c-2 9.1-9 16.3-18.2 17.8c-13.8 2.3-28 3.5-42.5 3.5s-28.7-1.2-42.5-3.5c-9.2-1.5-16.2-8.7-18.2-17.8l-12.5-57.1c-15.8-6.5-30.6-15.1-44-25.4L83.1 425.9c-8.8 2.8-18.6 .3-24.5-6.8c-8.1-9.8-15.5-20.2-22.1-31.2l-4.7-8.1c-6.1-11-11.4-22.4-15.8-34.3c-3.2-8.7-.5-18.4 6.4-24.6l43.3-39.4C64.6 273.1 64 264.6 64 256s.6-17.1 1.7-25.4L22.4 191.2c-6.9-6.2-9.6-15.9-6.4-24.6c4.4-11.9 9.7-23.3 15.8-34.3l4.7-8.1c6.6-11 14-21.4 22.1-31.2c5.9-7.2 15.7-9.6 24.5-6.8l55.7 17.7c13.4-10.3 28.2-18.9 44-25.4l12.5-57.1c2-9.1 9-16.3 18.2-17.8C227.3 1.2 241.5 0 256 0s28.7 1.2 42.5 3.5c9.2 1.5 16.2 8.7 18.2 17.8l12.5 57.1c15.8 6.5 30.6 15.1 44 25.4l55.7-17.7c8.8-2.8 18.6-.3 24.5 6.8c8.1 9.8 15.5 20.2 22.1 31.2l4.7 8.1c6.1 11 11.4 22.4 15.8 34.3zM256 336a80 80 0 1 0 0-160 80 80 0 1 0 0 160z" class="ci-primary"/>
        </svg>
        Settings
      </a>
    </li>
    <?php endif; ?>

    <li class="nav-divider"></li>

    <li class="nav-item">
      <a class="nav-link" href="#" id="btn-logout"
         onclick="confirmLogout(event)"
         style="color:#fca5a5 !important;">
        <svg class="nav-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512">
          <path fill="var(--ci-primary-color,currentcolor)" d="M502.6 278.6c12.5-12.5 12.5-32.8 0-45.3l-128-128c-12.5-12.5-32.8-12.5-45.3 0s-12.5 32.8 0 45.3L402.7 224 192 224c-17.7 0-32 14.3-32 32s14.3 32 32 32l210.7 0-73.4 73.4c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0l128-128zM160 96c17.7 0 32-14.3 32-32s-14.3-32-32-32L96 32C43 32 0 75 0 128L0 384c0 53 43 96 96 96l64 0c17.7 0 32-14.3 32-32s-14.3-32-32-32l-64 0c-17.7 0-32-14.3-32-32l0-256c0-17.7 14.3-32 32-32l64 0z" class="ci-primary"/>
        </svg>
        Logout
      </a>
    </li>

  </ul>

  <div class="sidebar-footer border-top d-none d-md-flex">
    <button class="sidebar-toggler" type="button" data-coreui-toggle="unfoldable"></button>
  </div>
</div>

<script>
function confirmLogout(e) {
  e.preventDefault();
  DonmaModal.confirm({
    title: 'Sign Out',
    message: 'Are you sure you want to log out?',
    confirmText: 'Yes, Log Out',
    cancelText: 'Cancel',
    type: 'danger',
    onConfirm: () => {
      window.location.href = '<?= $u ?>/authentication/logout.php';
    }
  });
}
</script>
