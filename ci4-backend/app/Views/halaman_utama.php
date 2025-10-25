<?= $this->extend('layout/sidebar') ?>
<?= $this->section('content') ?>

<style>
  .page-wrap { max-width: 1080px; }
  .hero {
    border:1px solid #e5e7eb; border-radius:.6rem; background:#fff;
    padding:18px; display:grid; grid-template-columns: 240px 1fr; gap:18px;
  }
  .card-kpi { border:1px solid #e5e7eb; border-radius:.6rem; background:#fff; padding:18px; }
  .kpi-grid { display:grid; grid-template-columns: 1fr 1fr; gap:18px; }
  .card-full { grid-column: 1 / -1; }
  .card-split { display:grid; grid-template-columns: 1fr 1fr; gap:0px; align-items:center; }
  @media (max-width: 768px){
    .hero{grid-template-columns:1fr;}
    .kpi-grid{grid-template-columns:1fr;}
    .card-split{grid-template-columns:1fr;}
  }
  .kpi-title{ color:#6c757d; margin-bottom:6px; }
  .kpi-value{ font-size:1.25rem; font-weight:700; }
  .logo-img{ max-width:100%; max-height:200px; object-fit:contain; display:block; }
  .pie-wrap{ width:300px; margin:0; }
  @media (max-width:768px){ .pie-wrap{ width:140px; } }
</style>

<div class="container-fluid mt-4 px-3">
  <div class="row">
    <div class="col-12 col-lg-11 col-xxl-10 page-wrap">

      <h3 class="fw-bold mb-3">Selamat Datang</h3>

      <!-- Hero -->
      <div class="hero mb-3">
        <img
          src="<?= base_url('assets/img/logo.png?v=1') ?>"
          alt="Logo Toko"
          class="logo-img"
          onerror="this.replaceWith(document.createTextNode('Logo Toko'));"
        >
        <div>
          <h5 class="fw-semibold mb-2">Aplikasi Apriori Toko Djati Intan Barokah</h5>
          <p class="mb-3 text-muted">
            Analisis transaksi untuk menemukan pasangan produk yang sering dibeli bersama
            agar promosi, bundling, dan penempatan produk lebih tepat sasaran.
          </p>
          <a href="http://localhost:8080/apriori" class="btn btn-primary btn-sm">
            Buat Analisis
          </a>
        </div>
      </div>

      <!-- KPIs -->
      <div class="kpi-grid">
        <div class="card-kpi">
          <div class="kpi-title">Jumlah Data Transaksi</div>
          <div class="kpi-value"><span id="v_trx">–</span> Data</div>
        </div>
        <div class="card-kpi">
          <div class="kpi-title">Jumlah Analisis</div>
          <div class="kpi-value"><span id="v_analisis">–</span> Data</div>
        </div>

        <div class="card-kpi card-full">
          <div class="kpi-title mb-2">Top 3 Produk Paling Sering Muncul Dalam Transaksi</div>

          <div class="card-split">
            <div class="pie-wrap">
              <canvas id="pieTopProducts"></canvas>
            </div>
            <div id="pieState" class="small text-muted mt-1">Memuat…</div>
            <div>
              <p class="mb-3 text-muted">
                Produk yang paling sering dibeli bersama dengan produk lainnya pada semua hasil analisis.
              </p>
              <a href="http://localhost:8080/report" class="btn btn-outline-primary btn-sm">
                Lihat hasil analisis
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  const apiUrl = "<?= rtrim(site_url('api/summary'), '/') ?>";
  const el = id => document.getElementById(id);
  const nInt = x => (x==null||x==='') ? '–'
                 : new Intl.NumberFormat('id-ID', {maximumFractionDigits:0}).format(Number(x));

  (async () => {
    try{
      const res = await fetch(apiUrl, { headers:{'Accept':'application/json'} });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      const j = await res.json();

      el('v_trx').textContent      = nInt(j?.data?.jumlahDataTransaksi);
      el('v_analisis').textContent = nInt(j?.data?.jumlahDataAnalisis);

    } catch(e){
      console.error(e);
      el('v_trx').textContent = '0';
      el('v_analisis').textContent = '0';
    }
  })();
</script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

<script>
  const apiTop = "<?= site_url('api/summary/top-products?limit=3') ?>";
  const pieState = document.getElementById('pieState');
  let pieChart = null;

  async function renderTopProductsPie(){
    try{
      const r = await fetch(apiTop, { headers:{'Accept':'application/json'} });
      if (!r.ok) throw new Error('HTTP ' + r.status);
      const j = await r.json();
      const rows = Array.isArray(j?.data) ? j.data : [];

      if (!rows.length){
        pieState.textContent = 'Tidak ada data.';
        return;
      }

      const labels = rows.map(x => x.product);
      const values = rows.map(x => x.value);

      const canvas = document.getElementById('pieTopProducts');
      if (!canvas) throw new Error('Canvas pieTopProducts tidak ditemukan');
      const ctx = canvas.getContext('2d');

      if (pieChart) pieChart.destroy();
      pieChart = new Chart(ctx, {
        type: 'pie',
        data: { labels, datasets: [{ data: values }] },
        options: {
          plugins: {
            legend: { position: 'bottom' },
            tooltip: { callbacks: { label: (c) => `${c.label}: ${c.formattedValue}` } }
          }
        }
      });

      pieState.classList.add('d-none');
    } catch (e){
      console.error(e);
      pieState.classList.remove('text-muted');
      pieState.classList.add('text-danger');
      pieState.textContent = 'Gagal memuat chart: ' + e.message;
    }
  }
  renderTopProductsPie();
</script>

<?= $this->endSection() ?>
