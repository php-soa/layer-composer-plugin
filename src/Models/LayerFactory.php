<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: roma_bb8
 * Date: 13.05.18
 * Time: 13:36
 */

namespace SOA\Plugins\Models;


use SOA\Notifications\Warning;
use Composer\Package\PackageInterface;
use Composer\Installer\InstallationManager;

/**
 * Class LayerFactory
 * @package SOA\Plugins\Models
 */
class LayerFactory {

    private const LAYER_INFO_KEY = 'layer-info';

    public const PRIORITY_DEFAULT = 0.5;

    //----------------------------------------

    /** @var InstallationManager $installationManager */
    private $installationManager;

    //########################################

    /**
     * @param PackageInterface $package
     * @return array
     */
    private function getSourceLayerByPackage(PackageInterface $package): array {

        $extra = $package->getExtra();

        if (false === empty($extra[self::LAYER_INFO_KEY])) {
            return $extra[self::LAYER_INFO_KEY];
        }

        Warning::throw(sprintf('Information in %s about the layer is not specified.', $package->getName()));

        return array();
    }

    //########################################

    /**
     * @param PackageInterface $package
     * @return Layer
     */
    public function create(PackageInterface $package): Layer {

        $layerSource = $this->getSourceLayerByPackage($package);

        if (false === array_key_exists('name', $layerSource)) {

            Warning::throw(sprintf('Name layer in %s empty use %s.', $package->getName(), $package->getName()));

            $layerSource['name'] = $package->getName();
        }

        if (false === array_key_exists('priority', $layerSource)) {

            Warning::throw(sprintf('Priority layer in %s empty use %f.', $package->getName(), self::PRIORITY_DEFAULT));

            $layerSource['priority'] = self::PRIORITY_DEFAULT;
        }

        $path = $this->installationManager->getInstallPath($package) . DIRECTORY_SEPARATOR;

        if (false === array_key_exists('config-dir', $layerSource)) {
            Warning::throw(sprintf('Config dir layer in %s empty.', $package->getName()));
        } else {

            if (0 === strpos($layerSource['config-dir'], DIRECTORY_SEPARATOR)) {
                $layerSource['config-dir'] = substr($layerSource['config-dir'], 1);
            }

            $len = (\strlen($layerSource['config-dir']) - 1);

            if (DIRECTORY_SEPARATOR === $layerSource['config-dir'][$len]) {
                $layerSource['config-dir'] = substr($layerSource['config-dir'], 0, $len);
            }

            $layerSource['config-dir'] = $path . $layerSource['config-dir'] . DIRECTORY_SEPARATOR;
        }

        if (false === array_key_exists('metadata', $layerSource)) {
            Warning::throw(sprintf('Metadata layer in %s empty.', $package->getName()));
        } else {

            if (0 === strpos($layerSource['metadata'], DIRECTORY_SEPARATOR)) {
                $layerSource['metadata'] = substr($layerSource['metadata'], 1);
            }

            $layerSource['metadata'] = $path . $layerSource['metadata'];
        }

        return new Layer($layerSource);
    }

    //########################################

    /**
     * Layer constructor.
     *
     * @param InstallationManager $installationManager
     */
    public function __construct(InstallationManager $installationManager) {
        $this->installationManager = $installationManager;
    }
}
