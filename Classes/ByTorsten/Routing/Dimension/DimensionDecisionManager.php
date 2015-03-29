<?php
namespace ByTorsten\Routing\Dimension;

use TYPO3\Flow\Annotations as Flow;
use ByTorsten\Routing\Dimension\Exception\DecisionMakerNotFoundException;
use TYPO3\Flow\Http\Request;

/**
 * @Flow\Scope("singleton")
 */
class DimensionDecisionManager {

    /**
     * @var \TYPO3\Flow\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var array<AbstractDimensionDecisionMaker>
     */
    protected $decisionMakers = array();

    /**
     * Constructor.
     *
     * @param \TYPO3\Flow\Object\ObjectManagerInterface $objectManager
     */
    public function __construct(\TYPO3\Flow\Object\ObjectManagerInterface $objectManager) {
        $this->objectManager = $objectManager;
    }

    /**
     * @param array $settings
     */
    public function injectSettings(array $settings) {
        $this->createDimensionDecisionMakers($settings['dimension']['dimensionDecisionMakers']);
    }

    /**
     * @return array
     */
    public function getDecisionMakers() {
        return $this->decisionMakers;
    }

    /**
     * @param array $decisionMakerClassNames
     * @return void
     * @throws DecisionMakerNotFoundException
     * @throws Exception\Exception
     */
    protected function createDimensionDecisionMakers($decisionMakerClassNames) {
        foreach($decisionMakerClassNames as $dimensionName => $decisionMakerClassName) {
            if (!$this->objectManager->isRegistered($decisionMakerClassName)) {
                throw new DecisionMakerNotFoundException('No decision maker of type ' . $decisionMakerClassName . ' found!', 1222267935);
            }

            $decisionMaker = $this->objectManager->get($decisionMakerClassName);
            if (!($decisionMaker instanceof AbstractDimensionDecisionMaker)) {
                throw new DecisionMakerNotFoundException('The found decision maker class did not extend \ByTorsten\Routing\Dimension\AbstractDimensionDecisionMaker', 1222268009);
            }

            $decisionMaker->setDimensionName($dimensionName);

            if (isset($this->decisionMakers[$dimensionName])) {
                throw new Exception\Exception('Decision Maker for dimension"%s already registered: %s.', $dimensionName, $decisionMakerClassName, 1222268010);
            }

            $this->decisionMakers[$dimensionName] = $decisionMaker;
        }
    }

    /**
     * @param string $requestPath
     * @return array|NULL
     */
    public function decide($requestPath) {
        $dimensions = array();
        /** @var AbstractDimensionDecisionMaker $decisionMaker */
        foreach($this->decisionMakers as $dimensionName => $decisionMaker) {
            $decisionMaker->setPath($requestPath);
            $dimension = $decisionMaker->getDimension($requestPath);

            if ($dimension !== NULL) {
                $dimensions[$dimensionName] = $dimension;
            }
        }

        return count($dimensions) > 0 ? $dimensions : NULL;
    }

    /**
     * @param Request $httpRequest
     * @return string|null
     */
    public function identify(Request $httpRequest) {
        $requestPath = $httpRequest->getRelativePath();
        $identities = array();

        /** @var AbstractDimensionDecisionMaker $decisionMaker */
        foreach($this->decisionMakers as $dimensionName => $decisionMaker) {
            $decisionMaker->setPath($requestPath);
            $identity = $decisionMaker->getUniqueIdentifier();

            if ($identity !== NULL) {
                $identities[] = sprintf('%s_%s', $dimensionName, $identity);
            }
        }

        return count($identities) > 0 ? implode('_', $identities) : NULL;
    }
}