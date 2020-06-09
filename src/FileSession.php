<?php
namespace Prinx\Rejoice;

require_once 'constants.php';
require_once 'Session.php';
require_once 'SessionInterface.php';
// use Session;
// use SessionInterface;

class FileSession extends Session implements SessionInterface
{
    protected $storage = '/../../../../storage/sessions/';
    protected $file;

    public function __construct($ussd_lib)
    {
        parent::__construct($ussd_lib);

        $this->id = trim($this->msisdn, '+');
        $this->file = realpath(__DIR__ . $this->storage) . '/' . $this->id;
        $this->start();
    }

    public function delete()
    {
        unlink($this->file);
    }

    public function resetData()
    {
        file_put_contents($this->file, '{}');
    }

    public function retrievePreviousData()
    {
        $data = $this->retrieveData();

        if (!empty($data)) {
            // $this->delete();
            $data['id'] = $this->ussd_lib->sessionId();
            $this->save($data);
        }

        return $data;
    }

    public function retrieveData()
    {
        if (!file_exists($this->file)) {
            return [];
        }

        $jsonData = file_get_contents($this->file);
        $data = ($jsonData !== '') ?
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

    public function save($data = [])
    {
        return file_put_contents($this->file, json_encode($data));
    }
}
