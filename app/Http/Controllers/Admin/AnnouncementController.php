<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use Illuminate\Http\Request;

class AnnouncementController extends Controller
{
    public function index()
    {
        return view('admin.announcements.index', [
            'announcements' => Announcement::latest()->get(),
        ]);
    }

    public function create()
    {
        return view('admin.announcements.form', ['announcement' => new Announcement(['is_active' => true, 'type' => 'notice', 'frequency' => 'daily'])]);
    }

    public function store(Request $request)
    {
        Announcement::create($this->validated($request));

        return redirect()->route('admin.announcements.index')->with('status', 'Popup created.');
    }

    public function edit(Announcement $announcement)
    {
        return view('admin.announcements.form', ['announcement' => $announcement]);
    }

    public function update(Request $request, Announcement $announcement)
    {
        $announcement->update($this->validated($request));

        return redirect()->route('admin.announcements.index')->with('status', 'Popup updated.');
    }

    public function destroy(Announcement $announcement)
    {
        $announcement->delete();

        return back()->with('status', 'Popup deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'type' => ['required', 'in:maintenance,notice,offer,promotion'],
            'title' => ['required', 'string', 'max:140'],
            'body' => ['nullable', 'string', 'max:2000'],
            'image_url' => ['nullable', 'string', 'max:500'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:500'],
            'frequency' => ['required', 'in:once,daily,always'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date'],
        ]) + ['is_active' => (bool) $request->boolean('is_active')];
    }
}
