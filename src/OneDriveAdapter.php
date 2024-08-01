<?php

/**
 * Created by PhpStorm.
 * User: alt
 * Date: 17-Oct-18
 * Time: 8:44 AM
 */

namespace Dkvhin\Flysystem\OneDrive;


use Microsoft\Graph\Graph;
use League\Flysystem\Adapter\AbstractAdapter;
use Dkvhin\Flysystem\OneDrive\Storage\OneDrive;
use Dkvhin\Flysystem\OneDrive\Support\GetTemporaryUrl;
use Dkvhin\Flysystem\OneDrive\Support\StorageToAdapterV1;

class OneDriveAdapter extends AbstractAdapter
{
    use StorageToAdapterV1;
    use GetTemporaryUrl;
    protected $storage;
    public function __construct(Graph $graph, $options = '')
    {
        if (!is_array($options)) {
            $options = ['root' => $options];
        }
        $this->storage = new OneDrive($graph, $options);
        $this->setPathPrefix($options['root'] ?? '');
        $this->throwException = $options['debug'] ?? '';
    }
    public function getTemporaryUrl($path, $expiration = null, $options = [])
    {
        return $this->getMetadata($path)['@downloadUrl'] ?? '';
    }
}
