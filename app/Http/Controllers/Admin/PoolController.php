<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoolAccount;
use App\Models\PoolSnapshot;
use App\Services\PoolApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class PoolController extends Controller
{
    public function index(PoolApiClient $api)
    {
        return view('admin.pool.index', [
            'pools' => PoolAccount::orderBy('account_ref')->get(),
            'isLive' => $api->isLive(),
        ]);
    }

    public function pnl()
    {
        return view('admin.pool.pnl', [
            'snapshots' => PoolSnapshot::with('poolAccount')->latest('snapshot_date')->limit(60)->get(),
        ]);
    }

    public function store(Request $request)
    {
        PoolAccount::create($this->validated($request));

        return back()->with('status', 'Pool account added.');
    }

    public function update(Request $request, PoolAccount $pool)
    {
        $pool->update($this->validated($request));

        return back()->with('status', 'Pool account updated.');
    }

    public function destroy(PoolAccount $pool)
    {
        $pool->delete();

        return back()->with('status', 'Pool account deleted.');
    }

    /**
     * Live pool figures for on-screen auto-refresh. The CubeX call is cached
     * ~15s per pool so many viewers share one request (rate-limit safe).
     */
    public function live(PoolApiClient $api)
    {
        $data = PoolAccount::where('is_active', true)->get()->map(function ($pool) use ($api) {
            $d = self::liveFigures($api, $pool);

            return [
                'id' => $pool->id,
                'ref' => $pool->account_ref,
                'balance' => $d['balance'],
                'floating' => $d['floating'] ?? 0,
                'closed_today' => $d['pnl'],
                'equity' => $d['equity'],
            ];
        });

        return response()->json(['data' => $data, 'at' => now()->format('H:i:s'), 'live' => $api->isLive()]);
    }

    /** Short-cached snapshot read (no DB writes), with a safe fallback. */
    public static function liveFigures(PoolApiClient $api, PoolAccount $pool): array
    {
        try {
            // Short TTL so the on-screen ticker refreshes fast; one CubeX call
            // per pool per 3s is shared by all viewers (well under 120/min).
            return Cache::remember("pool.live.{$pool->id}", 3, fn () => $api->snapshot($pool));
        } catch (\Throwable $e) {
            return [
                'balance' => (float) $pool->balance,
                'equity' => (float) $pool->equity,
                'floating' => (float) $pool->floating_pnl,
                'pnl' => 0.0,
            ];
        }
    }

    public function sync()
    {
        try {
            Artisan::call('pool:sync');

            return back()->with('status', 'Pool sync: ' . trim(Artisan::output()));
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('pool:sync failed: ' . $e->getMessage());

            return back()->with('status', 'Pool sync failed: ' . $e->getMessage());
        }
    }

    private function validated(Request $request): array
    {
        $poolId = optional($request->route('pool'))->id;

        return $request->validate([
            'account_ref' => ['required', 'string', 'max:100', Rule::unique('pool_accounts', 'account_ref')->ignore($poolId)],
            'name' => ['nullable', 'string', 'max:120'],
            'capacity' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
        ]) + ['is_active' => (bool) $request->boolean('is_active')];
    }
}
