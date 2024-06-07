<?php

declare(strict_types=1);

namespace OCA\GuardSharing\Controller;

use OCA\GuardSharing\AppInfo\Application;
use OCA\GuardSharing\Service\ShareService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

/**
 * @package OCA\GuardSharing\Controllers
 */
class ShareController extends Controller
{
    use Errors;

    public function __construct(
        IRequest             $request,
        private ShareService $service,
        private string       $userId
    )
    {
        parent::__construct(Application::APP_ID, $request);
    }

    /**
     * @NoAdminRequired
     *
     * @param string $path
     * @param int $shareType
     * @return DataResponse
     */
    public function createShare(
        string $path,
        int    $shareType
    ): DataResponse
    {
        return $this->handleException(function () use ($path, $shareType) {
            return $this->service->create($path, $shareType, $this->userId);
        });
    }
}