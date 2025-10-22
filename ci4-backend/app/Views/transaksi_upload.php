<?= $this->extend('layout/sidebar') ?>
<?= $this->section('content') ?>

<style>
  .page-wrap { max-width:1080px; }
  .card-uploader{ border:1px solid #e5e7eb; border-radius:.6rem; background:#fff; }
  .card-uploader .body{ padding:16px; }
  .hint{ color:#6c757d; font-size:.9rem; }
  .table-sm th,.table-sm td{ padding:.55rem .7rem; }
  .table-zebra tbody tr:nth-child(odd){ background:#f8f9fa; }
  .status-ok{ color:#087443; font-weight:600; }
  .status-err{ color:#b42318; font-weight:600; }
  tr.table-danger td { background-color:#f8d7da !important; }
</style>

<div class="container-fluid mt-4 px-3">
  <div class="row">
    <div class="col-12 col-lg-11 col-xxl-10 page-wrap">

      <h4>Upload Data Transaksi</h4>

      <div class="card-uploader mb-3">
        <div class="body">
          <form id="frmUpload">
            <div class="mb-3">
              <input type="file" class="form-control" id="inpFile" name="file" accept=".xlsx,.xls" required>
              <div class="form-text hint">Format yang didukung: .xlsx, .xls</div>
            </div>

            <div class="d-flex align-items-center gap-2">
              <button class="btn btn-primary" id="btnUpload" type="submit">Upload</button>
              <button class="btn btn-success" id="btnSave" type="button" disabled>Simpan</button>
              <span id="state" class="text-muted d-none">Memproses…</span>
            </div>
          </form>

          <div id="alertBox" class="alert d-none mt-3" role="alert"></div>

          <div id="resultWrap" class="mt-3 d-none">
            <div class="table-responsive">
              <table class="table table-bordered table-sm table-zebra" id="tblResult">
                <thead>
                  <tr>
                    <th style="width:10%">No Baris</th>
                    <th style="width:20%">Nomor Transaksi</th>
                    <th style="width:20%">Tanggal Transaksi</th>
                    <th style="width:30%">Nama Produk</th>
                    <th style="width:20%">Status</th>
                  </tr>
                </thead>
                <tbody></tbody>
              </table>
            </div>
          </div>

        </div>
      </div>

      <div class="d-flex justify-content-start">
        <a href="<?= site_url('transaksi') ?>" class="btn btn-secondary">Kembali</a>
      </div>

    </div>
  </div>
</div>

<script>
  const apiUpload = "<?= site_url('api/transactions/upload') ?>";
  const apiSave   = "<?= site_url('api/transactions/save') ?>";
  const redirectToList = "<?= site_url('transaksi') ?>"; // <-- halaman list transaksi (UI)

  const els = {
    form  : document.getElementById('frmUpload'),
    file  : document.getElementById('inpFile'),
    btnUp : document.getElementById('btnUpload'),
    btnSv : document.getElementById('btnSave'),
    state : document.getElementById('state'),
    alert : document.getElementById('alertBox'),
    wrap  : document.getElementById('resultWrap'),
    tbody : document.querySelector('#tblResult tbody'),
  };

  function setLoading(on){
    els.btnUp.disabled = on;
    els.state.classList.toggle('d-none', !on);
  }

  let alertTimer = null;
  function showAlert(type, msg, durationMs = 2000){
    els.alert.className = 'alert mt-3 alert-' + (type === 'success' ? 'success' : 'danger');
    els.alert.textContent = msg;
    els.alert.classList.remove('d-none');

    if (alertTimer) clearTimeout(alertTimer);
    if (type === 'success' && durationMs > 0) {
      alertTimer = setTimeout(() => { els.alert.classList.add('d-none'); }, durationMs);
    }
  }
  function clearAlert(){
    if (alertTimer) clearTimeout(alertTimer);
    els.alert.classList.add('d-none');
  }

  // cache hasil upload
  let lastRows = [];

  // normalisasi status
  const norm = (s) => String(s ?? '').toLowerCase().replace(/\s+/g,' ').trim();

  // Simpan aktif hanya jika SEMUA baris "Dapat disimpan"
  function allRowsValid(rows){
    if (!Array.isArray(rows) || rows.length === 0) return false;
    return rows.every(r => norm(r.status) === 'dapat disimpan');
  }

  function renderRows(rows){
    lastRows = Array.isArray(rows) ? rows : [];
    els.tbody.innerHTML = '';

    if (!Array.isArray(rows) || rows.length === 0){
      els.tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted">Tidak ada hasil.</td></tr>`;
      els.btnSv.disabled = true;
      return;
    }

    rows.forEach(r => {
      const tr = document.createElement('tr');
      const ok = (norm(r.status) === 'dapat disimpan');
      if (!ok) tr.classList.add('table-danger');

      tr.innerHTML = `
        <td>${r.row_number ?? ''}</td>
        <td>${r.no_transaksi ?? ''}</td>
        <td>${r.tgl_transaksi ?? ''}</td>
        <td>${r.product_name ?? ''}</td>
        <td class="${ok ? 'status-ok' : 'status-err'}">${r.status ?? ''}</td>
      `;
      els.tbody.appendChild(tr);
    });

    els.btnSv.disabled = !allRowsValid(rows);
  }

  // Upload handler
  els.form.addEventListener('submit', async (e) => {
    e.preventDefault();
    clearAlert();

    if (!els.file.files.length){
      showAlert('error', 'Silakan pilih file .xlsx / .xls.');
      return;
    }

    const fd = new FormData();
    fd.append('file', els.file.files[0]);

    setLoading(true);
    try{
      const res = await fetch(apiUpload, {
        method: 'POST',
        body: fd,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      const json = await res.json().catch(() => ({}));

      if (!res.ok){
        showAlert('error', json?.messages?.error || json?.message || 'Gagal mengunggah file.');
        els.wrap.classList.add('d-none');
        els.btnSv.disabled = true;
        return;
      }

      showAlert('success', 'Data berhasil ditampilkan.');
      renderRows(json?.data || []);
      els.wrap.classList.remove('d-none');

    } catch (err){
      console.error(err);
      showAlert('error', 'Terjadi kesalahan jaringan.');
      els.wrap.classList.add('d-none');
      els.btnSv.disabled = true;
    } finally {
      setLoading(false);
    }
  });

  // Simpan handler
  els.btnSv.addEventListener('click', async () => {
    clearAlert();

    const payload = (lastRows || [])
      .filter(r => norm(r.status) === 'dapat disimpan')
      .map(r => ({
        no_transaksi : r.no_transaksi ?? '',
        tgl_transaksi: r.tgl_transaksi ?? '',
        product_name : r.product_name ?? '',
      }));

    if (payload.length === 0){
      showAlert('error', 'Tidak ada baris yang dapat disimpan.');
      return;
    }

    els.btnSv.disabled = true;
    els.state.classList.remove('d-none');
    els.state.textContent = 'Menyimpan ke database…';

    try {
      const res = await fetch(apiSave, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify(payload)
      });
      const json = await res.json().catch(() => ({}));

      if (!res.ok){
        showAlert('error', json?.messages?.error || json?.message || 'Gagal menyimpan data.');
        return;
      }

      // Sembunyikan tabel hasil setelah simpan
      els.wrap.classList.add('d-none');
      els.tbody.innerHTML = '';

      // Tampilkan alert 2 detik lalu redirect ke halaman list transaksi (UI)
      showAlert('success', 'Data Berhasil Tersimpan', 2000);
      setTimeout(() => {
        window.location.href = redirectToList; // /transaksi
      }, 2000);

    } catch (err) {
      console.error(err);
      showAlert('error', 'Terjadi kesalahan jaringan saat menyimpan.');
      els.btnSv.disabled = !allRowsValid(lastRows);
    } finally {
      els.state.classList.add('d-none');
      els.state.textContent = 'Memproses…';
    }
  });
</script>

<?= $this->endSection() ?>
