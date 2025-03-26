<?php
namespace App\Repository;

interface IContactRepository
{
    public function createContact(array $data);
    public function createGroup(array $data);
    public function addContact($contactId);
    public function addGroup($groupId);
}