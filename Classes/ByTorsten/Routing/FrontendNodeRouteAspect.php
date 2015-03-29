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
     * @var bool
     */
    protected $shouldHideDimensions = FALSE;

    /**
     * @param JoinPointInterface $joinPoint
     * @Flow\Around("method(TYPO3\Neos\Routing\FrontendNodeRoutePartHandler->parseDimensionsAndNodePathFromRequestPath())")
     * @return array
     */
    public function allowDimensionWithEmptyUriPathAndSetDimensionFromDecisionMakers(JoinPointInterface $joinPoint) {

        $this->shouldHideDimensions = TRUE;
        try {
            $result = $joinPoint->getAdviceChain()->proceed($joinPoint);
        } catch (NoSuchDimensionValueException $exception) {
            $result = array();
        }
        $this->shouldHideDimensions = FALSE;

        $dimensions = $this->dimensionDecisionManager->decide($joinPoint->getMethodArgument('requestPath'));

        if (is_array($dimensions)) {
            $result = array_merge($result, $dimensions);
        }

        return $result;
    }

    /**
     * @param JoinPointInterface $joinPoint
     * @Flow\Around("method(TYPO3\TYPO3CR\Domain\Service\ConfigurationContentDimensionPresetSource->getAllPresets())")
     * @return array
     */
    public function hideDimensionsBeforeParsing(JoinPointInterface $joinPoint) {
        $result = $joinPoint->getAdviceChain()->proceed($joinPoint);

        if ($this->shouldHideDimensions) {
            foreach($this->dimensionDecisionManager->getDecisionMakers() as $dimensionName => $decisionMaker) {
                unset($result[$dimensionName]);
            }
        }


        return $result;
    }
}