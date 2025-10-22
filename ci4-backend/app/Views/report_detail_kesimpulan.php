<?= $this->extend('layout/sidebar') ?>
<?= $this->section('content') ?>

<?php 
  $s     = (int)($step ?? 5);
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

  .box { border:1px solid #ced4da; border-radius:.5rem; padding:1rem; }
  .meta-grid{ display:grid; grid-template-columns: 180px 12px 1fr; row-gap:.35rem; }
  .meta-grid .k { font-size:16px; }
  .insight-box{ border:1px solid #ced4da; border-radius:.5rem; padding:1rem; margin-top:1rem; }
  .ins-row{ display:grid; grid-template-columns: 200px 12px 1fr; gap:.5rem .75rem; margin-bottom:.35rem; }
  .wrap { white-space:pre-wrap; }
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
          <div class="m-0 fw-semibold">Kesimpulan Analisis</div>
        </div>

        <div class="card-body">
          <!-- Ringkasan -->
          <div class="box mb-3">
            <div class="meta-grid small">
              <div class="k">Nama Analisis</div><div>:</div><div id="v_title">–</div>
              <div class="k">Deskripsi</div><div>:</div><div id="v_desc">–</div>
              <div class="k">Tanggal Awal</div><div>:</div><div id="v_start">–</div>
              <div class="k">Tanggal Akhir</div><div>:</div><div id="v_end">–</div>
              <div class="k">Min Support</div><div>:</div><div id="v_min_support">–</div>
              <div class="k">Min Confidence</div><div>:</div><div id="v_min_conf">–</div>
              <div class="k">Jumlah Transaksi</div><div>:</div><div id="v_total">–</div>
            </div>
          </div>

          <!-- Insight -->
          <div class="insight-box" id="insightBox">
            <!-- Frequent (dinamis) -->
            <div id="ins_frequent_wrap"></div>

            <!-- Association (dinamis) -->
            <div id="ins_assoc_wrap"></div>

            <!-- Lainnya -->
            <div class="ins-row"><div class="k">Lift Ratio</div><div>:</div><div id="i_lift" class="wrap">–</div></div>
            <div class="ins-row"><div class="k">Insight Strategis</div><div>:</div><div id="i_strat" class="wrap">–</div></div>
          </div>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 justify-content-between mt-3">
        <a class="btn btn-secondary" href="<?= esc($backUrl ?? site_url('report/lift-ratio/'.$reportId)) ?>">Sebelumnya</a>
        <div class="d-flex gap-2">
          <a class="btn btn-outline-dark" href="<?= site_url('api/report/download-report/'.$reportId) ?>">
            Download Report
          </a>
          <a class="btn btn-primary" href="<?= site_url('report') ?>">Kembali ke list</a>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  const apiBase = "<?= rtrim(site_url('api/report'), '/') ?>";
  const id      = "<?= (int)($reportId ?? 0) ?>";

  // formatter angka
  const n4  = (x, max=4) => (x==null || x==='') ? '–'
               : new Intl.NumberFormat('en-US',{maximumFractionDigits:max, minimumFractionDigits:0, useGrouping:false}).format(Number(x));
  const nInt= (x) => (x==null || x==='') ? '–'
               : new Intl.NumberFormat('id-ID',{maximumFractionDigits:0}).format(Number(x));

  const el = (id) => document.getElementById(id);

  // render Frequent k-Itemset (dinamis) dari insights_frequent_all
  function renderFrequentInsights(mapObj){
    const wrap = document.getElementById('ins_frequent_wrap');
    wrap.innerHTML = '';

    if (!mapObj || typeof mapObj !== 'object') {
      wrap.innerHTML = '<div class="ins-row"><div class="k">Frequent Itemset</div><div>:</div><div class="wrap">–</div></div>';
      return;
    }

    const ks = Object.keys(mapObj)
      .filter(k => !Number.isNaN(Number(k)))
      .map(k => Number(k))
      .sort((a,b)=>a-b);

    if (ks.length === 0) {
      wrap.innerHTML = '<div class="ins-row"><div class="k">Frequent Itemset</div><div>:</div><div class="wrap">–</div></div>';
      return;
    }

    ks.forEach(k => {
      const arr = Array.isArray(mapObj[String(k)]) ? mapObj[String(k)] : [];
      const first = arr[0] || {};
      const text = first.text || '–';

      const row = document.createElement('div');
      row.className = 'ins-row';
      row.innerHTML = `
        <div class="k">Frequent ${k}-Itemset</div>
        <div>:</div>
        <div class="wrap">${text}</div>
      `;
      wrap.appendChild(row);
    });
  }

  // render Association Rule k-Itemset (dinamis) dari insights_association_all
  function renderAssociationInsights(mapObj){
    const wrap = document.getElementById('ins_assoc_wrap');
    wrap.innerHTML = '';

    if (!mapObj || typeof mapObj !== 'object') {
      wrap.innerHTML = '<div class="ins-row"><div class="k">Association Rule</div><div>:</div><div class="wrap">–</div></div>';
      return;
    }

    // mulai dari k=2 (rule minimal X->Y)
    const ks = Object.keys(mapObj)
      .filter(k => !Number.isNaN(Number(k)) && Number(k) >= 2)
      .map(k => Number(k))
      .sort((a,b)=>a-b);

    if (ks.length === 0) {
      wrap.innerHTML = '<div class="ins-row"><div class="k">Association Rule</div><div>:</div><div class="wrap">–</div></div>';
      return;
    }

    ks.forEach(k => {
      const arr = Array.isArray(mapObj[String(k)]) ? mapObj[String(k)] : [];
      const first = arr[0] || {};
      const text = first.text || '–';

      const row = document.createElement('div');
      row.className = 'ins-row';
      row.innerHTML = `
        <div class="k">Association Rule ${k}-Itemset</div>
        <div>:</div>
        <div class="wrap">${text}</div>
      `;
      wrap.appendChild(row);
    });
  }

  (async () => {
    if (!id) return;

    try {
      // >>>>>>>>> ambil dari API KESIMPULAN <<<<<<<<<
      const res = await fetch(`${apiBase}/kesimpulan/${id}`, { headers:{'Accept':'application/json'} });
      if (!res.ok) throw new Error('HTTP '+res.status);
      const js = await res.json();

      // meta
      el('v_title').textContent       = js.title ?? '–';
      el('v_desc').textContent        = js.description ?? '–';
      el('v_start').textContent       = js.start_date ?? '–';
      el('v_end').textContent         = js.end_date ?? '–';
      el('v_min_support').textContent = (js.min_support    != null ? n4(js.min_support, 4)    : '–');
      el('v_min_conf').textContent    = (js.min_confidence != null ? n4(js.min_confidence,4) : '–');
      el('v_total').textContent       = (js.transaction_total != null ? nInt(js.transaction_total) : '–');

      // frequent (dinamis)
      renderFrequentInsights(js.insights_frequent_all);

      // association (dinamis)
      renderAssociationInsights(js.insights_association_all);

      // lainnya
      el('i_lift').textContent = js.insight_lift_ratio          ?? '–';
      el('i_strat').textContent= js.insight_strategis           ?? '–';

    } catch (e) {
      console.error(e);
      alert('Gagal memuat kesimpulan: ' + e.message);
    }
  })();
</script>

<?= $this->endSection() ?>
