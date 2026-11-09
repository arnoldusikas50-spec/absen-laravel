<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;

/**
 * CbtAuthTrait - Common authentication methods for CBT controllers
 *
 * Reduces code duplication across CBT-related controllers.
 *
 * Port dari CodeIgniter 4 ke Laravel 13. Host controller wajib menyediakan:
 *   - properti $authModel dan $dashboardModel
 *     (App\Models\AuthModel dan App\Models\DashboardModel)
 *   - method isLoggedIn(), inGroup(string $group), getUserId()
 * Respons JSON CI4 diganti Illuminate\Http\JsonResponse.
 */
trait CbtAuthTrait
{
    protected $currentUser;
    protected $currentSiswa;
    protected $currentTp;
    protected $currentSmt;

    /**
     * Check if user is logged in as siswa
     * Returns error response if not authenticated
     */
    protected function requireSiswaAuth(): ?object
    {
        if (!$this->isLoggedIn() || !$this->inGroup('siswa')) {
            return null;
        }

        $this->loadCurrentContext();
        return $this->currentSiswa;
    }

    /**
     * Load current user context (user, siswa, tp, smt)
     * Caches result for the request lifetime
     */
    protected function loadCurrentContext(): void
    {
        if ($this->currentUser !== null) {
            return; // Already loaded
        }

        $this->currentUser = $this->authModel->user($this->getUserId());

        if (!$this->currentUser) {
            return;
        }

        $this->currentTp = $this->dashboardModel->getTahunActive();
        $this->currentSmt = $this->dashboardModel->getSemesterActive();

        $this->currentSiswa = $this->dashboardModel->getDataSiswa(
            $this->currentUser->username,
            $this->currentTp->id_tp,
            $this->currentSmt->id_smt
        );
    }

    /**
     * Return JSON error response
     */
    protected function jsonError(string $message, int $code = 400): JsonResponse
    {
        return response()->json([
            'status' => false,
            'success' => false,
            'message' => $message
        ], $code);
    }

    /**
     * Return JSON success response
     */
    protected function jsonSuccess($data = null, string $message = 'Success'): JsonResponse
    {
        $response = [
            'status' => true,
            'success' => true,
            'message' => $message
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        return response()->json($response);
    }

    /**
     * Validate AJAX request
     */
    protected function requireAjax(): bool
    {
        return request()->ajax();
    }

    /**
     * Get POST parameter with default
     */
    protected function postParam(string $key, $default = null)
    {
        return request()->input($key, $default);
    }

    /**
     * Get GET parameter with default
     */
    protected function getParam(string $key, $default = null)
    {
        return request()->query($key, $default);
    }

    /**
     * Validate required POST parameters
     * Returns array of missing params, or empty if all present
     */
    protected function validateRequired(array $params): array
    {
        $missing = [];
        foreach ($params as $param) {
            $value = request()->input($param);
            if ($value === null || $value === '') {
                $missing[] = $param;
            }
        }
        return $missing;
    }
}
