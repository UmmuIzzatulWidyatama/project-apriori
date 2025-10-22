<?php // app/Views/apriori_list.php ?>
<?= $this->extend('layout/sidebar'); ?>
<?= $this->section('content') ?>

<div class="container-fluid mt-4 px-3">
  
  <div class="row">
    <div class="col-12 col-lg-11 col-xxl-10">
      <h4>Proses Apriori</h4>
      <div class="card shadow-sm">
        <div class="card-body">
          <div id="resultBox" class="alert mt-3 d-none" role="alert"></div>
          <h6 class="fw-semibold mb-3">Main Info</h6>

          <form id="aprioriForm">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Tanggal awal <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="start_date" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Tanggal akhir <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="end_date" required>
              </div>

              <div class="col-12">
                <label class="form-label">Nama <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" placeholder="Nama analisis" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">Min Support <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="min_support" step="0.01" min="0" max="1" required>
              </div>
              <div class="col-md-6">
                <label class="form-label">Min Confidence <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="min_confidence" step="0.01" min="0" max="1" required>
              </div>

              <div class="col-12">
                <label class="form-label">Deskripsi</label>
                <textarea class="form-control" id="description" rows="3" placeholder="Catatan analisis (opsional)"></textarea>
              </div>
            </div>
          </div>
          </div>
          <div class="mt-3 d-flex align-items-center">
            <button id="btnProcess" class="btn btn-primary" type="submit">Proses</button>
            <span id="loading" class="text-muted small d-none ms-3">Memproses…</span>
          </div>
        </form>
    </div>
  </div>
</div>

<script>
const apiRun = "<?= base_url('api/apriori/run') ?>";
// base url untuk redirect ke main-info/{id}
const reportMainInfoBase = "<?= site_url('report/main-info') ?>";

function setLoading(on) {
  document.getElementById('btnProcess').disabled = on;
  document.getElementById('loading').classList.toggle('d-none', !on);
}

function showResult(type, msg) {
  const el = document.getElementById('resultBox');
  el.className = 'alert mt-3 ' + (type === 'success' ? 'alert-success' : 'alert-danger');
  el.textContent = msg;
  el.classList.remove('d-none');
  el.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

document.getElementById('aprioriForm').addEventListener('submit', async (e) => {
  e.preventDefault();

  const payload = {
    start_date:      document.getElementById('start_date').value,
    end_date:        document.getElementById('end_date').value,
    title:           document.getElementById('name').value.trim(),
    min_support:     parseFloat(document.getElementById('min_support').value),
    min_confidence:  parseFloat(document.getElementById('min_confidence').value),
    description:     document.getElementById('description').value.trim(),
  };

  // Validasi sederhana
  if (new Date(payload.start_date) > new Date(payload.end_date)) {
    showResult('error', 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.');
    return;
  }
  if (isNaN(payload.min_support) || payload.min_support < 0 || payload.min_support > 1) {
    showResult('error', 'Min Support harus 0–1, contoh 0.2');
    return;
  }
  if (isNaN(payload.min_confidence) || payload.min_confidence < 0 || payload.min_confidence > 1) {
    showResult('error', 'Min Confidence harus 0–1, contoh 0.6');
    return;
  }

  setLoading(true);
  let willRedirect = false;

  try {
    const res  = await fetch(apiRun, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(payload)
    });
    let json = null; try { json = await res.json(); } catch {}

    if (!res.ok) {
      throw new Error(json?.messages?.error || json?.message || 'Gagal memproses Apriori.');
    }

    // ====== SUKSES: tampilkan pesan 3 detik lalu redirect ======
    const id = json?.analisis_id;
    const target = json?.redirect_to
      || (id ? `${reportMainInfoBase}/${id}` : "<?= site_url('report') ?>");

    showResult('success', json?.message || 'Analisis berhasil dibuat');
    willRedirect = true;                         // jangan re-enable tombol

    setTimeout(() => { window.location.href = target; }, 2000);
    
  } catch (err) {
    console.error(err);
    showResult('error', err.message || 'Terjadi kesalahan jaringan.');
  } finally {
    if (!willRedirect) setLoading(false);        // tetap disable jika akan redirect
  }
});
</script>


<?= $this->endSection() ?>
