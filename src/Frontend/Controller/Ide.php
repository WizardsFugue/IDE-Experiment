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
}
