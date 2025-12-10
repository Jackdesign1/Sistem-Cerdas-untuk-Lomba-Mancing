<?php

require_once __DIR__ . '/env_db.php';   // <-- pakai env_db, hasilnya $pdo
require_once __DIR__ . '/process.php';

$spk = new SPK_Ikan($pdo);

$message = null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] == 'tambah') {
        $nama_peserta = trim($_POST['nama_peserta'] ?? '');
        $no_hp        = trim($_POST['no_hp'] ?? '');
        $berat_kg     = $_POST['berat_kg'] ?? '';
        $panjang_cm   = $_POST['panjang_cm'] ?? '';

        if ($nama_peserta === '' || $berat_kg === '' || $panjang_cm === '') {
            $message = ['status' => 'error', 'message' => 'Nama, berat, dan panjang wajib diisi!'];
        } else {
            // pastikan angka
            $berat_kg   = (float) $berat_kg;
            $panjang_cm = (float) $panjang_cm;

            if ($berat_kg <= 0 || $panjang_cm <= 0) {
                $message = ['status' => 'error', 'message' => 'Berat dan panjang harus lebih dari 0!'];
            } else {
                // method ini nanti kamu definisikan di process.php
                $result  = $spk->tambah_hasil($nama_peserta, $no_hp, $berat_kg, $panjang_cm);
                $message = $result;
            }
        }
    }

    if ($_POST['action'] == 'hapus') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id > 0) {
            $result  = $spk->hapus_hasil($id);
            $message = $result;
        }
    }
}


$hasil_spk = $spk->hitung_spk();

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem SPK Lomba Pemancingan Ikan Nila</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<div id="editModal" class="modal">
    <div class="modal-content">
        <h3>Data Baru Masuk</h3>
        <p style="font-size: 13px; color: #9ca3af; margin-bottom: 10px;">
            Silakan isi nama peserta. Data sensor (berat, panjang, time, timestamp) hanya bisa dibaca.
        </p>

        <form id="popup-form">
            <input type="hidden" name="id" id="popup-id">

            <div class="form-row-vertical">
                <label for="popup-nama">Nama Peserta</label>
                <input type="text" name="nama_peserta" id="popup-nama" required>
            </div>

            <div class="form-row-vertical">
                <label>Berat (kg)</label>
                <input type="text" id="popup-berat" readonly>
            </div>

            <div class="form-row-vertical">
                <label>Panjang (cm)</label>
                <input type="text" id="popup-panjang" readonly>
            </div>

            <div class="form-row-vertical">
                <label>Time</label>
                <input type="text" id="popup-time" readonly>
            </div>

            <div class="form-row-vertical">
                <label>Timestamp</label>
                <input type="text" id="popup-timestamp" readonly>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="hideModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<body>
<div class="container">
    <header>
        <h1>Sistem SPK Lomba Pemancingan Ikan Nila</h1>
    </header>

    <!-- Message -->
    <?php if ($message): ?>
        <div class="alert alert-<?php echo htmlspecialchars($message['status']); ?>">
            <?php echo htmlspecialchars($message['message']); ?>
        </div>
    <?php endif; ?>

    <!-- Form Input Data Hasil Tangkapan -->
    <!-- <section class="form-section">
        <h2>📝 Input Data Hasil Tangkapan</h2>
        <form method="POST" class="form-group">
            <input type="hidden" name="action" value="tambah">

            <div class="form-row">
                <div class="form-col">
                    <label for="nama_peserta">Nama Peserta:</label>
                    <input
                        type="text"
                        id="nama_peserta"
                        name="nama_peserta"
                        required
                        placeholder="Contoh: Budi Santoso"
                        autocomplete="off"
                    >
                </div>

                <div class="form-col">
                    <label for="no_hp">Nomor HP:</label>
                    <input
                        type="text"
                        id="no_hp"
                        name="no_hp"
                        placeholder="Contoh: 08123456789"
                        autocomplete="off"
                    >
                </div>

                <div class="form-col">
                    <label for="berat_kg">Berat Ikan (kg):</label>
                    <input
                        type="number"
                        id="berat_kg"
                        name="berat_kg"
                        step="0.01"
                        min="0"
                        required
                        placeholder="Contoh: 2.5"
                    >
                </div>

                <div class="form-col">
                    <label for="panjang_cm">Panjang Ikan (cm):</label>
                    <input
                        type="number"
                        id="panjang_cm"
                        name="panjang_cm"
                        step="0.01"
                        min="0"
                        required
                        placeholder="Contoh: 30.5"
                    >
                </div>
            </div>

            <button type="submit" class="btn btn-primary">🎯 Input Data Hasil</button>
        </form>
    </section> -->

    <!-- Informasi Kriteria -->
    <section class="info-section">
        <h2>📊 Kriteria Penilaian</h2>
        <div class="kriteria-grid">
            <div class="kriteria-card">
                <h3>⚖️ Berat Ikan</h3>
                <p><strong>Bobot:</strong> 60%</p>
                <p><strong>Tipe:</strong> Benefit</p>
                <p><strong>Penjelasan:</strong> Semakin berat ikan yang ditangkap semakin baik.</p>
            </div>
            <div class="kriteria-card">
                <h3>📏 Panjang Ikan</h3>
                <p><strong>Bobot:</strong> 40%</p>
                <p><strong>Tipe:</strong> Benefit</p>
                <p><strong>Penjelasan:</strong> Semakin panjang ikan yang ditangkap semakin baik.</p>
            </div>
        </div>
    </section>

    <!-- Hasil Perangkingan -->
    <section class="results-section">
        <h2>🏆 Hasil Perangkingan Pemenang</h2>

        <?php if ($hasil_spk && count($hasil_spk) > 0): ?>
            <div class="table-responsive">
                <table class="results-table">
                    <thead>
                    <tr>
                    <th>Ranking</th>
                    <th>Nama Peserta</th>
                    <th>Time (s)</th>
                    <th>Timestamp</th>
                    <th>Berat (kg)</th>
                    <th>Panjang (cm)</th>
                    <th>Norm. Berat</th>
                    <th>Norm. Panjang</th>
                    <th>Skor Akhir</th>
                    <th>Aksi</th>

                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($hasil_spk as $rank => $data): ?>
                        <tr class="<?php
                            echo $rank === 0 ? 'rank-1' :
                                ($rank === 1 ? 'rank-2' :
                                    ($rank === 2 ? 'rank-3' : ''));
                        ?>">
                            <td class="ranking">
                                <?php
                                if ($rank === 0) echo "🥇 1";
                                elseif ($rank === 1) echo "🥈 2";
                                elseif ($rank === 2) echo "🥉 3";
                                else echo "#" . ($rank + 1);
                                ?>
                            </td>
                            <td><?php echo htmlspecialchars($data['nama_peserta']); ?></td>

                                <?php

                                    $timeDisplay = '-';
                                    if (!empty($data['time'])) {
                                        $timeSeconds = $data['time'] / 1000;
                                        $timeDisplay = number_format($timeSeconds, 2) . ' s';
                                    }

                                    $timestampDisplay = '-';
                                    if (!empty($data['timestamp'])) {
                                        try {
                                            $dt = new DateTime($data['timestamp']);
                                            $timestampDisplay = $dt->format('d-m-Y H:i:s');
                                        } catch (Exception $e) {
                                            $timestampDisplay = $data['timestamp']; 
                                        }
                                    }
                                ?>

                                <td><?php echo htmlspecialchars($timeDisplay); ?></td>
                                <td><?php echo htmlspecialchars($timestampDisplay); ?></td>
                                <td><?php echo number_format($data['berat_kg'], 2); ?></td>
                                <td><?php echo number_format($data['panjang_cm'], 2); ?></td>
                                <td><?php echo number_format($data['norm_berat'], 4); ?></td>
                                <td><?php echo number_format($data['norm_panjang'], 4); ?></td>
                                <td class="skor-akhir"><?php echo number_format($data['skor_akhir'], 6); ?></td>
                                <td>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="action" value="hapus">
                                        <input type="hidden" name="id" value="<?php echo (int)$data['id']; ?>">
                                        <button type="submit"
                                                class="btn btn-danger btn-small"
                                                onclick="return confirm('Yakin ingin menghapus data ini?')">
                                            Hapus
                                        </button>
                                    </form>
                                </td>

                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Statistik -->
            <div class="stats-box">
                <h3>📈 Statistik Lomba</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <p class="stat-label">Total Peserta</p>
                        <p class="stat-value"><?php echo count($hasil_spk); ?></p>
                    </div>
                    <div class="stat-item">
                        <p class="stat-label">Pemenang</p>
                        <p class="stat-value"><?php echo htmlspecialchars($hasil_spk[0]['nama_peserta']); ?></p>
                    </div>
                    <div class="stat-item">
                        <p class="stat-label">Skor Tertinggi</p>
                        <p class="stat-value"><?php echo number_format($hasil_spk[0]['skor_akhir'], 6); ?></p>
                    </div>
                    <div class="stat-item">
                        <p class="stat-label">Berat Terberat</p>
                        <p class="stat-value">
                            <?php
                            $berat_max = max(array_column($hasil_spk, 'berat_kg'));
                            echo number_format($berat_max, 2) . ' kg';
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #999; padding: 40px;">
                Belum ada data peserta. Mulai input data hasil tangkapan!
            </p>
        <?php endif; ?>
    </section>

    <!-- Penjelasan Metode WP -->
    <section class="explanation-section">
        <h2>📚 Penjelasan Metode Weighted Product (WP)</h2>

        <div class="explanation-box">
<pre>
Skor WP = (Norm. Berat)^0.60 × (Norm. Panjang)^0.40

Normalisasi Benefit = (Nilai - Min) / (Max - Min)
</pre>
        </div>

        <div class="explanation-box">
            <h3>Penjelasan Singkat:</h3>
            <ul style="margin-left: 20px; margin-top: 10px;">
                <li><strong>Berat Ikan (60%):</strong> Semakin berat → semakin bagus.</li>
                <li><strong>Panjang Ikan (40%):</strong> Semakin panjang → semakin bagus.</li>
                <li><strong>Normalisasi:</strong> Nilai dikonversi ke skala 0–1.</li>
                <li><strong>Skor WP:</strong> Perkalian nilai-nilai yang sudah dinormalisasi, dipangkatkan bobot.</li>
                <li><strong>Ranking:</strong> Diurutkan dari skor tertinggi ke terendah.</li>
            </ul>
        </div>
    </section>

    <footer>
        <p>&copy; 2025 Sistem SPK Lomba Pemancingan Ikan Nila | Metode WP</p>
    </footer>
</div>

<script>
let currentPopupId = null;

function showModal(data) {
    currentPopupId = data.id;

    document.getElementById('popup-id').value        = data.id;
    document.getElementById('popup-nama').value      = data.nama_peserta || '';
    document.getElementById('popup-berat').value     = parseFloat(data.berat).toFixed(2);
    document.getElementById('popup-panjang').value   = parseFloat(data.panjang).toFixed(2);

    // time (ms) -> detik
    if (data.time) {
        const sec = (data.time / 1000).toFixed(2);
        document.getElementById('popup-time').value = sec + ' s';
    } else {
        document.getElementById('popup-time').value = '-';
    }

    document.getElementById('popup-timestamp').value = data.timestamp || '-';

    document.getElementById('editModal').classList.add('show');
}

function hideModal() {
    document.getElementById('editModal').classList.remove('show');
    currentPopupId = null;
}

// Cek data baru tiap 3 detik
function checkNewData() {
    // kalau popup lagi kebuka, jangan cek dulu
    if (currentPopupId !== null) return;

    fetch('get_latest_pending.php')
        .then(r => r.json())
        .then(res => {
            if (res.status === 'ok' && res.data) {
                showModal(res.data);
            }
        })
        .catch(err => {
            console.error('Error cek data baru:', err);
        });
}

setInterval(checkNewData, 3000);

// submit form popup via AJAX
document.getElementById('popup-form').addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);

    fetch('update_peserta.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.status === 'success') {
            hideModal();
            // refresh halaman supaya ranking SPK ikut update
            window.location.reload();
        } else {
            alert(res.message || 'Gagal menyimpan data');
        }
    })
    .catch(err => {
        console.error('Error submit popup:', err);
        alert('Terjadi kesalahan saat menyimpan data.');
    });
});
</script>

</body>
</html>
