<?php
namespace ChristianEssl\Youku\Resource\Rendering;

/***
 *
 * This file is part of the "Youku" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2019 Christian Eßl <indy.essl@gmail.com>, https://christianessl.at
 *
 ***/

use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperInterface;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\Rendering\FileRendererInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Youku renderer class
 */
class YoukuRenderer implements FileRendererInterface
{
    /**
     * @var OnlineMediaHelperInterface
     */
    protected $onlineMediaHelper;

    /**
     * Returns the priority of the renderer
     * This way it is possible to define/overrule a renderer
     * for a specific file type/context.
     * For example create a video renderer for a certain storage/driver type.
     * Should be between 1 and 100, 100 is more important than 1
     *
     * @return int
     */
    public function getPriority()
    {
        return 1;
    }

    /**
     * Check if given File(Reference) can be rendered
     *
     * @param FileInterface $file File of FileReference to render
     * @return bool
     */
    public function canRender(FileInterface $file)
    {
        return ($file->getMimeType() === 'video/youku' || $file->getExtension() === 'youku') && $this->getOnlineMediaHelper($file) !== false;
    }

    /**
     * Get online media helper
     *
     * @param FileInterface $file
     * @return bool|OnlineMediaHelperInterface
     */
    protected function getOnlineMediaHelper(FileInterface $file)
    {
        if ($this->onlineMediaHelper === null) {
            $orgFile = $file;
            if ($orgFile instanceof FileReference) {
                $orgFile = $orgFile->getOriginalFile();
            }
            if ($orgFile instanceof File) {
                $this->onlineMediaHelper = OnlineMediaHelperRegistry::getInstance()->getOnlineMediaHelper($orgFile);
            } else {
                $this->onlineMediaHelper = false;
            }
        }
        return $this->onlineMediaHelper;
    }

    /**
     * Render for given File(Reference) html output
     *
     * @param FileInterface $file
     * @param int|string $width TYPO3 known format; examples: 220, 200m or 200c
     * @param int|string $height TYPO3 known format; examples: 220, 200m or 200c
     * @param array $options
     * @param bool $usedPathsRelativeToCurrentScript See $file->getPublicUrl()
     * @return string
     */
    public function render(FileInterface $file, $width, $height, array $options = [], $usedPathsRelativeToCurrentScript = false)
    {
        $options = $this->collectOptions($options, $file);
        $src = $this->createYoukuUrl($options, $file);
        $attributes = $this->collectIframeAttributes($width, $height, $options);

        return sprintf(
            '<iframe src="%s"%s></iframe>',
            $src,
            empty($attributes) ? '' : ' ' . implode(' ', $attributes)
        );
    }

    /**
     * @param array $options
     * @param FileInterface $file
     * @return array
     */
    protected function collectOptions(array $options, FileInterface $file)
    {
        if (!isset($options['autoplay']) && $file instanceof FileReference) {
            $autoplay = $file->getProperty('autoplay');
            if ($autoplay !== null) {
                $options['autoplay'] = $autoplay;
            }
        }

        if (!isset($options['allow'])) {
            $options['allow'] = 'fullscreen';
            if (!empty($options['autoplay'])) {
                $options['allow'] = 'autoplay; fullscreen';
            }
        }

        return $options;
    }

    /**
     * @param array $options
     * @param FileInterface $file
     * @return string
     */
    protected function createYoukuUrl(array $options, FileInterface $file)
    {
        $videoId = $this->getVideoIdFromFile($file);

        $urlParams = [];
        if (!empty($options['autoplay'])) {
            $urlParams[] = 'autoplay=true';
        }
        if (!empty($options['loop'])) {
            $urlParams[] = 'loop=1';
        }
        $urlParams[] = 'title=' . (int)!empty($options['showinfo']);
        $urlParams[] = 'byline=' . (int)!empty($options['showinfo']);
        $urlParams[] = 'portrait=0';

        return sprintf('http://player.youku.com/embed/%s?%s', $videoId, implode('&amp;', $urlParams));
    }

    /**
     * @param FileInterface $file
     * @return string
     */
    protected function getVideoIdFromFile(FileInterface $file)
    {
        if ($file instanceof FileReference) {
            $orgFile = $file->getOriginalFile();
        } else {
            $orgFile = $file;
        }

        return $this->getOnlineMediaHelper($file)->getOnlineMediaId($orgFile);
    }

    /**
     * @param int|string $width
     * @param int|string $height
     * @param array $options
     * @return array
     */
    protected function collectIframeAttributes($width, $height, array $options)
    {
        $attributes = ['allowfullscreen'];
        if (isset($options['additionalAttributes']) && is_array($options['additionalAttributes'])) {
            $attributes[] = GeneralUtility::implodeAttributes($options['additionalAttributes'], true, true);
        }
        if (isset($options['data']) && is_array($options['data'])) {
            array_walk($options['data'], function (&$value, $key) {
                $value = 'data-' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
            });
            $attributes[] = implode(' ', $options['data']);
        }
        if ((int)$width > 0) {
            $attributes[] = 'width="' . (int)$width . '"';
        }
        if ((int)$height > 0) {
            $attributes[] = 'height="' . (int)$height . '"';
        }
        if (isset($GLOBALS['TSFE']) && is_object($GLOBALS['TSFE']) && $GLOBALS['TSFE']->config['config']['doctype'] !== 'html5') {
            $attributes[] = 'frameborder="0"';
        }
        foreach (['class', 'dir', 'id', 'lang', 'style', 'title', 'accesskey', 'tabindex', 'onclick', 'allow'] as $key) {
            if (!empty($options[$key])) {
                $attributes[] = $key . '="' . htmlspecialchars($options[$key]) . '"';
            }
        }
        return $attributes;
    }
}