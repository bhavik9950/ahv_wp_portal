<?php

declare(strict_types=1);

namespace App\Services\Audit;

use App\Models\AuditLog;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Records security-sensitive actions. Metadata is filtered to a safe allowlist
 * of scalar values — never store tokens, secrets, or raw request bodies.
 */
final class AuditLogger
{
    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation', 'access_token', 'app_secret',
        'token', 'secret', 'webhook_verify_token', 'authorization',
    ];

    public function __construct(
        private readonly TenantContext $tenant,
        private readonly Request $request,
    ) {}

    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(string $action, ?Model $subject = null, array $metadata = [], string $result = 'success'): AuditLog
    {
        return AuditLog::create([
            'organization_id' => $this->tenant->id(),
            'user_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $subject ? $subject->getMorphClass() : null,
            'auditable_id' => $subject?->getKey(),
            'result' => $result,
            'ip' => $this->request->ip(),
            'user_agent' => substr((string) $this->request->userAgent(), 0, 500),
            'metadata' => $this->sanitize($metadata),
        ]);
    }

    public function failure(string $action, ?Model $subject = null, array $metadata = []): AuditLog
    {
        return $this->log($action, $subject, $metadata, 'failure');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitize(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (in_array(strtolower((string) $key), self::SENSITIVE_KEYS, true)) {
                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitize($value);
            } elseif (is_scalar($value) || $value === null) {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }
}
