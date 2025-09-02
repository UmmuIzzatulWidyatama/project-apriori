<?= $this->extend('layout/sidebar') ?>
<?= $this->section('content') ?>

<?php
  $s      = (int)($step ?? 5); // step aktif: Association Rule
  $steps  = [1=>'Main Info',2=>'1-Itemset',3=>'2-Itemset',4=>'3-Itemset',5=>'Association Rule',6=>'Lift Ratio',7=>'Kesimpulan'];
?>

<style>
  .page-wrap { max-width:1080px; }
  .stepper{ --bubble:34px; position:relative; display:inline-flex; align-items:flex-start; gap:48px; margin:14px 0 30px; }
  .stepper .progress{ position:absolute; top:calc(var(--bubble)/2); left:calc(var(--bubble)/2); width:calc(100% - var(--bubble)); height:2px; background:#212529; }
  .stepper .step{ position:relative; text-align:center; flex:0 0 auto; }
  .stepper .bubble{ width:var(--bubble); height:var(--bubble); border-radius:50%; display:inline-flex; align-items:center; justify-content:center; border:2px solid #212529; background:#fff; line-height:1; font-weight:700; color:#212529; }
  .stepper .label{ font-size:.85rem; color:#6c757d; margin-top:6px; white-space:nowrap; }
  .stepper .active .bubble{ background:#000; color:#fff; border-color:#000; }
  .table-sm th,.table-sm td{ padding:.5rem .6rem; }
  .table-zebra tbody tr:nth-child(odd){ background:#f8f9fa; }
  .table-zebra thead th{ background:#e9ecef; border-bottom:1px solid #ced4da; }
  .meta{ margin-top:12px; margin-left:18px; }
  .meta .k{ color:#6c757d; }
</style>

<div class="container-fluid mt-4 px-3">
  <div class="row">
    <div class="col-12 col-lg-11 col-xxl-10 page-wrap">

      <h3 class="fw-bold mb-1">Hasil Analisis Data</h3>

      <div class="text-center">
        <div class="stepper">
          <div class="progress"></div>
          <?php foreach ($steps as $i => $lbl): ?>
            <div class="step <?= $i === $s ? 'active' : '' ?>">
              <div class="bubble"><?= $i ?></div>
              <div class="label"><?= esc($lbl) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="card shadow-sm rounded-3">
        <div class="card-header py-3">
          <div class="m-0 fw-semibold">Association Rule</div>
        </div>

        <div class="meta small">
          <div><span class="k">Minimum Confidence :</span> <span id="v_min_conf">–</span></div>
        </div>

        <div class="card-body">
          <div class="d-flex justify-content-between">
            <div class="m-0 fw-semibold">Association Rules 2-Itemset</div>  
          </div>
          <div id="state2" class="small text-muted mb-2">Memuat Association Rules 2-Itemset…</div>
          <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm table-zebra" id="tblAR2">
              <thead>
                <tr>
                  <th style="width:48%">Rules (X → Y)</th>
                  <th class="text-end" style="width:17%">Support X ∪ Y</th>
                  <th class="text-end" style="width:17%">Support X</th>
                  <th class="text-end" style="width:18%">Confidence</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

        <div class="d-flex justify-content-between">
          <div class="m-0 fw-semibold">Association Rules 3-Itemset</div>  
        </div>
          <div id="state3" class="small text-muted mb-2">Memuat Association Rules 3-Itemset…</div>
          <div class="table-responsive">
            <table class="table table-bordered table-sm table-zebra" id="tblAR3">
              <thead>
                <tr>
                  <th style="width:48%">Rules (X → Y)</th>
                  <th class="text-end" style="width:17%">Support X ∪ Y</th>
                  <th class="text-end" style="width:17%">Support X</th>
                  <th class="text-end" style="width:18%">Confidence</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between mt-3">
            <a class="btn btn-secondary" href="<?= esc($backUrl ?? site_url('report')) ?>">Sebelumnya</a>
            <a class="btn btn-primary<?= empty($reportId) ? ' disabled' : '' ?>" href="<?= esc($nextUrl ?? site_url('report')) ?>">Selanjutnya</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  const apiBase = "<?= rtrim(site_url('api/report'), '/') ?>";
  const id = "<?= (int)($reportId ?? 0) ?>";
  const els = {
    minc : document.getElementById('v_min_conf'),
    s2   : document.getElementById('state2'),
    s3   : document.getElementById('state3'),
    t2   : document.querySelector('#tblAR2 tbody'),
    t3   : document.querySelector('#tblAR3 tbody'),
  };

  const fmtSet = (arr) => `{${(arr || []).join(', ')}}`;
  
  // format angka tanpa trailing zero, maksimal 4 digit desimal
    const n4 = (x, max=4) => {
    if (x == null || x === '') return '-';
    return new Intl.NumberFormat('en-US', {
        maximumFractionDigits: max,
        minimumFractionDigits: 0,
        useGrouping: false
    }).format(Number(x));
    };

  const makeRow = (r) => {
    const a = r.antecedents ?? [];
    const c = r.consequents ?? [];
    const items = r.items ?? (fmtSet(a) + ' -> ' + fmtSet(c));
    return `<tr>
      <td>${items}</td>
      <td class="text-end">${n4(r.support)}</td>
      <td class="text-end">${n4(r.support_antecedents)}</td>
      <td class="text-end">${n4(r.confidence)}</td>
    </tr>`;
  };

  async function loadAR(kind){
    const url = `${apiBase}/association-itemset${kind}/${id}`;
    const res = await fetch(url, { headers:{'Accept':'application/json'} });
    if (!res.ok) throw new Error('HTTP '+res.status);
    return res.json();
  }

  (async () => {
    if (!id) return;

    try {
      const js2 = await loadAR(2);
      els.s2.classList.add('d-none');

      // set meta (ambil dari AR2 kalau ada)
      if (js2.min_confidence != null)   els.minc.textContent  = n4(js2.min_confidence, 4);

      const rows2 = Array.isArray(js2.data) ? js2.data : [];
      els.t2.innerHTML = rows2.length ? rows2.map(makeRow).join('') :
        '<tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>';

      const js3 = await loadAR(3);
      els.s3.classList.add('d-none');

      const rows3 = Array.isArray(js3.data) ? js3.data : [];
      els.t3.innerHTML = rows3.length ? rows3.map(makeRow).join('') :
        '<tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>';

    } catch (e) {
      console.error(e);
      els.s2.textContent = 'Gagal memuat Association Rules 2-Itemset.';
      els.s3.textContent = 'Gagal memuat Association Rules 3-Itemset.';
      els.s2.classList.remove('d-none');
      els.s3.classList.remove('d-none');
    }
  })();
</script>

<?= $this->endSection() ?>
