<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Habits;
use App\Models\HabitLog;
use Carbon\Carbon;
use Illuminate\Http\Request;

class habitLogController extends Controller
{
    public function habitLog(Request $request)
    {
        $habitId = $request->query('habit_id');
        $userId = $request->user()->id;

        if ($habitId) {
            $habit = Habits::where('id', $habitId)->where('user_id', $userId)->first();
            if (!$habit) {
                return response()->json([
                    'success' => false,
                    'errors' => ['habit' => ['Habit not found']],
                    'message' => 'Not found'
                ], 404);
            }

            $logs = HabitLog::where('habit_id', $habit->id)->orderBy('date', 'desc')->get();
            return response()->json([
                'success' => true,
                'data' => $logs,
                'message' => 'Logs fetched'
            ]);
        }

        $habitIds = Habits::where('user_id', $userId)->pluck('id');
        $logs = HabitLog::whereIn('habit_id', $habitIds)->orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
            'message' => 'Logs fetched'
        ]);
    }

    public function index(Request $request, $habitId)
    {
        $habit = $this->findHabit($request, $habitId);
        if (!$habit) {
            return response()->json([
                'success' => false,
                'errors' => ['habit' => ['Habit not found']],
                'message' => 'Not found'
            ], 404);
        }

        $logs = HabitLog::where('habit_id', $habit->id)->orderBy('date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $logs,
            'message' => 'Logs fetched'
        ]);
    }

    public function store(Request $request, $habitId)
    {
        $habit = $this->findHabit($request, $habitId);
        if (!$habit) {
            return response()->json([
                'success' => false,
                'errors' => ['habit' => ['Habit not found']],
                'message' => 'Not found'
            ], 404);
        }

        $incomingFields = $request->validate([
            'note' => 'nullable|string',
            'date' => 'nullable|date'
        ]);

        $date = isset($incomingFields['date'])
            ? Carbon::parse($incomingFields['date'])->toDateString()
            : Carbon::today()->toDateString();

        $alreadyLogged = HabitLog::where('habit_id', $habit->id)
            ->where('date', $date)
            ->exists();

        if ($alreadyLogged) {
            return response()->json([
                'success' => false,
                'errors' => ['date' => ['Already logged for this date']],
                'message' => 'Validation error'
            ], 422);
        }

        $log = new HabitLog($incomingFields);
        $log->habit_id = $habit->id;
        $log->date = $date;
        $log->save();

        return response()->json([
            'success' => true,
            'data' => $log,
            'message' => 'Log created'
        ], 201);
    }

    public function destroy(Request $request, $habitId, $logId)
    {
        $habit = $this->findHabit($request, $habitId);
        if (!$habit) {
            return response()->json([
                'success' => false,
                'errors' => ['habit' => ['Habit not found']],
                'message' => 'Not found'
            ], 404);
        }

        $log = HabitLog::where('habit_id', $habit->id)->where('id', $logId)->first();
        if (!$log) {
            return response()->json([
                'success' => false,
                'errors' => ['log' => ['Log not found']],
                'message' => 'Not found'
            ], 404);
        }

        $log->delete();

        return response()->json([
            'success' => true,
            'data' => null,
            'message' => 'Log deleted'
        ]);
    }

    public function stats(Request $request, $habitId)
    {
        $habit = $this->findHabit($request, $habitId);
        if (!$habit) {
            return response()->json([
                'success' => false,
                'errors' => ['habit' => ['Habit not found']],
                'message' => 'Not found'
            ], 404);
        }

        $logs = HabitLog::where('habit_id', $habit->id)->orderBy('date', 'asc')->get();
        $dates = $logs->pluck('date')->map(function ($d) {
            return Carbon::parse($d)->toDateString();
        })->unique()->values();

        $totalCompletions = $dates->count();
        $currentStreak = $this->calculateCurrentStreak($dates);
        $longestStreak = $this->calculateLongestStreak($dates);
        $completionRate = $this->calculateCompletionRate($dates);

        return response()->json([
            'success' => true,
            'data' => [
                'current_streak' => $currentStreak,
                'longest_streak' => $longestStreak,
                'total_completions' => $totalCompletions,
                'completion_rate' => $completionRate
            ],
            'message' => 'Stats fetched'
        ]);
    }

    public function overview(Request $request)
    {
        $userId = $request->user()->id;
        $habits = Habits::where('user_id', $userId)->get();
        $habitIds = $habits->pluck('id');

        $totalHabits = $habits->count();
        $activeHabits = $habits->where('is_active', true)->count();

        $logs = HabitLog::whereIn('habit_id', $habitIds)->get();
        $dates = $logs->pluck('date')->map(function ($d) {
            return Carbon::parse($d)->toDateString();
        });

        $totalCompletions = $dates->count();
        $completionRate = $this->calculateCompletionRate($dates->unique()->values(), max($totalHabits, 1));

        return response()->json([
            'success' => true,
            'data' => [
                'total_habits' => $totalHabits,
                'active_habits' => $activeHabits,
                'total_completions' => $totalCompletions,
                'completion_rate' => $completionRate
            ],
            'message' => 'Overview fetched'
        ]);
    }

    private function findHabit(Request $request, $habitId)
    {
        return Habits::where('id', $habitId)
            ->where('user_id', $request->user()->id)
            ->first();
    }

    private function calculateCurrentStreak($dates)
    {
        if ($dates->isEmpty()) {
            return 0;
        }

        $today = Carbon::today()->toDateString();
        $lastDate = $dates->last();
        if ($lastDate !== $today) {
            return 0;
        }

        $streak = 0;
        $current = Carbon::parse($today);
        $dateSet = $dates->flip();

        while ($dateSet->has($current->toDateString())) {
            $streak++;
            $current->subDay();
        }

        return $streak;
    }

    private function calculateLongestStreak($dates)
    {
        if ($dates->isEmpty()) {
            return 0;
        }

        $longest = 1;
        $current = 1;

        for ($i = 1; $i < $dates->count(); $i++) {
            $prev = Carbon::parse($dates[$i - 1]);
            $curr = Carbon::parse($dates[$i]);

            if ($prev->diffInDays($curr) === 1) {
                $current++;
                $longest = max($longest, $current);
            } else {
                $current = 1;
            }
        }

        return $longest;
    }

    private function calculateCompletionRate($dates, $habitCount = 1)
    {
        $start = Carbon::today()->subDays(29)->toDateString();
        $recentCount = $dates->filter(function ($d) use ($start) {
            return $d >= $start;
        })->count();

        $denominator = 30 * $habitCount;
        if ($denominator === 0) {
            return 0;
        }

        return round(($recentCount / $denominator) * 100, 2);
    }
}
