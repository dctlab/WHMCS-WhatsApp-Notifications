<?php

namespace Dct\HookNotification\Core\NotificationReport\Application;

use Dct\HookNotification\Core\NotificationReport\Infrastructure\NotificationReportRepository;
use Dct\HookNotification\Core\Platforms\Common\Infrastructure\PlatformApiClientFactory;
use Dct\HookNotification\Core\Platforms\Common\Infrastructure\PlatformSettingsFactory;
use Dct\HookNotification\Core\Platforms\MetaWhatsApp\Infrastructure\MetaWhatsAppApiClient;
use Throwable;

/**
 * Media handling for the WhatsApp Conversations page:
 *
 * - downloads inbound media (image/audio/video/document/sticker) from the
 *   Meta Cloud API and caches it on disk, outside the web root;
 * - validates and stores files an admin attaches or records in the chat;
 * - streams a cached file back to an authenticated admin.
 *
 * Files live in WHMCS's attachments directory (configuration.php
 * $attachments_dir) under dct_whatsapp_media/, never in the module folder,
 * because nginx ignores .htaccess and the module folder is web-served.
 *
 * @since 5.13.0
 */
final class ChatMediaService
{
    /**
     * WhatsApp Cloud API accepted MIME types and size limits per message type.
     *
     * @see https://developers.facebook.com/docs/whatsapp/cloud-api/reference/media#supported-media-types
     */
    public const LIMITS = [
        'image' => ['max' => 5 * 1024 * 1024, 'mimes' => ['image/jpeg', 'image/png']],
        'video' => ['max' => 16 * 1024 * 1024, 'mimes' => ['video/mp4', 'video/3gpp']],
        'audio' => ['max' => 16 * 1024 * 1024, 'mimes' => ['audio/aac', 'audio/amr', 'audio/mpeg', 'audio/mp4', 'audio/ogg']],
        'sticker' => ['max' => 500 * 1024, 'mimes' => ['image/webp']],
        'document' => ['max' => 100 * 1024 * 1024, 'mimes' => []], // any type
    ];

    /** File types that are never accepted as documents. */
    private const BLOCKED_EXTENSIONS = ['php', 'phtml', 'phar', 'php3', 'php4', 'php5', 'php7', 'pht', 'exe', 'bat', 'cmd', 'sh', 'js', 'html', 'htm', 'svg'];

    private NotificationReportRepository $repository;

    public function __construct()
    {
        $this->repository = new NotificationReportRepository();
    }

    public static function storageDir(): string
    {
        $base = null;

        try {
            // WHMCS 8/9: configuration.php values are exposed via the DI config.
            if (class_exists('\\DI')) {
                $config = \DI::make('config');
                $base   = $config['attachments_dir'] ?? $config->attachments_dir ?? null;
            }
        } catch (Throwable) {
            $base = null;
        }

        $base = $base ?: ($GLOBALS['attachments_dir'] ?? null);

        if (!$base || !is_dir($base) || !is_writable($base)) {
            $base = (defined('ROOTDIR') ? ROOTDIR : dirname(__DIR__, 7)) . '/attachments';
        }

        if (!is_dir($base) || !is_writable($base)) {
            $base = sys_get_temp_dir();
        }

        $dir = rtrim($base, '/\\') . '/dct_whatsapp_media';

        if (!is_dir($dir)) {
            @mkdir($dir, 0750, true);
            @file_put_contents($dir . '/index.html', '');
            @file_put_contents($dir . '/.htaccess', "Require all denied\nDeny from all\n");
        }

        return $dir;
    }

    /**
     * Maps a MIME type to the WhatsApp message type used to send it.
     */
    public static function messageTypeForMime(string $mime, bool $asDocument = false): string
    {
        $mime = strtolower(trim(explode(';', $mime)[0]));

        if ($asDocument) {
            return 'document';
        }

        foreach (['image', 'video', 'audio', 'sticker'] as $type) {
            if (in_array($mime, self::LIMITS[$type]['mimes'], true)) {
                return $type;
            }
        }

        return 'document';
    }

    public static function extensionForMime(string $mime): string
    {
        $mime = strtolower(trim(explode(';', $mime)[0]));

        return match ($mime) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
            'video/mp4' => 'mp4',
            'video/3gpp' => '3gp',
            'audio/ogg' => 'ogg',
            'audio/mpeg' => 'mp3',
            'audio/mp4' => 'm4a',
            'audio/aac' => 'aac',
            'audio/amr' => 'amr',
            'application/pdf' => 'pdf',
            default => 'bin',
        };
    }

    /**
     * Downloads (once) and caches the media for a chat message row, then
     * returns the absolute local path, or null if it can't be fetched (e.g.
     * Meta no longer has it - inbound media is only kept ~30 days).
     */
    public function ensureLocalFile(object $message): ?string
    {
        if (!empty($message->media_path)) {
            $path = self::storageDir() . '/' . basename((string) $message->media_path);

            if (is_file($path)) {
                return $path;
            }
        }

        if (empty($message->media_id)) {
            return null;
        }

        try {
            $client = $this->makeClient();

            if (!$client) {
                return null;
            }

            $info = $client->getMediaInfo((string) $message->media_id);

            if (empty($info['url'])) {
                lkn_hn_log('Chat media: media url lookup failed', ['media_id' => $message->media_id], $info);

                return null;
            }

            $bytes = $client->downloadMediaBinary((string) $info['url']);

            if ($bytes === null) {
                return null;
            }

            $mime     = (string) ($info['mime_type'] ?? $message->media_mime ?? 'application/octet-stream');
            $fileName = hash('sha256', (string) $message->media_id) . '.' . self::extensionForMime($mime);
            $path     = self::storageDir() . '/' . $fileName;

            if (file_put_contents($path, $bytes) === false) {
                lkn_hn_log('Chat media: could not write file', ['path' => $path], []);

                return null;
            }

            $this->repository->updateMessageFields((int) $message->id, [
                'media_path' => $fileName,
                'media_mime' => $mime,
                'media_size' => strlen($bytes),
            ]);

            return $path;
        } catch (Throwable $th) {
            lkn_hn_log('Chat media: download error', ['message_id' => $message->id ?? null], ['exception' => $th->__toString()]);

            return null;
        }
    }

    /**
     * Streams a message's media to the browser and exits.
     */
    public function stream(int $messageId, bool $forceDownload = false): void
    {
        $message = $this->repository->findMessageById($messageId);

        $path = $message ? $this->ensureLocalFile($message) : null;

        if (!$path) {
            http_response_code(404);
            header('Content-Type: text/plain');
            echo 'Media not available (WhatsApp only keeps received media for about 30 days).';
            exit;
        }

        $message = $this->repository->findMessageById($messageId);
        $mime    = strtolower(trim(explode(';', (string) ($message->media_mime ?: 'application/octet-stream'))[0]));

        // Only media types that browsers render safely are served inline;
        // everything else (documents) is always a download.
        $inlineSafe = preg_match('#^(image/(jpeg|png|webp|gif)|audio/[a-z0-9.+-]+|video/(mp4|3gpp))$#', $mime) === 1;

        $downloadName = $message->media_filename
            ?: ('whatsapp-' . $messageId . '.' . self::extensionForMime($mime));
        $downloadName = str_replace(['"', "\r", "\n", '/', '\\'], '_', (string) $downloadName);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: ' . ($inlineSafe ? $mime : 'application/octet-stream'));
        header('Content-Length: ' . filesize($path));
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; sandbox");
        header('Cache-Control: private, max-age=86400');
        header(
            'Content-Disposition: ' . (($inlineSafe && !$forceDownload) ? 'inline' : 'attachment')
            . '; filename="' . $downloadName . '"; filename*=UTF-8\'\'' . rawurlencode($downloadName)
        );

        readfile($path);
        exit;
    }

    /**
     * Validates an uploaded file ($_FILES entry) and moves it into storage.
     *
     * @param array{name?: string, type?: string, tmp_name?: string, error?: int, size?: int} $file
     *
     * @return array{ok: bool, error?: string, type?: string, mime?: string, path?: string, stored_name?: string, filename?: string, size?: int}
     */
    public function storeUpload(array $file, bool $asDocument = false): array
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);

        if ($error !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The file is larger than the server upload limit (upload_max_filesize / post_max_size).',
                UPLOAD_ERR_NO_FILE => 'No file was received.',
                default => 'Upload failed (error code ' . $error . ').',
            }];
        }

        $tmp = (string) ($file['tmp_name'] ?? '');

        if (!is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => 'Invalid upload.'];
        }

        $originalName = trim(basename((string) ($file['name'] ?? 'file')));
        $originalName = preg_replace('/[^\p{L}\p{N}._ ()-]+/u', '_', $originalName) ?: 'file';
        $extension    = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        // Detect the real type from the content, not the browser-sent header.
        $mime = function_exists('finfo_open')
            ? (string) finfo_file(finfo_open(FILEINFO_MIME_TYPE), $tmp)
            : (string) ($file['type'] ?? 'application/octet-stream');

        $mime = strtolower($mime);

        // finfo reports Opus/Vorbis-in-Ogg as audio/ogg or application/ogg,
        // and m4a/aac recordings as video/mp4 or audio/x-m4a.
        if ($mime === 'application/ogg') {
            $mime = 'audio/ogg';
        }

        if (in_array($mime, ['audio/x-m4a', 'audio/m4a'], true) || ($mime === 'video/mp4' && in_array($extension, ['m4a', 'aac'], true))) {
            $mime = 'audio/mp4';
        }

        if ($mime === 'audio/mp3' || $mime === 'audio/x-mpeg') {
            $mime = 'audio/mpeg';
        }

        if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
            return ['ok' => false, 'error' => 'This file type is not allowed.'];
        }

        if (str_starts_with($mime, 'audio/webm') || $mime === 'video/webm') {
            return ['ok' => false, 'error' => 'WebM audio/video is not supported by WhatsApp. Please send MP3, M4A, OGG (Opus) or MP4.'];
        }

        $type = self::messageTypeForMime($mime, $asDocument);
        $size = (int) filesize($tmp);

        // Stickers need a 512x512 WebP - an uploaded WebP photo goes as a
        // document instead. Likewise a non-JPEG/PNG image (gif, heic...) or
        // unsupported video is still deliverable as a document, and so is
        // a photo/video/audio that is over its type limit (100 MB max).
        if ($type === 'sticker' || ($type !== 'document' && $size > self::LIMITS[$type]['max'])) {
            $type = 'document';
        }

        if ($size > self::LIMITS[$type]['max']) {
            return ['ok' => false, 'error' => sprintf(
                'File is too large for a WhatsApp %s (%s MB max).',
                $type,
                round(self::LIMITS[$type]['max'] / 1048576, 1)
            )];
        }

        $storedName = bin2hex(random_bytes(16)) . '.' . ($extension !== '' && preg_match('/^[a-z0-9]{1,8}$/', $extension) ? $extension : self::extensionForMime($mime));
        $target     = self::storageDir() . '/' . $storedName;

        if (!move_uploaded_file($tmp, $target)) {
            return ['ok' => false, 'error' => 'Could not store the uploaded file on the server.'];
        }

        @chmod($target, 0640);

        return [
            'ok' => true,
            'type' => $type,
            'mime' => $mime,
            'path' => $target,
            'stored_name' => $storedName,
            'filename' => $originalName,
            'size' => $size,
        ];
    }

    /**
     * Pre-5.13.0 inbound media rows only have a "[Image message]" placeholder.
     * If module logging was on, the original webhook payload (with the media
     * id) is still in WHMCS's module log - recover the media id from there so
     * those older messages can be displayed too, while Meta still has them.
     *
     * Each row is only attempted once (media_mime is set either way).
     */
    public function backfillLegacyRows(string $phoneNumber): void
    {
        try {
            foreach ($this->repository->getLegacyMediaPlaceholderRows($phoneNumber) as $row) {
                $media = null;
                $raw   = $this->repository->findWebhookLogResponseContaining((string) $row->wa_message_id);

                if ($raw) {
                    $payload = json_decode($raw, true);

                    foreach ($payload['entry'] ?? [] as $entry) {
                        foreach ($entry['changes'] ?? [] as $change) {
                            foreach ($change['value']['messages'] ?? [] as $m) {
                                if (($m['id'] ?? null) === $row->wa_message_id) {
                                    $media = self::extractInboundMedia($m);
                                }
                            }
                        }
                    }
                }

                $legacyType = strtolower((string) preg_replace('/^\[(\w+) message\]$/', '$1', (string) $row->body));

                if ($media) {
                    $this->repository->updateMessageFields((int) $row->id, [
                        'message_type' => $media['type'],
                        'body' => $media['caption'],
                        'media_id' => $media['media_id'],
                        'media_mime' => $media['mime'],
                        'media_filename' => $media['filename'],
                    ]);
                } else {
                    // Mark as attempted so we don't search the log again.
                    $this->repository->updateMessageFields((int) $row->id, [
                        'message_type' => $legacyType ?: 'text',
                        'media_mime' => 'unavailable',
                    ]);
                }
            }
        } catch (Throwable $th) {
            lkn_hn_log('Chat media: legacy backfill error', ['phone' => $phoneNumber], ['exception' => $th->__toString()]);
        }
    }

    /**
     * Pulls the media part out of a Meta inbound message object.
     *
     * @param array<string, mixed> $message
     *
     * @return array{type: string, media_id: string, mime: ?string, filename: ?string, caption: ?string, voice: bool}|null
     */
    public static function extractInboundMedia(array $message): ?array
    {
        $type = (string) ($message['type'] ?? '');

        if (!in_array($type, ['image', 'audio', 'video', 'document', 'sticker'], true)) {
            return null;
        }

        $media = $message[$type] ?? [];

        if (empty($media['id'])) {
            return null;
        }

        $caption = isset($media['caption']) ? trim((string) $media['caption']) : null;

        return [
            'type' => $type,
            'media_id' => (string) $media['id'],
            'mime' => isset($media['mime_type']) ? (string) $media['mime_type'] : null,
            'filename' => isset($media['filename']) ? mb_substr((string) $media['filename'], 0, 255) : null,
            'caption' => $caption !== '' ? $caption : null,
            'voice' => (bool) ($media['voice'] ?? false),
        ];
    }

    /**
     * Short text used for the conversation list preview.
     */
    public static function previewLabel(string $type, ?string $caption = null, ?string $fileName = null, bool $voice = false): string
    {
        $label = match ($type) {
            'image' => '[Image]',
            'video' => '[Video]',
            'audio' => $voice ? '[Voice message]' : '[Audio]',
            'sticker' => '[Sticker]',
            'document' => '[Document' . ($fileName ? ': ' . $fileName : '') . ']',
            default => '[' . ucfirst($type) . ']',
        };

        return $caption ? $label . ' ' . mb_substr($caption, 0, 150) : $label;
    }

    private function makeClient(): ?MetaWhatsAppApiClient
    {
        $settings = PlatformSettingsFactory::makeMetaWhatsAppSettings();

        $client = (new PlatformApiClientFactory())->makeMetaWhatsAppClient($settings);

        return $client->areSettingsFilled() ? $client : null;
    }
}
