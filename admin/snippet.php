<?php
require_once __DIR__ . '/guard.php';
require_once __DIR__ . '/layout.php';
$user = require_user();

$limit = min(12, max(1, (int)($_GET['limit'] ?? 3)));
$rows = pdo_app()->query(
    "SELECT id, tag, short_title, front_image, full_title
     FROM berita WHERE status = 'published'
     ORDER BY no_urut ASC, published_at DESC LIMIT {$limit}")->fetchAll();

$cards = '';
foreach ($rows as $r) {
    $img = $r['front_image'] ? e(BASE_URL . '/' . $r['front_image']) : 'https://placehold.co/600x400/eef1f5/94a3b8?text=LJU+News';
    $cards .= <<<HTML
  <article style="background:#fff;border:1px solid #e5e9f0;border-radius:16px;overflow:hidden;display:flex;flex-direction:column">
    <img src="{$img}" alt="" style="width:100%;height:170px;object-fit:cover">
    <div style="padding:18px 20px;display:flex;flex-direction:column;gap:8px;flex:1">
      <span style="font-size:11px;font-weight:700;letter-spacing:1px;text-transform:uppercase;color:#1f6fb2">{$r['tag']}</span>
      <h3 style="font-size:15.5px;line-height:1.4;color:#0d2b4e;margin:0">{$r['short_title']}</h3>
      <a href="{$r['link']}" style="margin-top:auto;font-size:13px;font-weight:600">Baca selengkapnya →</a>
    </div>
  </article>\n
HTML;
    $cards = str_replace(['{$r[\'tag\']}', '{$r[\'short_title\']}', '{$r[\'link\']}'],
        [e($r['tag']), e($r['short_title']), e(NEWS_DETAIL_URL . $r['id'])], $cards);
}
$snippet = "<!-- LJU Kegiatan Terbaru — generated " . date('Y-m-d H:i') . " -->\n"
         . "<section style=\"padding:56px 6%;background:#f8fafc\">\n"
         . "  <h2 style=\"font-size:24px;color:#0d2b4e;margin:0 0 6px\">Kegiatan Terbaru</h2>\n"
         . "  <p style=\"color:#64748b;margin:0 0 28px\">Update Informasi LJU</p>\n"
         . "  <div style=\"display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px\">\n"
         . $cards
         . "  </div>\n</section>";

page_header('Snippet Homepage', $user, 'snippet');
?>
<div class="card">
    <div class="toolbar" style="margin-bottom:14px">
        <div style="font-size:13px;color:var(--muted)">Salin kode di bawah, lalu ganti section “Kegiatan Terbaru” pada <b>index.html</b> lju.co.id.</div>
        <div style="display:flex;gap:10px">
            <a class="btn btn-outline btn-sm" href="?limit=3">3 item</a>
            <a class="btn btn-outline btn-sm" href="?limit=6">6 item</a>
            <button class="btn btn-primary btn-sm" onclick="navigator.clipboard.writeText(document.getElementById('snippet').value);this.textContent='Tersalin ✓';setTimeout(()=>this.textContent='Copy HTML',1500)">Copy HTML</button>
        </div>
    </div>
    <textarea id="snippet" readonly rows="22" style="font-family:monospace;font-size:12px;background:#0d2b4e;color:#d7e4f4;border:0"><?= e($snippet) ?></textarea>
</div>

<div class="card" style="margin-top:20px">
    <h2 style="font-size:15px;color:var(--navy);margin-bottom:12px">Preview</h2>
    <div style="border:1px solid var(--line);border-radius:12px;padding:20px;overflow:auto"><?= $snippet ?></div>
</div>
<?php page_footer();
