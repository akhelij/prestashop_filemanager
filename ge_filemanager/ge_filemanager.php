<?php
/**
* 2007-2023 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2023 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ge_filemanager extends Module
{
    protected $config_form = false;

    public function __construct()
    {
        $this->name = 'ge_filemanager';
        $this->tab = 'administration';
        $this->version = '1.0.0';
        $this->author = 'Mohamed Akhelij';
        $this->need_instance = 0;

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('File Manager');
        $this->description = $this->l('A manager for your files ');

        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);

        $this->controllers = array('AdminGeFileManager');
    }

    private function _session()
    {
        return \PrestaShop\PrestaShop\Adapter\SymfonyContainer::getInstance()->get('session');
    }

    public function install()
    {
        return parent::install();
    }

    public function uninstall()
    {
        return parent::uninstall();
    }

    public function getContent()
    {
        $uploadDir = _PS_MODULE_DIR_ . 'ge_filemanager/uploads/';

        if (! is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                // Failed to create the directory
                die('Failed to create the upload directory.');
            }
        }

        if ($this->_session()->has('current_folder')) {
            $uploadDir = $this->_session()->get('current_folder');
        }
        
        $output = '';
        $files = array();

        if (Tools::isSubmit('submitAccessFolder')) {
            $currentFolder = Tools::getValue('folder_to_access');
            if (!empty($currentFolder)) {
                // Append the folder to the current path
                $this->_session()->set('current_folder', $currentFolder.'/');
                $uploadDir = $currentFolder.'/';                                   
            }
        }

        elseif (Tools::isSubmit('submitFolderCreate') ) {
            $newFolderName = Tools::getValue('new_folder_name');

            if (!empty($newFolderName) && $newFolderName != 'uploads') {
                $newFolderPath = $uploadDir . $newFolderName;
                if (!is_dir($newFolderPath)) {
                    if (mkdir($newFolderPath, 0777, true)) {
                        $output .= $this->displayConfirmation($this->l('Folder created successfully.'));
                    } else {
                        $output .= $this->displayError($this->l('Error creating folder.'));
                    }
                } else {
                    $output .= $this->displayError($this->l('Folder already exists.'));
                }
            } else {
                $output .= $this->displayError($this->l('Please enter a folder name.'));
            }
        }
        elseif(Tools::isSubmit('submitFileUpload')) {
            if (isset($_FILES['fileToUpload'])) {
                $fileCount = count($_FILES['fileToUpload']['name']);
            
                for ($i = 0; $i < $fileCount; $i++) {
                    $uploadedFile = $_FILES['fileToUpload']['name'][$i];
                    $uploadedFileTmp = $_FILES['fileToUpload']['tmp_name'][$i];
            
                    if (is_uploaded_file($uploadedFileTmp)) {
                        $targetFile = $uploadDir . basename($uploadedFile);
            
                        if (move_uploaded_file($uploadedFileTmp, $targetFile)) {
                            // File uploaded successfully
                            $this->context->controller->confirmations[] = $this->l('File uploaded successfully.');
                        } else {
                            // Error uploading file
                            $this->context->controller->errors[] = $this->l('Error uploading file.');
                        }
                    } else {
                        // Invalid file
                        $this->context->controller->errors[] = $this->l('Invalid file.');
                    }
                }
            }
        }

        elseif (Tools::isSubmit('deleteFile')) {
            $fileToDelete = Tools::getValue('file_to_delete');
            if (!empty($fileToDelete)) {
                $filePath = $uploadDir . $fileToDelete;

                if (file_exists($filePath)) {
                    unlink($filePath);
                    $this->context->controller->confirmations[] = $this->l('File deleted successfully.');
                } else {
                    $this->context->controller->errors[] = $this->l('File not found.');
                }
            }
        }

        elseif (Tools::isSubmit('deleteFolder')) {
            $folderToDelete = Tools::getValue('folder_to_delete');
            if (!empty($folderToDelete)) {
                $folderPath = $uploadDir . $folderToDelete;

                if (is_dir($folderPath)) {
                    $this->deleteDirectory($folderPath);
                    $this->context->controller->confirmations[] = $this->l('Folder deleted successfully.');
                } else {
                    $this->context->controller->errors[] = $this->l('Folder not found.');
                }
            }
        }
        
        $dirHandle = opendir($uploadDir);

        while (($file = readdir($dirHandle)) !== false) {
            if ($file != "." && $file != "..") {
                $filePath = $uploadDir . $file;
                $fileSize = filesize($filePath);
                $publicFileUrl = '/modules/ge_filemanager/uploads'.$this->getPathComplement($uploadDir).$file;
                $type = 'Other';
                $fileExtension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                if (is_dir($filePath)) {
                    $type = 'Folder';
                } elseif (in_array($fileExtension, array('jpg', 'jpeg', 'png', 'gif', 'bmp'), true)) {
                    $type = 'Image';
                } elseif (in_array($fileExtension, array('mp4', 'webm', 'ogg', 'avi'), true)) {
                    $type = 'Video';
                }

                $files[] = array(
                    'name' => $file,
                    'size' => $this->formatFileSize($fileSize),
                    'url' => $publicFileUrl,
                    'path' => $filePath,
                    'type' => $type
                );
            }
        }

        closedir($dirHandle);

        $this->context->smarty->assign(array(
            'files' => $files,
            'upload_url' => $_SERVER['REQUEST_URI'],
            'module_path' => $this->_path,
            'session' => $this->_session()->get('current_folder'),
            'previous' => rtrim(dirname($uploadDir), DIRECTORY_SEPARATOR)
        ));

        $output .= $this->display(__FILE__, 'filemanager.tpl');

        return $output;
    }

    private function getPathComplement($path) {
        // Find the first occurrence of "uploads" in the string
        $uploadsPosition = strstr($path, 'uploads');

        // Get the substring that comes after "uploads" (including "uploads" itself)
        $substringAfterUploads = substr($uploadsPosition, strlen('uploads'));

        // Check if the substring is empty, if so, set it to '/'
        if (empty($substringAfterUploads)) {
            $substringAfterUploads = '/';
        }

        return $substringAfterUploads; 
    }

    private function formatFileSize($size)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        $i = 0;

        while ($size >= 1024 && $i < 4) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }

    protected function deleteDirectory($dirPath)
    {
        if (!is_dir($dirPath)) {
            return;
        }

        $files = array_diff(scandir($dirPath), array('.', '..'));

        foreach ($files as $file) {
            $filePath = $dirPath . DIRECTORY_SEPARATOR . $file;

            if (is_dir($filePath)) {
                $this->deleteDirectory($filePath);
            } else {
                unlink($filePath);
            }
        }

        rmdir($dirPath);
    }

}
