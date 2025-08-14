<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= $title ?? 'Sistem Apriori' ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    body {
      margin: 0;
      font-family: sans-serif;
      display: flex;
      height: 100vh;
    }

    .sidebar {
      width: 220px;
      background-color: #f5f5f5;
      padding-top: 20px;
      border-right: 1px solid #ccc;
    }

    .sidebar h2 {
      text-align: center;
      font-size: 16px;
      margin-bottom: 30px;
    }

    .sidebar ul {
      list-style: none;
      padding: 0;
    }

    .sidebar ul li {
      padding: 12px 20px;
      display: flex;
      align-items: center;
    }

    .sidebar ul li i {
      margin-right: 10px;
    }

    .sidebar ul li a {
      text-decoration: none;
      color: #333;
      width: 100%;
    }

    .sidebar ul li.active, .sidebar ul li:hover {
      background-color: #cce0ff;
    }

    .content {
      flex-grow: 1;
      padding: 20px;
    }
  </style>
</head>
<body>

  <div class="sidebar">
    <h2>Djati Intan Barokah</h2>
    <ul>
      <li class="<?= uri_string() == 'halaman-utama' ? 'active' : '' ?>">
        <i class="fas fa-home"></i>
        <a href="<?= base_url('halaman-utama') ?>">Halaman Utama</a>
      </li>
      <li class="<?= uri_string() == 'transaksi' ? 'active' : '' ?>">
        <i class="fas fa-database"></i>
        <a href="<?= base_url('transaksi') ?>">Data Transaksi</a>
      </li>
      <li class="<?= uri_string() == 'apriori' ? 'active' : '' ?>">
        <i class="fas fa-briefcase"></i>
        <a href="<?= base_url('apriori') ?>">Proses Apriori</a>
      </li>
      <li class="<?= uri_string() == 'report' ? 'active' : '' ?>">
        <i class="fas fa-file-alt"></i>
        <a href="<?= base_url('report') ?>">Report</a>
      </li>
      <li>
        <i class="fas fa-sign-out-alt"></i>
        <a href="<?= base_url('logout') ?>">Logout</a>
      </li>
    </ul>
  </div>

  <div class="content">
    <?= $this->renderSection('content') ?>
  </div>

</body>
</html>
