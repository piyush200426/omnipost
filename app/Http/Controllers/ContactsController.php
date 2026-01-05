<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactsController extends Controller
{
    /**
     * List all contacts of logged-in user (JSON for Alpine)
     */
    public function index()
    {
        $userId = (string) Auth::id();

        $contacts = Contact::where('user_id', $userId)
            ->latest()
            ->get()
            ->map(fn ($c) => [
                '_id'          => (string) $c->_id,          // 🔥 IMPORTANT
                'name'         => $c->name,
                'phone_number' => $c->phone_number,
                'opt_in'       => (bool) $c->opt_in,
                'source'       => $c->source ?? 'manual',
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data'    => $contacts,
        ]);
    }

    /**
     * Store a new contact
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'nullable|string|max:255',
            'phone_number' => 'required|string',
            'opt_in'       => 'required|boolean',
        ]);

        $phone = $this->normalizePhone($request->phone_number);

        $contact = Contact::create([
            'user_id'      => (string) Auth::id(),   // 🔥 MUST
            'name'         => $request->name,
            'phone_number' => $phone,
            'opt_in'       => $request->opt_in,
            'source'       => 'manual',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact added successfully',
            'data'    => [
                '_id'          => (string) $contact->_id,
                'name'         => $contact->name,
                'phone_number' => $contact->phone_number,
                'opt_in'       => (bool) $contact->opt_in,
                'source'       => $contact->source,
            ],
        ]);
    }

    /**
     * Upload contacts via CSV
     */
    public function uploadCsv(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $handle = fopen($file->getRealPath(), 'r');

        if (!$handle) {
            return back()->with('error', 'Unable to read CSV file');
        }

        $userId = (string) Auth::id();
        $row = 0;
        $inserted = 0;

        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            $row++;

            // Skip header
            if ($row === 1) continue;

            $name  = $data[0] ?? null;
            $phone = $data[1] ?? null;

            if (!$phone) continue;

            $phone = $this->normalizePhone($phone);

            Contact::create([
                'user_id'      => $userId,
                'name'         => $name,
                'phone_number' => $phone,
                'opt_in'       => true,     // CSV assumed opt-in
                'source'       => 'csv',
            ]);

            $inserted++;
        }

        fclose($handle);

        return back()->with('success', "{$inserted} contacts uploaded successfully");
    }

    /**
     * Normalize phone number to 91XXXXXXXXXX
     */
    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);

        if (strlen($phone) === 10) {
            return '91' . $phone;
        }

        return $phone;
    }
}
