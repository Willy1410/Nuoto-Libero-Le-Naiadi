<?php
declare(strict_types=1);

/**
 * Adapter base Builder.io:
 * - usa API key da env
 * - legge contenuto pubblicato per model + urlPath
 * - espone mapping minimo e webhook metadata
 */
final class BuilderAdapter
{
    private string $apiKey;
    private string $cdnBaseUrl;

    public function __construct()
    {
        $this->apiKey = trim((string)(getenv('BUILDER_API_KEY') ?: getenv('BUILDER_PUBLIC_API_KEY') ?: ''));
        $cdn = trim((string)(getenv('BUILDER_CDN_BASE_URL') ?: 'https://cdn.builder.io'));
        $this->cdnBaseUrl = rtrim($cdn, '/');
    }

    public function fetchPublishedContent(string $model, string $urlPath): array
    {
        $safeModel = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($model)) ?: 'page';
        $safePath = trim($urlPath);
        if ($safePath === '') {
            $safePath = '/';
        }
        if ($safePath[0] !== '/') {
            $safePath = '/' . $safePath;
        }

        if ($this->apiKey === '') {
            return [
                'enabled' => false,
                'source' => 'builder.io',
                'message' => 'BUILDER_API_KEY non configurata',
                'model' => $safeModel,
                'urlPath' => $safePath,
            ];
        }

        $query = http_build_query([
            'apiKey' => $this->apiKey,
            'userAttributes.urlPath' => $safePath,
            'limit' => 1,
            'includeRefs' => 'true',
            'cachebust' => 'false',
        ]);
        $endpoint = $this->cdnBaseUrl . '/api/v2/content/' . rawurlencode($safeModel) . '?' . $query;

        $response = $this->httpGetJson($endpoint);
        if ($response === null) {
            return [
                'enabled' => true,
                'source' => 'builder.io',
                'message' => 'Richiesta Builder non riuscita',
                'model' => $safeModel,
                'urlPath' => $safePath,
            ];
        }

        $results = is_array($response['results'] ?? null) ? $response['results'] : [];
        $first = $results[0] ?? null;
        if (!is_array($first)) {
            return [
                'enabled' => true,
                'source' => 'builder.io',
                'message' => 'Nessun contenuto pubblicato per il path richiesto',
                'model' => $safeModel,
                'urlPath' => $safePath,
                'content_found' => false,
            ];
        }

        return [
            'enabled' => true,
            'source' => 'builder.io',
            'message' => 'Contenuto Builder caricato',
            'model' => $safeModel,
            'urlPath' => $safePath,
            'content_found' => true,
            'content_id' => (string)($first['id'] ?? ''),
            'published' => (bool)($first['published'] ?? true),
            'payload' => $first,
            'mapped' => $this->mapBuilderPayload($first),
        ];
    }

    public function mapBuilderPayload(array $payload): array
    {
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];
        $title = '';
        if (isset($data['title']) && is_scalar($data['title'])) {
            $title = trim((string)$data['title']);
        } elseif (isset($payload['name']) && is_scalar($payload['name'])) {
            $title = trim((string)$payload['name']);
        }

        $blocks = is_array($data['blocks'] ?? null) ? $data['blocks'] : [];
        return [
            'status' => 'ok',
            'title' => $title,
            'blocks_count' => count($blocks),
            'has_blocks' => count($blocks) > 0,
            'last_updated' => (string)($payload['lastUpdated'] ?? ''),
        ];
    }

    public function handleWebhook(array $payload): array
    {
        $operation = (string)($payload['operation'] ?? $payload['event'] ?? 'unknown');
        $modelName = (string)($payload['modelName'] ?? $payload['model'] ?? '');
        $entryId = (string)($payload['id'] ?? $payload['entryId'] ?? '');

        return [
            'handled' => true,
            'source' => 'builder.io',
            'operation' => $operation,
            'model' => $modelName,
            'entry_id' => $entryId,
            'received_at' => date('c'),
            'cache_invalidation_hint' => $modelName !== '' ? ('cms:' . strtolower($modelName)) : 'cms:generic',
        ];
    }

    private function httpGetJson(string $url): ?array
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => "Accept: application/json\r\nUser-Agent: NuotoLiberoCMS/1.0\r\n",
            ],
        ]);

        $result = @file_get_contents($url, false, $context);
        if (!is_string($result) || trim($result) === '') {
            return null;
        }

        $decoded = json_decode($result, true);
        return is_array($decoded) ? $decoded : null;
    }
}

