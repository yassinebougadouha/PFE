<?php
namespace App\Console\Commands;
 
use Illuminate\Console\Command;
use App\Models\User;
use App\Services\GlpiService;
 
class SyncGlpiUsers extends Command
{
    protected $signature   = 'glpi:sync-users';
    protected $description = 'Synchroniser tous les users locaux vers GLPI';
 
    public function handle(): int
    {
        $users = User::whereNull('glpi_user_id')
            ->whereIn('role', ['admin', 'client'])
            ->get();
 
        $this->info("⏳ Synchronisation de {$users->count()} users vers GLPI...");
 
        $glpi = app(GlpiService::class);
        $ok   = 0;
        $fail = 0;
 
        foreach ($users as $user) {
            $glpiId = $glpi->syncUser($user);
            if ($glpiId) {
                $ok++;
                $this->line("  ✅ {$user->email} → GLPI ID: {$glpiId}");
            } else {
                $fail++;
                $this->warn("  ⚠️  {$user->email} → échec");
            }
        }
 
        $glpi->killSession();
 
        $this->info("✅ {$ok} users synchronisés, {$fail} échecs.");
        return Command::SUCCESS;
    }
}