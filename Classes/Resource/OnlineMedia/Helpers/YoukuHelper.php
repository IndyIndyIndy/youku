<?php
namespace ChristianEssl\Youku\Resource\OnlineMedia\Helpers;

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
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\AbstractOEmbedHelper;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Youku helper class
 */
class YoukuHelper extends AbstractOEmbedHelper
{

    /** @var string */
    protected $extension = 'youku';

    /**
     * Get public url
     * Return NULL if you want to use core default behaviour
     *
     * @param File $file
     * @param bool $relativeToCurrentScript
     * @return string|null
     */
    public function getPublicUrl(File $file, $relativeToCurrentScript = false)
    {
        $videoId = $this->getOnlineMediaId($file);
        return sprintf('https://v.youku.com/v_show/id_%s', $videoId);
    }

    /**
     * Get local absolute file path to preview image
     *
     * @param File $file
     * @return string
     */
    public function getPreviewImage(File $file)
    {
        $videoId = $this->getOnlineMediaId($file);
        $temporaryFileName = $this->getTempFolderPath() . 'youku_' . md5($videoId) . '.jpg';

        if (!file_exists($temporaryFileName)) {
            $oEmbedData = $this->getOEmbedData($videoId);
            $previewImage = GeneralUtility::getUrl($oEmbedData['thumbnail_url']);

            if ($previewImage !== false) {
                file_put_contents($temporaryFileName, $previewImage);
                GeneralUtility::fixPermissions($temporaryFileName);
            }
        }
        return $temporaryFileName;
    }

    /**
     * Try to transform given URL to a File
     *
     * @param string $url
     * @param Folder $targetFolder
     * @return File|null
     */
    public function transformUrlToFile($url, Folder $targetFolder)
    {
        $videoId = null;
        if (preg_match("#youku\.com/(?:player.php/sid/|v_show/id_)([a-zA-Z0-9]+)(?:/|\\.)#", $url, $matches) ||
            preg_match("#id_(\w+)#", $url, $matches)
        ) {
            $videoId = $matches[1];
        }

        if (empty($videoId)) {
            return null;
        }

        return $this->transformMediaIdToFile($videoId, $targetFolder, $this->extension);
    }

    /**
     * Get oEmbed data url
     *
     * @param string $mediaId
     * @param string $format
     * @return string
     */
    protected function getOEmbedUrl($mediaId, $format = 'json')
    {
        return 'https://v.youku.com/v_show/id_'.$mediaId.'.html';
    }

    /**
     * Get OEmbed data
     *
     * Apparently youku.com does not provide an oEmbed API, but TYPO3 requires one.
     * So we have to rely on this ugly solution to "fake" an oEmbed response.
     *
     * @param string $mediaId
     * @return array|null
     */
    protected function getOEmbedData($mediaId)
    {
        $html = GeneralUtility::getUrl(
            $this->getOEmbedUrl($mediaId)
        );

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);

        $title = 'Youku Video'; // Default value
        $image = '';

        $metas = $doc->getElementsByTagName('meta');
        for ($i = 0; $i < $metas->length; $i++) {
            $meta = $metas->item($i);
            if($meta->getAttribute('property') == 'og:title') {
                $title = $meta->getAttribute('content');
            }
            if($meta->getAttribute('property') == 'og:image') {
                $image = $meta->getAttribute('content');
            }
        }

        return [
            'title' => $title,
            'width' => 480,
            'height' => 270,
            'author_name' => 'Youku',
            'thumbnail_url' => $image,
            'type' => 'video'
        ];
    }
}