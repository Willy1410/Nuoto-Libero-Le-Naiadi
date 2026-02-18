<?php
/**
 * Stub adapter per futura integrazione Builder.io.
 *
 * TODO integrazione reale:
 * - leggere key/API endpoint da config o env
 * - fetch contenuti pubblicati da Builder
 * - mapping payload Builder -> componenti frontend
 * - gestione webhook publish/unpublish per invalidazione cache
 */
final class BuilderAdapter
{
    public function fetchPublishedContent(string $model, string $urlPath): array
    {
        return [
            'enabled' => false,
            'source' => 'local-cms',
            'message' => 'Builder adapter non ancora collegato',
            'model' => $model,
            'urlPath' => $urlPath,
        ];
    }

    public function mapBuilderPayload(array $payload): array
    {
        return [
            'status' => 'stub',
            'payload' => $payload,
        ];
    }

    public function handleWebhook(array $payload): array
    {
        return [
            'handled' => false,
            'reason' => 'stub_adapter',
            'received_at' => date('c'),
            'payload' => $payload,
        ];
    }
}
