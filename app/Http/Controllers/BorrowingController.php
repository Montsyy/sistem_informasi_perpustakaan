<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BorrowingController extends Controller
{
    public function index(Request $request)
    {
        $borrowings = Borrowing::with(['member', 'user'])
            ->when($request->status, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('borrowings.index', compact('borrowings'));
    }

    public function create()
    {
        return view('borrowings.create', [
            'members' => Member::orderBy('name')->get(),
            'books' => Book::where('stock', '>', 0)->orderBy('title')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'book_ids' => ['required', 'array', 'min:1'],
            'book_ids.*' => ['required', 'integer', 'distinct', 'exists:books,id'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $books = Book::whereIn('id', $data['book_ids'])->lockForUpdate()->get();

            foreach ($books as $book) {
                if ($book->stock < 1) {
                    throw ValidationException::withMessages([
                        'book_ids' => "Stok buku \"{$book->title}\" habis.",
                    ]);
                }
            }

            $borrowing = Borrowing::create([
                'user_id' => $request->user()->id,
                'member_id' => $data['member_id'],
                'borrowed_at' => now()->toDateString(),
                'due_date' => now()->addWeek()->toDateString(),
                'returned_at' => null,
                'status' => 'borrowed',
            ]);

            $borrowing->books()->attach($books->pluck('id'));
            Book::whereIn('id', $books->pluck('id'))->decrement('stock');
        });

        return redirect()->route('borrowings.index')->with('success', 'Peminjaman berhasil dicatat.');
    }

    public function show(Borrowing $borrowing)
    {
        $borrowing->load(['member', 'user', 'books.category']);
        return view('borrowings.show', compact('borrowing'));
    }

    public function returnBook(Borrowing $borrowing)
    {
        if ($borrowing->status === 'returned') {
            return back()->with('error', 'Buku sudah dikembalikan.');
        }

        DB::transaction(function () use ($borrowing) {
            $borrowing->update([
                'returned_at' => now()->toDateString(),
                'status' => 'returned',
            ]);

            Book::whereIn('id', $borrowing->books()->pluck('books.id'))->increment('stock');
        });

        return redirect()->route('borrowings.show', $borrowing)->with('success', 'Pengembalian berhasil dicatat.');
    }
}