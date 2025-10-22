<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= $title ?? 'Apriori Web App' ?></title>
  <link rel="stylesheet" href="<?= base_url('assets/bootstrap/css/bootstrap.min.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    :root { font-size: 16px; }
    body {
      margin: 0;
      font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
      display: flex; min-height: 100vh;
    }
    .sidebar {
      width: 220px; flex: 0 0 220px; box-sizing: border-box;
      background-color: #f5f5f5; padding-top: 20px;
      border-right: 1px solid #ccc; overflow-y: auto;
    }
    /* ==== BRAND (logo + nama) ==== */
    .brand {
      display: flex; align-items: center; gap: 10px;
      padding: 0 16px 16px; margin: 0 0 14px; border-bottom: 1px solid #e1e1e1;
      text-decoration: none; color: inherit;
    }
    .brand img.brand-logo {
      width: 28px; height: 28px; object-fit: contain; flex: 0 0 28px;
      border-radius: 6px;
    }
    .brand .brand-name {
      font-size: 16px; font-weight: 600; color: #222; line-height: 1.2;
      white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    }

    .sidebar ul { list-style: none; padding: 0; margin: 0; }
    .sidebar ul li { padding: 12px 20px; display: flex; align-items: center; }
    .sidebar ul li i { margin-right: 10px; }
    .sidebar ul li a { text-decoration: none; color: #333; width: 100%; }
    .sidebar ul li.active, .sidebar ul li:hover { background-color: #cce0ff; }

    /* tombol logout biar tampilannya seragam dengan link */
    .sidebar ul li button.logout-btn {
      appearance: none; background: none; border: none; padding: 0;
      margin: 0; font: inherit; color: #333; text-align: left; width: 100%;
      cursor: pointer;
    }
    .content { flex: 1 1 auto; min-width: 0; padding: 20px; }
  </style>
</head>

<script>
  /**
   * apiFetch: pembungkus fetch() yang otomatis redirect ke halaman login saat 401.
   * Pemakaian sama seperti fetch(), return-nya tetap Response (promise).
   */
  async function apiFetch(url, options = {}) {
    const res = await fetch(url, options);
    if (res.status === 401) {
      // Kalau API minta login, arahkan user ke halaman login
      window.location.href = "<?= site_url('login') ?>";
      // Berhenti eksekusi script saat ini
      return new Response(null, { status: 401 });
    }
    return res;
  }
</script>

<body>
  <script src="<?= base_url('assets/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
  <?php
    $uri = service('uri');
    $seg1 = $uri->getSegment(1);
    if ($seg1 === 'index.php') $seg1 = $uri->getSegment(2);
  ?>

  <div class="sidebar">
    <a href="<?= base_url('halaman-utama') ?>" class="brand" aria-label="Halaman Utama">
      <img class="brand-logo" src="<?= base_url('assets/img/logo.png') ?>" alt="Logo Djati Intan Barokah">
      <span class="brand-name">Djati Intan Barokah</span>
    </a>

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
        <i class="fas fa-sign-out-alt"></i>
        <!-- ganti <a> jadi <button> supaya bisa POST -->
        <button type="button" id="btnLogout" class="logout-btn">Logout</button>
      </li>
    </ul>
  </div>

  <div class="content">
    <?= $this->renderSection('content') ?>
  </div>
</body>

<script>
  const apiLogout = "<?= base_url('api/logout') ?>";
  const afterLogoutUrl = "<?= base_url('login') ?>"; // ubah kalau mau redirect ke halaman lain

  document.getElementById('btnLogout').addEventListener('click', async () => {
    
    try {
      const res = await fetch(apiLogout, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      // walaupun gagal, biasanya kita tetap arahkan ke login agar sesi berakhir di sisi klien
      if (!res.ok) {
        const js = await res.json().catch(()=>null);
        alert(js?.message || 'Logout gagal di server, sesi akan diakhiri di sisi klien.');
      }
    } catch (e) {
      console.error(e);
      alert('Tidak dapat terhubung ke server. Anda akan diarahkan ke halaman login.');
    } finally {
      // hapus token localStorage jika kamu menyimpan token di frontend
      try { localStorage.removeItem('auth_token'); } catch {}
      window.location.href = afterLogoutUrl;
    }
  });
</script>
</html>
