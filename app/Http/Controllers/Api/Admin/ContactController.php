<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    // GET /api/admin/contacts
    public function index(Request $request)
    {
        $contacts = ContactSubmission::with('service:id,name')
            ->when($request->status,
                fn($q) => $q->where('status', $request->status))
            ->when($request->search,
                fn($q) => $q->where(function ($query) use ($request) {
                    $query->where('name', 'like', '%' . $request->search . '%')
                          ->orWhere('email', 'like', '%' . $request->search . '%')
                          ->orWhere('phone', 'like', '%' . $request->search . '%');
                }))
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($contacts);
    }

    // GET /api/admin/contacts/{id}
    public function show($id)
    {
        $contact = ContactSubmission::with('service:id,name')
                                    ->findOrFail($id);

        // Auto mark as read when admin opens it
        $contact->markAsRead();

        return response()->json(['contact' => $contact]);
    }

    // PATCH /api/admin/contacts/{id}/read
    public function markRead($id)
    {
        $contact = ContactSubmission::findOrFail($id);
        $contact->markAsRead();

        return response()->json(['message' => 'Marked as read.']);
    }

    // PATCH /api/admin/contacts/{id}/replied
    public function markReplied($id)
    {
        $contact = ContactSubmission::findOrFail($id);

        $contact->update([
            'status'     => 'replied',
            'replied_at' => now(),
        ]);

        return response()->json(['message' => 'Marked as replied.']);
    }

    // DELETE /api/admin/contacts/{id}
    public function destroy($id)
    {
        $contact = ContactSubmission::findOrFail($id);
        $contact->delete();

        return response()->json(['message' => 'Contact submission deleted.']);
    }
}
