<?= $this->extend('layout/sidebar') ?>

<?= $this->section('content') ?>
<link href="<?= base_url('assets/bootstrap/css/bootstrap.min.css') ?>" rel="stylesheet">

<div class="container mt-4">
    <h3 class="mb-4">Daftar Transaksi</h3>

    <div class="table-responsive">
        <table class="table table-bordered table-striped align-middle">
            <thead class="table-light">
                <tr>
                    <th>No</th>
                    <th>Tanggal Penjualan</th>
                    <th>Nomor Transaksi</th>
                    <th>Produk</th>
                    <th style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody id="transaction-body">
                <tr><td colspan="5" class="text-center">Loading...</td></tr>
            </tbody>
        </table>
    </div>

    <div id="pagination" class="mt-3 text-center"></div>
</div>

<script>
let currentPage = 1;
const limit = 10;

function loadTransactions(page = 1) {
    fetch(`<?= base_url('api/transactions') ?>?page=${page}&limit=${limit}`)
        .then(res => res.json())
        .then(result => {
            const data = result.data || [];
            const tbody = document.getElementById('transaction-body');
            tbody.innerHTML = '';

            if (data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center">Belum ada transaksi</td></tr>';
                return;
            }

            data.forEach((trx, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${(page - 1) * limit + index + 1}</td>
                    <td>${trx.sale_date}</td>
                    <td>${trx.transaction_number || '-'}</td>
                    <td>${trx.items}</td>
                    <td>
                        <a href="<?= base_url('api/transactions/') ?>${trx.id}" class="btn btn-sm btn-primary">Detail</a>
                        <form action="<?= base_url('api/transactions/') ?>${trx.id}" method="post" class="d-inline" onsubmit="return confirm('Yakin ingin menghapus?')">
                            <input type="hidden" name="_method" value="DELETE">
                            <button class="btn btn-sm btn-danger" type="submit">Hapus</button>
                        </form>
                    </td>
                `;
                tbody.appendChild(row);
            });

            // Pagination
            const pagination = document.getElementById('pagination');
            const total = result.meta?.total || 0;
            const totalPages = Math.ceil(total / limit);
            pagination.innerHTML = `
                <button class="btn btn-sm btn-outline-secondary me-2" ${page <= 1 ? 'disabled' : ''} onclick="loadTransactions(${page - 1})">Prev</button>
                <span>Page ${page} of ${totalPages}</span>
                <button class="btn btn-sm btn-outline-secondary ms-2" ${page >= totalPages ? 'disabled' : ''} onclick="loadTransactions(${page + 1})">Next</button>
            `;

            currentPage = page;
        })
        .catch(err => {
            console.error(err);
            document.getElementById('transaction-body').innerHTML =
                '<tr><td colspan="5" class="text-center text-danger">Gagal mengambil data</td></tr>';
        });
}

document.addEventListener('DOMContentLoaded', () => {
    loadTransactions();
});
</script>

<script src="<?= base_url('assets/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
<?= $this->endSection() ?>
