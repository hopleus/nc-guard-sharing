<?php

declare(strict_types=1);

namespace OCA\GuardSharing\Override;

use OC\Share20\Manager;
use OCA\GuardSharing\AppInfo\Application;
use OCA\GuardSharing\Service\ShareService;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Share\Exceptions\GenericShareException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;

class ShareManager extends Manager implements IManager
{
    protected IEventDispatcher $dispatcher;
    private ?IShare $share = null;

    public function __construct(...$args)
    {
        foreach ($args as $arg) {
            if ($arg instanceof IEventDispatcher) {
                $this->dispatcher = $arg;
            }
        }

        parent::__construct(...$args);
    }

    public function getShareByToken($token): IShare
    {
        $share = parent::getShareByToken($token);
        $attributes = $share->getAttributes();

        if (empty($attributes)) {
            return $share;
        }

        $isOnlyForAuthUser = $attributes->getAttribute(Application::APP_ID, ShareService::ONLY_FOR_AUTH_USER);
        if (empty($isOnlyForAuthUser)) {
            return $share;
        }

        \OC_User::setIncognitoMode(false);
        $authUser = \OC_User::getUser();

        if (empty($authUser)) {
            throw new ShareNotFound();
        }

        return $share;
    }

    /**
     * Share a path
     *
     * @param IShare $share
     * @return IShare The share object
     * @throws \Exception
     */
    public function createShare(IShare $share): IShare
    {
        $this->share = $share;

        return parent::createShare($share);
    }

    /**
     * Validate if the expiration date fits the system settings
     *
     * @throws GenericShareException
     */
    protected function validateExpirationDateLink(IShare $share): IShare
    {
        $attributes = $share->getAttributes();

        if (empty($attributes)) {
            return $share;
        }

        $isOnlyForAuthUser = $attributes->getAttribute(Application::APP_ID, ShareService::ONLY_FOR_AUTH_USER);
        if (empty($isOnlyForAuthUser)) {
            return $share;
        }

        return parent::validateExpirationDateLink($share);
    }

    /**
     * Verify if a password meets all requirements
     *
     * @param string $password
     * @throws \Exception
     */
    protected function verifyPassword($password): void
    {
        if ($password === null) {
            if (!is_null($this->share)) {
                return;
            }
        }

        parent::verifyPassword($password);
    }
}