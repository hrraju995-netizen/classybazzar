<?php

// Polyfill for fileinfo extension when blocked by Windows Smart App Control / policy

if (!defined('FILEINFO_NONE')) {
    define('FILEINFO_NONE', 0);
}
if (!defined('FILEINFO_SYMLINK')) {
    define('FILEINFO_SYMLINK', 2);
}
if (!defined('FILEINFO_MIME_TYPE')) {
    define('FILEINFO_MIME_TYPE', 16);
}
if (!defined('FILEINFO_CONTINUE')) {
    define('FILEINFO_CONTINUE', 32);
}
if (!defined('FILEINFO_PRESERVE_ATIME')) {
    define('FILEINFO_PRESERVE_ATIME', 128);
}
if (!defined('FILEINFO_RAW')) {
    define('FILEINFO_RAW', 256);
}
if (!defined('FILEINFO_MIME_ENCODING')) {
    define('FILEINFO_MIME_ENCODING', 1024);
}
if (!defined('FILEINFO_MIME')) {
    define('FILEINFO_MIME', 1040);
}
if (!defined('FILEINFO_EXTENSION')) {
    define('FILEINFO_EXTENSION', 16777216);
}

if (!class_exists('finfo')) {
    class finfo
    {
        private int $flags;

        public function __construct(int $flags = FILEINFO_NONE, ?string $magic_database = null)
        {
            $this->flags = $flags;
        }

        public function buffer(?string $string = null, int $flags = FILEINFO_NONE, $context = null): string|false
        {
            if ($string === null || $string === '') {
                return false;
            }

            if (str_starts_with($string, "\xFF\xD8\xFF")) {
                return 'image/jpeg';
            }
            if (str_starts_with($string, "\x89PNG\r\n\x1a\n")) {
                return 'image/png';
            }
            if (str_starts_with($string, 'GIF87a') || str_starts_with($string, 'GIF89a')) {
                return 'image/gif';
            }
            if (str_starts_with($string, 'RIFF') && strlen($string) >= 12 && substr($string, 8, 4) === 'WEBP') {
                return 'image/webp';
            }
            if (str_starts_with(trim($string), '<svg') || (str_starts_with(trim($string), '<?xml') && str_contains($string, '<svg'))) {
                return 'image/svg+xml';
            }

            return false;
        }

        public function file(?string $filename = null, int $flags = FILEINFO_NONE, $context = null): string|false
        {
            if (!$filename || !file_exists($filename)) {
                return false;
            }

            $content = @file_get_contents($filename, false, null, 0, 512);
            if ($content !== false && ($mime = $this->buffer($content))) {
                return $mime;
            }

            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mimes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
                'svg' => 'image/svg+xml',
                'bmp' => 'image/bmp',
                'ico' => 'image/x-icon',
                'pdf' => 'application/pdf',
                'json' => 'application/json',
                'txt' => 'text/plain',
                'csv' => 'text/csv',
                'zip' => 'application/zip',
                'mp4' => 'video/mp4',
                'mp3' => 'audio/mpeg',
            ];

            return $mimes[$ext] ?? 'application/octet-stream';
        }

        public function set_flags(int $flags): bool
        {
            $this->flags = $flags;
            return true;
        }
    }
}

if (!function_exists('finfo_open')) {
    function finfo_open(int $flags = FILEINFO_NONE, ?string $magic_database = null): finfo|false
    {
        return new finfo($flags, $magic_database);
    }
}

if (!function_exists('finfo_file')) {
    function finfo_file(finfo $finfo, string $filename, int $flags = FILEINFO_NONE, $context = null): string|false
    {
        return $finfo->file($filename, $flags, $context);
    }
}

if (!function_exists('finfo_buffer')) {
    function finfo_buffer(finfo $finfo, string $string, int $flags = FILEINFO_NONE, $context = null): string|false
    {
        return $finfo->buffer($string, $flags, $context);
    }
}

if (!function_exists('finfo_close')) {
    function finfo_close(finfo $finfo): bool
    {
        return true;
    }
}

if (!function_exists('mime_content_type')) {
    function mime_content_type(mixed $filename): string|false
    {
        if (is_resource($filename) || !is_string($filename)) {
            return false;
        }
        $f = new finfo(FILEINFO_MIME_TYPE);
        return $f->file($filename);
    }
}

