<?php

namespace OAuth2Server\Test;

use OAuth2Server\ClientManager;
use Doctrine\DBAL\DriverManager;

class ClientManagerTest extends AbstractDbTestCase
{
    /** @var ClientManager */
    protected $cm;

    public function setUp()
    {
        $dbal = DriverManager::getConnection(array('pdo' => $this->getPdo()));
        $this->cm = new ClientManager($dbal);

        parent::setUp();
    }

    public function testGetClient()
    {
        $id = 123;
        $secret = 'testsecret';
        $redirectUri = 'http://example.com';
        $name = 'Test client name';

        // Set up database fixture
        $stmt = $this->getPdo()->prepare('INSERT INTO oauth_clients (id, name, secret) VALUES (:id, :name, :secret)');
        $stmt->execute(array(':id' => $id, ':name' => $name, ':secret' => $secret));
        $stmt = $this->getPdo()->prepare('INSERT INTO oauth_client_endpoints (client_id, redirect_uri) VALUES (:clientId, :redirectUri)');
        $stmt->execute(array(':clientId' => $id, ':redirectUri' => $redirectUri));

        // Client ID & secret.
        $this->assertEquals(array('client_id' => $id, 'client_secret' => $secret, 'name' => $name),
            $this->cm->getClient($id, $secret));

        // Client ID & redirect URI, also returns redirect URI.
        $this->assertEquals(array('client_id' => $id, 'client_secret' => $secret, 'name' => $name, 'redirect_uri' => $redirectUri),
            $this->cm->getClient($id, null, $redirectUri));

        // Client ID, secret, & redirect URI also returns redirect URI.
        $this->assertEquals(array('client_id' => $id, 'client_secret' => $secret, 'name' => $name, 'redirect_uri' => $redirectUri),
            $this->cm->getClient($id, $secret, $redirectUri));

        // Returns false if any params are invalid.
        $this->assertFalse($this->cm->getClient());
        $this->assertFalse($this->cm->getClient('invalid-id'));
        $this->assertFalse($this->cm->getClient($id, 'invalid-secret'));
        $this->assertFalse($this->cm->getClient($id, null, 'invalid-redirect-uri'));
        $this->assertFalse($this->cm->getClient($id, $secret, 'invalid-redirect-uri'));
    }
}