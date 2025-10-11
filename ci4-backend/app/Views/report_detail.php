<?= $this->extend('layout/sidebar') ?>
<?= $this->section('content') ?>

<?php
  // step aktif (default 1) & daftar step
  $s      = (int)($step ?? 1);
  $steps = [1=>'Main Info',2=>'Frequent Itemset',3=>'Association Rule',4=>'Lift Ratio',5=>'Kesimpulan'];
  $total  = count($steps);
?>

<style>
  .page-wrap { max-width: 1080px; }

  /* === STEPPER (centered, garis hanya sepanjang deretan bubble) === */
  .stepper{
    --bubble: 34px;                    /* diameter bulatan */
    --step: <?= $s ?>;                 /* step aktif (server) */
    --total: <?= $total ?>;            /* jumlah step (server) */
    position: relative;
    display: inline-flex;
    align-items: flex-start;
    gap: 48px;
    margin: 10px 0 28px;
  }
  .stepper .progress{
    position: absolute;
    top: calc(var(--bubble) / 2);
    left: calc(var(--bubble) / 2);
    width: calc(100% - var(--bubble)); /* dari tepi kiri bubble #1 ke tepi kanan bubble #last */
    height: 2px;
    background: #212529;               /* warna garis (netral) */
  }
  .stepper .step{ position: relative; text-align: center; flex: 0 0 auto; }
  .stepper .bubble{
    width: var(--bubble); height: var(--bubble); border-radius: 50%;
    display: inline-flex; align-items: center; justify-content: center;
    border: 2px solid #212529; background: #fff; line-height: 1;
    font-weight: 700; color: #212529;
  }
  .stepper .label{ font-size: .85rem; color: #6c757d; margin-top: 6px; white-space: nowrap; }
  .stepper .active .bubble{ background:#000; color:#fff; border-color:#000; } /* aktif: hitam solid */

  /* Form look */
  .card { border-color:#e9ecef; }
  .card-header { background:#f8f9fa; border-bottom:1px solid #e9ecef; }
  .form-control[readonly], textarea[readonly]{ background:#f8f9fa; box-shadow:none; cursor:default; }

  @media (max-width: 576px){ .stepper{ gap: 22px } }
</style>

<div class="container-fluid mt-4 px-3">
  <div class="row">
    <div class="col-12 col-lg-11 col-xxl-10 page-wrap">

      <h3 class="fw-bold mb-1">Hasil Analisis Data</h3>

      <!-- Stepper di tengah -->
      <div class="text-center">
        <div class="stepper">
          <div class="progress"></div>
          <?php foreach ($steps as $i => $lbl):
            $cls = $i === $s ? 'active' : '';
          ?>
            <div class="step <?= $cls ?>">
              <div class="bubble"><?= $i ?></div>
              <div class="label"><?= esc($lbl) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card shadow-sm rounded-3">
        <div class="card-header py-3">
          <div class="m-0 fw-semibold">Main Info</div>
        </div>

        <div class="card-body">
          <div id="loadState" class="small text-muted mb-3">Memuat data…</div>
          <div id="errorState" class="alert alert-danger d-none"></div>

          <div class="mb-3">
            <label class="form-label">Tanggal awal</label>
            <input type="text" id="v_start_date" class="form-control" readonly>
          </div>

          <div class="mb-3">
            <label class="form-label">Tanggal akhir</label>
            <input type="text" id="v_end_date" class="form-control" readonly >
          </div>

          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" id="v_name" class="form-control" readonly >
          </div>

          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Min Support</label>
              <input type="text" id="v_min_support" class="form-control" readonly >
            </div>
            <div class="col-md-6">
              <label class="form-label">Min Confidence</label>
              <input type="text" id="v_min_confidence" class="form-control" readonly >
            </div>
          </div>

          <div class="mb-3 mt-3">
            <label class="form-label">Deskripsi</label>
            <textarea id="v_description" class="form-control" rows="2" readonly ></textarea>
          </div>
        </div>
      </div>
      <div class="d-flex justify-content-between mt-2">
        <a href="<?= esc($backUrl ?? site_url('report')) ?>" class="btn btn-secondary">Kembali ke list</a>
        <a class="btn btn-primary<?= empty($reportId) ? ' disabled' : '' ?>" href="<?= site_url('report/itemset/'.$reportId) ?>">Selanjutnya</a>  
      </div>
    </div>
  </div>
</div>

<script>
  const apiBase = "<?= site_url('api/report') ?>";
  document.addEventListener('DOMContentLoaded', async () => {
    const params = new URLSearchParams(window.location.search);
    const id = "<?= esc($reportId ?? '') ?>" || params.get('id');

    const el = {
      load: document.getElementById('loadState'),
      err : document.getElementById('errorState'),
      start: document.getElementById('v_start_date'),
      end  : document.getElementById('v_end_date'),
      name : document.getElementById('v_name'),
      ms   : document.getElementById('v_min_support'),
      mc   : document.getElementById('v_min_confidence'),
      desc : document.getElementById('v_description')
    };

    if (!id) {
      el.load.classList.add('d-none');
      el.err.textContent = 'ID report tidak ditemukan di URL (?id=...)';
      el.err.classList.remove('d-none');
      return;
    }

    const toISO = v => {
      if (!v) return '';
      const d = new Date(v);
      if (isNaN(d)) return String(v);
      const p = n => String(n).padStart(2,'0');
      return `${d.getFullYear()}-${p(d.getMonth()+1)}-${p(d.getDate())}`;
    };
    const norm = v => {
      if (v == null || v === '') return '';
      const n = Number(v);
      if (Number.isNaN(n)) return String(v);
      return n > 1 ? (n/100).toString() : n.toString(); // persen -> pecahan
    };

    try {
      const resp = await fetch(`${apiBase}/${encodeURIComponent(id)}`, { headers: { 'Accept':'application/json' }});
      if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
      const json = await resp.json();
      const data = json?.data ?? json;

      el.start.value = toISO(data.start_date ?? data.period_start ?? data.tanggal_awal);
      el.end.value   = toISO(data.end_date   ?? data.period_end   ?? data.tanggal_akhir);
      el.name.value  = (data.title ?? data.name ?? data.nama) ?? '';
      el.ms.value    = norm(data.min_support ?? data.minimum_support);
      el.mc.value    = norm(data.min_confidence ?? data.confidence_percent);
      el.desc.value  = data.description ?? data.desc ?? data.keterangan ?? '';

      el.load.classList.add('d-none');
    } catch (err) {
      el.load.classList.add('d-none');
      el.err.textContent = 'Gagal memuat data report. ' + err.message;
      el.err.classList.remove('d-none');
      console.error(err);
    }
  });
</script>

<?= $this->endSection() ?>
