<?php
/**
 *
 *
 *
 *
 */

namespace Cotya\IDE\Frontend\Controller;

use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class Ide
{

    public function filetree(Request $request, Application $app)
    {

        $dir = new \RecursiveDirectoryIterator($app['dirs.workspace'], \FilesystemIterator::SKIP_DOTS);
        $pathPrefix = $app['dirs.workspace'];
        $filterPath = function($path) use ($pathPrefix) {
            return preg_replace('/^' . preg_quote($pathPrefix, '/') . '/', '', $path);
        };
        $getDirContent = function(\RecursiveDirectoryIterator $directory) use (&$getDirContent, $filterPath) {
            $result = [];
            foreach ($directory as $fileinfo) {
                if ($fileinfo->isDir()) {
                    $result[] = [
                        'id'   => $filterPath($fileinfo->getRealPath()),
                        'name' => $fileinfo->getFilename(),
                        'type' => 'folder',
                        'children' => $getDirContent(
                            new \RecursiveDirectoryIterator($fileinfo, \FilesystemIterator::SKIP_DOTS)

                        )
                    ];
                }
                if ($fileinfo->isFile()) {
                    $result[] = [
                        'id'   => $filterPath($fileinfo->getRealPath()),
                        'name' => $fileinfo->getFilename(),
                        'type' => 'file',
                    ];
                }
            }
            return $result;
        };

        $result = [
            'id'=> 'root',
            'name'=> 'Root',
            'type'=> 'folder',
            'children' => $getDirContent($dir),
        ];
        return new JsonResponse($result);
    }
    
    public function loadFile(Request $request, Application $app)
    {
        $file = $app['dirs.workspace'] . $request->get('file');
        if (file_exists($file)) {
            $content = file_get_contents($file);
        }
        return new Response($content);
    }

    public function saveFile(Request $request, Application $app)
    {
        $file = $app['dirs.workspace'] . $request->get('file');
        $data = $request->get('content');
        if (file_exists($file)) {
            file_put_contents($file, $data);
            //var_dump($file,$data);
        }
        return new JsonResponse(['result'=>'success']);
    }
}
