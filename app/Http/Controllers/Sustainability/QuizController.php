<?php

namespace App\Http\Controllers\Sustainability;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class QuizController extends Controller
{
    public function submit(Request $request)
    {
        $validated = $request->validate([
            'answer' => ['required', 'string', 'max:40'],
        ]);

        $correct = $validated['answer'] === 'certified';

        return response()->json([
            'correct' => $correct,
            'points_preview' => $correct ? 20 : 0,
            'message' => $correct
                ? 'Correct. Certified repair, donation, or recycling channels prevent toxic exposure.'
                : 'Not quite. Regular trash and open burning are unsafe for electronics.',
        ]);
    }
}
