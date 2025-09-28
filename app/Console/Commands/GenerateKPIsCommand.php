<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class GenerateKPIsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'kpis:generate {--date=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily KPIs (revenue, order count, AOV) using Redis';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : Carbon::today();
        
        $this->info("Generating KPIs for {$date->format('Y-m-d')}");
        
        // Get orders for the date
        $orders = Order::whereDate('created_at', $date)
            ->where('status', 'paid')
            ->get();
        
        // Calculate KPIs
        $revenue = $orders->sum('total_amount');
        $orderCount = $orders->count();
        $averageOrderValue = $orderCount > 0 ? $revenue / $orderCount : 0;
        
        // Store in Redis with date key
        $dateKey = $date->format('Y-m-d');
        $kpiKey = "kpis:daily:{$dateKey}";
        
        Cache::put($kpiKey, [
            'revenue' => $revenue,
            'order_count' => $orderCount,
            'average_order_value' => round($averageOrderValue, 2),
            'generated_at' => now()->toISOString()
        ], 365 * 24 * 60); // 1 year
        
        // Update daily totals
        $this->updateDailyTotals($date, $revenue, $orderCount);
        
        $this->info("KPIs generated successfully!");
        $this->table(['Metric', 'Value'], [
            ['Revenue', '$' . number_format($revenue, 2)],
            ['Order Count', $orderCount],
            ['Average Order Value', '$' . number_format($averageOrderValue, 2)],
        ]);
        
        return 0;
    }
    
    private function updateDailyTotals(Carbon $date, float $revenue, int $orderCount): void
    {
        $year = $date->year;
        $month = $date->month;
        
        // Update yearly totals
        $yearlyKey = "kpis:yearly:{$year}";
        $yearlyData = Cache::get($yearlyKey, ['revenue' => 0, 'order_count' => 0]);
        Cache::put($yearlyKey, [
            'revenue' => $yearlyData['revenue'] + $revenue,
            'order_count' => $yearlyData['order_count'] + $orderCount
        ], 365 * 24 * 60);
        
        // Update monthly totals
        $monthlyKey = "kpis:monthly:{$year}-{$month}";
        $monthlyData = Cache::get($monthlyKey, ['revenue' => 0, 'order_count' => 0]);
        Cache::put($monthlyKey, [
            'revenue' => $monthlyData['revenue'] + $revenue,
            'order_count' => $monthlyData['order_count'] + $orderCount
        ], 365 * 24 * 60);
        
        // Update overall totals
        $overallData = Cache::get("kpis:overall", ['total_revenue' => 0, 'total_orders' => 0]);
        Cache::put("kpis:overall", [
            'total_revenue' => $overallData['total_revenue'] + $revenue,
            'total_orders' => $overallData['total_orders'] + $orderCount
        ], 365 * 24 * 60);
    }
}
