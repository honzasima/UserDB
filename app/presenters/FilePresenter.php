<?php

namespace App\Presenters;

use Nette,
    App\Model,
    Nette\Application\UI\Form,
    Nette\Forms\Container,
    Nette\Utils\Html,
    Tracy\Debugger,
    Nette\Utils\Strings,
    PdfResponse\PdfResponse,
    Nette\Application\Responses,
    App\Components;

use Nette\Forms\Controls\SubmitButton;
/**
 * File presenter.
 */
class FilePresenter extends BasePresenter
{
    /** @persistent */
    private $ipAdresa;

    /** @var Components\LogTableFactory @inject **/
    public $logTableFactory;
    function __construct(Model\IPAdresa $ipAdresa) {
    	$this->ipAdresa = $ipAdresa;
    }


public function actionDownloadWinboxCmd() {
      if($this->getParam('id'))
    	{
            if($ip= $this->ipAdresa->getIPAdresa($this->getParam('id')))
    	    {
                //$file = tempnam(sys_get_temp_dir(), "cmd");
                //$handle = fopen($file, "w");
                //fwrite($handle, "C:\winbox.exe " . $ip->ip_adresa . " " . $ip->login);
                //fclose($handle);
                $cmd = "C:\winbox.exe " . $ip->ip_adresa . " " . $ip->login;

                $httpResponse = $this->getHttpResponse();
                $httpResponse->setHeader('Pragma', "public");
                $httpResponse->setHeader('Expires', 0);
                $httpResponse->setHeader('Cache-Control', "must-revalidate, post-check=0, pre-check=0");
                $httpResponse->setHeader('Content-Disposition', "attachment; filename=winbox.cmd");
                $httpResponse->setHeader('Content-Type', "application/octet-stream");
                $httpResponse->setHeader('Content-Length', strlen($cmd));
                \Tracy\Dumper::dump(strlen($cmd));
                \Tracy\Dumper::dump($cmd);
                //$this->sendResponse(new TextResponse($cmd));
                //$httpResponse->setHeader('Content-Length', filesize($file));
                //$this->sendResponse(new FileResponse($file, "winbox.cmd", 'application/octet-stream', true));
            }
        }
    }
}