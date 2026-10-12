<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\Borrowing;
use App\Models\Member;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboard', [
            'totalTitles' => Book::count(),
            'totalStock' => (int) Book::sum('stock'),
            'totalMembers' => Member::count(),
            'totalBorrowed' => Borrowing::where('status', 'borrowed')->count(),
        ]);
    }
}