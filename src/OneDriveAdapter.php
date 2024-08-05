<?php

/**
 * Created by PhpStorm.
 * User: alt
 * Date: 17-Oct-18
 * Time: 8:44 AM
 */

namespace Dkvhin\Flysystem\OneDrive;


use Microsoft\Graph\Graph;
use League\Flysystem\PathPrefixer;
use League\Flysystem\FilesystemAdapter;
use Dkvhin\Flysystem\OneDrive\Storage\OneDrive;
use Dkvhin\Flysystem\OneDrive\Support\OneDriveOauth;
use Dkvhin\Flysystem\OneDrive\Support\StorageToAdapter;
use League\Flysystem\UrlGeneration\TemporaryUrlGenerator;

class OneDriveAdapter implements FilesystemAdapter, TemporaryUrlGenerator
{
    use StorageToAdapter;
    protected $storage;
    protected $prefixer;
    public function __construct(Graph $graph, $options = '', ?OneDriveOauth $auth = null)
    {
        if (!is_array($options)) {
            $options = ['root' => $options];
        }
        $this->storage = new OneDrive($graph, $options, $auth);
        $this->prefixer = new PathPrefixer($options['root'] ?? '', DIRECTORY_SEPARATOR);
    }
}
