<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Test API ESP32 - PlasticPoint</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background: #eef1f5; padding: 32px 16px; }
  .wrap { max-width: 680px; margin: 0 auto; }
  h1 { font-size: 20px; font-weight: 700; color: #0f2147; margin-bottom: 6px; }
  .sub { font-size: 13px; color: #6b7280; margin-bottom: 28px; }

  .panel { background: #fff; border-radius: 12px; padding: 24px; box-shadow: 0 1px 4px rgba(0,0,0,0.07); margin-bottom: 20px; }
  .panel h2 { font-size: 15px; font-weight: 700; margin-bottom: 16px; color: #1f2937; }

  .grid2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  label { display: block; font-size: 12.5px; font-weight: 600; color: #374151; margin-bottom: 4px; }
  input, select {
    width: 100%; padding: 9px 12px;
    border: 1px solid #d1d5db; border-radius: 8px;
    font-size: 13.5px; outline: none;
  }
  input:focus, select:focus { border-color: #0f2147; }
  .hint { font-size: 11.5px; color: #9ca3af; margin-top: 4px; }

  .btn {
    width: 100%; padding: 11px;
    background: #0f2147; color: #fff;
    border: none; border-radius: 8px;
    font-size: 14px; font-weight: 700; cursor: pointer;
    margin-top: 18px; transition: background 0.2s;
    letter-spacing: 0.3px;
  }
  .btn:hover { background: #16a34a; }
  .btn:disabled { background: #9ca3af; cursor: not-allowed; }

  /* Response box */
  .response-box {
    background: #0f172a; color: #e2e8f0;
    border-radius: 10px; padding: 18px;
    font-family: 'Courier New', monospace;
    font-size: 13px; line-height: 1.7;
    white-space: pre-wrap; word-break: break-all;
    min-height: 80px;
  }
  .response-box .sukses  { color: #4ade80; }
  .response-box .error   { color: #f87171; }
  .response-box .netral  { color: #94a3b8; font-style: italic; }

  .badge-status {
    display: inline-block; padding: 3px 12px;
    border-radius: 6px; font-size: 12px; font-weight: 700;
    margin-bottom: 10px;
  }
  .badge-status.ok  { background: #dcfce7; color: #16a34a; }
  .badge-status.err { background: #fee2e2; color: #dc2626; }
  .badge-status.wait { background: #fef9c3; color: #854d0e; }

  /* Curl snippet */
  .curl-box {
    background: #1e293b; color: #94a3b8;
    border-radius: 8px; padding: 14px 16px;
    font-family: 'Courier New', monospace;
    font-size: 12px; line-height: 1.7;
    white-space: pre-wrap; word-break: break-all;
    margin-top: 0;
  }
  .curl-box .hl { color: #7dd3fc; }
</style>
</head>
<body>
<div class="wrap">
  <h1>🔧 Simulator Kiriman ESP32</h1>
  <p class="sub">Halaman ini mensimulasikan data yang akan dikirim alat RVM ke server. Gunakan untuk testing sebelum ESP32 jadi.</p>

  <!-- Form kirim data -->
  <div class="panel">
    <h2>Kirim data setor ke API</h2>
    <div class="grid2">
      <div>
        <label for="user_id">User ID</label>
        <input type="number" id="user_id" value="2" min="1">
        <div class="hint">ID user dari tabel users (role = user)</div>
      </div>
      <div>
        <label for="jenis_sampah">Jenis Sampah</label>
        <select id="jenis_sampah">
          <option value="PET">PET</option>
          <option value="PP">PP</option>
        </select>
      </div>
      <div>
        <label for="jumlah">Jumlah (unit)</label>
        <input type="number" id="jumlah" value="1" min="1">
      </div>
      <div>
        <label for="confidence">Confidence CNN (0–1)</label>
        <input type="number" id="confidence" value="0.95" min="0" max="1" step="0.01">
        <div class="hint">Nilai keyakinan hasil klasifikasi model</div>
      </div>
      <div>
        <label for="status_infrared">Status Infrared</label>
        <select id="status_infrared">
          <option value="terdeteksi">terdeteksi</option>
          <option value="tidak_terdeteksi">tidak_terdeteksi</option>
        </select>
      </div>
      <div>
        <label for="status_servo">Status Servo</label>
        <select id="status_servo">
          <option value="terbuka">terbuka</option>
          <option value="tertutup">tertutup</option>
        </select>
      </div>
    </div>
    <button class="btn" id="btn-kirim" onclick="kirimData()">▶ Kirim ke API</button>
  </div>

  <!-- Hasil response -->
  <div class="panel">
    <h2>Response dari server</h2>
    <span class="badge-status wait" id="badge">Menunggu kiriman...</span>
    <div class="response-box" id="response-box">
      <span class="netral">Response JSON dari server akan muncul di sini setelah tombol ditekan.</span>
    </div>
  </div>

  <!-- Curl reference -->
  <div class="panel">
    <h2>Referensi: kirim manual via curl (cmd/terminal)</h2>
    <div class="curl-box" id="curl-box"><span class="hl">curl</span> -X POST http://localhost/rvm-web/api/esp32_input.php \
  -H <span class="hl">"Content-Type: application/json"</span> \
  -H <span class="hl">"X-API-KEY: rvm-secret-2025"</span> \
  -d <span class="hl">'{"user_id":2,"jenis_sampah":"PET","jumlah":1,"confidence":0.95,"status_infrared":"terdeteksi","status_servo":"terbuka"}'</span></div>
    <p style="font-size:12px; color:#9ca3af; margin-top:10px;">
      Nanti di ESP32, ganti <code>localhost</code> dengan IP komputer kamu di jaringan WiFi yang sama (misal <code>192.168.1.5</code>).
    </p>
  </div>

  <!-- Referensi kode ESP32 -->
  <div class="panel">
    <h2>Referensi: potongan kode ESP32 (Arduino)</h2>
    <div class="curl-box"><span class="hl">#include</span> &lt;WiFi.h&gt;
<span class="hl">#include</span> &lt;HTTPClient.h&gt;
<span class="hl">#include</span> &lt;ArduinoJson.h&gt;

<span class="hl">const char*</span> serverURL = <span class="hl">"http://192.168.1.5/rvm-web/api/esp32_input.php"</span>;
<span class="hl">const char*</span> apiKey   = <span class="hl">"rvm-secret-2025"</span>;

<span class="hl">void</span> kirimDataKeServer(<span class="hl">int</span> userId, <span class="hl">String</span> jenis, <span class="hl">float</span> conf) {
  HTTPClient http;
  http.begin(serverURL);
  http.addHeader(<span class="hl">"Content-Type"</span>, <span class="hl">"application/json"</span>);
  http.addHeader(<span class="hl">"X-API-KEY"</span>, apiKey);

  StaticJsonDocument&lt;256&gt; doc;
  doc[<span class="hl">"user_id"</span>]         = userId;
  doc[<span class="hl">"jenis_sampah"</span>]    = jenis;
  doc[<span class="hl">"jumlah"</span>]          = 1;
  doc[<span class="hl">"confidence"</span>]      = conf;
  doc[<span class="hl">"status_infrared"</span>] = <span class="hl">"terdeteksi"</span>;
  doc[<span class="hl">"status_servo"</span>]    = <span class="hl">"terbuka"</span>;

  <span class="hl">String</span> jsonStr;
  serializeJson(doc, jsonStr);

  <span class="hl">int</span> httpCode = http.POST(jsonStr);
  <span class="hl">String</span> response = http.getString();
  Serial.println(response); <span class="hl">// cek di Serial Monitor</span>
  http.end();
}</div>
    <p style="font-size:12px; color:#9ca3af; margin-top:10px;">
      Library yang dibutuhkan: <strong>HTTPClient</strong> (bawaan ESP32), <strong>ArduinoJson</strong> (install via Library Manager).
    </p>
  </div>
</div>

<script>
async function kirimData() {
  const btn = document.getElementById('btn-kirim');
  const box = document.getElementById('response-box');
  const badge = document.getElementById('badge');

  btn.disabled = true;
  btn.textContent = 'Mengirim...';
  badge.className = 'badge-status wait';
  badge.textContent = 'Mengirim...';
  box.innerHTML = '<span class="netral">Menunggu response dari server...</span>';

  const payload = {
    user_id:         parseInt(document.getElementById('user_id').value),
    jenis_sampah:    document.getElementById('jenis_sampah').value,
    jumlah:          parseInt(document.getElementById('jumlah').value),
    confidence:      parseFloat(document.getElementById('confidence').value),
    status_infrared: document.getElementById('status_infrared').value,
    status_servo:    document.getElementById('status_servo').value,
  };

  // Update curl box realtime sesuai input
  document.getElementById('curl-box').innerHTML = updateCurl(payload);

  try {
    const res = await fetch('esp32_input.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-API-KEY': 'rvm-secret-2025'
      },
      body: JSON.stringify(payload)
    });

    const data = await res.json();
    const json = JSON.stringify(data, null, 2);

    if (data.status === 'sukses') {
      badge.className = 'badge-status ok';
      badge.textContent = '✓ Sukses (' + res.status + ')';
      box.innerHTML = '<span class="sukses">' + escHtml(json) + '</span>';
    } else {
      badge.className = 'badge-status err';
      badge.textContent = '✗ Error (' + res.status + ')';
      box.innerHTML = '<span class="error">' + escHtml(json) + '</span>';
    }

  } catch (e) {
    badge.className = 'badge-status err';
    badge.textContent = '✗ Gagal terhubung';
    box.innerHTML = '<span class="error">Tidak bisa terhubung ke server.\n' + e.message + '</span>';
  }

  btn.disabled = false;
  btn.textContent = '▶ Kirim ke API';
}

function escHtml(str) {
  return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

function updateCurl(p) {
  const body = JSON.stringify(p);
  return `<span class="hl">curl</span> -X POST http://localhost/rvm-web/api/esp32_input.php \\\n  -H <span class="hl">"Content-Type: application/json"</span> \\\n  -H <span class="hl">"X-API-KEY: rvm-secret-2025"</span> \\\n  -d <span class="hl">'${body}'</span>`;
}

// Update curl box saat input berubah
document.querySelectorAll('input, select').forEach(el => {
  el.addEventListener('input', () => {
    const p = {
      user_id: parseInt(document.getElementById('user_id').value) || 2,
      jenis_sampah: document.getElementById('jenis_sampah').value,
      jumlah: parseInt(document.getElementById('jumlah').value) || 1,
      confidence: parseFloat(document.getElementById('confidence').value) || 0.95,
      status_infrared: document.getElementById('status_infrared').value,
      status_servo: document.getElementById('status_servo').value,
    };
    document.getElementById('curl-box').innerHTML = updateCurl(p);
  });
});
</script>
</body>
</html>
