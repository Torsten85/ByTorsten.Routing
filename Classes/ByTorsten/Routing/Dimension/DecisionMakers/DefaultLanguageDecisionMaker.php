<?php
namespace ByTorsten\Routing\Dimension\DecisionMakers;

use ByTorsten\Routing\Dimension\AbstractDimensionDecisionMaker;
use TYPO3\Flow\Annotations as Flow;


class DefaultLanguageDecisionMaker extends AbstractDimensionDecisionMaker {

    /**
     * @var \TYPO3\TYPO3CR\Domain\Service\ConfigurationContentDimensionPresetSource
     * @Flow\Inject
     */
    protected $configurationContentDimensionPresetSource;

    /**
     * @return string
     */
    function resolveDimension() {
        $defaultPreset = $this->contentDimensionPresetSource->getDefaultPreset('language');
        return $defaultPreset['identifier'];
    }
}