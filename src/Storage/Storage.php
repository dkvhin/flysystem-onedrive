<?php


namespace Dkvhin\Flysystem\OneDrive\Storage;

use Dkvhin\Flysystem\OneDrive\Contracts\Storage\StorageContract;
use Dkvhin\Flysystem\OneDrive\Service\HasLogger;

abstract class Storage implements StorageContract
{
	use HasLogger;
}