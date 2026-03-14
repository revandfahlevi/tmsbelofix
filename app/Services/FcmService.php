<?php
// app/Services/FcmService.php
namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Illuminate\Support\Facades\Log;

class FcmService
{
    protected $messaging;

    public function __construct()
    {
        try {
            $factory = (new Factory)->withServiceAccount(config('firebase.projects.app.credentials'));
            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('[FCM] Init error: ' . $e->getMessage());
        }
    }

    /**
     * Kirim notif ke satu device
     */
    public function sendToDevice(string $fcmToken, string $title, string $body, array $data = []): bool
    {
        if (!$this->messaging || !$fcmToken) return false;

        try {
            $message = CloudMessage::withTarget('token', $fcmToken)
                ->withNotification(Notification::create($title, $body))
                ->withData(array_map('strval', $data));

            $this->messaging->send($message);
            return true;
        } catch (\Exception $e) {
            Log::error('[FCM] Send error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim notif ke banyak device sekaligus
     */
    public function sendToMultiple(array $fcmTokens, string $title, string $body, array $data = []): void
    {
        if (!$this->messaging || empty($fcmTokens)) return;

        try {
            $messages = array_map(function ($token) use ($title, $body, $data) {
                return CloudMessage::withTarget('token', $token)
                    ->withNotification(Notification::create($title, $body))
                    ->withData(array_map('strval', $data));
            }, $fcmTokens);

            $this->messaging->sendAll($messages);
        } catch (\Exception $e) {
            Log::error('[FCM] SendMultiple error: ' . $e->getMessage());
        }
    }

    // ── Notif spesifik ──────────────────────────────────────

    /**
     * Driver di-assign ke job order
     */
    public function notifyDriverAssigned(string $fcmToken, string $jobNumber, string $origin, string $destination): void
    {
        $this->sendToDevice(
            $fcmToken,
            '📦 Job Order Baru!',
            "Kamu di-assign ke {$jobNumber}: {$origin} → {$destination}",
            ['type' => 'job_assigned', 'job_number' => $jobNumber]
        );
    }

    /**
     * Admin: driver apply job
     */
    public function notifyAdminDriverApplied(array $adminTokens, string $driverName, string $jobNumber): void
    {
        $this->sendToMultiple(
            $adminTokens,
            '🙋 Driver Apply Job',
            "{$driverName} mengajukan diri untuk job {$jobNumber}",
            ['type' => 'driver_applied', 'job_number' => $jobNumber]
        );
    }

    /**
     * Admin: POD baru masuk
     */
    public function notifyAdminPodSubmitted(array $adminTokens, string $driverName, string $jobNumber): void
    {
        $this->sendToMultiple(
            $adminTokens,
            '📸 POD Baru Masuk',
            "{$driverName} submit bukti pengiriman untuk {$jobNumber}",
            ['type' => 'pod_submitted', 'job_number' => $jobNumber]
        );
    }
}