<?php

namespace App\V1Module\Presenters;

use Nette\Application\Responses\JsonResponse,
    Nette\Application\Responses\TextResponse,
    App\Model;
use Nette\Http\Response;
use Nette\Utils\DateTime;
use \Firebase\JWT\JWT;

class ApiPresenter extends \Nette\Application\UI\Presenter
{

    # Api key for decryption token
    protected $apiKey = 'xxxx';

    protected $token;

    protected $apiKlicModel;

    protected $log;

    /** @var \Nette\Http\Response */
    protected $httpResponse;

    /** @var \Nette\Http\Request */
    protected $httpRequest;

    // to access these actions, only valid key is required;
    // no checks against AP-id and/or module restrictions is done regarding the API key
    protected $alwaysAllowedActions = ['Api:HealthCheck'];

    protected $jwtToken;
    protected $jwt;

    public function injectHttpResponse(\Nette\Http\Response $httpResponse)
    {
        $this->httpResponse = $httpResponse;
    }

    public function injectHttpRequest(\Nette\Http\Request $httpRequest)
    {
        $this->httpRequest = $httpRequest;
    }

    public function injectApiKlicModel(\App\Model\ApiKlic $apiKlicModel)
    {
        $this->apiKlicModel = $apiKlicModel;
    }

    public function injectApplikaceTokenModel(\App\Model\AplikaceToken $token)
    {
        $this->token = $token;
    }


    public function injectAplikaceLogModel(\App\Model\AplikaceLog $log)
    {
        $this->log = $log;
    }


    public function checkRequirements($element)
    {
        // due to CORS preflight test, we have to respond 200 OK (not 401) to OPTIONS request
        if ($this->httpRequest->getMethod() == 'OPTIONS') {
            $this->handleOptionsMethod();
            return;
        }

        $this->jwtToken = $this->readAuthorizationToken();
        $this->jwt = $this->jwtDecode( $this->jwtToken , array('HS256'));
        $keyRec = $this->apiKlicModel->getApiKlic($this->jwt->apiKey);



        if ($keyRec) {
            if ($this->jwt->apiToken == $keyRec->klic) {
                if ($this->apiKlicModel->isNotExpired($keyRec->plati_do)) {
                    // OK, the key s valid
                    // check if the action (module) and AP are allowed
                    $requestedModule = $this->getName();;
                    $requestedApId = $this->getParameter('id');
                    if (!in_array($requestedModule, $this->alwaysAllowedActions)) {
                        // action (module) is NOT always allowed, test module and/or AP-id restrictions
                        if ($keyRec->presenter && $requestedModule != $keyRec->presenter) {
                            // key is restricted to a module and it does not match to requested module
                            $this->sendErrorResponse(Response::S403_FORBIDDEN, 'not allowed to view module=' . $requestedModule);
                        } else {
                            // key is allowed to view this modules
                            // check AP restrictions
                            if ($keyRec->AP_id && $requestedApId != $keyRec->AP_id) {
                                // key is restricted to an AP and does not match to requested AP
                                $this->sendErrorResponse(Response::S403_FORBIDDEN, 'not allowed to view AP=' . $requestedApId);
                            } else {
                                // key is not restricted to AP or the requested AP match the key's AP
                                // go on
                                parent::checkRequirements($element);
                                return;
                            }
                        }
                    } else {
                        // always allowed module, go on
                        parent::checkRequirements($element);
                        return;
                    }
                }
            }
        }

        $this->sendAuthRequired('Invalid credentials'); // fallback
    }


    /**
     * Read HTTP reader with authorization token
     * If everything is ok, it return token. In other situations returns false and set errorMessage.
     *
     * @return string|null
     */
    private function readAuthorizationToken(): ?string
    {
        if (!isset($_SERVER['HTTP_AUTHORIZATION'])) {
            //$this->errorMessage = 'Authorization header HTTP_Authorization is not set';
            $this->sendErrorResponse(Response::S401_UNAUTHORIZED, 'Authorization header HTTP_Authorization is not set');
            return null;
        }
        $parts = explode(' ', $_SERVER['HTTP_AUTHORIZATION']);
        if (count($parts) !== 2) {
            //$this->errorMessage = 'Authorization header contains invalid structure';
            $this->sendErrorResponse(Response::S401_UNAUTHORIZED,  'Authorization header contains invalid structure');
            return null;
        }
        if (strtolower($parts[0]) !== 'bearer') {
            //$this->errorMessage = 'Authorization header doesn\'t contains bearer token';
            $this->sendErrorResponse(Response::S401_UNAUTHORIZED, 'Authorization header doesn\'t contains bearer token');
            return null;
        }
        return $parts[1];
    }

    public function handleOptionsMethod()
    {
        $this->sendResponse(new TextResponse(""));
    }

    public function sendErrorResponse(int $code, string $logMessage)
    {
        $this->log->log('api.mobile.'.$this->getName() . '.' . $this->presenter->getAction().'.error', $this->jwt);
        $this->httpResponse->setCode($code);
        $this->sendResponse(new JsonResponse(['errors' => [['code' => $code, 'message' => $logMessage]]]));
    }

    public function sendSuccessResponse($data)
    {
        $this->log->log('api.mobile.'.$this->getName() . '.' . $this->presenter->getAction().'.successful', $this->jwt);
        $this->sendResponse($data);
    }

    public function sendAuthRequired($reason)
    {
        $this->httpResponse->setCode(Response::S401_UNAUTHORIZED);
        $this->httpResponse->addHeader('WWW-Authenticate', 'Basic realm="UserDB API"');
        $this->sendResponse(new JsonResponse(['result' => 'UNAUTHORIZED: ' . $reason]));
    }

    public function verifyToken()
    {
        if (!$this->token->verifyToken($this->jwt->uid, $this->jwtToken )) {
            $this->log->log('api.mobile.verifyToken.failed', array($this->jwt->uid, $this->jwtToken ));
            $this->sendErrorResponse(Response::S401_UNAUTHORIZED, 'Token invalid');
        }
    }

    public function verifyAdministrator(){
        if (!($this->jwt->administrator)) {
            $this->log->log('api.mobile.verifyAdministrator.failed', array($this->jwt->uid, $this->jwtToken));
            $this->sendErrorResponse(Response::S400_BAD_REQUEST, 'Rule invalid');
        }
    }

    public function jwtEncode($playload){
        return JWT::encode($playload,$this->apiKey);
    }

    public function jwtDecode($playload){
        return JWT::decode($playload,$this->apiKey, array('HS256'));
    }
}
