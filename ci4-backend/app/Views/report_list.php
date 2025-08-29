<?= $this->extend('layout/sidebar'); ?>

<?= $this->section('content') ?>

<div class="container-fluid mt-4 px-3">
  <div class="row">
    <div class="col-12 col-lg-10 col-xl-9"> 
      <h4>Report Hasil Analisis Data</h4>

      <div class="table-responsive">
        <table class="table table-bordered mt-3" id="reportTable">
          <thead class="table-light">
            <tr>
              <th>ID</th>
              <th>Nama</th>
              <th>Tanggal Mulai</th>
              <th>Tanggal Akhir</th>
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
    const apiBase       = "<?= site_url('api/report') ?>";
    const mainInfoBase  = "<?= rtrim(site_url('report/main-info'), '/') ?>";
    let reports = [];
    const limit = 5;
    let page = 1;

    async function fetchReports() {
      try {
        const res = await fetch(apiBase);
        const json = await res.json();
        reports = json.data || [];
        renderTable();
        renderPagination();
      } catch (err) {
        alert("Gagal memuat data report");
        console.error(err);
      }
    }

    function renderTable() {
      const tbody = document.querySelector("#reportTable tbody");
      tbody.innerHTML = "";
      const start = (page - 1) * limit;
      const paginated = reports.slice(start, start + limit);

      paginated.forEach((rpt) => {
        const row = document.createElement('tr');
        row.innerHTML = `
          <td>${rpt.id}</td>
          <td>${rpt.title}</td>
          <td>${rpt.start_date}</td>
          <td>${rpt.end_date}</td>
          <td>
            <a href="${mainInfoBase}/${encodeURIComponent(rpt.id)}" class="btn btn-sm btn-primary">Detail</a>
            <button type="button" class="btn btn-sm btn-danger ms-1" onclick="deleterpt(${rpt.id})">Hapus</button>
          </td>
        `;
        tbody.appendChild(row);
      });
    }

    function renderPagination() {
        const total = reports.length;
        const totalPages = Math.ceil(total / limit);
        const pagination = document.getElementById("pagination");
        pagination.innerHTML = "";

        for (let i = 1; i <= totalPages; i++) {
            const li = document.createElement("li");
            li.className = "page-item" + (i === page ? " active" : "");
            li.innerHTML = `<a class="page-link" href="#">${i}</a>`;
            li.addEventListener("click", () => {
                page = i;
                renderTable();
                renderPagination();
            });
            pagination.appendChild(li);
        }
    }

    async function deleterpt(id) {
        if (!confirm('Yakin ingin menghapus transaksi ini?')) return;

        try {
            const res = await fetch(`${apiBase}/${id}`, {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json().catch(() => ({}));

            if (res.ok) {
            // hapus dari array lokal dan render ulang (tanpa reload halaman)
            reports = reports.filter(t => String(t.id) !== String(id));

            const totalPages = Math.max(1, Math.ceil(reports.length / limit));
            if (page > totalPages) page = totalPages;

            renderTable();
            renderPagination();
            } else {
            alert(json.message || json.error || 'Gagal menghapus transaksi.');
            }
        } catch (e) {
            console.error(e);
            alert('Terjadi kesalahan jaringan.');
        }
    }


    document.addEventListener("DOMContentLoaded", fetchReports);
</script>
<?= $this->endSection() ?>