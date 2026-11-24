<?php

namespace App\Http\Controllers;

use App\Models\Note;
use Illuminate\View\View;

class NoteController extends Controller
{
    /**
     * 食记广场：厨友的下厨记录，新的在前
     */
    public function index(): View
    {
        $notes = Note::query()
            ->with('user')
            ->withCount('comments')
            ->latest('id')
            ->paginate(9);

        return view('notes.index', compact('notes'));
    }

    /**
     * 食记详情：正文 + 厨友评论
     */
    public function show(Note $note): View
    {
        $comments = $note->comments()->with('user')->get();

        return view('notes.show', compact('note', 'comments'));
    }
}
