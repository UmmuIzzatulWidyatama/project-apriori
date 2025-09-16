<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= $title ?? 'Sistem Apriori' ?></title>
  <link rel="stylesheet" href="<?= base_url('assets/bootstrap/css/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root { /* opsional: samakan skala font di semua halaman */
      font-size: 16px;
    }
    body {
      margin: 0;
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
      display: flex;
      min-height: 100vh;
    }
    .sidebar {
      width: 220px;
      flex: 0 0 220px;          /* kunci lebar tetap */
      box-sizing: border-box;   /* padding & border tidak menambah lebar */
      background-color: #f5f5f5;
      padding-top: 20px;
      border-right: 1px solid #ccc;
      overflow-y: auto;
    }
    .sidebar h2 {
      text-align: center;
      font-size: 16px;
      margin-bottom: 30px;
    }
    .sidebar ul { list-style: none; padding: 0; margin: 0; }
    .sidebar ul li { padding: 12px 20px; display: flex; align-items: center; }
    .sidebar ul li i { margin-right: 10px; }
    .sidebar ul li a { text-decoration: none; color: #333; width: 100%; }
    .sidebar ul li.active, .sidebar ul li:hover { background-color: #cce0ff; }
    .content {
      flex: 1 1 auto;
      min-width: 0;
      padding: 20px;
    }
  </style>
</head>

<body>
  <script src="<?= base_url('assets/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
  <?php
    $uri = service('uri');
    $seg1 = $uri->getSegment(1);
    if ($seg1 === 'index.php') $seg1 = $uri->getSegment(2);
  ?>
  <div class="sidebar">
    <h2>Djati Intan Barokah</h2>
    <ul>
      <li class="<?= ($seg1 === '' || $seg1 === 'halaman-utama') ? 'active' : '' ?>">
        <i class="fas fa-home"></i><a href="<?= base_url('halaman-utama') ?>">Halaman Utama</a>
      </li>
      <li class="<?= ($seg1 === 'transaksi') ? 'active' : '' ?>">
        <i class="fas fa-database"></i><a href="<?= base_url('transaksi') ?>">Data Transaksi</a>
      </li>
      <li class="<?= ($seg1 === 'apriori') ? 'active' : '' ?>">
        <i class="fas fa-briefcase"></i><a href="<?= base_url('apriori') ?>">Proses Apriori</a>
      </li>
      <li class="<?= ($seg1 === 'report') ? 'active' : '' ?>">
        <i class="fas fa-file-alt"></i><a href="<?= base_url('report') ?>">Report</a>
      </li>
      <li>
        <i class="fas fa-sign-out-alt"></i><a href="<?= base_url('logout') ?>">Logout</a>
      </li>
    </ul>
  </div>

  <div class="content">
    <?= $this->renderSection('content') ?>
  </div>

</body>
</html>
