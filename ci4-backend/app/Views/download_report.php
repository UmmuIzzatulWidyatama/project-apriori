<?php
// Helper kecil untuk ambil teks frequent / association
$freq  = $insights_frequent_all ?? [];
$assoc = $insights_association_all ?? [];

// Sanitize printer
function escp($s){ return htmlspecialchars((string)($s ?? ''), ENT_QUOTES, 'UTF-8'); }

// Ambil & urutkan k (hanya yang numerik) untuk frequent
$freqKs = [];
if (is_array($freq)) {
  foreach ($freq as $k => $arr) {
    if (is_numeric($k)) $freqKs[] = (int)$k;
  }
  sort($freqKs, SORT_NUMERIC);
}

// Ambil & urutkan k (hanya yang numerik) untuk association
$assocKs = [];
if (is_array($assoc)) {
  foreach ($assoc as $k => $arr) {
    if (is_numeric($k)) $assocKs[] = (int)$k;
  }
  sort($assocKs, SORT_NUMERIC);
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Laporan Apriori</title>
<style>
  @page { margin: 28mm 18mm 20mm 18mm; }
  body { font-family: DejaVu Sans, Arial, Helvetica, sans-serif; font-size: 12px; color:#111; }
  .header { display:flex; align-items:center; gap:16px; margin-bottom:12px; }
  .title-wrap { text-align:center; flex:1; }
  .title { font-weight:700; font-size:16px; margin:0; }
  .subtitle { margin:4px 0 0; }
  .meta { margin-top:20px; width:100%; border-collapse:collapse; }
  .meta td { padding:4px 1px; vertical-align:top; }
  .meta .k { width:150px; }
  .section { margin-top:16px; }
  .section h4 { margin:0 0 6px 0; font-size:13px; }
  .line { margin:2px 0; }
  .muted { color:#666; }
  .strong { font-weight:700; }
  .mb8 { margin-bottom:8px; }
  .hr { height:1px; background:#ddd; border:0; margin:10px 0; }
</style>
</head>
<body>

  <div class="header">
    <div class="title-wrap">
      <p class="title">Laporan Analisis Apriori Data Penjualan Produk</p>
      <p class="subtitle">Toko Djati Intan Barokah</p>
    </div>
  </div>

  <table class="meta">
    <tr><td class="k">Nama Analisis</td><td>:</td><td><?= escp($title ?? '-') ?></td></tr>
    <tr><td class="k">Deskripsi</td><td>:</td><td><?= escp($description ?? '-') ?></td></tr>
    <tr><td class="k">Tanggal Awal</td><td>:</td><td><?= escp($start_date ?? '-') ?></td></tr>
    <tr><td class="k">Tanggal Akhir</td><td>:</td><td><?= escp($end_date ?? '-') ?></td></tr>
    <tr><td class="k">Min Support</td><td>:</td><td><?= escp($min_support ?? '-') ?></td></tr>
    <tr><td class="k">Min Confidence</td><td>:</td><td><?= escp($min_confidence ?? '-') ?></td></tr>
    <tr><td class="k">Jumlah Transaksi</td><td>:</td><td><?= escp($transaction_total ?? '-') ?></td></tr>
  </table>

  <!-- Frequent (dinamis 1..n) -->
  <div class="section">
    <h4>Insight Frequent Itemset</h4>
    <?php if (!empty($freqKs)): ?>
      <?php foreach ($freqKs as $k): ?>
        <?php
          $arr  = $freq[(string)$k] ?? [];
          $text = isset($arr[0]['text']) ? (string)$arr[0]['text'] : '-';
        ?>
        <div class="line">Frequent <?= (int)$k ?>-Itemset : <?= escp($text) ?></div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="line muted">Tidak ada insight frequent itemset.</div>
    <?php endif; ?>
  </div>

  <!-- Association (juga dinamis biar seragam) -->
  <div class="section">
    <span class="strong">Insight Association Rule</span>
    <?php if (!empty($assocKs)): ?>
      <?php foreach ($assocKs as $k): ?>
        <?php
          $arr  = $assoc[(string)$k] ?? [];
          $text = isset($arr[0]['text']) ? (string)$arr[0]['text'] : '-';
        ?>
        <div class="line">Association Rule <?= (int)$k ?>-Itemset : <?= escp($text) ?></div>
      <?php endforeach; ?>
    <?php else: ?>
      <div class="line muted">Tidak ada insight association rule.</div>
    <?php endif; ?>
  </div>

  <div class="section">
    <div class="line"><span class="strong">Insight Lift Ratio :</span> <?= escp($insight_lift_ratio ?? '-') ?></div>
  </div>

  <div class="section">
    <div class="line"><span class="strong">Insight Strategis :</span> <?= escp($insight_strategis ?? '-') ?></div>
  </div>

</body>
</html>
