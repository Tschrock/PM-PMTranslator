<?php

namespace tschrock\PMTranslator;

use pocketmine\scheduler\PluginTask;

class CheckCurlPluginTask extends PluginTask {


    public function __construct(\pocketmine\plugin\Plugin $owner) {
        parent::__construct($owner);
    }

    public function onRun($currentTick) {
        $this->getOwner()->threadedCurl->checkForCompletedRequests();
    }

}
