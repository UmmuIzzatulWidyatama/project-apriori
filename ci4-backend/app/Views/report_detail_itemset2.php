<?= $this->extend('layout/sidebar') ?>
<?= $this->section('content') ?>

<?php
  $s      = (int)($step ?? 3); // step aktif: Itemset 2
  $steps  = [1=>'Main Info',2=>'Itemset 1',3=>'Itemset 2',4=>'Itemset 3',5=>'Asosiasi',6=>'Lift Ratio',7=>'Kesimpulan'];
  $total  = count($steps);
?>

<style>
  .page-wrap { max-width: 1080px; }
  .stepper{
    --bubble: 34px; position: relative; display: inline-flex; align-items: flex-start;
    gap: 48px; margin: 14px 0 30px;
  }
  .stepper .progress{ position:absolute; top:calc(var(--bubble)/2); left:calc(var(--bubble)/2);
    width:calc(100% - var(--bubble)); height:2px; background:#212529; }
  .stepper .step{ position:relative; text-align:center; flex:0 0 auto; }
  .stepper .bubble{
    width:var(--bubble);height:var(--bubble);border-radius:50%; display:inline-flex;align-items:center;justify-content:center;
    border:2px solid #212529;background:#fff;line-height:1;font-weight:700;color:#212529;
  }
  .stepper .label{ font-size:.85rem;color:#6c757d;margin-top:6px;white-space:nowrap; }
  .stepper .active .bubble{ background:#000;color:#fff;border-color:#000; }

  .table-sm th, .table-sm td{ padding:.5rem .6rem; }
  .table-zebra tbody tr:nth-child(odd){ background:#f8f9fa; }
  .table-zebra thead th{ background:#e9ecef;border-bottom:1px solid #ced4da; }

  .meta{ margin-bottom:10px; }
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

      <h5 class="fw-semibold mb-2">Itemset 2</h5>

      <div class="meta small">
        <div><span class="k">Total Transaksi :</span> <span id="v_total_tx">–</span></div>
        <div><span class="k">Minimum Support :</span> <span id="v_min_support">–</span></div>
      </div>

      <div class="card shadow-sm rounded-3">
        <div class="card-body">
          <div id="loadState" class="small text-muted mb-2">Memuat itemset 2…</div>
          <div id="errorState" class="alert alert-danger d-none"></div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm table-zebra" id="tblItemset2">
              <thead>
                <tr>
                  <th style="width:50%">Itemset</th>
                  <th style="width:20%">Frekuensi</th>
                  <th style="width:30%">Support</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>

          <div class="d-flex justify-content-between mt-3">
            <a class="btn btn-secondary" href="<?= site_url('report/itemset1/'.$reportId) ?>">Itemset 1</a>
            <a class="btn btn-primary<?= empty($reportId) ? ' disabled' : '' ?>" href="<?= site_url('report/itemset3/'.$reportId) ?>">Selanjutnya</a>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  const apiBase = "<?= site_url('api/report') ?>";
  const id = "<?= esc($reportId ?? '') ?>";

  const els = {
    load : document.getElementById('loadState'),
    err  : document.getElementById('errorState'),
    tbody: document.querySelector('#tblItemset2 tbody'),
    total: document.getElementById('v_total_tx'),
    minS : document.getElementById('v_min_support'),
  };

  function extractName(row){
    const src =
      row.itemsets ?? row.items ?? row.itemset ??
      row.item_names ?? row.item_name ??
      row.product ?? row.nama_produk ?? row.nama ?? row.item;

    if (Array.isArray(src)) {
      return src.map(x => {
        if (x == null) return '';
        if (typeof x === 'string') return x;
        if (typeof x === 'object') return (x.name ?? x.product ?? x.product_name ?? x.nama ?? '');
        return String(x);
      }).filter(Boolean).join(', ');
    }
    return (src ?? '(?)');
  }

  async function loadItemset2(){
    if (!id){
      els.load.classList.add('d-none');
      els.err.textContent = 'ID report tidak ditemukan.';
      els.err.classList.remove('d-none');
      return;
    }

    try{
      const r = await fetch(`${apiBase}/itemset2/${encodeURIComponent(id)}`, { headers:{'Accept':'application/json'} });
      if (!r.ok) throw new Error(`HTTP ${r.status}`);
      const j = await r.json();

      els.total.textContent = (j.transaction_total ?? '–');
      els.minS.textContent  = (j.min_support ?? '–');

      const rows = Array.isArray(j.data) ? j.data
                   : (j.data?.rows ?? j.data?.items ?? []);

      els.tbody.innerHTML = '';
      if (!Array.isArray(rows) || rows.length === 0){
        els.tbody.innerHTML = `<tr><td colspan="3" class="text-center text-muted">Tidak ada data itemset 2.</td></tr>`;
      } else {
        rows.forEach(row => {
          const name = extractName(row);
          const freq = row.frequency ?? row.frekuensi ?? row.count ?? row.total ?? '';
          const supp = (row.support ?? row.support_value ?? row.support_percent ?? row.s ?? '');

          const tr = document.createElement('tr');
          tr.innerHTML = `<td>${name || '(?)'}</td><td>${freq}</td><td>${supp}</td>`;
          els.tbody.appendChild(tr);
        });
      }

      els.load.classList.add('d-none');
    } catch (e){
      els.load.classList.add('d-none');
      els.err.textContent = 'Gagal memuat Itemset 2. ' + e.message;
      els.err.classList.remove('d-none');
      console.error(e);
    }
  }

  document.addEventListener('DOMContentLoaded', loadItemset2);
</script>

<?= $this->endSection() ?>
