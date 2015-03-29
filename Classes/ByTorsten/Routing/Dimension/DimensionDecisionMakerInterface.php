<?php
namespace ByTorsten\Routing\Dimension;

interface DimensionDecisionMakerInterface {

    /**
     * @return array
     */
    public function getDimension();

    /**
     * @return string
     */
    public function getUniqueIdentifier();
}