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
     * @var DimensionDecisionMakerInterface
     */
    protected $decisionMaker;

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
        $decisionMakerClassName = $settings['dimension']['dimensionDecisionMaker'];
        if ($decisionMakerClassName) {
            $this->createDimensionDecisionMaker($settings['dimension']['dimensionDecisionMaker']);
        }
    }

    /**
     * @param string $decisionMakerClassName
     * @return void
     * @throws DecisionMakerNotFoundException
     */
    protected function createDimensionDecisionMaker($decisionMakerClassName) {
        if (!$this->objectManager->isRegistered($decisionMakerClassName)) {
            throw new DecisionMakerNotFoundException('No decision maker of type ' . $decisionMakerClassName . ' found!', 1222267935);
        }

        $decisionMaker = $this->objectManager->get($decisionMakerClassName);
        if (!($decisionMaker instanceof DimensionDecisionMakerInterface)) {
            throw new DecisionMakerNotFoundException('The found decision maker class did not implement \ByTorsten\Routing\Dimension\DimensionDecisionMakerInterface', 1222268009);
        }

        $this->decisionMaker = $decisionMaker;
    }

    /**
     * @param string $requestPath
     * @return array|NULL
     */
    public function decide($requestPath) {
        if ($this->decisionMaker) {
            if (method_exists($this->decisionMaker, 'setPath')) {
                $this->decisionMaker->setPath($requestPath);
            }
            return $this->decisionMaker->getDimension($requestPath);
        }

        return NULL;
    }

    /**
     * @param Request $httpRequest
     * @return string|null
     */
    public function identify(Request $httpRequest) {
        if (strpos($httpRequest->getRelativePath(), '@') === FALSE &&  $this->decisionMaker) {
            if (method_exists($this->decisionMaker, 'setPath')) {
                $this->decisionMaker->setPath($httpRequest->getRelativePath());
            }

            return $this->decisionMaker->getUniqueIdentifier($httpRequest);
        }

        return NULL;
    }
}