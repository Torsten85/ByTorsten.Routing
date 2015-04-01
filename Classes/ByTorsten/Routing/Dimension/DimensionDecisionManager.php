<?php
namespace ByTorsten\Routing\Dimension;

use TYPO3\Flow\Annotations as Flow;
use ByTorsten\Routing\Dimension\Exception\DecisionMakerNotFoundException;
use TYPO3\Flow\Http\Request;
use TYPO3\Neos\Domain\Service\ContentDimensionPresetSourceInterface;

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
     * @Flow\Inject
     * @var ContentDimensionPresetSourceInterface
     */
    protected $contentDimensionPresetSource;

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
        $decisionMakerClassNames = $settings['dimension']['dimensionDecisionMakers'];
        if (is_array($decisionMakerClassNames)) {
            $this->createDimensionDecisionMakers($decisionMakerClassNames);
        }
    }

    /**
     * @param string $requestPath
     * @return array
     */
    public function handleDimensionsInBackend($requestPath) {
        $dimensionValues = array();

        /** @var AbstractDimensionDecisionMaker $decisionMaker */
        foreach($this->decisionMakers as $dimensionName => $decisionMaker) {
            $decisionMaker->setPath($requestPath);
            $dimensionValues[$dimensionName] = $decisionMaker->getDimensionFromPath();
        }

        return $dimensionValues;
    }

    /**
     * @param $requestPath
     * @return array
     */
    public function handleNotSetDimensions($requestPath) {

        preg_match(\TYPO3\Neos\Routing\FrontendNodeRoutePartHandler::DIMENSION_REQUEST_PATH_MATCHER, $requestPath, $matches);
        $dimensionValues = array();

        if (isset($matches['dimensionPresetUriSegments'])) {


            $dimensionPresetUriSegments = explode('_', $matches['dimensionPresetUriSegments']);
            foreach($dimensionPresetUriSegments as $dimensionPresetUriSegment) {

                /** @var AbstractDimensionDecisionMaker $decisionMaker */
                foreach($this->decisionMakers as $dimensionName => $decisionMaker) {
                    $preset = $this->contentDimensionPresetSource->findPresetByUriSegment($dimensionName, $dimensionPresetUriSegment);

                    if ($preset === NULL) {
                        $decisionMaker->setPath($requestPath);
                        $dimensionValue = $decisionMaker->getDimension();

                        if ($dimensionValue !== NULL) {
                            $dimensionValues[$dimensionName] = $dimensionValue;
                        }
                    }
                }
            }

        } else {

            /** @var AbstractDimensionDecisionMaker $decisionMaker */
            foreach($this->decisionMakers as $dimensionName => $decisionMaker) {
                $decisionMaker->setPath($requestPath);
                $dimensionValues[$dimensionName] = $decisionMaker->getDimension();
            }

        }


        return $dimensionValues;
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
     * @param string $dimensionName
     * @param string $requestPath
     * @return array|NULL
     * @throws DecisionMakerNotFoundException
     * @throws \TYPO3\Neos\Routing\Exception\NoSuchDimensionValueException
     */
    public function decide($dimensionName, $requestPath) {

        if (!isset($this->decisionMakers[$dimensionName])) {
            throw new DecisionMakerNotFoundException(sprintf('No decision maker found for dimension "%s".', $dimensionName), 1222268009);
        }

        /** @var AbstractDimensionDecisionMaker $decisionMaker */
        $decisionMaker = $this->decisionMakers[$dimensionName];

        $decisionMaker->setPath($requestPath);
        return $decisionMaker->getDimension($requestPath);
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