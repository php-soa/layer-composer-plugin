<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: toor
 * Date: 27.04.18
 * Time: 23:25
 */

namespace SOA\Plugins;


use Exception;
use Composer\Composer;
use Composer\Json\JsonFile;
use Composer\IO\IOInterface;
use Composer\Script\ScriptEvents;
use SOA\Plugins\Models\LayerManager;
use SOA\Plugins\Models\LayerFactory;
use Composer\Plugin\PluginInterface;
use Composer\Installer\PackageEvent;
use Composer\Installer\PackageEvents;
use Composer\Package\PackageInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\DependencyResolver\Operation\UpdateOperation;
use Composer\DependencyResolver\Operation\InstallOperation;

/**
 * Class LayerComposerPlugin
 * @package SOA\Plugins
 */
class LayerComposerPlugin implements PluginInterface, EventSubscriberInterface {

    public const LAYERS_LOCK_FILE = 'layers.composer.lock';

    private const PROJECT_TYPE = 'project';
    private const LAYER_TYPE = 'layer';

    private const SUPPORTS_TYPES = array(
        self::PROJECT_TYPE,
        self::LAYER_TYPE
    );

    //----------------------------------------

    /** @var LayerManager $layerManager */
    private $layerManager;

    /** @var LayerFactory $layerFactory */
    private $layerFactory;

    /** @var JsonFile $layerStore */
    private $layerStore;

    /** @var Composer $composer */
    private $composer;

    //########################################

    /**
     * @param PackageEvent $packageEvent
     * @return PackageInterface|null
     */
    private function getSupportPackageByPackageEvent(PackageEvent $packageEvent): ?PackageInterface {

        /** @var InstallOperation|UpdateOperation $operation */
        $operation = $packageEvent->getOperation();
        $package = method_exists($operation, 'getPackage') ? $operation->getPackage() : $operation->getInitialPackage();

        if (false === $this->supports($package->getType())) {
            return null;
        }

        return $package;
    }

    //########################################

    private function registerRootLayer(): void {

        $layer = $this->layerFactory->create($this->composer->getPackage());

        $path = \dirname($this->composer->getConfig()->get('vendor-dir')) . DIRECTORY_SEPARATOR;

        // This fix path to root folder for root layer.
        $vendor = $this->composer->getConfig()->get('vendor-dir') . DIRECTORY_SEPARATOR;
        $delete = $vendor . $this->composer->getPackage()->getName() . DIRECTORY_SEPARATOR;
        $configDir = str_replace($delete, '', $layer->getConfigDir());
        $metadata = str_replace($delete, '', $layer->getMetadata());
        // end.

        $layer->setConfigDir($path . $configDir);
        $layer->setMetadata($path . $metadata);

        $this->layerManager->registerLayer($layer);
    }

    //########################################

    /**
     * @param Composer $composer
     * @param IOInterface $io
     */
    public function activate(Composer $composer, IOInterface $io): void {

        $this->layerFactory = new LayerFactory($composer->getInstallationManager());
        $this->layerManager = LayerManager::getInstance();
        $this->composer = $composer;

        $path = \dirname($this->composer->getConfig()->get('vendor-dir')) . DIRECTORY_SEPARATOR;
        $this->layerStore = new JsonFile($path . self::LAYERS_LOCK_FILE);
        $this->layerManager->load($this->layerStore);
    }

    /**
     * @throws Exception
     */
    private function inactivate(): void {
        $this->layerManager->save($this->layerStore);
    }

    //########################################

    /**
     * @param PackageEvent $packageEvent
     */
    public function postPackageInstall(PackageEvent $packageEvent): void {

        if (null === $package = $this->getSupportPackageByPackageEvent($packageEvent)) {
            return;
        }

        $layer = $this->layerFactory->create($package);

        $this->layerManager->registerLayer($layer);
    }

    /**
     * @param PackageEvent $packageEvent
     */
    public function postPackageUninstall(PackageEvent $packageEvent): void {

        if (null === $package = $this->getSupportPackageByPackageEvent($packageEvent)) {
            return;
        }

        $layer = $this->layerFactory->create($package);

        $this->layerManager->unRegisterLayer($layer);
    }

    //########################################

    /**
     * @throws Exception
     */
    public function postInstallCmd(): void {
        $this->registerRootLayer();
        $this->inactivate();
    }

    //########################################

    /**
     * @param string $type
     * @return bool
     */
    public function supports(string $type): bool {
        return \in_array($type, self::SUPPORTS_TYPES, true);
    }

    //########################################

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array {
        return array(
            PackageEvents::POST_PACKAGE_UNINSTALL => array('postPackageUninstall'),
            PackageEvents::POST_PACKAGE_INSTALL   => array('postPackageInstall'),
            PackageEvents::POST_PACKAGE_UPDATE    => array('postPackageInstall'),
            ScriptEvents::POST_INSTALL_CMD        => array('postInstallCmd'),
            ScriptEvents::POST_UPDATE_CMD         => array('postInstallCmd')
        );
    }
}
