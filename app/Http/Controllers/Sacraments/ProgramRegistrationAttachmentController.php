<?php

namespace App\Http\Controllers\Sacraments;

use App\Http\Controllers\Controller;
use App\Models\Leadership\JumuiyaLeadership;
use App\Models\Sacraments\SacramentAttachment;
use App\Models\Sacraments\SacramentProgramCycle;
use App\Models\Sacraments\SacramentProgramRegistration;
use App\Models\Structure\Jumuiya;
use App\Services\Sacraments\SacramentWorkflowEventService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProgramRegistrationAttachmentController extends Controller
{
    private const ALLOWED_TYPES = [
        SacramentProgramRegistration::TYPE_BAPTISM_CERTIFICATE,
        SacramentProgramRegistration::TYPE_PARISH_LETTER_COMMUNION_STUDY,
        'transfer_approval_letter',
    ];

    protected SacramentWorkflowEventService $workflowEvents;

    public function __construct(SacramentWorkflowEventService $workflowEvents)
    {
        $this->workflowEvents = $workflowEvents;
    }

    private function activeLeadershipJumuiyaIds(int $memberId): array
    {
        if (! $memberId) {
            return [];
        }

        $today = now()->toDateString();

        return JumuiyaLeadership::query()
            ->where('member_id', $memberId)
            ->where('is_active', true)
            ->where(function ($q) use ($today) {
                $q->whereNull('start_date')->orWhere('start_date', '<=', $today);
            })
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $today);
            })
            ->pluck('jumuiya_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values()
            ->all();
    }

    private function ensureActiveJumuiyaLeaderForMember(?int $userId, int $userMemberId, int $targetJumuiyaId): void
    {
        if (! $userId || ! $targetJumuiyaId) {
            abort(403, 'Invalid user context.');
        }

        if (! $userMemberId) {
            abort(403, 'Your account is not linked to a member record. Please contact the parish admin.');
        }

        $today = now()->toDateString();

        $jumuiyaName = Jumuiya::query()->where('id', $targetJumuiyaId)->value('name');
        $jumuiyaLabel = $jumuiyaName ? ($jumuiyaName.' (ID '.$targetJumuiyaId.')') : ('Jumuiya ID '.$targetJumuiyaId);

        $leadership = JumuiyaLeadership::query()
            ->where('member_id', $userMemberId)
            ->where('jumuiya_id', $targetJumuiyaId)
            ->orderByDesc('start_date')
            ->first();

        if (! $leadership) {
            abort(403, 'You are not assigned as Jumuiya leadership for '.$jumuiyaLabel.'.');
        }

        if (! (bool) $leadership->is_active) {
            abort(403, 'Your Jumuiya leadership assignment is inactive for '.$jumuiyaLabel.'.');
        }

        if ($leadership->start_date && $leadership->start_date->toDateString() > $today) {
            abort(403, 'Your Jumuiya leadership assignment starts on '.$leadership->start_date->toDateString().' for '.$jumuiyaLabel.'.');
        }

        if ($leadership->end_date && $leadership->end_date->toDateString() < $today) {
            abort(403, 'Your Jumuiya leadership assignment ended on '.$leadership->end_date->toDateString().' for '.$jumuiyaLabel.'.');
        }
    }

    private function canViewRegistration(Request $request, SacramentProgramRegistration $registration): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        $canGlobalView = $user->can('users.manage')
            || $user->can('permissions.manage')
            || $user->can('sacraments.cross_parish.search');

        if ($canGlobalView) {
            return true;
        }

        $parishId = (int) ($user->parish_id ?? 0);

        $canParishView = false;
        $canLeaderView = false;

        if (($registration->program ?? null) === SacramentProgramCycle::PROGRAM_FIRST_COMMUNION) {
            $canParishView = $user->can('communions.parish.view');
            $canLeaderView = $user->can('communions.view');
        }

        if (($registration->program ?? null) === SacramentProgramCycle::PROGRAM_CONFIRMATION) {
            $canParishView = $user->can('confirmations.parish.view');
            $canLeaderView = $user->can('confirmations.view');
        }

        if ($parishId && (int) $registration->parish_id === $parishId && $canParishView) {
            return true;
        }

        $memberId = (int) ($user->member_id ?? $user->member?->id ?? 0);
        $leaderJumuiyaIds = $this->activeLeadershipJumuiyaIds($memberId);

        return $canLeaderView
            && (int) $registration->origin_jumuiya_id > 0
            && in_array((int) $registration->origin_jumuiya_id, $leaderJumuiyaIds, true);
    }

    private function canEditRegistration(Request $request, SacramentProgramRegistration $registration): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        $canGlobalOverride = $user->can('users.manage')
            || $user->can('permissions.manage')
            || $user->can('sacraments.cross_parish.search')
            || $user->can('sacraments.cycle.override');

        if (($registration->program ?? null) === SacramentProgramCycle::PROGRAM_FIRST_COMMUNION) {
            if (! $user->can('communions.register')) {
                return false;
            }
        } elseif (($registration->program ?? null) === SacramentProgramCycle::PROGRAM_CONFIRMATION) {
            if (! $user->can('confirmations.register')) {
                return false;
            }
        } else {
            return false;
        }

        $userMemberId = (int) ($user->member_id ?? $user->member?->id ?? 0);
        $this->ensureActiveJumuiyaLeaderForMember((int) $user->id, $userMemberId, (int) $registration->origin_jumuiya_id);

        return true;
    }

    public function store(Request $request, SacramentProgramRegistration $registration): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'max:100'],
            'file' => ['required', 'file', 'max:3072', 'mimetypes:application/pdf'],
        ]);

        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        $type = strtolower(trim((string) $validated['type']));
        if (! in_array($type, self::ALLOWED_TYPES, true)) {
            return back()->with('error', 'Invalid document type.');
        }

        if (! $this->canEditRegistration($request, $registration)) {
            abort(403);
        }

        if (in_array($registration->status, [
            SacramentProgramRegistration::STATUS_SUBMITTED,
            SacramentProgramRegistration::STATUS_APPROVED,
            SacramentProgramRegistration::STATUS_COMPLETED,
            SacramentProgramRegistration::STATUS_ISSUED,
        ], true)) {
            return back()->with('error', 'This registration can no longer be updated.');
        }

        $file = $request->file('file');

        $attachmentUuid = (string) Str::uuid();
        $ext = $file->getClientOriginalExtension();
        $ext = is_string($ext) && $ext !== '' ? strtolower($ext) : 'bin';

        $relativePath = 'sacraments/program-registrations/'.$registration->uuid.'/'.$type.'/'.$attachmentUuid.'.'.$ext;

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
        $createdAttachment = null;

        try {
            $sha256 = hash_file('sha256', Storage::disk('local')->path($relativePath));

            DB::transaction(function () use ($registration, $type, $file, $relativePath, $sha256, $user, &$oldDisk, &$oldPath, &$createdAttachment): void {
                $existing = SacramentAttachment::query()
                    ->where('entity_type', 'program_registration')
                    ->where('entity_id', (int) $registration->id)
                    ->where('type', $type)
                    ->orderByDesc('id')
                    ->first();

                if ($existing) {
                    $oldDisk = $existing->storage_disk ?: 'local';
                    $oldPath = $existing->storage_path;
                    $existing->delete();
                }

                $createdAttachment = SacramentAttachment::create([
                    'uuid' => (string) Str::uuid(),
                    'parish_id' => (int) $registration->parish_id,
                    'entity_type' => 'program_registration',
                    'entity_id' => (int) $registration->id,
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

        if ($createdAttachment) {
            $this->workflowEvents->record(
                $request,
                (int) $registration->parish_id,
                SacramentWorkflowEventService::ENTITY_PROGRAM_REGISTRATION,
                (int) $registration->id,
                'attachment_upload',
                null,
                null,
                [
                    'program' => (string) ($registration->program ?? ''),
                    'type' => (string) $type,
                    'attachment_uuid' => (string) ($createdAttachment->uuid ?? ''),
                    'original_name' => (string) ($createdAttachment->original_name ?? ''),
                    'sha256' => (string) ($createdAttachment->sha256 ?? ''),
                    'size_bytes' => (int) ($createdAttachment->size_bytes ?? 0),
                ]
            );
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

    public function destroy(Request $request, SacramentProgramRegistration $registration, SacramentAttachment $attachment): RedirectResponse
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if ($attachment->entity_type !== 'program_registration' || (int) $attachment->entity_id !== (int) $registration->id) {
            abort(404);
        }

        if (! $this->canEditRegistration($request, $registration)) {
            abort(403);
        }

        if (in_array($registration->status, [
            SacramentProgramRegistration::STATUS_SUBMITTED,
            SacramentProgramRegistration::STATUS_APPROVED,
            SacramentProgramRegistration::STATUS_COMPLETED,
            SacramentProgramRegistration::STATUS_ISSUED,
        ], true)) {
            return back()->with('error', 'This registration can no longer be updated.');
        }

        $meta = [
            'program' => (string) ($registration->program ?? ''),
            'type' => (string) ($attachment->type ?? ''),
            'attachment_uuid' => (string) ($attachment->uuid ?? ''),
            'original_name' => (string) ($attachment->original_name ?? ''),
            'sha256' => (string) ($attachment->sha256 ?? ''),
            'size_bytes' => (int) ($attachment->size_bytes ?? 0),
        ];

        try {
            DB::transaction(function () use ($attachment): void {
                $attachment->delete();
            });

            $disk = Storage::disk($attachment->storage_disk ?: 'local');
            if ($attachment->storage_path && $disk->exists($attachment->storage_path)) {
                $disk->delete($attachment->storage_path);
            }

            $this->workflowEvents->record(
                $request,
                (int) $registration->parish_id,
                SacramentWorkflowEventService::ENTITY_PROGRAM_REGISTRATION,
                (int) $registration->id,
                'attachment_delete',
                null,
                null,
                $meta
            );

            return back()->with('success', 'Attachment removed.');
        } catch (\Throwable $e) {
            return back()->with('error', 'Unable to remove attachment. Please try again.');
        }
    }

    public function download(Request $request, SacramentProgramRegistration $registration, SacramentAttachment $attachment)
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }

        if ($attachment->entity_type !== 'program_registration' || (int) $attachment->entity_id !== (int) $registration->id) {
            abort(404);
        }

        if (! $this->canViewRegistration($request, $registration)) {
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
