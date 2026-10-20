<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Gss\Import\GssImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Admin\Enums\UserRole;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Import an updated GSS workbook from the Reports page (Super Admin only), in two steps: the uploaded file is first
 * checked with a dry run that saves nothing, then imported when the Super Admin confirms.
 */
class GssImportController extends Controller
{
    private const UPLOADS = 'imports/uploads';

    public function check(Request $request, GssImporter $importer): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);
        $data = $request->validate([
            'workbook' => ['required', 'file', 'mimes:xlsx', 'max:30720'],
            'year' => ['required', 'integer', 'between:2020,2100'],
        ], [
            'workbook.mimes' => 'Choose the GSS workbook saved as an Excel .xlsx file.',
            'workbook.max' => 'The workbook is larger than 30 MB.',
        ]);

        $this->clearOldUploads();
        $token = (string) Str::uuid();
        $request->file('workbook')->storeAs(self::UPLOADS, $token.'.xlsx', 'local');

        $result = $this->run($importer, $token, (int) $data['year'], commit: false);
        $request->session()->put('gss_import', $result + [
            'token' => $token,
            'year' => (int) $data['year'],
            'file_name' => Str::limit($request->file('workbook')->getClientOriginalName(), 120),
        ]);

        return redirect()->route('reports.gss-import.show');
    }

    public function show(Request $request): View|RedirectResponse
    {
        $this->authorizeSuperAdmin($request);
        $import = $request->session()->get('gss_import');
        if (! $import) {
            return redirect()->route('reports.index');
        }

        return view('reports.gss-import', ['import' => $import]);
    }

    public function commit(Request $request, GssImporter $importer): RedirectResponse
    {
        $this->authorizeSuperAdmin($request);
        $import = $request->session()->get('gss_import');
        $token = (string) $request->input('token');
        if (! $import || ($import['committed'] ?? false) || ! hash_equals((string) $import['token'], $token) || ! is_file($this->path($token))) {
            return redirect()->route('reports.index')->withErrors(['gss_import' => 'That check has expired. Upload the workbook again.']);
        }

        $result = $this->run($importer, $token, (int) $import['year'], commit: true);
        @unlink($this->path($token));
        $request->session()->put('gss_import', $result + ['token' => $token, 'year' => $import['year'], 'file_name' => $import['file_name']]);

        return redirect()->route('reports.gss-import.show');
    }

    /** The review list of the last check or import */
    public function review(Request $request, string $file): BinaryFileResponse
    {
        $this->authorizeSuperAdmin($request);
        abort_unless(preg_match('/^gss\d{4}-review(-dry-run)?\.xlsx$/', $file) === 1, 404);
        $path = storage_path('app/imports/'.$file);
        abort_unless(is_file($path), 404);

        return response()->download($path, $file);
    }

    private function run(GssImporter $importer, string $token, int $year, bool $commit): array
    {
        // The whole workbook takes several seconds to read
        @set_time_limit(600);
        @ini_set('memory_limit', '1G');
        $result = $importer->run($this->path($token), $year, $commit);
        $result['review_file'] = basename($result['review_file']);

        return $result;
    }

    private function path(string $token): string
    {
        abort_unless(Str::isUuid($token), 404);

        return storage_path('app/private/'.self::UPLOADS.'/'.$token.'.xlsx');
    }

    /** Uploaded workbooks are only kept until they are imported; leftovers go after a day */
    private function clearOldUploads(): void
    {
        foreach (glob(storage_path('app/private/'.self::UPLOADS.'/*.xlsx')) ?: [] as $file) {
            if (filemtime($file) < time() - 86400) {
                @unlink($file);
            }
        }
    }

    private function authorizeSuperAdmin(Request $request): void
    {
        abort_unless($request->user()?->role === UserRole::SUPER_ADMIN, 403);
        abort_unless(Schema::hasTable('import_records'), 503, 'The database update for GSS imports has not been run yet.');
    }
}
