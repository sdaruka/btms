<?php

namespace App\Http\Controllers;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Design;
use App\Models\Measurement;
use App\Models\OrderItem;
use App\Models\Rate;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;


class DashboardController extends Controller
{
    public function index(){
        $now = Carbon::now()->timezone('UTC');

$startOfThisWeek   = $now->copy()->startOfWeek()->startOfDay(); // e.g. Mon 00:00 UTC
$startOfLastWeek   = $startOfThisWeek->copy()->subWeek();       // Last Mon 00:00 UTC
$endOfLastWeek     = $startOfThisWeek->copy()->subSecond();     // Sun 23:59:59 UTC
        // dd($startOfLastWeek, $endOfLastWeek);
            $role = auth()->user()->role;
            $pendingOrders = Order::where('status','pending')->count();
            $lastWeekPending = Order::whereNotIn('status', ['completed', 'cancelled', 'delivered'])
            ->whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])
            ->count();
        $totalOrders=Order::all()->count();
        $lastWeekOrders=Order::whereBetween('created_at', [$startOfLastWeek, $endOfLastWeek])
        ->get();
        $recentOrders = Order::where('created_at', '>', $endOfLastWeek)->get();
        $orders= Order::get();

            return match($role) {
                'admin' => view('dashboard.admin',compact('pendingOrders', 'totalOrders','recentOrders','orders')),
                'staff' => view('dashboard.staff' ,compact('pendingOrders', 'totalOrders','recentOrders','orders')),
                'tailor' => view('dashboard.tailor' ,compact('pendingOrders', 'totalOrders','recentOrders','orders')),
                'artisan' => view('dashboard.artisan',compact('pendingOrders', 'totalOrders','recentOrders','orders')),
                'customer' => view('dashboard.customer',compact('pendingOrders', 'totalOrders','recentOrders','orders')),
                default => abort(403, 'Unauthorized action.'),
            };
            
        }
       
}
