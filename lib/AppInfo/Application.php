<?php

declare(strict_types=1);

namespace OCA\GuardSharing\AppInfo;

use OC\AppFramework\Middleware\SessionMiddleware;
use OC\KnownUser\KnownUserService;
use OC\Share20\ProviderFactory;
use OC\Share20\ShareDisableChecker;
use OCA\Files\Event\LoadAdditionalScriptsEvent;
use OCA\GuardSharing\Override\ShareManager;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use OCP\Share\IManager;
use OCP\Share\IProviderFactory;
use OCP\Util;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;

class Application extends App implements IBootstrap
{
    public const APP_ID = 'nc-guard-sharing';

    public function __construct(array $urlParams = [])
    {
        parent::__construct(self::APP_ID, $urlParams);
    }

    public function register(IRegistrationContext $context): void
    {
        $context->registerMiddleware(SessionMiddleware::class);
        \OC::$server->registerService(IManager::class, function (ContainerInterface $c) {
            $config = $c->get(IConfig::class);
            $factoryClass = $config->getSystemValue('sharing.managerFactory', ProviderFactory::class);
            /** @var IProviderFactory $factory */
            $factory = new $factoryClass(\OC::$server);

            return new ShareManager(
                $c->get(LoggerInterface::class),
                $c->get(IConfig::class),
                $c->get(ISecureRandom::class),
                $c->get(IHasher::class),
                $c->get(IMountManager::class),
                $c->get(IGroupManager::class),
                $c->getL10N('lib'),
                $c->get(IFactory::class),
                $factory,
                $c->get(IUserManager::class),
                $c->get(IRootFolder::class),
                $c->get(IMailer::class),
                $c->get(IURLGenerator::class),
                $c->get('ThemingDefaults'),
                $c->get(IEventDispatcher::class),
                $c->get(IUserSession::class),
                $c->get(KnownUserService::class),
                $c->get(ShareDisableChecker::class)
            );
        });
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function boot(IBootContext $context): void
    {
        /* @var IEventDispatcher $appEventDispatcher */
        $appEventDispatcher = $context->getAppContainer()->get(IEventDispatcher::class);

        /**
         * Load scripts that mount Vue components
         */
        $appEventDispatcher->addListener(LoadAdditionalScriptsEvent::class, function () {
            Util::addScript(self::APP_ID, 'nc-guard-sharing-main');
        });
    }
}
