<?php
/**
 * Logfile Backend Controller
 * 
 * $Id: $
 */

/**
 * Backend controller which provides download function for logfile
 */
class Shopware_Controllers_Backend_MoptAvalaraLog extends Shopware_Controllers_Backend_ExtJs
{
  /**
   * called when user downloads avalara logfile in shop backend
   * @return returns avalara logfile with an echo.
   */
  public function downloadLogfileAction()
  {
      try {
          $fileNamePart = \Shopware\Plugins\MoptAvalara\Util\FormCreator::LOG_FILE_NAME;
          $logFileName = $fileNamePart . '-' . date('Y-m-d') . \Shopware\Plugins\MoptAvalara\Util\FormCreator::LOG_FILE_EXT;

          $logDirectory = $this->getLogDir();

          $file = $logDirectory . $logFileName;

          if (!file_exists($file)) {
              $this->View()->assign(array(
                  'success' => false,
                  'data' => $logFileName,
                  'message' => 'File not exist'
              ));
          }

          $response = $this->Response();
          $response->setHeader('Cache-Control', 'public');
          $response->setHeader('Content-Description', 'File Transfer');
          $response->setHeader('Content-disposition', 'attachment; filename=' . $logFileName );
          $response->setHeader('Content-Type', 'text/plain');
          $response->setHeader('Content-Transfer-Encoding', 'binary');
          $response->setHeader('Content-Length', filesize($file));
          $response->setBody(readfile($file));
          $response->sendResponse();
          exit;
      }
      catch (Exception $e) {
          echo("Exception");
          $this->View()->assign(array(
             'success' => false,
             'data' => $this->Request()->getParams(),
             'message' => $e->getMessage()
          ));
          return;
      }

    //removes the global PostDispatch Event to prevent assignments to the view
    Enlight_Application::Instance()->Events()->removeListener(new Enlight_Event_EventHandler('Enlight_Controller_Action_PostDispatch',''));
  }

  /**
   * @return string
   */
  protected function getLogDir()
  {
      return Shopware()->Application()->Kernel()->getLogDir() . '/';
  }
}