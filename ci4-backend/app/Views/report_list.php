<?= $this->extend('layout/sidebar'); ?>
<?= $this->section('content') ?>

<style>
  td.col-actions { white-space: nowrap; width: 1%; }
  .table .btn { display: inline-flex; align-items: center; width: auto; }

  /* Pagination rapi saat halaman banyak */
  #pagination { flex-wrap: wrap; gap: .25rem; }
</style>

<div class="container-fluid mt-4 px-3">
  <div class="row">
    <div class="col-12 col-lg-11 col-xxl-10">
      <h4>Report Hasil Analisis Data</h4>

      <!-- Page size selector + info range -->
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
        <table class="table table-bordered mt-3" id="reportTable">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Nama</th>
              <th>Tanggal Mulai</th>
              <th>Tanggal Akhir</th>
              <th class="th-actions">Aksi</th>
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
  const apiBase      = "<?= site_url('api/report') ?>";
  const mainInfoBase = "<?= rtrim(site_url('report/main-info'), '/') ?>";
  const apiDelete    = "<?= site_url('api/report/delete') ?>";

  let page  = 1;
  let limit = 10;
  let total = 0;
  let totalPages = 1;
  let currentRows = [];

  function escapeHtml(str) {
    return String(str ?? '')
      .replace(/&/g,"&amp;").replace(/</g,"&lt;")
      .replace(/>/g,"&gt;").replace(/"/g,"&quot;")
      .replace(/'/g,"&#039;");
  }

  async function fetchReports(goToPage = 1) {
    page = goToPage;
    try {
      const res  = await fetch(`${apiBase}?page=${page}&limit=${limit}`, {
        headers: { 'Accept':'application/json', 'X-Requested-With':'XMLHttpRequest' }
      });
      const json = await res.json();

      currentRows = json.data || [];
      const meta  = json.meta || {};

      // Hitung totalPages dari total (hindari 0 di pager)
      const metaTotal = Number(
        meta.total ?? meta.total_count ?? meta.count ?? meta.records ?? 0
      ) || 0;

      total = metaTotal;
      totalPages = Math.max(1, Math.ceil(total / limit));

      // Clamp page bila melewati totalPages
      if (page > totalPages) page = totalPages;

      renderTable(currentRows);
      renderPagination();
      renderRangeInfo();
    } catch (err) {
      console.error(err);
      alert("Gagal memuat data report");
    }
  }

  function renderRangeInfo() {
    const el = document.getElementById('rangeInfo');
    const start = (currentRows.length > 0) ? ((page - 1) * limit + 1) : 0;
    const end   = (currentRows.length > 0) ? (start + currentRows.length - 1) : 0;
    el.textContent = (total && total > 0)
      ? `Showing ${start}–${end} of ${total}`
      : (currentRows.length ? `Showing ${start}–${end}` : 'No data');
  }

  function renderTable(rows) {
    const tbody = document.querySelector("#reportTable tbody");
    tbody.innerHTML = rows.map(rpt => {
      const idSafe    = encodeURIComponent(rpt.id);
      const titleSafe = escapeHtml(rpt.title);
      return `
        <tr>
          <td>${rpt.id}</td>
          <td>${titleSafe}</td>
          <td>${rpt.start_date ?? '-'}</td>
          <td>${rpt.end_date ?? '-'}</td>
          <td class="col-actions">
            <div class="d-inline-flex align-items-center gap-1">
              <a href="${mainInfoBase}/${idSafe}" class="btn btn-sm btn-primary">Detail</a>
              <button type="button" class="btn btn-sm btn-danger" onclick="deleteReport('${idSafe}')">Hapus</button>
            </div>
          </td>
        </tr>`;
    }).join('');
  }

  function renderPagination() {
    const pagination = document.getElementById("pagination");
    pagination.innerHTML = "";

    totalPages = Math.max(1, Number(totalPages) || 1);
    page = Math.min(Math.max(1, Number(page) || 1), totalPages);

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
          fetchReports(page);
          window.scrollTo({top: 0, behavior: 'auto'});
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

    // Kumpulkan angka halaman yang valid (tidak mungkin 0)
    const collectPages = (page, totalPages, win = 2) => {
      const arr = [];
      const push = p => { if (p >= 1 && p <= totalPages && !arr.includes(p)) arr.push(p); };
      push(1);
      push(2);
      push(totalPages - 1);
      push(totalPages);
      for (let p = page - win; p <= page + win; p++) push(p);
      return arr.sort((a, b) => a - b);
    };

    // First + Prev
    pagination.appendChild(makeItem("«", 1, {disabled: page === 1}));
    pagination.appendChild(makeItem("‹", page - 1, {disabled: page === 1}));

    // Nomor halaman (aman, tersaring)
    const pages = collectPages(page, totalPages, 2);
    let last = 0;
    for (const p of pages) {
      if (p - last > 1) addEllipsis();
      pagination.appendChild(makeItem(String(p), p, {active: p === page}));
      last = p;
    }

    // Next + Last
    pagination.appendChild(makeItem("›", page + 1, {disabled: page === totalPages}));
    pagination.appendChild(makeItem("»", totalPages, {disabled: page === totalPages}));
  }

  async function deleteReport(id) {
    if (!confirm('Yakin ingin menghapus data ini?')) return;
    try {
      const res = await fetch(`${apiDelete}/${id}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      const js = await res.json().catch(() => ({}));
      if (!res.ok) throw new Error(js?.message || js?.error || 'Gagal menghapus analisis.');

      // Setelah hapus → refetch halaman saat ini (clamp otomatis di fetchReports)
      await fetchReports(page);
    } catch (e) {
      console.error(e);
      alert(e.message || 'Terjadi kesalahan jaringan.');
    }
  }

  document.addEventListener("DOMContentLoaded", () => {
    // Page size selector
    document.getElementById('pageSize').addEventListener('change', (e) => {
      limit = parseInt(e.target.value, 10) || 10;
      page = 1;
      fetchReports(page);
    });
    fetchReports(1);
  });
</script>

<?= $this->endSection() ?>
