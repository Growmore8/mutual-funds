<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AccountType;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AccountTypeController extends Controller
{
    public function index()
    {
        $types = AccountType::orderBy('sort_order')->get();

        return view('admin.account-types.index', compact('types'));
    }

    public function create()
    {
        return view('admin.account-types.form', ['type' => new AccountType(['is_active' => true, 'profit_share_pct' => 100])]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = Str::slug($data['name']);
        $data['features'] = $this->features($request);
        AccountType::create($data);

        return redirect()->route('admin.account-types.index')->with('status', 'Account type created.');
    }

    public function edit(AccountType $account_type)
    {
        return view('admin.account-types.form', ['type' => $account_type]);
    }

    public function update(Request $request, AccountType $account_type)
    {
        $data = $this->validated($request);
        $data['features'] = $this->features($request);
        $account_type->update($data);

        return redirect()->route('admin.account-types.index')->with('status', 'Account type updated.');
    }

    public function destroy(AccountType $account_type)
    {
        $account_type->delete();

        return back()->with('status', 'Account type deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'min_deposit' => ['required', 'numeric', 'min:0'],
            'max_deposit' => ['nullable', 'numeric', 'min:0'],
            'management_fee_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'profit_share_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'lock_in_months' => ['required', 'integer', 'min:0'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]) + ['is_active' => (bool) $request->boolean('is_active')];
    }

    private function features(Request $request): array
    {
        return collect(explode("\n", (string) $request->input('features_text')))
            ->map(fn ($l) => trim($l))->filter()->values()->all();
    }
}
