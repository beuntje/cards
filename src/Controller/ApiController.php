<?php

namespace Cards\Controller;

use Cards\Card;
use Cards\Barcode;

class ApiController
{
    private array $user;

    public function __construct(array $user)
    {
        $this->user = $user;
    }

    public function logoSearch(): void
    {
        header('Content-Type: application/json');
        $query = trim($_GET['q'] ?? '');

        if (!$query || empty($_ENV['LOGO_DEV_SECRET'])) {
            echo json_encode([]);
            return;
        }

        $token = $_ENV['LOGO_DEV_TOKEN'] ?? '';
        $secret = $_ENV['LOGO_DEV_SECRET'];

        $ch = curl_init('https://api.logo.dev/search?q=' . urlencode($query));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $secret],
            CURLOPT_TIMEOUT => 5,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $brands = json_decode($response, true) ?: [];
        $results = [];
        foreach ($brands as $brand) {
            $domain = $brand['domain'] ?? '';
            if (!$domain) continue;
            $results[] = [
                'name' => $brand['name'] ?? $domain,
                'domain' => $domain,
                'logo_url' => 'https://img.logo.dev/' . $domain . '?token=' . $token . '&size=128&format=png',
            ];
        }
        echo json_encode($results);
    }

    public function cards(): void
    {
        header('Content-Type: application/json');
        $cards = Card::all($this->user['id'], [
            'search' => $_GET['search'] ?? '',
            'sort' => $_GET['sort'] ?? 'created_at',
            'favorite' => !empty($_GET['favorite']),
            'latitude' => isset($_GET['lat']) ? (float)$_GET['lat'] : null,
            'longitude' => isset($_GET['lng']) ? (float)$_GET['lng'] : null,
        ]);
        foreach ($cards as &$c) {
            $c['barcode_svg'] = Barcode::render($c['number'], $c['barcode_type']);
        }
        echo json_encode($cards);
    }

    public function logoUpload(): void
    {
        header('Content-Type: application/json');

        if (empty($_FILES['logo']) || $_FILES['logo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['error' => 'No file uploaded']);
            return;
        }

        $file = $_FILES['logo'];

        // Max 2MB
        if ($file['size'] > 2 * 1024 * 1024) {
            echo json_encode(['error' => 'File too large (max 2MB)']);
            return;
        }

        $allowed = ['image/png', 'image/jpeg', 'image/gif', 'image/webp'];
        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, $allowed)) {
            echo json_encode(['error' => 'Invalid file type']);
            return;
        }

        $logoDir = dirname(__DIR__, 2) . '/public_html/logos';
        if (!is_dir($logoDir)) {
            mkdir($logoDir, 0755, true);
        }

        $imageData = file_get_contents($file['tmp_name']);
        $filename = sha1($imageData) . '.webp';
        $dest = $logoDir . '/' . $filename;

        if (!file_exists($dest)) {
            if (!Card::optimizeLogo($imageData, $dest)) {
                echo json_encode(['error' => 'Could not process image']);
                return;
            }
        }

        echo json_encode(['path' => '/logos/' . $filename]);
    }

    public function recordUsage(string $id): void
    {
        $card = Card::find((int)$id, $this->user['id']);
        if (!$card) {
            http_response_code(404);
            echo json_encode(['error' => 'Not found']);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?? [];
        $lat = isset($input['latitude']) ? (float)$input['latitude'] : null;
        $lng = isset($input['longitude']) ? (float)$input['longitude'] : null;
        Card::recordUsage((int)$id, $lat, $lng);

        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
    }
}
