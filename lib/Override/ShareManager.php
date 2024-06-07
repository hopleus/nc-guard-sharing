<?php

declare(strict_types=1);

namespace OCA\GuardSharing\Override;

use OC\Share20\Manager;
use OCA\GuardSharing\AppInfo\Application;
use OCA\GuardSharing\Service\ShareService;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IManager;
use OCP\Share\IShare;

class ShareManager extends Manager implements IManager
{
    public function getShareByToken($token): IShare
    {
        $share = parent::getShareByToken($token);
        if (\OC_User::isIncognitoMode() === false) {
            return $share;
        }

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
}