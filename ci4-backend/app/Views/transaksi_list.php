<?= $this->include('layout/sidebar'); ?>

<?= $this->section('content') ?>

<div class="container-fluid mt-4 px-3">
  <div class="row">
    <div class="col-12 col-lg-10 col-xl-9"> 
      <h4>Daftar Transaksi</h4>

      <div class="table-responsive">
        <table class="table table-bordered mt-3" id="transaksiTable">
          <thead class="table-light">
            <tr>
              <th>No</th>
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
    const limit = 5;
    let page = 1;

    async function fetchTransactions() {
        try {
            const res = await fetch("<?= base_url('api/transactions') ?>");
            const json = await res.json();
            transactions = json.data || [];
            renderTable();
            renderPagination();
        } catch (err) {
            alert("Gagal memuat data transaksi");
            console.error(err);
        }
    }

    function renderTable() {
        const tbody = document.querySelector("#transaksiTable tbody");
        tbody.innerHTML = "";

        const start = (page - 1) * limit;
        const paginated = transactions.slice(start, start + limit);

        paginated.forEach((trx, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${start + index + 1}</td>
                <td>${trx.sale_date}</td>
                <td>${trx.transaction_number || '-'}</td>
                <td>${trx.items}</td>
                <td>
                    <a href="<?= base_url('transaksi/detail') ?>?id=${trx.id}" class="btn btn-sm btn-primary">Detail</a>
                    <button type="button" class="btn btn-sm btn-danger ms-1" onclick="deleteTrx(${trx.id})">Hapus</button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }

    function renderPagination() {
        const total = transactions.length;
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

    async function deleteTrx(id) {
        if (!confirm('Yakin ingin menghapus transaksi ini?')) return;

        try {
            const res = await fetch(`${apiBase}/${id}`, {
            method: 'DELETE',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const json = await res.json().catch(() => ({}));

            if (res.ok) {
            // hapus dari array lokal dan render ulang (tanpa reload halaman)
            transactions = transactions.filter(t => String(t.id) !== String(id));

            const totalPages = Math.max(1, Math.ceil(transactions.length / limit));
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


    document.addEventListener("DOMContentLoaded", fetchTransactions);
</script>
