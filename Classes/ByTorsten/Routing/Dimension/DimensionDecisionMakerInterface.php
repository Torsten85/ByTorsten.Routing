<?php
namespace ByTorsten\Routing\Dimension;

interface DimensionDecisionMakerInterface {

    /**
     * @param string $requestPath
     * @return array
     */
    public function getDimension($requestPath);

    /**
     * @param \TYPO3\Flow\Http\Request $httpRequest
     * @return string
     */
    public function getUniqueIdentifier(\TYPO3\Flow\Http\Request $httpRequest);
}