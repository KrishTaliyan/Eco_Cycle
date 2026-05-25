<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaUploadService
{
    public function avatar(UploadedFile $file): string
    {
        if ($this->cloudinaryConfigured()) {
            $uploaded = $this->uploadToCloudinary($file);

            if ($uploaded) {
                return $uploaded;
            }
        }

        return Storage::disk('public')->url($file->store('avatars', 'public'));
    }

    private function cloudinaryConfigured(): bool
    {
        return filled(config('services.cloudinary.cloud_name'))
            && filled(config('services.cloudinary.api_key'))
            && filled(config('services.cloudinary.api_secret'));
    }

    private function uploadToCloudinary(UploadedFile $file): ?string
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $timestamp = time();
        $publicId = (string) Str::uuid();
        $signaturePayload = "folder=ecocycle/avatars&public_id={$publicId}&timestamp={$timestamp}".config('services.cloudinary.api_secret');
        $signature = sha1($signaturePayload);

        $response = Http::attach('file', file_get_contents($file->getRealPath()), $file->getClientOriginalName())
            ->asMultipart()
            ->post("https://api.cloudinary.com/v1_1/{$cloudName}/image/upload", [
                ['name' => 'api_key', 'contents' => config('services.cloudinary.api_key')],
                ['name' => 'timestamp', 'contents' => (string) $timestamp],
                ['name' => 'folder', 'contents' => 'ecocycle/avatars'],
                ['name' => 'public_id', 'contents' => $publicId],
                ['name' => 'signature', 'contents' => $signature],
            ]);

        return $response->successful() ? $response->json('secure_url') : null;
    }
}
