<?php
namespace ByTorsten\Routing;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\AOP\JoinPointInterface as JoinPointInterface;
use TYPO3\Neos\Routing\Exception\NoSuchDimensionValueException;
use ByTorsten\Routing\Dimension\DimensionDecisionManager;

/**
 * @Flow\Aspect
 * @Flow\Scope("singleton")
 */
class FrontendNodeRouteAspect {

    /**
     * @Flow\Inject
     * @var DimensionDecisionManager
     */
    protected $dimensionDecisionManager;

    /**
     * @var string
     */
    protected $lastRequestedDimensionName;

    /**
     * @var array
     */
    protected $handledDimensionNames = array();

    /**
     * @param string $requestPath
     * @return string
     */
    protected function stripDimensionFromPath($requestPath) {
        $matches = array();
        preg_match(\TYPO3\Neos\Routing\FrontendNodeRoutePartHandler::DIMENSION_REQUEST_PATH_MATCHER, $requestPath, $matches);
        return (isset($matches['remainingRequestPath']) ? $matches['remainingRequestPath'] : $requestPath);
    }

    /**

     * @param JoinPointInterface $joinPoint
     * @Flow\Around("method(TYPO3\Neos\Routing\FrontendNodeRoutePartHandler->parseDimensionsAndNodePathFromRequestPath())")
     * @return array
     *
     * @throws Dimension\Exception\DecisionMakerNotFoundException
     * @throws NoSuchDimensionValueException
     * @throws \Exception
     */
    public function setDimensionFromDecisionMakers(JoinPointInterface $joinPoint) {

        $requestPath = $joinPoint->getMethodArgument('requestPath');

        if (strpos($requestPath, '@') !== FALSE) {

            $strippedRequestPath = $this->stripDimensionFromPath($requestPath);
            if ($strippedRequestPath[0] !== '@') {
                $joinPoint->setMethodArgument('requestPath', $strippedRequestPath);
            }
            return $this->dimensionDecisionManager->handleDimensionsInBackend($requestPath);

        }

        $result = array();
        $originalResult = array();

        $handledDimensions = $this->dimensionDecisionManager->handleNotSetDimensions($requestPath);
        foreach($handledDimensions as $dimensionName => $dimensionValue) {
            $result[$dimensionName] = $dimensionValue;
            $this->handledDimensionNames[] = $dimensionName;
        }

        while(TRUE) {
            try {
                $originalResult = $joinPoint->getAdviceChain()->proceed($joinPoint);
                break;
            } catch (\TYPO3\Neos\Routing\Exception $exception) {
                $dimensionName = $this->lastRequestedDimensionName;
                $dimension = $this->dimensionDecisionManager->decide($dimensionName, $requestPath);
                if ($dimension === NULL) {
                    throw $exception;
                }
                $result[$dimensionName] = $dimension;
                $this->handledDimensionNames[] = $dimensionName;
            }
        }

        $this->handledDimensionNames = array();
        return array_merge($result, $originalResult);
    }

    /**
     * @param JoinPointInterface $joinPoint
     * @Flow\Around("method(TYPO3\TYPO3CR\Domain\Service\ConfigurationContentDimensionPresetSource->getAllPresets())")
     * @return array
     */
    public function hideDimensionsBeforeParsing(JoinPointInterface $joinPoint) {
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);

        foreach($this->handledDimensionNames as $dimensionName) {
            unset($result[$dimensionName]);
        }

        return $result;
    }

    /**
     * @param JoinPointInterface $joinPoint
     * @Flow\After("method(TYPO3\Neos\Domain\Service\ConfigurationContentDimensionPresetSource->findPresetByUriSegment())")
     * @return void
     */
    public function registerLastRequestedDimension(JoinPointInterface $joinPoint) {
        $this->lastRequestedDimensionName = $joinPoint->getMethodArgument('dimensionName');
    }
}