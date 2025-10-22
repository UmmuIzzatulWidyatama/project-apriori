<?= $this->extend('layout/sidebar') ?>
<?= $this->section('content') ?>

<div class="container-fluid mt-4 px-3">
  <h4>Detail Transaksi</h4>
  
  <div class="row">
    <div class="col-12 col-lg-11 col-xxl-10">
      <div class="card shadow-sm">
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label">Nomor Transaksi</label>
            <input type="text" id="transaction_number" class="form-control" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label">Tanggal Transaksi</label>
            <input type="date" id="sale_date" class="form-control" readonly>
          </div>

          <label class="form-label">List Produk</label>
          <div class="table-responsive">
            <table class="table table-bordered mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:80px;">No</th>
                  <th>Produk</th>
                </tr>
              </thead>
              <tbody id="product_list">
                <tr><td colspan="2" class="text-center text-muted">Memuat...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
      <a href="<?= site_url('transaksi') ?>" class="btn btn-secondary mt-3">Kembali</a>
    </div>
  </div>
</div>

<script>
  // gunakan site_url supaya aman walau baseURL mengandung index.php
  const apiBase = "<?= site_url('api/transactions') ?>";
  const backUrl = "<?= site_url('transaksi') ?>";

  document.addEventListener('DOMContentLoaded', async () => {
    const params = new URLSearchParams(window.location.search);
    const id = params.get('id');

    if (!id) {
      alert('ID transaksi tidak ditemukan.');
      location.href = backUrl;
      return;
    }

    try {
      const resp = await fetch(`${apiBase}/${encodeURIComponent(id)}`);
      if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
      const json = await resp.json();
      // console.log('Response JSON:', json);

      // normalisasi bentuk respons
      let trx = null;
      if (json && json.data) {
        if (Array.isArray(json.data) && json.data.length) trx = json.data[0];
        else if (typeof json.data === 'object') trx = json.data;
      } else if (json && typeof json === 'object' && json.id) {
        trx = json; // fallback bila API kirim objek langsung
      }

      if (!trx) {
        alert('Data transaksi tidak ditemukan.');
        location.href = backUrl;
        return;
      }

      document.getElementById('transaction_number').value = trx.transaction_number || '-';
      document.getElementById('sale_date').value = trx.sale_date || '';

      const tbody = document.getElementById('product_list');
      tbody.innerHTML = '';

      const products = Array.isArray(trx.products) ? trx.products : [];
      if (products.length === 0) {
        tbody.innerHTML = `<tr><td colspan="2" class="text-center">Tidak ada produk</td></tr>`;
      } else {
        products.forEach((p, i) => {
          const tr = document.createElement('tr');
          tr.innerHTML = `
            <td class="text-center">${i + 1}</td>
            <td>${p.product_name ?? '-'}</td>
          `;
          tbody.appendChild(tr);
        });
      }
    } catch (err) {
      console.error(err);
      alert('Gagal memuat data transaksi.');
    }
  });
</script>

<?= $this->endSection() ?>
