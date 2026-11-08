<?php

namespace App\Http\Controllers;

use App\Actions\Backup\ExportUserDataAction;
use App\Actions\Backup\RestoreUserDataAction;
use App\Http\Requests\RestoreBackupRequest;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index(): Response
    {
        $userId = auth()->id();

        $stats = [
            'total_clients' => Client::where('user_id', $userId)->count(),
            'total_appointments' => Appointment::where('user_id', $userId)->count(),
            'total_transactions' => Transaction::where('user_id', $userId)->count(),
            'total_receipts' => Receipt::where('user_id', $userId)->count(),
        ];

        return Inertia::render('Backup/Index', [
            'stats' => $stats,
        ]);
    }

    public function export(ExportUserDataAction $action): StreamedResponse
    {
        $user = auth()->user();
        $payload = $action->execute($user);
        $filename = sprintf('gestagil_backup_%s_%s.json', Str::slug($user->name) ?: 'user', now()->format('Y-m-d_His'));

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function restore(RestoreBackupRequest $request, RestoreUserDataAction $action): RedirectResponse
    {
        $user = auth()->user();
        $content = $request->file('backup_file')->get();
        $payload = json_decode($content, true);

        if (! is_array($payload) || ! isset($payload['gestagil_backup_version'])) {
            return back()->withErrors([
                'backup_file' => 'O arquivo fornecido não é um backup válido do GestAgil.',
            ]);
        }

        try {
            $summary = $action->execute(
                user: $user,
                payload: $payload,
                mode: $request->input('mode'),
                confirmedOverwrite: $request->boolean('confirmed_overwrite')
            );

            $msg = $summary['mode'] === 'replace'
                ? 'Dados sobrescritos com sucesso a partir do backup!'
                : 'Dados mesclados com sucesso!';

            return redirect()->route('backup.index')->with('success', $msg);
        } catch (\Exception $e) {
            return back()->withErrors([
                'backup_file' => 'Erro ao processar backup: '.$e->getMessage(),
            ]);
        }
    }
}
