<?php

class SPK_Ikan
{
    private $pdo;
    private $bobot_berat   = 0.60; 
    private $bobot_panjang = 0.40; 

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function tambah_hasil($nama_peserta, $no_hp, $berat_kg, $panjang_cm)
    {
        $nama_peserta = trim($nama_peserta);
        $berat_kg     = (float)$berat_kg;
        $panjang_cm   = (float)$panjang_cm;

        if ($nama_peserta === '' || $berat_kg <= 0 || $panjang_cm <= 0) {
            return [
                'status'  => 'error',
                'message' => 'Nama peserta, berat, dan panjang wajib diisi dengan benar'
            ];
        }

        $sql = "INSERT INTO data_ikan (nama_peserta, berat, panjang)
                VALUES (:nama_peserta, :berat, :panjang)";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nama_peserta' => $nama_peserta,
                ':berat'        => $berat_kg,
                ':panjang'      => $panjang_cm,
            ]);

            return [
                'status'  => 'success',
                'message' => 'Data hasil tangkapan berhasil disimpan'
            ];
        } catch (PDOException $e) {
            return [
                'status'  => 'error',
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ];
        }
    }

    public function hapus_hasil($id)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return [
                'status'  => 'error',
                'message' => 'ID tidak valid'
            ];
        }

        $sql = "DELETE FROM data_ikan WHERE id = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':id' => $id]);

            return [
                'status'  => 'success',
                'message' => 'Data berhasil dihapus'
            ];
        } catch (PDOException $e) {
            return [
                'status'  => 'error',
                'message' => 'Gagal menghapus data: ' . $e->getMessage()
            ];
        }
    }

    private function get_all_data()
    {
        $sql = "SELECT id, nama_peserta, `timestamp`, `time`, berat, panjang 
                FROM data_ikan";

        try {
            $stmt = $this->pdo->query($sql);
            $rows = $stmt->fetchAll();
            return $rows ?: [];
        } catch (PDOException $e) {
            return [];
        }
    }

    public function hitung_spk()
    {
        $rows = $this->get_all_data();
        if (empty($rows)) {
            return [];
        }

        $berat_list   = array_column($rows, 'berat');
        $panjang_list = array_column($rows, 'panjang');

        $min_berat   = min($berat_list);
        $max_berat   = max($berat_list);
        $min_panjang = min($panjang_list);
        $max_panjang = max($panjang_list);

        $range_berat   = $max_berat - $min_berat;
        $range_panjang = $max_panjang - $min_panjang;

        $hasil = [];

        foreach ($rows as $row) {
            $berat   = (float)$row['berat'];
            $panjang = (float)$row['panjang'];

            if ($range_berat == 0) {
                $norm_berat = 1;
            } else {
                $norm_berat = ($berat - $min_berat) / $range_berat;
            }

            if ($range_panjang == 0) {
                $norm_panjang = 1;
            } else {
                $norm_panjang = ($panjang - $min_panjang) / $range_panjang;
            }

            $epsilon          = 1e-9;
            $norm_berat_wp    = max($norm_berat, $epsilon);
            $norm_panjang_wp  = max($norm_panjang, $epsilon);

            $skor = pow($norm_berat_wp, $this->bobot_berat) *
                    pow($norm_panjang_wp, $this->bobot_panjang);

            $hasil[] = [
                'id'            => (int)$row['id'],
                'nama_peserta'  => $row['nama_peserta'],
                'time'          => $row['time'],     
                'timestamp'     => $row['timestamp'],   
                'berat_kg'      => $berat,
                'panjang_cm'    => $panjang,
                'norm_berat'    => $norm_berat,
                'norm_panjang'  => $norm_panjang,
                'skor_akhir'    => $skor,
            ];
        }

        usort($hasil, function ($a, $b) {
            if ($a['skor_akhir'] == $b['skor_akhir']) return 0;
            return ($a['skor_akhir'] < $b['skor_akhir']) ? 1 : -1;
        });

        return $hasil;
    }
}
