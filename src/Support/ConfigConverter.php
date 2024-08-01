<?php

namespace Dkvhin\Flysystem\OneDrive\Support;

trait ConfigConverter
{
    protected function convertConfig($config=null)
    {
        if($config){
            if(is_array($config)){
                return new \Dkvhin\Flysystem\OneDrive\Support\Config($config);
            }
            if(method_exists($config,'toArray')) {
                return new \Dkvhin\Flysystem\OneDrive\Support\Config($config->toArray());
            }else{
                return new \Dkvhin\Flysystem\OneDrive\Support\Config([
                    'copy_destination_same_as_source'=>$config->get('copy_destination_same_as_source'),
                    'move_destination_same_as_source'=>$config->get('move_destination_same_as_source'),
                    'visibility'=>$config->get('visibility'),
                    'directory_visibility'=>$config->get('directory_visibility'),
                ]);
            }
        }
        return new \Dkvhin\Flysystem\OneDrive\Support\Config();
    }
}