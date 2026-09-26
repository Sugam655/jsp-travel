<?php

namespace Modules\Contact\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Modules\Contact\Models\ContactMessage;

class ContactMessageController extends Controller
{
    /**
     * Display a listing of the contact messages.
     */
    public function index(): View
    {
        $messages = ContactMessage::query()
            ->orderByDesc('id')
            ->get();

        return view('contact::admin.messages.index', [
            'messages' => $messages,
            'heads' => [
                'ID',
                'Name',
                'Email',
                'Phone',
                'Subject',
                'Status',
                'Date',
                ['label' => 'Actions', 'no-export' => true],
            ],
            'config' => [
                'order' => [[0, 'desc']],
                'pageLength' => 10,
                'lengthMenu' => [[5, 10, 25, 50], [5, 10, 25, 50]],
                'autoWidth' => false,
                'layout' => [
                    'topStart' => 'pageLength',
                    'topEnd' => 'search',
                    'bottomStart' => 'info',
                    'bottomEnd' => 'paging',
                ],
                'columnDefs' => [
                    ['type' => 'num', 'targets' => 0],
                    ['orderable' => false, 'searchable' => false, 'targets' => 7],
                ],
                'language' => [
                    'search' => 'Search:',
                    'lengthMenu' => 'Show _MENU_ entries',
                    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
                    'emptyTable' => 'No messages received yet.',
                ],
            ],
        ]);
    }

    /**
     * Display the selected message and mark it as read.
     */
    public function show(ContactMessage $message): View
    {
        if ($message->status === 'new') {
            $message->update([
                'status' => 'read',
                'read_at' => now(),
            ]);
        }

        return view('contact::admin.messages.show', [
            'message' => $message,
            'statuses' => ContactMessage::STATUSES,
        ]);
    }

    /**
     * Update the status (and optional admin note) of the selected message.
     */
    public function status(Request $request, ContactMessage $message): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(ContactMessage::STATUSES)],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $message->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? null,
            'read_at' => $message->read_at ?? now(),
        ]);

        return redirect()
            ->route('admin.contact.messages.show', $message)
            ->with('success', 'Message marked as '.$message->status_label.'.');
    }

    /**
     * Remove the selected message.
     */
    public function destroy(ContactMessage $message): RedirectResponse
    {
        $message->delete();

        return redirect()
            ->route('admin.contact.messages.index')
            ->with('success', 'Message deleted successfully.');
    }
}
