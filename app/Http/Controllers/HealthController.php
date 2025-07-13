<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;
use Exception;

class HealthController extends Controller
{
    /**
     * Check the overall health status of the API
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $health = [
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => '1.0.0',
            'environment' => config('app.env'),
            'checks' => [
                'database' => $this->checkDatabase(),
                'cache' => $this->checkCache(),
                'storage' => $this->checkStorage(),
                'memory' => $this->checkMemory(),
            ]
        ];

        // Determine overall status
        $overallStatus = 'healthy';
        foreach ($health['checks'] as $check) {
            if ($check['status'] === 'unhealthy') {
                $overallStatus = 'unhealthy';
                break;
            }
        }

        $health['status'] = $overallStatus;
        $statusCode = $overallStatus === 'healthy' ? 200 : 503;

        return response()->json($health, $statusCode);
    }

    /**
     * Check database connectivity and performance
     *
     * @return array
     */
    private function checkDatabase(): array
    {
        try {
            $startTime = microtime(true);

            // Test database connection
            DB::connection()->getPdo();

            // Test a simple query
            $result = DB::select('SELECT 1 as test');

            $responseTime = (microtime(true) - $startTime) * 1000; // Convert to milliseconds

            return [
                'status' => 'healthy',
                'connection' => config('database.default'),
                'response_time_ms' => round($responseTime, 2),
                'message' => 'Database connection successful'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'connection' => config('database.default'),
                'error' => $e->getMessage(),
                'message' => 'Database connection failed'
            ];
        }
    }

    /**
     * Check cache system status
     *
     * @return array
     */
    private function checkCache(): array
    {
        try {
            $startTime = microtime(true);

            // Test cache write
            $testKey = 'health_check_' . time();
            $testValue = 'test_value';
            Cache::put($testKey, $testValue, 60);

            // Test cache read
            $retrievedValue = Cache::get($testKey);

            // Clean up
            Cache::forget($testKey);

            $responseTime = (microtime(true) - $startTime) * 1000;

            if ($retrievedValue === $testValue) {
                return [
                    'status' => 'healthy',
                    'driver' => config('cache.default'),
                    'response_time_ms' => round($responseTime, 2),
                    'message' => 'Cache system working correctly'
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'driver' => config('cache.default'),
                    'error' => 'Cache read/write test failed',
                    'message' => 'Cache system not working correctly'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'driver' => config('cache.default'),
                'error' => $e->getMessage(),
                'message' => 'Cache system error'
            ];
        }
    }

    /**
     * Check storage system status
     *
     * @return array
     */
    private function checkStorage(): array
    {
        try {
            $startTime = microtime(true);

            // Test storage write
            $testFile = 'health_check_' . time() . '.txt';
            $testContent = 'health_check_content';

            Storage::put($testFile, $testContent);

            // Test storage read
            $retrievedContent = Storage::get($testFile);

            // Clean up
            Storage::delete($testFile);

            $responseTime = (microtime(true) - $startTime) * 1000;

            if ($retrievedContent === $testContent) {
                return [
                    'status' => 'healthy',
                    'driver' => config('filesystems.default'),
                    'response_time_ms' => round($responseTime, 2),
                    'message' => 'Storage system working correctly'
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'driver' => config('filesystems.default'),
                    'error' => 'Storage read/write test failed',
                    'message' => 'Storage system not working correctly'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'driver' => config('filesystems.default'),
                'error' => $e->getMessage(),
                'message' => 'Storage system error'
            ];
        }
    }

    /**
     * Check memory usage and system resources
     *
     * @return array
     */
    private function checkMemory(): array
    {
        try {
            $memoryLimit = ini_get('memory_limit');
            $memoryUsage = memory_get_usage(true);
            $peakMemoryUsage = memory_get_peak_usage(true);

            // Convert memory limit to bytes for comparison
            $memoryLimitBytes = $this->convertToBytes($memoryLimit);
            $memoryUsagePercent = ($memoryLimitBytes > 0) ? ($memoryUsage / $memoryLimitBytes) * 100 : 0;

            $status = 'healthy';
            if ($memoryUsagePercent > 80) {
                $status = 'warning';
            }

            return [
                'status' => $status,
                'memory_limit' => $memoryLimit,
                'memory_usage' => $this->formatBytes($memoryUsage),
                'peak_memory_usage' => $this->formatBytes($peakMemoryUsage),
                'memory_usage_percent' => round($memoryUsagePercent, 2),
                'message' => 'Memory usage is normal'
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'error' => $e->getMessage(),
                'message' => 'Unable to check memory usage'
            ];
        }
    }

    /**
     * Convert memory string to bytes
     *
     * @param string $memoryString
     * @return int
     */
    private function convertToBytes(string $memoryString): int
    {
        $unit = strtolower(substr($memoryString, -1));
        $value = (int) substr($memoryString, 0, -1);

        switch ($unit) {
            case 'k':
                return $value * 1024;
            case 'm':
                return $value * 1024 * 1024;
            case 'g':
                return $value * 1024 * 1024 * 1024;
            default:
                return $value;
        }
    }

    /**
     * Format bytes to human readable format
     *
     * @param int $bytes
     * @return string
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}