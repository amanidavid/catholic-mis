<?php

namespace App\Http\Controllers\Sacraments;

use App\Http\Controllers\Controller;
use App\Models\Sacraments\Marriage;
use App\Models\Sacraments\SacramentAttachment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarriageAttachmentController extends Controller
{
    private const ALLOWED_TYPES = [
        'groom_baptism_certificate',
        'bride_baptism_certificate',
        'groom_confirmation_certificate',
        'bride_confirmation_certificate',
        'groom_id_document',
        'bride_id_document',
        'bride_home_parish_letter',
        'groom_home_parish_letter',
        'groom_parents_marriage_certificate',
        'bride_parents_marriage_certificate',
        'marriage_class_certificate',
        'death_certificate_previous_spouse',
        'annulment_document',
    ];

    public function store(Request $request, Marriage $marriage): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'file' => ['required', 'file', 'max:3072', 'mimetypes:application/pdf'],
        ]);

        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);

        if ($parishId && (int) $marriage->parish_id !== $parishId) {
            abort(403);
        }

        if (in_array($marriage->status, [Marriage::STATUS_APPROVED, Marriage::STATUS_COMPLETED, Marriage::STATUS_ISSUED], true)) {
            return back()->with('error', 'This request can no longer be updated.');
        }

        $type = strtolower(trim((string) $validated['type']));
        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            return back()->with('error', 'Invalid document type.');
        }

        $file = $request->file('file');

        $attachmentUuid = (string) Str::uuid();
        $ext = $file->getClientOriginalExtension();
        $ext = is_string($ext) && $ext !== '' ? strtolower($ext) : 'bin';

        $relativePath = 'sacraments/marriages/'.$marriage->uuid.'/'.$type.'/'.$attachmentUuid.'.'.$ext;

        $stored = Storage::disk('local')->putFileAs(
            dirname($relativePath),
            $file,
            basename($relativePath)
        );

        if (! $stored) {
            return back()->with('error', 'Failed to upload file.');
        }

        $oldDisk = null;
        $oldPath = null;

        try {
            $sha256 = hash_file('sha256', Storage::disk('local')->path($relativePath));

            DB::transaction(function () use ($marriage, $type, $file, $relativePath, $sha256, $user, &$oldDisk, &$oldPath): void {
                $existing = SacramentAttachment::query()
                    ->where('entity_type', 'marriage')
                    ->where('entity_id', (int) $marriage->id)
                    ->where('type', $type)
                    ->orderByDesc('id')
                    ->first();

                if ($existing) {
                    $oldDisk = $existing->storage_disk ?: 'local';
                    $oldPath = $existing->storage_path;
                    $existing->delete();
                }

                SacramentAttachment::create([
                    'uuid' => (string) Str::uuid(),
                    'parish_id' => (int) $marriage->parish_id,
                    'entity_type' => 'marriage',
                    'entity_id' => (int) $marriage->id,
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

        if (is_string($oldPath) && $oldPath !== '') {
            try {
                $disk = Storage::disk(is_string($oldDisk) && $oldDisk !== '' ? $oldDisk : 'local');
                if ($disk->exists($oldPath)) {
                    $disk->delete($oldPath);
                }
            } catch (\Throwable) {
            }
        }

        return back()->with('success', 'File uploaded.');
    }

    public function destroy(Request $request, Marriage $marriage, SacramentAttachment $attachment): RedirectResponse
    {
        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);

        if ($parishId && (int) $marriage->parish_id !== $parishId) {
            abort(403);
        }

        if ($attachment->entity_type !== 'marriage' || (int) $attachment->entity_id !== (int) $marriage->id) {
            abort(404);
        }

        if ($parishId && (int) $attachment->parish_id !== $parishId) {
            abort(403);
        }

        if (in_array($marriage->status, [Marriage::STATUS_APPROVED, Marriage::STATUS_COMPLETED, Marriage::STATUS_ISSUED], true)) {
            return back()->with('error', 'This request can no longer be updated.');
        }

        try {
            DB::transaction(function () use ($attachment): void {
                $attachment->delete();
            });

            $disk = Storage::disk($attachment->storage_disk ?: 'local');
            if ($attachment->storage_path && $disk->exists($attachment->storage_path)) {
                $disk->delete($attachment->storage_path);
            }

            return back()->with('success', 'Attachment removed.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Unable to remove attachment. Please try again.');
        }
    }

    public function download(Request $request, Marriage $marriage, SacramentAttachment $attachment)
    {
        $user = $request->user();
        $parishId = (int) ($user?->parish_id ?? 0);

        if ($parishId && (int) $marriage->parish_id !== $parishId) {
            abort(403);
        }

        if ($attachment->entity_type !== 'marriage' || (int) $attachment->entity_id !== (int) $marriage->id) {
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
