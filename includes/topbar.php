<?php
$userName = $_SESSION['name'] ?? 'User';
$userRole = $_SESSION['role'] ?? 'guest';
$u        = APP_URL;
?>
<header class="header header-sticky p-0 mb-4">
  <div class="container-fluid border-bottom px-4">
    <button class="header-toggler" type="button"
      onclick="coreui.Sidebar.getInstance(document.querySelector('#sidebar')).toggle()"
      aria-label="Toggle sidebar">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="icon icon-lg">
        <path fill="var(--ci-primary-color,currentColor)" d="M64 384h384v-42.666H64V384zm0-106.666h384v-42.667H64v42.667zM64 128v42.665h384V128H64z"/>
      </svg>
    </button>

    <span class="d-none d-md-inline ms-2 fw-semibold text-body">
      Don Marcelo C. Marty Elementary School &mdash; Attendance Tracking System
    </span>

    <ul class="header-nav ms-auto me-3 d-flex align-items-center gap-3">
      <li class="nav-item d-none d-sm-block">
        <span class="nav-link text-body-secondary" id="live-clock"></span>
      </li>
    </ul>

    <ul class="header-nav">
      <li class="nav-item dropdown">
        <button class="btn btn-link nav-link dropdown-toggle py-2 px-0 px-md-2 d-flex align-items-center"
          data-coreui-toggle="dropdown" aria-expanded="false">
          <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" class="icon icon-lg">
            <path fill="currentColor" d="M361.5 1.2c5 2.1 8.6 6.6 9.6 11.9L391 121l107.9 19.8c5.3 1 9.8 4.6 11.9 9.6s1.5 10.7-1.6 15.2L446.9 256l62.3 90.3c3.1 4.5 3.7 10.2 1.6 15.2s-6.6 8.6-11.9 9.6L391 391 371.1 498.9c-1 5.3-4.6 9.8-9.6 11.9s-10.7 1.5-15.2-1.6L256 446.9l-90.3 62.3c-4.5 3.1-10.2 3.7-15.2 1.6s-8.6-6.6-9.6-11.9L121 391 13.1 371.1c-5.3-1-9.8-4.6-11.9-9.6s-1.5-10.7 1.6-15.2L65.1 256 2.8 165.7c-3.1-4.5-3.7-10.2-1.6-15.2s6.6-8.6 11.9-9.6L121 121 140.9 13.1c1-5.3 4.6-9.8 9.6-11.9s10.7-1.5 15.2 1.6L256 65.1 346.3 2.8c4.5-3.1 10.2-3.7 15.2-1.6zM160 256a96 96 0 1 1 192 0A96 96 0 1 1 160 256z"/>
          </svg>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li><button class="dropdown-item" data-coreui-theme-value="light">Light</button></li>
          <li><button class="dropdown-item" data-coreui-theme-value="dark">Dark</button></li>
          <li><button class="dropdown-item" data-coreui-theme-value="auto">Auto</button></li>
        </ul>
      </li>
    </ul>
  </div>
</header>
<script>
  (function () {
    function tick() {
      const el = document.getElementById('live-clock');
      if (el) el.textContent = new Date().toLocaleString('en-PH', {
        weekday: 'short', year: 'numeric', month: 'short',
        day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit'
      });
    }
    tick();
    setInterval(tick, 1000);
  })();
</script>
