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
                ->where ('is_deleted', 0)
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
        $note = $request->input ('note');

        return Note::create ([
            'text'    => $note,
            'user_id' => \JWTAuth::parseToken()->authenticate()->id,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $note = Note::where ('id', $id)
                    ->where ('user_id', \JWTAuth::parseToken()->authenticate()->id)
                    ->where ('is_deleted', 0)
                    ->first ();

        if (! $note) {
            return response ()->json (['error' => 'not found'], 404);
        }

        return response ()->json ($note);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        Note::where ('id', $id)
            ->where ('user_id', \JWTAuth::parseToken()->authenticate()->id)
            ->update (['is_deleted' => 1]);

        return response ()->json (['deleted' => $id]);
    }

    /**
     * Recovers deleted note
     *
     * @param $id Note ID
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {
        $note = Note::where ('id', $id)
            ->where ('user_id', \JWTAuth::parseToken()->authenticate()->id)
            ->first ();

        if (! $note) {
            return response ()->json (['error' => 'not found'], 404);
        }

        $note->is_deleted = 0;
        $note->save ();

        return response ()->json ($note);
    }

    /**
     * Adds a file to a note
     *
     * @param         $id      Note ID
     * @param Request $request Request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function addfile($id, Request $request)
    {
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

        $note = Note::where ('id', $id)->where ('user_id', \JWTAuth::parseToken()->authenticate()->id)->first ();

        $note->file = $fileName;
        $note->save ();

        return response ()->json ($note);
    }
}
