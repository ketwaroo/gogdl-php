<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Gogdl;

/**
 * Description of ProgressServer
 *
 * @author Yaasir Ketwaroo<yaasir@ketwaroo.com>
 */
class ProgressServer extends AbstractDownloaderDecorator
{

    const progress_cache_file = 'gogdl-progress';
    const state_not_started   = 'not-started';
    const state_in_progress   = 'in-progress';
    const state_complete      = 'complete';

    protected $progressChacheDir,
        $progressData = array();

    public function __construct()
    {
        $this->setProgressChacheDir(sys_get_temp_dir(), false);
    }

    public function __destruct()
    {
        $this->saveProgress();
    }

    public function addFile($game, $file, $expectedSize)
    {
        if(!isset($this->progressData[$game]))
        {
            $this->progressData[$game] = array();
        }
        if(!isset($this->progressData[$game][$file]))
        {
            $this->progressData[$game][$file] = array();
        }

        $this->progressData[$game][$file]['expectedSize'] = $expectedSize;

        return $this;
    }

    public function removeGame($game)
    {
        unset($this->progressData[$game]);
        return $this;
    }

    /**
     * 
     * @return \Gogdl\ProgressServer
     */
    public function loadProgress()
    {
        if(empty($this->progressData))
        {
            $progressFile       = $this->getProgressChacheFile();
            $progressData       = !is_file($progressFile) ? array() : json_decode(file_get_contents($progressFile), true);
            $this->progressData = $progressData;
        }
        $this->updateProgress();
        return $this;
    }

    /**
     * 
     * @return \Gogdl\ProgressServer
     */
    protected function updateProgress()
    {
        foreach($this->progressData as $game => $files)
        {
            foreach($files as $file => $meta)
            {
                $current                                         = filesize($file);
                $this->progressData[$game][$file]['currentSize'] = $current;
            }
        }
        return $this;
    }

    /**
     * Saves progress to file
     * @return \Gogdl\ProgressServer
     */
    public function saveProgress()
    {
        file_put_contents($this->getProgressChacheFile(), json_encode($this->getProgressData()));
        return $this;
    }

    /**
     * 
     * @return string
     */
    public function getProgressChacheFile()
    {
        return $this->progressChacheDir . '/' . static::progress_cache_file;
    }

    /**
     * 
     * @param string $progressChacheDir
     * @param boolean $reload
     * @return \Gogdl\ProgressServer
     */
    public function setProgressChacheDir($progressChacheDir, $reload = true)
    {
        $this->progressChacheDir = $progressChacheDir;

        if($reload)
        {
            $this->loadProgress();
        }

        return $this;
    }

    /**
     * 
     * @return array
     */
    public function getProgressData()
    {
        return $this->progressData;
    }

}
