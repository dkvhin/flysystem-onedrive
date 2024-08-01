<?php


namespace Dkvhin\Flysystem\OneDrive\Cache;


use Dkvhin\Flysystem\OneDrive\Cache\Stores\ArrayStore;
use Dkvhin\Flysystem\OneDrive\Cache\Stores\GoogleDriveStore;
use Dkvhin\Flysystem\OneDrive\Contracts\Cache\PathStore;


/**
 * Class PathCache
 * @package Dkvhin\Flysystem\OneDrive\Cache
 * @mixin PathStore
 * @mixin GoogleDriveStore
 * @mixin ArrayStore
 */
class PathCache
{
	protected $store;
	public function __construct(PathStore $store=null)
	{
		if($store==null) {
			$store=new ArrayStore();
		}
		$this->store = $store;
	}

	/**
	 * @return ArrayStore|GoogleDriveStore|PathStore|null
	 */
	public function getStore(){
		return $this->store;
	}

	public function __call($name, $arguments)
	{
		return $this->store->$name(...$arguments);
	}
}
