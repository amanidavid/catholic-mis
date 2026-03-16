<?php

namespace App\Http\Controllers\Sacraments;

use App\Http\Controllers\Controller;
use App\Models\Sacraments\Baptism;
use App\Models\Sacraments\SacramentAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BaptismAttachmentController extends Controller
{
    private const ALLOWED_TYPES = [
        'parents_marriage_certificate',
        'birth_certificate',
        'sponsor_confirmation_certificate',
    ];

    public function store(Request $request, Baptism $baptism): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'file' => ['required', 'file', 'max:3072', 'mimetypes:application/pdf'],
        ]);

        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);

        if ($parishId && (int) $baptism->parish_id !== $parishId) {
            abort(403);
        }

        $file = $request->file('file');

        $attachmentUuid = (string) Str::uuid();
        $ext = $file->getClientOriginalExtension();
        $ext = is_string($ext) && $ext !== '' ? strtolower($ext) : 'bin';

        $type = strtolower(trim((string) $validated['type']));

        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            return back()->with('error', 'Invalid document type.');
        }

        $already = SacramentAttachment::query()
            ->where('entity_type', 'baptism')
            ->where('entity_id', (int) $baptism->id)
            ->where('type', $type)
            ->exists();

        if ($already) {
            return back()->with('error', 'This document type was already uploaded.');
        }

        $relativePath = 'sacraments/baptisms/'.$baptism->uuid.'/'.$type.'/'.$attachmentUuid.'.'.$ext;

        $stored = Storage::disk('local')->putFileAs(
            dirname($relativePath),
            $file,
            basename($relativePath)
        );

        if (! $stored) {
            return back()->with('error', 'Failed to upload file.');
        }

        try {
            $sha256 = hash_file('sha256', Storage::disk('local')->path($relativePath));

            DB::transaction(function () use ($baptism, $file, $relativePath, $sha256, $type, $user): void {
                SacramentAttachment::create([
                    'parish_id' => (int) $baptism->parish_id,
                    'entity_type' => 'baptism',
                    'entity_id' => (int) $baptism->id,
                    'type' => $type,
                    'original_name' => (string) $file->getClientOriginalName(),
                    'mime_type' => 'application/pdf',
                    'size_bytes' => (int) $file->getSize(),
                    'storage_disk' => 'local',
                    'storage_path' => $relativePath,
                    'sha256' => $sha256,
                    'uploaded_by_user_id' => (int) $user->id,
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('local')->delete($relativePath);
            return back()->with('error', 'Failed to save uploaded file.');
        }

        return back()->with('success', 'File uploaded.');
    }

    public function download(Request $request, Baptism $baptism, SacramentAttachment $attachment)
    {
        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);

        if ($parishId && (int) $baptism->parish_id !== $parishId) {
            abort(403);
        }

        if ($attachment->entity_type !== 'baptism' || (int) $attachment->entity_id !== (int) $baptism->id) {
            abort(404);
        }

        if ($parishId && (int) $attachment->parish_id !== $parishId) {
            abort(403);
        }

        $disk = Storage::disk($attachment->storage_disk ?: 'local');

        if (! $disk->exists($attachment->storage_path)) {
            abort(404);
        }

        $disposition = $request->query('disposition') === 'inline' ? 'inline' : 'attachment';

        return $disk->response($attachment->storage_path, $attachment->original_name, [
            'Content-Type' => $attachment->mime_type ?: 'application/octet-stream',
            'Content-Disposition' => $disposition.'; filename="'.$attachment->original_name.'"',
        ]);
    }
}
