<?= $this->include('layout/sidebar'); ?>

<style>
  .truncate-2{
    display:-webkit-box;
    -webkit-line-clamp:2;
    -webkit-box-orient:vertical;
    overflow:hidden;
    max-width:360px;
  }
  td.col-actions { white-space: nowrap; }     /* = .text-nowrap */
  td.col-actions .btn { display: inline-flex; align-items: center; width: auto; }
  #pagination{flex-wrap:wrap;gap:.25rem}
</style>

<div class="container-fluid mt-4 px-3">
  <div class="row">
    <div class="col-12 col-lg-11 col-xxl-10"> 
      <div class="d-flex align-items-center gap-2 mb-2">
      <h4 class="mb-0">Daftar Transaksi</h4>
      <a href="<?= base_url('transaksi/upload') ?>" class="btn btn-sm btn-primary ms-auto text-nowrap" id="btnUpload">
        <i class="fa fa-upload me-1"></i> Upload Data
      </a>
    </div>

      <div class="d-flex align-items-center gap-2 mt-2">
        <label for="pageSize" class="form-label m-0 small">Rows</label>
        <select id="pageSize" class="form-select form-select-sm" style="width:auto">
          <option value="10" selected>10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span id="rangeInfo" class="text-muted small ms-auto"></span>
      </div>

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
  let limit = 10;
  let page = 1;

  async function fetchTransactions() {
    try {
      const res = await fetch(`${apiBase}?limit=${limit}&page=${page}`);
      const json = await res.json();
      transactions = json.data || [];
      total = json.meta?.total ?? 0;

      const totalPages = Math.max(1, Math.ceil(total / limit));
      if (page > totalPages) page = totalPages;

      renderTable();
      renderPagination();
      renderRangeInfo();
    } catch (err) {
      console.error(err);
      alert("Gagal memuat data transaksi");
    }
  }

  function renderRangeInfo() {
    const el = document.getElementById('rangeInfo');
    const start = total ? (page - 1) * limit + 1 : 0;
    const end = Math.min(start + transactions.length - 1, total);
    el.textContent = total ? `Showing ${start}–${end} of ${total}` : 'No data';
  }

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g,"&amp;").replace(/</g,"&lt;")
      .replace(/>/g,"&gt;").replace(/"/g,"&quot;")
      .replace(/'/g,"&#039;");
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
      <td><span class="truncate-2" title="${produkFull}">${produkFull}</span></td>
      <td class="col-actions">
        <div class="d-inline-flex align-items-center gap-1">
          <a href="<?= base_url('transaksi/detail') ?>?id=${trx.id}" class="btn btn-sm btn-primary">Detail</a>
          <button type="button" class="btn btn-sm btn-danger" onclick="deleteTrx('${trx.id}')">Hapus</button>
        </div>
      </td>`;
      tbody.appendChild(row);
    });
  }

  function renderPagination() {
    const pagination = document.getElementById("pagination");
    pagination.innerHTML = "";
    const totalPages = Math.max(1, Math.ceil(total / limit));
    page = Math.min(Math.max(1, page), totalPages);

    const makeItem = (label, target, {disabled=false, active=false}={}) => {
      const li = document.createElement("li");
      li.className = "page-item" + (disabled ? " disabled" : "") + (active ? " active" : "");
      const a = document.createElement("a");
      a.className = "page-link";
      a.href = "#";
      a.textContent = label;
      if (!disabled && !active) {
        a.addEventListener("click", e => {
          e.preventDefault();
          page = target;
          fetchTransactions();
          window.scrollTo({top:0, behavior:'auto'});
        });
      }
      li.appendChild(a);
      return li;
    };

    const addEllipsis = () => {
      const li = document.createElement("li");
      li.className = "page-item disabled";
      li.innerHTML = `<span class="page-link">…</span>`;
      pagination.appendChild(li);
    };

    pagination.appendChild(makeItem("«", 1, {disabled: page === 1}));
    pagination.appendChild(makeItem("‹", page - 1, {disabled: page === 1}));

    const win = 2;
    const set = new Set([1, 2, totalPages - 1, totalPages]);
    for (let p = page - win; p <= page + win; p++) {
      if (p >= 1 && p <= totalPages) set.add(p);
    }
    const pages = [...set].sort((a, b) => a - b);

    let last = 0;
    for (const p of pages) {
      if (p - last > 1) addEllipsis();
      pagination.appendChild(makeItem(String(p), p, {active: p === page}));
      last = p;
    }

    pagination.appendChild(makeItem("›", page + 1, {disabled: page === totalPages}));
    pagination.appendChild(makeItem("»", totalPages, {disabled: page === totalPages}));
  }

  async function deleteTrx(id) {
    if (!confirm('Yakin ingin menghapus transaksi ini?')) return;
    try {
      const res = await fetch(`${apiBase}/${id}`, {
        method: 'DELETE',
        headers: {'X-Requested-With': 'XMLHttpRequest'}
      });
      const json = await res.json().catch(() => ({}));
      if (res.ok) {
        await fetchTransactions();
      } else {
        alert(json.message || json.error || 'Gagal menghapus transaksi.');
      }
    } catch (e) {
      console.error(e);
      alert('Terjadi kesalahan jaringan.');
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    document.getElementById('pageSize').addEventListener('change', (e) => {
      limit = parseInt(e.target.value, 10) || 10;
      page = 1;
      fetchTransactions();
    });
    fetchTransactions();
  });
</script>
