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
<link rel="stylesheet" href="<?= $appUrl ?>/css/style.css?v=<?= date('YmdHi') ?>">
<style>
/* ── Sidebar brand visibility ── */
.sidebar-brand-narrow { display: none; }
.sidebar-brand-full   { display: block; }
.sidebar-narrow .sidebar-brand-full,
.sidebar-narrow-unfoldable:not(:hover) .sidebar-brand-full { display: none !important; }
.sidebar-narrow .sidebar-brand-narrow,
.sidebar-narrow-unfoldable:not(:hover) .sidebar-brand-narrow { display: block !important; }

/* ── Medium screens (576–1199px): icon-only sidebar ── */
@media (min-width: 576px) and (max-width: 1199.98px) {
  #sidebar {
    display: flex !important;
    position: fixed !important;
    width: 4rem !important;
    flex: 0 0 4rem !important;
    overflow: hidden !important;
  }
  #sidebar .sidebar-nav {
    overflow: hidden !important;
    width: 4rem !important;
  }
  #sidebar .nav-label,
  #sidebar .nav-badge,
  #sidebar .nav-title {
    display: none !important;
  }
  #sidebar .nav-link {
    justify-content: center !important;
    padding-left:  0 !important;
    padding-right: 0 !important;
    overflow: hidden !important;
  }
  #sidebar .sidebar-brand-full   { display: none !important; }
  #sidebar .sidebar-brand-narrow { display: block !important; }
  #sidebar .sidebar-footer       { display: none !important; }
  /* Push wrapper content past the 4rem sidebar */
  .wrapper {
    margin-left: 4rem !important;
  }
  /* Hide the mobile backdrop */
  .sidebar-backdrop { display: none !important; }
}

/* ── Large screens (≥1200px) narrow/unfoldable state ── */
@media (min-width: 1200px) {
  .sidebar-narrow .nav-label,
  .sidebar-narrow-unfoldable:not(:hover) .nav-label,
  .sidebar-narrow .nav-badge,
  .sidebar-narrow-unfoldable:not(:hover) .nav-badge,
  .sidebar-narrow .nav-title,
  .sidebar-narrow-unfoldable:not(:hover) .nav-title {
    display: none !important;
  }
  .sidebar-narrow .nav-link,
  .sidebar-narrow-unfoldable:not(:hover) .nav-link {
    justify-content: center !important;
    padding-left:  0 !important;
    padding-right: 0 !important;
    overflow: hidden !important;
  }
  .sidebar-narrow .sidebar-nav,
  .sidebar-narrow-unfoldable:not(:hover) .sidebar-nav {
    overflow: hidden !important;
  }
}

/* ── Mobile optimisations (screens < 576px) ── */
@media (max-width: 575.98px) {
  .container-lg { padding-left:.75rem !important; padding-right:.75rem !important; }
  .breadcrumb { font-size:.75rem; margin-bottom:.5rem !important; }
  .card-body { padding:.75rem !important; }
  .card .fs-2 { font-size:1.6rem !important; }
  .card .small { font-size:.7rem !important; }
  .table { font-size:.78rem; }
  .table th, .table td { padding:.4rem .5rem; }
  .card-header { font-size:.82rem; padding:.6rem .75rem; }
  .btn-sm { padding:.25rem .5rem; font-size:.72rem; }
  .d-flex.gap-2.flex-wrap { gap:.4rem !important; }
  .footer { font-size:.72rem; padding:.5rem .75rem !important; }
  .header .container-fluid { padding-left:.75rem; padding-right:.75rem; }
  .header .fw-semibold { font-size:.75rem; }
  h4 { font-size:1rem !important; }
}
</style>
<script src="<?= $appUrl ?>/js/config.js"></script>
<script src="<?= $appUrl ?>/js/color-modes.js"></script>
<script src="<?= $appUrl ?>/js/donma-modal.js"></script>
<script src="<?= $appUrl ?>/js/sidebar-narrow.js?v=<?= date('YmdHi') ?>" defer></script>
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
