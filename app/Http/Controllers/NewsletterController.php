<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Inscription à la newsletter de la page vitrine.
 * Le formulaire du template envoyait les inscrits à un service du créateur du template : il écrit maintenant ici.
 */
class NewsletterController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        // Champs-pièges du formulaire Framer : un robot les remplit, un humain non.
        if ($request->filled('website') || $request->filled('company') || $request->filled('message')) {
            return response()->json(['success' => true]);
        }

        $data = $request->validate([
            'Name' => ['required', 'string', 'max:120'],
            'Email' => ['required', 'email:rfc', 'max:190'],
        ]);

        $email = mb_strtolower(trim($data['Email']));
        $now = now();

        DB::table('newsletter_subscribers')->updateOrInsert(
            ['email' => $email],
            ['name' => trim($data['Name']), 'source' => 'vitrine', 'updated_at' => $now, 'created_at' => $now]
        );

        return response()->json(['success' => true]);
    }
}
