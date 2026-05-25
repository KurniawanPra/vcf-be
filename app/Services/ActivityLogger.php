<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Request;

/**
 * ActivityLogger — Singleton service untuk mencatat semua aktivitas sistem.
 *
 * Cara pakai:
 *   ActivityLogger::log('vcf.created', 'vcf', 'created', $vcf, "VCF #{$vcf->nomor_urut} berhasil dibuat");
 *   ActivityLogger::log('auth.login', 'auth', 'login', null, "User {$user->nama} login");
 */
class ActivityLogger
{
    /**
     * Catat satu activity log.
     *
     * @param  string       $event         Kode event (contoh: 'vcf.created')
     * @param  string       $module        Modul (contoh: 'vcf', 'master', 'auth', 'settings')
     * @param  string       $action        Aksi (contoh: 'created', 'updated', 'deleted', 'login')
     * @param  mixed|null   $subject       Model yang jadi subject (bisa null)
     * @param  string       $description   Kalimat manusiawi yang akan ditampilkan di UI
     * @param  array        $properties    Data tambahan opsional (misal: ['before'=>[], 'after'=>[]])
     * @param  string|null  $subjectLabel  Label ringkas subject (misal: "VCF #00001")
     */
    public static function log(
        string $event,
        string $module,
        string $action,
        $subject,
        string $description,
        array $properties = [],
        ?string $subjectLabel = null
    ): void {
        try {
            $user = auth()->user();

            $subjectType = null;
            $subjectId   = null;

            if ($subject && is_object($subject)) {
                $subjectType = get_class($subject);
                $subjectId   = $subject->getKey();
            }

            ActivityLog::create([
                'user_id'       => $user?->id,
                'user_name'     => $user?->nama,
                'user_role'     => $user?->role,
                'event'         => $event,
                'module'        => $module,
                'action'        => $action,
                'subject_type'  => $subjectType,
                'subject_id'    => $subjectId,
                'description'   => $description,
                'subject_label' => $subjectLabel,
                'properties'    => !empty($properties) ? $properties : null,
                'ip_address'    => Request::ip(),
                'user_agent'    => substr(Request::userAgent() ?? '', 0, 500),
            ]);

            // Garbage collection: Hapus log yang lebih tua dari 30 hari secara berkala (probability 1%)
            if (mt_rand(1, 100) === 42) {
                ActivityLog::where('created_at', '<', now()->subDays(30))->delete();
            }
        } catch (\Throwable $e) {
            // Jangan sampai logging error mengganggu alur utama aplikasi
            \Illuminate\Support\Facades\Log::error('ActivityLogger failed: ' . $e->getMessage());
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Shorthand helpers
    // ─────────────────────────────────────────────────────────────────────────

    public static function vcfCreated($vcf, string $extra = ''): void
    {
        $label = "VCF #{$vcf->nomor_urut} ({$vcf->no_polisi})";
        self::log(
            'vcf.created', 'vcf', 'created', $vcf,
            "VCF baru dibuat: {$label}" . ($extra ? " — {$extra}" : ''),
            [], $label
        );
    }

    public static function vcfUpdated($vcf, array $changed = []): void
    {
        $label = "VCF #{$vcf->nomor_urut} ({$vcf->no_polisi})";
        self::log(
            'vcf.updated', 'vcf', 'updated', $vcf,
            "VCF diperbarui: {$label}",
            $changed ? ['changed_fields' => array_keys($changed)] : [],
            $label
        );
    }

    public static function vcfRejected($vcf, string $catatan, string $stage = ''): void
    {
        $label = "VCF #{$vcf->nomor_urut} ({$vcf->no_polisi})";
        $stageStr = $stage ? " [{$stage}]" : '';
        self::log(
            'vcf.rejected', 'vcf', 'rejected', $vcf,
            "VCF DITOLAK{$stageStr}: {$label} — Alasan: {$catatan}",
            ['catatan_reject' => $catatan, 'stage' => $stage],
            $label
        );
    }

    public static function vcfFinalized($vcf): void
    {
        $label = "VCF #{$vcf->nomor_urut} ({$vcf->no_polisi})";
        self::log(
            'vcf.finalized', 'vcf', 'finalized', $vcf,
            "VCF SELESAI: {$label} — Kendaraan keluar Main Gate",
            [], $label
        );
    }

    public static function vcfStageDone($vcf, string $stageName): void
    {
        $label = "VCF #{$vcf->nomor_urut} ({$vcf->no_polisi})";
        self::log(
            'vcf.' . strtolower(str_replace(' ', '_', $stageName)) . '.done',
            'vcf', 'stage_completed', $vcf,
            "{$stageName} selesai: {$label}",
            ['stage' => $stageName],
            $label
        );
    }

    public static function masterCreated(string $modelName, $model, string $label): void
    {
        self::log(
            "master.{$modelName}.created", 'master', 'created', $model,
            "Master data {$modelName} ditambahkan: {$label}",
            [], $label
        );
    }

    public static function masterUpdated(string $modelName, $model, string $label): void
    {
        $changed = [];
        if ($model && method_exists($model, 'getChanges')) {
            $changed = $model->getChanges();
        }
        self::log(
            "master.{$modelName}.updated", 'master', 'updated', $model,
            "Master data {$modelName} diperbarui: {$label}",
            $changed ? ['changed_fields' => array_keys($changed)] : [],
            $label
        );
    }

    public static function masterDeleted(string $modelName, string $label): void
    {
        self::log(
            "master.{$modelName}.deleted", 'master', 'deleted', null,
            "Master data {$modelName} dihapus: {$label}",
            [], $label
        );
    }

    public static function authLogin($user): void
    {
        self::log(
            'auth.login', 'auth', 'login', $user,
            "Login: {$user->nama} ({$user->role})",
            [], $user->nama
        );
    }

    public static function authLogout($user): void
    {
        self::log(
            'auth.logout', 'auth', 'logout', $user,
            "Logout: {$user->nama} ({$user->role})",
            [], $user->nama
        );
    }

    public static function settingUpdated(string $key, $oldValue, $newValue): void
    {
        self::log(
            'settings.updated', 'settings', 'updated', null,
            "Pengaturan diperbarui: {$key}",
            ['key' => $key, 'old' => $oldValue, 'new' => $newValue],
            $key
        );
    }

    public static function timbanganUpdated($vcf, string $field, $oldVal, $newVal): void
    {
        $label = "VCF #{$vcf->nomor_urut} ({$vcf->no_polisi})";
        self::log(
            'timbangan.updated', 'timbangan', 'updated', $vcf,
            "Data timbangan diperbarui pada {$label}: {$field} {$oldVal} → {$newVal}",
            ['field' => $field, 'old' => $oldVal, 'new' => $newVal],
            $label
        );
    }

    public static function violationCreated($violation, string $targetLabel): void
    {
        self::log(
            'violation.created', 'violation', 'created', $violation,
            "Pelanggaran dicatat untuk: {$targetLabel}",
            [], $targetLabel
        );
    }

    public static function violationDeleted(string $targetLabel): void
    {
        self::log(
            'violation.deleted', 'violation', 'deleted', null,
            "Catatan pelanggaran dihapus: {$targetLabel}",
            [], $targetLabel
        );
    }
}
