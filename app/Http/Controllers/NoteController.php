<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;
use Auth;
use App\Note;
use Validator;

class NoteController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $page = (int)$request->input ('page');
        if ($page < 1) {
            $page = 1;
        }

        $limit  = env ('NOTE_PAGE_LIMIT');
        $offset = ( $page - 1 ) * $limit;

        return response ()->json (
            Note::where ('user_id', \JWTAuth::parseToken()->authenticate()->id)
                ->skip ($offset)->take ($limit)
                ->get ()
        );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make ($request->all (), [
            'note' => 'required',
        ]);

        if ($validator->fails ()) {
            return response ()->json ($validator->messages (), 400);
        }

        $note = $request->input ('note');

        return Note::create ([
            'text'    => $note,
            'user_id' => \JWTAuth::parseToken()->authenticate()->id,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param integer $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $note = Note::where ('id', $id)
                    ->first ();

        if (! $note) {
            return response ()->json (['error' => 'not found'], 404);
        }

        if ($note->user_id != \JWTAuth::parseToken()->authenticate()->id) {
            return response ()->json (['error' => 'not access'], 401);
        }

        return response ()->json ($note);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param integer $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $note = Note::where ('id', $id)
                    ->first ();

        if(! $note) {
            return response ()->json (['error' => 'not found'], 404);
        }

        if ($note->user_id != \JWTAuth::parseToken()->authenticate()->id) {
            return response ()->json (['error' => 'not access'], 401);
        }

        $note->delete();
        return response ()->json (['deleted' => $id]);
    }

    /**
     * Recovers deleted note
     *
     * @param integer $id Note ID
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {
        $note = Note::withTrashed()
                ->where ('id', $id)
                ->first ();

        if (! $note) {
            return response ()->json (['error' => 'not found'], 404);
        }

        if ($note->user_id != \JWTAuth::parseToken()->authenticate()->id) {
            return response ()->json (['error' => 'not access'], 401);
        }

        $note->restore();
        return response ()->json ($note);
    }

    /**
     * Adds a file to a note
     *
     * @param integer $id      Note ID
     * @param Request $request Request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function addfile($id, Request $request)
    {
        $note = Note::where ('id', $id)
                    ->first ();

        if (! $note) {
            return response ()->json (['error' => 'not found'], 404);
        }

        if ($note->user_id != \JWTAuth::parseToken()->authenticate()->id) {
            return response ()->json (['error' => 'not access'], 401);
        }

        $validator = Validator::make ($request->all (), [
            'attache' => 'required|mimes:jpeg,png',
        ]);

        if ($validator->fails ()) {
            return response ()->json ($validator->messages (), 400);
        }

        $attache  = $request->file ('attache');
        $fileName = $id . '.' . $attache->getClientOriginalExtension ();

        if (file_exists (base_path () . env ('NOTE_FILE_DIR') . $fileName)) {
            unlink (base_path () . env ('NOTE_FILE_DIR') . $fileName);
        }

        $attache->move (base_path () . env ('NOTE_FILE_DIR'), $fileName);

        $note->file = $fileName;
        $note->save ();

        return response ()->json ($note);
    }
}
