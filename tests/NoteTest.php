<?php

class NoteTest extends TestCase
{
    /**
     * API Token
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

        $response = $this->call ('POST', 'api/register', [
            'name'                  => 'Test',
            'email'                 => str_random ('5') . '@gmail.com',
            'password'              => '123456',
            'password_confirmation' => '123456',
        ]);

        self::$apiToken = json_decode ($response->content ())->api_token;

        $this->assertEquals (60, strlen (self::$apiToken));
    }

    /**
     * Testing note actions
     *
     * @return void
     */
    public function testNote()
    {
        // added new note
        $response = $this->call ('POST', '/api/note', [
            'note'      => 'test note',
            'api_token' => self::$apiToken,
        ]);

        $this->assertEquals ('test note', json_decode ($response->content ())->text);

        $noteId = json_decode ($response->content ())->id;

        // added atache
        copy (__DIR__ . '/_files/_test.jpg', __DIR__ . '/_files/test.jpg');
        $file = new \Illuminate\Http\UploadedFile (__DIR__ . '/_files/test.jpg', 'test.jpg', 'image/jpeg', 104511, 0, true);

        $response = $this->call ('POST', '/api/note/addfile/' . $noteId, [
            'api_token' => self::$apiToken,
        ], [], ['attache' => $file]);

        $this->assertEquals ($noteId . '.jpg', json_decode ($response->content ())->file);

        // get one note for id
        $response = $this->call ('GET', '/api/note/' . $noteId, [
            'api_token' => self::$apiToken,
        ]);

        $this->assertEquals ($noteId, json_decode ($response->content ())->id);

        // added second note
        $response = $this->call ('POST', '/api/note', [
            'note'      => 'test note 2',
            'api_token' => self::$apiToken,
        ]);

        // get all notes
        $response = $this->call ('GET', '/api/note/', [
            'api_token' => self::$apiToken,
        ]);

        $this->assertEquals (2, count (json_decode ($response->content ())));

        // delete one note
        $response = $this->call ('POST', '/api/note/' . $noteId, [
            '_method'   => 'DELETE',
            'api_token' => self::$apiToken,
        ]);

        // get all notes
        $response = $this->call ('GET', '/api/note/', [
            'api_token' => self::$apiToken,
        ]);

        $this->assertEquals (1, count (json_decode ($response->content ())));

        // restore note
        $response = $this->call ('GET', '/api/note/restore/' . $noteId, [
            'api_token' => self::$apiToken,
        ]);

        // get all notes
        $response = $this->call ('GET', '/api/note/', [
            'api_token' => self::$apiToken,
        ]);

        $this->assertEquals (2, count (json_decode ($response->content ())));
    }
}
