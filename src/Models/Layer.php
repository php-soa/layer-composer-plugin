<?php declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: roma_bb8
 * Date: 09.05.18
 * Time: 22:15
 */

namespace SOA\Plugins\Models;


use JsonSerializable;

/**
 * Class Layer
 * @package SOA\Plugins\Models
 */
class Layer implements JsonSerializable {

    /** @var string $name */
    private $name;

    /** @var float $priority */
    private $priority;

    /** @var string $configDir */
    private $configDir;

    /** @var string $metadata */
    private $metadata;

    //----------------------------------------

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void {
        $this->name = $name;
    }

    /**
     * @return float
     */
    public function getPriority(): float {
        return $this->priority;
    }

    /**
     * @param float $priority
     */
    public function setPriority(float $priority): void {
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getConfigDir(): string {
        return $this->configDir;
    }

    /**
     * @param string $configDir
     */
    public function setConfigDir(string $configDir): void {
        $this->configDir = $configDir;
    }

    /**
     * @return string
     */
    public function getMetadata(): string {
        return $this->metadata;
    }

    /**
     * @param string $metadata
     */
    public function setMetadata(string $metadata): void {
        $this->metadata = $metadata;
    }

    //########################################

    /**
     * {@inheritdoc}
     */
    public function jsonSerialize(): array {
        return array(
            'name'       => $this->getName(),
            'priority'   => $this->getPriority(),
            'config-dir' => $this->getConfigDir(),
            'metadata'   => $this->getMetadata()
        );
    }

    //########################################

    /**
     * Layer constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = array()) {

        if (true === empty($data)) {
            return;
        }

        array_key_exists('name', $data) && $this->setName($data['name']);
        array_key_exists('priority', $data) && $this->setPriority($data['priority']);
        array_key_exists('config-dir', $data) && $this->setConfigDir($data['config-dir']);
        array_key_exists('metadata', $data) && $this->setMetadata($data['metadata']);
    }
}
