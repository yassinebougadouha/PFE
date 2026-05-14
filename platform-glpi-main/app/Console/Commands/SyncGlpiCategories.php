<?php
namespace App\Console\Commands;
 
use Illuminate\Console\Command;
use App\Services\GlpiService;
 
class SyncGlpiCategories extends Command
{
    protected $signature   = 'glpi:sync-categories';
    protected $description = 'Synchroniser les catégories GLPI (ITILCategory) vers la base locale';
 
    public function handle(): int
    {
        $this->info('⏳ Synchronisation des catégories GLPI...');
 
        try {
            $glpi  = app(GlpiService::class);
            $count = $glpi->syncCategoriesToLocal();
            $glpi->killSession();
 
            $this->info("✅ {$count} catégories synchronisées depuis GLPI.");
            return Command::SUCCESS;
 
        } catch (\Exception $e) {
            $this->error('❌ Erreur: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}