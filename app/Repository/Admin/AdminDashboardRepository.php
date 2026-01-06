<?php

namespace App\Repository\Admin;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\Withdrawal;
use App\Models\Transaction;
use App\Models\Advertise;
use App\Models\Referral;
use App\Models\ResellerConversion;
use App\Models\Task;
use App\Models\TaskPayout;
use App\Models\AdvertPayout;
use App\Models\ResellerPayout;
use App\Models\FundsRecord;
use Carbon\Carbon;
use App\Repository\Admin\IAdminDashboardRepository;

class AdminDashboardRepository implements IAdminDashboardRepository
{
    public function getDashboardStats()
    {
        // Basic counts
        $totalUsers = User::count();
        $totalMembers = User::where('is_member', true)->count();
        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $totalWithdrawals = Withdrawal::count();
        $totalAdverts = Advertise::count();
        $totalReferrals = Referral::count();
        $totalResellerConversions = ResellerConversion::count();

        // ----------------------
        //  cashIn, Revenue and Expenses
        // ----------------------

        //  Total Cash In
        $totalCashIn = Transaction::whereIn('category', [
           'deposit',
           'platform_charges',
           'advert',
           'task',
           'resell_budget',
        ])->sum('amount');


        // Only platform_charges are actual revenue
        $totalRevenue = Transaction::where('category', 'platform_charges')->sum('amount');

        // Expenses = referral_commission (and any other platform-funded costs)
        $totalExpenses = Transaction::where('category', 'referral_commission')->sum('amount');

        $totalProfit = $totalRevenue - $totalExpenses;

        // ----------------------
        // User Funds Under Management (UFUM)
        // ----------------------
        $walletFunds = User::sum('balance');

        // Remaining Task Budgets = total task budgets - payouts already made
        $taskBudgets = Task::where('status', 'success')->sum('task_amount')
            - FundsRecord::sum('pending');

        // Remaining Advert Budgets
        $advertBudgets = Advertise::where('status', 'success')->sum('estimated_cost')
            - FundsRecord::sum('pending');

        // Remaining Reseller Budgets
        $resellerBudgets = Product::sum('resell_budget')
            - Transaction::where('category', 'reseller_commission')->sum('amount');

        $totalUFUM = $walletFunds + $taskBudgets + $advertBudgets + $resellerBudgets;

        // ----------------------
        // Pending and Other Metrics
        // ----------------------
        $pendingOrders = Order::where('status', 'pending')->count();
        $pendingWithdrawals = Withdrawal::where('status', 'pending')->count();
        $activeAdverts = Advertise::where('status', 'success')->count();
        $usersLast30Days = User::where('created_at', '>=', Carbon::now()->subDays(30))->count();

        // Revenue last 30 days
        $revenueLast30Days = Transaction::where('category', 'platform_charges')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->sum('amount');

        // Recent transactions (last 10)
        $recentTransactions = Transaction::with('user')->latest()->take(10)->get();

        // Monthly revenue for chart (last 12 months)
        $monthlyRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $month = $date->format('M Y');
            $amount = Transaction::where('category', 'platform_charges')
                ->whereYear('created_at', $date->year)
                ->whereMonth('created_at', $date->month)
                ->sum('amount');
            $monthlyRevenue[] = ['month' => $month, 'revenue' => (float) $amount];
        }

        // Top products by orders
        $topProducts = Product::withCount('orders')->orderBy('orders_count', 'desc')->take(5)->get();

        // Top users by balance
        $topUsers = User::orderBy('balance', 'desc')->take(5)->get(['id', 'fname', 'lname', 'balance']);

        return [
            'total_users' => $totalUsers,
            'total_members' => $totalMembers,
            'total_products' => $totalProducts,
            'total_orders' => $totalOrders,
            'total_withdrawals' => $totalWithdrawals,
            'total_adverts' => $totalAdverts,
            'total_referrals' => $totalReferrals,
            'total_reseller_conversions' => $totalResellerConversions,
            'total_revenue' => (float) $totalRevenue,
            'total_expenses' => (float) $totalExpenses,
            'total_profit' => (float) $totalProfit,
            'total_cash_in' => (float) $totalCashIn,
            'total_UFUM' => (float) $totalUFUM, // User Funds Under Management
            'pending_orders' => $pendingOrders,
            'pending_withdrawals' => $pendingWithdrawals,
            'active_adverts' => $activeAdverts,
            'users_last_30_days' => $usersLast30Days,
            'revenue_last_30_days' => (float) $revenueLast30Days,
            'recent_transactions' => $recentTransactions,
            'monthly_revenue' => $monthlyRevenue,
            'top_products' => $topProducts,
            'top_users' => $topUsers,
        ];
    }
}
