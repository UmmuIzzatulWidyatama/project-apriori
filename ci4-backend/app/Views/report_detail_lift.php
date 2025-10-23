<?= $this->extend('layout/sidebar') ?>
<?= $this->section('content') ?>

<?php
  // step aktif: Lift Ratio
  $s     = (int)($step ?? 4);
  $steps = [1=>'Main Info',2=>'Frequent Itemset',3=>'Association Rule',4=>'Lift Ratio',5=>'Kesimpulan'];
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
          <div class="m-0 fw-semibold">Lift Ratio</div>
        </div>

        <div class="card-body">
          <!-- Insight dari endpoint kesimpulan -->
          <div id="liftInsight" class="alert alert-warning d-none" role="alert"></div>

          <div id="stateLift" class="small text-muted mb-2">Memuat Lift Ratio…</div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm table-zebra" id="tblLift">
              <thead>
                <tr>
                  <th style="width:75%">Rules</th>
                  <th class="text-end" style="width:25%">Lift</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-3">
        <a class="btn btn-secondary" href="<?= esc($backUrl ?? site_url('report')) ?>">Sebelumnya</a>
        <a class="btn btn-primary<?= empty($reportId) ? ' disabled' : '' ?>" href="<?= site_url('report/kesimpulan/'.$reportId) ?>">Selanjutnya</a>
      </div>
    </div>
  </div>
</div>

<script>
  const apiBase = "<?= rtrim(site_url('api/report'), '/') ?>";
  const id      = "<?= (int)($reportId ?? 0) ?>";

  const els = {
    state  : document.getElementById('stateLift'),
    tbody  : document.querySelector('#tblLift tbody'),
    insight: document.getElementById('liftInsight'),
  };

  // format angka tanpa trailing zero (maks 4 desimal)
  const n4 = (x, max=4) => {
    if (x == null || x === '') return '-';
    return new Intl.NumberFormat('en-US', {
      maximumFractionDigits: max,
      minimumFractionDigits: 0,
      useGrouping: false
    }).format(Number(x));
  };
  const fmtSet = (arr) => `{${(arr || []).join(', ')}}`;

  const makeRow = (r) => {
    const a = r.antecedents ?? [];
    const c = r.consequents ?? [];
    const items = r.items ?? (fmtSet(a) + ' -> ' + fmtSet(c));
    return `<tr>
      <td>${items}</td>
      <td class="text-end">${n4(r.lift, 4)}</td>
    </tr>`;
  };

  (async () => {
    if (!id) return;
    try {
      // Ambil daftar lift & insight lift sekaligus
      const [resLift, resSum] = await Promise.all([
        fetch(`${apiBase}/lift/${id}?order=lift&dir=DESC`, { headers:{'Accept':'application/json'} }),
        fetch(`${apiBase}/kesimpulan/${id}`,              { headers:{'Accept':'application/json'} }),
      ]);
      if (!resLift.ok) throw new Error('HTTP '+resLift.status+' (lift)');
      if (!resSum.ok)  throw new Error('HTTP '+resSum.status+' (kesimpulan)');

      // TABEL LIFT
      const jsLift = await resLift.json();
      const rows   = Array.isArray(jsLift.data) ? jsLift.data : [];
      els.tbody.innerHTML = rows.length
        ? rows.map(makeRow).join('')
        : '<tr><td colspan="2" class="text-center text-muted">Tidak ada data.</td></tr>';
      els.state.classList.add('d-none');

      // INSIGHT LIFT
      const jsSum = await resSum.json();
      const insightText = jsSum?.insight_lift_ratio || '';
      if (insightText) {
        els.insight.textContent = insightText; // aman (plain text)
        els.insight.classList.remove('d-none');
      }
    } catch (e) {
      els.state.textContent = 'Gagal memuat Lift Ratio. ' + e.message;
      console.error(e);
    }
  })();
</script>

<?= $this->endSection() ?>
