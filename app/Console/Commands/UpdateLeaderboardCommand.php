<?php

namespace App\Console\Commands;

use App\Models\Customer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class UpdateLeaderboardCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'leaderboard:update {--limit=10}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update top customers leaderboard using Redis';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        
        $this->info("Updating leaderboard for top {$limit} customers...");
        
        // Get top customers by total spent
        $topCustomers = Customer::orderBy('total_spent', 'desc')
            ->limit($limit)
            ->get();
        
        // Store leaderboard in cache
        $leaderboardData = [];
        foreach ($topCustomers as $customer) {
            $leaderboardData[] = [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'total_spent' => $customer->total_spent,
                'total_orders' => $customer->total_orders
            ];
        }
        
        Cache::put('leaderboard:top_customers', $leaderboardData, 365 * 24 * 60); // 1 year
        
        // Display leaderboard
        $this->displayLeaderboard($limit);
        
        $this->info("Leaderboard updated successfully!");
        
        return 0;
    }
    
    private function displayLeaderboard(int $limit): void
    {
        $leaderboard = Cache::get('leaderboard:top_customers', []);
        
        $tableData = [];
        $rank = 1;
        
        foreach (array_slice($leaderboard, 0, $limit) as $customer) {
            $tableData[] = [
                $rank++,
                $customer['name'],
                $customer['email'],
                '$' . number_format($customer['total_spent'], 2),
                $customer['total_orders']
            ];
        }
        
        $this->table(['Rank', 'Name', 'Email', 'Total Spent', 'Orders'], $tableData);
    }
}
