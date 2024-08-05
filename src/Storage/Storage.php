<?php


namespace Dkvhin\Flysystem\OneDrive\Storage;

use Closure;
use Dkvhin\Flysystem\OneDrive\Service\HasLogger;
use Dkvhin\Flysystem\OneDrive\Contracts\Storage\StorageContract;

abstract class Storage implements StorageContract
{
    use HasLogger;
}