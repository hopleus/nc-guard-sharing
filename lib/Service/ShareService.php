<?php

declare(strict_types=1);

namespace OCA\GuardSharing\Service;

use OC\User\NoUserException;
use OCA\GuardSharing\AppInfo\Application;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\Constants;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IL10N;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Share\Exceptions\GenericShareException;
use OCP\Share\IManager;
use OCP\Share\IShare;
use Psr\Log\LoggerInterface;

class ShareService
{
    public const ONLY_FOR_AUTH_USER = "only_for_auth_user";

    /** @var Node */
    private Node $lockedNode;

    /** @var string|null */
    private ?string $currentUserId = null;


    public function __construct(
        private LoggerInterface $logger,
        private IManager        $shareManager,
        private IRootFolder     $rootFolder,
        private IL10N           $l10n,
    )
    {
    }

    /**
     * @param string $path
     * @param int $shareType
     * @param string $userId
     * @return array
     *
     * @throws NoUserException
     * @throws NotPermittedException
     * @throws OCSBadRequestException
     * @throws OCSException
     * @throws OCSForbiddenException
     * @throws OCSNotFoundException
     * @throws LockedException
     */
    public function create(
        string $path,
        int    $shareType,
        string $userId,
    ): array
    {
        if ($this->currentUserId !== $userId) {
            $this->currentUserId = $userId;
        }

        if ($shareType != IShare::TYPE_LINK) {
            throw new OCSBadRequestException($this->l10n->t('Invalid share type'));
        }

        if (!$this->shareManager->shareApiAllowLinks()) {
            throw new OCSNotFoundException($this->l10n->t('Public link sharing is disabled by the administrator'));
        }

        $userFolder = $this->rootFolder->getUserFolder($this->currentUserId);
        try {
            $node = $userFolder->get($path);
        } catch (NotFoundException $e) {
            throw new OCSNotFoundException($this->l10n->t('Wrong path, file/folder does not exist'));
        }

        $share = $this->shareManager->newShare();
        $share->setNode($node);

        try {
            $this->lock($share->getNode());
        } catch (NotFoundException|LockedException $e) {
            throw new OCSNotFoundException($this->l10n->t('Could not create share'));
        }

        $attributes = $share->newAttributes();
        $attributes->setAttribute(Application::APP_ID, self::ONLY_FOR_AUTH_USER, true);
        $share->setAttributes($attributes);

        $permissions = Constants::PERMISSION_READ;
        if (($permissions & Constants::PERMISSION_READ) && $this->shareManager->outgoingServer2ServerSharesAllowed()) {
            $permissions |= Constants::PERMISSION_SHARE;
        }
        $share->setPermissions($permissions);

        $share->setShareType($shareType);
        $share->setSharedBy($this->currentUserId);

        // Create share in the database
        try {
            $share = $this->shareManager->createShare($share);
        } catch (GenericShareException $e) {
            $this->logger->warning('Error creating share: ' . $e->getMessage(), ['trace' => $e->getTrace()]);
            $code = $e->getCode() === 0 ? 403 : $e->getCode();
            throw new OCSException($e->getHint(), $code);
        } catch (\Exception $e) {
            $this->logger->warning('Error creating share: ' . $e->getMessage(), ['trace' => $e->getTrace()]);
            throw new OCSForbiddenException($e->getMessage(), $e);
        }

        $this->lockedNode->unlock(ILockingProvider::LOCK_SHARED);

        return $this->serializeShare($share);
    }

    /**
     * Lock a Node
     *
     * @param Node $node
     * @throws LockedException
     */
    private function lock(Node $node): void
    {
        $node->lock(ILockingProvider::LOCK_SHARED);
        $this->lockedNode = $node;
    }

    /**
     * @param IShare $share
     * @return array
     */
    private function serializeShare(IShare $share): array {
        return [
            'id' => $share->getId(),
            'share_type' => $share->getShareType(),
            'uid_owner' => $share->getSharedBy(),
            'displayname_owner' => $share->getSharedBy(),
            // recipient permissions
            'permissions' => $share->getPermissions(),
            // current user permissions on this share
            'stime' => $share->getShareTime()->getTimestamp(),
            'parent' => null,
            'expiration' => null,
            'token' => $share->getToken(),
            'uid_file_owner' => $share->getShareOwner(),
            'note' => $share->getNote(),
            'label' => $share->getLabel(),
            'displayname_file_owner' => $share->getShareOwner(),
        ];
    }
}