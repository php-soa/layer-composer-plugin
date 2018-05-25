<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: roma_bb8
 * Date: 10.05.18
 * Time: 1:03
 */

namespace SOA\Plugins\Models;


use Exception;
use Composer\Json\JsonFile;
use SOA\Interfaces\Singleton;
use SOA\Interfaces\SingletonTrait;
use SOA\Exceptions\LogicException;

/**
 * Class LayerManager
 * @package SOA\Plugins\Models
 */
class LayerManager implements Singleton {
    use SingletonTrait;

    private const QUEUE_NEXT = 1;
    private const QUEUE_BACK = -1;

    private const PHP_EXTENSION = 'php';

    private const LOCAL_PREFIX = 'local.';

    private const APPLICATION_CONFIG_FILE = 'application.json';
    private const PROJECT_CONFIG_FILE = 'project.json';

    //----------------------------------------

    /** @var Layer[] $heap */
    private $heap = array();

    //########################################

    /**
     * @param Layer $current
     * @param Layer $next
     * @return int
     */
    private function compare(Layer $current, Layer $next): int {

        if ($current->getPriority() < $next->getPriority()) {
            return self::QUEUE_NEXT;
        }

        if ($current->getPriority() > $next->getPriority()) {
            return self::QUEUE_BACK;
        }

        $name = array($current->getName(), $next->getName());

        sort($name, SORT_STRING);

        $name = array_shift($name);

        return ($name === $current->getName() ? self::QUEUE_BACK : self::QUEUE_NEXT);
    }

    //########################################

    /**
     * @param array $base
     * @param array $next
     * @return array
     */
    private function merge(array $base, array $next): array {
        return array_merge($base, $next);
    }

    //########################################

    /**
     * @param string $dir
     * @param string $name
     * @return array
     */
    private function getConfig(string $dir, string $name): array {

        if (null === is_dir($dir)) {
            return array();
        }

        if (false === is_file($file = $dir . $name)) {
            return array();
        }

        $local = array();
        $data = (new JsonFile($file))->read();

        if (true === is_file($file = $dir . self::LOCAL_PREFIX . $name)) {
            $local = (new JsonFile($file))->read();
        }

        return $this->merge($data, $local);
    }

    /**
     * @param string $file
     * @return array
     */
    private function getMetadata(string $file): array {

        if (false === is_readable($file)) {
            return array();
        }

        if (self::PHP_EXTENSION === strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            return array();
        }

        return require $file;
    }

    //########################################

    /**
     * @return array
     */
    private function getContent(): array {

        usort($this->heap, array($this, 'compare'));

        $application = array();
        $project = array();
        $metadata = array();

        foreach ($this->heap as $layer) {

            $config = $this->getConfig($layer->getConfigDir(), self::APPLICATION_CONFIG_FILE);
            $application = $this->merge($application, $config);

            $config = $this->getConfig($layer->getConfigDir(), self::PROJECT_CONFIG_FILE);
            $project = $this->merge($project, $config);

            $metadata[$layer->getName()] = $this->getMetadata($layer->getMetadata());
        }

        return array(
            '_readme'     => array(
                'This file locks the layer of SOA project to a known state',
                'Below, in the salt section, you can see in what order the layers',
                'Read more about it at https://phpsoa.github.io/',
                'This file is @generated automatically'
            ),
            'layers'      => $this->heap,
            'application' => $application,
            'project'     => $project,
            'metadata'    => $metadata
        );
    }

    //########################################

    /**
     * @param Layer $layer
     */
    public function registerLayer(Layer $layer): void {
        $this->heap[$layer->getName()] = $layer;
    }

    /**
     * @param Layer $layer
     */
    public function unregisterLayer(Layer $layer): void {

        if (true === empty($this->heap[$layer->getName()])) {
            return;
        }

        unset($this->heap[$layer->getName()]);
    }

    //########################################

    /**
     * @param JsonFile $store
     * @throws Exception
     */
    public function save(JsonFile $store): void {

        $store->write($this->getContent());

        if (false === $store->exists()) {
            throw new LogicException('Could not create a data lock file about layers.');
        }
    }

    /**
     * @param JsonFile $store
     */
    public function load(JsonFile $store): void {

        if (false === $store->exists()) {
            return;
        }

        $data = $store->read();

        if (true === empty($data['layers'])) {
            return;
        }

        foreach ($data['layers'] as $layer) {
            $this->registerLayer(new Layer($layer));
        }
    }
}
