<?php
/**
 * Handle the USSD Session: save and retrieve the session data from the database
 *
 * @author Prince Dorcis <princedorcis@gmail.com>
 * @license MIT
 */

namespace Prinx\Rejoice;

require_once 'constants.php';
require_once 'Database.php';
require_once 'Session.php';
require_once 'SessionInterface.php';
// use Session;
// use SessionInterface;

class DatabaseSession extends Session implements SessionInterface
{
    protected $db;

    protected $table_name;
    protected $table_name_suffix = '_ussd_sessions';

    public function __construct($ussd_lib)
    {
        parent::__construct($ussd_lib);

        $this->table_name = strtolower($ussd_lib->id()) . $this->table_name_suffix;

        $this->loadDB();

        if ($ussd_lib->appParams()['environment'] !== PROD) {
            $this->createSessionTableIfNotExists();
        }

        $this->start();
    }

    public function loadDB()
    {
        $this->db = Database::loadSessionDB();
    }

    private function createSessionTableIfNotExists()
    {
        $sql = "CREATE TABLE IF NOT EXISTS `$this->table_name`(
                  `id` INT(11) NOT NULL AUTO_INCREMENT,
                  `msisdn` VARCHAR(20) NOT NULL,
                  `session_id` VARCHAR(50) NOT NULL,
                  `ddate` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                  `session_data` TEXT,
                  PRIMARY KEY (`id`),
                  UNIQUE KEY `NewIndex1` (`msisdn`),
                  UNIQUE KEY `NewIndex2` (`session_id`)
                ) ENGINE=INNODB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4;";

        $result = $this->db->query($sql);
        $result->closeCursor();
    }

    public function delete()
    {
        $sql = "DELETE FROM $this->table_name WHERE msisdn = :msisdn";
        $result = $this->db->prepare($sql);
        $result->execute(['msisdn' => $this->msisdn]);
        $result->closeCursor();
    }

    public function resetData()
    {
        $sql = "UPDATE $this->table_name SET session_data=null WHERE msisdn = :msisdn";
        $result = $this->db->prepare($sql);
        $result->execute(['msisdn' => $this->msisdn]);
        $result->closeCursor();
    }

    public function retrievePreviousData()
    {
        $data = $this->retrieveData();

        if (!empty($data)) {
            $this->updateId();
        }

        return $data;
    }

    public function retrieveData()
    {
        $sql = "SELECT (session_data) FROM $this->table_name WHERE msisdn = :msisdn";

        $req = $this->db->prepare($sql);
        $req->execute(['msisdn' => $this->msisdn]);

        $result = $req->fetchAll(\PDO::FETCH_ASSOC);
        $req->closeCursor();

        if (empty($result)) {
            return [];
        }

        $session_data = $result[0]['session_data'];

        $data = ($session_data !== '') ? json_decode($session_data, true) : [];

        // var_dump($data);
        return $data;
    }

    public function updateId()
    {
        $req = $this->db
            ->prepare("UPDATE $this->table_name SET session_id = :session_id WHERE msisdn = :msisdn");

        $req->execute([
            'session_id' => $this->id,
            'msisdn' => $this->msisdn,
        ]);

        return $req->closeCursor();
    }

    public function data()
    {
        return $this->data;
    }

    public function previousSessionNotExists()
    {
        $sql = "SELECT COUNT(*) FROM $this->table_name WHERE msisdn = :msisdn";
        $result = $this->db->prepare($sql);
        $result->execute(['msisdn' => $this->msisdn]);

        $nb_rows = (int) $result->fetchColumn();

        $result->closeCursor();

        return $nb_rows <= 0;
    }

    public function save($data = [])
    {
        if ($this->previousSessionNotExists()) {
            return $this->createDataRecord($data);
        }

        return $this->updateDataRecord($data);
    }

    public function createDataRecord($data)
    {
        $sql = "INSERT INTO $this->table_name (session_data, msisdn, session_id) VALUES (:session_data, :msisdn, :session_id)";

        $result = $this->db->prepare($sql);
        $result->execute([
            'session_data' => json_encode($data),
            'msisdn' => $this->msisdn,
            'session_id' => $this->id,
        ]);

        return $result->closeCursor();
    }

    public function updateDataRecord($data)
    {
        $sql = "UPDATE $this->table_name SET session_data = :session_data WHERE msisdn = :msisdn";

        $result = $this->db->prepare($sql);

        $result->execute([
            'session_data' => json_encode($data),
            'msisdn' => $this->msisdn,
        ]);

        return $result->closeCursor();
    }
}
