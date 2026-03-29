<?php

namespace App\Helpers;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;

class Logger
{
    /**
     * Log an audit event.
     * 
     * @param string $type The event type (e.g., AUTH_SUCCESS, SEARCH_QUERY)
     * @param string $action A short description of the action
     * @param string|null $module The module affected
     * @param string|null $details Detailed description or old/new values
     * @param array|null $metadata Contextual data (e.g., search filters)
     */
    public static function log(string $type, string $action, ?string $module = null, ?string $details = null, ?array $metadata = null)
    {
        $userId = Current::id();
        $ip = Request::ip();
        $userAgent = Request::userAgent();

        // Hybrid Routing Logic
        $fileLoggedTypes = ['AUTH_FAILURE', 'SEARCH_QUERY', 'DATA_EXPORT'];

        if (in_array($type, $fileLoggedTypes)) {
            // Write to dedicated 'audit' log channel (daily rotating file)
            Log::channel('audit')->info($action, [
                'event_type' => $type,
                'user_id' => $userId,
                'ip_address' => $ip,
                'target_module' => $module,
                'details' => $details,
                'metadata' => $metadata,
                'user_agent' => $userAgent,
            ]);
        } else {
            // Save to database audit_logs table
            AuditLog::create([
                'user_id' => $userId,
                'event_type' => $type,
                'action' => $action,
                'target_module' => $module,
                'ip_address' => $ip,
                'details' => $details,
                'metadata' => $metadata,
                'user_agent' => $userAgent,
            ]);
        }
    }
}
