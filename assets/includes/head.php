<?php
// Shared <head> partial
$pageTitle = $pageTitle ?? 'DONMA Attendance System';
$appUrl    = APP_URL;

// ── Flash message helper ──────────────────────────────────────────────────
// Usage anywhere in PHP: $_SESSION['flash'] = ['type'=>'success','msg'=>'Done!'];
$flash = null;
if (!empty($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}
?>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1.0, shrink-to-fit=no">
<meta name="description" content="Automated Attendance Tracking System — Don Marcelo C. Marty Elementary School">
<title><?= htmlspecialchars($pageTitle) ?> | DONMA ATS</title>
<link rel="icon" type="image/png" sizes="32x32" href="<?= $appUrl ?>/assets/favicon/favicon-32x32.png?v=202608092157">
<link rel="icon" type="image/png" sizes="16x16" href="<?= $appUrl ?>/assets/favicon/favicon-16x16.png?v=202608092157">
<link rel="stylesheet" href="<?= $appUrl ?>/vendors/simplebar/css/simplebar.css">
<link rel="stylesheet" href="<?= $appUrl ?>/css/vendors/simplebar.css">
<link href="<?= $appUrl ?>/css/style.css" rel="stylesheet">
<style>
/* Sidebar brand: show logo only when sidebar is narrow/collapsed */
.sidebar-narrow          .sidebar-brand-full,
.sidebar-narrow-unfoldable:not(:hover) .sidebar-brand-full { display:none !important; }
.sidebar-narrow          .sidebar-brand-narrow,
.sidebar-narrow-unfoldable:not(:hover) .sidebar-brand-narrow { display:block !important; }
.sidebar-brand-narrow { display:none; }
.sidebar-brand-full   { display:block; }

/* ── Mobile optimisations (screens < 576px) ── */
@media (max-width: 575.98px) {
  /* Tighter page padding */
  .container-lg { padding-left:.75rem !important; padding-right:.75rem !important; }

  /* Breadcrumb — smaller */
  .breadcrumb { font-size:.75rem; margin-bottom:.5rem !important; }

  /* Stat cards — compact, equal height in 2×2 grid */
  .card-body { padding:.75rem !important; }
  .card .fs-2 { font-size:1.6rem !important; }
  .card .small { font-size:.7rem !important; }

  /* Table — make text smaller so it fits */
  .table { font-size:.78rem; }
  .table th, .table td { padding:.4rem .5rem; }

  /* Card headers */
  .card-header { font-size:.82rem; padding:.6rem .75rem; }

  /* Buttons in tables — compact */
  .btn-sm { padding:.25rem .5rem; font-size:.72rem; }

  /* Quick Actions — stack vertically, full width */
  .d-flex.gap-2.flex-wrap { gap:.4rem !important; }

  /* Footer — smaller */
  .footer { font-size:.72rem; padding:.5rem .75rem !important; }

  /* Header topbar */
  .header .container-fluid { padding-left:.75rem; padding-right:.75rem; }

  /* Reduce topbar school name width */
  .header .fw-semibold { font-size:.75rem; }

  /* Dashboard heading */
  h4 { font-size:1rem !important; }
}
</style>
<script src="<?= $appUrl ?>/js/config.js"></script>
<script src="<?= $appUrl ?>/js/color-modes.js"></script>
<script src="<?= $appUrl ?>/js/donma-modal.js"></script>
<?php if ($flash): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
  DonmaModal.toast({
    message: <?= json_encode($flash['msg']) ?>,
    type:    <?= json_encode($flash['type'] ?? 'info') ?>,
    duration: 4000
  });
});
</script>
<?php endif; ?>
