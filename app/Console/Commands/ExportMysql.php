<?php

namespace App\Console\Commands;

use App\Models\Keyword;
use App\Models\Topic;
use App\Models\User;
use App\Models\Word;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ExportMysql extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:export-mysql {path? : Lokasi file .sql tujuan}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export skema (kompatibel MySQL) + data aplikasi saat ini jadi satu file .sql siap-impor';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = $this->argument('path') ?? database_path('exports/sepuluh-mysql-'.now()->format('Y_m_d_His').'.sql');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $this->buildSql());

        $this->info("Export selesai: {$path}");
        $this->line('Import ke server MySQL dengan:');
        $this->line('  mysql -u USER -p NAMA_DATABASE < "'.$path.'"');

        return self::SUCCESS;
    }

    private function buildSql(): string
    {
        $parts = [
            '-- Export database "Sepuluh" untuk MySQL',
            '-- Dibuat: '.now()->toDateTimeString(),
            '-- Tabel sesi/cache/antrian sengaja dibuat kosong (transient, tidak perlu dibawa).',
            'SET NAMES utf8mb4;',
            'SET FOREIGN_KEY_CHECKS = 0;',
            '',
            $this->schema(),
            $this->data(),
            'SET FOREIGN_KEY_CHECKS = 1;',
        ];

        return implode("\n", $parts);
    }

    private function schema(): string
    {
        return <<<'SQL'
        -- ==================== SKEMA ====================

        DROP TABLE IF EXISTS `migrations`;
        CREATE TABLE `migrations` (
          `id` int unsigned NOT NULL AUTO_INCREMENT,
          `migration` varchar(255) NOT NULL,
          `batch` int NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        DROP TABLE IF EXISTS `users`;
        CREATE TABLE `users` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `name` varchar(255) NOT NULL,
          `email` varchar(255) NOT NULL,
          `email_verified_at` timestamp NULL DEFAULT NULL,
          `password` varchar(255) NOT NULL,
          `remember_token` varchar(100) DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `users_email_unique` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        DROP TABLE IF EXISTS `password_reset_tokens`;
        CREATE TABLE `password_reset_tokens` (
          `email` varchar(255) NOT NULL,
          `token` varchar(255) NOT NULL,
          `created_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        DROP TABLE IF EXISTS `sessions`;
        CREATE TABLE `sessions` (
          `id` varchar(255) NOT NULL,
          `user_id` bigint unsigned DEFAULT NULL,
          `ip_address` varchar(45) DEFAULT NULL,
          `user_agent` text,
          `payload` longtext NOT NULL,
          `last_activity` int NOT NULL,
          PRIMARY KEY (`id`),
          KEY `sessions_user_id_index` (`user_id`),
          KEY `sessions_last_activity_index` (`last_activity`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        DROP TABLE IF EXISTS `cache`;
        CREATE TABLE `cache` (
          `key` varchar(255) NOT NULL,
          `value` mediumtext NOT NULL,
          `expiration` int NOT NULL,
          PRIMARY KEY (`key`),
          KEY `cache_expiration_index` (`expiration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        DROP TABLE IF EXISTS `cache_locks`;
        CREATE TABLE `cache_locks` (
          `key` varchar(255) NOT NULL,
          `owner` varchar(255) NOT NULL,
          `expiration` int NOT NULL,
          PRIMARY KEY (`key`),
          KEY `cache_locks_expiration_index` (`expiration`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        DROP TABLE IF EXISTS `jobs`;
        CREATE TABLE `jobs` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `queue` varchar(255) NOT NULL,
          `payload` longtext NOT NULL,
          `attempts` tinyint unsigned NOT NULL,
          `reserved_at` int unsigned DEFAULT NULL,
          `available_at` int unsigned NOT NULL,
          `created_at` int unsigned NOT NULL,
          PRIMARY KEY (`id`),
          KEY `jobs_queue_index` (`queue`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        DROP TABLE IF EXISTS `job_batches`;
        CREATE TABLE `job_batches` (
          `id` varchar(255) NOT NULL,
          `name` varchar(255) NOT NULL,
          `total_jobs` int NOT NULL,
          `pending_jobs` int NOT NULL,
          `failed_jobs` int NOT NULL,
          `failed_job_ids` longtext NOT NULL,
          `options` mediumtext,
          `cancelled_at` int DEFAULT NULL,
          `created_at` int NOT NULL,
          `finished_at` int DEFAULT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        DROP TABLE IF EXISTS `failed_jobs`;
        CREATE TABLE `failed_jobs` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `uuid` varchar(255) NOT NULL,
          `connection` text NOT NULL,
          `queue` text NOT NULL,
          `payload` longtext NOT NULL,
          `exception` longtext NOT NULL,
          `failed_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `failed_jobs_uuid_unique` (`uuid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        DROP TABLE IF EXISTS `keywords`;
        CREATE TABLE `keywords` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `term` varchar(255) NOT NULL,
          `slug` varchar(255) NOT NULL,
          `is_active` tinyint(1) NOT NULL DEFAULT 0,
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `keywords_slug_unique` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        DROP TABLE IF EXISTS `words`;
        CREATE TABLE `words` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `keyword_id` bigint unsigned NOT NULL,
          `en` varchar(255) NOT NULL,
          `ipa` varchar(255) DEFAULT NULL,
          `pos` varchar(255) DEFAULT NULL,
          `translation` varchar(255) NOT NULL,
          `example` text,
          `example_translation` text,
          `verb1` varchar(255) DEFAULT NULL,
          `verb2` varchar(255) DEFAULT NULL,
          `verb3` varchar(255) DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `words_keyword_id_foreign` (`keyword_id`),
          CONSTRAINT `words_keyword_id_foreign` FOREIGN KEY (`keyword_id`) REFERENCES `keywords` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

        DROP TABLE IF EXISTS `topics`;
        CREATE TABLE `topics` (
          `id` bigint unsigned NOT NULL AUTO_INCREMENT,
          `keyword_id` bigint unsigned NOT NULL,
          `title` varchar(255) NOT NULL,
          `blurb` varchar(255) DEFAULT NULL,
          `partner` varchar(255) DEFAULT NULL,
          `lines` json DEFAULT NULL,
          `keys` json DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT NULL,
          `updated_at` timestamp NULL DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `topics_keyword_id_foreign` (`keyword_id`),
          CONSTRAINT `topics_keyword_id_foreign` FOREIGN KEY (`keyword_id`) REFERENCES `keywords` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        SQL;
    }

    private function data(): string
    {
        $parts = ['', '-- ==================== DATA ===================='];

        $parts[] = $this->insertsFor(
            'migrations',
            collect(DB::table('migrations')->orderBy('id')->get())->map(fn ($row) => (array) $row),
        );

        $parts[] = $this->insertsFor('users', User::orderBy('id')->get()->map(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'email_verified_at' => $u->email_verified_at,
            'password' => $u->password,
            'remember_token' => $u->remember_token,
            'created_at' => $u->created_at,
            'updated_at' => $u->updated_at,
        ]));

        $parts[] = $this->insertsFor('keywords', Keyword::orderBy('id')->get()->map(fn (Keyword $k) => [
            'id' => $k->id,
            'term' => $k->term,
            'slug' => $k->slug,
            'is_active' => $k->is_active ? 1 : 0,
            'created_at' => $k->created_at,
            'updated_at' => $k->updated_at,
        ]));

        $parts[] = $this->insertsFor('words', Word::orderBy('id')->get()->map(fn (Word $w) => [
            'id' => $w->id,
            'keyword_id' => $w->keyword_id,
            'en' => $w->en,
            'ipa' => $w->ipa,
            'pos' => $w->pos,
            'translation' => $w->translation,
            'example' => $w->example,
            'example_translation' => $w->example_translation,
            'verb1' => $w->verb1,
            'verb2' => $w->verb2,
            'verb3' => $w->verb3,
            'created_at' => $w->created_at,
            'updated_at' => $w->updated_at,
        ]));

        $parts[] = $this->insertsFor('topics', Topic::orderBy('id')->get()->map(fn (Topic $t) => [
            'id' => $t->id,
            'keyword_id' => $t->keyword_id,
            'title' => $t->title,
            'blurb' => $t->blurb,
            'partner' => $t->partner,
            'lines' => json_encode($t->lines ?? []),
            'keys' => json_encode($t->keys ?? []),
            'created_at' => $t->created_at,
            'updated_at' => $t->updated_at,
        ]));

        return implode("\n", array_filter($parts));
    }

    private function insertsFor(string $table, Collection $rows): string
    {
        if ($rows->isEmpty()) {
            return "-- (tabel `{$table}` kosong)\n";
        }

        $columns = array_keys($rows->first());
        $columnList = implode(', ', array_map(fn ($c) => "`{$c}`", $columns));

        $valueLines = $rows->map(function (array $row) {
            $values = collect($row)->map(fn ($v) => $this->quote($v))->implode(', ');

            return "  ({$values})";
        })->implode(",\n");

        return "INSERT INTO `{$table}` ({$columnList}) VALUES\n{$valueLines};\n";
    }

    private function quote(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value instanceof \DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }

        $escaped = str_replace(
            ['\\', "\0", "\n", "\r", "'", '"', "\x1a"],
            ['\\\\', '\\0', '\\n', '\\r', "\\'", '\\"', '\\Z'],
            (string) $value,
        );

        return "'{$escaped}'";
    }
}
