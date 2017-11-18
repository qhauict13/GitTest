<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2017 Amasty (https://www.amasty.com)
 * @package Amasty_Stockstatus
 */
namespace Amasty\Stockstatus\Helper;
use Magento\Framework\App\Filesystem\DirectoryList;

class Image extends \Magento\Framework\App\Helper\AbstractHelper
{
    /**
    * @var \Magento\MediaStorage\Model\File\UploaderFactory
    */
    protected $_uploaderFactory;

    /**
     * Filesystem facade
     *
     * @var \Magento\Framework\Filesystem
     */
    protected $_filesystem;

    /**
     * File Uploader factory
     *
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_fileUploaderFactory;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * File check
     *
     * @var \Magento\Framework\Filesystem\Io\File
     */
    protected $_ioFile;


    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\MediaStorage\Model\File\UploaderFactory $fileUploaderFactory,
        \Magento\Framework\Filesystem\Io\File $ioFile,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->_filesystem = $filesystem;
        $this->_fileUploaderFactory = $fileUploaderFactory;
        $this->_ioFile = $ioFile;
        $this->_storeManager = $storeManager;
    }

    public function getStatusIconUrl($optionId) {
        $path = $this->_filesystem->getDirectoryRead(
            DirectoryList::MEDIA
             )->getAbsolutePath(
            'amasty/stockstatus/'
        );

        $name = $optionId . '.jpg';
        if ($this->_ioFile->fileExists($path . $name)) {
            $path = $this->_storeManager->getStore()->getBaseUrl(
                \Magento\Framework\UrlInterface::URL_TYPE_MEDIA
            );
            return $path . 'amasty/stockstatus/'. $name;
        }
        else {
            return "";
        }
    }

    public function delete($optionId) {
        $path = $this->_filesystem->getDirectoryRead(
            DirectoryList::MEDIA
             )->getAbsolutePath(
            'amasty/stockstatus/'
        );

        $path .= $optionId . '.jpg';
        if ($this->_ioFile->fileExists($path)) {
            $this->_ioFile->rm($path);
        }
    }

    public function uploadImage($optionId)
    {
        $path = $this->_filesystem->getDirectoryRead(
            DirectoryList::MEDIA
        )->getAbsolutePath(
            'amasty/stockstatus/'
        );

        $this->_ioFile->checkAndCreateFolder($path);

        try {
            /** @var $uploader \Magento\MediaStorage\Model\File\Uploader */
            $uploader = $this->_fileUploaderFactory->create(
                ['fileId' => 'amstockstatus_icon[' . $optionId . ']']
            );
            $uploader->setAllowedExtensions(['jpg', 'jpeg', 'gif', 'png']);
            $uploader->setAllowRenameFiles(true);
            $result = $uploader->save($path, $optionId . '.jpg' );
        } catch (\Exception $e) {
            if ($e->getCode() != \Magento\MediaStorage\Model\File\Uploader::TMP_NAME_EMPTY) {
                $this->_logger->critical($e);
            }
        }
        return $this;
    }
}
