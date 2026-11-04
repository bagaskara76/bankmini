<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use RuntimeException;

class DeploymentController extends Controller
{
    public function migrate(Request $request): JsonResponse
    {
        $expectedSecret = (string) config('app.deploy_secret');
        $providedSecret = $request->bearerToken();

        if (strlen($expectedSecret) < 32) {
            return response()->json([
                'status' => 'error',
                'message' => 'Endpoint deploy belum dikonfigurasi.',
            ], 503);
        }

        if ($providedSecret === null || ! hash_equals($expectedSecret, $providedSecret)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak.',
            ], 403);
        }

        $commands = [
            'migrate' => ['--force' => true],
            'storage:link' => ['--force' => true],
            'optimize:clear' => [],
        ];

        foreach ($commands as $command => $parameters) {
            if (Artisan::call($command, $parameters) !== 0) {
                throw new RuntimeException("Perintah deploy {$command} gagal.");
            }

            if ($command === 'storage:link') {
                foreach (array_keys(config('filesystems.links', [])) as $link) {
                    if (! is_link($link)) {
                        throw new RuntimeException("Symbolic link {$link} tidak berhasil dibuat.");
                    }
                }
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Migrasi dan persiapan aplikasi berhasil.',
        ]);
    }
}
