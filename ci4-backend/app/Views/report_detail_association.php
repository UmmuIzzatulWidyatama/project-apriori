<?= $this->extend('layout/sidebar') ?>
<?= $this->section('content') ?>

<?php
  $s     = (int)($step ?? 3); // step aktif: Association Rule
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

  .section-card{ border:1px solid #e5e7eb; border-radius:.6rem; background:#fff; overflow:hidden; margin-bottom:22px; }
  .section-head{ background:#f6f7f9; padding:.75rem 1rem; font-weight:600; font-size:.95rem; }
  .table-sm th,.table-sm td{ padding:.55rem .7rem; }
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

      <!-- Meta (Minimum Confidence) -->
      <div class="section-card mb-3" id="metaBox" hidden>
        <div class="section-head">Ringkasan</div>
        <div class="p-3 small text-muted">
          <div>Minimum Confidence : <span id="v_min_conf">–</span></div>
        </div>
      </div>

      <!-- Container untuk section dinamis -->
      <div id="wrapSections"></div>

      <div class="d-flex justify-content-between my-3">
        <a class="btn btn-secondary" href="<?= esc($backUrl ?? site_url('report')) ?>">Sebelumnya</a>
        <a class="btn btn-primary<?= empty($reportId) ? ' disabled' : '' ?>" href="<?= esc($nextUrl ?? site_url('report')) ?>">Selanjutnya</a>
      </div>

    </div>
  </div>
</div>

<script>
  const apiUrl = "<?= rtrim(site_url('api/report/association'), '/') ?>/<?= (int)($reportId ?? 0) ?>";
  const wrap   = document.getElementById('wrapSections');

  // helpers
  const n4 = x => (x==null || Number.isNaN(+x)) ? '–'
              : new Intl.NumberFormat('en-US',{maximumFractionDigits:4, minimumFractionDigits:0, useGrouping:false})
                .format(+x);
  const fmtSet = (arr) => `{${(arr||[]).join(', ')}}`;
  const ruleText = (a,c) => `${fmtSet(a)} -> ${fmtSet(c)}`;

  // render satu section (k = ukuran itemset)
  function renderSection(k, rows){
    const card = document.createElement('div');
    card.className = 'section-card';
    card.innerHTML = `
      <div class="section-head">Association Rules ${k}-Itemset</div>
      <div class="p-3 pt-2">
        <div class="table-responsive">
          <table class="table table-bordered table-sm table-zebra">
            <thead>
              <tr>
                <th style="width:48%">Rules (X → Y)</th>
                <th class="text-end" style="width:17%">Support X ∪ Y</th>
                <th class="text-end" style="width:17%">Support X</th>
                <th class="text-end" style="width:18%">Confidence</th>
              </tr>
            </thead>
            <tbody>
              ${
                (!Array.isArray(rows) || rows.length===0)
                  ? `<tr><td colspan="4" class="text-center text-muted">Tidak ada data.</td></tr>`
                  : rows.map(r => `
                      <tr>
                        <td>${r.items ?? ruleText(r.antecedents, r.consequents)}</td>
                        <td class="text-end">${n4(r.support)}</td>
                        <td class="text-end">${n4(r.support_antecedents)}</td>
                        <td class="text-end">${n4(r.confidence)}</td>
                      </tr>
                    `).join('')
              }
            </tbody>
          </table>
        </div>
      </div>
    `;
    return card;
  }

  (async () => {
    try{
      const res = await fetch(apiUrl, { headers:{'Accept':'application/json'} });
      if (!res.ok) throw new Error('HTTP '+res.status);
      const j = await res.json();

      // tampilkan meta Minimum Confidence
      if (j.min_confidence != null){
        document.getElementById('v_min_conf').textContent = n4(j.min_confidence);
        document.getElementById('metaBox').hidden = false;
      }

      // groups: { "2":[...], "3":[...], ... }
      const groups = (j.data && typeof j.data === 'object') ? j.data : {};
      const ks = Object.keys(groups).map(Number).sort((a,b)=>a-b);

      wrap.innerHTML = '';
      if (ks.length === 0){
        wrap.innerHTML = `<div class="alert alert-warning">Tidak ada association rules untuk analisis ini.</div>`;
        return;
      }
      ks.forEach(k => wrap.appendChild(renderSection(k, groups[String(k)])));
    } catch(e){
      console.error(e);
      wrap.innerHTML = `<div class="alert alert-danger">Gagal memuat Association Rules: ${e.message}</div>`;
    }
  })();
</script>

<?= $this->endSection() ?>
