<?php
namespace Prinx\Rejoice;

interface SessionInterface
{
    public function isPrevious();

    public function delete();

    public function resetData();

    public function retrievePreviousData();

    public function retrieveData();

    public function previousSessionNotExists();

    public function save($data);
}
