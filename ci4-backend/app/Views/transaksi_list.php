<?= $this->include('layout/sidebar'); ?>

<?= $this->section('content') ?>
<style>
  .truncate-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    max-width: 360px;      /* ubah sesuai layout */
  }
</style>

<div class="container-fluid mt-4 px-3">
  <div class="row">
    <div class="col-12 col-lg-11 col-xxl-10"> 
      <h4>Daftar Transaksi</h4> 
      <a href="<?= base_url('transaksi/upload') ?>" class="btn btn-sm btn-primary" id="btnUpload">
        <i class="fa fa-upload me-1"></i> Upload Data
      </a>

      <div class="table-responsive">
        <table class="table table-bordered mt-3" id="transaksiTable">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Tanggal Penjualan</th>
              <th>Nomor Transaksi</th>
              <th>Produk</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>

      <!-- Pagination -->
      <nav>
        <ul class="pagination justify-content-center" id="pagination"></ul>
      </nav>
    </div>
  </div>
</div>

<script>
  const apiBase = "<?= base_url('api/transactions') ?>";
  let transactions = [];
  let total = 0;
  const limit = 10; // tampilkan 10 per halaman di UI
  let page = 1;

  async function fetchTransactions() {
    try {
      const res = await fetch(`${apiBase}?limit=${limit}&page=${page}`);
      const json = await res.json();
      transactions = json.data || [];
      total = json.meta?.total ?? 0;
      renderTable();
      renderPagination();
    } catch (err) {
      console.error(err);
      alert("Gagal memuat data transaksi");
    }
  }

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g, "&amp;").replace(/</g, "&lt;")
      .replace(/>/g, "&gt;").replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function renderTable() {
    const tbody = document.querySelector("#transaksiTable tbody");
    tbody.innerHTML = "";
    transactions.forEach(trx => {
      const produkFull = escapeHtml(trx.items ?? "-");
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${trx.id}</td>
        <td>${trx.sale_date}</td>
        <td>${trx.transaction_number || '-'}</td>
        <td>
          <span class="truncate-2" title="${produkFull}">${produkFull}</span>
        </td>
        <td>
          <a href="<?= base_url('transaksi/detail') ?>?id=${trx.id}" class="btn btn-sm btn-primary">Detail</a>
          <button type="button" class="btn btn-sm btn-danger ms-1" onclick="deleteTrx('${trx.id}')">Hapus</button>
        </td>`;
      tbody.appendChild(row);
    });
  }

  function renderPagination() {
    const pagination = document.getElementById("pagination");
    pagination.innerHTML = "";
    const totalPages = Math.max(1, Math.ceil(total / limit));

    for (let i = 1; i <= totalPages; i++) {
      const li = document.createElement("li");
      li.className = "page-item" + (i === page ? " active" : "");
      li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
      li.addEventListener("click", (e) => {
        e.preventDefault();
        page = i;
        fetchTransactions();
      });
      pagination.appendChild(li);
    }
  }

  async function deleteTrx(id) {
    if (!confirm('Yakin ingin menghapus transaksi ini?')) return;
    try {
      const res = await fetch(`${apiBase}/${id}`, { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      const json = await res.json().catch(() => ({}));
      if (res.ok) {
        // Setelah hapus, refetch agar total & page sinkron
        const totalPagesBefore = Math.max(1, Math.ceil(total / limit));
        await fetchTransactions();
        const totalPagesAfter = Math.max(1, Math.ceil(total / limit));
        if (page > totalPagesAfter) { page = totalPagesAfter; await fetchTransactions(); }
      } else {
        alert(json.message || json.error || 'Gagal menghapus transaksi.');
      }
    } catch (e) {
      console.error(e);
      alert('Terjadi kesalahan jaringan.');
    }
  }

  document.addEventListener("DOMContentLoaded", fetchTransactions);
</script>

