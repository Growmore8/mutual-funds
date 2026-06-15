<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoolAccount;
use App\Models\PoolSnapshot;
use App\Services\PoolApiClient;
use Illuminate\Support\Facades\Artisan;

class PoolController extends Controller
{
    public function index(PoolApiClient $api)
    {
        $pool = PoolAccount::first();
        $snapshots = PoolSnapshot::with('poolAccount')
            ->latest('snapshot_date')
            ->limit(30)
            ->get();

        return view('admin.pool.index', [
            'pool' => $pool,
            'snapshots' => $snapshots,
            'isLive' => $api->isLive(),
        ]);
    }

    public function sync()
    {
        Artisan::call('pool:sync');

        return back()->with('status', 'Pool sync run: ' . trim(Artisan::output()));
    }
}
