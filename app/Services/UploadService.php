<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use RuntimeException;

class UploadService
{
    public static function image(string $field, string $directory, ?string $existing = null): ?string
    {
        if (empty($_FILES[$field]) || !is_array($_FILES[$field])) {
            return $existing;
        }

        $file = $_FILES[$field];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return $existing;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Image upload failed. Please choose another file.');
        }

        $maxMb = Config::int('UPLOAD_MAX_MB', 3);
        $maxBytes = $maxMb * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxBytes) {
            throw new RuntimeException('Image must not exceed ' . $maxMb . ' MB.');
        }

        $tmpName = (string) $file['tmp_name'];
        // is_uploaded_file() only applies to real HTTP uploads; CLI harnesses
        // (e.g. the SQLite test runner) legitimately build $_FILES manually.
        if (!is_uploaded_file($tmpName) && PHP_SAPI !== 'cli') {
            throw new RuntimeException('Invalid uploaded file.');
        }

        if (function_exists('finfo_open')) {
            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            $mime = (string) $finfo->file($tmpName);
        } else {
            $imageInfo = @getimagesize($tmpName);
            $mime = $imageInfo && !empty($imageInfo['mime']) ? (string) $imageInfo['mime'] : '';
        }
        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];

        if (!isset($allowed[$mime])) {
            throw new RuntimeException('Only JPG, PNG, WEBP or GIF images are allowed.');
        }

        // Reject files that are not actually decodable images and cap pixel size.
        $imageInfo = @getimagesize($tmpName);
        if ($imageInfo === false || empty($imageInfo[0]) || empty($imageInfo[1])) {
            throw new RuntimeException('The uploaded file is not a valid image.');
        }
        if ($imageInfo[0] > 6000 || $imageInfo[1] > 6000) {
            throw new RuntimeException('Image dimensions are too large (max 6000x6000 px).');
        }

        $targetDir = trim($directory, '/');
        $absoluteDir = PUBLIC_PATH . DIRECTORY_SEPARATOR . $targetDir;
        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0775, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        $absolutePath = $absoluteDir . DIRECTORY_SEPARATOR . $filename;

        $moved = false;
        if (PHP_SAPI === 'cli') {
            $moved = @copy($tmpName, $absolutePath);
        } else {
            $moved = @move_uploaded_file($tmpName, $absolutePath);
        }
        if (!$moved) {
            throw new RuntimeException('Could not save uploaded image.');
        }

        return $targetDir . '/' . $filename;
    }
}
