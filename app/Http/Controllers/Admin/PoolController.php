<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PoolAccount;
use App\Models\PoolSnapshot;
use App\Services\PoolApiClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class PoolController extends Controller
{
    public function index(PoolApiClient $api)
    {
        return view('admin.pool.index', [
            'pools' => PoolAccount::orderBy('account_ref')->get(),
            'snapshots' => PoolSnapshot::with('poolAccount')->latest('snapshot_date')->limit(30)->get(),
            'isLive' => $api->isLive(),
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
        return $request->validate([
            'account_ref' => ['required', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:120'],
            'capacity' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'max:10'],
        ]) + ['is_active' => (bool) $request->boolean('is_active')];
    }
}
