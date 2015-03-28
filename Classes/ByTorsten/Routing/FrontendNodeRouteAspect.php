<?php
namespace ByTorsten\Routing;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\AOP\JoinPointInterface as JoinPointInterface;
use TYPO3\Neos\Routing\Exception\NoSuchDimensionValueException;
use ByTorsten\Routing\Dimension\DimensionDecisionManager;

/**
 * @Flow\Aspect
 */
class FrontendNodeRouteAspect {

    /**
     * @Flow\Inject
     * @var DimensionDecisionManager
     */
    protected $dimensionDecisionManager;

    /**
     * @param JoinPointInterface $joinPoint
     * @Flow\Around("method(TYPO3\Neos\Routing\FrontendNodeRoutePartHandler->parseDimensionsAndNodePathFromRequestPath())")
     * @return array
     */
    public function allowDimensionWithEmptyUriPath(JoinPointInterface $joinPoint) {

        $dimension = $this->dimensionDecisionManager->decide($joinPoint->getMethodArgument('requestPath'));
        if (is_array($dimension)) {
            return $dimension;
        }

        try {
            $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
        } catch (NoSuchDimensionValueException $exception) {
            $result = array();
        }

        return $result;
    }
}