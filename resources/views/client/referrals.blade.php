<x-client-layout title="Refer & Earn">
    @php $money = fn ($n) => '$' . number_format((float) $n, 2); @endphp

    <div class="max-w-2xl mx-auto space-y-6" x-data="{ link: @js($user->referralLink()), copied: false,
            copy(){ navigator.clipboard.writeText(this.link); this.copied=true; setTimeout(()=>this.copied=false,1500); } }">
        <x-back-link />
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Refer &amp; Earn</h2>
            <p class="text-sm text-gray-500 dark:text-gray-400">Share your link. You earn <strong>1% of every deposit</strong> anyone makes after joining through it.</p>
        </div>

        {{-- Earnings + count --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="rounded-2xl bg-emerald-600 text-white p-5">
                <p class="text-sm text-white/80">Referral earnings</p>
                <p class="text-2xl font-bold mt-1">{{ $money($earned) }}</p>
            </div>
            <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] p-5">
                <p class="text-sm text-gray-500 dark:text-gray-400">People referred</p>
                <p class="text-2xl font-bold text-gray-900 dark:text-white mt-1">{{ $referrals->count() }}</p>
            </div>
        </div>

        {{-- Link --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] p-5">
            <p class="text-sm font-semibold text-gray-900 dark:text-white mb-1">Your referral link</p>
            <p class="text-xs text-gray-400 mb-3">Code: <span class="font-mono">{{ $user->referral_code }}</span></p>
            <div class="flex items-center gap-2">
                <input type="text" readonly :value="link" class="flex-1 text-sm border-gray-300 rounded-lg bg-gray-50 dark:bg-white/5 dark:border-white/10 dark:text-gray-200">
                <button type="button" @click="copy()" class="px-3 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shrink-0">
                    <span x-show="!copied"><i class="fa-regular fa-copy mr-1"></i>Copy</span>
                    <span x-show="copied" x-cloak><i class="fa-solid fa-check mr-1"></i>Copied</span>
                </button>
            </div>
        </div>

        {{-- Referred people --}}
        <div class="rounded-2xl bg-white dark:bg-white/[0.04] border border-gray-100 dark:border-white/[0.06] p-5">
            <p class="font-semibold text-gray-900 dark:text-white mb-3">Your referrals</p>
            <div class="divide-y divide-gray-100 dark:divide-white/[0.06] text-sm">
                @forelse ($referrals as $r)
                    <div class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-3 min-w-0">
                            <span class="w-9 h-9 rounded-full bg-emerald-500 text-[#04231a] grid place-items-center font-bold shrink-0">{{ strtoupper(substr($r->name,0,1)) }}</span>
                            <div class="min-w-0">
                                <p class="font-medium text-gray-800 dark:text-gray-200 truncate">{{ $r->name }}</p>
                                <p class="text-[11px] text-gray-400">Joined {{ $r->created_at->format('d M Y') }}</p>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $r->status==='active' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300' : 'bg-amber-100 text-amber-800 dark:bg-amber-500/15 dark:text-amber-300' }}">{{ ucfirst($r->status) }}</span>
                    </div>
                @empty
                    <p class="py-6 text-center text-gray-400">No referrals yet — share your link to start earning.</p>
                @endforelse
            </div>
        </div>
    </div>
</x-client-layout>
