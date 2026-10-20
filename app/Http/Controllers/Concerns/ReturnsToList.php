<?php

declare(strict_types=1);

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Keeps people where they were in a list: after an action they go back to the same page, search and filters,
 * and a page that no longer exists (the last item on it was deleted) becomes the last page that does.
 */
trait ReturnsToList
{
    /**
     * Back to the list page the action was sent from (with its ?page=, ?search=, filters), or to the list itself
     * when the action came from somewhere else.
     */
    protected function backToList(string $route, array $parameters = []): RedirectResponse
    {
        $list = route($route, $parameters);
        $previous = url()->previous();
        $samePage = parse_url($previous, PHP_URL_HOST) === parse_url($list, PHP_URL_HOST)
            && rtrim((string) parse_url($previous, PHP_URL_PATH), '/') === rtrim((string) parse_url($list, PHP_URL_PATH), '/');

        return redirect()->to($samePage ? $previous : $list);
    }

    /** A redirect to the last page when the requested page is past the end of the list, otherwise null */
    protected function pageOutOfRange(LengthAwarePaginator $paginator): ?RedirectResponse
    {
        if ($paginator->currentPage() <= 1 || $paginator->currentPage() <= $paginator->lastPage()) {
            return null;
        }

        $query = request()->query();
        unset($query['page']);
        if ($paginator->lastPage() > 1) {
            $query = ['page' => $paginator->lastPage()] + $query;
        }

        return redirect()->to(request()->url().($query ? '?'.http_build_query($query) : ''));
    }
}
