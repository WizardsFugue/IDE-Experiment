<?php
/**
 * 
 * 
 * 
 * 
 */

namespace Cotya\IDE\Frontend;

use \React\Http\Request;
use \React\Http\Response;
use Symfony\Component\HttpFoundation\Request as SilexRequest;
use Symfony\Component\HttpFoundation\Response as SilexResponse;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class Application
{
    protected $publicDir;
    protected $workspace;
    protected $silexApp;
    protected $i = 0;
    
    public function __construct(\Silex\Application $silexApp, $publicDir, $workspace)
    {
        $this->silexApp = $silexApp;
        $this->publicDir = $publicDir;
        $this->workspace = $workspace;
        echo "server started".PHP_EOL;
    }
    
    protected function logRequest( Request $request)
    {
        $queryString = json_encode($request->getQuery());
        echo " {$request->getMethod()} {$request->getPath()} {$queryString}".PHP_EOL;
    }

    public function onRequest( Request $request, Response $response )
    {
        $this->logRequest($request);
        
        switch(true){
            case ('/'===$request->getPath()):
                $this->defaultRequest($request,$response);
                break;
            case (strpos($request->getPath(), '/style/')===0):
            case (strpos($request->getPath(), '/js/')===0):
            case (strpos($request->getPath(), '/vendor/')===0):
                $this->handlePublicDirRequest($request, $response);
                break;
            case ('/ide'===$request->getPath()):
                $this->handleIdeRequest($response);
                break;
            case ('/editor'===$request->getPath()):
                $this->editorRequest($request, $response);
                break;
            case ('/memory'===$request->getPath()):
                $text = memory_get_usage();
                $this->simpleTextOutput($text, $response);
                break;
            case ('/favicon.ico'===$request->getPath()):
                $this->handleFavicon($request,$response);
                break;
            default:
                if ($request->getMethod() === 'GET') {
                    $subRequest = SilexRequest::create(
                        $request->getPath(),
                        $request->getMethod(),
                        $request->getQuery()
                    );
                    $silexResponse = $this->silexApp->handle($subRequest);
                    $this->outputSilexResponse($silexResponse, $response);
                } elseif ($request->getMethod() === 'POST') {
                    $request->on('data', function($data) use ($request, $response) {
                        $data = json_decode($data, true);
                        $subRequest = SilexRequest::create(
                            $request->getPath(),
                            $request->getMethod(),
                            $data
                        );
                        $silexResponse = $this->silexApp->handle($subRequest);
                        $this->outputSilexResponse($silexResponse, $response);
                    });
                }
                //$this->notFound($response);
        }
    }
    
    protected function outputSilexResponse(SilexResponse $silexResponse, Response $response)
    {
        
        $headers = array('Content-Type' => $silexResponse->headers->get('Content-Type'));
        $response->writeHead($silexResponse->getStatusCode(), $headers);
        $response->end($silexResponse->getContent());
    }
    
    
    protected function simpleTextOutput($text, Response $response)
    {
        $headers = array('Content-Type' => 'text/plain');
        $response->writeHead(200, $headers);
        $response->end($text);
    }
    
    protected function handlePublicDirRequest(Request $request, Response $response)
    {

        // @todo fix /../ attack point
        $file = $this->publicDir.$request->getPath();
        if (file_exists($file)) {
            $data = file_get_contents($file);
            $headers = array('Content-Type' => 'text/plain');
            $response->writeHead(200, $headers);
            $response->end($data);
        } else {
            $this->notFound($response);
        }
    }
    
    protected function handleIdeRequest(Response $response)
    {

        $includeCall = function() {
            ob_start();
            include __DIR__ .'/../../res/template/editor.phtml';
            $content = ob_get_contents();
            ob_end_clean();
            return $content;
        };
        $output = $includeCall();
        $headers = array('Content-Type' => 'text/html');
        $response->writeHead(200, $headers);
        $response->end($output);
    }
    
    protected function editorOutput($fileContent, Response $response)
    {
        $fileContent = htmlspecialchars($fileContent);
        $output = <<<HTML
<html>
<head>

</head>
<body>
  <div id="editor" style="height: 800px;width: 600px;">$fileContent</div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.1.3/ace.js" type="text/javascript" charset="utf-8"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.1.3/mode-php.js" type="text/javascript" charset="utf-8"></script>
    <script>
        var editor = ace.edit("editor");
        editor.getSession().setMode("ace/mode/php");
    </script>
</body>
</html>
HTML;

        $headers = array('Content-Type' => 'text/html');
        $response->writeHead(200, $headers);
        $response->end($output);
    }
    
    protected function handleFavicon( Request $request, Response $response )
    {
        $data = file_get_contents($this->publicDir.'/favicon.ico');
        $headers = array('Content-Type' => 'image/vnd.microsoft.icon');
        $response->writeHead(200, $headers);
        $response->end($data);
    }
    
    protected function defaultRequest( Request $request, Response $response )
    {
        $this->i++;

        $text = "This is request number $this->i.\n";
        $text .= get_class($request);
        $text .= get_class($response);
        $this->simpleTextOutput($text, $response);
    }
    
    protected function editorRequest( Request $request, Response $response )
    {
        $file = false;
        if(isset($request->getQuery()['file'])){
            $file = $request->getQuery()['file'];
        }
        if($file === '/index.php'){
            $fileContent = file_get_contents($this->workspace.$file);
            $this->editorOutput($fileContent, $response);
        }else{
            $this->notFound($response);
        }
    }
    
    protected function notFound( Response $response )
    {

        $response->writeHead(404);
        $response->end('');
    }
    
    
}
