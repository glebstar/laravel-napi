<?php

class NoteTest extends TestCase
{
    /**
     * Test user email
     *
     * @var string
     */
    private static $email = '';

    /**
     * Test user password
     *
     * @var string
     */
    private static $password = '123456';

    /**
     * Auth Token
     *
     * @var string
     */
    private static $apiToken = '';

    /**
     * Create a new user
     *
     * @return void
     */
    public function testRegister()
    {
        $response = $this->call ('POST', 'api/register');
        $this->assertEquals ('The name field is required.', json_decode ($response->content ())->name[0]);
        self::$email = str_random ('5') . '@gmail.com';

        $response = $this->call ('POST', 'api/register', [
            'name'                  => 'Test',
            'email'                 => self::$email,
            'password'              => self::$password,
            'password_confirmation' => self::$password,
        ]);

        $response = json_decode ($response->content ());
        $this->assertEquals (true, isset( $response->token ));
    }

    /**
     * Testing note actions
     *
     * @return void
     */
    public function testNote()
    {
        $response = $this->call ('POST', 'api/login', [
            'email'                 => self::$email,
            'password'              => self::$password,
        ]);

        self::$apiToken = json_decode ($response->content ())->token;

        // added new note
        $response = $this->call ('POST', '/api/note?token=' . self::$apiToken, [
            'note'      => 'test note',
        ]);
        $this->assertEquals ('test note', json_decode ($response->content ())->text);

        $noteId = json_decode ($response->content ())->id;

        //try added bad attache
        $this->assertEquals (400, $this->call ('POST', '/api/note/addfile/' . $noteId . '?token=' . self::$apiToken)->getStatusCode ());

        // added atache
        copy (__DIR__ . '/_files/_test.jpg', __DIR__ . '/_files/test.jpg');
        $file = new \Illuminate\Http\UploadedFile (__DIR__ . '/_files/test.jpg', 'test.jpg', 'image/jpeg', 104511, 0, true);

        $response = $this->call ('POST', '/api/note/addfile/' . $noteId . '?token=' . self::$apiToken, [], [], ['attache' => $file]);
        $this->assertEquals ($noteId . '.jpg', json_decode ($response->content ())->file);

        // try get unknown note
        $this->assertEquals (404, $this->call ('GET', '/api/note/0?token=' . self::$apiToken)->getStatusCode ());

        // get one note for id
        $response = $this->call ('GET', '/api/note/' . $noteId . '?token=' . self::$apiToken);
        $this->assertEquals ($noteId, json_decode ($response->content ())->id);

        // added second note
        $this->call ('POST', '/api/note?token=' . self::$apiToken, [
            'note'      => 'test note 2',
        ]);

        // get all notes
        $response = $this->call ('GET', '/api/note?token=' . self::$apiToken);
        $this->assertEquals (2, count (json_decode ($response->content ())));

        // delete one note
        $this->assertEquals (200, $this->call ('POST', '/api/note/' . $noteId . '?token=' . self::$apiToken, ['_method'   => 'DELETE'])->getStatusCode ());

        // get all notes
        $response = $this->call ('GET', '/api/note?token=' . self::$apiToken);
        $this->assertEquals (1, count (json_decode ($response->content ())));

        // try restore unknown note
        $this->assertEquals (404, $this->call ('GET', '/api/note/restore/0?token=' . self::$apiToken)->getStatusCode ());

        // restore note
        $this->assertEquals (200, $this->call ('GET', '/api/note/restore/' . $noteId . '?token=' . self::$apiToken)->getStatusCode ());

        // get all notes
        $response = $this->call ('GET', '/api/note?token=' . self::$apiToken);
        $this->assertEquals (2, count (json_decode ($response->content ())));
    }
}
