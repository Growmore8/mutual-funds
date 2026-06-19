<x-admin-layout title="{{ $announcement->exists ? 'Edit' : 'New' }} Popup">
    <a href="{{ route('admin.announcements.index') }}" class="text-sm text-gray-500">&larr; Back</a>
    <div class="bg-white shadow rounded-xl p-6 mt-4 max-w-2xl">
        @php $f = fn ($k, $d = '') => old($k, $announcement->$k ?? $d); @endphp
        <form method="POST" action="{{ $announcement->exists ? route('admin.announcements.update',$announcement) : route('admin.announcements.store') }}" class="space-y-4">
            @csrf
            @if ($announcement->exists) @method('PUT') @endif

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Type</label>
                    <select name="type" class="mt-1 w-full border-gray-300 rounded-md">
                        @foreach (['notice'=>'Notice','maintenance'=>'Maintenance','offer'=>'Offer','promotion'=>'Promotion'] as $v=>$l)
                            <option value="{{ $v }}" @selected($f('type','notice')===$v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Show frequency</label>
                    <select name="frequency" class="mt-1 w-full border-gray-300 rounded-md">
                        @foreach (['once'=>'Once per client','daily'=>'Once a day','always'=>'Every app open'] as $v=>$l)
                            <option value="{{ $v }}" @selected($f('frequency','daily')===$v)>{{ $l }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Title</label>
                <input name="title" value="{{ $f('title') }}" class="mt-1 w-full border-gray-300 rounded-md" required>
                @error('title')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Body</label>
                <textarea name="body" rows="3" class="mt-1 w-full border-gray-300 rounded-md">{{ $f('body') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Image URL (optional)</label>
                <input name="image_url" value="{{ $f('image_url') }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="https://…/banner.png">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700">Button label (optional)</label><input name="cta_label" value="{{ $f('cta_label') }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="Learn more"></div>
                <div><label class="block text-sm font-medium text-gray-700">Button link (optional)</label><input name="cta_url" value="{{ $f('cta_url') }}" class="mt-1 w-full border-gray-300 rounded-md" placeholder="https://…"></div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-sm font-medium text-gray-700">Start (optional)</label><input type="datetime-local" name="starts_at" value="{{ optional($announcement->starts_at)->format('Y-m-d\TH:i') }}" class="mt-1 w-full border-gray-300 rounded-md"></div>
                <div><label class="block text-sm font-medium text-gray-700">End (optional)</label><input type="datetime-local" name="ends_at" value="{{ optional($announcement->ends_at)->format('Y-m-d\TH:i') }}" class="mt-1 w-full border-gray-300 rounded-md"></div>
            </div>
            <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($f('is_active',true)) class="rounded"> Active</label>
            <div class="pt-2"><button class="px-5 py-2 bg-emerald-600 text-white rounded-md">{{ $announcement->exists ? 'Save changes' : 'Create' }}</button></div>
        </form>
    </div>
</x-admin-layout>
