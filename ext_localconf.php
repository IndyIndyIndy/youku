<?php

defined('TYPO3_MODE') || die('Access denied.');

call_user_func(
    function () {
        // Register file extension "youku"
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'] .= ',youku';

        // Register the online media helper
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['onlineMediaHelpers']['youku']
            = \ChristianEssl\Youku\Resource\OnlineMedia\Helpers\YoukuHelper::class;

        // Register the mime type
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']['youku'] = 'video/youku';

        // Register the renderer for the frontend
        $rendererRegistry = \TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::getInstance();
        $rendererRegistry->registerRendererClass(\ChristianEssl\Youku\Resource\Rendering\YoukuRenderer::class);
    }
);
