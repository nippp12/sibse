<?php

namespace App\Services;

use App\Models\Broadcast;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Exception;
use Illuminate\Support\Facades\Log; // Untuk logging error

class Fonnte
{
    protected string $baseUrl;
    protected string $apiKey;
    protected ?string $deviceId; // Device ID bisa null jika tidak digunakan atau tidak spesifik

    public function __construct()
    {
        // LANGSUNG DARI ENV() SESUAI KEINGINAN ANDA
        $this->baseUrl = env('FONNTE_BASE_URL', 'https://api.fonnte.com'); // Ambil dari .env, dengan fallback
        $this->apiKey = env('FONNTE_API_KEY'); // Ambil langsung dari .env
        $this->deviceId = env('FONNTE_DEVICE_ID'); // Ambil langsung dari .env jika ada

        if (empty($this->apiKey)) {
            Log::error('Fonnte API Key tidak ditemukan di .env. Pengiriman pesan akan gagal.');
            // Ini akan memastikan log error muncul jika API key kosong
        }
    }

    /**
     * Mengirim permintaan HTTP ke API Fonnte.
     * Metode ini bersifat private karena hanya dipanggil secara internal oleh service ini.
     *
     * @param array $params Parameter untuk permintaan API (target, message, data, dll).
     * @return array Respon dari API Fonnte dalam format standar (status, message, data/id).
     */
    private function sendRequest(array $params): array
    {
        if (empty($this->apiKey)) {
            return ['status' => false, 'message' => 'Fonnte API Key tidak dikonfigurasi.'];
        }

        // Tambahkan device ID jika ada dan belum ada di params
        if ($this->deviceId && !isset($params['device'])) {
            $params['device'] = $this->deviceId;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => $this->apiKey,
            ])
            ->timeout(30) // Timeout 30 detik untuk permintaan
            ->post($this->baseUrl . '/send', $params); // Endpoint '/send' untuk semua jenis kirim

            $responseData = $response->json();

            // Log respons lengkap untuk debugging
            Log::info('Fonnte API Response (Raw):', [
                'http_status' => $response->status(),
                'response_body' => $responseData,
                'request_params' => $params,
                'used_base_url' => $this->baseUrl // Tambahkan ini untuk debug
            ]);

            // Cek status HTTP response
            if ($response->serverError()) {
                $errorMessage = $responseData['reason'] ?? $responseData['message'] ?? 'Terjadi kesalahan server Fonnte yang tidak diketahui.';
                Log::error('Fonnte API Server Error:', [
                    'status_code' => $response->status(),
                    'response_data' => $responseData,
                    'request_params' => $params
                ]);
                return ['status' => false, 'message' => 'Terjadi kesalahan server Fonnte: ' . $errorMessage];
            }

            if ($response->clientError()) {
                $errorMessage = $responseData['reason'] ?? $responseData['message'] ?? 'Permintaan ke Fonnte gagal (kesalahan klien).';
                Log::warning('Fonnte API Client Error:', [
                    'status_code' => $response->status(),
                    'response_data' => $responseData,
                    'request_params' => $params
                ]);
                if ($response->status() === 401 || $response->status() === 403) {
                    return ['status' => false, 'message' => 'API Key Fonnte tidak valid atau tidak memiliki izin.'];
                }
                return ['status' => false, 'message' => $errorMessage];
            }

            if (isset($responseData['status']) && $responseData['status'] === false) {
                $reason = $responseData['reason'] ?? 'Kesalahan tidak diketahui dari Fonnte API.';
                Log::error('Fonnte API Application Error (status: false):', [
                    'reason' => $reason,
                    'response_data' => $responseData,
                    'request_params' => $params
                ]);
                return ['status' => false, 'message' => $reason];
            }

            return [
                'status' => true,
                'data' => $responseData['detail'] ?? ($responseData['id'] ?? $responseData)
            ];

        } catch (Exception $e) {
            Log::error('Fonnte HTTP Request Exception:', [
                'error_message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_params' => $params
            ]);
            return ['status' => false, 'message' => 'Gagal terhubung ke Fonnte API: ' . $e->getMessage()];
        }
    }

    /**
     * Memformat nomor telepon agar sesuai dengan format Fonnte (62xxxxxxxxxx).
     *
     * @param string $phoneNumber Nomor telepon yang akan diformat.
     * @return string Nomor telepon yang diformat.
     */
    public function formatPhoneNumber(string $phoneNumber): string
    {
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);

        if (str_starts_with($phoneNumber, '+')) {
            $phoneNumber = substr($phoneNumber, 1);
        }

        if (str_starts_with($phoneNumber, '0')) {
            $phoneNumber = '62' . substr($phoneNumber, 1);
        } elseif (!str_starts_with($phoneNumber, '62')) {
            $phoneNumber = '62' . $phoneNumber;
        }

        return $phoneNumber;
    }

    /**
     * Menyiapkan pesan broadcast dengan melakukan substitusi placeholder dan formatting.
     * Metode ini menerima instance Broadcast dan User untuk mempersonalisasi pesan.
     *
     * @param Broadcast $broadcast Instance Broadcast.
     * @param User $user Instance User (penerima).
     * @return string Pesan yang sudah diformat dan siap dikirim.
     */
    public function prepareMessage(Broadcast $broadcast, User $user): string
    {
        $message = '';
        $message .= "📢 *{$broadcast->judul}*\n\n";
        if ($broadcast->mention_user) {
            $userName = $user->username ?? $user->name ?? 'Penerima Yang Terhormat';
            $message .= "Kepada Yth. *{$userName}*\n";
        }
        if ($broadcast->jenis) $message .= "Perihal: {$broadcast->jenis}\n";


        $message .= "\n" . strip_tags($broadcast->pesan) . "\n\n";

        if ($broadcast->tanggal_acara) $message .= "📅 Tanggal: {$broadcast->tanggal_acara->format('d F Y')}\n";
        if ($broadcast->lokasi) $message .= "📍 Lokasi: {$broadcast->lokasi}";

        return $message;
    }

    /**
     * Mengirim pesan tunggal ke satu nomor telepon.
     * Mengembalikan status keberhasilan dan ID pesan (jika ada).
     *
     * @param string $phoneNumber Nomor telepon tujuan.
     * @param string $message Pesan yang akan dikirim.
     * @return array Hasil pengiriman: ['status' => bool, 'message' => string, 'message_id' => string|null]
     */
    public function sendSingleMessage(string $phoneNumber, string $message): array
    {
        $formattedPhoneNumber = $this->formatPhoneNumber($phoneNumber);

        if (empty($formattedPhoneNumber) || strlen($formattedPhoneNumber) < 9) {
            return ['status' => false, 'message' => 'Nomor telepon penerima tidak valid setelah diformat.'];
        }

        $params = [
            'target' => $formattedPhoneNumber,
            'message' => $message,
        ];

        $res = $this->sendRequest($params);

        if ($res['status']) {
            $messageId = $res['data']['id'] ?? ($res['data'] ?? null);
            return ['status' => true, 'message_id' => $messageId, 'message' => 'Pesan berhasil dikirim ke Fonnte.', 'deskripsi_raw' => json_encode($res['data'])];
        }

        return ['status' => false, 'message' => $res['message'] ?? 'Gagal mengirim pesan tunggal.', 'deskripsi_raw' => json_encode($res['data'] ?? $res['message'])];
    }

    /**
     * Mengirim beberapa pesan sekaligus (bulk send) menggunakan Fonnte.
     * Metode ini menerima array data pesan yang sudah diformat untuk Fonnte API.
     *
     * @param array $messagesData Array data pesan, e.g., [['broadcast_user_id' => 1, 'target' => '628...', 'message' => '...'], ...]
     * @return array Hasil pengiriman massal: ['status' => bool, 'message' => string, 'message_data' => array]
     */
    public function sendBulkMessages(array $messagesData): array
    {
        if (empty($messagesData)) {
            return ['status' => false, 'message' => 'Tidak ada pesan untuk dikirim secara massal.'];
        }

        $fonnteBulkData = [];
        foreach ($messagesData as $msg) {
            $formattedTarget = $this->formatPhoneNumber($msg['target']);
            if (!empty($formattedTarget) && strlen($formattedTarget) >= 9) {
                $fonnteBulkData[] = [
                    'target' => $formattedTarget,
                    'message' => $msg['message'],
                ];
            } else {
                Log::warning('Fonnte Bulk Send: Nomor telepon tidak valid, dilewati.', ['original_target' => $msg['target']]);
            }
        }

        if (empty($fonnteBulkData)) {
            return ['status' => false, 'message' => 'Tidak ada target valid untuk pengiriman massal setelah pembersihan dan pemformatan.'];
        }

        $params = [
            'data' => json_encode($fonnteBulkData),
        ];

        $res = $this->sendRequest($params);

        if ($res['status']) {
            $responseData = $res['data'] ?? [];
            return ['status' => true, 'message' => 'Pesan massal berhasil dikirim ke Fonnte.', 'message_data' => $responseData];
        }

        return ['status' => false, 'message' => $res['message'] ?? 'Gagal mengirim pesan massal.', 'deskripsi_raw' => json_encode($res['data'] ?? $res['message'])];
    }
}