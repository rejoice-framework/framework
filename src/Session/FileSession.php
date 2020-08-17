<?php

/*
 * This file is part of the Rejoice package.
 *
 * (c) Prince Dorcis <princedorcis@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Rejoice\Session;

use Rejoice\Foundation\Kernel;
use Rejoice\Session\Session;

require_once __DIR__.'/../../constants.php';

/**
 * Handles file session storage
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 */
class FileSession extends Session implements SessionInterface
{
    protected $file = null;

    public function __construct(Kernel $app)
    {
        parent::__construct($app);

        $this->id = trim($this->msisdn, '+');
        $this->file = $this->generateSessionFileName($app);
        $this->start();
    }

    public function generateSessionFileName($app)
    {
        $filename = \hash('sha256', base64_encode($this->id));

        if (!is_dir($app->path('session_root_dir'))) {
            mkdir($app->path('session_root_dir'));
        }

        return $app->path('session_root_dir').$filename;
    }

    public function delete()
    {
        if ($this->file && file_exists($this->file)) {
            unlink($this->file);
        }
    }

    public function hardReset()
    {
        file_put_contents($this->file, '{}');
    }

    public function retrievePreviousData()
    {
        $this->data = $this->retrieveData();

        if (!empty($this->data)) {
            // $this->delete();
            $this->data['id'] = $this->app->sessionId();
            $this->save();
        }

        return $this->data;
    }

    public function retrieveData()
    {
        if (!file_exists($this->file)) {
            return [];
        }

        $jsonData = file_get_contents($this->file);
        $data = ('' !== $jsonData) ?
        json_decode($jsonData, true) : [];

        return $data;
    }

    public function previousSessionNotExists()
    {
        if (file_exists($this->file)) {
            $data = file_get_contents($this->file);

            return empty(json_decode($data, true));
        }

        return false;
    }

    public function save()
    {
        $data = $this->app->isDevEnv() ? json_encode($this->data, JSON_PRETTY_PRINT) : json_encode($this->data);

        return file_put_contents($this->file, $data);
    }

    public function file()
    {
        return $this->file;
    }
}
