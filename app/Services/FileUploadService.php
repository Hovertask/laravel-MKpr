<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileUploadService
{
    /**
     * Upload a file to a dynamic folder with a unique name
     *
     * @param UploadedFile $file
     * @param string $folder
     * @param string $userEmail
     * @return string
     */
    public function upload(UploadedFile $file, string $folder)
    {
        $userEmail = auth()->user()->email ?? 'guest';
        // Generate a unique file name using user email + timestamp
        $timestamp = now()->format('Ymd_His');
        $extension = $file->getClientOriginalExtension();
        $emailSlug = Str::slug($userEmail, '_');

        $fileName = "{$emailSlug}_{$timestamp}.{$extension}";

        // Store the file in the specified folder
        return $file->storeAs($folder, $fileName, 'public');
    }

    /**
     * Delete an existing file from storage
     *
     * @param string $filePath
     * @return void
     */
    public function delete(string $filePath)
    {
        if (Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
    }
}
