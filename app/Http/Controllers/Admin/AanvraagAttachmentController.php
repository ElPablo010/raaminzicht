<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Aanvraag;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Download van een bijlage bij een aanvraag (admin → Aanvragen). De bestanden
 * staan op de private 'local' disk, dus enkel admins mogen ze ophalen.
 */
class AanvraagAttachmentController extends Controller
{
    public function __invoke(Request $request, Aanvraag $aanvraag, int $index): StreamedResponse
    {
        $user = $request->user();
        abort_unless($user && $user->canAccessPanel(Filament::getPanel('admin')), 403);

        $file = ($aanvraag->attachments ?? [])[$index] ?? null;
        abort_unless($file && ! empty($file['path']) && Storage::disk('local')->exists($file['path']), 404);

        return Storage::disk('local')->download($file['path'], $file['name'] ?? basename($file['path']));
    }
}
