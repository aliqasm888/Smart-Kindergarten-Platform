<?php

namespace App\Services;

use App\Models\Attachment;
use App\Models\Lesson;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AttachmentService
{
    public function getLessonAttachments(Lesson $lesson)
    {
        return $lesson->attachments()->latest()->get();
    }

    public function createAttachment(Lesson $lesson, array $data): Attachment
    {
        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            $fileData = $this->handleFileUpload($data['file'], $data['type']);
            $data = array_merge($data, $fileData);
            unset($data['file']);
        }

        return $lesson->attachments()->create($data);
    }

    public function updateAttachment(Attachment $attachment, array $data): Attachment
    {
        if (isset($data['file']) && $data['file'] instanceof UploadedFile) {
            // Delete old file if exists
            if ($attachment->url && !$attachment->isLink()) {
                Storage::delete($attachment->url);
            }

            $fileData = $this->handleFileUpload($data['file'], $data['type']);
            $data = array_merge($data, $fileData);
            unset($data['file']);
        }

        $attachment->update($data);
        return $attachment;
    }

    public function deleteAttachment(Attachment $attachment): void
    {
        if (!$attachment->isLink() && $attachment->url) {
            Storage::delete($attachment->url);
        }
        $attachment->delete();
    }

    public function validateAttachmentBelongsToLesson(Lesson $lesson, Attachment $attachment): void
    {
        if ($attachment->attachable_id !== $lesson->id || $attachment->attachable_type !== Lesson::class) {
            abort(404, 'Attachment not found for this lesson');
        }
    }

    private function handleFileUpload(UploadedFile $file, string $type): array
    {
        $extension = $file->getClientOriginalExtension();
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $fileName = Str::slug($originalName) . '-' . time() . '.' . $extension;
        $path = "attachments/{$type}s/{$fileName}";

        $file->storeAs("public/attachments/{$type}s", $fileName);

        return [
            'url' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ];
    }
}
