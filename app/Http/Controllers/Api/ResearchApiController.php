<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\QuranicLensAnalysis;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class ResearchApiController extends Controller
{
    /**
     * Issue an API token to an existing user via email/password.
     */
    public function token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = strtolower(trim($request->email));
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json(['error' => 'Invalid email or password.'], 401);
        }

        if (empty($user->password)) {
            return response()->json(['error' => 'You registered via OTP and do not have a password set. Please log in via OTP first and set a password in your Profile.'], 400);
        }

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid email or password.'], 401);
        }

        $tokenName = $request->input('token_name', 'api-token');
        $token = $user->createToken($tokenName)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_researcher' => $user->is_researcher,
                'is_admin' => $user->is_admin,
            ]
        ]);
    }

    /**
     * Retrieve research content (approved/pending Quranic Lens Analyses).
     */
    public function index(Request $request)
    {
        $query = QuranicLensAnalysis::with(['user:id,name,display_name,email', 'theme:id,title,slug']);

        // Check if requester has researcher or admin access
        $user = Auth::guard('sanctum')->user();
        $isModerator = $user && ($user->is_researcher || $user->is_admin);

        // Status filter: defaults to approved unless moderator specifies otherwise
        if ($isModerator && $request->has('status')) {
            $status = $request->input('status');
            if (in_array($status, ['approved', 'pending', 'rejected'])) {
                $query->where('status', $status);
            }
        } else {
            $query->where('status', 'approved');
        }

        // Apply filters
        if ($request->has('lens_type')) {
            $query->where('lens_type', $request->input('lens_type'));
        }

        if ($request->has('chapter_number')) {
            $query->where('chapter_number', (int) $request->input('chapter_number'));
        }

        if ($request->has('verse_number')) {
            $query->where('verse_number', (int) $request->input('verse_number'));
        }

        if ($request->has('theme_id')) {
            $query->where('theme_id', (int) $request->input('theme_id'));
        }

        // Sort by newest
        $query->orderBy('created_at', 'desc');

        $perPage = min((int) $request->input('per_page', 15), 100);
        $analyses = $query->paginate($perPage);

        return response()->json($analyses);
    }

    /**
     * Submit a new research analysis.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'chapter_number' => 'required|integer|min:1|max:114',
            'verse_number' => 'required|integer|min:1',
            'lens_type' => 'required|string|in:tafsir,hadith,seerat,science,biology,maths,history,bible,torah,psychology',
            'title' => 'required|string|max:255',
            'content' => 'required|string|min:10',
            'theme_id' => 'nullable|integer|exists:themes,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $user = $request->user();
        $isModerator = $user->is_researcher || $user->is_admin;
        $status = $isModerator ? 'approved' : 'pending';

        $analysis = QuranicLensAnalysis::create([
            'user_id' => $user->id,
            'chapter_number' => $request->chapter_number,
            'verse_number' => $request->verse_number,
            'lens_type' => $request->lens_type,
            'title' => $request->title,
            'content' => $request->content,
            'theme_id' => $request->theme_id,
            'status' => $status,
        ]);

        return response()->json([
            'success' => true,
            'message' => $status === 'approved' 
                ? 'Your research analysis has been published successfully.' 
                : 'Your research analysis has been submitted and is currently pending review by a researcher.',
            'data' => $analysis->load(['user:id,name,display_name,email', 'theme:id,title,slug'])
        ], 201);
    }
}
