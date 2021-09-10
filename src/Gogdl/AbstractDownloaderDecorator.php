<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gogdl;

/**
 * Description of AbstractDownloaderDecorator
 *
 * @author Yaasir Ketwaroo
 */
abstract class AbstractDownloaderDecorator
{

    abstract public function addFile($game, $file, $expectedSize);

    public function runDownloader(Downloader $downloader, array $games)
    {
        $files = $downloader->run($games)
            ->getFiles();

        foreach($files as $game => $files)
        {
            foreach($files as $file => $meta)
            {
                $this->addFile($game, $file, intval($meta['filesize']));
            }
        }
    }

}
