<?php

class NoteTest extends TestCase
{
    /**
     * Create a new user.
     *
     * @return array
     */
    public function testRegister()
    {
        $response = $this->call ('POST', 'api/register');
        $this->assertEquals ('The name field is required.', json_decode ($response->content ())->name[0]);

        $faker = Faker\Factory::create();

        $email = $faker->email;
        $password = '123456';

        $response = $this->call ('POST', 'api/register', [
            'name'                  => 'Test',
            'email'                 => $email,
            'password'              => $password,
            'password_confirmation' => $password,
        ]);

        $response = json_decode ($response->content ());
        $this->assertTrue (isset( $response->token ));

        return [
            'email' => $email,
            'password' => $password,
        ];
    }

    /**
     * User authentication.
     *
     * @param array $credentials Email and password
     *
     * @depends testRegister
     *
     * @return string
     */
    public function testAuth(array $credentials)
    {
        // not valid password
        $response = $this->call ('POST', 'api/login', [
            'email'                 => $credentials['email'],
            'password'              => '--',
        ]);

        $this->assertEquals (401, $response->getStatusCode ());

        // valid auth
        $response = $this->call ('POST', 'api/login', [
            'email'                 => $credentials['email'],
            'password'              => $credentials['password'],
        ]);

        return json_decode ($response->content ())->token;
    }

    /**
     * Added new note.
     *
     * @param string $token
     *
     * @depends testAuth
     *
     * @return array
     */
    public function testAddNote($token)
    {
        // try added empty note
        $response = $this->call ('POST', '/api/note?token=' . $token, [
            'note'      => '',
        ]);
        $this->assertEquals (400, $response->getStatusCode ());

        $faker = Faker\Factory::create();
        $noteText = $faker->text;

        $response = $this->call ('POST', '/api/note?token=' . $token, [
            'note'      => $noteText,
        ]);
        $this->assertEquals ($noteText, json_decode ($response->content ())->text);

        return [
            'token' => $token,
            'noteId' => json_decode ($response->content ())->id,
        ];
    }

    /**
     * Added attache for note.
     *
     * @param array $params token and noteId
     *
     * @depends testAddNote
     *
     * @return void
     */
    public function testAddAttacheForNote(array $params)
    {
        // try added atache for unknown note
        $this->assertEquals (404, $this->call ('POST', '/api/note/addfile/0?token=' . $params['token'])->getStatusCode ());

        //try added bad attache
        $this->assertEquals (400, $this->call ('POST', '/api/note/addfile/' . $params['noteId'] . '?token=' . $params['token'])
            ->getStatusCode ());

        // added atache
        copy (__DIR__ . '/_files/_test.jpg', __DIR__ . '/_files/test.jpg');
        $file = new \Illuminate\Http\UploadedFile (__DIR__ . '/_files/test.jpg', 'test.jpg', 'image/jpeg', 104511, 0, true);

        $response = $this->call ('POST', '/api/note/addfile/' . $params['noteId'] . '?token=' . $params['token'], [], [], ['attache' => $file]);
        $this->assertEquals ($params['noteId'] . '.jpg', json_decode ($response->content ())->file);
    }

    /**
     * Get one note.
     *
     * @param array $params token and noteId
     *
     * @depends testAddNote
     *
     * @return void
     */
    public function testGetOneNote(array $params)
    {
        // try get unknown note
        $this->assertEquals (404, $this->call ('GET', '/api/note/0?token=' . $params['token'])->getStatusCode ());

        // get one note for id
        $response = $this->call ('GET', '/api/note/' . $params['noteId'] . '?token=' . $params['token']);
        $this->assertEquals ($params['noteId'], json_decode ($response->content ())->id);
    }

    /**
     * Get all notes.
     *
     * @param array $params token and noteId
     *
     * @depends testAddNote
     *
     * @return void
     */
    public function testGetNotes(array $params)
    {
        // added second note
        $this->call ('POST', '/api/note?token=' . $params['token'], [
            'note'      => 'test note 2',
        ]);

        // get all notes
        $response = $this->call ('GET', '/api/note?token=' . $params['token']);
        $this->assertEquals (2, count (json_decode ($response->content ())));
    }

    /**
     * Delete Note.
     *
     * @param array $params token and noteId
     *
     * @depends testAddNote
     *
     * @return void
     */
    public function testDeleteNote(array $params)
    {
        // delete one note
        $this->assertEquals (200, $this->call ('POST', '/api/note/' . $params['noteId'] . '?token=' . $params['token'], ['_method'   => 'DELETE'])
            ->getStatusCode ());

        // get all notes
        $response = $this->call ('GET', '/api/note?token=' . $params['token']);
        $this->assertEquals (1, count (json_decode ($response->content ())));
    }

    /**
     * Restore Note.
     *
     * @param array $params token and noteId
     *
     * @depends testAddNote
     *
     * @return void
     */
    public function testRestoreNote(array $params)
    {
        // try restore unknown note
        $this->assertEquals (404, $this->call ('GET', '/api/note/restore/0?token=' . $params['token'])->getStatusCode ());

        // restore note
        $this->assertEquals (200, $this->call ('GET', '/api/note/restore/' . $params['noteId'] . '?token=' . $params['token'])
            ->getStatusCode ());

        // get all notes
        $response = $this->call ('GET', '/api/note?token=' . $params['token']);
        $this->assertEquals (2, count (json_decode ($response->content ())));
    }
}
