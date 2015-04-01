<?php
namespace ByTorsten\Routing\Dimension;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use TYPO3\Neos\Routing\Exception\NoSuchDimensionValueException;

abstract class AbstractDimensionDecisionMaker implements DimensionDecisionMakerInterface {

    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $dimensionName = NULL;

    /**
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

    /**
     * @param string $path
     * @return void
     */
    public function setPath($path) {
        $this->path = $path;
    }

    /**
     * @param string $dimensionName
     * @return void
     */
    public function setDimensionName($dimensionName) {
        $this->dimensionName = $dimensionName;
    }

    /**
     * @return bool
     */
    protected function isBackend() {
        return !(strpos($this->path, '@') === FALSE);
    }

    /**
     * @return array
     */
    public function getDimensionFromPath() {
        if ($this->dimensionName !== NULL && preg_match('/(?:;|&)' . $this->dimensionName . '=(.+?)(?:;|&|\.html|$)/', $this->path, $matches)) {
            return explode(',', $matches[1]);
        }

        return NULL;
    }

    /**
     * @return array|NULL
     * @throws NoSuchDimensionValueException
     */
    public function getDimension() {
        $dimension = $this->resolveDimension();

        if (is_string($dimension)) {
            $allPresets = $this->contentDimensionPresetSource->getAllPresets();

            if (!isset($allPresets[$this->dimensionName])) {
                throw new NoSuchDimensionValueException(sprintf('Could not find a configuration for content dimension "%s".', $this->dimensionName), 1413389322);
            }
            $presets = $allPresets[$this->dimensionName]['presets'];

            if (!isset($presets[$dimension])) {
                throw new NoSuchDimensionValueException(sprintf('Could not find a preset for content dimension "%s", preset "%s".', $this->dimensionName, $dimension), 1413389322);
            }

            return $presets[$dimension]['values'];
        }

        return NULL;
    }

    /**
     * @return string
     */
    public function getUniqueIdentifier() {
        $dimension = $this->getDimension();
        if (!$dimension) {
            $dimension = array();
        }


        return implode('_', $dimension);
    }

    /**
     * @return string
     */
    abstract function resolveDimension();
}