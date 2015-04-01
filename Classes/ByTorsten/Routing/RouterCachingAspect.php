<?php
namespace ByTorsten\Routing;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\AOP\JoinPointInterface as JoinPointInterface;
use ByTorsten\Routing\Dimension\DimensionDecisionManager;

/**
 * @Flow\Aspect
 */
class RouterCachingAspect {

    /**
     * @Flow\Inject
     * @var DimensionDecisionManager
     */
    protected $dimensionDecisionManager;

    /**
     * @param JoinPointInterface $joinPoint
     * @Flow\Around("method(TYPO3\Flow\Mvc\Routing\RouterCachingService->buildRouteCacheIdentifier())")
     * @return array
     */
    public function getCacheIdentifier(JoinPointInterface $joinPoint) {
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);

        $identifier = $this->dimensionDecisionManager->identify($joinPoint->getMethodArgument('httpRequest'));
        if (is_string($identifier)) {
            $result = md5(sprintf('%s_%s', $identifier, $result));
        }

        return $result;
    }
}