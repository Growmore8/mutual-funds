@props(['baseUrl'])
{{-- Trigger + modal for choosing a statement period and downloading / emailing the PDF. --}}
<div x-data="{
        open:false, period:'month', from:'', to:'',
        url(action){
            let p = new URLSearchParams({period:this.period, action});
            if(this.period==='custom'){ p.set('from', this.from); p.set('to', this.to); }
            return @js($baseUrl) + '?' + p.toString();
        }
     }" class="contents">
    <button type="button" @click="open=true" {{ $attributes }}>{{ $slot }}</button>

    <template x-teleport="body">
        <div x-show="open" x-cloak style="display:none" class="fixed inset-0 z-[60] grid place-items-center p-4">
            <div class="absolute inset-0 bg-black/50" @click="open=false"></div>
            <div class="relative bg-white dark:bg-[#0f1b38] dark:ring-1 dark:ring-white/10 rounded-2xl shadow-xl w-full max-w-sm p-5 text-sm">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-3"><i class="fa-solid fa-file-pdf text-emerald-500 mr-1"></i> Statement (PDF)</h3>

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
                    <a :href="url('download')" class="flex-1 text-center px-3 py-2.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white font-semibold"><i class="fa-solid fa-download mr-1"></i> Download</a>
                    <a :href="url('email')" @click="open=false" class="flex-1 text-center px-3 py-2.5 rounded-lg border border-emerald-600 text-emerald-700 dark:text-emerald-300 font-semibold"><i class="fa-solid fa-paper-plane mr-1"></i> Email</a>
                </div>
                <button type="button" @click="open=false" class="mt-3 w-full text-center text-gray-400 text-xs hover:text-gray-600">Cancel</button>
            </div>
        </div>
    </template>
</div>
