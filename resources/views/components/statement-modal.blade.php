@props(['baseUrl'])
{{-- Statement: choose account -> period -> download / email. --}}
<div x-data="{
        open:false, step:1, scope:'fund', period:'month', from:'', to:'', sending:false, msg:'', ok:false,
        scopeLabel(){ return {fund:'Mutual Fund', spot_usd:'Spot · NYSE (US/Global/Crypto)', spot_inr:'Spot · NSE (India)', all:'All accounts'}[this.scope]; },
        pick(s){ this.scope=s; this.step=2; },
        url(action){
            let p = new URLSearchParams({scope:this.scope, period:this.period, action});
            if(this.period==='custom'){ p.set('from', this.from); p.set('to', this.to); }
            return @js($baseUrl) + '?' + p.toString();
        },
        emailNow(){
            this.sending=true; this.ok=false; this.msg='Sending…';
            fetch(this.url('email'), {headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}})
                .then(r => r.json().then(d => ({status:r.ok, d})))
                .then(({status, d}) => { this.ok = status && d.ok; this.msg = d.message || (this.ok ? 'Sent.' : 'Could not send.'); })
                .catch(() => { this.ok=false; this.msg='Could not send. Please try again.'; })
                .finally(() => { this.sending=false; });
        }
     }" class="contents">
    <button type="button" @click="open=true; step=1; msg=''" {{ $attributes }}>{{ $slot }}</button>

    <template x-teleport="body">
        <div x-show="open" x-cloak style="display:none" class="fixed inset-0 z-[60] grid place-items-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="open=false"></div>
            <div class="relative bg-white dark:bg-[#0f1b38] dark:ring-1 dark:ring-white/10 rounded-2xl shadow-xl w-full max-w-sm p-5 text-sm">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3"><i class="fa-solid fa-file-pdf text-emerald-500 mr-1"></i> Statement (PDF)</h3>

                {{-- Step 1: account --}}
                <div x-show="step===1">
                    <p class="text-gray-600 dark:text-gray-300 mb-2">Which account?</p>
                    <div class="space-y-2">
                        <button @click="pick('fund')" class="w-full text-left px-3 py-2.5 rounded-lg border border-gray-200 dark:border-white/10 hover:border-emerald-400"><i class="fa-solid fa-layer-group text-emerald-500 mr-1"></i> Mutual Fund</button>
                        <button @click="pick('spot_usd')" class="w-full text-left px-3 py-2.5 rounded-lg border border-gray-200 dark:border-white/10 hover:border-blue-400"><i class="fa-solid fa-arrow-trend-up text-blue-500 mr-1"></i> NYSE · US/Global/Crypto</button>
                        <button @click="pick('spot_inr')" class="w-full text-left px-3 py-2.5 rounded-lg border border-gray-200 dark:border-white/10 hover:border-orange-400"><i class="fa-solid fa-arrow-trend-up text-orange-500 mr-1"></i> NSE · India</button>
                        <button @click="pick('all')" class="w-full text-left px-3 py-2.5 rounded-lg border border-gray-200 dark:border-white/10 hover:border-emerald-400"><i class="fa-solid fa-layer-group mr-1"></i> All accounts</button>
                    </div>
                </div>

                {{-- Step 2: period --}}
                <div x-show="step===2" x-cloak>
                    <button @click="step=1; msg=''" class="text-xs text-emerald-600 mb-2"><i class="fa-solid fa-chevron-left"></i> <span x-text="scopeLabel()"></span></button>
                    <label class="block text-gray-600 dark:text-gray-300 mb-1">Period</label>
                    <select x-model="period" class="w-full border-gray-300 rounded-md mb-3 dark:bg-white/10 dark:border-white/10 dark:text-white">
                        <option value="week">This week</option>
                        <option value="month">This month</option>
                        <option value="year">This year</option>
                        <option value="custom">Custom range</option>
                    </select>
                    <div x-show="period==='custom'" class="grid grid-cols-2 gap-2 mb-3">
                        <div><label class="block text-xs text-gray-500 mb-1">From</label><input type="date" x-model="from" class="w-full border-gray-300 rounded-md dark:bg-white/10 dark:border-white/10 dark:text-white"></div>
                        <div><label class="block text-xs text-gray-500 mb-1">To</label><input type="date" x-model="to" class="w-full border-gray-300 rounded-md dark:bg-white/10 dark:border-white/10 dark:text-white"></div>
                    </div>
                    <div class="flex gap-2 mt-2">
                        <a :href="url('download')" target="_blank" rel="noopener" class="flex-1 text-center px-3 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold"><i class="fa-solid fa-download mr-1"></i> Download</a>
                        <button type="button" @click="emailNow()" :disabled="sending" class="flex-1 text-center px-3 py-2.5 rounded-lg border border-emerald-600 text-emerald-700 dark:text-emerald-300 font-semibold disabled:opacity-60"><i class="fa-solid fa-paper-plane mr-1"></i> <span x-text="sending ? 'Sending…' : 'Email'"></span></button>
                    </div>
                    <p x-show="msg" x-cloak x-text="msg" :class="ok ? 'text-emerald-600 dark:text-emerald-400' : 'text-red-600 dark:text-red-400'" class="mt-3 text-xs text-center font-medium"></p>
                </div>

                <button type="button" @click="open=false; msg=''" class="mt-4 w-full text-center text-gray-400 text-xs hover:text-gray-600">Close</button>
            </div>
        </div>
    </template>
</div>
