<?php

namespace App\Http\Controllers;

use App\Models\Note;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Inertia\Inertia;

class NoteController extends Controller
{
    public function index()
    {
        return Inertia::render("note.NoteIndex");
    }

    public function store(Request $request)
    {
        $request->validate([
            "note" => "required"
        ]);
        try {
            $note = new Note();
            $note->content = Crypt::encrypt($request->note);
            if (Auth::check()) {
                $note->created_by = Auth()->user()->id;
            }
            $note->save();
            $uuid = Str::random(5) . $note->id;
            $note->uuid = $uuid;
            $note->save();
            return to_route("detail", ['uuid' => $uuid]);
        } catch (\Exception $err) {
            abort(500, $err);
        }
    }

    public function details(string $uuid)
    {
        $note = Note::where("uuid", $uuid)->first();
        if (!$note) {
            abort(404, "Note Not Found");
        }
        $noteContent = Crypt::decrypt($note->content);
        $noteAuthID = null;
        if ($note->created_ny) {
            $noteAuthID = $note->created_by;
        }
        return Inertia::render("Note.NoteDetail", [
            "content" => $noteContent,
            "creator_id" => $noteAuthID
        ]);
    }

    public function update(string $uuid, Request $request)
    {
        $request->validate([
            "note" => "required"
        ]);
        $note = Note::where("uuid", $uuid)->first();
        if (!$note) {
            abort(404, "Note Not Found");
        }
        if (!Auth::check() || Auth::user()->id != $note->created_by) {
            return redirect()->back()->withErrors(['note' => 'Unauthorized. You are not the creator of this note.']);
        }
        $note->content = Crypt::encrypt($request->note);
        $note->save();
        return redirect()->back();
    }

    public function destroy(string $uuid)
    {
        $note = Note::where("uuid", $uuid)->first();
        if (!$note) {
            abort(404, "Note Not Found");
        }
        if (!Auth::check() || Auth::user()->id != $note->created_by) {
            return redirect()->back()->withErrors(['note' => 'Unauthorized. You are not the creator of this note.']);
        }
        Note::destroy($note->id);
        return to_route("home");
    }
}
