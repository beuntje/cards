<?php

namespace Cards;

class Card
{
    public static function all(int $userId, array $options = []): array
    {
        $db = Database::getInstance();
        $params = [$userId];

        $sort = $options['sort'] ?? 'smart';
        $allowed = ['name', 'usage', 'location', 'created_at', 'smart'];
        if (!in_array($sort, $allowed)) {
            $sort = 'smart';
        }

        $lat = $options['latitude'] ?? null;
        $lng = $options['longitude'] ?? null;

        if ($sort === 'smart' && $lat !== null && $lng !== null) {
            // Smart sort: nearby (1km) first, then by usage count, then by recency
            $sql = 'SELECT c.*,
                    COUNT(cu.id) AS usage_count,
                    SUM(CASE WHEN cu.latitude IS NOT NULL AND (6371000 * ACOS(
                        COS(RADIANS(?)) * COS(RADIANS(cu.latitude)) *
                        COS(RADIANS(cu.longitude) - RADIANS(?)) +
                        SIN(RADIANS(?)) * SIN(RADIANS(cu.latitude))
                    )) <= 1000 THEN 1 ELSE 0 END) AS nearby_count
                    FROM cards c
                    LEFT JOIN card_usage cu ON cu.card_id = c.id
                    WHERE c.user_id = ?';
            $params = [$lat, $lng, $lat, $userId];
        } elseif ($sort === 'usage' || ($sort === 'smart' && $lat === null)) {
            $sql = 'SELECT c.*, COUNT(cu.id) AS usage_count FROM cards c
                    LEFT JOIN card_usage cu ON cu.card_id = c.id
                    WHERE c.user_id = ?';
        } elseif ($sort === 'location') {
            if ($lat !== null && $lng !== null) {
                $sql = 'SELECT c.*, COUNT(cu.id) AS nearby_count FROM cards c
                        LEFT JOIN card_usage cu ON cu.card_id = c.id
                            AND cu.latitude IS NOT NULL
                            AND (6371000 * ACOS(
                                COS(RADIANS(?)) * COS(RADIANS(cu.latitude)) *
                                COS(RADIANS(cu.longitude) - RADIANS(?)) +
                                SIN(RADIANS(?)) * SIN(RADIANS(cu.latitude))
                            )) <= 1000
                        WHERE c.user_id = ?';
                $params = [$lat, $lng, $lat, $userId];
            } else {
                $sql = 'SELECT c.*, 0 AS nearby_count FROM cards c WHERE c.user_id = ?';
            }
        } else {
            $sql = 'SELECT c.* FROM cards c WHERE c.user_id = ?';
        }

        if (!empty($options['search'])) {
            $sql .= ' AND c.name LIKE ?';
            $params[] = '%' . $options['search'] . '%';
        }

        if ($sort === 'smart' && $lat !== null && $lng !== null) {
            $sql .= ' GROUP BY c.id ORDER BY c.favorite DESC, nearby_count DESC, usage_count DESC, c.created_at DESC';
        } elseif ($sort === 'usage' || ($sort === 'smart' && $lat === null)) {
            $sql .= ' GROUP BY c.id ORDER BY c.favorite DESC, usage_count DESC, c.created_at DESC';
        } elseif ($sort === 'location') {
            $sql .= ' GROUP BY c.id ORDER BY nearby_count DESC, c.name ASC';
        } elseif ($sort === 'name') {
            $sql .= ' ORDER BY c.name ASC';
        } else {
            $sql .= ' ORDER BY c.created_at DESC';
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id, int $userId): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('SELECT * FROM cards WHERE id = ? AND user_id = ?');
        $stmt->execute([$id, $userId]);
        return $stmt->fetch() ?: null;
    }

    public static function create(int $userId, array $data): int
    {
        $db = Database::getInstance();
        $logo = self::downloadLogo($data['logo_url'] ?? '');
        $manualColor = ($data['color'] ?? '') !== '#ffffff' ? ($data['color'] ?? '') : '';
        $color = $manualColor ?: self::extractColor($logo);
        if (!$color && !$logo) {
            $color = self::randomColor();
        }
        $stmt = $db->prepare(
            'INSERT INTO cards (user_id, name, number, barcode_type, logo, color) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $data['name'],
            $data['number'],
            $data['barcode_type'] ?? 'code128',
            $logo,
            $color,
        ]);
        return (int) $db->lastInsertId();
    }

    public static function update(int $id, int $userId, array $data): bool
    {
        $db = Database::getInstance();
        $logo = self::downloadLogo($data['logo_url'] ?? '');
        $manualColor = ($data['color'] ?? '') !== '#ffffff' ? ($data['color'] ?? '') : '';
        $color = $manualColor ?: self::extractColor($logo);
        $favorite = !empty($data['favorite']) ? 1 : 0;

        $stmt = $db->prepare(
            'UPDATE cards SET name = ?, number = ?, barcode_type = ?, logo = ?, color = ?, favorite = ? WHERE id = ? AND user_id = ?'
        );
        return $stmt->execute([
            $data['name'],
            $data['number'],
            $data['barcode_type'] ?? 'code128',
            $logo,
            $color,
            $favorite,
            $id,
            $userId,
        ]);
    }

    public static function delete(int $id, int $userId): bool
    {
        $db = Database::getInstance();
        $stmt = $db->prepare('DELETE FROM cards WHERE id = ? AND user_id = ?');
        return $stmt->execute([$id, $userId]);
    }


    public static function recordUsage(int $id, ?float $latitude = null, ?float $longitude = null): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare(
            'INSERT INTO card_usage (card_id, latitude, longitude) VALUES (?, ?, ?)'
        );
        $stmt->execute([$id, $latitude, $longitude]);
    }

    public static function getTopLocations(int $cardId, int $limit = 3, int $radiusMeters = 1000): array
    {
        $db = Database::getInstance();
        // Get distinct location clusters by rounding coordinates (~100m precision)
        $stmt = $db->prepare(
            'SELECT ROUND(latitude, 3) AS lat, ROUND(longitude, 3) AS lng, COUNT(*) AS visit_count
             FROM card_usage
             WHERE card_id = ? AND latitude IS NOT NULL
             GROUP BY ROUND(latitude, 3), ROUND(longitude, 3)
             ORDER BY visit_count DESC
             LIMIT ?'
        );
        $stmt->execute([$cardId, $limit]);
        return $stmt->fetchAll();
    }


    private static function extractColor(?string $logoPath): ?string
    {
        if (empty($logoPath)) {
            return null;
        }

        $file = __DIR__ . '/../public_html' . $logoPath;
        if (!file_exists($file)) {
            return null;
        }

        $img = @imagecreatefromwebp($file);
        if (!$img) {
            $img = @imagecreatefrompng($file);
        }
        if (!$img) {
            $img = @imagecreatefromjpeg($file);
        }
        if (!$img) {
            return null;
        }

        $w = imagesx($img);
        $h = imagesy($img);

        // Sample corner and edge pixels to find the background color
        $samples = [
            [0, 0], [1, 0], [0, 1],
            [$w - 1, 0], [$w - 2, 0], [$w - 1, 1],
            [0, $h - 1], [1, $h - 1], [0, $h - 2],
            [$w - 1, $h - 1], [$w - 2, $h - 1], [$w - 1, $h - 2],
            [(int)($w / 2), 0], [(int)($w / 2), $h - 1],
            [0, (int)($h / 2)], [$w - 1, (int)($h / 2)],
        ];

        $colors = [];
        foreach ($samples as [$x, $y]) {
            $rgb = imagecolorat($img, $x, $y);
            $a = ($rgb >> 24) & 0x7F;
            if ($a > 64) {
                continue; // skip mostly transparent pixels
            }
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            $hex = sprintf('#%02x%02x%02x', $r, $g, $b);
            $colors[$hex] = ($colors[$hex] ?? 0) + 1;
        }

        imagedestroy($img);

        if (empty($colors)) {
            return null;
        }

        arsort($colors);
        $dominant = array_key_first($colors);

        // Skip if the detected color is near-white or near-black
        $r = hexdec(substr($dominant, 1, 2));
        $g = hexdec(substr($dominant, 3, 2));
        $b = hexdec(substr($dominant, 5, 2));
        if ($r > 240 && $g > 240 && $b > 240) {
            return null;
        }
        if ($r < 15 && $g < 15 && $b < 15) {
            return null;
        }

        return $dominant;
    }

    private static function downloadLogo(string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // If it's already a local path, keep it
        if (str_starts_with($url, '/logos/')) {
            return $url;
        }

        // Only allow HTTPS URLs to prevent SSRF
        if (!preg_match('#^https://#i', $url)) {
            return null;
        }

        $logoDir = __DIR__ . '/../public_html/logos';
        if (!is_dir($logoDir)) {
            mkdir($logoDir, 0755, true);
        }

        $hash = sha1($url);
        $filename = $hash . '.webp';
        $dest = $logoDir . '/' . $filename;
        $publicPath = '/logos/' . $filename;

        // Skip download if already cached
        if (file_exists($dest)) {
            return $publicPath;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        $imageData = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || empty($imageData)) {
            return null;
        }

        if (!self::optimizeLogo($imageData, $dest)) {
            return null;
        }

        return $publicPath;
    }

    /**
     * Resize image to max 256x256 and save as WebP.
     */
    public static function optimizeLogo(string $imageData, string $destPath): bool
    {
        $img = @imagecreatefromstring($imageData);
        if (!$img) {
            return false;
        }

        $width = imagesx($img);
        $height = imagesy($img);
        $maxSize = 256;

        if ($width > $maxSize || $height > $maxSize) {
            $ratio = min($maxSize / $width, $maxSize / $height);
            $newWidth = (int) round($width * $ratio);
            $newHeight = (int) round($height * $ratio);

            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagealphablending($resized, false);
            imagesavealpha($resized, true);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($img);
            $img = $resized;
        } else {
            imagealphablending($img, false);
            imagesavealpha($img, true);
        }

        $result = imagewebp($img, $destPath, 85);
        imagedestroy($img);

        return $result;
    }

    private static function randomColor(): string
    {
        $colors = [
            '#ef4444', '#f97316', '#f59e0b', '#eab308',
            '#84cc16', '#22c55e', '#14b8a6', '#06b6d4',
            '#0ea5e9', '#3b82f6', '#6366f1', '#8b5cf6',
            '#a855f7', '#d946ef', '#ec4899', '#f43f5e',
        ];
        return $colors[array_rand($colors)];
    }
}
