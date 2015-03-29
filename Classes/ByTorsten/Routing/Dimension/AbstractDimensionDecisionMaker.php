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
     * @return bool
     */
    protected function isBackend() {
        return !(strpos($this->path, '@') === FALSE);
    }

    /**
     * @return array
     */
    protected function getDimensionFromPath() {
        if ($this->dimensionName !== NULL && preg_match('/;' . $this->dimensionName . '=(.+?)(?:;|\.html|$)/', $this->path, $matches)) {
            return array($this->dimensionName => explode(',', $matches[1]));
        }

        return NULL;
    }

    /**
     * @return array|NULL
     * @throws NoSuchDimensionValueException
     */
    public function getDimension() {
        $dimension = $this->getDimensionFromPath();
        if ($dimension) {
            return $dimension;
        }

        $dimension = $this->resolveDimension();

        if (is_string($dimension) && $this->dimensionName !== NULL) {
            $allPresets = $this->contentDimensionPresetSource->getAllPresets();

            if (!isset($allPresets[$this->dimensionName])) {
                throw new NoSuchDimensionValueException(sprintf('Could not find a configuration for content dimension "%s".', $this->dimensionName), 1413389322);
            }
            $presets = $allPresets[$this->dimensionName]['presets'];

            if (!isset($presets[$dimension])) {
                throw new NoSuchDimensionValueException(sprintf('Could not find a preset for content dimension "%s", preset "%s".', $this->dimensionName, $dimension), 1413389322);
            }

            return array($this->dimensionName => $presets[$dimension]['values']);
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

        if ($this->dimensionName && isset($dimension[$this->dimensionName])) {
            return implode('_', $dimension[$this->dimensionName]);
        }

        return serialize($dimension);
    }

    /**
     * @return string
     */
    abstract function resolveDimension();
}